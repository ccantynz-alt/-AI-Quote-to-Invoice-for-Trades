<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_AI {

	private const API_URL    = 'https://api.anthropic.com/v1/messages';
	private const MODEL      = 'claude-sonnet-4-20250514';
	private const MAX_TOKENS = 2048;

	private static $trade_prompts = array(
		'general'      => 'trades and local service businesses',
		'plumber'      => 'plumbing — use units like hr, ea, m for pipe lengths. Common items: labour (hr), materials (ea/ls), call-out fee (ls).',
		'electrician'  => 'electrical work — use units like hr, ea, m for cable runs. Common items: labour (hr), fittings/materials (ea), board upgrades (ls).',
		'builder'      => 'building and construction — use units like hr, m2, m3, ls, sheet, pack. Common items: framing labour (hr), materials (m2/sheet), concrete (m3).',
		'painter'      => 'painting and decorating — use units like hr, m2, L (litres of paint). Common items: prep/labour (hr), paint (L), primer (L), area coverage (m2).',
		'landscaper'   => 'landscaping and gardening — use units like hr, m2, m3, ea, tonne. Common items: labour (hr), turf/plants (m2/ea), soil/bark (m3), plants (ea).',
		'hvac'         => 'HVAC — heating, ventilation, and air conditioning — use units like hr, ea, ls. Common items: install labour (hr), unit supply (ea), refrigerant (kg).',
		'cleaner'      => 'cleaning services — use units like hr, ls, m2. Common items: labour (hr), cleaning products (ls), room/area pricing (m2).',
		'roofer'       => 'roofing — use units like hr, m2, ea, ls. Common items: labour (hr), roofing iron/tiles (m2), flashing (m), guttering (m).',
		'tiler'        => 'tiling — use units like hr, m2, ea. Common items: labour (hr), tiles (m2), adhesive (ls), grout (ls).',
	);

	private static function api_key() {
		return TQA_Settings::get( 'claude_api_key', '' );
	}

	public static function generate_quote( string $job_description, string $trade_type = 'general' ) {
		$api_key = self::api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'Claude API key is not configured. Please add your API key in TradeQuote AI Settings.', 'tradequote-ai' ) );
		}

		if ( ! tqa_can_create_quote() ) {
			return new WP_Error(
				'quota_exceeded',
				sprintf(
					__( 'You have used all %d free quotes this month. <a href="%s" target="_blank">Upgrade to Pro</a> for unlimited quotes.', 'tradequote-ai' ),
					TQA_FREE_QUOTA,
					'https://tradequoteai.com/#pricing'
				)
			);
		}

		$trade_context = self::$trade_prompts[ $trade_type ] ?? self::$trade_prompts['general'];

		$system = 'You are a quoting assistant for ' . $trade_context . ' '
			. 'Extract structured line items from a job description provided by a tradie. '
			. 'Use realistic market pricing for the trade described. '
			. 'Return ONLY valid JSON with no markdown, no code fences, no explanation. '
			. 'JSON structure: { "line_items": [{"description": "string", "qty": number, "unit": "string", "unit_price": number, "total": number}], '
			. '"subtotal": number, "notes": "string", "suggested_terms": "string" }. '
			. 'Round all monetary values to 2 decimal places. '
			. 'If the description is too vague, make reasonable assumptions and note them in the "notes" field.';

		$body = wp_json_encode( array(
			'model'      => self::MODEL,
			'max_tokens' => self::MAX_TOKENS,
			'system'     => $system,
			'messages'   => array(
				array( 'role' => 'user', 'content' => sanitize_textarea_field( $job_description ) ),
			),
		) );

		$response = wp_remote_post( self::API_URL, array(
			'timeout' => 45,
			'headers' => array(
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', __( 'Could not reach Claude API: ', 'tradequote-ai' ) . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code !== 200 ) {
			$msg = $data['error']['message'] ?? ( 'API error (HTTP ' . $code . ')' );
			return new WP_Error( 'api_error', $msg );
		}

		$text = $data['content'][0]['text'] ?? '';
		if ( empty( $text ) ) {
			return new WP_Error( 'empty_response', __( 'Claude returned an empty response. Please try again.', 'tradequote-ai' ) );
		}

		// Strip accidental markdown code fences.
		$text = preg_replace( '/^```[a-z]*\n?/m', '', $text );
		$text = preg_replace( '/```$/m', '', $text );
		$text = trim( $text );

		$parsed = json_decode( $text, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $parsed['line_items'] ) ) {
			return new WP_Error( 'parse_error', __( 'Could not parse the AI response. Please simplify your job description and try again.', 'tradequote-ai' ) );
		}

		return self::sanitise_parsed( $parsed );
	}

	private static function sanitise_parsed( array $parsed ) {
		$line_items = array();
		foreach ( (array) $parsed['line_items'] as $item ) {
			$qty        = max( 0, (float) ( $item['qty'] ?? 1 ) );
			$unit_price = max( 0, (float) ( $item['unit_price'] ?? 0 ) );
			$line_items[] = array(
				'description' => sanitize_text_field( $item['description'] ?? '' ),
				'qty'         => $qty,
				'unit'        => sanitize_text_field( $item['unit'] ?? 'ea' ),
				'unit_price'  => round( $unit_price, 2 ),
				'total'       => round( $qty * $unit_price, 2 ),
			);
		}

		$subtotal = 0;
		foreach ( $line_items as $item ) {
			$subtotal += $item['total'];
		}

		return array(
			'line_items'      => $line_items,
			'subtotal'        => round( $subtotal, 2 ),
			'notes'           => sanitize_textarea_field( $parsed['notes'] ?? '' ),
			'suggested_terms' => sanitize_textarea_field( $parsed['suggested_terms'] ?? '' ),
		);
	}

	public static function get_trade_types() {
		return array_keys( self::$trade_prompts );
	}

	/**
	 * Test an API key by making a minimal request.
	 */
	public static function test_api_key( string $api_key ) {
		$body = wp_json_encode( array(
			'model'      => self::MODEL,
			'max_tokens' => 10,
			'messages'   => array( array( 'role' => 'user', 'content' => 'Hi' ) ),
		) );

		$response = wp_remote_post( self::API_URL, array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code === 200 ) return true;
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return new WP_Error( 'invalid_key', $data['error']['message'] ?? 'Invalid API key' );
	}
}
