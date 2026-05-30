<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;color:#333;max-width:600px;margin:auto;padding:20px;">
  <div style="border-bottom:3px solid #1a56db;padding-bottom:12px;margin-bottom:20px;">
    <div style="font-size:20px;font-weight:bold;color:#1a56db;"><?php echo esc_html( $business ); ?></div>
  </div>

  <p>Dear <?php echo esc_html( $customer->name ); ?>,</p>

  <p>Thank you for your enquiry. Please find your quote <strong><?php echo esc_html( $quote->quote_number ); ?></strong> attached.</p>

  <table style="width:100%;border-collapse:collapse;margin:20px 0;">
    <tr>
      <td style="padding:8px;background:#f3f4f6;font-weight:bold;">Quote Number</td>
      <td style="padding:8px;"><?php echo esc_html( $quote->quote_number ); ?></td>
    </tr>
    <tr>
      <td style="padding:8px;background:#f3f4f6;font-weight:bold;">Total (incl. tax)</td>
      <td style="padding:8px;font-size:18px;font-weight:bold;color:#1a56db;">$<?php echo number_format( (float) $quote->total, 2 ); ?></td>
    </tr>
    <?php if ( $quote->valid_until ) : ?>
    <tr>
      <td style="padding:8px;background:#f3f4f6;font-weight:bold;">Valid Until</td>
      <td style="padding:8px;"><?php echo esc_html( date( 'd M Y', strtotime( $quote->valid_until ) ) ); ?></td>
    </tr>
    <?php endif; ?>
  </table>

  <?php if ( $terms ) : ?>
    <p style="font-size:12px;color:#666;"><?php echo esc_html( $terms ); ?></p>
  <?php endif; ?>

  <p>If you have any questions, please don't hesitate to get in touch.</p>

  <p>Kind regards,<br><strong><?php echo esc_html( $business ); ?></strong></p>
</body>
</html>
