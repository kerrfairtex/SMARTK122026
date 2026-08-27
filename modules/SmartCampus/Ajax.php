<?php
/**
 * Ajax.php
 *
 * JSON web-service endpoint for the SmartCampus client (client.html).
 * Rewritten against the CONFIRMED schema from rosariosis.sql (mobile
 * branch) — table/column names below are real, not guessed.
 *
 * Known open question, flagged rather than guessed: attendance_codes
 * has three candidate columns for "is this code a present mark" —
 * type, state_code, default_code. This file does NOT assume one.
 * portal_stats and attendance_codes return raw data so you can check
 * against your actual installed data (SELECT * FROM attendance_codes
 * WHERE school_id=...) or TakeAttendance.php's own logic before wiring
 * up a rate calculation that depends on the answer.
 *
 * @package SmartCampus
 * @since   1.1
 */

// Direct-access guard: requires the RosarioSIS bootstrap. If opened directly, return a
// clean 403 JSON instead of a fatal "undefined function AllowUse" error.
if ( ! function_exists( 'AllowUse' ) ) {
	if ( PHP_SAPI !== 'cli' ) {
		http_response_code( 403 );
		header( 'Content-Type: application/json' );
		echo json_encode( [ 'error' => 'Direct access not allowed. Open via the SmartCampus portal.' ] );
	}
	exit;
}

if ( ! isset( $_REQUEST['modfunc'] ) ) {
	$_REQUEST['modfunc'] = false;
}

if ( ! AllowUse() ) {
	http_response_code( 403 );
	echo json_encode( [ 'error' => 'Not authorized' ] );
	exit;
}

$write_actions = [ 'attendance_save', 'discipline_save', 'enrollment_save' ];

if ( in_array( $_REQUEST['modfunc'], $write_actions, true )
	&& ( empty( $_REQUEST['token'] ) || $_REQUEST['token'] !== $_SESSION['token'] ) ) {
	http_response_code( 403 );
	echo json_encode( [ 'error' => 'Invalid or missing token' ] );
	exit;
}

header( 'Content-Type: application/json; charset=utf-8' );

$school_id = UserSchool();
$syear     = UserSyear();

switch ( $_REQUEST['modfunc'] ) {

	// -----------------------------------------------------------
	// Real join path: students -> student_enrollment (school_id,
	// syear live on the enrollment row, not on students itself).
	// "Currently enrolled" = no end_date, or end_date in the future.
	case 'portal_stats':

		$enr_RET = DBQuery( "SELECT COUNT(*) AS enrolled_count
			FROM student_enrollment
			WHERE school_id='" . (int) $school_id . "'
			AND syear='" . (int) $syear . "'
			AND ( end_date IS NULL OR end_date >= CURRENT_DATE )" );
		$enr_RES = DBFetchArray( $enr_RET );

		$disc_RET = DBQuery( "SELECT COUNT(*) AS referral_count
			FROM discipline_referrals
			WHERE school_id='" . (int) $school_id . "'
			AND syear='" . (int) $syear . "'" );
		$disc_RES = DBFetchArray( $disc_RET );

		echo json_encode( [
			'totalEnrolled'      => ! empty( $enr_RES[0]['enrolled_count'] ) ? (int) $enr_RES[0]['enrolled_count'] : 0,
			'referralsThisYear'  => ! empty( $disc_RES[0]['referral_count'] ) ? (int) $disc_RES[0]['referral_count'] : 0,
			// Attendance rate deliberately omitted — see file header note.
		] );
		break;

	// -----------------------------------------------------------
	// Returns this school's actual attendance codes so the client
	// renders real buttons instead of hardcoded P/A/T.
	case 'attendance_codes':

		$RET = DBQuery( "SELECT id, title, short_name, type, state_code, default_code, sort_order
			FROM attendance_codes
			WHERE school_id='" . (int) $school_id . "'
			AND syear='" . (int) $syear . "'
			ORDER BY sort_order" );

		echo json_encode( [ 'codes' => DBFetchArray( $RET ) ?: [] ] );
		break;

	// -----------------------------------------------------------
	case 'attendance_list':

		$course_period_id = (int) ( $_REQUEST['course_period_id'] ?? 0 );
		$period_id         = (int) ( $_REQUEST['period_id'] ?? 0 );

		$RET = DBQuery( "SELECT s.student_id, s.first_name, s.last_name,
				s.custom_200000003 AS id_number,
				ap.attendance_code
			FROM students s
			INNER JOIN schedule sch ON sch.student_id = s.student_id
			INNER JOIN student_enrollment se ON se.student_id = s.student_id
			LEFT JOIN attendance_period ap
				ON ap.student_id = s.student_id
				AND ap.school_date = CURRENT_DATE
				AND ap.course_period_id = sch.course_period_id
				AND ap.period_id = '" . $period_id . "'
			WHERE sch.course_period_id = '" . $course_period_id . "'
			AND se.school_id = '" . (int) $school_id . "'
			AND se.syear = '" . (int) $syear . "'
			AND ( se.end_date IS NULL OR se.end_date >= CURRENT_DATE )
			ORDER BY s.last_name, s.first_name" );

		echo json_encode( [ 'learners' => DBFetchArray( $RET ) ?: [] ] );
		break;

	// -----------------------------------------------------------
	// marks: { student_id: attendance_code_id (integer, from the
	// attendance_codes list — NOT a 'P'/'A'/'T' letter) }
	case 'attendance_save':

		$course_period_id = (int) ( $_REQUEST['course_period_id'] ?? 0 );
		$period_id         = (int) ( $_REQUEST['period_id'] ?? 0 );
		$marks             = $_REQUEST['marks'] ?? [];

		if ( ! is_array( $marks ) || ! $course_period_id || ! $period_id ) {
			http_response_code( 400 );
			echo json_encode( [ 'error' => 'Missing course_period_id, period_id, or marks' ] );
			break;
		}

		foreach ( $marks as $student_id => $code_id ) {
			$student_id = (int) $student_id;
			$code_id    = (int) $code_id;

			DBQuery( "INSERT INTO attendance_period
					(student_id, school_date, period_id, attendance_code, course_period_id)
				VALUES
					('" . $student_id . "', CURRENT_DATE, '" . $period_id . "', '" . $code_id . "', '" . $course_period_id . "')
				ON CONFLICT (student_id, school_date, period_id)
				DO UPDATE SET attendance_code = EXCLUDED.attendance_code" );
		}

		echo json_encode( [ 'ok' => true, 'saved' => count( $marks ) ] );
		break;

	// -----------------------------------------------------------
	// No status column exists on discipline_referrals — category_1
	// is shown as-is. What it actually contains depends on this
	// school's Discipline field configuration; verify before
	// labeling it in the UI as "Concern" or similar.
	case 'discipline_list':

		$RET = DBQuery( "SELECT dr.id, s.first_name, s.last_name,
				dr.referral_date, dr.category_1
			FROM discipline_referrals dr
			INNER JOIN students s ON s.student_id = dr.student_id
			WHERE dr.school_id='" . (int) $school_id . "'
			AND dr.syear='" . (int) $syear . "'
			ORDER BY dr.referral_date DESC
			LIMIT 50" );

		echo json_encode( [ 'referrals' => DBFetchArray( $RET ) ?: [] ] );
		break;
        // -----------------------------------------------------------
        // enrollment_code / drop_code are integer FKs whose lookup table
        // hasn't been confirmed yet — returned raw. No enrollment_save
        // exists yet; don't add one until that's checked (same trap as
        // the attendance_code guess earlier in this file).
        case 'enrollment_list':

                $RET = DBQuery( "SELECT s.student_id, s.first_name, s.last_name,
                                se.grade_id, se.start_date, se.end_date,
                                se.enrollment_code, se.drop_code
                        FROM student_enrollment se
                        INNER JOIN students s ON s.student_id = se.student_id
                        WHERE se.school_id='" . (int) $school_id . "'
                        AND se.syear='" . (int) $syear . "'
                        ORDER BY s.last_name, s.first_name" );

                echo json_encode( [ 'enrollments' => DBFetchArray( $RET ) ?: [] ] );
                break;

	// -----------------------------------------------------------
	default:
		http_response_code( 404 );
		echo json_encode( [ 'error' => 'Unknown modfunc: ' . $_REQUEST['modfunc'] ] );
}
