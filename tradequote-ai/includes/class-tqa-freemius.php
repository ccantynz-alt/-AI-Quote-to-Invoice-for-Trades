<?php
/**
 * Freemius SDK integration.
 *
 * The Freemius SDK is NOT bundled — download it from your Freemius dashboard,
 * place it at vendor/freemius/wordpress-sdk/start.php, then update the
 * credentials below (app_id, public_key).
 *
 * Without the SDK the plugin runs in free-tier-only mode.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function tqa_fs() {
	global $tqa_fs;

	if ( ! isset( $tqa_fs ) ) {
		$sdk = TQA_PLUGIN_DIR . 'vendor/freemius/wordpress-sdk/start.php';

		if ( file_exists( $sdk ) ) {
			require_once $sdk;
			$tqa_fs = fs_dynamic_init( array(
				'id'                  => '0000',        // Replace with your Freemius App ID.
				'slug'                => 'tradequote-ai',
				'type'                => 'plugin',
				'public_key'          => 'pk_YOUR_PUBLIC_KEY', // Replace with your public key.
				'is_premium'          => false,
				'premium_suffix'      => 'Pro',
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'trial'               => array(
					'days'               => 14,
					'is_require_payment' => false,
				),
				'menu'                => array(
					'slug'           => 'tradequote-ai',
					'contact'        => false,
					'support'        => false,
				),
			) );
		} else {
			$tqa_fs = null;
		}
	}

	return $tqa_fs;
}

// Initialise Freemius early.
tqa_fs();
do_action( 'tqa_fs_loaded' );

/**
 * Returns true if the site has an active paid plan (Pro or Business).
 */
function tqa_is_pro() {
	$fs = tqa_fs();
	if ( $fs ) {
		return $fs->is__premium_only() || $fs->is_plan( 'pro', true ) || $fs->is_plan( 'business', true );
	}
	// Fallback: check a manually entered license key stored in settings.
	$key = TQA_Settings::get( 'license_key', '' );
	return ! empty( $key ) && tqa_verify_license_key( $key );
}

/**
 * Offline license key verification fallback (HMAC-based).
 * Keys are generated as: base64_encode( 'tqa_pro:' . hash_hmac( 'sha256', 'tqa_pro:' . $site_url, SECRET ) )
 */
function tqa_verify_license_key( string $key ) {
	$secret   = defined( 'TQA_LICENSE_SECRET' ) ? TQA_LICENSE_SECRET : AUTH_KEY;
	$site_url = home_url();
	$expected = base64_encode( 'tqa_pro:' . hash_hmac( 'sha256', 'tqa_pro:' . $site_url, $secret ) );
	return hash_equals( $expected, $key );
}

/**
 * Returns remaining free-tier quote allowance for the current month.
 */
function tqa_quota_remaining() {
	if ( tqa_is_pro() ) {
		return PHP_INT_MAX;
	}
	return max( 0, TQA_FREE_QUOTA - TQA_Quote::count_this_month() );
}

/**
 * Returns true if the current site can create another quote.
 */
function tqa_can_create_quote() {
	return tqa_quota_remaining() > 0;
}
