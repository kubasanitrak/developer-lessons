<?php
/**
 * WP-CLI commands for analytics backfill.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Analytics_CLI {

    /**
     * Backfill analytics user meta from historical plugin data.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Run even if automatic backfill already completed.
     *
     * [--overwrite]
     * : Recompute values from historical data instead of preserving existing meta.
     *
     * ## EXAMPLES
     *
     *     wp dl analytics backfill
     *     wp dl analytics backfill --force
     *     wp dl analytics backfill --force --overwrite
     *
     * @when after_wp_load
     */
    public function backfill($args, $assoc_args) {
        $result = DL_Analytics::backfill_user_meta(array(
            'force' => isset($assoc_args['force']),
            'overwrite' => isset($assoc_args['overwrite']),
        ));

        if (!empty($result['skipped'])) {
            WP_CLI::warning('Backfill skipped because it already ran. Use --force to run again.');
            return;
        }

        WP_CLI::success(sprintf(
            'Backfill complete. Processed %d users. Updated registration: %d, first login: %d, last login: %d, login count: %d.',
            (int) $result['processed'],
            (int) $result['updated_registration'],
            (int) $result['updated_first_login'],
            (int) $result['updated_last_login'],
            (int) $result['updated_login_count']
        ));
    }
}

WP_CLI::add_command('dl analytics', 'DL_Analytics_CLI');
