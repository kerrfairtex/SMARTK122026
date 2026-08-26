<?php
/**
 * SmartCampus Dashboard module
 *
 * Default dashboard block for the BATU-BATU NIHS portal.
 * Registered automatically by classes/RosarioSIS/Functions/Dashboard.php
 * which calls DashboardDefaultSmartCampus() (module name without separators).
 *
 * Returns key/value pairs that the DashboardModule::data() renderer turns into
 * summary cards.  Null values (data not applicable / no permission) are skipped.
 *
 * @package SmartCampus
 * @since   1.0
 */

if ( ! function_exists( 'DashboardDefaultSmartCampus' ) ) :

/**
 * Batu-Batu National Integrated High School — dashboard summary cards.
 *
 * Shows the Philippine K-12 school the items requested in the migration
 * spec: total & active students, teacher & section counts, current school
 * year, current quarter, and today's attendance snapshot.
 *
 * @since 1.0
 *
 * @return array<string,string|null> Dashboard label => value pairs.
 */
function DashboardDefaultSmartCampus()
{
	$profile = User( 'PROFILE' );
	$school_id = UserSchool();
	$syear     = UserSyear();

	$data = [];

	// ---- Current quarter (Philippine K-12 quarters Q1–Q4) ----
	// The mp column stores 'QTR' for quarters; short_name stores 'Q1'..'Q4'.
	$mp_ret = DBGet( "SELECT title, short_name, start_date, end_date
		FROM school_marking_periods
		WHERE syear='" . (int) $syear . "'
		AND school_id='" . (int) $school_id . "'
		AND mp='QTR'
		AND start_date <= CURRENT_DATE
		AND end_date >= CURRENT_DATE
		ORDER BY sort_order
		LIMIT 1" );

	$current_quarter = '';
	if ( ! empty( $mp_ret[1] ) ) {
		$current_quarter = trim( (string) ( $mp_ret[1]['short_name'] ?: $mp_ret[1]['title'] ) );
	}
	$data[_( 'Current Quarter' )] = $current_quarter ?: null;

	// ---- School year label: "SY 2026–2027" ----
	// The school year is stored as a numeric syear (start year). Philippine
	// school years run June–May, so SY 2026 = June 2026 → May 2027.
	$data[_( 'School Year' )] = 'SY ' . $syear . '–' . ( (int) $syear + 1 );

	// ---- Student counts: admin only (sensitive aggregate) ----
	if ( $profile === 'admin' ) {
		$enroll_ret = DBGet( "SELECT
			COUNT(*) AS TOTAL,
			SUM(CASE WHEN (end_date IS NULL OR CURRENT_DATE <= end_date)
				AND CURRENT_DATE >= start_date THEN 1 END) AS ACTIVE
			FROM student_enrollment
			WHERE syear='" . (int) $syear . "'
			AND school_id='" . (int) $school_id . "'" );

		$total_students   = (int) $enroll_ret[1]['TOTAL'];
		$active_students  = (int) $enroll_ret[1]['ACTIVE'];

		$data[_( 'Total Students' )]     = NoInput( $total_students, _( 'Students' ) );
		$data[_( 'Active Students' )]    = NoInput( $active_students, _( 'Students' ) );
	}

	// ---- Teacher count: admin & teacher ----
	if ( $profile === 'admin' || $profile === 'teacher' ) {
		$teacher_nb = (int) DBGetOne( "SELECT COUNT(*)
			FROM staff
			WHERE syear='" . (int) $syear . "'
			AND profile='teacher'
			AND (schools IS NULL OR position(CONCAT(',', '" . (int) $school_id . "', ',') IN schools) > 0)" );

		$data[_( 'Teachers' )] = NoInput( $teacher_nb, _( 'Teachers' ) );
	}

	// ---- Section count (course periods): admin & teacher ----
	if ( $profile === 'admin' || $profile === 'teacher' ) {
		$section_nb = (int) DBGetOne( "SELECT COUNT(*)
			FROM course_periods
			WHERE syear='" . (int) $syear . "'
			AND school_id='" . (int) $school_id . "'" );

		$data[_( 'Sections' )] = NoInput( $section_nb, _( 'Sections' ) );
	}

	// ---- Today's attendance snapshot: admin & teacher ----
	// Uses attendance_day (daily rollup with state_value: 1.0=present, 0.5=half, 0=absent).
	// Joins through student_enrollment to scope to current school — avoids multi-school leak.
	// Note: COUNT(*) always returns 1 row even if no matches; handle NULL from SUM with COALESCE.
	if ( $profile === 'admin' || $profile === 'teacher' ) {
		$att_ret = DBGet( "SELECT
				COUNT(*) AS TOTAL,
				COALESCE( SUM( CASE WHEN ad.state_value >= 1 THEN 1 END ), 0 ) AS PRESENT
			FROM attendance_day ad
			JOIN student_enrollment se ON (
				se.student_id = ad.student_id
				AND se.syear = '" . (int) $syear . "'
				AND se.school_id = '" . (int) $school_id . "'
				AND CURRENT_DATE >= se.start_date
				AND (se.end_date IS NULL OR CURRENT_DATE <= se.end_date)
			)
			WHERE ad.school_date = CURRENT_DATE" );

		$att_total   = isset( $att_ret[1]['TOTAL'] ) ? (int) $att_ret[1]['TOTAL'] : 0;
		$att_present = isset( $att_ret[1]['PRESENT'] ) ? (int) $att_ret[1]['PRESENT'] : 0;
		$att_pct     = $att_total > 0 ? round( ( $att_present / $att_total ) * 100 ) : 0;

		$data[_( 'Attendance Today' )] = $att_total > 0
			? sprintf( '%d/%d (%d%%)', $att_present, $att_total, $att_pct )
			: _( 'No data' );
	}

	// Drop empty entries — dashboard renderer skips null, but guard anyway.
	$data = array_filter( $data, function ( $val ) { return $val !== null && $val !== ''; } );

	return $data;
}

endif;
