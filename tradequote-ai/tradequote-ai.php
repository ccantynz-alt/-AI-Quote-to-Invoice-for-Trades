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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TQA_VERSION', '1.0.0' );
define( 'TQA_PLUGIN_FILE', __FILE__ );
define( 'TQA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TQA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TQA_FREE_QUOTA', 5 );

require_once TQA_PLUGIN_DIR . 'includes/class-tqa-loader.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-activator.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-deactivator.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-settings.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-customer.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-quote.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-invoice.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-ai.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-pdf.php';
require_once TQA_PLUGIN_DIR . 'includes/class-tqa-email.php';
require_once TQA_PLUGIN_DIR . 'admin/class-tqa-admin.php';

register_activation_hook( __FILE__, array( 'TQA_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TQA_Deactivator', 'deactivate' ) );

function tqa_run() {
	$loader = new TQA_Loader();
	$admin  = new TQA_Admin();
	$admin->register( $loader );
	$loader->run();
}

tqa_run();
