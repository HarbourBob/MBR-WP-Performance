<?php
/**
 * Font Optimizations
 *
 * @package MBRPE
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Font_Optimizations {
    private static $instance = null;
    private $options = array();

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = mbrpe()->get_options( 'fonts' );
        $this->init_optimizations();
    }

    private function init_optimizations() {
        // Self-host Google Fonts - ALWAYS load local fonts if enabled
        if ( $this->get_option( 'self_host_google_fonts' ) ) {
            // Preload local fonts if enabled
            if ( $this->get_option( 'preload_local_fonts' ) ) {
                add_action( 'wp_head', array( $this, 'preload_local_fonts' ), 2 );
            }
            
            // Load local fonts in head
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_local_fonts' ), 5 );
            
            // Remove Google Fonts
            add_action( 'wp_enqueue_scripts', array( $this, 'replace_google_fonts' ), 999 );
            add_filter( 'style_loader_tag', array( $this, 'filter_google_font_links' ), 10, 4 );
        }
        
        // Preload fonts (custom URLs)
        if ( $this->get_option( 'preload_fonts' ) ) {
            add_action( 'wp_head', array( $this, 'preload_fonts' ), 1 );
        }
        
        // Disable Google Fonts
        if ( $this->get_option( 'disable_google_fonts' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_google_fonts' ), 999 );
            add_filter( 'style_loader_tag', array( $this, 'remove_google_font_links' ), 10, 4 );
            
            // Block at script/style source level
            add_filter( 'style_loader_src', array( $this, 'block_google_font_src' ), 10, 2 );
            add_filter( 'script_loader_src', array( $this, 'block_google_font_src' ), 10, 2 );
            
            // Remove from head
            add_action( 'wp_head', array( $this, 'remove_google_fonts_meta' ), 1 );

            // Final-output sweep: strips hardcoded <link> tags, preconnects, and
            // inline @font-face/@import that never pass through the enqueue
            // system (e.g. fonts hardcoded into a theme's header.php).
            add_action( 'template_redirect', array( $this, 'maybe_strip_google_fonts' ), 1 );
        }

        // Disable Elementor's Google Fonts requests entirely.
        if ( $this->get_option( 'disable_elementor_fonts' ) && defined( 'ELEMENTOR_VERSION' ) ) {
            add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
        }
        
        // Preconnect to font domains
        if ( $this->get_option( 'preconnect_domains' ) ) {
            add_action( 'wp_head', array( $this, 'preconnect_domains' ), 1 );
        }
        
        // DNS Prefetch
        if ( $this->get_option( 'dns_prefetch' ) ) {
            add_action( 'wp_head', array( $this, 'dns_prefetch' ), 1 );
        }
        
        // Disable Font Awesome
        if ( $this->get_option( 'disable_font_awesome' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_font_awesome' ), 999 );
        }
        
        // Async Font Awesome
        if ( $this->get_option( 'async_font_awesome' ) ) {
            add_filter( 'style_loader_tag', array( $this, 'async_font_awesome' ), 10, 4 );
        }
    }
    
    /**
     * Preload local fonts
     */
    public function preload_local_fonts() {
        $local_fonts = get_option( 'mbrpe_local_fonts', array() );
        $fonts_dir = get_option( 'mbrpe_fonts_dir' );
        
        if ( empty( $local_fonts ) || empty( $fonts_dir ) ) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $fonts_url = $upload_dir['baseurl'] . '/mbr-performance-fonts';
        
        echo "\n<!-- MBR Performance: Preloading Local Fonts -->\n";
        
        $preloaded = 0;
        
        foreach ( $local_fonts as $family => $variants ) {
            if ( ! is_array( $variants ) ) {
                $variants = array( $variants );
            }
            
            foreach ( $variants as $variant ) {
                // Find the actual font files for this variant
                $css_filename = sanitize_file_name( $family . '-' . $variant . '.css' );
                $css_path = $fonts_dir . '/' . $css_filename;
                
                if ( file_exists( $css_path ) ) {
                    // Read the CSS to extract font file URLs
                    $css_content = file_get_contents( $css_path );
                    
                    // Extract ONLY the first WOFF2 file (main Latin subset)
                    // This prevents loading 10+ files per weight
                    preg_match( '/url\(["\']?([^"\']+\.woff2)["\']?\)/', $css_content, $woff2_match );
                    
                    if ( ! empty( $woff2_match[1] ) ) {
                        // Preload only the FIRST (primary) font file
                        printf(
                            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">' . "\n",
                            esc_url( $woff2_match[1] )
                        );
                        $preloaded++;
                    }
                }
            }
        }
        
        echo "<!-- Preloaded " . (int) $preloaded . " font files -->\n\n";
    }
    
    /**
     * Enqueue self-hosted local fonts as inline CSS.
     *
     * The per-variant CSS files produced by the self-hosting feature are
     * concatenated and attached via wp_add_inline_style() on a registered
     * (src-less) handle, rather than echoed inside a raw <style> tag. This
     * keeps the CSS inline (no extra request) while going through the proper
     * stylesheet API, so no manual output escaping is required.
     */
    public function enqueue_local_fonts() {
        $local_fonts = get_option( 'mbrpe_local_fonts', array() );
        $fonts_dir   = get_option( 'mbrpe_fonts_dir' );

        if ( empty( $local_fonts ) || empty( $fonts_dir ) ) {
            return;
        }

        $css = '';
        foreach ( $local_fonts as $family => $variants ) {
            if ( ! is_array( $variants ) ) {
                $variants = array( $variants );
            }
            foreach ( $variants as $variant ) {
                $css_filename = sanitize_file_name( $family . '-' . $variant . '.css' );
                $css_path     = $fonts_dir . '/' . $css_filename;
                if ( ! file_exists( $css_path ) ) {
                    continue;
                }
                $css_content = file_get_contents( $css_path );
                if ( ! empty( $css_content ) ) {
                    $label = preg_replace( '/[^A-Za-z0-9 _.\-]/', '', $family . ' - ' . $variant );
                    $css  .= '/* ' . $label . " */\n" . $css_content . "\n";
                }
            }
        }

        if ( '' === $css ) {
            return;
        }

        wp_register_style( 'mbr-performance-local-fonts', false, array(), MBRPE_VERSION );
        wp_enqueue_style( 'mbr-performance-local-fonts' );
        wp_add_inline_style( 'mbr-performance-local-fonts', wp_strip_all_tags( $css ) );
    }
    
    /**
     * Get option value
     */
    private function get_option( $key, $default = false ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    /**
     * Preload fonts
     */
    public function preload_fonts() {
        $font_urls = $this->get_option( 'preload_font_urls' );
        
        if ( empty( $font_urls ) ) {
            return;
        }
        
        $urls = array_filter( array_map( 'trim', explode( "\n", $font_urls ) ) );
        
        foreach ( $urls as $url ) {
            printf(
                '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">' . "\n",
                esc_url( $url )
            );
        }
    }

    /**
     * Replace Google Fonts with local versions
     */
    public function replace_google_fonts() {
        // Local fonts are loaded via enqueue_local_fonts().
        self::neutralise_google_font_styles();
    }

    /**
     * Stop registered Google Fonts stylesheets from loading, without
     * unregistering them.
     *
     * Deregistering looks like the thorough thing to do and is actively
     * harmful. A handle is not just a URL: other stylesheets may name it as a
     * dependency, and themes routinely hang wp_add_inline_style() CSS off
     * whatever handle is to hand. Remove the handle and WordPress silently
     * declines to print anything that depended on it, and drops the attached
     * inline CSS with it — which is how switching on font self-hosting could
     * take a theme's header styling with it.
     *
     * Blanking the src instead keeps the handle resolvable. WP_Styles treats a
     * src-less handle as an alias: dependents still resolve, attached inline
     * CSS is still printed, and no <link> is emitted because there is nothing
     * to point it at.
     *
     * @since 2.0.1
     * @return void
     */
    private static function neutralise_google_font_styles() {
        global $wp_styles;

        if ( empty( $wp_styles->registered ) ) {
            return;
        }

        foreach ( $wp_styles->registered as $handle => $style ) {
            if ( empty( $style->src ) || ! is_string( $style->src ) ) {
                continue;
            }
            if ( false === strpos( $style->src, 'fonts.googleapis.com' )
                && false === strpos( $style->src, 'fonts.gstatic.com' ) ) {
                continue;
            }

            $wp_styles->registered[ $handle ]->src = false;
        }
    }

    /**
     * Filter Google Font link tags
     */
    public function filter_google_font_links( $tag, $handle, $href, $media ) {
        // Remove enqueued remote Google Fonts (stylesheet and font files)
        if ( strpos( $href, 'fonts.googleapis.com' ) !== false || 
             strpos( $href, 'fonts.gstatic.com' ) !== false ) {
            return ''; // Remove the tag
        }
        return $tag;
    }

    /**
     * Remove Google Font links
     */
    public function remove_google_font_links( $tag, $handle, $href, $media ) {
        // Remove enqueued remote Google Fonts (stylesheet and font files)
        if ( strpos( $href, 'fonts.googleapis.com' ) !== false || 
             strpos( $href, 'fonts.gstatic.com' ) !== false ) {
            return '';
        }
        return $tag;
    }

    /**
     * Disable Google Fonts
     */
    public function disable_google_fonts() {
        self::neutralise_google_font_styles();
    }

    /**
     * Preconnect to font domains
     */
    public function preconnect_domains() {
        $domains = $this->get_option( 'font_domains' );
        
        if ( empty( $domains ) ) {
            return;
        }
        
        $domain_list = array_filter( array_map( 'trim', explode( "\n", $domains ) ) );
        
        foreach ( $domain_list as $domain ) {
            printf(
                '<link rel="preconnect" href="https://%s" crossorigin="anonymous">' . "\n",
                esc_attr( $domain )
            );
        }
    }

    /**
     * DNS Prefetch for font domains
     */
    public function dns_prefetch() {
        $domains = $this->get_option( 'font_domains' );
        
        if ( empty( $domains ) ) {
            return;
        }
        
        $domain_list = array_filter( array_map( 'trim', explode( "\n", $domains ) ) );
        
        foreach ( $domain_list as $domain ) {
            printf(
                '<link rel="dns-prefetch" href="https://%s">' . "\n",
                esc_attr( $domain )
            );
        }
    }

    /**
     * Disable Font Awesome
     */
    public function disable_font_awesome() {
        global $wp_styles;
        
        // Blanked rather than deregistered, for the reason set out on
        // neutralise_google_font_styles(): a handle can carry dependents and
        // inline CSS that have nothing to do with the icon font.
        $fa_handles = array( 'font-awesome', 'fontawesome', 'fa', 'fa5', 'fa-brands', 'fa-regular', 'fa-solid' );
        
        foreach ( $fa_handles as $handle ) {
            if ( wp_style_is( $handle, 'registered' ) && ! empty( $wp_styles->registered[ $handle ] ) ) {
                $wp_styles->registered[ $handle ]->src = false;
            }
        }
        
        // Also check for Font Awesome in registered styles
        if ( ! empty( $wp_styles->registered ) ) {
            foreach ( $wp_styles->registered as $handle => $style ) {
                if ( empty( $style->src ) || ! is_string( $style->src ) ) {
                    continue;
                }
                if ( strpos( $style->src, 'font-awesome' ) !== false || strpos( $style->src, 'fontawesome' ) !== false ) {
                    $wp_styles->registered[ $handle ]->src = false;
                }
            }
        }
    }

    /**
     * Make Font Awesome async
     */
    public function async_font_awesome( $tag, $handle, $href, $media ) {
        $fa_handles = array( 'font-awesome', 'fontawesome', 'fa', 'fa5', 'fa-brands', 'fa-regular', 'fa-solid' );
        
        if ( in_array( $handle, $fa_handles ) || strpos( $href, 'font-awesome' ) !== false || strpos( $href, 'fontawesome' ) !== false ) {
            $tag = str_replace( "rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag );
            $tag .= '<noscript>' . str_replace( " onload=\"this.onload=null;this.rel='stylesheet'\"", '', $tag ) . '</noscript>';
        }
        
        return $tag;
    }

    /**
     * Parse Google Font URL (simplified)
     */
    private function parse_google_font_url_simple( $url ) {
        $fonts = array();
        
        $url = html_entity_decode( $url );
        $parsed_url = wp_parse_url( $url );
        
        if ( isset( $parsed_url['query'] ) ) {
            parse_str( $parsed_url['query'], $params );
            
            if ( isset( $params['family'] ) ) {
                $families = is_array( $params['family'] ) ? $params['family'] : array( $params['family'] );
                
                foreach ( $families as $family_string ) {
                    if ( strpos( $family_string, ':' ) !== false ) {
                        list( $family, $variants_string ) = explode( ':', $family_string, 2 );
                        $family = trim( str_replace( '+', ' ', $family ) );
                        
                        $variants = array();
                        if ( strpos( $variants_string, '@' ) !== false ) {
                            $variants_string = explode( '@', $variants_string )[1];
                            $variants = explode( ';', $variants_string );
                        } else {
                            $variants = explode( ',', $variants_string );
                        }
                        
                        $fonts[ $family ] = array_map( 'trim', $variants );
                    } else {
                        $family = trim( str_replace( '+', ' ', $family_string ) );
                        $fonts[ $family ] = array( '400' );
                    }
                }
            }
        }
        
        return $fonts;
    }
    
    /**
     * Block Google Font sources at the WordPress level
     */
    public function block_google_font_src( $src, $handle ) {
        if ( $src && ( strpos( $src, 'fonts.googleapis.com' ) !== false || 
             strpos( $src, 'fonts.gstatic.com' ) !== false ) ) {
            return false; // Return false to prevent loading
        }
        return $src;
    }
    
    /**
     * Remove Google Fonts resource hints.
     *
     * This used to drop wp_resource_hints() from wp_head altogether, which
     * takes every hint on the page with it — a theme's preconnect to its CDN,
     * a plugin's dns-prefetch, the lot — to remove at most two Google entries.
     * Filtering the list leaves everything else where it was.
     *
     * @since 2.0.1 Narrowed from removing the whole wp_resource_hints action.
     * @return void
     */
    public function remove_google_fonts_meta() {
        add_filter( 'wp_resource_hints', array( $this, 'filter_font_resource_hints' ), 10, 2 );
    }

    /**
     * Drop Google Fonts domains from a resource-hint list.
     *
     * @since 2.0.1
     * @param array  $urls          Hint URLs, possibly with attribute arrays.
     * @param string $relation_type dns-prefetch, preconnect, prefetch, prerender.
     * @return array
     */
    public function filter_font_resource_hints( $urls, $relation_type ) {
        if ( ! is_array( $urls ) ) {
            return $urls;
        }

        if ( ! in_array( $relation_type, array( 'dns-prefetch', 'preconnect' ), true ) ) {
            return $urls;
        }

        foreach ( $urls as $key => $url ) {
            // An entry is either a URL string or an array with an 'href'.
            $href = is_array( $url ) && isset( $url['href'] ) ? $url['href'] : $url;

            if ( ! is_string( $href ) ) {
                continue;
            }

            if ( false !== strpos( $href, 'fonts.googleapis.com' )
                || false !== strpos( $href, 'fonts.gstatic.com' ) ) {
                unset( $urls[ $key ] );
            }
        }

        return array_values( $urls );
    }

    /**
     * Decide whether the output-buffer sweep should run for this request.
     *
     * @return bool True to skip (admin, AJAX, REST, feeds, non-GET, etc.).
     */
    private function skip_font_buffer() {
        if ( is_admin() || is_feed() || is_embed() ) {
            return true;
        }
        if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX )
            || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
            || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            return true;
        }
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
            return true;
        }
        if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
            return true;
        }
        return false;
    }

    /**
     * Start buffering front-end output so hardcoded Google Fonts can be stripped.
     */
    public function maybe_strip_google_fonts() {
        if ( $this->skip_font_buffer() ) {
            return;
        }
        ob_start( array( $this, 'strip_google_fonts_buffer' ) );
    }

    /**
     * Strip Google Fonts references that bypass the enqueue system: hardcoded
     * <link> tags (stylesheet, preconnect, preload, dns-prefetch), inline
     * @font-face blocks, and @import statements.
     *
     * @param string $html Buffered page HTML.
     * @return string
     */
    public function strip_google_fonts_buffer( $html ) {
        // Cheap bail-out when there's nothing Google-fonty to remove.
        if ( '' === $html || false === stripos( $html, 'fonts.g' ) ) {
            return $html;
        }
        if ( false === stripos( $html, '</head>' ) ) {
            return $html; // Not a full HTML document.
        }

        // 1) Any <link> referencing the Google Fonts domains, whatever its rel.
        $out = preg_replace(
            '#<link\b[^>]*?(?:fonts\.googleapis\.com|fonts\.gstatic\.com)[^>]*?>#i',
            '',
            $html
        );
        if ( null !== $out ) {
            $html = $out;
        }

        // 2) Inline @font-face blocks that pull from Google Fonts.
        $out = preg_replace(
            '#@font-face\s*\{[^{}]*?(?:fonts\.googleapis\.com|fonts\.gstatic\.com)[^{}]*?\}#is',
            '',
            $html
        );
        if ( null !== $out ) {
            $html = $out;
        }

        // 3) @import statements pulling from Google Fonts.
        $out = preg_replace(
            '#@import\s+(?:url\()?["\']?[^"\')]*fonts\.googleapis\.com[^"\')]*["\']?\)?\s*;?#i',
            '',
            $html
        );
        if ( null !== $out ) {
            $html = $out;
        }

        return $html;
    }
}
