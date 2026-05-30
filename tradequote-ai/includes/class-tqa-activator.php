<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Activator {

	public static function activate() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$customers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tqa_customers (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL DEFAULT '',
			email VARCHAR(255) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			address TEXT NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email (email)
		) $charset;";

		$quotes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tqa_quotes (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			quote_number VARCHAR(50) NOT NULL DEFAULT '',
			customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			job_description TEXT NOT NULL DEFAULT '',
			line_items LONGTEXT NOT NULL DEFAULT '',
			subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
			tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			status ENUM('draft','sent','accepted','declined','expired') NOT NULL DEFAULT 'draft',
			notes TEXT NOT NULL DEFAULT '',
			acceptance_token VARCHAR(64) DEFAULT NULL,
			valid_until DATE DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY quote_number (quote_number),
			UNIQUE KEY acceptance_token (acceptance_token),
			KEY customer_id (customer_id),
			KEY status (status)
		) $charset;";

		$invoices = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tqa_invoices (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			invoice_number VARCHAR(50) NOT NULL DEFAULT '',
			quote_id BIGINT UNSIGNED DEFAULT NULL,
			customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			line_items LONGTEXT NOT NULL DEFAULT '',
			subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			status ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
			due_date DATE DEFAULT NULL,
			paid_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY invoice_number (invoice_number),
			KEY customer_id (customer_id),
			KEY quote_id (quote_id),
			KEY status (status)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $customers );
		dbDelta( $quotes );
		dbDelta( $invoices );

		if ( ! get_option( 'tqa_version' ) ) {
			add_option( 'tqa_version', TQA_VERSION );
			add_option( 'tqa_quote_counter', 0 );
			add_option( 'tqa_invoice_counter', 0 );
			// Flag to redirect to settings on first activation.
			set_transient( 'tqa_activation_redirect', true, 30 );
		} else {
			update_option( 'tqa_version', TQA_VERSION );
		}
	}
}
