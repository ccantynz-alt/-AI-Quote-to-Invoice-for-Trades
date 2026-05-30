<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Customer {

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'tqa_customers';
	}

	public static function create( array $data ) {
		global $wpdb;
		$wpdb->insert( self::table(), array(
			'name'       => sanitize_text_field( $data['name'] ?? '' ),
			'email'      => sanitize_email( $data['email'] ?? '' ),
			'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
			'address'    => sanitize_textarea_field( $data['address'] ?? '' ),
			'created_at' => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$wpdb->update( self::table(), array(
			'name'    => sanitize_text_field( $data['name'] ?? '' ),
			'email'   => sanitize_email( $data['email'] ?? '' ),
			'phone'   => sanitize_text_field( $data['phone'] ?? '' ),
			'address' => sanitize_textarea_field( $data['address'] ?? '' ),
		), array( 'id' => $id ) );
	}

	public static function get( int $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function get_all( array $args = array() ) {
		global $wpdb;
		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 100;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
		$search = isset( $args['search'] ) ? '%' . $wpdb->esc_like( $args['search'] ) . '%' : '';

		if ( $search ) {
			return $wpdb->get_results( $wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE name LIKE %s OR email LIKE %s ORDER BY name ASC LIMIT %d OFFSET %d',
				$search, $search, $limit, $offset
			) );
		}
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' ORDER BY name ASC LIMIT %d OFFSET %d',
			$limit, $offset
		) );
	}

	public static function find_or_create( array $data ) {
		global $wpdb;
		$email = sanitize_email( $data['email'] ?? '' );
		if ( $email ) {
			$existing = $wpdb->get_row( $wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE email = %s',
				$email
			) );
			if ( $existing ) {
				return (int) $existing->id;
			}
		}
		return self::create( $data );
	}

	public static function delete( int $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => $id ) );
	}
}
