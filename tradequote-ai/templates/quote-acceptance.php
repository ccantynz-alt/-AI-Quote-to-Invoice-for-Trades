<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$responded  = in_array( $quote->status, array( 'accepted', 'declined' ), true );
$is_expired = $quote->status === 'expired' || ( $quote->valid_until && strtotime( $quote->valid_until ) < time() );
$biz        = $settings['business_name'] ?? get_bloginfo( 'name' );
$sym        = $settings['currency_symbol'] ?? '$';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $biz ); ?> — Quote <?php echo esc_html( $quote->quote_number ); ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; color: #1f2937; margin: 0; padding: 20px; }
  .wrap { max-width: 720px; margin: 0 auto; }
  .card { background: #fff; border-radius: 12px; padding: 28px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
  .biz-name { font-size: 22px; font-weight: 700; color: #1a56db; }
  .doc-title { font-size: 26px; font-weight: 700; color: #374151; text-align: right; }
  .doc-meta  { font-size: 13px; color: #6b7280; text-align: right; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 16px 0; }
  th { background: #f9fafb; padding: 8px 12px; text-align: left; font-size: 12px; color: #6b7280; text-transform: uppercase; }
  td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; }
  .total-row { font-weight: 700; font-size: 16px; }
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 12px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: opacity .15s; min-width: 140px; }
  .btn:hover { opacity: .85; }
  .btn-accept  { background: #0e9f6e; color: #fff; }
  .btn-decline { background: #fff; color: #374151; border: 2px solid #d1d5db; }
  .btn-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px; }
  .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 14px; text-transform: uppercase; }
  .status-accepted { background: #d1fae5; color: #065f46; }
  .status-declined { background: #fee2e2; color: #991b1b; }
  .status-expired  { background: #f3f4f6; color: #6b7280; }
  .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
  .footer { font-size: 12px; color: #9ca3af; text-align: center; margin-top: 24px; }
  @media (max-width: 600px) {
    .header { flex-direction: column; gap: 10px; }
    .doc-title, .doc-meta { text-align: left; }
    .btn-actions { flex-direction: column; }
    .btn { width: 100%; }
  }
</style>
</head>
<body>
<div class="wrap">

  <?php if ( $responded ) : ?>
    <div class="alert alert-success">
      <?php if ( $quote->status === 'accepted' ) : ?>
        ✅ <strong>Quote accepted!</strong> We'll be in touch soon to arrange the work.
      <?php else : ?>
        ❌ <strong>Quote declined.</strong> Thank you for letting us know.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="header">
      <div>
        <?php if ( ! empty( $settings['business_logo'] ) ) : ?>
          <img src="<?php echo esc_url( $settings['business_logo'] ); ?>" alt="Logo" style="max-height:56px;margin-bottom:8px;">
        <?php endif; ?>
        <div class="biz-name"><?php echo esc_html( $biz ); ?></div>
        <?php if ( ! empty( $settings['tax_number'] ) ) : ?>
          <div style="font-size:12px;color:#6b7280;">Tax No: <?php echo esc_html( $settings['tax_number'] ); ?></div>
        <?php endif; ?>
      </div>
      <div>
        <div class="doc-title">QUOTE</div>
        <div class="doc-meta">
          <?php echo esc_html( $quote->quote_number ); ?><br>
          Dated: <?php echo esc_html( date( 'd M Y', strtotime( $quote->created_at ) ) ); ?><br>
          <?php if ( $quote->valid_until ) : ?>
            Valid until: <?php echo esc_html( date( 'd M Y', strtotime( $quote->valid_until ) ) ); ?>
          <?php endif; ?>
        </div>
        <?php if ( $responded || $is_expired ) : ?>
          <div style="margin-top:8px;">
            <span class="status-badge status-<?php echo esc_attr( $quote->status ); ?>">
              <?php echo esc_html( ucfirst( $quote->status ) ); ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ( $customer ) : ?>
      <div style="font-size:14px;margin-bottom:16px;">
        <strong><?php echo esc_html( $customer->name ); ?></strong>
        <?php if ( $customer->email ) : ?><br><?php echo esc_html( $customer->email ); ?><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ( $quote->job_description ) : ?>
      <div style="background:#f9fafb;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:14px;color:#374151;">
        <?php echo nl2br( esc_html( $quote->job_description ) ); ?>
      </div>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th style="width:42%">Description</th>
          <th style="width:10%;text-align:center">Qty</th>
          <th style="width:10%;text-align:center">Unit</th>
          <th style="width:19%;text-align:right">Unit Price</th>
          <th style="width:19%;text-align:right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( (array) $quote->line_items as $item ) : ?>
          <tr>
            <td><?php echo esc_html( $item['description'] ); ?></td>
            <td style="text-align:center"><?php echo esc_html( $item['qty'] ); ?></td>
            <td style="text-align:center"><?php echo esc_html( $item['unit'] ); ?></td>
            <td style="text-align:right"><?php echo esc_html( $sym ); ?><?php echo number_format( (float) $item['unit_price'], 2 ); ?></td>
            <td style="text-align:right"><?php echo esc_html( $sym ); ?><?php echo number_format( (float) $item['total'], 2 ); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="4" style="text-align:right;color:#6b7280;font-size:13px;">Subtotal</td><td style="text-align:right"><?php echo esc_html( $sym ); ?><?php echo number_format( (float) $quote->subtotal, 2 ); ?></td></tr>
        <?php if ( (float) $quote->tax_amount > 0 ) : ?>
          <tr><td colspan="4" style="text-align:right;color:#6b7280;font-size:13px;">Tax (<?php echo esc_html( $quote->tax_rate ); ?>%)</td><td style="text-align:right"><?php echo esc_html( $sym ); ?><?php echo number_format( (float) $quote->tax_amount, 2 ); ?></td></tr>
        <?php endif; ?>
        <tr class="total-row"><td colspan="4" style="text-align:right">Total</td><td style="text-align:right;font-size:18px;"><?php echo esc_html( $sym ); ?><?php echo number_format( (float) $quote->total, 2 ); ?></td></tr>
      </tfoot>
    </table>

    <?php if ( ! empty( $settings['payment_terms'] ) ) : ?>
      <div style="font-size:13px;color:#6b7280;margin-top:12px;"><?php echo esc_html( $settings['payment_terms'] ); ?></div>
    <?php endif; ?>

    <?php if ( ! $responded && ! $is_expired ) : ?>
      <form method="post">
        <?php wp_nonce_field( 'tqa_accept_' . $quote->acceptance_token ); ?>
        <div class="btn-actions">
          <button type="submit" name="tqa_action" value="accept" class="btn btn-accept">
            ✓ Accept This Quote
          </button>
          <button type="submit" name="tqa_action" value="decline" class="btn btn-decline">
            ✕ Decline
          </button>
        </div>
      </form>
    <?php elseif ( $is_expired && ! $responded ) : ?>
      <div style="margin-top:16px;color:#9ca3af;font-style:italic;">This quote has expired. Please contact us for an updated quote.</div>
    <?php endif; ?>
  </div>

  <div class="footer">
    Powered by <strong>TradeQuote AI</strong> — Professional quoting for trades businesses.
  </div>

</div>
</body>
</html>
