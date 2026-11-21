<?php
/**
 * SMS notifications to parents/guardians through Semaphore
 * (https://api.semaphore.co/docs, POST /api/v4/messages).
 *
 * Configuration (environment):
 *   SEMAPHORE_API_KEY  — when empty, messages are only logged ("dry_run").
 *   SMS_SENDER_NAME    — optional registered Sender Name; Semaphore uses the
 *                        account default when omitted.
 *   SEMAPHORE_API_URL  — optional override of the endpoint (tests only).
 *
 * Sending never throws: a failed SMS must not undo an approval or an
 * enrollment. Every attempt is written to sms_log with a masked number.
 *
 * @package SmartCampus
 */

const SMARTCAMPUS_SMS_SCHOOL = 'Batu-Batu NHS';

/**
 * Philippine mobile number as 09XXXXXXXXX, or null if it is not one.
 * Accepts 09…, 9…, 639…, +639… with spaces, dashes or dots.
 */
function SmartCampusNormalizeMobile( string $raw )
{
	$digits = preg_replace( '/\D/', '', $raw );

	if ( preg_match( '/^(?:63|0)?(9\d{9})$/', $digits, $m ) )
	{
		return '0' . $m[1];
	}

	return null;
}

/** 0917***4567 — enough to recognise, not enough to reuse. */
function SmartCampusMaskMobile( string $mobile ): string
{
	return strlen( $mobile ) === 11 ? substr( $mobile, 0, 4 ) . '***' . substr( $mobile, -4 ) : '***';
}

/**
 * Message text for a template, kept to one 160-character SMS.
 *
 * @param array $app first_name, ref, (section)
 */
function SmartCampusSmsText( string $template, array $app ): string
{
	// First given name only ("Maria Clarissa" -> "Maria"): short and natural.
	$name = mb_substr( (string) strtok( trim( (string) ( $app['first_name'] ?? '' ) ), ' ' ), 0, 20 ) ?: 'your child';
	$ref = (string) ( $app['ref'] ?? '' );

	switch ( $template )
	{
		case 'submitted':
			$text = 'We received the enrollment application of ' . $name . '. Ref: ' . $ref . '. Keep this number to check the status online.';
			break;

		case 'approved':
			$text = 'The enrollment application of ' . $name . ' (Ref ' . $ref . ') is APPROVED. Please visit the school to complete enrollment.';
			break;

		case 'rejected':
			$text = 'The enrollment application of ' . $name . ' (Ref ' . $ref . ') was not approved. Please contact the school office for details.';
			break;

		case 'enrolled':
			$section = trim( (string) ( $app['section'] ?? '' ) );
			$text = $name . ' is now ENROLLED' . ( $section !== '' ? ' in section ' . $section : '' ) . '. Welcome to ' . SMARTCAMPUS_SMS_SCHOOL . '!';
			break;

		default:
			throw new InvalidArgumentException( 'Unknown SMS template.' );
	}

	// School name first (also avoids Semaphore dropping messages that start with "TEST").
	$text = SMARTCAMPUS_SMS_SCHOOL . ': ' . $text;

	return substr( SmartCampusSmsAscii( $text ), 0, 160 );
}

/**
 * Plain ASCII keeps a message to one 160-character segment
 * ("Niño" -> "Nino"); anything left over is dropped.
 */
function SmartCampusSmsAscii( string $text ): string
{
	if ( function_exists( 'transliterator_transliterate' ) )
	{
		$text = (string) transliterator_transliterate( 'Any-Latin; Latin-ASCII', $text );
	}
	else
	{
		$text = strtr( $text, [
			'ñ' => 'n', 'Ñ' => 'N', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
			'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'ü' => 'u', 'Ü' => 'U',
		] );
	}

	return preg_replace( '/[^\x20-\x7E]/', '', $text );
}

/**
 * Send one SMS via Semaphore. Returns [status, provider_message_id, detail].
 */
function SmartCampusSemaphoreSend( string $mobile, string $message ): array
{
	$key = (string) getenv( 'SEMAPHORE_API_KEY' );

	if ( $key === '' )
	{
		return [ 'dry_run', null, 'SEMAPHORE_API_KEY not set' ];
	}

	$fields = [ 'apikey' => $key, 'number' => $mobile, 'message' => $message ];
	$sender = trim( (string) getenv( 'SMS_SENDER_NAME' ) );

	if ( $sender !== '' )
	{
		$fields['sendername'] = $sender;
	}

	$ch = curl_init( getenv( 'SEMAPHORE_API_URL' ) ?: 'https://api.semaphore.co/api/v4/messages' );
	curl_setopt_array( $ch, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query( $fields ),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 3,
		CURLOPT_TIMEOUT => 6,
	] );
	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$error = curl_error( $ch );

	if ( $body === false )
	{
		return [ 'failed', null, 'network: ' . mb_substr( $error, 0, 200 ) ];
	}

	$json = json_decode( (string) $body, true );
	$first = is_array( $json ) && isset( $json[0] ) && is_array( $json[0] ) ? $json[0] : null;

	if ( $code >= 200 && $code < 300 && $first && isset( $first['message_id'] ) )
	{
		return [ 'sent', (string) $first['message_id'], (string) ( $first['status'] ?? '' ) ];
	}

	// Error bodies are not documented; keep a short excerpt (never the API key).
	return [ 'failed', null, 'HTTP ' . $code . ': ' . mb_substr( str_replace( $key, '***', (string) $body ), 0, 200 ) ];
}

/**
 * Text the guardian of an application, and log the attempt.
 *
 * @param array $extra Extra template values (e.g. section).
 * @return string Status: sent | dry_run | failed | skipped.
 */
function SmartCampusNotifyGuardian( $conn, int $application_id, string $template, array $extra = [] ): string
{
	try
	{
		$result = pg_query_params(
			$conn,
			'SELECT id, ref, first_name, learner_name, parent_contact FROM enrollment_applications WHERE id = $1',
			[ $application_id ]
		);
		$app = $result ? pg_fetch_assoc( $result ) : false;

		if ( ! $app )
		{
			return 'skipped';
		}

		if ( empty( $app['first_name'] ) )
		{
			$app['first_name'] = strtok( (string) $app['learner_name'], ' ' );
		}

		$mobile = SmartCampusNormalizeMobile( (string) $app['parent_contact'] );

		if ( $mobile === null )
		{
			[ $status, $message_id, $detail ] = [ 'skipped', null, 'not a PH mobile number' ];
			$masked = '***';
		}
		else
		{
			[ $status, $message_id, $detail ] = SmartCampusSemaphoreSend( $mobile, SmartCampusSmsText( $template, $app + $extra ) );
			$masked = SmartCampusMaskMobile( $mobile );
		}

		pg_query_params(
			$conn,
			'INSERT INTO sms_log (application_id, template, phone_masked, status, provider_message_id, detail)
			VALUES ($1, $2, $3, $4, $5, $6)',
			[ $application_id, $template, $masked, $status, $message_id, $detail ]
		);

		if ( $status === 'failed' )
		{
			error_log( '[SMS] ' . $template . ' to ' . $masked . ' failed: ' . $detail );
		}

		return $status;
	}
	catch ( Throwable $e )
	{
		error_log( '[SMS] ' . $template . ' notification error: ' . $e->getMessage() );

		return 'failed';
	}
}
