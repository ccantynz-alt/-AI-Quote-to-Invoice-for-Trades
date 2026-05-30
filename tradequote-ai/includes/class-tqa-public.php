<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public-facing quote acceptance portal.
 *
 * Customers visit: https://yoursite.com/?tqa_accept=TOKEN
 * They can accept or decline the quote without a WordPress account.
 */
class TQA_Public {

	public static function register() {
		add_action( 'init',              array( __CLASS__, 'add_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_handle_portal' ) );
		add_action( 'rest_api_init',     array( __CLASS__, 'register_public_routes' ) );
	}

	public static function add_query_vars() {
		add_rewrite_tag( '%tqa_accept%', '([a-f0-9]{32,64})' );
	}

	public static function maybe_handle_portal() {
		$token = get_query_var( 'tqa_accept' );
		if ( empty( $token ) ) {
			$token = $_GET['tqa_accept'] ?? '';
		}
		if ( empty( $token ) ) return;

		$quote = TQA_Quote::get_by_token( sanitize_text_field( $token ) );

		if ( ! $quote ) {
			wp_die( 'Quote not found or this link has expired.', 'Quote Not Found', array( 'response' => 404 ) );
		}

		$action = $_POST['tqa_action'] ?? '';
		if ( $action && check_admin_referer( 'tqa_accept_' . $token ) ) {
			if ( in_array( $action, array( 'accept', 'decline' ), true ) ) {
				$new_status = $action === 'accept' ? 'accepted' : 'declined';
				TQA_Quote::update_status( (int) $quote->id, $new_status );
				if ( $new_status === 'accepted' ) {
					self::notify_owner_accepted( $quote );
				}
				$quote = TQA_Quote::get( (int) $quote->id );
			}
		}

		$customer = TQA_Customer::get( (int) $quote->customer_id );
		$settings = TQA_Settings::get_all();

		include TQA_PLUGIN_DIR . 'templates/quote-acceptance.php';
		exit;
	}

	public static function register_public_routes() {
		$ns = 'tradequote-ai/v1';

		register_rest_route( $ns, '/public/quotes/(?P<token>[a-f0-9]{32,64})', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'api_get_public_quote' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( $ns, '/public/quotes/(?P<token>[a-f0-9]{32,64})/respond', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'api_respond_quote' ),
			'permission_callback' => '__return_true',
		) );
	}

	public static function api_get_public_quote( WP_REST_Request $req ) {
		$token = sanitize_text_field( $req['token'] );
		$quote = TQA_Quote::get_by_token( $token );
		if ( ! $quote ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}
		$customer = TQA_Customer::get( (int) $quote->customer_id );
		return rest_ensure_response( array(
			'quote_number' => $quote->quote_number,
			'status'       => $quote->status,
			'total'        => $quote->total,
			'valid_until'  => $quote->valid_until,
			'line_items'   => $quote->line_items,
			'customer'     => $customer ? array( 'name' => $customer->name ) : null,
			'business'     => TQA_Settings::get( 'business_name', '' ),
		) );
	}

	public static function api_respond_quote( WP_REST_Request $req ) {
		$token  = sanitize_text_field( $req['token'] );
		$quote  = TQA_Quote::get_by_token( $token );
		if ( ! $quote ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}
		$data   = $req->get_json_params();
		$action = sanitize_text_field( $data['action'] ?? '' );
		if ( ! in_array( $action, array( 'accept', 'decline' ), true ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid action.' ), 400 );
		}
		if ( in_array( $quote->status, array( 'accepted', 'declined', 'expired' ), true ) ) {
			return new WP_REST_Response( array( 'error' => 'This quote can no longer be changed.' ), 409 );
		}
		$new_status = $action === 'accept' ? 'accepted' : 'declined';
		TQA_Quote::update_status( (int) $quote->id, $new_status );
		if ( $new_status === 'accepted' ) {
			self::notify_owner_accepted( $quote );
		}
		return rest_ensure_response( array( 'status' => $new_status ) );
	}

	private static function notify_owner_accepted( $quote ) {
		$to      = get_option( 'admin_email' );
		$from    = TQA_Settings::get( 'email_from_address', $to );
		$biz     = TQA_Settings::get( 'business_name', get_bloginfo( 'name' ) );
		$subject = sprintf( '[%s] Quote %s has been ACCEPTED', $biz, $quote->quote_number );
		$body    = sprintf(
			"Great news! Your quote <strong>%s</strong> for $%s has been accepted by the customer.\n\nLog in to convert it to an invoice: %s",
			$quote->quote_number,
			number_format( (float) $quote->total, 2 ),
			admin_url( 'admin.php?page=tqa-quotes' )
		);
		wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $biz . ' <' . $from . '>' ) );
	}
}
