<?php
/**
 * WooCommerce Tab
 *
 * @package MBR_WP_Performance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$wc_options = isset( $options['woocommerce'] ) ? $options['woocommerce'] : array();

// If WC isn't active, show a notice instead of the options.
if ( ! class_exists( 'WooCommerce' ) ) :
    ?>
    <div class="mbr-wp-performance-tab-content">
        <div class="mbr-wp-performance-notice">
            <p><?php esc_html_e( 'WooCommerce is not active on this site. Activate WooCommerce to configure these optimisations.', 'mbr-wp-performance' ); ?></p>
        </div>
    </div>
    <?php
    return;
endif;

$cart_fragments_mode = isset( $wc_options['cart_fragments_mode'] ) ? $wc_options['cart_fragments_mode'] : 'default';
$action_scheduler_retention = isset( $wc_options['action_scheduler_retention'] ) ? absint( $wc_options['action_scheduler_retention'] ) : 0;
?>

<div class="mbr-wp-performance-tab-content">

    <div class="mbr-wp-performance-notice">
        <p><?php esc_html_e( 'WooCommerce loads scripts and styles on every page by default, creates visitor sessions, and accumulates Action Scheduler history. These options target the most common performance drains. Test on a staging copy before enabling on a live store.', 'mbr-wp-performance' ); ?></p>
    </div>

    <?php
    // Geolocation + page cache advisor — shows a warning when WooCommerce is
    // configured with a geolocation mode that interacts badly with full-page
    // caching. Uses the existing .notice classes so it matches WP admin styling.
    $geo_advisory = MBR_WP_Performance_WooCommerce_Optimizations::get_geolocation_advisory();
    if ( $geo_advisory ) :
        $notice_class = $geo_advisory['severity'] === 'warning' ? 'notice-warning' : 'notice-info';
        ?>
        <div class="notice <?php echo esc_attr( $notice_class ); ?>" style="margin: 12px 0; padding: 10px 12px;">
            <p style="margin: 0.5em 0;">
                <strong><?php esc_html_e( 'Geolocation & page cache:', 'mbr-wp-performance' ); ?></strong>
                <?php echo esc_html( $geo_advisory['message'] ); ?>
            </p>
            <p style="margin: 0.5em 0;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>" class="button button-secondary">
                    <?php esc_html_e( 'Open WooCommerce → Settings → General', 'mbr-wp-performance' ); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Frontend Assets Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Frontend Assets', 'mbr-wp-performance' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cart_fragments_mode">
                        <?php esc_html_e( 'Cart Fragments', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WooCommerce fires an admin-ajax request on every page load to update mini-cart counts. Disabling this is the single biggest performance win on cached sites, but will stop the mini-cart widget from updating live without a page reload.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[woocommerce][cart_fragments_mode]" id="cart_fragments_mode">
                        <option value="default" <?php selected( $cart_fragments_mode, 'default' ); ?>><?php esc_html_e( 'Default (WooCommerce behaviour)', 'mbr-wp-performance' ); ?></option>
                        <option value="non_shop" <?php selected( $cart_fragments_mode, 'non_shop' ); ?>><?php esc_html_e( 'Disable on non-shop pages', 'mbr-wp-performance' ); ?></option>
                        <option value="always" <?php selected( $cart_fragments_mode, 'always' ); ?>><?php esc_html_e( 'Disable site-wide', 'mbr-wp-performance' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Most stores can safely disable site-wide if you are not using a live-updating mini-cart.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_scripts_non_shop">
                        <?php esc_html_e( 'Disable WC Scripts on Non-Shop Pages', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Dequeues WooCommerce, wc-add-to-cart, selectWoo, blockUI and related scripts on pages that are not shop, product, cart, checkout, or account.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_scripts_non_shop]" id="disable_scripts_non_shop" value="1" <?php checked( isset( $wc_options['disable_scripts_non_shop'] ) && $wc_options['disable_scripts_non_shop'] ); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_styles_non_shop">
                        <?php esc_html_e( 'Disable WC Styles on Non-Shop Pages', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Dequeues woocommerce-general, woocommerce-layout, woocommerce-smallscreen and related stylesheets on non-shop pages.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_styles_non_shop]" id="disable_styles_non_shop" value="1" <?php checked( isset( $wc_options['disable_styles_non_shop'] ) && $wc_options['disable_styles_non_shop'] ); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_block_assets_non_shop">
                        <?php esc_html_e( 'Disable WC Block Assets on Non-Shop Pages', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Dequeues wc-blocks-style and related block editor assets where they are not needed. Safe if you are not using the Cart or Checkout blocks on landing pages.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_block_assets_non_shop]" id="disable_block_assets_non_shop" value="1" <?php checked( isset( $wc_options['disable_block_assets_non_shop'] ) && $wc_options['disable_block_assets_non_shop'] ); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_password_strength">
                        <?php esc_html_e( 'Disable Password Strength Meter', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes the zxcvbn password strength library from the frontend. Saves around 50KB on login and account pages.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_password_strength]" id="disable_password_strength" value="1" <?php checked( isset( $wc_options['disable_password_strength'] ) && $wc_options['disable_password_strength'] ); ?>>
                </td>
            </tr>
        </table>
    </div>

    <!-- Admin Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Admin', 'mbr-wp-performance' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="disable_marketplace_suggestions">
                        <?php esc_html_e( 'Disable Marketplace Suggestions', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Stops WooCommerce fetching and displaying extension recommendations in the admin. Removes outbound requests to WooCommerce.com.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_marketplace_suggestions]" id="disable_marketplace_suggestions" value="1" <?php checked( isset( $wc_options['disable_marketplace_suggestions'] ) && $wc_options['disable_marketplace_suggestions'] ); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_dashboard_widgets">
                        <?php esc_html_e( 'Disable Dashboard Widgets', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes the WooCommerce Status, Recent Reviews and setup widgets from the WordPress dashboard. Fewer queries on dashboard load.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_dashboard_widgets]" id="disable_dashboard_widgets" value="1" <?php checked( isset( $wc_options['disable_dashboard_widgets'] ) && $wc_options['disable_dashboard_widgets'] ); ?>>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="disable_admin_scripts_non_wc">
                        <?php esc_html_e( 'Disable WC Admin Scripts on Non-WC Pages', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Prevents the heavy wc-admin React bundles and enhanced-select scripts from loading on non-WooCommerce admin screens. Makes the regular wp-admin faster.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][disable_admin_scripts_non_wc]" id="disable_admin_scripts_non_wc" value="1" <?php checked( isset( $wc_options['disable_admin_scripts_non_wc'] ) && $wc_options['disable_admin_scripts_non_wc'] ); ?>>
                </td>
            </tr>
        </table>
    </div>

    <!-- Cleanup Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Database Cleanup', 'mbr-wp-performance' ); ?></h2>

        <?php
        $as_stats      = MBR_WP_Performance_WooCommerce_Optimizations::get_action_scheduler_stats();
        $session_count = MBR_WP_Performance_WooCommerce_Optimizations::count_expired_sessions();
        $cleanup_log   = MBR_WP_Performance_WooCommerce_Optimizations::get_last_scheduled_cleanup_log();
        $next_run      = wp_next_scheduled( 'mbr_wp_performance_database_cleanup' );
        ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="enable_scheduled_cleanup">
                        <?php esc_html_e( 'Enable Weekly Automated Cleanup', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Runs the three cleanups below automatically on the weekly cron: expired sessions, WooCommerce transients, and Action Scheduler history. All three operations are idempotent and respect the retention period above.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[woocommerce][enable_scheduled_cleanup]" id="enable_scheduled_cleanup" value="1" <?php checked( isset( $wc_options['enable_scheduled_cleanup'] ) && $wc_options['enable_scheduled_cleanup'] ); ?>>
                    <p class="description">
                        <?php
                        if ( $next_run ) {
                            printf(
                                /* translators: %s: next scheduled cleanup time */
                                esc_html__( 'Next scheduled run: %s', 'mbr-wp-performance' ),
                                esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run ) )
                            );
                        } else {
                            esc_html_e( 'Weekly cron event is not currently scheduled. Re-activate the plugin to restore it.', 'mbr-wp-performance' );
                        }
                        ?>
                    </p>
                    <?php if ( $cleanup_log && ! empty( $cleanup_log['timestamp'] ) ) : ?>
                        <p class="description" style="margin-top: 6px;">
                            <strong><?php esc_html_e( 'Last run:', 'mbr-wp-performance' ); ?></strong>
                            <?php
                            printf(
                                /* translators: 1: date/time, 2: sessions, 3: actions */
                                esc_html__( '%1$s — %2$d expired sessions, %3$d Action Scheduler actions, transients cleared.', 'mbr-wp-performance' ),
                                esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $cleanup_log['timestamp'] ) ),
                                (int) $cleanup_log['sessions_deleted'],
                                (int) $cleanup_log['as_actions_deleted']
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="action_scheduler_retention">
                        <?php esc_html_e( 'Action Scheduler Retention', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'WooCommerce keeps completed and failed scheduled actions for 30 days by default, which can balloon the actionscheduler_actions table on busy stores. Reduces via the action_scheduler_retention_period filter.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[woocommerce][action_scheduler_retention]" id="action_scheduler_retention">
                        <option value="0" <?php selected( $action_scheduler_retention, 0 ); ?>><?php esc_html_e( 'Default (30 days)', 'mbr-wp-performance' ); ?></option>
                        <option value="14" <?php selected( $action_scheduler_retention, 14 ); ?>><?php esc_html_e( '14 days', 'mbr-wp-performance' ); ?></option>
                        <option value="7" <?php selected( $action_scheduler_retention, 7 ); ?>><?php esc_html_e( '7 days', 'mbr-wp-performance' ); ?></option>
                        <option value="3" <?php selected( $action_scheduler_retention, 3 ); ?>><?php esc_html_e( '3 days', 'mbr-wp-performance' ); ?></option>
                    </select>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %1$d total, %2$d complete, %3$d failed */
                            esc_html__( 'Current: %1$d total actions (%2$d complete, %3$d failed).', 'mbr-wp-performance' ),
                            (int) $as_stats['total'],
                            (int) $as_stats['complete'],
                            (int) $as_stats['failed']
                        );
                        ?>
                        <button type="button" class="button" id="wc-run-action-scheduler-cleanup" style="margin-left:8px;"><?php esc_html_e( 'Run Cleanup Now', 'mbr-wp-performance' ); ?></button>
                    </p>
                    <p id="wc-action-scheduler-stats" class="description"></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Expired Sessions', 'mbr-wp-performance' ); ?>
                </th>
                <td>
                    <p>
                        <?php
                        printf(
                            /* translators: %d: number of expired sessions */
                            esc_html( _n( '%d expired session in the database.', '%d expired sessions in the database.', $session_count, 'mbr-wp-performance' ) ),
                            (int) $session_count
                        );
                        ?>
                    </p>
                    <p>
                        <button type="button" class="button" id="wc-clear-expired-sessions"><?php esc_html_e( 'Clear Expired Sessions', 'mbr-wp-performance' ); ?></button>
                    </p>
                    <p id="wc-session-stats" class="description"></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e( 'WooCommerce Transients', 'mbr-wp-performance' ); ?>
                </th>
                <td>
                    <p class="description"><?php esc_html_e( 'Clears product, shop order and expired transients using WooCommerce\'s own helpers.', 'mbr-wp-performance' ); ?></p>
                    <p>
                        <button type="button" class="button" id="wc-clear-transients"><?php esc_html_e( 'Clear WC Transients', 'mbr-wp-performance' ); ?></button>
                    </p>
                    <p id="wc-transient-stats" class="description"></p>
                </td>
            </tr>
        </table>
    </div>

</div>
