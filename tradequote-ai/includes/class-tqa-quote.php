<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Quote {

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'tqa_quotes';
	}

	private static function next_number() {
		$counter = (int) get_option( 'tqa_quote_counter', 0 ) + 1;
		update_option( 'tqa_quote_counter', $counter );
		$prefix = TQA_Settings::get( 'quote_prefix', 'Q-' );
		return $prefix . date( 'Y' ) . '-' . str_pad( $counter, 4, '0', STR_PAD_LEFT );
	}

	public static function create( array $data ) {
		global $wpdb;

		$line_items = isset( $data['line_items'] ) ? wp_json_encode( $data['line_items'] ) : '[]';
		$subtotal   = (float) ( $data['subtotal'] ?? 0 );
		$tax_rate   = (float) ( $data['tax_rate'] ?? TQA_Settings::get( 'default_tax_rate', 0 ) );
		$tax_amount = round( $subtotal * ( $tax_rate / 100 ), 2 );
		$total      = round( $subtotal + $tax_amount, 2 );

		$valid_days  = (int) TQA_Settings::get( 'quote_valid_days', 30 );
		$valid_until = isset( $data['valid_until'] ) ? $data['valid_until'] : date( 'Y-m-d', strtotime( "+{$valid_days} days" ) );

		$wpdb->insert( self::table(), array(
			'quote_number'    => self::next_number(),
			'customer_id'     => (int) ( $data['customer_id'] ?? 0 ),
			'job_description' => sanitize_textarea_field( $data['job_description'] ?? '' ),
			'line_items'      => $line_items,
			'subtotal'        => $subtotal,
			'tax_rate'        => $tax_rate,
			'tax_amount'      => $tax_amount,
			'total'           => $total,
			'status'          => 'draft',
			'notes'           => sanitize_textarea_field( $data['notes'] ?? '' ),
			'valid_until'     => $valid_until,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	public static function update( int $id, array $data ) {
		global $wpdb;

		$update = array();

		if ( isset( $data['line_items'] ) ) {
			$update['line_items'] = wp_json_encode( $data['line_items'] );
		}
		if ( isset( $data['subtotal'] ) ) {
			$subtotal               = (float) $data['subtotal'];
			$tax_rate               = (float) ( $data['tax_rate'] ?? self::get( $id )->tax_rate );
			$tax_amount             = round( $subtotal * ( $tax_rate / 100 ), 2 );
			$update['subtotal']     = $subtotal;
			$update['tax_rate']     = $tax_rate;
			$update['tax_amount']   = $tax_amount;
			$update['total']        = round( $subtotal + $tax_amount, 2 );
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_text_field( $data['status'] );
		}
		if ( isset( $data['notes'] ) ) {
			$update['notes'] = sanitize_textarea_field( $data['notes'] );
		}
		if ( isset( $data['valid_until'] ) ) {
			$update['valid_until'] = sanitize_text_field( $data['valid_until'] );
		}
		if ( isset( $data['customer_id'] ) ) {
			$update['customer_id'] = (int) $data['customer_id'];
		}
		if ( isset( $data['job_description'] ) ) {
			$update['job_description'] = sanitize_textarea_field( $data['job_description'] );
		}

		$update['updated_at'] = current_time( 'mysql' );
		$wpdb->update( self::table(), $update, array( 'id' => $id ) );
	}

	public static function update_status( int $id, string $status ) {
		self::update( $id, array( 'status' => $status ) );
	}

	public static function get( int $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
		if ( $row && $row->line_items ) {
			$row->line_items = json_decode( $row->line_items, true );
		}
		return $row;
	}

	public static function get_all( array $args = array() ) {
		global $wpdb;
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'q.status = %s';
			$values[] = $args['status'];
		}
		if ( ! empty( $args['customer_id'] ) ) {
			$where[]  = 'q.customer_id = %d';
			$values[] = (int) $args['customer_id'];
		}

		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 50;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		$sql = 'SELECT q.*, c.name AS customer_name, c.email AS customer_email
				FROM ' . self::table() . ' q
				LEFT JOIN ' . $wpdb->prefix . 'tqa_customers c ON q.customer_id = c.id
				WHERE ' . implode( ' AND ', $where ) . '
				ORDER BY q.created_at DESC
				LIMIT %d OFFSET %d';

		$values[] = $limit;
		$values[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	public static function count_this_month() {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::table() . ' WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d',
			date( 'Y' ), date( 'n' )
		) );
	}

	public static function acceptance_rate() {
		global $wpdb;
		$total    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE status != 'draft'" );
		$accepted = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE status = 'accepted'" );
		if ( $total === 0 ) return 0;
		return round( ( $accepted / $total ) * 100, 1 );
	}

	public static function check_quota() {
		// Returns true if user is within free quota.
		$count = self::count_this_month();
		return $count < TQA_FREE_QUOTA;
	}

	public static function delete( int $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => $id ) );
	}
}
