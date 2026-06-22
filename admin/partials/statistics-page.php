<?php
/**
 * Statistics page template
 */

if (!defined('ABSPATH')) {
    exit;
}

$currency_symbol = get_option('dl_currency_symbol', 'Kč');
$tab = isset($tab) ? $tab : (isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'sales');
if (!in_array($tab, array('sales', 'users', 'lessons'), true)) {
    $tab = 'sales';
}

$base_url = admin_url('admin.php?page=dl-statistics');
?>
<div class="wrap dl-admin-wrap">
    <h1><?php _e('Statistics', 'developer-lessons'); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'sales', 'range' => $range), $base_url)); ?>"
           class="nav-tab <?php echo $tab === 'sales' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Sales', 'developer-lessons'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'users', 'range' => $range), $base_url)); ?>"
           class="nav-tab <?php echo $tab === 'users' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Users', 'developer-lessons'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'lessons', 'range' => $range), $base_url)); ?>"
           class="nav-tab <?php echo $tab === 'lessons' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Lesson Views', 'developer-lessons'); ?>
        </a>
    </nav>

    <div class="dl-stats-filter">
        <form method="get">
            <input type="hidden" name="page" value="dl-statistics">
            <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
            <select name="range" onchange="this.form.submit()">
                <option value="7days" <?php selected($range, '7days'); ?>><?php _e('Last 7 Days', 'developer-lessons'); ?></option>
                <option value="30days" <?php selected($range, '30days'); ?>><?php _e('Last 30 Days', 'developer-lessons'); ?></option>
                <option value="90days" <?php selected($range, '90days'); ?>><?php _e('Last 90 Days', 'developer-lessons'); ?></option>
                <option value="year" <?php selected($range, 'year'); ?>><?php _e('Last Year', 'developer-lessons'); ?></option>
                <option value="all" <?php selected($range, 'all'); ?>><?php _e('All Time', 'developer-lessons'); ?></option>
            </select>
            <?php if ($tab === 'users') :
                $per_page = isset($per_page) ? (int) $per_page : 20;
                ?>
                <label for="dl-stats-users-per-page" class="dl-stats-users-per-page">
                    <?php _e('Users per page', 'developer-lessons'); ?>
                    <select id="dl-stats-users-per-page" name="users_per_page" onchange="this.form.submit()">
                        <?php foreach (array(10, 20, 50, 100, 200, 999) as $users_per_page_option) : ?>
                            <option value="<?php echo esc_attr((string) $users_per_page_option); ?>" <?php selected((int) $per_page, $users_per_page_option); ?>>
                                <?php echo esc_html((string) $users_per_page_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($tab === 'users') :
        $users_pagination_base = add_query_arg(array(
            'page' => 'dl-statistics',
            'tab' => 'users',
            'range' => $range,
            'paged' => '%#%',
        ), admin_url('admin.php'));
        ?>
        <div class="dl-stats-section dl-stats-wide">
            <h2><?php _e('Recent Registrations', 'developer-lessons'); ?></h2>
            <p class="description">
                <?php _e('Track who registered, whether they logged in, and how long it took to return.', 'developer-lessons'); ?>
            </p>
            <?php if (current_user_can('manage_options')) : ?>
                <p class="dl-analytics-backfill-actions">
                    <a href="<?php echo esc_url(DL_Admin_Statistics::get_backfill_url($range)); ?>"
                       class="button button-secondary">
                        <?php _e('Backfill missing login meta', 'developer-lessons'); ?>
                    </a>
                    <a href="<?php echo esc_url(DL_Admin_Statistics::get_backfill_url($range, true)); ?>"
                       class="button button-secondary"
                       onclick="return confirm('<?php echo esc_js(__('Recompute login meta from historical orders, purchases, and events? Existing values will be overwritten.', 'developer-lessons')); ?>');">
                        <?php _e('Recompute from historical data', 'developer-lessons'); ?>
                    </a>
                </p>
                <p class="description">
                    <?php _e('Use after deployment to populate older accounts. CLI: wp dl analytics backfill --force', 'developer-lessons'); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($total_users)) : ?>
                <div class="tablenav top">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php
                            printf(
                                _n('%s user', '%s users', (int) $total_users, 'developer-lessons'),
                                number_format_i18n((int) $total_users)
                            );
                            ?>
                        </span>
                        <?php if ($total_pages > 1) : ?>
                            <?php
                            echo paginate_links(array(
                                'base' => $users_pagination_base,
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page,
                            ));
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'developer-lessons'); ?></th>
                        <th><?php _e('Registered', 'developer-lessons'); ?></th>
                        <th><?php _e('Logged In', 'developer-lessons'); ?></th>
                        <th><?php _e('Days to First Login', 'developer-lessons'); ?></th>
                        <th><?php _e('Last Login', 'developer-lessons'); ?></th>
                        <th><?php _e('Logins', 'developer-lessons'); ?></th>
                        <th><?php _e('Lesson Views', 'developer-lessons'); ?></th>
                        <th><?php _e('Purchases', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_stats)) : ?>
                        <tr><td colspan="8"><?php _e('No registrations in this period.', 'developer-lessons'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($user_stats as $stat) :
                            $has_logged_in = !empty($stat->first_login_at);
                            $days_to_first = null;
                            if ($has_logged_in) {
                                $days_to_first = (int) floor((strtotime($stat->first_login_at) - strtotime($stat->user_registered)) / DAY_IN_SECONDS);
                            }
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_user_link($stat->ID)); ?>">
                                        <?php echo esc_html($stat->user_login); ?>
                                    </a>
                                    <br><small><?php echo esc_html($stat->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stat->user_registered))); ?></td>
                                <td><?php echo $has_logged_in ? esc_html__('Yes', 'developer-lessons') : esc_html__('No', 'developer-lessons'); ?></td>
                                <td>
                                    <?php
                                    if ($has_logged_in) {
                                        echo esc_html((string) $days_to_first);
                                    } else {
                                        echo '&mdash;';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($stat->last_login_at)) {
                                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stat->last_login_at)));
                                    } else {
                                        echo '&mdash;';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html((string) intval($stat->login_count)); ?></td>
                                <td><?php echo esc_html((string) intval($stat->lesson_views)); ?></td>
                                <td><?php echo esc_html((string) intval($stat->purchase_count)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($total_users) && $total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => $users_pagination_base,
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($tab === 'lessons') : ?>
        <div class="dl-stats-section dl-stats-wide">
            <h2><?php _e('Lesson Page Views', 'developer-lessons'); ?></h2>
            <p class="description">
                <?php _e('Unique views are deduplicated per user and lesson every 30 minutes.', 'developer-lessons'); ?>
            </p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Lesson', 'developer-lessons'); ?></th>
                        <th><?php _e('Views', 'developer-lessons'); ?></th>
                        <th><?php _e('Unique Users', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lesson_view_stats)) : ?>
                        <tr><td colspan="3"><?php _e('No lesson views recorded in this period.', 'developer-lessons'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($lesson_view_stats as $stat) : ?>
                            <tr>
                                <td>
                                    <?php if ($stat->lesson_id) : ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($stat->lesson_id)); ?>">
                                            <?php echo esc_html($stat->title ?: __('(deleted lesson)', 'developer-lessons')); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php _e('Unknown lesson', 'developer-lessons'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo intval($stat->views); ?></td>
                                <td><?php echo intval($stat->unique_users); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>

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
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), mysql2date('U', $stat->date))); ?></td>
                                <td><?php echo intval($stat->sales); ?></td>
                                <td><?php echo number_format((float)$stat->revenue, 2); ?> <?php echo esc_html($currency_symbol); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
