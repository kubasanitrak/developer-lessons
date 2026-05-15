<?php
/**
 * Purchase confirmation email template (HTML)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Purchase Confirmation', 'developer-lessons'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
        }
        .header {
            background: #0073aa;
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .order-details {
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .order-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-details th,
        .order-details td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .order-details th {
            background: #eee;
        }
        .total-row {
            font-weight: bold;
            font-size: 18px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #0073aa;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .footer {
            background: #f4f4f4;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo esc_html($site_name); ?></h1>
        </div>
        
        <div class="content">
            <?php
            $email_phase = isset($email_phase) ? $email_phase : 'payment_confirmed';
            if ($email_phase === 'order_placed') :
            ?>
            <h2><?php _e('Order received - awaiting payment', 'developer-lessons'); ?></h2>
            <p><?php printf(__('Hello %s,', 'developer-lessons'), esc_html($user->display_name)); ?></p>
            <p><?php _e('Thank you for your order. Please complete payment to receive access to your lessons. A pro-forma invoice is attached to this email.', 'developer-lessons'); ?></p>
            <?php if (!empty($payment_instructions)) : ?>
                <div><?php echo $payment_instructions; ?></div>
            <?php endif; ?>
            <?php else : ?>
            <h2><?php _e('Thank you for your purchase!', 'developer-lessons'); ?></h2>
            <p><?php printf(__('Hello %s,', 'developer-lessons'), esc_html($user->display_name)); ?></p>
            <p><?php _e('Your payment has been processed successfully. You now have full access to the lessons you purchased. Your pro-forma invoice and tax invoice are attached to this email.', 'developer-lessons'); ?></p>
            <?php endif; ?>
            
            <div class="order-details">
                <h3><?php _e('Order Details', 'developer-lessons'); ?></h3>
                <p><strong><?php _e('Order Number:', 'developer-lessons'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
                <?php
                $order_date = !empty($order->paid_at) ? $order->paid_at : $order->created_at;
                ?>
                <p><strong><?php _e('Date:', 'developer-lessons'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order_date)); ?></p>
                
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Lesson', 'developer-lessons'); ?></th>
                            <th style="text-align:right;"><?php _e('Price', 'developer-lessons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order->items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->lesson_title); ?></td>
                                <td style="text-align:right;"><?php echo DL_Payments::format_price($item->price); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($order->discount > 0): ?>
                            <tr>
                                <td><?php _e('Discount', 'developer-lessons'); ?></td>
                                <td style="text-align:right;">-<?php echo DL_Payments::format_price($order->discount); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td><?php _e('Total', 'developer-lessons'); ?></td>
                            <td style="text-align:right;"><?php echo DL_Payments::format_price($order->total); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if ($email_phase !== 'order_placed') : ?>
            <p style="text-align: center;">
                <a href="<?php echo esc_url($dashboard_url); ?>" class="btn">
                    <?php _e('Access Your Lessons', 'developer-lessons'); ?>
                </a>
            </p>
            <?php elseif (!empty($checkout_url)) : ?>
            <p style="text-align: center;">
                <a href="<?php echo esc_url($checkout_url); ?>" class="btn">
                    <?php _e('Complete Payment', 'developer-lessons'); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. <?php _e('All rights reserved.', 'developer-lessons'); ?></p>
        </div>
    </div>
</body>
</html>
