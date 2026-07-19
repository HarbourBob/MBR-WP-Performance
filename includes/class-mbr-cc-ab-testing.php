<?php
/**
 * A/B Testing for Banner Position
 *
 * Randomly assigns visitors to one of three banner position variants:
 *   A — bottom bar  (control — mirrors current default)
 *   B — popup       (centred modal with overlay)
 *   C — box-left    (floating box, bottom-left)
 *
 * Assignment is stored in a session cookie so the same visitor always sees
 * the same variant. Impressions and accept-all conversions are tracked per
 * variant in wp_options. The winner (highest accept-all rate) can be promoted
 * to the live setting with a single button click in wp-admin.
 *
 * @package MBR_Cookie_Consent
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_CC_AB_Testing {

    private static $instance = null;

    /** Cookie name used to persist variant assignment across page views. */
    const ASSIGNMENT_COOKIE = 'mbr_cc_ab_variant';

    /** Option key prefix for stats storage. */
    const STATS_OPTION = 'mbr_cc_ab_stats';

    /** Available variants: key => position value used by the banner. */
    const VARIANTS = array(
        'a' => 'bottom',
        'b' => 'popup',
        'c' => 'box-left',
    );

    /** Human-readable variant labels for the admin UI. */
    const VARIANT_LABELS = array(
        'a' => 'A — Bottom bar',
        'b' => 'B — Popup',
        'c' => 'C — Box left',
    );

    private function __construct() {
        if ( ! get_option( 'mbr_cc_ab_testing_enabled', false ) ) {
            return;
        }

        // Assign variant and override banner position on frontend.
        add_action( 'init', array( $this, 'assign_variant' ) );
        add_filter( 'option_mbr_cc_banner_position', array( $this, 'override_position' ) );

        // Track impressions via AJAX (fired by JS when banner is shown).
        add_action( 'wp_ajax_mbr_cc_ab_impression',        array( $this, 'ajax_track_impression' ) );
        add_action( 'wp_ajax_nopriv_mbr_cc_ab_impression', array( $this, 'ajax_track_impression' ) );

        // Track accept-all conversions (fires on mbr_cc_consent_saved).
        add_action( 'wp_ajax_mbr_cc_ab_conversion',        array( $this, 'ajax_track_conversion' ) );
        add_action( 'wp_ajax_nopriv_mbr_cc_ab_conversion', array( $this, 'ajax_track_conversion' ) );

        // Enqueue the tiny tracker script on the frontend.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracker' ) );

        // Admin AJAX: promote winner.
        add_action( 'wp_ajax_mbr_cc_ab_promote_winner', array( $this, 'ajax_promote_winner' ) );

        // Admin AJAX: reset stats.
        add_action( 'wp_ajax_mbr_cc_ab_reset_stats', array( $this, 'ajax_reset_stats' ) );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Variant assignment ────────────────────────────────────────────────

    /**
     * Assign the visitor to a variant if not already assigned.
     * Stored in a session cookie (no expiry = deleted when browser closes).
     */
    public function assign_variant() {
        if ( is_admin() ) {
            return;
        }

        if ( isset( $_COOKIE[ self::ASSIGNMENT_COOKIE ] ) ) {
            $variant = sanitize_key( $_COOKIE[ self::ASSIGNMENT_COOKIE ] );
            if ( array_key_exists( $variant, self::VARIANTS ) ) {
                return; // Already assigned.
            }
        }

        // Random assignment weighted equally across all variants.
        $keys    = array_keys( self::VARIANTS );
        $variant = $keys[ array_rand( $keys ) ];

        setcookie(
            self::ASSIGNMENT_COOKIE,
            $variant,
            0, // Session cookie.
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // httpOnly.
        );

        $_COOKIE[ self::ASSIGNMENT_COOKIE ] = $variant;
    }

    /**
     * Return the current visitor's assigned variant key, or 'a' as fallback.
     *
     * @return string
     */
    public static function current_variant() {
        if ( isset( $_COOKIE[ self::ASSIGNMENT_COOKIE ] ) ) {
            $v = sanitize_key( $_COOKIE[ self::ASSIGNMENT_COOKIE ] );
            if ( array_key_exists( $v, self::VARIANTS ) ) {
                return $v;
            }
        }
        return 'a';
    }

    /**
     * Override the banner position option for this visitor's variant.
     *
     * @param  string $position  Value from get_option().
     * @return string
     */
    public function override_position( $position ) {
        if ( is_admin() ) {
            return $position;
        }
        $variant = self::current_variant();
        return self::VARIANTS[ $variant ];
    }

    // ── Tracking ──────────────────────────────────────────────────────────

    /**
     * Get stats array, initialised with zeros if missing.
     *
     * @return array  [ variant => [ impressions => int, conversions => int ] ]
     */
    public static function get_stats() {
        $stats = get_option( self::STATS_OPTION, array() );
        foreach ( array_keys( self::VARIANTS ) as $key ) {
            if ( ! isset( $stats[ $key ] ) ) {
                $stats[ $key ] = array( 'impressions' => 0, 'conversions' => 0 );
            }
        }
        return $stats;
    }

    /** @param string $variant */
    private static function increment( $variant, $field ) {
        $stats = self::get_stats();
        if ( ! isset( $stats[ $variant ][ $field ] ) ) {
            $stats[ $variant ][ $field ] = 0;
        }
        $stats[ $variant ][ $field ]++;
        update_option( self::STATS_OPTION, $stats, false );
    }

    /** AJAX: record a banner impression. */
    public function ajax_track_impression() {
        $variant = isset( $_POST['variant'] ) ? sanitize_key( $_POST['variant'] ) : '';
        if ( array_key_exists( $variant, self::VARIANTS ) ) {
            self::increment( $variant, 'impressions' );
        }
        wp_send_json_success();
    }

    /** AJAX: record an accept-all conversion. */
    public function ajax_track_conversion() {
        $variant = isset( $_POST['variant'] ) ? sanitize_key( $_POST['variant'] ) : '';
        if ( array_key_exists( $variant, self::VARIANTS ) ) {
            self::increment( $variant, 'conversions' );
        }
        wp_send_json_success();
    }

    // ── Admin actions ─────────────────────────────────────────────────────

    /** AJAX: promote winning variant to the live position setting. */
    public function ajax_promote_winner() {
        check_ajax_referer( 'mbr_cc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $winner = self::get_winner();
        if ( ! $winner ) {
            wp_send_json_error( array( 'message' => 'No winner determined yet — not enough data.' ) );
        }

        $position = self::VARIANTS[ $winner ];
        update_option( 'mbr_cc_banner_position', $position );
        update_option( 'mbr_cc_ab_testing_enabled', false );

        wp_send_json_success( array(
            'message'  => sprintf(
                /* translators: 1: promoted variant label, 2: banner position the variant maps to. */
                __( 'Variant %1$s promoted. Banner position set to "%2$s". A/B testing disabled.', 'mbr-cookie-consent' ),
                strtoupper( $winner ),
                $position
            ),
            'position' => $position,
        ) );
    }

    /** AJAX: reset all A/B stats. */
    public function ajax_reset_stats() {
        check_ajax_referer( 'mbr_cc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }
        delete_option( self::STATS_OPTION );
        wp_send_json_success( array( 'message' => __( 'A/B test stats reset.', 'mbr-cookie-consent' ) ) );
    }

    /**
     * Return the variant key with the highest accept-all rate, or null if
     * no variant has enough impressions (minimum 10) to be meaningful.
     *
     * @return string|null
     */
    public static function get_winner() {
        $stats      = self::get_stats();
        $best_key   = null;
        $best_rate  = -1;

        foreach ( $stats as $key => $data ) {
            if ( $data['impressions'] < 10 ) {
                continue;
            }
            $rate = $data['impressions'] > 0
                ? $data['conversions'] / $data['impressions']
                : 0;
            if ( $rate > $best_rate ) {
                $best_rate = $rate;
                $best_key  = $key;
            }
        }

        return $best_key;
    }

    // ── Frontend tracker script ───────────────────────────────────────────

    public function enqueue_tracker() {
        if ( is_admin() ) {
            return;
        }
        // Inline script — tiny enough not to warrant a separate file.
        $variant  = self::current_variant();
        $ajax_url = admin_url( 'admin-ajax.php' );
        $script   = "
(function() {
    var variant = " . json_encode( $variant ) . ";
    var ajaxUrl = " . json_encode( $ajax_url ) . ";

    function post(action) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('variant', variant);
        navigator.sendBeacon ? navigator.sendBeacon(ajaxUrl, fd)
            : fetch(ajaxUrl, { method: 'POST', body: fd });
    }

    // Track impression when banner becomes visible.
    document.addEventListener('DOMContentLoaded', function() {
        var banner = document.getElementById('mbr-cc-banner');
        if (!banner) return;
        var obs = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.type === 'attributes' && m.attributeName === 'style') {
                    if (banner.style.display !== 'none' && banner.offsetParent !== null) {
                        post('mbr_cc_ab_impression');
                        obs.disconnect();
                    }
                }
            });
        });
        obs.observe(banner, { attributes: true });
        // Also catch it if already visible on load.
        if (banner.style.display !== 'none' && getComputedStyle(banner).display !== 'none') {
            post('mbr_cc_ab_impression');
        }
    });

    // Track accept-all conversion.
    document.addEventListener('mbr_cc_consent_saved', function(e) {
        var consent = e.detail && e.detail[0];
        if (consent && (consent.all === true)) {
            post('mbr_cc_ab_conversion');
        }
    });
    // jQuery event fallback.
    if (window.jQuery) {
        jQuery(document).on('mbr_cc_consent_saved', function(e, consent) {
            if (consent && consent.all === true) {
                post('mbr_cc_ab_conversion');
            }
        });
    }
})();
";
        wp_add_inline_script( 'mbr-cc-banner', $script );
    }

    /** @return bool */
    public static function is_enabled() {
        return (bool) get_option( 'mbr_cc_ab_testing_enabled', false );
    }
}
