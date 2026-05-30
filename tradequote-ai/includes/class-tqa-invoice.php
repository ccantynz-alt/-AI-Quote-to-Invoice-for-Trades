<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Invoice {

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'tqa_invoices';
	}

	private static function next_number() {
		$counter = (int) get_option( 'tqa_invoice_counter', 0 ) + 1;
		update_option( 'tqa_invoice_counter', $counter );
		$prefix = TQA_Settings::get( 'invoice_prefix', 'INV-' );
		return $prefix . date( 'Y' ) . '-' . str_pad( $counter, 4, '0', STR_PAD_LEFT );
	}

	public static function create( array $data ) {
		global $wpdb;

		$line_items = isset( $data['line_items'] ) ? wp_json_encode( $data['line_items'] ) : '[]';
		$subtotal   = (float) ( $data['subtotal'] ?? 0 );
		$tax_rate   = (float) ( $data['tax_rate'] ?? TQA_Settings::get( 'default_tax_rate', 0 ) );
		$tax_amount = round( $subtotal * ( $tax_rate / 100 ), 2 );
		$total      = round( $subtotal + $tax_amount, 2 );

		$due_days = 14;
		$due_date = isset( $data['due_date'] ) ? $data['due_date'] : date( 'Y-m-d', strtotime( "+{$due_days} days" ) );

		$wpdb->insert( self::table(), array(
			'invoice_number' => self::next_number(),
			'quote_id'       => isset( $data['quote_id'] ) ? (int) $data['quote_id'] : null,
			'customer_id'    => (int) ( $data['customer_id'] ?? 0 ),
			'line_items'     => $line_items,
			'subtotal'       => $subtotal,
			'tax_amount'     => $tax_amount,
			'total'          => $total,
			'status'         => 'draft',
			'due_date'       => $due_date,
			'paid_at'        => null,
			'created_at'     => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	public static function create_from_quote( int $quote_id, string $due_date = '' ) {
		$quote = TQA_Quote::get( $quote_id );
		if ( ! $quote ) {
			return new WP_Error( 'not_found', 'Quote not found' );
		}

		return self::create( array(
			'quote_id'    => $quote_id,
			'customer_id' => $quote->customer_id,
			'line_items'  => $quote->line_items,
			'subtotal'    => $quote->subtotal,
			'tax_rate'    => $quote->tax_rate,
			'due_date'    => $due_date ?: '',
		) );
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$update = array();

		if ( isset( $data['line_items'] ) ) {
			$update['line_items'] = wp_json_encode( $data['line_items'] );
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_text_field( $data['status'] );
			if ( $data['status'] === 'paid' && empty( $data['paid_at'] ) ) {
				$update['paid_at'] = current_time( 'mysql' );
			}
		}
		if ( isset( $data['due_date'] ) ) {
			$update['due_date'] = sanitize_text_field( $data['due_date'] );
		}

		$wpdb->update( self::table(), $update, array( 'id' => $id ) );
	}

	public static function mark_paid( int $id ) {
		self::update( $id, array( 'status' => 'paid' ) );
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
			$where[]  = 'i.status = %s';
			$values[] = $args['status'];
		}
		if ( ! empty( $args['customer_id'] ) ) {
			$where[]  = 'i.customer_id = %d';
			$values[] = (int) $args['customer_id'];
		}

		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 50;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		$sql = 'SELECT i.*, c.name AS customer_name, c.email AS customer_email
				FROM ' . self::table() . ' i
				LEFT JOIN ' . $wpdb->prefix . 'tqa_customers c ON i.customer_id = c.id
				WHERE ' . implode( ' AND ', $where ) . '
				ORDER BY i.created_at DESC
				LIMIT %d OFFSET %d';

		$values[] = $limit;
		$values[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	public static function outstanding_total() {
		global $wpdb;
		return (float) $wpdb->get_var(
			"SELECT SUM(total) FROM " . self::table() . " WHERE status IN ('sent','overdue')"
		);
	}

	public static function delete( int $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => $id ) );
	}
}
