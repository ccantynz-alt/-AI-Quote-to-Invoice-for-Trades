<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; color: #333; font-size: 13px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
  .business-name { font-size: 22px; font-weight: bold; color: #1a56db; }
  .doc-title { font-size: 28px; font-weight: bold; text-align: right; color: #444; }
  .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
  .meta-box { padding: 12px; background: #f9fafb; border-radius: 6px; }
  .meta-label { font-size: 10px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  table.items th { background: #1a56db; color: #fff; padding: 8px 10px; text-align: left; font-size: 12px; }
  table.items td { padding: 8px 10px; border-bottom: 1px solid #eee; }
  table.items tr:nth-child(even) td { background: #f9fafb; }
  .totals { width: 280px; margin-left: auto; }
  .totals td { padding: 5px 10px; }
  .totals .grand-total td { font-weight: bold; font-size: 15px; border-top: 2px solid #1a56db; }
  .footer { margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #eee; padding-top: 12px; }
</style>
</head>
<body>
<div class="header">
  <div>
    <?php if ( ! empty( $settings['business_logo'] ) ) : ?>
      <img src="<?php echo esc_url( $settings['business_logo'] ); ?>" style="max-height:60px;margin-bottom:8px;" alt="Logo">
    <?php endif; ?>
    <div class="business-name"><?php echo esc_html( $settings['business_name'] ); ?></div>
    <?php if ( ! empty( $settings['tax_number'] ) ) : ?>
      <div style="font-size:11px;color:#666;">Tax No: <?php echo esc_html( $settings['tax_number'] ); ?></div>
    <?php endif; ?>
  </div>
  <div>
    <div class="doc-title">QUOTE</div>
    <div style="text-align:right;margin-top:6px;">
      <div><strong><?php echo esc_html( $quote->quote_number ); ?></strong></div>
      <div>Date: <?php echo esc_html( date( 'd M Y', strtotime( $quote->created_at ) ) ); ?></div>
      <?php if ( $quote->valid_until ) : ?>
        <div>Valid Until: <?php echo esc_html( date( 'd M Y', strtotime( $quote->valid_until ) ) ); ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="meta-grid">
  <div class="meta-box">
    <div class="meta-label">Bill To</div>
    <?php if ( $customer ) : ?>
      <div><strong><?php echo esc_html( $customer->name ); ?></strong></div>
      <?php if ( $customer->email ) : ?><div><?php echo esc_html( $customer->email ); ?></div><?php endif; ?>
      <?php if ( $customer->phone ) : ?><div><?php echo esc_html( $customer->phone ); ?></div><?php endif; ?>
      <?php if ( $customer->address ) : ?><div><?php echo nl2br( esc_html( $customer->address ) ); ?></div><?php endif; ?>
    <?php endif; ?>
  </div>
  <?php if ( $quote->job_description ) : ?>
  <div class="meta-box">
    <div class="meta-label">Job Description</div>
    <div><?php echo nl2br( esc_html( $quote->job_description ) ); ?></div>
  </div>
  <?php endif; ?>
</div>

<table class="items">
  <thead>
    <tr>
      <th style="width:40%">Description</th>
      <th style="width:10%;text-align:center">Qty</th>
      <th style="width:10%;text-align:center">Unit</th>
      <th style="width:20%;text-align:right">Unit Price</th>
      <th style="width:20%;text-align:right">Total</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ( (array) $quote->line_items as $item ) : ?>
    <tr>
      <td><?php echo esc_html( $item['description'] ); ?></td>
      <td style="text-align:center"><?php echo esc_html( $item['qty'] ); ?></td>
      <td style="text-align:center"><?php echo esc_html( $item['unit'] ); ?></td>
      <td style="text-align:right">$<?php echo number_format( (float) $item['unit_price'], 2 ); ?></td>
      <td style="text-align:right">$<?php echo number_format( (float) $item['total'], 2 ); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<table class="totals">
  <tr><td>Subtotal</td><td style="text-align:right">$<?php echo number_format( (float) $quote->subtotal, 2 ); ?></td></tr>
  <tr><td>Tax (<?php echo esc_html( $quote->tax_rate ); ?>%)</td><td style="text-align:right">$<?php echo number_format( (float) $quote->tax_amount, 2 ); ?></td></tr>
  <tr class="grand-total"><td><strong>Total</strong></td><td style="text-align:right"><strong>$<?php echo number_format( (float) $quote->total, 2 ); ?></strong></td></tr>
</table>

<?php if ( $quote->notes ) : ?>
  <div style="margin-top:20px;"><strong>Notes:</strong><br><?php echo nl2br( esc_html( $quote->notes ) ); ?></div>
<?php endif; ?>

<?php if ( ! empty( $settings['payment_terms'] ) ) : ?>
  <div style="margin-top:16px;font-size:12px;"><strong>Terms:</strong> <?php echo esc_html( $settings['payment_terms'] ); ?></div>
<?php endif; ?>

<div class="footer">
  <?php echo esc_html( $settings['business_name'] ); ?> &mdash; Generated by TradeQuote AI
</div>
</body>
</html>
