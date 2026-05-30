<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Admin {

	public function register( TQA_Loader $loader ) {
		$loader->add_action( 'admin_menu', $this, 'add_menu_pages' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_assets' );
		$loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );
		$loader->add_action( 'admin_post_tqa_save_settings', $this, 'handle_save_settings' );
		$loader->add_action( 'admin_post_tqa_download_quote_pdf', $this, 'handle_download_quote_pdf' );
		$loader->add_action( 'admin_post_tqa_download_invoice_pdf', $this, 'handle_download_invoice_pdf' );
	}

	public function add_menu_pages() {
		add_menu_page(
			'TradeQuote AI',
			'TradeQuote AI',
			'manage_options',
			'tradequote-ai',
			array( $this, 'page_dashboard' ),
			'dashicons-clipboard',
			30
		);
		add_submenu_page( 'tradequote-ai', 'Dashboard', 'Dashboard', 'manage_options', 'tradequote-ai', array( $this, 'page_dashboard' ) );
		add_submenu_page( 'tradequote-ai', 'New Quote', 'New Quote', 'manage_options', 'tqa-new-quote', array( $this, 'page_new_quote' ) );
		add_submenu_page( 'tradequote-ai', 'Quotes', 'Quotes', 'manage_options', 'tqa-quotes', array( $this, 'page_quotes' ) );
		add_submenu_page( 'tradequote-ai', 'Invoices', 'Invoices', 'manage_options', 'tqa-invoices', array( $this, 'page_invoices' ) );
		add_submenu_page( 'tradequote-ai', 'Customers', 'Customers', 'manage_options', 'tqa-customers', array( $this, 'page_customers' ) );
		add_submenu_page( 'tradequote-ai', 'Settings', 'Settings', 'manage_options', 'tqa-settings', array( $this, 'page_settings' ) );
	}

	public function enqueue_assets( $hook ) {
		$tqa_pages = array(
			'toplevel_page_tradequote-ai',
			'tradequote-ai_page_tqa-new-quote',
			'tradequote-ai_page_tqa-quotes',
			'tradequote-ai_page_tqa-invoices',
			'tradequote-ai_page_tqa-customers',
			'tradequote-ai_page_tqa-settings',
		);

		if ( ! in_array( $hook, $tqa_pages, true ) ) return;

		wp_enqueue_style( 'tqa-admin', TQA_PLUGIN_URL . 'assets/css/admin.css', array(), TQA_VERSION );

		// Compiled React bundle (built via `npm run build`).
		$bundle = TQA_PLUGIN_DIR . 'admin/js/app/index.js';
		if ( file_exists( $bundle ) ) {
			wp_enqueue_script( 'tqa-app', TQA_PLUGIN_URL . 'admin/js/app/index.js', array( 'wp-element' ), TQA_VERSION, true );
		}

		wp_localize_script( 'tqa-app', 'tqaData', array(
			'restUrl'   => esc_url_raw( rest_url( 'tradequote-ai/v1/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'settings'  => TQA_Settings::get_all(),
			'adminUrl'  => admin_url( 'admin.php' ),
			'pluginUrl' => TQA_PLUGIN_URL,
			'freeQuota' => TQA_FREE_QUOTA,
		) );
	}

	// ---- Page renderers ----

	public function page_dashboard() {
		include TQA_PLUGIN_DIR . 'admin/partials/dashboard.php';
	}

	public function page_new_quote() {
		include TQA_PLUGIN_DIR . 'admin/partials/new-quote.php';
	}

	public function page_quotes() {
		include TQA_PLUGIN_DIR . 'admin/partials/quotes-list.php';
	}

	public function page_invoices() {
		include TQA_PLUGIN_DIR . 'admin/partials/invoices-list.php';
	}

	public function page_customers() {
		include TQA_PLUGIN_DIR . 'admin/partials/customers.php';
	}

	public function page_settings() {
		include TQA_PLUGIN_DIR . 'admin/partials/settings.php';
	}

	// ---- REST API routes ----

	public function register_rest_routes() {
		$ns = 'tradequote-ai/v1';

		// AI quote generation
		register_rest_route( $ns, '/ai/generate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'api_generate_quote' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Quotes CRUD
		register_rest_route( $ns, '/quotes', array(
			array( 'methods' => 'GET',  'callback' => array( $this, 'api_get_quotes' ),   'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'api_create_quote' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
		register_rest_route( $ns, '/quotes/(?P<id>\d+)', array(
			array( 'methods' => 'GET',    'callback' => array( $this, 'api_get_quote' ),    'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'PUT',    'callback' => array( $this, 'api_update_quote' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( $this, 'api_delete_quote' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
		register_rest_route( $ns, '/quotes/(?P<id>\d+)/send', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'api_send_quote' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
		register_rest_route( $ns, '/quotes/(?P<id>\d+)/convert', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'api_convert_to_invoice' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Invoices CRUD
		register_rest_route( $ns, '/invoices', array(
			array( 'methods' => 'GET',  'callback' => array( $this, 'api_get_invoices' ),   'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'api_create_invoice' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
		register_rest_route( $ns, '/invoices/(?P<id>\d+)', array(
			array( 'methods' => 'GET',    'callback' => array( $this, 'api_get_invoice' ),    'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'PUT',    'callback' => array( $this, 'api_update_invoice' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( $this, 'api_delete_invoice' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
		register_rest_route( $ns, '/invoices/(?P<id>\d+)/send', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'api_send_invoice' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
		register_rest_route( $ns, '/invoices/(?P<id>\d+)/mark-paid', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'api_mark_invoice_paid' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Customers
		register_rest_route( $ns, '/customers', array(
			array( 'methods' => 'GET',  'callback' => array( $this, 'api_get_customers' ),   'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'api_create_customer' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
		register_rest_route( $ns, '/customers/(?P<id>\d+)', array(
			array( 'methods' => 'GET',    'callback' => array( $this, 'api_get_customer' ),    'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'PUT',    'callback' => array( $this, 'api_update_customer' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( $this, 'api_delete_customer' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );

		// Dashboard stats
		register_rest_route( $ns, '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'api_get_stats' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Settings
		register_rest_route( $ns, '/settings', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'api_get_settings' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'api_save_settings' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	// ---- REST handlers ----

	public function api_generate_quote( WP_REST_Request $req ) {
		$desc = sanitize_textarea_field( $req->get_param( 'job_description' ) ?? '' );
		if ( empty( $desc ) ) {
			return new WP_Error( 'missing_description', 'Job description is required.', array( 'status' => 400 ) );
		}
		$result = TQA_AI::generate_quote( $desc );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 422 );
		}
		return rest_ensure_response( $result );
	}

	public function api_get_quotes( WP_REST_Request $req ) {
		$args = array(
			'status'      => $req->get_param( 'status' ),
			'customer_id' => $req->get_param( 'customer_id' ),
			'limit'       => (int) ( $req->get_param( 'limit' ) ?: 50 ),
			'offset'      => (int) ( $req->get_param( 'offset' ) ?: 0 ),
		);
		return rest_ensure_response( TQA_Quote::get_all( array_filter( $args ) ) );
	}

	public function api_create_quote( WP_REST_Request $req ) {
		$data        = $req->get_json_params();
		$customer_id = (int) ( $data['customer_id'] ?? 0 );

		// Auto-create customer if inline data provided.
		if ( ! $customer_id && ! empty( $data['customer'] ) ) {
			$customer_id = TQA_Customer::find_or_create( $data['customer'] );
		}

		$data['customer_id'] = $customer_id;
		$id = TQA_Quote::create( $data );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create quote.' ), 500 );
		}
		return rest_ensure_response( array( 'id' => $id, 'quote' => TQA_Quote::get( $id ) ) );
	}

	public function api_get_quote( WP_REST_Request $req ) {
		$quote = TQA_Quote::get( (int) $req['id'] );
		if ( ! $quote ) return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		return rest_ensure_response( $quote );
	}

	public function api_update_quote( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$data = $req->get_json_params();
		TQA_Quote::update( $id, $data );
		return rest_ensure_response( TQA_Quote::get( $id ) );
	}

	public function api_delete_quote( WP_REST_Request $req ) {
		TQA_Quote::delete( (int) $req['id'] );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public function api_send_quote( WP_REST_Request $req ) {
		$data    = $req->get_json_params();
		$result  = TQA_Email::send_quote( (int) $req['id'], $data['subject'] ?? '', $data['message'] ?? '' );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 422 );
		}
		return rest_ensure_response( array( 'sent' => true ) );
	}

	public function api_convert_to_invoice( WP_REST_Request $req ) {
		$data       = $req->get_json_params();
		$invoice_id = TQA_Invoice::create_from_quote( (int) $req['id'], $data['due_date'] ?? '' );
		if ( is_wp_error( $invoice_id ) ) {
			return new WP_REST_Response( array( 'error' => $invoice_id->get_error_message() ), 422 );
		}
		TQA_Quote::update_status( (int) $req['id'], 'accepted' );
		return rest_ensure_response( array( 'invoice_id' => $invoice_id, 'invoice' => TQA_Invoice::get( $invoice_id ) ) );
	}

	public function api_get_invoices( WP_REST_Request $req ) {
		$args = array(
			'status'      => $req->get_param( 'status' ),
			'customer_id' => $req->get_param( 'customer_id' ),
			'limit'       => (int) ( $req->get_param( 'limit' ) ?: 50 ),
			'offset'      => (int) ( $req->get_param( 'offset' ) ?: 0 ),
		);
		return rest_ensure_response( TQA_Invoice::get_all( array_filter( $args ) ) );
	}

	public function api_create_invoice( WP_REST_Request $req ) {
		$data = $req->get_json_params();
		$id   = TQA_Invoice::create( $data );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create invoice.' ), 500 );
		}
		return rest_ensure_response( array( 'id' => $id, 'invoice' => TQA_Invoice::get( $id ) ) );
	}

	public function api_get_invoice( WP_REST_Request $req ) {
		$invoice = TQA_Invoice::get( (int) $req['id'] );
		if ( ! $invoice ) return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		return rest_ensure_response( $invoice );
	}

	public function api_update_invoice( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$data = $req->get_json_params();
		TQA_Invoice::update( $id, $data );
		return rest_ensure_response( TQA_Invoice::get( $id ) );
	}

	public function api_delete_invoice( WP_REST_Request $req ) {
		TQA_Invoice::delete( (int) $req['id'] );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public function api_send_invoice( WP_REST_Request $req ) {
		$data   = $req->get_json_params();
		$result = TQA_Email::send_invoice( (int) $req['id'], $data['subject'] ?? '', $data['message'] ?? '' );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 422 );
		}
		return rest_ensure_response( array( 'sent' => true ) );
	}

	public function api_mark_invoice_paid( WP_REST_Request $req ) {
		TQA_Invoice::mark_paid( (int) $req['id'] );
		return rest_ensure_response( array( 'paid' => true ) );
	}

	public function api_get_customers( WP_REST_Request $req ) {
		$args = array(
			'search' => $req->get_param( 'search' ),
			'limit'  => (int) ( $req->get_param( 'limit' ) ?: 100 ),
			'offset' => (int) ( $req->get_param( 'offset' ) ?: 0 ),
		);
		return rest_ensure_response( TQA_Customer::get_all( array_filter( $args ) ) );
	}

	public function api_create_customer( WP_REST_Request $req ) {
		$data = $req->get_json_params();
		$id   = TQA_Customer::create( $data );
		return rest_ensure_response( array( 'id' => $id, 'customer' => TQA_Customer::get( $id ) ) );
	}

	public function api_get_customer( WP_REST_Request $req ) {
		$c = TQA_Customer::get( (int) $req['id'] );
		if ( ! $c ) return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		return rest_ensure_response( $c );
	}

	public function api_update_customer( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$data = $req->get_json_params();
		TQA_Customer::update( $id, $data );
		return rest_ensure_response( TQA_Customer::get( $id ) );
	}

	public function api_delete_customer( WP_REST_Request $req ) {
		TQA_Customer::delete( (int) $req['id'] );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public function api_get_stats( WP_REST_Request $req ) {
		return rest_ensure_response( array(
			'quotes_this_month'     => TQA_Quote::count_this_month(),
			'acceptance_rate'       => TQA_Quote::acceptance_rate(),
			'outstanding_invoices'  => TQA_Invoice::outstanding_total(),
			'free_quota_remaining'  => max( 0, TQA_FREE_QUOTA - TQA_Quote::count_this_month() ),
		) );
	}

	public function api_get_settings( WP_REST_Request $req ) {
		$settings = TQA_Settings::get_all();
		// Never expose API key to frontend.
		$settings['claude_api_key'] = ! empty( $settings['claude_api_key'] ) ? '••••••••' : '';
		return rest_ensure_response( $settings );
	}

	public function api_save_settings( WP_REST_Request $req ) {
		$data = $req->get_json_params();
		// Don't overwrite the real API key if the masked value was sent back.
		if ( isset( $data['claude_api_key'] ) && strpos( $data['claude_api_key'], '•' ) !== false ) {
			unset( $data['claude_api_key'] );
		}
		TQA_Settings::save( $data );
		return rest_ensure_response( array( 'saved' => true ) );
	}

	// ---- Form-based handlers ----

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'tqa_save_settings' ) ) {
			wp_die( 'Unauthorised' );
		}
		TQA_Settings::save( $_POST );
		wp_redirect( admin_url( 'admin.php?page=tqa-settings&saved=1' ) );
		exit;
	}

	public function handle_download_quote_pdf() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'tqa_download_pdf' ) ) {
			wp_die( 'Unauthorised' );
		}
		$id = (int) ( $_GET['id'] ?? 0 );
		TQA_PDF::stream_quote( $id );
	}

	public function handle_download_invoice_pdf() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'tqa_download_pdf' ) ) {
			wp_die( 'Unauthorised' );
		}
		$id = (int) ( $_GET['id'] ?? 0 );
		TQA_PDF::stream_invoice( $id );
	}
}
