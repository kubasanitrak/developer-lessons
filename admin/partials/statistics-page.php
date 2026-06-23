<?php
/**
 * Statistics page template
 */

if (!defined('ABSPATH')) {
    exit;
}

$currency_symbol = get_option('dl_currency_symbol', 'Kč');
$tab = isset($tab) ? $tab : (isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'sales');
if (!in_array($tab, array('sales', 'users', 'lessons', 'funnel', 'review'), true)) {
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
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'funnel', 'range' => $range), $base_url)); ?>"
           class="nav-tab <?php echo $tab === 'funnel' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Funnel', 'developer-lessons'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'review'), $base_url)); ?>"
           class="nav-tab <?php echo $tab === 'review' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Review', 'developer-lessons'); ?>
            <?php
            $review_queue_count = DL_Spam_Scoring::count_review_queue('review');
            if ($review_queue_count > 0) :
                ?>
                <span class="dl-review-tab-count"><?php echo esc_html((string) $review_queue_count); ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <?php if ($tab !== 'review') : ?>
    <div class="dl-stats-filter">
        <form method="get">
            <input type="hidden" name="page" value="dl-statistics">
            <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
            <?php if ($tab === 'users' && !empty($orderby)) : ?>
                <input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>">
                <input type="hidden" name="order" value="<?php echo esc_attr($order); ?>">
            <?php endif; ?>
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
    <?php endif; ?>

    <?php if ($tab === 'users') :
        $orderby = isset($orderby) ? $orderby : 'user_registered';
        $order = isset($order) ? $order : 'desc';
        $users_pagination_base = add_query_arg(array(
            'page' => 'dl-statistics',
            'tab' => 'users',
            'range' => $range,
            'orderby' => $orderby,
            'order' => $order,
            'users_per_page' => isset($per_page) ? (int) $per_page : 20,
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
                        <?php
                        DL_Admin_Statistics::render_users_sortable_header(__('User', 'developer-lessons'), 'user_login', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Registered', 'developer-lessons'), 'user_registered', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Logged In', 'developer-lessons'), 'logged_in', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Days to First Login', 'developer-lessons'), 'days_to_first_login', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Last Login', 'developer-lessons'), 'last_login', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Logins', 'developer-lessons'), 'login_count', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Lesson Views', 'developer-lessons'), 'lesson_views', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Basket Adds', 'developer-lessons'), 'basket_adds', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Checkouts', 'developer-lessons'), 'checkout_starts', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Video Plays', 'developer-lessons'), 'video_plays', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Purchases', 'developer-lessons'), 'purchase_count', $orderby, $order, $range, $per_page);
                        DL_Admin_Statistics::render_users_sortable_header(__('Spam Score', 'developer-lessons'), 'spam_score', $orderby, $order, $range, $per_page);
                        ?>
                        <th scope="col" class="manage-column column-account_flag"><?php esc_html_e('Flag', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_stats)) : ?>
                        <tr><td colspan="13"><?php _e('No registrations in this period.', 'developer-lessons'); ?></td></tr>
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
                                <td><?php echo esc_html((string) intval($stat->basket_adds)); ?></td>
                                <td><?php echo esc_html((string) intval($stat->checkout_starts)); ?></td>
                                <td><?php echo esc_html((string) intval($stat->video_plays)); ?></td>
                                <td><?php echo esc_html((string) intval($stat->purchase_count)); ?></td>
                                <td>
                                    <?php
                                    $spam_score = isset($stat->spam_score) ? (int) $stat->spam_score : 0;
                                    $score_class = 'dl-spam-score-low';
                                    if ($spam_score >= 70) {
                                        $score_class = 'dl-spam-score-high';
                                    } elseif ($spam_score >= 50) {
                                        $score_class = 'dl-spam-score-medium';
                                    }
                                    ?>
                                    <span class="dl-spam-score <?php echo esc_attr($score_class); ?>">
                                        <?php echo esc_html((string) $spam_score); ?>
                                    </span>
                                </td>
                                <td><?php DL_Admin_Statistics::render_account_flag_badge($stat->account_flag ?? 'normal'); ?></td>
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
    <?php elseif ($tab === 'review') :
        $review_filter = isset($review_filter) ? $review_filter : 'review';
        $review_pagination_base = add_query_arg(array(
            'page' => 'dl-statistics',
            'tab' => 'review',
            'review_filter' => $review_filter,
            'orderby' => isset($orderby) ? $orderby : 'score',
            'order' => isset($order) ? $order : 'desc',
            'paged' => '%#%',
        ), admin_url('admin.php'));
        ?>
        <div class="dl-stats-summary">
            <div class="dl-stat-card <?php echo !empty($review_counts['review']) ? 'dl-stat-alert' : ''; ?>">
                <h3><?php _e('Needs Review', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($review_counts['review']); ?></div>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('Marked Spam', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($review_counts['spam']); ?></div>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('High Score (70+)', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($review_counts['high']); ?></div>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('All Suspicious', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($review_counts['all']); ?></div>
            </div>
        </div>

        <div class="dl-stats-section dl-stats-wide">
            <h2><?php _e('Account Review Queue', 'developer-lessons'); ?></h2>
            <p class="description">
                <?php _e('Scores are computed daily from engagement signals. High scores queue accounts for manual review — nothing is blocked automatically.', 'developer-lessons'); ?>
            </p>

            <div class="dl-review-filter-tabs">
                <?php
                $review_filters = array(
                    'review' => __('Needs Review', 'developer-lessons'),
                    'high' => __('High Score', 'developer-lessons'),
                    'spam' => __('Marked Spam', 'developer-lessons'),
                    'all' => __('All Suspicious', 'developer-lessons'),
                );
                foreach ($review_filters as $filter_key => $filter_label) :
                    ?>
                    <a href="<?php echo esc_url(add_query_arg(array('tab' => 'review', 'review_filter' => $filter_key), $base_url)); ?>"
                       class="button <?php echo $review_filter === $filter_key ? 'button-primary' : 'button-secondary'; ?>">
                        <?php echo esc_html($filter_label); ?>
                        (<?php echo esc_html((string) intval($review_counts[$filter_key])); ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (current_user_can('manage_options')) : ?>
                <p class="dl-spam-recalculate-actions">
                    <a href="<?php echo esc_url(DL_Admin_Statistics::get_spam_recalculate_url($review_filter)); ?>"
                       class="button button-secondary">
                        <?php _e('Recalculate all scores now', 'developer-lessons'); ?>
                    </a>
                </p>
                <p class="description">
                    <?php _e('Runs automatically with the daily cron. CLI: wp dl spam recalculate', 'developer-lessons'); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($total_users)) : ?>
                <div class="tablenav top">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php
                            printf(
                                _n('%s account', '%s accounts', (int) $total_users, 'developer-lessons'),
                                number_format_i18n((int) $total_users)
                            );
                            ?>
                        </span>
                        <?php if ($total_pages > 1) : ?>
                            <?php
                            echo paginate_links(array(
                                'base' => $review_pagination_base,
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

            <table class="widefat striped dl-review-queue-table">
                <thead>
                    <tr>
                        <th><?php _e('User', 'developer-lessons'); ?></th>
                        <th><?php _e('Registered', 'developer-lessons'); ?></th>
                        <th><?php _e('Score', 'developer-lessons'); ?></th>
                        <th><?php _e('Flag', 'developer-lessons'); ?></th>
                        <th><?php _e('Signals', 'developer-lessons'); ?></th>
                        <th><?php _e('Engagement', 'developer-lessons'); ?></th>
                        <th><?php _e('Actions', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($review_users)) : ?>
                        <tr><td colspan="7"><?php _e('No accounts in this queue.', 'developer-lessons'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($review_users as $row) :
                            $signals = DL_Spam_Scoring::get_signal_labels($row->spam_signals);
                            $spam_score = isset($row->spam_score) ? (int) $row->spam_score : 0;
                            $score_class = 'dl-spam-score-low';
                            if ($spam_score >= 70) {
                                $score_class = 'dl-spam-score-high';
                            } elseif ($spam_score >= 50) {
                                $score_class = 'dl-spam-score-medium';
                            }
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_user_link($row->ID)); ?>">
                                        <?php echo esc_html($row->user_login); ?>
                                    </a>
                                    <br><small><?php echo esc_html($row->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($row->user_registered))); ?></td>
                                <td><span class="dl-spam-score <?php echo esc_attr($score_class); ?>"><?php echo esc_html((string) $spam_score); ?></span></td>
                                <td><?php DL_Admin_Statistics::render_account_flag_badge($row->account_flag ?: 'normal'); ?></td>
                                <td>
                                    <?php if (empty($signals)) : ?>
                                        <span class="description">&mdash;</span>
                                    <?php else : ?>
                                        <ul class="dl-spam-signals">
                                            <?php foreach ($signals as $signal) : ?>
                                                <li><?php echo esc_html($signal); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $has_logged_in = !empty($row->first_login_at);
                                    printf(
                                        esc_html__('Logged in: %1$s | Lessons: %2$d | Videos: %3$d | Basket: %4$d | Purchases: %5$d', 'developer-lessons'),
                                        $has_logged_in ? __('Yes', 'developer-lessons') : __('No', 'developer-lessons'),
                                        (int) $row->lesson_views,
                                        (int) $row->video_plays,
                                        (int) $row->basket_adds,
                                        (int) $row->purchase_count
                                    );
                                    ?>
                                </td>
                                <td class="dl-review-actions">
                                    <a class="button button-small" href="<?php echo esc_url(DL_Admin_Statistics::get_spam_action_url('mark_normal', $row->ID, $review_filter)); ?>">
                                        <?php _e('Mark Normal', 'developer-lessons'); ?>
                                    </a>
                                    <a class="button button-small" href="<?php echo esc_url(DL_Admin_Statistics::get_spam_action_url('mark_review', $row->ID, $review_filter)); ?>">
                                        <?php _e('Mark Review', 'developer-lessons'); ?>
                                    </a>
                                    <a class="button button-small button-link-delete" href="<?php echo esc_url(DL_Admin_Statistics::get_spam_action_url('mark_spam', $row->ID, $review_filter)); ?>"
                                       onclick="return confirm('<?php echo esc_js(__('Mark this account as spam?', 'developer-lessons')); ?>');">
                                        <?php _e('Mark Spam', 'developer-lessons'); ?>
                                    </a>
                                    <a class="button button-small" href="<?php echo esc_url(DL_Admin_Statistics::get_spam_action_url('recalculate', $row->ID, $review_filter)); ?>">
                                        <?php _e('Recalculate', 'developer-lessons'); ?>
                                    </a>
                                </td>
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
                            'base' => $review_pagination_base,
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
                <?php _e('Views are deduplicated per user and lesson every 30 minutes. Full vs teaser shows purchased access state.', 'developer-lessons'); ?>
            </p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Lesson', 'developer-lessons'); ?></th>
                        <th><?php _e('Views', 'developer-lessons'); ?></th>
                        <th><?php _e('Full', 'developer-lessons'); ?></th>
                        <th><?php _e('Teaser', 'developer-lessons'); ?></th>
                        <th><?php _e('Video Plays', 'developer-lessons'); ?></th>
                        <th><?php _e('Unique Users', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lesson_view_stats)) : ?>
                        <tr><td colspan="6"><?php _e('No lesson views recorded in this period.', 'developer-lessons'); ?></td></tr>
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
                                <td><?php echo intval($stat->full_views); ?></td>
                                <td><?php echo intval($stat->teaser_views); ?></td>
                                <td><?php echo intval($stat->video_plays); ?></td>
                                <td><?php echo intval($stat->unique_users); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($tab === 'funnel') : ?>
        <div class="dl-stats-summary">
            <div class="dl-stat-card">
                <h3><?php _e('Registrations', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['registrations']); ?></div>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('First Logins', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['first_logins']); ?></div>
                <p class="description"><?php printf(esc_html__('%s%% of registrations', 'developer-lessons'), esc_html((string) $funnel_summary['registration_to_login_rate'])); ?></p>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('Lesson Views', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['lesson_views']); ?></div>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('Video Plays', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['video_plays']); ?></div>
                <p class="description"><?php printf(esc_html__('%s%% of lesson views', 'developer-lessons'), esc_html((string) $funnel_summary['lesson_to_video_rate'])); ?></p>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('Basket Adds', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['basket_adds']); ?></div>
                <p class="description"><?php printf(esc_html__('%s%% of lesson views', 'developer-lessons'), esc_html((string) $funnel_summary['lesson_to_basket_rate'])); ?></p>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('Checkout Starts', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['checkout_starts']); ?></div>
            </div>
            <div class="dl-stat-card">
                <h3><?php _e('Completed Orders', 'developer-lessons'); ?></h3>
                <div class="dl-stat-value"><?php echo intval($funnel_summary['checkout_completes']); ?></div>
                <p class="description"><?php printf(esc_html__('%s%% of checkout starts', 'developer-lessons'), esc_html((string) $funnel_summary['checkout_completion_rate'])); ?></p>
            </div>
        </div>

        <div class="dl-stats-section dl-stats-wide">
            <h2><?php _e('Daily Activity', 'developer-lessons'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'developer-lessons'); ?></th>
                        <th><?php _e('Registrations', 'developer-lessons'); ?></th>
                        <th><?php _e('First Logins', 'developer-lessons'); ?></th>
                        <th><?php _e('Lesson Views', 'developer-lessons'); ?></th>
                        <th><?php _e('Video Plays', 'developer-lessons'); ?></th>
                        <th><?php _e('Basket Adds', 'developer-lessons'); ?></th>
                        <th><?php _e('Checkout Starts', 'developer-lessons'); ?></th>
                        <th><?php _e('Completed Orders', 'developer-lessons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_activity)) : ?>
                        <tr><td colspan="8"><?php _e('No activity recorded in this period.', 'developer-lessons'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($daily_activity as $row) : ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($row->date))); ?></td>
                                <td><?php echo intval($row->registrations); ?></td>
                                <td><?php echo intval($row->first_logins); ?></td>
                                <td><?php echo intval($row->lesson_views); ?></td>
                                <td><?php echo intval($row->video_plays); ?></td>
                                <td><?php echo intval($row->basket_adds); ?></td>
                                <td><?php echo intval($row->checkout_starts); ?></td>
                                <td><?php echo intval($row->checkout_completes); ?></td>
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
