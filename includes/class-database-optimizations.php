<?php
/**
 * Database Optimizations
 *
 * Wires the database tab's auto-cleanup toggles to a cron callback.
 *
 * The interactive scan/delete buttons on the Database tab are served
 * by AJAX handlers in class-admin.php — this class is solely the
 * scheduled-cleanup driver and option enforcer.
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_Database_Optimizations {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_Database_Optimizations
     */
    private static $instance = null;

    /**
     * Options.
     *
     * @var array
     */
    private $options = array();

    /**
     * Get instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->options = mbr_wp_performance()->get_options( 'database' );
        $this->init_optimizations();
    }

    /**
     * Get an option value.
     */
    private function get_option( $key, $default = false ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    /**
     * Initialise hooks.
     */
    private function init_optimizations() {
        // Honour the chosen schedule (daily / weekly / manual).
        add_action( 'init', array( $this, 'reschedule_cleanup_event' ), 20 );

        // The actual cleanup runner.
        add_action( 'mbr_wp_performance_database_cleanup', array( $this, 'run_scheduled_cleanup' ) );
    }

    /**
     * Make the cleanup event match the user's chosen schedule.
     *
     * If "manual" is selected, unschedule the event entirely. If daily/weekly,
     * ensure the event is scheduled with that recurrence.
     */
    public function reschedule_cleanup_event() {
        $schedule = $this->get_option( 'cleanup_schedule', 'weekly' );
        $next     = wp_next_scheduled( 'mbr_wp_performance_database_cleanup' );

        if ( 'manual' === $schedule ) {
            if ( $next ) {
                wp_unschedule_event( $next, 'mbr_wp_performance_database_cleanup' );
            }
            return;
        }

        $desired = ( 'daily' === $schedule ) ? 'daily' : 'weekly';

        // If scheduled but recurrence doesn't match, reschedule.
        if ( $next ) {
            $event = wp_get_scheduled_event( 'mbr_wp_performance_database_cleanup' );
            if ( $event && isset( $event->schedule ) && $event->schedule !== $desired ) {
                wp_unschedule_event( $next, 'mbr_wp_performance_database_cleanup' );
                $next = false;
            }
        }

        if ( ! $next ) {
            // Schedule for 03:00 local time on the next occurrence.
            $first_run = strtotime( 'tomorrow 03:00:00', current_time( 'timestamp' ) );
            wp_schedule_event( $first_run, $desired, 'mbr_wp_performance_database_cleanup' );
        }
    }

    /**
     * Run the scheduled cleanup.
     *
     * Each operation is gated on its own option toggle so a user only pays
     * for what they've enabled.
     */
    public function run_scheduled_cleanup() {
        $log = array(
            'started_at' => current_time( 'mysql' ),
            'actions'    => array(),
        );

        if ( $this->get_option( 'auto_delete_drafts' ) ) {
            $age = max( 1, (int) $this->get_option( 'draft_age', 7 ) );
            $log['actions']['drafts_deleted'] = $this->delete_old_auto_drafts( $age );
        }

        if ( $this->get_option( 'auto_empty_trash' ) ) {
            $age = max( 1, (int) $this->get_option( 'trash_age', 30 ) );
            $log['actions']['trash_emptied'] = $this->empty_old_trash( $age );
        }

        if ( $this->get_option( 'delete_spam_comments' ) ) {
            $age = max( 1, (int) $this->get_option( 'spam_age', 14 ) );
            $log['actions']['spam_deleted'] = $this->delete_old_spam_comments( $age );
        }

        if ( $this->get_option( 'delete_unapproved_comments' ) ) {
            $age = max( 1, (int) $this->get_option( 'unapproved_age', 30 ) );
            $log['actions']['unapproved_deleted'] = $this->delete_old_unapproved_comments( $age );
        }

        if ( $this->get_option( 'auto_delete_transients' ) ) {
            $log['actions']['transients_deleted'] = $this->delete_expired_transients();
        }

        // Keep last N revisions per post.
        $keep = (int) $this->get_option( 'keep_revisions', 0 );
        if ( $keep > 0 ) {
            $log['actions']['revisions_trimmed'] = $this->trim_revisions( $keep );
        }

        $log['completed_at'] = current_time( 'mysql' );
        update_option( 'mbr_wp_performance_db_last_cleanup', $log, false );
    }

    /**
     * Delete auto-drafts older than N days.
     *
     * @param int $age_days
     * @return int Number of posts deleted.
     */
    private function delete_old_auto_drafts( $age_days ) {
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $age_days * DAY_IN_SECONDS ) );
        $ids    = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'auto-draft' AND post_modified_gmt < %s",
                $cutoff
            )
        );
        if ( empty( $ids ) ) {
            return 0;
        }
        $count = 0;
        foreach ( $ids as $id ) {
            if ( wp_delete_post( (int) $id, true ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Permanently delete trashed items older than N days.
     *
     * @param int $age_days
     * @return int
     */
    private function empty_old_trash( $age_days ) {
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $age_days * DAY_IN_SECONDS ) );
        $ids    = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash' AND post_modified_gmt < %s",
                $cutoff
            )
        );
        if ( empty( $ids ) ) {
            return 0;
        }
        $count = 0;
        foreach ( $ids as $id ) {
            if ( wp_delete_post( (int) $id, true ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Delete spam comments older than N days.
     *
     * @param int $age_days
     * @return int
     */
    private function delete_old_spam_comments( $age_days ) {
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $age_days * DAY_IN_SECONDS ) );
        $ids    = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam' AND comment_date_gmt < %s",
                $cutoff
            )
        );
        if ( empty( $ids ) ) {
            return 0;
        }
        $count = 0;
        foreach ( $ids as $id ) {
            if ( wp_delete_comment( (int) $id, true ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Delete unapproved (pending) comments older than N days.
     *
     * @param int $age_days
     * @return int
     */
    private function delete_old_unapproved_comments( $age_days ) {
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $age_days * DAY_IN_SECONDS ) );
        $ids    = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = '0' AND comment_date_gmt < %s",
                $cutoff
            )
        );
        if ( empty( $ids ) ) {
            return 0;
        }
        $count = 0;
        foreach ( $ids as $id ) {
            if ( wp_delete_comment( (int) $id, true ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Delete expired transients.
     *
     * @return int
     */
    private function delete_expired_transients() {
        global $wpdb;
        $now = time();
        // Find expired transient timeouts.
        $expired = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like( '_transient_timeout_' ) . '%',
                $now
            )
        );
        if ( empty( $expired ) ) {
            return 0;
        }
        $count = 0;
        foreach ( $expired as $timeout_key ) {
            $name = preg_replace( '/^_transient_timeout_/', '', $timeout_key );
            if ( delete_transient( $name ) ) {
                $count++;
            }
        }
        // Site transients on multisite.
        if ( is_multisite() ) {
            $site_expired = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d",
                    $wpdb->esc_like( '_site_transient_timeout_' ) . '%',
                    $now
                )
            );
            foreach ( $site_expired as $timeout_key ) {
                $name = preg_replace( '/^_site_transient_timeout_/', '', $timeout_key );
                if ( delete_site_transient( $name ) ) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Keep only the most recent N revisions per post.
     *
     * @param int $keep
     * @return int Revisions deleted.
     */
    private function trim_revisions( $keep ) {
        global $wpdb;
        // Get post IDs that have more than $keep revisions.
        $sql = $wpdb->prepare(
            "SELECT post_parent, COUNT(*) as c
             FROM {$wpdb->posts}
             WHERE post_type = 'revision'
             GROUP BY post_parent
             HAVING c > %d",
            $keep
        );
        $rows = $wpdb->get_results( $sql );
        if ( empty( $rows ) ) {
            return 0;
        }
        $count = 0;
        foreach ( $rows as $row ) {
            $excess = (int) $row->c - $keep;
            if ( $excess <= 0 ) {
                continue;
            }
            $ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = 'revision' AND post_parent = %d
                     ORDER BY post_date_gmt ASC
                     LIMIT %d",
                    $row->post_parent,
                    $excess
                )
            );
            foreach ( $ids as $id ) {
                if ( wp_delete_post_revision( (int) $id ) ) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
