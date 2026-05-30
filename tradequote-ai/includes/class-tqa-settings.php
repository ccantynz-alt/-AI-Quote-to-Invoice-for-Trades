<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Settings {

	private static $defaults = array(
		'business_name'        => '',
		'business_logo'        => '',
		'tax_number'           => '',
		'default_tax_rate'     => '15',
		'currency_symbol'      => '$',
		'currency_code'        => 'NZD',
		'payment_terms'        => 'Payment due within 14 days',
		'bank_details'         => '',
		'quote_valid_days'     => '30',
		'claude_api_key'       => '',
		'email_from_name'      => '',
		'email_from_address'   => '',
		'quote_prefix'         => 'Q-',
		'invoice_prefix'       => 'INV-',
		'license_key'          => '',
		'email_quote_subject'  => 'Your quote from {business_name}',
		'email_invoice_subject'=> 'Invoice from {business_name}',
	);

	public static function get( $key, $fallback = null ) {
		$all = get_option( 'tqa_settings', array() );
		if ( isset( $all[ $key ] ) && $all[ $key ] !== '' ) {
			return $all[ $key ];
		}
		if ( isset( self::$defaults[ $key ] ) ) {
			return self::$defaults[ $key ];
		}
		return $fallback;
	}

	public static function get_all() {
		$saved = get_option( 'tqa_settings', array() );
		return array_merge( self::$defaults, $saved );
	}

	public static function save( array $data ) {
		$current = get_option( 'tqa_settings', array() );

		$text_fields = array(
			'business_name', 'tax_number', 'default_tax_rate', 'currency_symbol',
			'currency_code', 'quote_valid_days', 'email_from_name', 'email_from_address',
			'quote_prefix', 'invoice_prefix', 'license_key', 'email_quote_subject',
			'email_invoice_subject',
		);
		$textarea_fields = array( 'payment_terms', 'bank_details' );
		$url_fields      = array( 'business_logo' );

		foreach ( $text_fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$current[ $key ] = sanitize_text_field( $data[ $key ] );
			}
		}
		foreach ( $textarea_fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$current[ $key ] = sanitize_textarea_field( $data[ $key ] );
			}
		}
		foreach ( $url_fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$current[ $key ] = esc_url_raw( $data[ $key ] );
			}
		}

		// API key: never overwrite with an empty string.
		if ( ! empty( $data['claude_api_key'] ) && strpos( $data['claude_api_key'], '•' ) === false ) {
			$current['claude_api_key'] = sanitize_text_field( $data['claude_api_key'] );
		}

		update_option( 'tqa_settings', $current );
	}

	public static function get_defaults() {
		return self::$defaults;
	}

	public static function format_currency( float $amount ) {
		$symbol = self::get( 'currency_symbol', '$' );
		return $symbol . number_format( $amount, 2 );
	}
}
