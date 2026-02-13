<?php
/**
 * Database Tab
 *
 * @package MBR_WP_Performance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$db_options = isset( $options['database'] ) ? $options['database'] : array();
?>

<div class="mbr-wp-performance-tab-content">
    
    <div class="mbr-wp-performance-notice">
        <p><strong><?php esc_html_e( '⚠️ IMPORTANT:', 'mbr-wp-performance' ); ?></strong> <?php esc_html_e( 'Always backup your database before using cleanup features. These operations are permanent and cannot be undone.', 'mbr-wp-performance' ); ?></p>
    </div>
    
    <!-- Post Revisions Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Post Revisions', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="keep_revisions">
                        <?php esc_html_e( 'Keep Last X Revisions per Post', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Keeps only the most recent revisions and deletes older ones', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[database][keep_revisions]" id="keep_revisions" value="<?php echo isset( $db_options['keep_revisions'] ) ? absint( $db_options['keep_revisions'] ) : 5; ?>" min="0" max="100" class="small-text">
                    <p>
                        <button type="button" class="button" id="clean-revisions"><?php esc_html_e( 'Delete Excess Revisions Now', 'mbr-wp-performance' ); ?></button>
                    </p>
                    <p id="revision-stats" class="description"></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Auto-Cleanup Settings Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Auto-Cleanup Settings', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="auto_delete_drafts">
                        <?php esc_html_e( 'Auto-Delete Old Auto-Drafts', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes auto-saved drafts older than specified days', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[database][auto_delete_drafts]" id="auto_delete_drafts" value="1" <?php checked( isset( $db_options['auto_delete_drafts'] ) && $db_options['auto_delete_drafts'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="draft_age">
                        <?php esc_html_e( 'Auto-Draft Age (days)', 'mbr-wp-performance' ); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[database][draft_age]" id="draft_age" value="<?php echo isset( $db_options['draft_age'] ) ? absint( $db_options['draft_age'] ) : 7; ?>" min="1" max="365" class="small-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_empty_trash">
                        <?php esc_html_e( 'Auto-Empty Trash', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Permanently deletes trashed items after specified days', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[database][auto_empty_trash]" id="auto_empty_trash" value="1" <?php checked( isset( $db_options['auto_empty_trash'] ) && $db_options['auto_empty_trash'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="trash_age">
                        <?php esc_html_e( 'Trash Retention (days)', 'mbr-wp-performance' ); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[database][trash_age]" id="trash_age" value="<?php echo isset( $db_options['trash_age'] ) ? absint( $db_options['trash_age'] ) : 30; ?>" min="1" max="365" class="small-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="delete_spam_comments">
                        <?php esc_html_e( 'Delete Spam Comments', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Permanently removes spam comments', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[database][delete_spam_comments]" id="delete_spam_comments" value="1" <?php checked( isset( $db_options['delete_spam_comments'] ) && $db_options['delete_spam_comments'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="spam_age">
                        <?php esc_html_e( 'Spam Retention (days)', 'mbr-wp-performance' ); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[database][spam_age]" id="spam_age" value="<?php echo isset( $db_options['spam_age'] ) ? absint( $db_options['spam_age'] ) : 14; ?>" min="1" max="365" class="small-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="delete_unapproved_comments">
                        <?php esc_html_e( 'Delete Unapproved Comments', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes pending comments after specified days', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[database][delete_unapproved_comments]" id="delete_unapproved_comments" value="1" <?php checked( isset( $db_options['delete_unapproved_comments'] ) && $db_options['delete_unapproved_comments'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="unapproved_age">
                        <?php esc_html_e( 'Unapproved Retention (days)', 'mbr-wp-performance' ); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="mbr_wp_performance_options[database][unapproved_age]" id="unapproved_age" value="<?php echo isset( $db_options['unapproved_age'] ) ? absint( $db_options['unapproved_age'] ) : 30; ?>" min="1" max="365" class="small-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="cleanup_schedule">
                        <?php esc_html_e( 'Cleanup Schedule', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'How often to run automatic cleanup', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[database][cleanup_schedule]" id="cleanup_schedule">
                        <option value="daily" <?php selected( isset( $db_options['cleanup_schedule'] ) ? $db_options['cleanup_schedule'] : 'weekly', 'daily' ); ?>><?php esc_html_e( 'Daily (3:00 AM)', 'mbr-wp-performance' ); ?></option>
                        <option value="weekly" <?php selected( isset( $db_options['cleanup_schedule'] ) ? $db_options['cleanup_schedule'] : 'weekly', 'weekly' ); ?>><?php esc_html_e( 'Weekly (Sunday 3:00 AM)', 'mbr-wp-performance' ); ?></option>
                        <option value="manual" <?php selected( isset( $db_options['cleanup_schedule'] ) ? $db_options['cleanup_schedule'] : 'weekly', 'manual' ); ?>><?php esc_html_e( 'Manual Only', 'mbr-wp-performance' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Orphaned Data Cleanup Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Orphaned Data Cleanup', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Clean Orphaned Post Meta', 'mbr-wp-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes post meta with no associated post', 'mbr-wp-performance' ); ?>">?</span>
                </th>
                <td>
                    <button type="button" class="button" id="scan-post-meta"><?php esc_html_e( 'Scan for Orphaned Post Meta', 'mbr-wp-performance' ); ?></button>
                    <button type="button" class="button button-secondary" id="delete-post-meta" disabled><?php esc_html_e( 'Delete Orphaned Post Meta', 'mbr-wp-performance' ); ?></button>
                    <p id="post-meta-stats" class="description"></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Clean Orphaned Comment Meta', 'mbr-wp-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes comment meta with no associated comment', 'mbr-wp-performance' ); ?>">?</span>
                </th>
                <td>
                    <button type="button" class="button" id="scan-comment-meta"><?php esc_html_e( 'Scan for Orphaned Comment Meta', 'mbr-wp-performance' ); ?></button>
                    <button type="button" class="button button-secondary" id="delete-comment-meta" disabled><?php esc_html_e( 'Delete Orphaned Comment Meta', 'mbr-wp-performance' ); ?></button>
                    <p id="comment-meta-stats" class="description"></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Clean Orphaned Relationships', 'mbr-wp-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes term relationships with no post', 'mbr-wp-performance' ); ?>">?</span>
                </th>
                <td>
                    <button type="button" class="button" id="scan-relationships"><?php esc_html_e( 'Scan for Orphaned Relationships', 'mbr-wp-performance' ); ?></button>
                    <button type="button" class="button button-secondary" id="delete-relationships" disabled><?php esc_html_e( 'Delete Orphaned Relationships', 'mbr-wp-performance' ); ?></button>
                    <p id="relationship-stats" class="description"></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Clean Orphaned Term Meta', 'mbr-wp-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes term meta with no associated term', 'mbr-wp-performance' ); ?>">?</span>
                </th>
                <td>
                    <button type="button" class="button" id="scan-term-meta"><?php esc_html_e( 'Scan for Orphaned Term Meta', 'mbr-wp-performance' ); ?></button>
                    <button type="button" class="button button-secondary" id="delete-term-meta" disabled><?php esc_html_e( 'Delete Orphaned Term Meta', 'mbr-wp-performance' ); ?></button>
                    <p id="term-meta-stats" class="description"></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Transients Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Transients', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="auto_delete_transients">
                        <?php esc_html_e( 'Auto-Delete Expired Transients', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Removes expired cached data automatically', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[database][auto_delete_transients]" id="auto_delete_transients" value="1" <?php checked( isset( $db_options['auto_delete_transients'] ) && $db_options['auto_delete_transients'] ); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Transient Statistics', 'mbr-wp-performance' ); ?>
                </th>
                <td>
                    <button type="button" class="button" id="get-transient-stats"><?php esc_html_e( 'Get Statistics', 'mbr-wp-performance' ); ?></button>
                    <p id="transient-stats" class="description"></p>
                    <p>
                        <button type="button" class="button" id="delete-expired-transients"><?php esc_html_e( 'Delete Expired Transients', 'mbr-wp-performance' ); ?></button>
                        <button type="button" class="button button-secondary" id="delete-all-transients"><?php esc_html_e( 'Delete ALL Transients', 'mbr-wp-performance' ); ?></button>
                    </p>
                    <p class="description" style="color: #d63638;"><?php esc_html_e( 'WARNING: Deleting all transients may cause temporary performance drop while cache rebuilds.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Database Optimization Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Database Optimization', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Optimize Database Tables', 'mbr-wp-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Defragments tables and reclaims space', 'mbr-wp-performance' ); ?>">?</span>
                </th>
                <td>
                    <button type="button" class="button button-primary" id="optimize-tables"><?php esc_html_e( 'Optimize All Tables Now', 'mbr-wp-performance' ); ?></button>
                    <p id="optimization-status" class="description"></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="schedule_optimization">
                        <?php esc_html_e( 'Schedule Automatic Optimization', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Run database optimization on a schedule', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="mbr_wp_performance_options[database][schedule_optimization]" id="schedule_optimization" value="1" <?php checked( isset( $db_options['schedule_optimization'] ) && $db_options['schedule_optimization'] ); ?>>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="optimization_day">
                        <?php esc_html_e( 'Optimization Day', 'mbr-wp-performance' ); ?>
                    </label>
                </th>
                <td>
                    <select name="mbr_wp_performance_options[database][optimization_day]" id="optimization_day">
                        <?php
                        $days = array(
                            'sunday' => __( 'Sunday', 'mbr-wp-performance' ),
                            'monday' => __( 'Monday', 'mbr-wp-performance' ),
                            'tuesday' => __( 'Tuesday', 'mbr-wp-performance' ),
                            'wednesday' => __( 'Wednesday', 'mbr-wp-performance' ),
                            'thursday' => __( 'Thursday', 'mbr-wp-performance' ),
                            'friday' => __( 'Friday', 'mbr-wp-performance' ),
                            'saturday' => __( 'Saturday', 'mbr-wp-performance' ),
                        );
                        foreach ( $days as $value => $label ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $value ),
                                selected( isset( $db_options['optimization_day'] ) ? $db_options['optimization_day'] : 'sunday', $value, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            
            <tr class="mbr-wp-performance-child-row">
                <th scope="row">
                    <label for="optimization_time">
                        <?php esc_html_e( 'Optimization Time', 'mbr-wp-performance' ); ?>
                    </label>
                </th>
                <td>
                    <input type="time" name="mbr_wp_performance_options[database][optimization_time]" id="optimization_time" value="<?php echo isset( $db_options['optimization_time'] ) ? esc_attr( $db_options['optimization_time'] ) : '03:00'; ?>">
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Advanced Database Operations Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Advanced Database Operations', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="convert_to_innodb">
                        <?php esc_html_e( 'Convert Tables to InnoDB', 'mbr-wp-performance' ); ?>
                        <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Converts MyISAM tables to InnoDB for better performance. WARNING: Test on staging first.', 'mbr-wp-performance' ); ?>">?</span>
                    </label>
                </th>
                <td>
                    <button type="button" class="button" id="convert-innodb"><?php esc_html_e( 'Convert to InnoDB', 'mbr-wp-performance' ); ?></button>
                    <p id="innodb-status" class="description"></p>
                    <p class="description" style="color: #d63638;"><?php esc_html_e( '⚠️ WARNING: Test on staging first. InnoDB uses more disk space but offers better reliability and performance.', 'mbr-wp-performance' ); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e( 'Repair Database Tables', 'mbr-wp-performance' ); ?>
                    <span class="mbr-tooltip" data-tip="<?php esc_attr_e( 'Fixes corrupted tables', 'mbr-wp-performance' ); ?>">?</span>
                </th>
                <td>
                    <button type="button" class="button" id="repair-tables"><?php esc_html_e( 'Check & Repair Tables', 'mbr-wp-performance' ); ?></button>
                    <p id="repair-status" class="description"></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Database Information Section -->
    <div class="mbr-wp-performance-section">
        <h2><?php esc_html_e( 'Database Information', 'mbr-wp-performance' ); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Database Info', 'mbr-wp-performance' ); ?></th>
                <td>
                    <button type="button" class="button" id="get-db-info"><?php esc_html_e( 'Get Database Information', 'mbr-wp-performance' ); ?></button>
                    <div id="db-info" class="description"></div>
                </td>
            </tr>
        </table>
    </div>
    
</div>
