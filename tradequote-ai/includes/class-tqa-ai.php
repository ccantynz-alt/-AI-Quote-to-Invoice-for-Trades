<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_AI {

	private const API_URL   = 'https://api.anthropic.com/v1/messages';
	private const MODEL     = 'claude-sonnet-4-20250514';
	private const MAX_TOKENS = 2048;

	private static function api_key() {
		return TQA_Settings::get( 'claude_api_key', '' );
	}

	public static function generate_quote( string $job_description ) {
		$api_key = self::api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'Claude API key is not configured. Please add your API key in TradeQuote AI settings.' );
		}

		$system = 'You are a trades quoting assistant for skilled tradespeople (plumbers, electricians, builders, painters, landscapers, HVAC technicians, cleaners). '
			. 'Extract structured line items from a job description provided by a tradie. '
			. 'Be realistic with pricing — use market rates for the described trade and region if inferrable. '
			. 'Return ONLY valid JSON with no markdown, no code fences, no explanation. '
			. 'JSON structure: { "line_items": [{"description": "string", "qty": number, "unit": "string", "unit_price": number, "total": number}], '
			. '"subtotal": number, "notes": "string", "suggested_terms": "string" }. '
			. 'The "unit" field should be one of: ea, hr, m, m2, m3, ls (lump sum), or similar trade-standard unit. '
			. 'Round all monetary values to 2 decimal places.';

		$body = wp_json_encode( array(
			'model'      => self::MODEL,
			'max_tokens' => self::MAX_TOKENS,
			'system'     => $system,
			'messages'   => array(
				array( 'role' => 'user', 'content' => sanitize_textarea_field( $job_description ) ),
			),
		) );

		$response = wp_remote_post( self::API_URL, array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', 'Could not reach Claude API: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code !== 200 ) {
			$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown API error (HTTP ' . $code . ')';
			return new WP_Error( 'api_error', $msg );
		}

		$text = $data['content'][0]['text'] ?? '';
		if ( empty( $text ) ) {
			return new WP_Error( 'empty_response', 'Claude returned an empty response.' );
		}

		// Strip accidental markdown code fences.
		$text = preg_replace( '/^```[a-z]*\n?/m', '', $text );
		$text = preg_replace( '/```$/m', '', $text );
		$text = trim( $text );

		$parsed = json_decode( $text, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $parsed['line_items'] ) ) {
			return new WP_Error( 'parse_error', 'Could not parse AI response. Please try again or simplify your description.' );
		}

		return self::sanitise_parsed( $parsed );
	}

	private static function sanitise_parsed( array $parsed ) {
		$line_items = array();
		foreach ( (array) $parsed['line_items'] as $item ) {
			$qty        = (float) ( $item['qty'] ?? 1 );
			$unit_price = (float) ( $item['unit_price'] ?? 0 );
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
}
