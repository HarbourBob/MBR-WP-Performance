<?php
/**
 * Orphaned Images Settings Tab
 *
 * Three-section layout:
 *  1. Settings (restore window, exclusions)
 *  2. Scanner & candidate list
 *  3. Staged-for-deletion (restore queue)
 *
 * @package MBR_WP_Performance
 * @since   1.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options       = get_option( 'mbr_wp_performance_options', array() );
$orphan_opts   = isset( $options['orphaned_images'] ) ? $options['orphaned_images'] : array();
$restore_days  = isset( $orphan_opts['restore_days'] )
    ? (int) $orphan_opts['restore_days']
    : MBR_WP_Performance_Orphaned_Images::DEFAULT_RESTORE_DAYS;
$excluded_ids  = isset( $orphan_opts['excluded_ids'] ) && is_array( $orphan_opts['excluded_ids'] )
    ? $orphan_opts['excluded_ids']
    : array();

$candidate_stats = MBR_WP_Performance_Orphaned_Images::get_candidate_stats();
$staged_stats    = MBR_WP_Performance_Orphaned_Images::get_staged_stats();
$scan_state      = MBR_WP_Performance_Orphaned_Images::get_scan_state();
?>

<div class="mbr-wp-performance-section">
    <h2><?php esc_html_e( 'Orphaned Images', 'mbr-wp-performance' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Find and remove image attachments that are no longer referenced anywhere on your site. Detection covers post parents, featured images, post content, and postmeta. Builder-specific data stores (Elementor, Bricks, etc.) are not yet covered — review results carefully.', 'mbr-wp-performance' ); ?>
    </p>

    <div class="notice notice-warning inline" style="margin: 12px 0;">
        <p>
            <strong><?php esc_html_e( 'Read this before deleting anything:', 'mbr-wp-performance' ); ?></strong>
            <?php esc_html_e( 'Deletion physically removes the image file and all its sized variants (including matching .webp siblings). The database record can be restored within the configured window, but the file itself cannot. Test on a staging site first if you have any doubt.', 'mbr-wp-performance' ); ?>
        </p>
    </div>
</div>

<!-- Settings Section -->
<div class="mbr-wp-performance-section">
    <h3><?php esc_html_e( 'Settings', 'mbr-wp-performance' ); ?></h3>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="orphan_restore_days">
                        <?php esc_html_e( 'Restore Window', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'How long to keep deleted attachment records available for restore. Files are removed immediately on deletion; this controls the database record retention.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[orphaned_images][restore_days]" id="orphan_restore_days">
                        <option value="7"  <?php selected( $restore_days, 7 ); ?>><?php esc_html_e( '7 days',  'mbr-wp-performance' ); ?></option>
                        <option value="14" <?php selected( $restore_days, 14 ); ?>><?php esc_html_e( '14 days', 'mbr-wp-performance' ); ?></option>
                        <option value="30" <?php selected( $restore_days, 30 ); ?>><?php esc_html_e( '30 days (recommended)', 'mbr-wp-performance' ); ?></option>
                        <option value="60" <?php selected( $restore_days, 60 ); ?>><?php esc_html_e( '60 days', 'mbr-wp-performance' ); ?></option>
                        <option value="0"  <?php selected( $restore_days, 0 ); ?>><?php esc_html_e( 'Keep forever', 'mbr-wp-performance' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'A daily cron job removes staging records past their restore window.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="orphan_excluded_ids">
                        <?php esc_html_e( 'Exclusions', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Attachment IDs that should never be flagged as orphan, even if they appear unused. One ID per line, or comma-separated.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea
                        name="mbr_wp_performance_options[orphaned_images][excluded_ids]"
                        id="orphan_excluded_ids"
                        rows="4"
                        cols="40"
                        class="large-text code"
                        placeholder="123, 456, 789"><?php echo esc_textarea( implode( ', ', array_map( 'intval', $excluded_ids ) ) ); ?></textarea>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %d: number of currently-excluded attachments */
                            esc_html( _n( '%d attachment currently excluded.', '%d attachments currently excluded.', count( $excluded_ids ), 'mbr-wp-performance' ) ),
                            count( $excluded_ids )
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Scanner Section -->
<div class="mbr-wp-performance-section">
    <h3><?php esc_html_e( 'Scanner', 'mbr-wp-performance' ); ?></h3>

    <p>
        <button type="button" class="button button-primary" id="mbr-orphan-scan">
            <?php esc_html_e( 'Run Scan', 'mbr-wp-performance' ); ?>
        </button>
        <span id="mbr-orphan-scan-status" class="description" style="margin-left: 10px;">
            <?php
            if ( $scan_state['finished_at'] ) {
                printf(
                    /* translators: %s: human-readable time difference, e.g. "5 minutes ago" */
                    esc_html__( 'Last scan: %s', 'mbr-wp-performance' ),
                    esc_html( human_time_diff( $scan_state['finished_at'], time() ) . ' ' . __( 'ago', 'mbr-wp-performance' ) )
                );
            } else {
                esc_html_e( 'No scan run yet.', 'mbr-wp-performance' );
            }
            ?>
        </span>
    </p>

    <div id="mbr-orphan-scan-progress" style="display: none; margin: 12px 0;">
        <div class="mbr-progress-bar" style="background: #2a2d33; height: 22px; border-radius: 4px; overflow: hidden; max-width: 600px;">
            <div id="mbr-orphan-scan-progress-fill" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.2s;"></div>
        </div>
        <p id="mbr-orphan-scan-progress-text" class="description" style="margin-top: 6px;"></p>
    </div>

    <!-- Results summary -->
    <div id="mbr-orphan-summary" style="margin: 16px 0;">
        <div class="mbr-orphan-stats" style="display: flex; gap: 24px; flex-wrap: wrap;">
            <div class="mbr-orphan-stat-card" style="background: #2a2d33; padding: 12px 18px; border-radius: 4px; min-width: 180px;">
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'High Confidence', 'mbr-wp-performance' ); ?></div>
                <div style="font-size: 1.6em; font-weight: 600;" id="mbr-orphan-stat-high"><?php echo (int) $candidate_stats['high']['count']; ?></div>
                <div style="font-size: 0.85em; color: #9ca3af;" id="mbr-orphan-stat-high-bytes"><?php echo esc_html( size_format( (int) $candidate_stats['high']['bytes'], 2 ) ); ?></div>
            </div>
            <div class="mbr-orphan-stat-card" style="background: #2a2d33; padding: 12px 18px; border-radius: 4px; min-width: 180px;">
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'Review Required', 'mbr-wp-performance' ); ?></div>
                <div style="font-size: 1.6em; font-weight: 600;" id="mbr-orphan-stat-review"><?php echo (int) $candidate_stats['review']['count']; ?></div>
                <div style="font-size: 0.85em; color: #9ca3af;" id="mbr-orphan-stat-review-bytes"><?php echo esc_html( size_format( (int) $candidate_stats['review']['bytes'], 2 ) ); ?></div>
            </div>
            <div class="mbr-orphan-stat-card" style="background: #2a2d33; padding: 12px 18px; border-radius: 4px; min-width: 180px;">
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'Total Reclaimable', 'mbr-wp-performance' ); ?></div>
                <div style="font-size: 1.6em; font-weight: 600;" id="mbr-orphan-stat-total-bytes"><?php echo esc_html( size_format( (int) $candidate_stats['total_bytes'], 2 ) ); ?></div>
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'across all candidates', 'mbr-wp-performance' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Filter / bulk actions toolbar -->
    <div class="mbr-orphan-toolbar" style="display: flex; gap: 10px; align-items: center; margin: 16px 0; flex-wrap: wrap;">
        <label for="mbr-orphan-filter">
            <?php esc_html_e( 'Show:', 'mbr-wp-performance' ); ?>
            <select id="mbr-orphan-filter">
                <option value=""><?php esc_html_e( 'All candidates', 'mbr-wp-performance' ); ?></option>
                <option value="high"><?php esc_html_e( 'High confidence only', 'mbr-wp-performance' ); ?></option>
                <option value="review"><?php esc_html_e( 'Review required only', 'mbr-wp-performance' ); ?></option>
            </select>
        </label>

        <label for="mbr-orphan-orderby">
            <?php esc_html_e( 'Sort by:', 'mbr-wp-performance' ); ?>
            <select id="mbr-orphan-orderby">
                <option value="file_size"><?php esc_html_e( 'File size (largest first)', 'mbr-wp-performance' ); ?></option>
                <option value="attachment_id"><?php esc_html_e( 'Attachment ID', 'mbr-wp-performance' ); ?></option>
                <option value="scanned_at"><?php esc_html_e( 'Most recently scanned', 'mbr-wp-performance' ); ?></option>
            </select>
        </label>

        <span style="flex: 1;"></span>

        <button type="button" class="button" id="mbr-orphan-bulk-delete" disabled>
            <?php esc_html_e( 'Delete Selected', 'mbr-wp-performance' ); ?>
            <span id="mbr-orphan-bulk-count">(0)</span>
        </button>
    </div>

    <!-- Candidates table -->
    <table class="wp-list-table widefat striped" id="mbr-orphan-table">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="mbr-orphan-select-all" />
                </td>
                <th class="manage-column" style="width: 80px;"><?php esc_html_e( 'Preview', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'File', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 90px;"><?php esc_html_e( 'Size', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 130px;"><?php esc_html_e( 'Confidence', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 200px;"><?php esc_html_e( 'Actions', 'mbr-wp-performance' ); ?></th>
            </tr>
        </thead>
        <tbody id="mbr-orphan-tbody">
            <tr>
                <td colspan="6" style="text-align: center; padding: 24px;">
                    <em><?php esc_html_e( 'Run a scan to populate this list.', 'mbr-wp-performance' ); ?></em>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="mbr-orphan-pagination" style="margin-top: 12px; text-align: center;"></div>
</div>

<!-- Staged-for-deletion / Restore Section -->
<div class="mbr-wp-performance-section">
    <h3>
        <?php esc_html_e( 'Recently Deleted', 'mbr-wp-performance' ); ?>
        <span class="mbr-orphan-staged-count" style="font-weight: normal; color: #9ca3af; font-size: 0.85em;">
            (<?php echo (int) $staged_stats['count']; ?>)
        </span>
    </h3>
    <p class="description">
        <?php esc_html_e( 'Deleted attachments awaiting permanent purge. The database record can be restored — the image file itself cannot, and will need to be re-uploaded.', 'mbr-wp-performance' ); ?>
    </p>

    <table class="wp-list-table widefat striped" id="mbr-orphan-staged-table">
        <thead>
            <tr>
                <th class="manage-column"><?php esc_html_e( 'File Path', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 90px;"><?php esc_html_e( 'Size', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 160px;"><?php esc_html_e( 'Deleted', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 160px;"><?php esc_html_e( 'Purges', 'mbr-wp-performance' ); ?></th>
                <th class="manage-column" style="width: 140px;"><?php esc_html_e( 'Actions', 'mbr-wp-performance' ); ?></th>
            </tr>
        </thead>
        <tbody id="mbr-orphan-staged-tbody">
            <tr>
                <td colspan="5" style="text-align: center; padding: 18px;">
                    <em><?php esc_html_e( 'Loading…', 'mbr-wp-performance' ); ?></em>
                </td>
            </tr>
        </tbody>
    </table>
</div>
