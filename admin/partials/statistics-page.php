<?php
/**
 * Statistics page template
 */

if (!defined('ABSPATH')) {
    exit;
}

$currency_symbol = get_option('dl_currency_symbol', 'Kč');
?>
<div class="wrap dl-admin-wrap">
    <h1><?php _e('Statistics', 'developer-lessons'); ?></h1>

    <div class="dl-stats-filter">
        <form method="get">
            <input type="hidden" name="post_type" value="lesson">
            <input type="hidden" name="page" value="dl-statistics">
            <select name="range" onchange="this.form.submit()">
                <option value="7days" <?php selected($range, '7days'); ?>><?php _e('Last 7 Days', 'developer-lessons'); ?></option>
                <option value="30days" <?php selected($range, '30days'); ?>><?php _e('Last 30 Days', 'developer-lessons'); ?></option>
                <option value="90days" <?php selected($range, '90days'); ?>><?php _e('Last 90 Days', 'developer-lessons'); ?></option>
                <option value="year" <?php selected($range, 'year'); ?>><?php _e('Last Year', 'developer-lessons'); ?></option>
                <option value="all" <?php selected($range, 'all'); ?>><?php _e('All Time', 'developer-lessons'); ?></option>
            </select>
        </form>
    </div>


    <!-- Summary Cards -->
    <div class="dl-stats-summary">
        <div class="dl-stat-card">
            <h3><?php _e('Total Sales', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo intval($summary['total_sales']); ?></div>
        </div>
        <div class="dl-stat-card">
            <h3><?php _e('Total Revenue', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo number_format((float)$summary['total_revenue'], 2); ?> <?php echo esc_html($currency_symbol); ?></div>
        </div>
        <div class="dl-stat-card">
            <h3><?php _e('Total Orders', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo intval($summary['total_orders']); ?></div>
        </div>
        <div class="dl-stat-card">
            <h3><?php _e('Avg. Order Value', 'developer-lessons'); ?></h3>
            <div class="dl-stat-value"><?php echo number_format((float)$summary['avg_order_value'], 2); ?> <?php echo esc_html($currency_symbol); ?></div>
        </div>
    </div>

    <div class="dl-stats-grid">
        <!-- Top Lessons -->
        <div class="dl-stats-section">
            <h2><?php _e('Top Selling Lessons', 'developer-lessons'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Lesson', 'developer-lessons'); ?></th>
                        <th><?php _e('Sales', 'developer-lessons'); ?></th>
                        <th><?php _e('Revenue', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lesson_stats)): ?>
                        <tr><td colspan="3"><?php _e('No data available', 'developer-lessons'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($lesson_stats as $stat): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($stat->lesson_id); ?>">
                                        <?php echo esc_html($stat->title); ?>
                                    </a>
                                </td>
                                <td><?php echo intval($stat->sales); ?></td>
                                <td><?php echo number_format((float)$stat->revenue, 2); ?> <?php echo esc_html($currency_symbol); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Sales by Price Range -->
        <div class="dl-stats-section">
            <h2><?php _e('Sales by Price Range', 'developer-lessons'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Price Range', 'developer-lessons'); ?></th>
                        <th><?php _e('Count', 'developer-lessons'); ?></th>
                        <th><?php _e('Total', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($price_stats)): ?>
                        <tr><td colspan="3"><?php _e('No data available', 'developer-lessons'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($price_stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html($stat->price_range); ?> <?php echo esc_html($currency_symbol); ?></td>
                                <td><?php echo intval($stat->count); ?></td>
                                <td><?php echo number_format((float)$stat->total, 2); ?> <?php echo esc_html($currency_symbol); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Sales by Bundle Size -->
        <div class="dl-stats-section">
            <h2><?php _e('Sales by Bundle Size', 'developer-lessons'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Bundle Type', 'developer-lessons'); ?></th>
                        <th><?php _e('Orders', 'developer-lessons'); ?></th>
                        <th><?php _e('Revenue', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bundle_stats)): ?>
                        <tr><td colspan="3"><?php _e('No data available', 'developer-lessons'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($bundle_stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html($stat->bundle_type); ?></td>
                                <td><?php echo intval($stat->orders); ?></td>
                                <td><?php echo number_format((float)$stat->revenue, 2); ?> <?php echo esc_html($currency_symbol); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Daily Sales -->
        <div class="dl-stats-section dl-stats-wide">
            <h2><?php _e('Daily Sales', 'developer-lessons'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'developer-lessons'); ?></th>
                        <th><?php _e('Sales', 'developer-lessons'); ?></th>
                        <th><?php _e('Revenue', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($time_stats)): ?>
                        <tr><td colspan="3"><?php _e('No data available', 'developer-lessons'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($time_stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($stat->date))); ?></td>
                                <td><?php echo intval($stat->sales); ?></td>
                                <td><?php echo number_format((float)$stat->revenue, 2); ?> <?php echo esc_html($currency_symbol); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
