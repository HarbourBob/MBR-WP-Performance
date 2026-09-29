<?php
/**
 * RUM tab — self-hosted Core Web Vitals field data.
 *
 * Settings plus a read-only field-data panel (scorecard, per-template breakdown,
 * worst URLs). All data is local; nothing leaves the server.
 *
 * @package MBRPE
 * @since   1.21.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rum      = isset( $options['rum'] ) && is_array( $options['rum'] ) ? $options['rum'] : array();
$enabled  = ! empty( $rum['enabled'] );
$sample   = isset( $rum['sample_rate'] ) ? (int) $rum['sample_rate'] : 100;
$excl_in  = isset( $rum['exclude_logged_in'] ) ? (bool) $rum['exclude_logged_in'] : true;
$raw_days = isset( $rum['raw_retention_days'] ) ? (int) $rum['raw_retention_days'] : 3;
$agg_days = isset( $rum['agg_retention_days'] ) ? (int) $rum['agg_retention_days'] : 60;
$metrics  = isset( $rum['metrics'] ) && is_array( $rum['metrics'] ) ? $rum['metrics'] : array( 'LCP', 'CLS', 'INP' );

$thresholds = MBRPE_RUM::thresholds();

/**
 * Format a metric value for display.
 *
 * @param string $metric
 * @param float  $value
 * @return string
 */
$fmt = function ( $metric, $value ) {
    if ( 'CLS' === $metric ) {
        return number_format( (float) $value, 3 );
    }
    if ( 'LCP' === $metric ) {
        return number_format( (float) $value / 1000, 2 ) . 's';
    }
    return (int) round( $value ) . 'ms'; // INP.
};

/**
 * Rating class from a value against thresholds.
 *
 * @param string $metric
 * @param float  $value
 * @return string 'good' | 'ni' | 'poor'
 */
$rate = function ( $metric, $value ) use ( $thresholds ) {
    if ( ! isset( $thresholds[ $metric ] ) ) {
        return 'ni';
    }
    if ( $value <= $thresholds[ $metric ]['good'] ) {
        return 'good';
    }
    if ( $value > $thresholds[ $metric ]['poor'] ) {
        return 'poor';
    }
    return 'ni';
};

$device_label = array( 0 => __( 'Desktop', 'mbr-performance' ), 1 => __( 'Tablet', 'mbr-performance' ), 2 => __( 'Mobile', 'mbr-performance' ) );

// Roll any raw samples that have arrived since the last aggregation so the
// panel reflects traffic from moments ago rather than waiting for the nightly
// cron. Throttled internally to one automatic run per minute.
if ( $enabled ) {
    MBRPE_RUM::maybe_aggregate();
}

// Below this sample count a p75 is shown but flagged as provisional.
$min_samples = MBRPE_RUM::MIN_SAMPLES;

$scorecard = $enabled ? MBRPE_RUM::scorecard() : array();
$counts    = MBRPE_RUM::row_counts();
$pending   = $enabled ? MBRPE_RUM::has_pending_raw() : false;
?>
<div class="mbr-performance-tab-content">

    <?php if ( isset( $_GET['rum_clear'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'RUM data cleared.', 'mbr-performance' ); ?></p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['rum_agg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Aggregation complete. Field data below is up to date.', 'mbr-performance' ); ?></p></div>
    <?php endif; ?>

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Real User Monitoring', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Collects real-user Core Web Vitals (LCP, CLS, INP) from actual visitors and stores them in a local database table. No cookies, no IP addresses, no data ever leaves your server. Unlike the Doctor\'s synthetic scan, this is field data — and it is the only way to see INP, which only exists when a real person interacts with the page.', 'mbr-performance' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable RUM', 'mbr-performance' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mbrpe_options[rum][enabled]" value="1" <?php checked( $enabled ); ?>>
                        <?php esc_html_e( 'Collect Core Web Vitals from real visitors', 'mbr-performance' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Off by default. Loads a small (~12 KB) script for visitors; it is kept out of combine/defer/delay so it measures accurately.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Metrics', 'mbr-performance' ); ?></th>
                <td>
                    <?php foreach ( array( 'LCP', 'CLS', 'INP' ) as $m ) : ?>
                        <label style="margin-right:1.5em;">
                            <input type="checkbox" name="mbrpe_options[rum][metrics][]" value="<?php echo esc_attr( $m ); ?>" <?php checked( in_array( $m, $metrics, true ) ); ?>>
                            <?php echo esc_html( $m ); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Sample rate', 'mbr-performance' ); ?></th>
                <td>
                    <input type="number" name="mbrpe_options[rum][sample_rate]" value="<?php echo esc_attr( $sample ); ?>" min="1" max="100" step="1" class="small-text"> %
                    <p class="description"><?php esc_html_e( 'Percentage of page views that report metrics. Lower this on high-traffic sites; the p75 stays stable well below 100%.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Exclude logged-in users', 'mbr-performance' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mbrpe_options[rum][exclude_logged_in]" value="1" <?php checked( $excl_in ); ?>>
                        <?php esc_html_e( 'Do not measure logged-in sessions (keeps your own admin/editor visits out of the data)', 'mbr-performance' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Retention', 'mbr-performance' ); ?></th>
                <td>
                    <label><?php esc_html_e( 'Raw samples', 'mbr-performance' ); ?>
                        <input type="number" name="mbrpe_options[rum][raw_retention_days]" value="<?php echo esc_attr( $raw_days ); ?>" min="1" max="30" step="1" class="small-text">
                        <?php esc_html_e( 'days', 'mbr-performance' ); ?>
                    </label>
                    &nbsp;&nbsp;
                    <label><?php esc_html_e( 'Aggregates', 'mbr-performance' ); ?>
                        <input type="number" name="mbrpe_options[rum][agg_retention_days]" value="<?php echo esc_attr( $agg_days ); ?>" min="7" max="365" step="1" class="small-text">
                        <?php esc_html_e( 'days', 'mbr-performance' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'A nightly job rolls raw samples into daily per-template and per-URL aggregates, then deletes raw beyond the raw window. Aggregates are trimmed beyond the aggregate window.', 'mbr-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <?php if ( ! $enabled ) : ?>
        <div class="mbr-performance-section">
            <p><?php esc_html_e( 'Enable RUM above and save to begin collecting. Field data will appear here once real visitors have loaded pages.', 'mbr-performance' ); ?></p>
        </div>
    <?php else : ?>

        <div class="mbr-performance-section">
            <h2><?php esc_html_e( 'Core Web Vitals (field, p75)', 'mbr-performance' ); ?></h2>
            <?php if ( empty( $scorecard ) ) : ?>
                <p>
                    <?php
                    if ( $counts['raw'] > 0 ) {
                        /* translators: %s: number of raw samples */
                        echo esc_html( sprintf( __( '%s raw sample(s) recorded but not yet aggregated. Use "Run aggregation now" below to process them immediately.', 'mbr-performance' ), number_format_i18n( $counts['raw'] ) ) );
                    } else {
                        esc_html_e( 'No samples yet. Visit a few pages on the front end as a logged-out visitor (an incognito window works), then reload this tab.', 'mbr-performance' );
                    }
                    ?>
                </p>
            <?php else : ?>
                <div class="mbr-rum-scorecard" style="display:flex;gap:1em;flex-wrap:wrap;">
                    <?php foreach ( array( 'LCP', 'INP', 'CLS' ) as $m ) :
                        if ( ! isset( $scorecard[ $m ] ) ) {
                            continue;
                        }
                        $card    = $scorecard[ $m ];
                        $total   = max( 1, (int) $card['good'] + (int) $card['ni'] + (int) $card['poor'] );
                        $g_pct   = round( 100 * $card['good'] / $total );
                        $n_pct   = round( 100 * $card['ni'] / $total );
                        $p_pct   = max( 0, 100 - $g_pct - $n_pct );
                        $verdict = $rate( $m, $card['p75'] );
                        $colour  = ( 'good' === $verdict ) ? '#0a0' : ( ( 'poor' === $verdict ) ? '#c00' : '#c80' );
                        $enough  = (int) $card['samples'] >= $min_samples;
                    ?>
                        <div style="flex:1;min-width:180px;border:1px solid #dcdcde;border-radius:6px;padding:12px;">
                            <div style="font-weight:600;"><?php echo esc_html( $m ); ?></div>
                            <div style="font-size:1.8em;line-height:1.2;color:<?php echo esc_attr( $enough ? $colour : '#666' ); ?>;">
                                <?php echo esc_html( $fmt( $m, $card['p75'] ) ); ?>
                            </div>
                            <div style="height:8px;border-radius:4px;overflow:hidden;background:#eee;display:flex;margin:6px 0;">
                                <span style="width:<?php echo esc_attr( $g_pct ); ?>%;background:#0a0;"></span>
                                <span style="width:<?php echo esc_attr( $n_pct ); ?>%;background:#c80;"></span>
                                <span style="width:<?php echo esc_attr( $p_pct ); ?>%;background:#c00;"></span>
                            </div>
                            <div class="description">
                                <?php
                                /* translators: %d: number of samples */
                                echo esc_html( sprintf( __( '%s samples', 'mbr-performance' ), number_format_i18n( $card['samples'] ) ) );
                                if ( ! $enough ) {
                                    /* translators: %d: minimum sample count */
                                    echo ' · ' . esc_html( sprintf( __( 'provisional — under %d', 'mbr-performance' ), $min_samples ) );
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description" style="margin-top:8px;">
                    <?php esc_html_e( 'Good / needs-improvement / poor distribution shown as the bar. p75 is the 28-day weighted average of daily p75s.', 'mbr-performance' ); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $scorecard ) ) : ?>
        <div class="mbr-performance-section">
            <h2><?php esc_html_e( 'By template', 'mbr-performance' ); ?></h2>
            <p class="description">
                <?php
                /* translators: %d: minimum sample count */
                echo esc_html( sprintf( __( 'Where the problem actually is. Worst templates first. Values marked * are provisional (under %d samples).', 'mbr-performance' ), $min_samples ) );
                ?>
            </p>
            <?php foreach ( array( 'LCP', 'INP', 'CLS' ) as $m ) :
                if ( ! isset( $scorecard[ $m ] ) ) {
                    continue;
                }
                $rows = MBRPE_RUM::by_template( $m );
                if ( empty( $rows ) ) {
                    continue;
                }
            ?>
                <h3><?php echo esc_html( $m ); ?></h3>
                <table class="wp-list-table widefat striped" style="max-width:640px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Template', 'mbr-performance' ); ?></th>
                            <th><?php esc_html_e( 'Device', 'mbr-performance' ); ?></th>
                            <th><?php esc_html_e( 'p75', 'mbr-performance' ); ?></th>
                            <th><?php esc_html_e( 'Samples', 'mbr-performance' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $rows as $r ) :
                        $verdict = $rate( $m, (float) $r['p75'] );
                        $colour  = ( 'good' === $verdict ) ? '#0a0' : ( ( 'poor' === $verdict ) ? '#c00' : '#c80' );
                        $enough  = (int) $r['samples'] >= $min_samples;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $r['template'] ); ?></td>
                            <td><?php echo esc_html( isset( $device_label[ (int) $r['device'] ] ) ? $device_label[ (int) $r['device'] ] : '—' ); ?></td>
                            <td style="color:<?php echo esc_attr( $enough ? $colour : '#666' ); ?>;"><?php echo esc_html( $fmt( $m, (float) $r['p75'] ) ); ?><?php echo $enough ? '' : ' *'; ?></td>
                            <td><?php echo esc_html( number_format_i18n( (int) $r['samples'] ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>

        <div class="mbr-performance-section">
            <h2><?php esc_html_e( 'Worst offenders', 'mbr-performance' ); ?></h2>
            <p class="description"><?php esc_html_e( 'URLs with the most poor-rated samples, and the element or handler most often responsible.', 'mbr-performance' ); ?></p>
            <?php foreach ( array( 'LCP', 'INP', 'CLS' ) as $m ) :
                $rows = MBRPE_RUM::worst_urls( $m, 10 );
                if ( empty( $rows ) ) {
                    continue;
                }
            ?>
                <h3><?php echo esc_html( $m ); ?></h3>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'URL', 'mbr-performance' ); ?></th>
                            <th><?php esc_html_e( 'p75', 'mbr-performance' ); ?></th>
                            <th><?php esc_html_e( 'Poor', 'mbr-performance' ); ?></th>
                            <th><?php esc_html_e( 'Culprit', 'mbr-performance' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $rows as $r ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $r['url_path'] ); ?></code></td>
                            <td><?php echo esc_html( $fmt( $m, (float) $r['p75'] ) ); ?></td>
                            <td><?php echo esc_html( number_format_i18n( (int) $r['poor'] ) ); ?></td>
                            <td><?php echo '' !== $r['worst_target'] ? '<code>' . esc_html( $r['worst_target'] ) . '</code>' : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="mbr-performance-section">
        <h2><?php esc_html_e( 'Data health', 'mbr-performance' ); ?></h2>
        <p class="description">
            <?php
            /* translators: 1: raw row count, 2: aggregate row count */
            echo esc_html( sprintf( __( 'Raw samples stored: %1$s · Aggregate rows: %2$s', 'mbr-performance' ), number_format_i18n( $counts['raw'] ), number_format_i18n( $counts['agg'] ) ) );
            ?>
        </p>
        <?php
        // Nonced link (not a nested <form> — this tab renders inside the settings form).
        $clear_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=mbrpe_rum_clear' ),
            'mbrpe_rum_clear'
        );
        ?>
        <?php
        $agg_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=mbrpe_rum_aggregate_now' ),
            'mbrpe_rum_aggregate_now'
        );
        ?>
        <a href="<?php echo esc_url( $agg_url ); ?>" class="button button-primary">
            <?php esc_html_e( 'Run aggregation now', 'mbr-performance' ); ?>
        </a>
        &nbsp;
        <a href="<?php echo esc_url( $clear_url ); ?>" class="button button-secondary"
           onclick="return confirm('<?php echo esc_js( __( 'Delete all collected RUM data? This cannot be undone.', 'mbr-performance' ) ); ?>');">
            <?php esc_html_e( 'Clear RUM data', 'mbr-performance' ); ?>
        </a>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e( 'Raw samples are rolled into daily per-template and per-URL aggregates by a nightly job. This tab also aggregates on demand when you open it, so you should not normally need the button — it is here for when you want the numbers refreshed straight away.', 'mbr-performance' ); ?>
        </p>
    </div>

</div>
