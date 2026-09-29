<?php
/**
 * WP-Cron Viewer
 *
 * Surfaces all registered cron events with their next-run, recurrence and
 * registered hook callbacks. Lets admins delete dead events (typically left
 * by deactivated plugins) and provides guidance for disabling WP-Cron in
 * favour of a real system cron job.
 *
 * @package MBRPE
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Cron_Viewer {

    /**
     * Single instance.
     *
     * @var MBRPE_Cron_Viewer
     */
    private static $instance = null;

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
        // No runtime hooks — admin-side only via class-admin AJAX handlers.
    }

    /**
     * Get all scheduled cron events, flattened to one row per (hook, signature).
     *
     * @return array Each row: timestamp, hook, schedule, args, has_callback
     */
    public static function get_events() {
        $cron = _get_cron_array();
        if ( ! is_array( $cron ) ) {
            return array();
        }

        $events = array();
        foreach ( $cron as $timestamp => $hooks ) {
            if ( ! is_array( $hooks ) ) {
                continue;
            }
            foreach ( $hooks as $hook => $by_sig ) {
                if ( ! is_array( $by_sig ) ) {
                    continue;
                }
                foreach ( $by_sig as $sig => $data ) {
                    $events[] = array(
                        'timestamp'    => (int) $timestamp,
                        'next_run_h'   => human_time_diff( time(), (int) $timestamp )
                            . ( $timestamp >= time() ? ' from now' : ' ago' ),
                        'next_run_iso' => gmdate( 'Y-m-d H:i:s', (int) $timestamp ),
                        'hook'         => (string) $hook,
                        'signature'    => (string) $sig,
                        'schedule'     => isset( $data['schedule'] ) ? (string) $data['schedule'] : '',
                        'interval'     => isset( $data['interval'] ) ? (int) $data['interval'] : 0,
                        'args'         => isset( $data['args'] ) ? $data['args'] : array(),
                        'has_callback' => self::hook_has_callbacks( $hook ),
                    );
                }
            }
        }

        // Sort by next-run.
        usort( $events, function ( $a, $b ) {
            return $a['timestamp'] <=> $b['timestamp'];
        } );

        return $events;
    }

    /**
     * Does this hook have any registered PHP callbacks in the current request?
     *
     * If not, the event is almost certainly orphaned by a deactivated plugin.
     *
     * @param string $hook
     * @return bool
     */
    public static function hook_has_callbacks( $hook ) {
        global $wp_filter;
        return isset( $wp_filter[ $hook ] ) && ! empty( $wp_filter[ $hook ]->callbacks );
    }

    /**
     * Find orphaned events — hooks with no registered callback.
     *
     * @return array
     */
    public static function get_orphaned() {
        $all = self::get_events();
        return array_values( array_filter( $all, function ( $e ) {
            return empty( $e['has_callback'] );
        } ) );
    }

    /**
     * Unschedule a single event.
     *
     * @param string $hook
     * @param int    $timestamp
     * @param array  $args
     * @return bool
     */
    public static function unschedule( $hook, $timestamp, $args = array() ) {
        $hook = (string) $hook;
        if ( '' === $hook ) {
            return false;
        }
        return (bool) wp_unschedule_event( (int) $timestamp, $hook, (array) $args );
    }

    /**
     * Clear every scheduled occurrence of a hook.
     *
     * @param string $hook
     * @return int|bool Number cleared, or false.
     */
    public static function clear_all_for_hook( $hook ) {
        $hook = (string) $hook;
        if ( '' === $hook ) {
            return false;
        }
        return wp_clear_scheduled_hook( $hook );
    }

    /**
     * Detect whether WP-Cron is disabled.
     *
     * @return bool True if disabled (real cron expected).
     */
    public static function wp_cron_disabled() {
        return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
    }

    /**
     * Real-cron snippet for the user to install.
     *
     * @return string
     */
    public static function real_cron_snippet() {
        $url = site_url( 'wp-cron.php?doing_wp_cron' );
        return sprintf(
            "*/5 * * * * wget -q -O - %s >/dev/null 2>&1",
            $url
        );
    }
}
