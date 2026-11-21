<?php
/**
 * SmartCampus school-year setup: sections, advisory and learning-area
 * classes, school days, and assigning learners to sections.
 *
 * Builds the RosarioSIS structures the school would otherwise enter by hand
 * in School Setup and Scheduling:
 *   course_subjects ("Advisory" + learning areas)
 *   -> courses (one per subject per grade level)
 *   -> course_periods (one per section, short_name = section name)
 *   -> schedule (each learner in their section's classes).
 * A section is the "Advisory" course period of a grade level; its adviser
 * takes attendance there (SF2).
 *
 * Every step is get-or-create, so running it again only adds what is
 * missing. All queries use pg_query_params.
 *
 * @package SmartCampus
 */

/** Subject that represents a section / homeroom. */
const SMARTCAMPUS_ADVISORY = 'Advisory';

/** DepEd Junior High School learning areas (Grades 7–10). */
const SMARTCAMPUS_JHS_LEARNING_AREAS = [
	'Filipino',
	'English',
	'Mathematics',
	'Science',
	'Araling Panlipunan',
	'Values Education (EsP)',
	'TLE',
	'MAPEH',
];

/** Run a parameterized query; throw a RuntimeException on failure. */
function SmartCampusSetupQuery( $conn, string $sql, array $params = [] )
{
	$result = pg_query_params( $conn, $sql, $params );

	if ( $result === false )
	{
		throw new RuntimeException( 'School setup failed while saving. No changes were kept.', 500 );
	}

	return $result;
}

/** First column of the first row, or null. */
function SmartCampusSetupValue( $conn, string $sql, array $params = [] )
{
	$row = pg_fetch_row( SmartCampusSetupQuery( $conn, $sql, $params ) );

	return $row ? $row[0] : null;
}

/**
 * Full-year marking period, default calendar and time slots for the year.
 *
 * @return array{fy:int, fy_start:string, fy_end:string, calendar:int, advisory_period:int, periods:int[]}
 */
function SmartCampusSetupBasics( $conn, array $context ): array
{
	$fy = pg_fetch_assoc( SmartCampusSetupQuery(
		$conn,
		"SELECT marking_period_id, start_date, end_date FROM school_marking_periods
		WHERE syear = $1 AND school_id = $2 AND mp = 'FY' ORDER BY marking_period_id LIMIT 1",
		[ $context['syear'], $context['school_id'] ]
	) );

	if ( ! $fy )
	{
		throw new RuntimeException( 'Create the Full Year marking period first (School Setup → Marking Periods).', 409 );
	}

	$calendar = SmartCampusSetupValue(
		$conn,
		"SELECT calendar_id FROM attendance_calendars WHERE syear = $1 AND school_id = $2
		ORDER BY (default_calendar = 'Y') DESC NULLS LAST, calendar_id LIMIT 1",
		[ $context['syear'], $context['school_id'] ]
	);

	if ( ! $calendar )
	{
		throw new RuntimeException( 'Create a school calendar first (School Setup → Calendars).', 409 );
	}

	$periods = pg_fetch_all_columns( SmartCampusSetupQuery(
		$conn,
		"SELECT period_id FROM school_periods WHERE syear = $1 AND school_id = $2
		AND attendance = 'Y' ORDER BY sort_order, period_id",
		[ $context['syear'], $context['school_id'] ]
	), 0 );

	if ( ! $periods )
	{
		throw new RuntimeException( 'Create at least one attendance period first (School Setup → Periods).', 409 );
	}

	$periods = array_map( 'intval', $periods );

	return [
		'fy' => (int) $fy['marking_period_id'],
		'fy_start' => $fy['start_date'],
		'fy_end' => $fy['end_date'],
		'calendar' => (int) $calendar,
		// Advisory uses the first attendance period (e.g. "Full Day");
		// learning areas take the following ones, repeating if there are fewer.
		'advisory_period' => $periods[0],
		'periods' => count( $periods ) > 1 ? array_slice( $periods, 1 ) : $periods,
	];
}

/**
 * Add Monday–Friday school days for the full year when the calendar has
 * none yet (teachers cannot take attendance on non-school days).
 *
 * @return int Days added.
 */
function SmartCampusSetupSchoolDays( $conn, array $context, array $basics ): int
{
	$has_days = SmartCampusSetupValue(
		$conn,
		'SELECT 1 FROM attendance_calendar WHERE syear = $1 AND school_id = $2 AND calendar_id = $3 LIMIT 1',
		[ $context['syear'], $context['school_id'], $basics['calendar'] ]
	);

	if ( $has_days )
	{
		return 0;
	}

	// minutes 999 = full day, as RosarioSIS School Setup > Calendars does.
	$result = SmartCampusSetupQuery(
		$conn,
		"INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, calendar_id)
		SELECT $1, $2, d::date, 999, $3
		FROM generate_series($4::date, $5::date, interval '1 day') d
		WHERE extract(isodow FROM d) BETWEEN 1 AND 5
		ON CONFLICT DO NOTHING",
		[ $context['syear'], $context['school_id'], $basics['calendar'], $basics['fy_start'], $basics['fy_end'] ]
	);

	return pg_affected_rows( $result );
}

/** Get or create a subject; returns [id, created]. */
function SmartCampusSetupSubject( $conn, array $context, string $title, int $sort ): array
{
	$id = SmartCampusSetupValue(
		$conn,
		'SELECT subject_id FROM course_subjects WHERE syear = $1 AND school_id = $2 AND title = $3 LIMIT 1',
		[ $context['syear'], $context['school_id'], $title ]
	);

	if ( $id )
	{
		return [ (int) $id, false ];
	}

	return [ (int) SmartCampusSetupValue(
		$conn,
		'INSERT INTO course_subjects (syear, school_id, title, sort_order) VALUES ($1, $2, $3, $4) RETURNING subject_id',
		[ $context['syear'], $context['school_id'], $title, $sort ]
	), true ];
}

/** Get or create a course for a subject and grade; returns [id, created]. */
function SmartCampusSetupCourse( $conn, array $context, int $subject_id, int $grade_id, string $title, string $short ): array
{
	$id = SmartCampusSetupValue(
		$conn,
		'SELECT course_id FROM courses WHERE syear = $1 AND school_id = $2 AND subject_id = $3 AND grade_level = $4 LIMIT 1',
		[ $context['syear'], $context['school_id'], $subject_id, $grade_id ]
	);

	if ( $id )
	{
		return [ (int) $id, false ];
	}

	return [ (int) SmartCampusSetupValue(
		$conn,
		'INSERT INTO courses (syear, school_id, subject_id, grade_level, title, short_name)
		VALUES ($1, $2, $3, $4, $5, $6) RETURNING course_id',
		[ $context['syear'], $context['school_id'], $subject_id, $grade_id, $title, mb_substr( $short, 0, 25 ) ]
	), true ];
}

/** Get or create one section's class in a course; returns [id, created]. */
function SmartCampusSetupClass( $conn, array $context, array $basics, int $course_id, string $section, string $title, int $teacher_id, int $period_id, bool $attendance ): array
{
	$id = SmartCampusSetupValue(
		$conn,
		'SELECT course_period_id FROM course_periods WHERE course_id = $1 AND short_name = $2 LIMIT 1',
		[ $course_id, $section ]
	);

	if ( $id )
	{
		return [ (int) $id, false ];
	}

	$id = (int) SmartCampusSetupValue(
		$conn,
		"INSERT INTO course_periods (syear, school_id, course_id, title, short_name, mp,
			marking_period_id, teacher_id, calendar_id, does_attendance)
		VALUES ($1, $2, $3, $4, $5, 'FY', $6, $7, $8, $9) RETURNING course_period_id",
		[
			$context['syear'], $context['school_id'], $course_id, $title, $section,
			$basics['fy'], $teacher_id, $basics['calendar'],
			// ',0,' = the default attendance category, as Scheduling > Courses saves it.
			$attendance ? ',0,' : null,
		]
	);

	SmartCampusSetupQuery(
		$conn,
		"INSERT INTO course_period_school_periods (course_period_id, period_id, days) VALUES ($1, $2, 'MTWHF')",
		[ $id, $period_id ]
	);

	return [ $id, true ];
}

/**
 * Sections of a grade level: advisory class id => section name, adviser, size.
 */
function SmartCampusGradeSections( $conn, array $context, int $grade_id ): array
{
	$result = SmartCampusSetupQuery(
		$conn,
		"SELECT cp.course_period_id, cp.short_name, cp.teacher_id,
			TRIM(st.first_name || ' ' || st.last_name) AS adviser,
			(SELECT COUNT(*) FROM schedule sch WHERE sch.course_period_id = cp.course_period_id
				AND (sch.end_date IS NULL OR sch.end_date >= CURRENT_DATE)) AS learners
		FROM course_periods cp
		JOIN courses c ON c.course_id = cp.course_id
		JOIN course_subjects cs ON cs.subject_id = c.subject_id
		LEFT JOIN staff st ON st.staff_id = cp.teacher_id
		WHERE cs.title = $1 AND c.grade_level = $2 AND cp.syear = $3 AND cp.school_id = $4
		ORDER BY cp.course_period_id",
		[ SMARTCAMPUS_ADVISORY, $grade_id, $context['syear'], $context['school_id'] ]
	);

	$sections = [];

	while ( $row = pg_fetch_assoc( $result ) )
	{
		$sections[ (int) $row['course_period_id'] ] = [
			'name' => $row['short_name'],
			'teacher_id' => (int) $row['teacher_id'],
			'adviser' => (string) $row['adviser'],
			'learners' => (int) $row['learners'],
		];
	}

	return $sections;
}

/**
 * Put one learner in the smallest section of their grade (advisory plus
 * that section's learning-area classes). No-op if the grade has no
 * sections or the learner already has one.
 *
 * @return string|null Section name, or null when not assigned.
 */
function SmartCampusAssignToSection( $conn, array $context, int $student_id, int $grade_id, string $start_date )
{
	$sections = SmartCampusGradeSections( $conn, $context, $grade_id );

	if ( ! $sections )
	{
		return null;
	}

	$already = SmartCampusSetupValue(
		$conn,
		'SELECT 1 FROM schedule WHERE student_id = $1 AND course_period_id = ANY ($2::int[])
		AND (end_date IS NULL OR end_date >= CURRENT_DATE) LIMIT 1',
		[ $student_id, '{' . implode( ',', array_keys( $sections ) ) . '}' ]
	);

	if ( $already )
	{
		return null;
	}

	uasort( $sections, static fn( $a, $b ) => $a['learners'] <=> $b['learners'] );
	$advisory_id = array_key_first( $sections );
	$section = $sections[ $advisory_id ]['name'];

	// Advisory + every class of the same section in this grade.
	SmartCampusSetupQuery(
		$conn,
		"INSERT INTO schedule (syear, school_id, student_id, start_date, course_id, course_period_id, mp, marking_period_id)
		SELECT cp.syear, cp.school_id, $1, $2, cp.course_id, cp.course_period_id, cp.mp, cp.marking_period_id
		FROM course_periods cp JOIN courses c ON c.course_id = cp.course_id
		WHERE c.grade_level = $3 AND cp.short_name = $4 AND cp.syear = $5 AND cp.school_id = $6
		AND NOT EXISTS (SELECT 1 FROM schedule s WHERE s.student_id = $1 AND s.course_period_id = cp.course_period_id)",
		[ $student_id, $start_date, $grade_id, $section, $context['syear'], $context['school_id'] ]
	);

	return $section;
}

/**
 * Create sections and classes for the chosen grades, fill school days if
 * empty, and place every enrolled learner without a section.
 *
 * @param array $grades grade_id => ['sections' => string[], 'adviser' => int]
 * @return array Counts of what was created / assigned.
 */
function SmartCampusRunSchoolSetup( $conn, array $context, array $grades, bool $learning_areas ): array
{
	$summary = [ 'school_days' => 0, 'subjects' => 0, 'courses' => 0, 'classes' => 0, 'sections' => 0, 'assigned' => 0 ];

	pg_query( $conn, 'BEGIN' );

	try
	{
		$basics = SmartCampusSetupBasics( $conn, $context );
		$summary['school_days'] = SmartCampusSetupSchoolDays( $conn, $context, $basics );

		[ $advisory_subject, $created ] = SmartCampusSetupSubject( $conn, $context, SMARTCAMPUS_ADVISORY, 0 );
		$summary['subjects'] += (int) $created;

		$area_subjects = [];

		if ( $learning_areas )
		{
			foreach ( SMARTCAMPUS_JHS_LEARNING_AREAS as $i => $area )
			{
				[ $area_subjects[ $area ], $created ] = SmartCampusSetupSubject( $conn, $context, $area, $i + 1 );
				$summary['subjects'] += (int) $created;
			}
		}

		$grade_titles = [];
		$result = SmartCampusSetupQuery( $conn, 'SELECT id, title FROM school_gradelevels WHERE school_id = $1', [ $context['school_id'] ] );

		while ( $row = pg_fetch_assoc( $result ) )
		{
			$grade_titles[ (int) $row['id'] ] = $row['title'];
		}

		foreach ( $grades as $grade_id => $config )
		{
			if ( ! isset( $grade_titles[ $grade_id ] ) )
			{
				throw new RuntimeException( 'Unknown grade level.', 400 );
			}

			$grade = $grade_titles[ $grade_id ];
			$number = preg_match( '/(\d{1,2})/', $grade, $m ) ? (int) $m[1] : 0;

			[ $course, $created ] = SmartCampusSetupCourse( $conn, $context, $advisory_subject, $grade_id, SMARTCAMPUS_ADVISORY . ' — ' . $grade, 'ADV' . $number );
			$summary['courses'] += (int) $created;

			foreach ( $config['sections'] as $section )
			{
				[ , $created ] = SmartCampusSetupClass( $conn, $context, $basics, $course, $section, $grade . ' — ' . $section, $config['adviser'], $basics['advisory_period'], true );
				$summary['sections'] += (int) $created;
			}

			// Learning areas apply to Junior High (Grades 7–10) only.
			if ( $learning_areas && $number >= 7 && $number <= 10 )
			{
				$i = 0;

				foreach ( $area_subjects as $area => $subject_id )
				{
					[ $area_course, $created ] = SmartCampusSetupCourse( $conn, $context, $subject_id, $grade_id, $area . ' ' . $number, mb_substr( $area, 0, 20 ) . ' ' . $number );
					$summary['courses'] += (int) $created;
					$period = $basics['periods'][ $i++ % count( $basics['periods'] ) ];

					foreach ( $config['sections'] as $section )
					{
						[ , $created ] = SmartCampusSetupClass( $conn, $context, $basics, $area_course, $section, $area . ' — ' . $section, $config['adviser'], $period, false );
						$summary['classes'] += (int) $created;
					}
				}
			}

			// Place enrolled learners without a section, alphabetically.
			$learners = SmartCampusSetupQuery(
				$conn,
				'SELECT s.student_id, se.start_date FROM students s
				JOIN student_enrollment se ON se.student_id = s.student_id
				WHERE se.syear = $1 AND se.school_id = $2 AND se.grade_id = $3
				AND (se.end_date IS NULL OR se.end_date >= CURRENT_DATE)
				ORDER BY s.last_name, s.first_name',
				[ $context['syear'], $context['school_id'], $grade_id ]
			);

			while ( $row = pg_fetch_assoc( $learners ) )
			{
				$start = max( (string) $row['start_date'], $basics['fy_start'] );

				if ( SmartCampusAssignToSection( $conn, $context, (int) $row['student_id'], $grade_id, $start ) !== null )
				{
					$summary['assigned']++;
				}
			}
		}

		pg_query( $conn, 'COMMIT' );
	}
	catch ( Throwable $e )
	{
		pg_query( $conn, 'ROLLBACK' );

		throw $e instanceof RuntimeException ? $e : new RuntimeException( 'School setup failed. No changes were kept.', 500, $e );
	}

	return $summary;
}
