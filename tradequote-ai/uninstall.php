<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tqa_invoices" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tqa_quotes" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tqa_customers" );

delete_option( 'tqa_version' );
delete_option( 'tqa_settings' );
delete_option( 'tqa_quote_counter' );
delete_option( 'tqa_invoice_counter' );
