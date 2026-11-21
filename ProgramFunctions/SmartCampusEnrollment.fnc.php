<?php
/**
 * SmartCampus enrollment helpers, shared by the staff dashboard API.
 *
 * Turns an approved online application into a real RosarioSIS student
 * (students + student_enrollment rows) so the learner shows up in every
 * student list, attendance roll and report, and lists enrolled students
 * for the dashboard "Students" view.
 *
 * All queries use pg_query_params. Errors are thrown as RuntimeException
 * with an HTTP-style code (400/404/409/500) and a message safe to show staff.
 *
 * @package SmartCampus
 */

/**
 * Current school and school year for the logged-in staff session.
 *
 * @param resource|\PgSql\Connection $conn
 * @return array{school_id:int, syear:int}
 */
function SmartCampusSchoolContext( $conn ): array
{
	$syear = (int) ( $_SESSION['UserSyear'] ?? 0 );

	if ( ! $syear )
	{
		$syear = (int) ( getenv( 'DEFAULT_SYEAR' ) ?: date( 'Y' ) );
	}

	$school_id = (int) ( $_SESSION['UserSchool'] ?? 0 );

	if ( ! $school_id )
	{
		$result = pg_query_params(
			$conn,
			'SELECT id FROM schools WHERE syear = $1 ORDER BY id LIMIT 1',
			[ $syear ]
		);
		$row = $result ? pg_fetch_assoc( $result ) : false;
		$school_id = $row ? (int) $row['id'] : 0;
	}

	if ( ! $school_id )
	{
		throw new RuntimeException( 'No school is configured for school year ' . $syear . '.', 500 );
	}

	return [ 'school_id' => $school_id, 'syear' => $syear ];
}

/**
 * Map an application's free-text grade ("Grade 7", "7", "07", "Kinder")
 * to a school_gradelevels id for the given school.
 *
 * @return int|null Null when no grade level matches.
 */
function SmartCampusResolveGradeId( $conn, string $grade_level, int $school_id )
{
	$grade_level = trim( $grade_level );

	if ( $grade_level === '' )
	{
		return null;
	}

	$digits = preg_match( '/(\d{1,2})/', $grade_level, $m ) ? (string) (int) $m[1] : '';
	$is_kinder = stripos( $grade_level, 'kinder' ) !== false ? 't' : 'f';

	// Exact title/short name first, then number-based forms, then Kinder.
	$sql = "SELECT id FROM school_gradelevels
		WHERE school_id = $1
		AND (
			LOWER(title) = LOWER($2)
			OR LOWER(short_name) = LOWER($2)
			OR ( $3 <> '' AND (
				short_name IN ( $3, LPAD($3, 2, '0') )
				OR title ~* ( '^(grade\\s*)?0?' || $3 || '(st|nd|rd|th)?(\\s*grade)?$' )
			) )
			OR ( $4::boolean AND ( UPPER(short_name) IN ('K', 'KG') OR title ILIKE '%kinder%' ) )
		)
		ORDER BY
			CASE WHEN LOWER(title) = LOWER($2) OR LOWER(short_name) = LOWER($2) THEN 0 ELSE 1 END,
			sort_order, id
		LIMIT 1";

	$result = pg_query_params( $conn, $sql, [ $school_id, $grade_level, $digits, $is_kinder ] );
	$row = $result ? pg_fetch_assoc( $result ) : false;

	return $row ? (int) $row['id'] : null;
}

/**
 * Build a unique login name (first initial + last name) across students and staff.
 */
function SmartCampusUniqueUsername( $conn, string $first_name, string $last_name ): string
{
	$ascii = static function ( string $s ): string {
		$s = function_exists( 'iconv' ) ? (string) @iconv( 'UTF-8', 'ASCII//TRANSLIT', $s ) : $s;
		return strtolower( preg_replace( '/[^A-Za-z0-9]/', '', $s ) );
	};

	$base = substr( $ascii( $first_name ), 0, 1 ) . $ascii( $last_name );
	$base = substr( $base !== '' ? $base : 'student', 0, 90 );

	for ( $n = 0; $n < 1000; $n++ )
	{
		$candidate = $n === 0 ? $base : $base . $n;

		$result = pg_query_params(
			$conn,
			'SELECT 1 FROM students WHERE LOWER(username) = $1
			UNION ALL
			SELECT 1 FROM staff WHERE LOWER(username) = $1
			LIMIT 1',
			[ $candidate ]
		);

		if ( $result && pg_num_rows( $result ) === 0 )
		{
			return $candidate;
		}
	}

	throw new RuntimeException( 'Could not generate a unique username.', 500 );
}

/**
 * Split the application's name into first/last/middle/suffix.
 *
 * Prefers the structured columns (migration 006); falls back to splitting
 * learner_name as "First Rest-of-name".
 */
function SmartCampusApplicationNames( array $app ): array
{
	$first = trim( (string) ( $app['first_name'] ?? '' ) );
	$last = trim( (string) ( $app['last_name'] ?? '' ) );

	if ( $first === '' && $last === '' )
	{
		$parts = preg_split( '/\s+/', trim( (string) ( $app['learner_name'] ?? '' ) ), 2 );
		$first = $parts[0] ?? '';
		$last = $parts[1] ?? '';
	}

	// Column limits from the students table.
	return [
		'first_name' => mb_substr( $first, 0, 50 ),
		'last_name' => mb_substr( $last, 0, 50 ),
		'middle_name' => mb_substr( trim( (string) ( $app['middle_name'] ?? '' ) ), 0, 50 ),
		'name_suffix' => mb_substr( trim( (string) ( $app['name_suffix'] ?? '' ) ), 0, 3 ),
	];
}

/**
 * Enroll an approved application as a RosarioSIS student, atomically.
 *
 * @return array{student_id:int, username:string, password:string, name:string}
 *         The plain temporary password is returned once so staff can hand it over.
 */
function SmartCampusEnrollApplication( $conn, int $application_id ): array
{
	$context = SmartCampusSchoolContext( $conn );

	pg_query( $conn, 'BEGIN' );

	try
	{
		$result = pg_query_params(
			$conn,
			'SELECT id, status, student_id, learner_name, first_name, middle_name,
				last_name, name_suffix, grade_level
			FROM enrollment_applications WHERE id = $1 FOR UPDATE',
			[ $application_id ]
		);
		$app = $result ? pg_fetch_assoc( $result ) : false;

		if ( ! $app )
		{
			throw new RuntimeException( 'Application not found.', 404 );
		}

		if ( $app['status'] === 'enrolled' || ! empty( $app['student_id'] ) )
		{
			throw new RuntimeException( 'This application is already enrolled.', 409 );
		}

		if ( $app['status'] !== 'approved' )
		{
			throw new RuntimeException( 'Approve the application before enrolling.', 409 );
		}

		$names = SmartCampusApplicationNames( $app );

		if ( $names['first_name'] === '' || $names['last_name'] === '' )
		{
			throw new RuntimeException( 'The learner needs both a first and last name before enrolling.', 400 );
		}

		$grade_id = SmartCampusResolveGradeId( $conn, (string) $app['grade_level'], $context['school_id'] );

		if ( $grade_id === null )
		{
			throw new RuntimeException(
				'Grade "' . $app['grade_level'] . '" does not match any grade level in School Setup.',
				400
			);
		}

		$username = SmartCampusUniqueUsername( $conn, $names['first_name'], $names['last_name'] );
		$password = substr( str_replace( [ '+', '/', '=' ], '', base64_encode( random_bytes( 12 ) ) ), 0, 10 );
		$hash = function_exists( 'encrypt_password' ) ?
			encrypt_password( $password ) :
			password_hash( $password, PASSWORD_DEFAULT );

		$result = pg_query_params(
			$conn,
			'INSERT INTO students (first_name, last_name, middle_name, name_suffix, username, password)
			VALUES ($1, $2, NULLIF($3, \'\'), NULLIF($4, \'\'), $5, $6)
			RETURNING student_id',
			[
				$names['first_name'],
				$names['last_name'],
				$names['middle_name'],
				$names['name_suffix'],
				$username,
				$hash,
			]
		);
		$student_id = $result ? (int) pg_fetch_result( $result, 0, 0 ) : 0;

		if ( ! $student_id )
		{
			throw new RuntimeException( 'Could not create the student record.', 500 );
		}

		// Default "Add" enrollment code and attendance calendar keep the student
		// visible in RosarioSIS lists and attendance for this school year.
		$result = pg_query_params(
			$conn,
			"INSERT INTO student_enrollment
				(syear, school_id, student_id, grade_id, start_date, enrollment_code, calendar_id)
			VALUES ($1, $2, $3, $4, CURRENT_DATE,
				(SELECT id FROM student_enrollment_codes
					WHERE syear = $1 AND type = 'Add'
					ORDER BY (default_code = 'Y') DESC NULLS LAST, sort_order, id LIMIT 1),
				(SELECT calendar_id FROM attendance_calendars
					WHERE school_id = $2 AND syear = $1
					ORDER BY (default_calendar = 'Y') DESC NULLS LAST, calendar_id LIMIT 1))",
			[ $context['syear'], $context['school_id'], $student_id, $grade_id ]
		);

		if ( ! $result )
		{
			throw new RuntimeException( 'Could not create the enrollment record.', 500 );
		}

		$result = pg_query_params(
			$conn,
			"UPDATE enrollment_applications SET status = 'enrolled', student_id = $1 WHERE id = $2",
			[ $student_id, $application_id ]
		);

		if ( ! $result )
		{
			throw new RuntimeException( 'Could not update the application.', 500 );
		}

		pg_query( $conn, 'COMMIT' );
	}
	catch ( Throwable $e )
	{
		pg_query( $conn, 'ROLLBACK' );

		if ( ! $e instanceof RuntimeException )
		{
			throw new RuntimeException( 'Enrollment failed.', 500, $e );
		}

		throw $e;
	}

	return [
		'student_id' => $student_id,
		'username' => $username,
		'password' => $password,
		'name' => trim( $names['first_name'] . ' ' . $names['last_name'] ),
	];
}

/**
 * Students actively enrolled in the current school and year.
 *
 * @return array Rows, totals and the grade levels for the filter.
 */
function SmartCampusListStudents( $conn, string $search, int $grade_id, int $page, int $per_page ): array
{
	$context = SmartCampusSchoolContext( $conn );
	$per_page = min( 50, max( 1, $per_page ) );

	$params = [ $context['syear'], $context['school_id'] ];
	$where = '';

	if ( $search !== '' )
	{
		$params[] = '%' . $search . '%';
		$n = count( $params );
		$where .= " AND (s.first_name ILIKE \$$n OR s.last_name ILIKE \$$n
			OR (s.first_name || ' ' || s.last_name) ILIKE \$$n
			OR s.username ILIKE \$$n OR s.student_id::text = \$" . ( $n + 1 ) . ')';
		$params[] = $search;
	}

	if ( $grade_id > 0 )
	{
		$params[] = $grade_id;
		$where .= ' AND se.grade_id = $' . count( $params );
	}

	// One row per student: their latest active enrollment this year.
	$from = "FROM students s
		JOIN student_enrollment se ON se.id = (
			SELECT id FROM student_enrollment
			WHERE student_id = s.student_id AND syear = $1 AND school_id = $2
			AND (end_date IS NULL OR end_date >= CURRENT_DATE)
			ORDER BY start_date DESC NULLS LAST, id DESC LIMIT 1
		)
		LEFT JOIN school_gradelevels gl ON gl.id = se.grade_id
		WHERE 1=1" . $where;

	$result = pg_query_params( $conn, 'SELECT COUNT(*) ' . $from, $params );
	$total = $result ? (int) pg_fetch_result( $result, 0, 0 ) : 0;
	$total_pages = max( 1, (int) ceil( $total / $per_page ) );
	$page = min( max( 1, $page ), $total_pages );

	$list_params = array_merge( $params, [ $per_page, ( $page - 1 ) * $per_page ] );
	$n = count( $params );

	$result = pg_query_params(
		$conn,
		"SELECT s.student_id, s.first_name, s.middle_name, s.last_name, s.username,
			gl.title AS grade, se.start_date,
			(SELECT ref FROM enrollment_applications ea
				WHERE ea.student_id = s.student_id ORDER BY ea.id DESC LIMIT 1) AS application_ref
		" . $from . "
		ORDER BY gl.sort_order NULLS LAST, s.last_name, s.first_name
		LIMIT $" . ( $n + 1 ) . ' OFFSET $' . ( $n + 2 ),
		$list_params
	);

	if ( ! $result )
	{
		throw new RuntimeException( 'Could not load students.', 500 );
	}

	$students = [];

	while ( $row = pg_fetch_assoc( $result ) )
	{
		$students[] = [
			'id' => (int) $row['student_id'],
			'name' => trim( $row['last_name'] . ', ' . $row['first_name'] . ' ' . (string) $row['middle_name'] ),
			'username' => (string) $row['username'],
			'grade' => (string) $row['grade'],
			'enrolled_on' => (string) $row['start_date'],
			'application_ref' => (string) $row['application_ref'],
		];
	}

	$grades = [];
	$result = pg_query_params(
		$conn,
		'SELECT id, title FROM school_gradelevels WHERE school_id = $1 ORDER BY sort_order, id',
		[ $context['school_id'] ]
	);

	while ( $result && ( $row = pg_fetch_assoc( $result ) ) )
	{
		$grades[] = [ 'id' => (int) $row['id'], 'title' => $row['title'] ];
	}

	return [
		'success' => true,
		'students' => $students,
		'grades' => $grades,
		'total_count' => $total,
		'total_pages' => $total_pages,
		'page' => $page,
		'per_page' => $per_page,
		'syear' => $context['syear'],
	];
}
