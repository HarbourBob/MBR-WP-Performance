<?php
/**
 * Script Blocker
 *
 * Prevents non-consented scripts and iframes from loading, and optionally
 * replaces blocked iframes with a branded placeholder overlay.
 *
 * Built-in service definitions cover the most common third-party embeds so
 * site owners do not need to configure them manually. Custom entries added
 * via the Scanner screen are merged on top.
 *
 * @package MBR_Cookie_Consent
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MBR_CC_Script_Blocker
 */
class MBR_CC_Script_Blocker {

    /** @var MBR_CC_Script_Blocker|null */
    private static $instance = null;

    /** @var array Custom blocked-script entries from the database. */
    private $blocked_scripts = array();

    /**
     * Built-in service definitions organised by consent category.
     * Each entry is checked against every <script src="…"> and <iframe src="…">
     * in the page output.
     *
     * 'domains' — URL fragments matched against src attributes.
     * 'type'    — 'script' | 'iframe' | 'both'
     *
     * @var array
     */
    private static $builtin_services = array(

        // ── Marketing ─────────────────────────────────────────────────────
        'marketing' => array(
            array(
                'name'    => 'YouTube',
                'domains' => array( 'youtube.com/embed', 'youtube-nocookie.com/embed', 'youtu.be' ),
                'type'    => 'iframe',
            ),
            array(
                'name'    => 'Google Ads / DoubleClick',
                'domains' => array( 'googleadservices.com', 'doubleclick.net', 'googlesyndication.com' ),
                'type'    => 'both',
            ),
            array(
                'name'    => 'Facebook Pixel',
                'domains' => array( 'connect.facebook.net', 'facebook.com/tr' ),
                'type'    => 'both',
            ),
            array(
                'name'    => 'Twitter / X',
                'domains' => array( 'platform.twitter.com', 'syndication.twitter.com', 'ads-twitter.com' ),
                'type'    => 'both',
            ),
            array(
                'name'    => 'LinkedIn Insight',
                'domains' => array( 'snap.licdn.com', 'linkedin.com/insight' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'TikTok Pixel',
                'domains' => array( 'analytics.tiktok.com' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Pinterest Tag',
                'domains' => array( 'ct.pinterest.com', 'pintrk' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Hotjar',
                'domains' => array( 'static.hotjar.com' ),
                'type'    => 'script',
            ),
        ),

        // ── Analytics ─────────────────────────────────────────────────────
        'analytics' => array(
            array(
                'name'    => 'Google Analytics',
                'domains' => array( 'google-analytics.com', 'googletagmanager.com', 'gtag/js' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Matomo / Piwik',
                'domains' => array( 'matomo.js', 'piwik.js' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Clarity',
                'domains' => array( 'clarity.ms' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Mixpanel',
                'domains' => array( 'cdn.mxpnl.com' ),
                'type'    => 'script',
            ),
        ),

        // ── Preferences ───────────────────────────────────────────────────
        'preferences' => array(
            array(
                'name'    => 'Vimeo',
                'domains' => array( 'player.vimeo.com' ),
                'type'    => 'iframe',
            ),
            array(
                'name'    => 'Google Maps',
                'domains' => array( 'maps.google.com', 'maps.googleapis.com', 'google.com/maps' ),
                'type'    => 'iframe',
            ),
            array(
                'name'    => 'Google Fonts',
                'domains' => array( 'fonts.googleapis.com', 'fonts.gstatic.com' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Spotify Embed',
                'domains' => array( 'open.spotify.com/embed' ),
                'type'    => 'iframe',
            ),
            array(
                'name'    => 'SoundCloud',
                'domains' => array( 'w.soundcloud.com/player' ),
                'type'    => 'iframe',
            ),
            array(
                'name'    => 'Intercom',
                'domains' => array( 'widget.intercom.io', 'js.intercomcdn.com' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'Drift',
                'domains' => array( 'js.driftt.com' ),
                'type'    => 'script',
            ),
            array(
                'name'    => 'HubSpot',
                'domains' => array( 'js.hs-scripts.com', 'js.hsforms.net' ),
                'type'    => 'script',
            ),
        ),
    );

    // ─────────────────────────────────────────────────────────────────────
    // Singleton
    // ─────────────────────────────────────────────────────────────────────

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook at template_redirect priority 1 (before most plugins).
        add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );

        // Tell WP Rocket not to lazy-load iframes that we will be blocking —
        // this prevents WP Rocket transforming src -> data-lazy-src before our
        // buffer processes the HTML, which would cause our regex to miss them.
        add_filter( 'rocket_lazy_load_exclude_iframes', array( $this, 'rocket_exclude_iframes' ) );

        // Tell WP Rocket's new Delay JS / Minify not to touch our assets.
        add_filter( 'rocket_delay_js_exclusions', array( $this, 'rocket_exclude_js' ) );

        $this->load_blocked_scripts();
    }

    /**
     * Tell WP Rocket to skip lazy-loading iframes from services we block.
     * This ensures src attributes are preserved so our regex can match them.
     *
     * @param  array $exclusions Existing WP Rocket iframe exclusions.
     * @return array
     */
    public function rocket_exclude_iframes( $exclusions ) {
        $domains = array();
        foreach ( self::$builtin_services as $services ) {
            foreach ( $services as $service ) {
                if ( 'iframe' === $service['type'] || 'both' === $service['type'] ) {
                    foreach ( $service['domains'] as $domain ) {
                        $domains[] = $domain;
                    }
                }
            }
        }
        // Add custom iframe entries too.
        foreach ( $this->blocked_scripts as $script ) {
            if ( 'iframe' === ( $script['type'] ?? '' ) ) {
                $domains[] = $script['identifier'];
            }
        }
        return array_merge( $exclusions, $domains );
    }

    /**
     * Tell WP Rocket not to delay or minify our consent JS.
     *
     * @param  array $exclusions Existing exclusions.
     * @return array
     */
    public function rocket_exclude_js( $exclusions ) {
        $exclusions[] = 'mbr-cookie-consent';
        $exclusions[] = 'mbr-cc-banner';
        $exclusions[] = 'mbr-cc-blocked-content';
        return $exclusions;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Output buffering
    // ─────────────────────────────────────────────────────────────────────

    private function load_blocked_scripts() {
        $this->blocked_scripts = get_option( 'mbr_cc_blocked_scripts', array() );
    }

    public function start_buffer() {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }
        ob_start( array( $this, 'process_buffer' ) );
    }

    public function process_buffer( $buffer ) {
        $consent = $this->get_user_consent();

        // User accepted everything — nothing to block.
        if ( isset( $consent['all'] ) && true === $consent['all'] ) {
            return $buffer;
        }

        // Process built-in service rules.
        $buffer = $this->apply_builtin_rules( $buffer, $consent );

        // Process custom manually-added entries.
        $buffer = $this->apply_custom_rules( $buffer, $consent );

        return $buffer;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Consent cookie
    // ─────────────────────────────────────────────────────────────────────

    private function get_user_consent() {
        if ( ! isset( $_COOKIE['mbr_cc_consent'] ) ) {
            return array();
        }
        $data = json_decode( stripslashes( $_COOKIE['mbr_cc_consent'] ), true );
        return is_array( $data ) ? $data : array();
    }

    private function category_allowed( $category, $consent ) {
        if ( 'necessary' === $category ) {
            return true;
        }
        return isset( $consent[ $category ] ) && true === $consent[ $category ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Built-in rules
    // ─────────────────────────────────────────────────────────────────────

    private function apply_builtin_rules( $html, $consent ) {
        foreach ( self::$builtin_services as $category => $services ) {
            if ( $this->category_allowed( $category, $consent ) ) {
                continue; // Consent granted — leave alone.
            }
            foreach ( $services as $service ) {
                foreach ( $service['domains'] as $domain ) {
                    if ( 'script' === $service['type'] || 'both' === $service['type'] ) {
                        $html = $this->block_script_src( $html, $domain, $category );
                    }
                    if ( 'iframe' === $service['type'] || 'both' === $service['type'] ) {
                        $html = $this->block_iframe_src( $html, $domain, $service['name'], $category );
                    }
                }
            }
        }
        return $html;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Custom (manually-added) rules
    // ─────────────────────────────────────────────────────────────────────

    private function apply_custom_rules( $html, $consent ) {
        $by_category = array();
        foreach ( $this->blocked_scripts as $script ) {
            $cat = $script['category'] ?? 'marketing';
            $by_category[ $cat ][] = $script;
        }

        foreach ( $by_category as $category => $scripts ) {
            if ( $this->category_allowed( $category, $consent ) ) {
                continue;
            }
            foreach ( $scripts as $script ) {
                $id   = $script['identifier'];
                $type = $script['type'] ?? 'src';

                if ( 'src' === $type ) {
                    $html = $this->block_script_src( $html, $id, $category );
                } elseif ( 'inline' === $type ) {
                    $html = $this->block_inline_script( $html, $id );
                } elseif ( 'iframe' === $type ) {
                    $html = $this->block_iframe_src( $html, $id, $script['name'] ?? '', $category );
                }
            }
        }
        return $html;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Blocking primitives
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Block <script src="…"> tags whose src contains $pattern.
     */
    private function block_script_src( $html, $pattern, $category = 'marketing' ) {
        $regex = '/<script(\s[^>]*)?src=(["\'])([^"\']*'
               . preg_quote( $pattern, '/' )
               . '[^"\']*)(\2)/i';

        return preg_replace_callback( $regex, function ( $m ) {
            $attrs = $m[1];
            $src   = $m[3];
            return '<script' . $attrs
                 . ' type="text/plain"'
                 . ' data-mbr-cc-blocked="true"'
                 . ' data-mbr-cc-category="' . esc_attr( $category ) . '"'
                 . ' data-mbr-cc-src="' . esc_attr( $src ) . '"';
        }, $html ) ?? $html;
    }

    /**
     * Block <script>…content…</script> tags whose body contains $pattern.
     */
    private function block_inline_script( $html, $pattern ) {
        $regex = '/<script(\s[^>]*)?>.*?' . preg_quote( $pattern, '/' ) . '.*?<\/script>/is';

        return preg_replace_callback( $regex, function ( $m ) {
            $attrs   = isset( $m[1] ) ? $m[1] : '';
            $content = $m[0];
            // Strip existing type attribute, then set to text/plain.
            $attrs = preg_replace( '/\s*type=["\'][^"\']*["\']/', '', $attrs );
            $attrs .= ' type="text/plain" data-mbr-cc-blocked="true"';
            // Rebuild tag preserving content.
            preg_match( '/<script[^>]*>(.*?)<\/script>/is', $content, $inner );
            return '<script' . $attrs . '>' . ( $inner[1] ?? '' ) . '</script>';
        }, $html ) ?? $html;
    }

    /**
     * Block <iframe src="…"> tags whose src contains $pattern.
     *
     * Replaces the entire <iframe>…</iframe> with:
     *   - The branded placeholder overlay (if enabled), plus
     *   - The original iframe with src removed and hidden (ready to restore
     *     when consent is later granted via banner.js unblockScripts).
     *
     * The regex intentionally handles:
     *   - Single or double quotes around src.
     *   - src appearing anywhere in the tag (not necessarily first).
     *   - Self-closing or paired iframes.
     */
    private function block_iframe_src( $html, $pattern, $service_name = '', $category = 'marketing' ) {
        $placeholder_class = 'MBR_CC_Blocked_Placeholder';
        $escaped           = preg_quote( $pattern, '/' );

        // Build per-attribute regexes. The 'src' pattern uses a negative
        // lookbehind so it doesn't accidentally match 'data-lazy-src'.
        $attr_patterns = array(
            'src'            => '(?<![a-zA-Z0-9_-])src',
            'data-lazy-src'  => 'data-lazy-src',
            'data-src'       => 'data-src',
            'data-rocket-src'=> 'data-rocket-src',
        );

        foreach ( $attr_patterns as $attr => $attr_regex ) {
            $regex = '/<iframe(\s[^>]*?)?' . $attr_regex
                   . '=(["\'])([^"\']*' . $escaped . '[^"\']*)(\2)([^>]*)>/i';

            $html = ( preg_replace_callback(
                $regex,
                function ( $m ) use ( $service_name, $category, $placeholder_class ) {
                    $before = isset( $m[1] ) ? $m[1] : '';
                    $src    = $m[3];
                    $after  = isset( $m[5] ) ? $m[5] : '';

                    $blocked = '<iframe'
                        . $before
                        . ' data-mbr-cc-blocked="true"'
                        . ' data-mbr-cc-category="' . esc_attr( $category ) . '"'
                        . ' data-mbr-cc-src="' . esc_attr( $src ) . '"'
                        . $after
                        . ' style="display:none !important" aria-hidden="true">';

                    // Always render a placeholder — silently hiding content with no
                    // explanation is bad UX and leaves users with no way to unblock it.
                    // The admin toggle controls customisation options, not visibility.
                    $overlay = class_exists( $placeholder_class )
                        ? $placeholder_class::render( array( 'service' => $service_name ) )
                        : '';

                    return $overlay . $blocked;
                },
                $html
            ) ?? $html );
        }

        return $html;
    }

    // ─────────────────────────────────────────────────────────────────────
    // CRUD — custom blocked-script entries (used by Scanner screen / AJAX)
    // ─────────────────────────────────────────────────────────────────────

    public function add_blocked_script( $script ) {
        $defaults = array(
            'name'        => '',
            'identifier'  => '',
            'type'        => 'src',
            'category'    => 'marketing',
            'description' => '',
        );
        $script = wp_parse_args( $script, $defaults );
        if ( empty( $script['name'] ) || empty( $script['identifier'] ) ) {
            return false;
        }
        $this->blocked_scripts[] = $script;
        return update_option( 'mbr_cc_blocked_scripts', $this->blocked_scripts );
    }

    public function remove_blocked_script( $index ) {
        if ( ! isset( $this->blocked_scripts[ $index ] ) ) {
            return false;
        }
        unset( $this->blocked_scripts[ $index ] );
        $this->blocked_scripts = array_values( $this->blocked_scripts );
        return update_option( 'mbr_cc_blocked_scripts', $this->blocked_scripts );
    }

    public function update_blocked_script( $index, $script ) {
        if ( ! isset( $this->blocked_scripts[ $index ] ) ) {
            return false;
        }
        $this->blocked_scripts[ $index ] = array_merge( $this->blocked_scripts[ $index ], $script );
        return update_option( 'mbr_cc_blocked_scripts', $this->blocked_scripts );
    }

    public function get_blocked_scripts() {
        return $this->blocked_scripts;
    }

    public function clear_blocked_scripts() {
        $this->blocked_scripts = array();
        return delete_option( 'mbr_cc_blocked_scripts' );
    }

    /**
     * Return the built-in service list (used by admin UI if needed).
     *
     * @return array
     */
    public static function get_builtin_services() {
        return self::$builtin_services;
    }
}
