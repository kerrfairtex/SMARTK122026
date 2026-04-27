<?php
/**
 * Balance (Student Billing) Widget class
 *
 * @since 12.7
 *
 * @package RosarioSIS
 */

namespace RosarioSIS\Widget;

class Balance implements \RosarioSIS\Widget
{
	function canBuild( $modules )
	{
		return $modules['Student_Billing']
			&& AllowUse( 'Student_Billing/StudentFees.php' );
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['balance_low'] )
			|| ! is_numeric( $_REQUEST['balance_low'] )
			|| ! isset( $_REQUEST['balance_high'] )
			|| ! is_numeric( $_REQUEST['balance_high'] ) )
		{
			return $extra;
		}

		if ( $_REQUEST['balance_low'] > $_REQUEST['balance_high'] )
		{
			$temp = $_REQUEST['balance_high'];
			$_REQUEST['balance_high'] = $_REQUEST['balance_low'];
			$_REQUEST['balance_low'] = $temp;
		}

		// @since 12.9 SQL performance: replace subqueries with LEFT JOINs
		$extra['FROM'] .= ' LEFT JOIN (SELECT STUDENT_ID,SYEAR,SUM(AMOUNT) AS TOTAL
			FROM billing_fees
			GROUP BY STUDENT_ID,SYEAR) f ON f.STUDENT_ID=ssm.STUDENT_ID
			AND f.SYEAR=ssm.SYEAR
		LEFT JOIN (SELECT STUDENT_ID,SYEAR,SUM(AMOUNT) AS TOTAL
			FROM billing_payments
			GROUP BY STUDENT_ID,SYEAR) p ON p.STUDENT_ID=ssm.STUDENT_ID
			AND p.SYEAR=ssm.SYEAR';

		$extra['WHERE'] .= " AND COALESCE(p.TOTAL,0)-COALESCE(f.TOTAL,0)
			BETWEEN '" . $_REQUEST['balance_low'] . "'
			AND '" . $_REQUEST['balance_high'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Student Billing Balance' ) . ' ' . _( 'Between' ) .': </b>' .
				$_REQUEST['balance_low'] . ' &amp; ' .
				$_REQUEST['balance_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' . _( 'Balance' ) . '</td><td><label>' . _( 'Between' ) .
		' <input type="number" name="balance_low" step="0.01" min="-999999999999999" max="999999999999999"></label>' .
		' <label>&amp;' .
		' <input type="number" name="balance_high" step="0.01" min="-999999999999999" max="999999999999999"></label>
		</td></tr>';
	}
}
