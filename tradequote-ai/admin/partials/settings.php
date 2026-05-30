<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$welcome = ! empty( $_GET['welcome'] );
?>
<div class="wrap tqa-wrap">
	<h1 class="tqa-page-title">TradeQuote AI — Settings</h1>
	<?php if ( $welcome ) : ?>
		<div class="notice notice-success"><p>🎉 <strong>Welcome to TradeQuote AI!</strong> Set up your business details below to get started.</p></div>
	<?php endif; ?>
	<div id="tqa-settings-root">
		<?php /* React settings UI mounts here. If JS bundle not built, falls back to PHP form below. */ ?>
	</div>
	<noscript>
		<?php
		$s = TQA_Settings::get_all();
		$saved = isset( $_GET['saved'] );
		?>
		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'tqa_save_settings' ); ?>
			<input type="hidden" name="action" value="tqa_save_settings">
			<table class="form-table">
				<tr><th><label for="business_name">Business Name</label></th>
				<td><input type="text" id="business_name" name="business_name" value="<?php echo esc_attr( $s['business_name'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="tax_number">ABN / GST / Tax Number</label></th>
				<td><input type="text" id="tax_number" name="tax_number" value="<?php echo esc_attr( $s['tax_number'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="default_tax_rate">Default Tax Rate (%)</label></th>
				<td><input type="number" id="default_tax_rate" name="default_tax_rate" value="<?php echo esc_attr( $s['default_tax_rate'] ); ?>" step="0.01" min="0" max="100" class="small-text">
				<p class="description">e.g. 15 for NZ GST, 10 for AU GST</p></td></tr>
				<tr><th><label for="currency_symbol">Currency Symbol</label></th>
				<td><input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr( $s['currency_symbol'] ); ?>" class="small-text" placeholder="$"></td></tr>
				<tr><th><label for="currency_code">Currency Code</label></th>
				<td><input type="text" id="currency_code" name="currency_code" value="<?php echo esc_attr( $s['currency_code'] ); ?>" class="small-text" placeholder="NZD"></td></tr>
				<tr><th><label for="payment_terms">Default Payment Terms</label></th>
				<td><textarea id="payment_terms" name="payment_terms" class="large-text" rows="3"><?php echo esc_textarea( $s['payment_terms'] ); ?></textarea></td></tr>
				<tr><th><label for="bank_details">Bank Account Details</label></th>
				<td><textarea id="bank_details" name="bank_details" class="large-text" rows="4"><?php echo esc_textarea( $s['bank_details'] ); ?></textarea></td></tr>
				<tr><th><label for="quote_valid_days">Quote Valid For (days)</label></th>
				<td><input type="number" id="quote_valid_days" name="quote_valid_days" value="<?php echo esc_attr( $s['quote_valid_days'] ); ?>" min="1" class="small-text"></td></tr>
				<tr><th><label for="quote_prefix">Quote Number Prefix</label></th>
				<td><input type="text" id="quote_prefix" name="quote_prefix" value="<?php echo esc_attr( $s['quote_prefix'] ); ?>" class="small-text" placeholder="Q-"></td></tr>
				<tr><th><label for="invoice_prefix">Invoice Number Prefix</label></th>
				<td><input type="text" id="invoice_prefix" name="invoice_prefix" value="<?php echo esc_attr( $s['invoice_prefix'] ); ?>" class="small-text" placeholder="INV-"></td></tr>
				<tr><th><label for="email_from_name">Email From Name</label></th>
				<td><input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr( $s['email_from_name'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="email_from_address">Email From Address</label></th>
				<td><input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr( $s['email_from_address'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="claude_api_key">Claude API Key</label></th>
				<td><input type="password" id="claude_api_key" name="claude_api_key" value="" class="regular-text"
					placeholder="<?php echo ! empty( $s['claude_api_key'] ) ? 'Key saved — enter new key to update' : 'sk-ant-...'; ?>">
				<p class="description">Leave blank to keep existing key. Get a key at <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>.</p></td></tr>
			</table>
			<?php submit_button( 'Save Settings' ); ?>
		</form>
	</noscript>
</div>
