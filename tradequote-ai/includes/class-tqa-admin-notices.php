<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Admin_Notices {

	public static function register() {
		add_action( 'admin_notices', array( __CLASS__, 'render' ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Only show on TQA pages.
		$page = $_GET['page'] ?? '';
		if ( strpos( $page, 'tradequote' ) === false && strpos( $page, 'tqa-' ) === false ) {
			// Also show on the main dashboard so the user notices.
			if ( get_current_screen()->id !== 'dashboard' ) return;
		}

		$settings_url = admin_url( 'admin.php?page=tqa-settings' );

		if ( empty( TQA_Settings::get( 'claude_api_key', '' ) ) ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			printf(
				'<strong>TradeQuote AI:</strong> No Claude API key configured. <a href="%s">Add your API key in Settings</a> to start generating quotes with AI.',
				esc_url( $settings_url )
			);
			echo '</p></div>';
		}

		if ( empty( TQA_Settings::get( 'business_name', '' ) ) ) {
			echo '<div class="notice notice-info is-dismissible"><p>';
			printf(
				'<strong>TradeQuote AI:</strong> Set your business name and details in <a href="%s">Settings</a> so they appear on your quotes and invoices.',
				esc_url( $settings_url )
			);
			echo '</p></div>';
		}

		if ( ! function_exists( 'tqa_is_pro' ) || ! tqa_is_pro() ) {
			$remaining = tqa_quota_remaining();
			if ( $remaining <= 1 && ! empty( TQA_Settings::get( 'claude_api_key', '' ) ) ) {
				echo '<div class="notice notice-warning is-dismissible"><p>';
				printf(
					'<strong>TradeQuote AI:</strong> You have <strong>%d</strong> free quote%s remaining this month. <a href="%s" target="_blank">Upgrade to Pro</a> for unlimited quotes.',
					$remaining,
					$remaining === 1 ? '' : 's',
					'https://tradequoteai.com/#pricing'
				);
				echo '</p></div>';
			}
		}

		$mpdf = TQA_PLUGIN_DIR . 'vendor/mpdf/mpdf/src/Mpdf.php';
		if ( ! file_exists( $mpdf ) ) {
			echo '<div class="notice notice-info is-dismissible"><p>';
			echo '<strong>TradeQuote AI:</strong> mPDF is not installed — PDFs will be generated as HTML files until you run <code>composer install</code> in the plugin folder.';
			echo '</p></div>';
		}
	}
}
