<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_Email {

	public static function send_quote( int $quote_id, string $subject = '', string $message = '' ) {
		$quote    = TQA_Quote::get( $quote_id );
		$customer = $quote ? TQA_Customer::get( (int) $quote->customer_id ) : null;

		if ( ! $quote || ! $customer ) {
			return new WP_Error( 'not_found', 'Quote or customer not found.' );
		}

		$to      = $customer->email;
		$subject = $subject ?: sprintf( 'Quote %s from %s', $quote->quote_number, TQA_Settings::get( 'business_name', get_bloginfo( 'name' ) ) );
		$body    = $message ?: self::default_quote_body( $quote, $customer );

		$pdf_path = TQA_PDF::generate_quote( $quote_id );
		$attachments = is_wp_error( $pdf_path ) ? array() : array( $pdf_path );

		$result = self::send( $to, $subject, $body, $attachments );

		if ( ! is_wp_error( $result ) ) {
			TQA_Quote::update_status( $quote_id, 'sent' );
		}

		return $result;
	}

	public static function send_invoice( int $invoice_id, string $subject = '', string $message = '' ) {
		$invoice  = TQA_Invoice::get( $invoice_id );
		$customer = $invoice ? TQA_Customer::get( (int) $invoice->customer_id ) : null;

		if ( ! $invoice || ! $customer ) {
			return new WP_Error( 'not_found', 'Invoice or customer not found.' );
		}

		$to      = $customer->email;
		$subject = $subject ?: sprintf( 'Invoice %s from %s', $invoice->invoice_number, TQA_Settings::get( 'business_name', get_bloginfo( 'name' ) ) );
		$body    = $message ?: self::default_invoice_body( $invoice, $customer );

		$pdf_path    = TQA_PDF::generate_invoice( $invoice_id );
		$attachments = is_wp_error( $pdf_path ) ? array() : array( $pdf_path );

		$result = self::send( $to, $subject, $body, $attachments );

		if ( ! is_wp_error( $result ) ) {
			TQA_Invoice::update( $invoice_id, array( 'status' => 'sent' ) );
		}

		return $result;
	}

	private static function send( string $to, string $subject, string $body, array $attachments = array() ) {
		$from_name  = TQA_Settings::get( 'email_from_name', get_bloginfo( 'name' ) );
		$from_email = TQA_Settings::get( 'email_from_address', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
			'Reply-To: ' . $from_email,
		);

		$sent = wp_mail( $to, $subject, $body, $headers, $attachments );

		if ( ! $sent ) {
			return new WP_Error( 'send_failed', 'Email could not be sent. Check your WordPress mail settings.' );
		}

		return true;
	}

	private static function default_quote_body( $quote, $customer ) {
		$business = TQA_Settings::get( 'business_name', get_bloginfo( 'name' ) );
		$terms    = TQA_Settings::get( 'payment_terms', '' );
		ob_start();
		include TQA_PLUGIN_DIR . 'templates/email-quote.php';
		return ob_get_clean();
	}

	private static function default_invoice_body( $invoice, $customer ) {
		$business    = TQA_Settings::get( 'business_name', get_bloginfo( 'name' ) );
		$bank        = TQA_Settings::get( 'bank_details', '' );
		$terms       = TQA_Settings::get( 'payment_terms', '' );
		$invoice_num = $invoice->invoice_number;
		$total       = number_format( (float) $invoice->total, 2 );
		$due_date    = $invoice->due_date;

		return '<html><body style="font-family:Arial,sans-serif;color:#333;max-width:600px;margin:auto;">'
			. '<h2>Invoice ' . esc_html( $invoice_num ) . '</h2>'
			. '<p>Dear ' . esc_html( $customer->name ) . ',</p>'
			. '<p>Please find your invoice attached. A total of <strong>$' . esc_html( $total ) . '</strong> is due by <strong>' . esc_html( $due_date ) . '</strong>.</p>'
			. ( $bank ? '<p><strong>Payment details:</strong><br>' . nl2br( esc_html( $bank ) ) . '</p>' : '' )
			. ( $terms ? '<p>' . esc_html( $terms ) . '</p>' : '' )
			. '<p>Thank you for your business.</p>'
			. '<p>' . esc_html( $business ) . '</p>'
			. '</body></html>';
	}
}
