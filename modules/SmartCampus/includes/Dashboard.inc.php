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
	$mp_ret = DBGetOne( "SELECT title, short_name, start_date, end_date
		FROM school_marking_periods
		WHERE syear='" . (int) $syear . "'
		AND school_id='" . (int) $school_id . "'
		AND mp IN ('Q1','Q2','Q3','Q4','QTR')
		AND start_date <= CURRENT_DATE
		AND end_date >= CURRENT_DATE
		ORDER BY sort_order
		LIMIT 1" );

	$current_quarter = '';
	if ( $mp_ret ) {
		// Prefer the Philippine quarter short name, fall back to title.
		$current_quarter = trim( (string) $mp_ret['short_name'] ?: $mp_ret['title'] );
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
		$teacher_ret = DBGetOne( "SELECT COUNT(*) AS NB
			FROM staff
			WHERE syear='" . (int) $syear . "'
			AND profile='teacher'
			AND (schools IS NULL OR position(',' . '" . (int) $school_id . "' . ',', IN schools) > 0)" );

		$teacher_nb = (int) $teacher_ret['NB'];
		$data[_( 'Teachers' )] = NoInput( $teacher_nb, _( 'Teachers' ) );
	}

	// ---- Section count (course periods): admin & teacher ----
	if ( $profile === 'admin' || $profile === 'teacher' ) {
		$section_ret = DBGetOne( "SELECT COUNT(*) AS NB
			FROM course_periods
			WHERE syear='" . (int) $syear . "'
			AND school_id='" . (int) $school_id . "'" );

		$data[_( 'Sections' )] = NoInput( (int) $section_ret['NB'], _( 'Sections' ) );
	}

	// ---- Today's attendance snapshot: admin & teacher ----
	if ( $profile === 'admin' || $profile === 'teacher' ) {
		$att_ret = DBGetOne( "SELECT
			COUNT(*) AS TOTAL,
			SUM(CASE WHEN status IN ('P','M','T') THEN 1 END) AS PRESENT
			FROM attendance_completed
			WHERE school_date = CURRENT_DATE" );

		$att_total = (int) $att_ret['TOTAL'];
		$att_present = (int) $att_ret['PRESENT'];
		$att_pct = $att_total > 0 ? round( ( $att_present / $att_total ) * 100 ) : 0;

		$data[_( 'Attendance Today' )] = $att_total > 0
			? sprintf( '%d/%d (%d%%)', $att_present, $att_total, $att_pct )
			: _( 'No data' );
	}

	// Drop empty entries — dashboard renderer skips null, but guard anyway.
	$data = array_filter( $data, function ( $val ) { return $val !== null && $val !== ''; } );

	return $data;
}

endif;
