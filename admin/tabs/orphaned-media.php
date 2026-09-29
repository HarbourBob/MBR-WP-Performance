<?php
/**
 * Orphaned Media Settings Tab
 *
 * Renamed from orphaned-images.php in v1.11.0 when scope expanded to cover
 * videos, audio, documents, and archives alongside images. The legacy file
 * name is no longer required — the admin class includes this file for both
 * the new (orphaned-media) and legacy (orphaned-images) tab slugs.
 *
 * Three-section layout:
 *  1. Settings (media types, restore window, exclusions)
 *  2. Scanner & candidate list
 *  3. Staged-for-deletion (restore queue)
 *
 * @package MBRPE
 * @since   1.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options       = get_option( 'mbrpe_options', array() );
$orphan_opts   = isset( $options['orphaned_images'] ) ? $options['orphaned_images'] : array();
$restore_days  = isset( $orphan_opts['restore_days'] )
    ? (int) $orphan_opts['restore_days']
    : MBRPE_Orphaned_Images::DEFAULT_RESTORE_DAYS;
$enabled_types = MBRPE_Orphaned_Images::get_enabled_types();
$excluded_ids  = isset( $orphan_opts['excluded_ids'] ) && is_array( $orphan_opts['excluded_ids'] )
    ? $orphan_opts['excluded_ids']
    : array();

$candidate_stats = MBRPE_Orphaned_Images::get_candidate_stats();
$staged_stats    = MBRPE_Orphaned_Images::get_staged_stats();
$scan_state      = MBRPE_Orphaned_Images::get_scan_state();

// Type checkbox metadata: label + short description for the settings UI.
$type_meta = array(
    'images'    => array( 'label' => __( 'Images',    'mbr-performance' ), 'desc' => 'JPG, PNG, GIF, WebP, SVG, etc.' ),
    'videos'    => array( 'label' => __( 'Videos',    'mbr-performance' ), 'desc' => 'MP4, MOV, WebM, AVI, MKV, etc.' ),
    'audio'     => array( 'label' => __( 'Audio',     'mbr-performance' ), 'desc' => 'MP3, WAV, M4A, OGG, FLAC, etc.' ),
    'documents' => array( 'label' => __( 'Documents', 'mbr-performance' ), 'desc' => 'PDF, DOC, XLS, PPT, ODT, RTF, CSV, TXT' ),
    'archives'  => array( 'label' => __( 'Archives',  'mbr-performance' ), 'desc' => 'ZIP, TAR, GZ, RAR, 7Z' ),
);
?>

<div class="mbr-performance-section">
    <h2><?php esc_html_e( 'Orphaned Media', 'mbr-performance' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Find and remove media attachments that are no longer referenced anywhere on your site. Detection covers post parents, featured images, post content, and postmeta. Builder-specific data stores (Elementor, Bricks, etc.) are not yet directly inspected — review results carefully.', 'mbr-performance' ); ?>
    </p>

    <div class="notice notice-warning inline" style="margin: 12px 0;">
        <p>
            <strong><?php esc_html_e( 'Read this before deleting anything:', 'mbr-performance' ); ?></strong>
            <?php esc_html_e( 'Deletion physically removes the file (and for images, all sized variants and matching .webp siblings). The database record can be restored within the configured window, but the file itself cannot. Test on a staging site first if you have any doubt.', 'mbr-performance' ); ?>
        </p>
    </div>
</div>

<!-- Settings Section -->
<div class="mbr-performance-section">
    <h3><?php esc_html_e( 'Settings', 'mbr-performance' ); ?></h3>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label>
                        <?php esc_html_e( 'Media Types to Scan', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Which media types the scanner should look at. Defaults to images-only to preserve v1.10.0 behaviour for upgrading sites. Tick more boxes to expand the scan.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <fieldset>
                        <?php foreach ( $type_meta as $key => $meta ) : ?>
                            <label style="display: block; margin-bottom: 6px;">
                                <input type="checkbox"
                                       name="mbrpe_options[orphaned_images][enabled_types][]"
                                       value="<?php echo esc_attr( $key ); ?>"
                                       <?php checked( in_array( $key, $enabled_types, true ) ); ?> />
                                <strong><?php echo esc_html( $meta['label'] ); ?></strong>
                                <span style="color: #9ca3af; margin-left: 6px;">— <?php echo esc_html( $meta['desc'] ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description" style="margin-top: 10px;">
                        <?php esc_html_e( 'Re-run the scan after changing these to see the updated candidate list.', 'mbr-performance' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="orphan_restore_days">
                        <?php esc_html_e( 'Restore Window', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'How long to keep deleted attachment records available for restore. Files are removed immediately on deletion; this controls the database record retention.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbrpe_options[orphaned_images][restore_days]" id="orphan_restore_days">
                        <option value="7"  <?php selected( $restore_days, 7 ); ?>><?php esc_html_e( '7 days',  'mbr-performance' ); ?></option>
                        <option value="14" <?php selected( $restore_days, 14 ); ?>><?php esc_html_e( '14 days', 'mbr-performance' ); ?></option>
                        <option value="30" <?php selected( $restore_days, 30 ); ?>><?php esc_html_e( '30 days (recommended)', 'mbr-performance' ); ?></option>
                        <option value="60" <?php selected( $restore_days, 60 ); ?>><?php esc_html_e( '60 days', 'mbr-performance' ); ?></option>
                        <option value="0"  <?php selected( $restore_days, 0 ); ?>><?php esc_html_e( 'Keep forever', 'mbr-performance' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'A daily cron job removes staging records past their restore window.', 'mbr-performance' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="orphan_excluded_ids">
                        <?php esc_html_e( 'Exclusions', 'mbr-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Attachment IDs that should never be flagged as orphan, even if they appear unused. One ID per line, or comma-separated.', 'mbr-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <textarea
                        name="mbrpe_options[orphaned_images][excluded_ids]"
                        id="orphan_excluded_ids"
                        rows="4"
                        cols="40"
                        class="large-text code"
                        placeholder="123, 456, 789"><?php echo esc_textarea( implode( ', ', array_map( 'intval', $excluded_ids ) ) ); ?></textarea>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %d: number of currently-excluded attachments */
                            esc_html( _n( '%d attachment currently excluded.', '%d attachments currently excluded.', count( $excluded_ids ), 'mbr-performance' ) ),
                            count( $excluded_ids )
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
    // Inline Save button (since 1.11.0). The scanner reads from the saved
    // option, not the current form state, so changes to the media-type
    // checkboxes above must be persisted before "Run Scan" sees them. The
    // bottom-of-page Save Changes button still works identically — this one
    // just saves the user a scroll. Respects the same multisite readonly
    // gate as the bottom button.
    $orphan_panel_readonly = is_multisite()
        && class_exists( 'MBRPE_Multisite' )
        && ! MBRPE_Multisite::allow_site_overrides()
        && ! is_super_admin();
    ?>
    <p style="margin-top: 14px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <button type="submit"
                class="button button-primary"
                <?php echo $orphan_panel_readonly ? 'disabled' : ''; ?>>
            <?php esc_html_e( 'Save Settings', 'mbr-performance' ); ?>
        </button>
        <span class="description" style="color: #9ca3af;">
            <?php esc_html_e( 'Save before running a scan — the scanner reads from saved settings, not the current form state.', 'mbr-performance' ); ?>
        </span>
    </p>
</div>

<!-- Scanner Section -->
<div class="mbr-performance-section">
    <h3><?php esc_html_e( 'Scanner', 'mbr-performance' ); ?></h3>

    <p>
        <button type="button" class="button button-primary" id="mbr-orphan-scan">
            <?php esc_html_e( 'Run Scan', 'mbr-performance' ); ?>
        </button>
        <span id="mbr-orphan-scan-status" class="description" style="margin-left: 10px;">
            <?php
            if ( $scan_state['finished_at'] ) {
                printf(
                    /* translators: %s: human-readable time difference, e.g. "5 minutes ago" */
                    esc_html__( 'Last scan: %s', 'mbr-performance' ),
                    esc_html( human_time_diff( $scan_state['finished_at'], time() ) . ' ' . __( 'ago', 'mbr-performance' ) )
                );
            } else {
                esc_html_e( 'No scan run yet.', 'mbr-performance' );
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

    <!-- Confidence summary cards -->
    <div id="mbr-orphan-summary" style="margin: 16px 0;">
        <div class="mbr-orphan-stats" style="display: flex; gap: 24px; flex-wrap: wrap;">
            <div class="mbr-orphan-stat-card" style="background: #2a2d33; padding: 12px 18px; border-radius: 4px; min-width: 180px;">
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'High Confidence', 'mbr-performance' ); ?></div>
                <div style="font-size: 1.6em; font-weight: 600;" id="mbr-orphan-stat-high"><?php echo (int) $candidate_stats['high']['count']; ?></div>
                <div style="font-size: 0.85em; color: #9ca3af;" id="mbr-orphan-stat-high-bytes"><?php echo esc_html( size_format( (int) $candidate_stats['high']['bytes'], 2 ) ); ?></div>
            </div>
            <div class="mbr-orphan-stat-card" style="background: #2a2d33; padding: 12px 18px; border-radius: 4px; min-width: 180px;">
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'Review Required', 'mbr-performance' ); ?></div>
                <div style="font-size: 1.6em; font-weight: 600;" id="mbr-orphan-stat-review"><?php echo (int) $candidate_stats['review']['count']; ?></div>
                <div style="font-size: 0.85em; color: #9ca3af;" id="mbr-orphan-stat-review-bytes"><?php echo esc_html( size_format( (int) $candidate_stats['review']['bytes'], 2 ) ); ?></div>
            </div>
            <div class="mbr-orphan-stat-card" style="background: #2a2d33; padding: 12px 18px; border-radius: 4px; min-width: 180px;">
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'Total Reclaimable', 'mbr-performance' ); ?></div>
                <div style="font-size: 1.6em; font-weight: 600;" id="mbr-orphan-stat-total-bytes"><?php echo esc_html( size_format( (int) $candidate_stats['total_bytes'], 2 ) ); ?></div>
                <div style="font-size: 0.85em; color: #9ca3af;"><?php esc_html_e( 'across all candidates', 'mbr-performance' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Per-type breakdown row (since 1.11.0). Hidden when only images are
         enabled to keep the v1.10.0-equivalent UI clean for those users. -->
    <?php if ( count( $enabled_types ) > 1 || $candidate_stats['by_type']['videos']['count'] || $candidate_stats['by_type']['audio']['count'] || $candidate_stats['by_type']['documents']['count'] || $candidate_stats['by_type']['archives']['count'] ) : ?>
        <div id="mbr-orphan-type-breakdown" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
            <?php
            $type_icons = array(
                'images'    => '🖼️',
                'videos'    => '🎬',
                'audio'     => '🎵',
                'documents' => '📄',
                'archives'  => '📦',
                'other'     => '📁',
            );
            foreach ( $type_meta as $key => $meta ) :
                $bt = $candidate_stats['by_type'][ $key ];
                if ( $bt['count'] === 0 ) {
                    continue;
                }
                ?>
                <div class="mbr-orphan-type-card" data-type="<?php echo esc_attr( $key ); ?>"
                     style="background: #1f2227; padding: 8px 14px; border-radius: 4px; font-size: 0.9em;">
                    <span style="margin-right: 6px;"><?php echo esc_html( $type_icons[ $key ] ); ?></span>
                    <strong><?php echo esc_html( $meta['label'] ); ?>:</strong>
                    <span class="mbr-orphan-type-count"><?php echo (int) $bt['count']; ?></span>
                    <span style="color: #9ca3af;">·</span>
                    <span class="mbr-orphan-type-bytes"><?php echo esc_html( size_format( (int) $bt['bytes'], 2 ) ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Filter / bulk actions toolbar -->
    <div class="mbr-orphan-toolbar" style="display: flex; gap: 10px; align-items: center; margin: 16px 0; flex-wrap: wrap;">
        <label for="mbr-orphan-filter">
            <?php esc_html_e( 'Confidence:', 'mbr-performance' ); ?>
            <select id="mbr-orphan-filter">
                <option value=""><?php esc_html_e( 'All', 'mbr-performance' ); ?></option>
                <option value="high"><?php esc_html_e( 'High only', 'mbr-performance' ); ?></option>
                <option value="review"><?php esc_html_e( 'Review only', 'mbr-performance' ); ?></option>
            </select>
        </label>

        <label for="mbr-orphan-type-filter">
            <?php esc_html_e( 'Type:', 'mbr-performance' ); ?>
            <select id="mbr-orphan-type-filter">
                <option value=""><?php esc_html_e( 'All types', 'mbr-performance' ); ?></option>
                <option value="images"><?php esc_html_e( 'Images', 'mbr-performance' ); ?></option>
                <option value="videos"><?php esc_html_e( 'Videos', 'mbr-performance' ); ?></option>
                <option value="audio"><?php esc_html_e( 'Audio', 'mbr-performance' ); ?></option>
                <option value="documents"><?php esc_html_e( 'Documents', 'mbr-performance' ); ?></option>
                <option value="archives"><?php esc_html_e( 'Archives', 'mbr-performance' ); ?></option>
            </select>
        </label>

        <label for="mbr-orphan-orderby">
            <?php esc_html_e( 'Sort:', 'mbr-performance' ); ?>
            <select id="mbr-orphan-orderby">
                <option value="file_size"><?php esc_html_e( 'File size (largest first)', 'mbr-performance' ); ?></option>
                <option value="attachment_id"><?php esc_html_e( 'Attachment ID', 'mbr-performance' ); ?></option>
                <option value="scanned_at"><?php esc_html_e( 'Most recently scanned', 'mbr-performance' ); ?></option>
            </select>
        </label>

        <span style="flex: 1;"></span>

        <button type="button" class="button" id="mbr-orphan-bulk-delete" disabled>
            <?php esc_html_e( 'Delete Selected', 'mbr-performance' ); ?>
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
                <th class="manage-column" style="width: 80px;"><?php esc_html_e( 'Preview', 'mbr-performance' ); ?></th>
                <th class="manage-column"><?php esc_html_e( 'File', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 90px;"><?php esc_html_e( 'Type', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 90px;"><?php esc_html_e( 'Size', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 130px;"><?php esc_html_e( 'Confidence', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 200px;"><?php esc_html_e( 'Actions', 'mbr-performance' ); ?></th>
            </tr>
        </thead>
        <tbody id="mbr-orphan-tbody">
            <tr>
                <td colspan="7" style="text-align: center; padding: 24px;">
                    <em><?php esc_html_e( 'Run a scan to populate this list.', 'mbr-performance' ); ?></em>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="mbr-orphan-pagination" style="margin-top: 12px; text-align: center;"></div>
</div>

<!-- Staged-for-deletion / Restore Section -->
<div class="mbr-performance-section">
    <h3>
        <?php esc_html_e( 'Recently Deleted', 'mbr-performance' ); ?>
        <span class="mbr-orphan-staged-count" style="font-weight: normal; color: #9ca3af; font-size: 0.85em;">
            (<?php echo (int) $staged_stats['count']; ?>)
        </span>
    </h3>
    <p class="description">
        <?php esc_html_e( 'Deleted attachments awaiting permanent purge. The database record can be restored — the file itself cannot, and will need to be re-uploaded.', 'mbr-performance' ); ?>
    </p>

    <table class="wp-list-table widefat striped" id="mbr-orphan-staged-table">
        <thead>
            <tr>
                <th class="manage-column"><?php esc_html_e( 'File Path', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 90px;"><?php esc_html_e( 'Size', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 160px;"><?php esc_html_e( 'Deleted', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 160px;"><?php esc_html_e( 'Purges', 'mbr-performance' ); ?></th>
                <th class="manage-column" style="width: 140px;"><?php esc_html_e( 'Actions', 'mbr-performance' ); ?></th>
            </tr>
        </thead>
        <tbody id="mbr-orphan-staged-tbody">
            <tr>
                <td colspan="5" style="text-align: center; padding: 18px;">
                    <em><?php esc_html_e( 'Loading…', 'mbr-performance' ); ?></em>
                </td>
            </tr>
        </tbody>
    </table>
</div>
