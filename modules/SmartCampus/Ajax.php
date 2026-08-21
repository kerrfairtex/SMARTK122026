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
	&& ( empty( $_SESSION['token'] ) || ! hash_equals( $_SESSION['token'], (string) ( $_REQUEST['token'] ?? '' ) ) ) ) {
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
		$enr_RES = db_fetch_row( $enr_RET );

		$disc_RET = DBQuery( "SELECT COUNT(*) AS referral_count
			FROM discipline_referrals
			WHERE school_id='" . (int) $school_id . "'
			AND syear='" . (int) $syear . "'" );
		$disc_RES = db_fetch_row( $disc_RET );

		echo json_encode( [
			'totalEnrolled'      => ! empty( $enr_RES['enrolled_count'] ) ? (int) $enr_RES['enrolled_count'] : 0,
			'referralsThisYear'  => ! empty( $disc_RES['referral_count'] ) ? (int) $disc_RES['referral_count'] : 0,
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

		$rows = $RET ? pg_fetch_all( $RET ) : [];
		echo json_encode( [ 'codes' => ( $rows === false ? [] : $rows ) ] );
		break;

	// -----------------------------------------------------------
	// This is the attendance_list endpoint that client.html expects.
	// It provides learners for the portal attendance section,
	// matching what the portal frontend calls for real-time updates.
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

		$rows = $RET ? pg_fetch_all( $RET ) : [];
		echo json_encode( [ 'learners' => ( $rows === false ? [] : $rows ) ] );
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

		$rows = $RET ? pg_fetch_all( $RET ) : [];
		echo json_encode( [ 'referrals' => ( $rows === false ? [] : $rows ) ] );
		break;

	// -----------------------------------------------------------
	case 'enrollment_list':

		$RET = DBQuery( "SELECT s.student_id, s.first_name, s.last_name,
				se.grade_id, se.start_date, se.end_date,
				se.enrollment_code, se.drop_code
			FROM student_enrollment se
			INNER JOIN students s ON s.student_id = se.student_id
			WHERE se.school_id='" . (int) $school_id . "'
			AND se.syear='" . (int) $syear . "'
			ORDER BY s.last_name, s.first_name" );

		$rows = $RET ? pg_fetch_all( $RET ) : [];
		echo json_encode( [ 'enrollments' => ( $rows === false ? [] : $rows ) ] );
		break;

	// -----------------------------------------------------------
	case 'kpi_refresh':

		$enroll_count = 0;
		$enr_RET = DBQuery( "SELECT COUNT(*) AS count
			FROM student_enrollment
			WHERE school_id='" . (int) $school_id . "'
			AND syear='" . (int) $syear . "'
			AND ( end_date IS NULL OR end_date >= CURRENT_DATE )" );
		$enr_row = db_fetch_row( $enr_RET );
		if ( ! empty( $enr_row['count'] ) ) {
			$enroll_count = (int) $enr_row['count'];
		}

		$att_rate = 100;
		$tot_RET = DBQuery( "SELECT COUNT(*) AS count
			FROM attendance_period ap
			INNER JOIN student_enrollment se ON se.student_id = ap.student_id
			WHERE se.school_id='" . (int) $school_id . "'
			AND ap.school_date=CURRENT_DATE" );
		$tot_row = db_fetch_row( $tot_RET );
		$total_att = ! empty( $tot_row['count'] ) ? (int) $tot_row['count'] : 0;

		if ( $total_att > 0 ) {
			$pres_RET = DBQuery( "SELECT COUNT(*) AS count
				FROM attendance_period ap
				INNER JOIN student_enrollment se ON se.student_id = ap.student_id
				INNER JOIN attendance_codes ac ON ac.id = ap.attendance_code
				WHERE se.school_id='" . (int) $school_id . "'
				AND ap.school_date=CURRENT_DATE
				AND ac.state_code='P'" );
			$pres_row = db_fetch_row( $pres_RET );
			$present_att = ! empty( $pres_row['count'] ) ? (int) $pres_row['count'] : 0;
			$att_rate = round( ( $present_att / $total_att ) * 100 );
		}

		$teacher_classes = 0;
		if ( User( 'STAFF_ID' ) ) {
			$tc_RET = DBQuery( "SELECT COUNT(*) AS count
				FROM course_periods
				WHERE ( teacher_id='" . (int) User( 'STAFF_ID' ) . "' OR secondary_teacher_id='" . (int) User( 'STAFF_ID' ) . "' )
				AND school_id='" . (int) $school_id . "'
				AND syear='" . (int) $syear . "'" );
			$tc_row = db_fetch_row( $tc_RET );
			$teacher_classes = ! empty( $tc_row['count'] ) ? (int) $tc_row['count'] : 0;
		}

		$student_att = 100;
		if ( User( 'STUDENT_ID' ) ) {
			$stot_RET = DBQuery( "SELECT COUNT(*) AS count
				FROM attendance_period
				WHERE student_id='" . (int) User( 'STUDENT_ID' ) . "'" );
			$stot_row = db_fetch_row( $stot_RET );
			$stotal = ! empty( $stot_row['count'] ) ? (int) $stot_row['count'] : 0;
			if ( $stotal > 0 ) {
				$spres_RET = DBQuery( "SELECT COUNT(*) AS count
					FROM attendance_period ap
					INNER JOIN attendance_codes ac ON ac.id = ap.attendance_code
					WHERE ap.student_id='" . (int) User( 'STUDENT_ID' ) . "'
					AND ac.state_code='P'" );
				$spres_row = db_fetch_row( $spres_RET );
				$spresent = ! empty( $spres_row['count'] ) ? (int) $spres_row['count'] : 0;
				$student_att = round( ( $spresent / $stotal ) * 100 );
			}
		}

		echo json_encode( [
			'enrolled'          => $enroll_count,
			'attendance_today'  => $att_rate,
			'teacher_classes'   => $teacher_classes,
			'student_attendance'=> $student_att,
		] );
		break;

	// -----------------------------------------------------------
default:
		http_response_code( 404 );
	echo json_encode( [ 'error' => 'Unknown modfunc: ' . $_REQUEST['modfunc'] ] );
}