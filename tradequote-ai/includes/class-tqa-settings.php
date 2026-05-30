<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Settings {

	private static $defaults = array(
		'business_name'        => '',
		'business_logo'        => '',
		'tax_number'           => '',
		'default_tax_rate'     => '15',
		'payment_terms'        => 'Payment due within 14 days',
		'bank_details'         => '',
		'quote_valid_days'     => '30',
		'claude_api_key'       => '',
		'email_from_name'      => '',
		'email_from_address'   => '',
		'quote_prefix'         => 'Q-',
		'invoice_prefix'       => 'INV-',
	);

	public static function get( $key, $fallback = null ) {
		$all = get_option( 'tqa_settings', array() );
		if ( isset( $all[ $key ] ) ) {
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
		$allowed  = array_keys( self::$defaults );
		foreach ( $allowed as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$current[ $key ] = sanitize_text_field( $data[ $key ] );
			}
		}
		// Textarea fields need different sanitisation.
		foreach ( array( 'payment_terms', 'bank_details' ) as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$current[ $key ] = sanitize_textarea_field( $data[ $key ] );
			}
		}
		update_option( 'tqa_settings', $current );
	}

	public static function get_defaults() {
		return self::$defaults;
	}
}
