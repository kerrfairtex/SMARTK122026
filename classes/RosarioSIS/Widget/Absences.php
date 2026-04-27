<?php
/**
 * Days Absent (Attendance) Widget class
 *
 * @since 12.7
 *
 * @package RosarioSIS
 */

namespace RosarioSIS\Widget;

class Absences implements \RosarioSIS\Widget
{
	function canBuild( $modules )
	{
		return $modules['Attendance'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['absences_low'] )
			|| ! is_numeric( $_REQUEST['absences_low'] )
			|| ! isset( $_REQUEST['absences_high'] )
			|| ! is_numeric( $_REQUEST['absences_high'] ) )
		{
			return $extra;
		}

		if ( $_REQUEST['absences_low'] > $_REQUEST['absences_high'] )
		{
			$temp = $_REQUEST['absences_high'];

			$_REQUEST['absences_high'] = $_REQUEST['absences_low'];

			$_REQUEST['absences_low'] = $temp;
		}

		// Set Absences number SQL condition.
		$absences_sql = $_REQUEST['absences_low'] == $_REQUEST['absences_high'] ?
			" = '" . $_REQUEST['absences_low'] . "'" :
			" BETWEEN '" . $_REQUEST['absences_low'] . "'
				AND '" . $_REQUEST['absences_high'] . "'";

		// @since 12.9 SQL performance: replace subqueries with LEFT JOINs
		$extra['FROM'] .= " LEFT JOIN (SELECT STUDENT_ID,SYEAR,sum(1-STATE_VALUE) AS TOTAL
			FROM attendance_day
			WHERE MARKING_PERIOD_ID IN (" . GetChildrenMP( $_REQUEST['absences_term'], UserMP() ) . ")
			GROUP BY STUDENT_ID,SYEAR) ad ON ad.STUDENT_ID=ssm.STUDENT_ID
			AND ad.SYEAR=ssm.SYEAR";

		$extra['WHERE'] .= " AND COALESCE(ad.TOTAL,0)" .
			$absences_sql;

		switch ( $_REQUEST['absences_term'] )
		{
			case 'FY':
				$term = _( 'this school year to date' );
			break;

			case 'SEM':
				$term = _( 'this semester to date' );
			break;

			case 'QTR':
				$term = _( 'this marking period to date' );
			break;
		}

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Days Absent' ) . ' ' . $term . ': </b>' .
				_( 'Between' ) . ' ' .
				$_REQUEST['absences_low'] . ' &amp; ' . $_REQUEST['absences_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' .	_( 'Days Absent' ) .
		'<br />
		<label title="' . AttrEscape( _( 'this school year to date' ) ) . '">
			<input type="radio" name="absences_term" value="FY" checked>&nbsp;' .
			_( 'YTD' ) .
		'</label> &nbsp;
		<label title="' . AttrEscape( _( 'this semester to date' ) ) . '">
			<input type="radio" name="absences_term" value="SEM">&nbsp;' .
			GetMP( GetParentMP( 'SEM', UserMP() ), 'SHORT_NAME' ) .
		'</label> &nbsp;
		<label title="' . AttrEscape( _( 'this marking period to date' ) ) . '">
			<input type="radio" name="absences_term" value="QTR">&nbsp;' .
			GetMP( UserMP(), 'SHORT_NAME' ) .
		'</label>
		</td><td><label>' . _( 'Between' ) .
		' <input type="text" name="absences_low" size="3" maxlength="3"></label>' .
		' <label>&amp; ' .
		'<input type="text" name="absences_high" size="3" maxlength="3"><label>
		</td></tr>';
	}
}
