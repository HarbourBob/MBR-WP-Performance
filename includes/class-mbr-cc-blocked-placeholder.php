<?php
/**
 * Blocked Content Placeholder
 *
 * Generates a branded modal shown in place of blocked content when the
 * visitor has not granted the required cookie consent.
 *
 * IMPORTANT: render() deliberately uses string concatenation rather than
 * ob_start() / ob_get_clean() because it is called from inside
 * MBR_CC_Script_Blocker::process_buffer() — which is itself an ob_start()
 * callback. Nesting ob_start() inside an active output-buffer callback
 * suppresses the entire page output, so we avoid it here.
 *
 * @package MBR_Cookie_Consent
 * @since   1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MBR_CC_Blocked_Placeholder
 */
class MBR_CC_Blocked_Placeholder {

    /**
     * Render the placeholder HTML for a blocked element.
     *
     * Returns a string — safe to echo or concatenate directly.
     * Must NOT use ob_start() (see class docblock).
     *
     * @param  array $args Optional overrides: heading, message, btn_text.
     * @return string      HTML ready to insert into the page.
     */
    public static function render( array $args = [] ) {

        // ── Text — use service name for specific messaging if provided ──────
        $service  = ! empty( $args['service'] ) ? sanitize_text_field( $args['service'] ) : '';

        // Heading: prefer explicit override, then service-specific, then admin setting, then generic.
        if ( ! empty( $args['heading'] ) ) {
            $heading = sanitize_text_field( $args['heading'] );
        } elseif ( $service ) {
            /* translators: %s: service name e.g. "YouTube" */
            $heading = sprintf( __( '%s video blocked', 'mbr-cookie-consent' ), $service );
        } else {
            $heading = self::get_opt( 'blocked_overlay_heading', __( 'Content blocked', 'mbr-cookie-consent' ) );
        }

        // Message: prefer explicit override, then service-specific, then admin setting, then generic.
        if ( ! empty( $args['message'] ) ) {
            $message = sanitize_text_field( $args['message'] );
        } elseif ( $service ) {
            $message = sprintf(
                /* translators: %s: service name e.g. "YouTube" */
                __( 'This %s video is blocked because marketing cookies have not been accepted. Accept cookies to watch it.', 'mbr-cookie-consent' ),
                $service
            );
        } else {
            $message = self::get_opt(
                'blocked_overlay_message',
                __( "Some content on this page requires cookie consent that hasn't been granted yet. Update your preferences to view it.", 'mbr-cookie-consent' )
            );
        }

        $btn_text = ! empty( $args['btn_text'] )
            ? sanitize_text_field( $args['btn_text'] )
            : self::get_opt( 'blocked_overlay_btn_text', __( 'Accept cookies & play video', 'mbr-cookie-consent' ) );

        // ── Colours ───────────────────────────────────────────────────────
        $accent      = self::get_opt( 'primary_color', '#0073aa' );
        $accent_dark = self::darken_hex( $accent, 12 );

        // ── Logo ──────────────────────────────────────────────────────────
        $logo_html = self::get_logo_html();

        // ── SVG icons (inline — zero external dependencies) ───────────────
        // Play icon — makes it immediately clear this is a blocked video.
        $lock_svg =
            '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="white">'
            . '<path d="M8 5v14l11-7z"/>'
            . '</svg>';

        $cog_svg =
            '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"'
            . ' fill="none" stroke="currentColor" stroke-width="2.5"'
            . ' stroke-linecap="round" stroke-linejoin="round">'
            . '<circle cx="12" cy="12" r="3"/>'
            . '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83'
            . 'l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4'
            . ' 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0'
            . ' 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2'
            . ' 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06'
            . 'a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0'
            . ' 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0'
            . ' 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9'
            . 'a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'
            . '</svg>';

        // ── Build HTML via string concatenation (no ob_start!) ────────────
        $html  = '<div class="mbr-cc-blocked-wrapper"';
        $html .= ' role="region"';
        $html .= ' aria-label="' . esc_attr__( 'Blocked content', 'mbr-cookie-consent' ) . '"';
        $html .= ' style="--mbr-cc-accent:' . esc_attr( $accent ) . ';--mbr-cc-accent-dark:' . esc_attr( $accent_dark ) . '">';
        $html .=   '<div class="mbr-cc-blocked-modal">';
        $html .=     $logo_html;
        $html .=     '<div class="mbr-cc-blocked-icon">' . $lock_svg . '</div>';
        $html .=     '<p class="mbr-cc-blocked-heading">' . esc_html( $heading ) . '</p>';
        $html .=     '<p class="mbr-cc-blocked-message">' . esc_html( $message ) . '</p>';
        $html .=     '<button type="button" class="mbr-cc-blocked-btn"';
        $html .=       ' aria-label="' . esc_attr__( 'Open cookie settings', 'mbr-cookie-consent' ) . '">';
        $html .=       $cog_svg;
        $html .=       esc_html( $btn_text );
        $html .=     '</button>';
        $html .=   '</div>';
        $html .= '</div>';

        return $html;
    }

    // ── Public helpers ────────────────────────────────────────────────────

    /**
     * Whether the blocked-content overlay feature is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) self::get_opt( 'blocked_overlay_enabled', false );
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Retrieve a single option (mbr_cc_ prefix applied automatically).
     *
     * @param  string $key     Option key without prefix.
     * @param  mixed  $default Default value.
     * @return mixed
     */
    private static function get_opt( $key, $default = '' ) {
        return get_option( 'mbr_cc_' . $key, $default );
    }

    /**
     * Build logo HTML using a priority cascade:
     *   1. Custom URL from plugin settings.
     *   2. WordPress Customiser / block-theme logo.
     *   3. WordPress site icon (favicon at 64 px).
     *   4. Site-name text fallback.
     *
     * @return string Escaped HTML.
     */
    private static function get_logo_html() {
        $site_name = get_bloginfo( 'name' );

        // 1. Custom logo URL entered in plugin settings.
        $custom_url = self::get_opt( 'blocked_overlay_logo_url', '' );
        if ( $custom_url ) {
            return '<img src="' . esc_url( $custom_url ) . '" alt="' . esc_attr( $site_name ) . '" class="mbr-cc-blocked-logo">';
        }

        // 2. WordPress theme / Customizer logo.
        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            $src = wp_get_attachment_image_src( $logo_id, 'medium' );
            if ( ! empty( $src[0] ) ) {
                return '<img src="' . esc_url( $src[0] ) . '" alt="' . esc_attr( $site_name ) . '" class="mbr-cc-blocked-logo">';
            }
        }

        // 3. Site icon.
        $icon_url = get_site_icon_url( 64 );
        if ( $icon_url ) {
            return '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $site_name ) . '" class="mbr-cc-blocked-logo mbr-cc-blocked-logo--icon">';
        }

        // 4. Text fallback.
        return '<span class="mbr-cc-blocked-logo-text">' . esc_html( $site_name ) . '</span>';
    }

    /**
     * Darken a hex colour by a percentage (for button hover state).
     *
     * @param  string $hex     Hex colour (with or without leading #).
     * @param  int    $percent Amount to darken (0–100).
     * @return string          Hex colour string.
     */
    private static function darken_hex( $hex, $percent ) {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( 6 !== strlen( $hex ) ) {
            return '#005a87';
        }
        $factor = 1 - ( $percent / 100 );
        $r      = max( 0, (int) round( hexdec( substr( $hex, 0, 2 ) ) * $factor ) );
        $g      = max( 0, (int) round( hexdec( substr( $hex, 2, 2 ) ) * $factor ) );
        $b      = max( 0, (int) round( hexdec( substr( $hex, 4, 2 ) ) * $factor ) );
        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }
}
