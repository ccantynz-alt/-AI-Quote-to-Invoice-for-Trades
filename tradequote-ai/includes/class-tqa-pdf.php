<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TQA_PDF {

	/**
	 * Generate a quote PDF and return the file path.
	 * Uses mPDF when available (via composer), falls back to HTML-based inline PDF.
	 */
	public static function generate_quote( int $quote_id ) {
		$quote    = TQA_Quote::get( $quote_id );
		$customer = $quote ? TQA_Customer::get( (int) $quote->customer_id ) : null;
		if ( ! $quote ) {
			return new WP_Error( 'not_found', 'Quote not found' );
		}

		$html = self::render_template( 'pdf-quote', array(
			'quote'    => $quote,
			'customer' => $customer,
			'settings' => TQA_Settings::get_all(),
		) );

		return self::html_to_pdf( $html, 'quote-' . $quote->quote_number );
	}

	public static function generate_invoice( int $invoice_id ) {
		$invoice  = TQA_Invoice::get( $invoice_id );
		$customer = $invoice ? TQA_Customer::get( (int) $invoice->customer_id ) : null;
		if ( ! $invoice ) {
			return new WP_Error( 'not_found', 'Invoice not found' );
		}

		$html = self::render_template( 'pdf-invoice', array(
			'invoice'  => $invoice,
			'customer' => $customer,
			'settings' => TQA_Settings::get_all(),
		) );

		return self::html_to_pdf( $html, 'invoice-' . $invoice->invoice_number );
	}

	private static function html_to_pdf( string $html, string $filename ) {
		$mpdf_path = TQA_PLUGIN_DIR . 'vendor/mpdf/mpdf/src/Mpdf.php';

		if ( file_exists( $mpdf_path ) ) {
			require_once $mpdf_path;
			$mpdf = new \Mpdf\Mpdf( array(
				'mode'        => 'utf-8',
				'format'      => 'A4',
				'margin_top'  => 15,
				'margin_left' => 15,
				'margin_right'=> 15,
			) );
			$mpdf->WriteHTML( $html );

			$upload_dir = wp_upload_dir();
			$dir        = $upload_dir['basedir'] . '/tradequote-ai/';
			wp_mkdir_p( $dir );
			$path = $dir . sanitize_file_name( $filename ) . '.pdf';
			$mpdf->Output( $path, 'F' );
			return $path;
		}

		// mPDF not installed — save the HTML file as a fallback.
		$upload_dir = wp_upload_dir();
		$dir        = $upload_dir['basedir'] . '/tradequote-ai/';
		wp_mkdir_p( $dir );
		$path = $dir . sanitize_file_name( $filename ) . '.html';
		file_put_contents( $path, $html );
		return $path;
	}

	private static function render_template( string $template, array $vars ) {
		$template_path = TQA_PLUGIN_DIR . 'templates/' . $template . '.php';
		if ( ! file_exists( $template_path ) ) {
			return '<p>Template not found: ' . esc_html( $template ) . '</p>';
		}
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	public static function stream_quote( int $quote_id ) {
		$path = self::generate_quote( $quote_id );
		if ( is_wp_error( $path ) ) return $path;
		self::stream_file( $path );
	}

	public static function stream_invoice( int $invoice_id ) {
		$path = self::generate_invoice( $invoice_id );
		if ( is_wp_error( $path ) ) return $path;
		self::stream_file( $path );
	}

	private static function stream_file( string $path ) {
		$ext  = pathinfo( $path, PATHINFO_EXTENSION );
		$mime = $ext === 'pdf' ? 'application/pdf' : 'text/html';
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		exit;
	}
}
