<?php
/**
 * Plugin Name: TradeQuote AI
 * Plugin URI:  https://tradequoteai.com
 * Description: AI-powered quoting and invoicing for trades and local service businesses. Generate professional quotes in 2 minutes.
 * Version:     1.0.0
 * Author:      TradeQuote AI
 * Author URI:  https://tradequoteai.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: tradequote-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TQA_VERSION',    '1.0.0' );
define( 'TQA_PLUGIN_FILE', __FILE__ );
define( 'TQA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'TQA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'TQA_FREE_QUOTA',  5 );

// Core includes — order matters.
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-loader.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-settings.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-activator.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-deactivator.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-freemius.php';   // defines tqa_can_create_quote()
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-customer.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-quote.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-invoice.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-ai.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-pdf.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-email.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-admin-notices.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-public.php';
require_once TQA_PLUGIN_DIR . 'admin/class-tqa-admin.php';

register_activation_hook( __FILE__, array( 'TQA_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TQA_Deactivator', 'deactivate' ) );

function tqa_run() {
	// i18n.
	add_action( 'plugins_loaded', 'tqa_load_textdomain' );

	// Activation redirect.
	add_action( 'admin_init', 'tqa_maybe_redirect_after_activation' );

	// Public portal.
	TQA_Public::register();

	// Admin notices.
	TQA_Admin_Notices::register();

	// Admin UI and REST API.
	$loader = new TQA_Loader();
	$admin  = new TQA_Admin();
	$admin->register( $loader );
	$loader->run();
}

function tqa_load_textdomain() {
	load_plugin_textdomain( 'tradequote-ai', false, dirname( plugin_basename( TQA_PLUGIN_FILE ) ) . '/languages' );
}

function tqa_maybe_redirect_after_activation() {
	if ( get_transient( 'tqa_activation_redirect' ) ) {
		delete_transient( 'tqa_activation_redirect' );
		if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=tqa-settings&welcome=1' ) );
			exit;
		}
	}
}

tqa_run();
