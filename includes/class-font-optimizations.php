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
        global $wp_styles;
        
        // Just remove Google Fonts - local fonts are loaded via load_local_fonts()
        if ( ! empty( $wp_styles->registered ) ) {
            foreach ( $wp_styles->registered as $handle => $style ) {
                // Remove enqueued remote Google Fonts (stylesheet and font files)
                if ( strpos( $style->src, 'fonts.googleapis.com' ) !== false || 
                     strpos( $style->src, 'fonts.gstatic.com' ) !== false ) {
                    wp_dequeue_style( $handle );
                    wp_deregister_style( $handle );
                }
            }
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
        global $wp_styles;
        
        if ( ! empty( $wp_styles->registered ) ) {
            foreach ( $wp_styles->registered as $handle => $style ) {
                // Remove enqueued remote Google Fonts (stylesheet and font files)
                if ( strpos( $style->src, 'fonts.googleapis.com' ) !== false || 
                     strpos( $style->src, 'fonts.gstatic.com' ) !== false ) {
                    wp_dequeue_style( $handle );
                    wp_deregister_style( $handle );
                }
            }
        }
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
        
        $fa_handles = array( 'font-awesome', 'fontawesome', 'fa', 'fa5', 'fa-brands', 'fa-regular', 'fa-solid' );
        
        foreach ( $fa_handles as $handle ) {
            if ( wp_style_is( $handle, 'registered' ) ) {
                wp_dequeue_style( $handle );
                wp_deregister_style( $handle );
            }
        }
        
        // Also check for Font Awesome in registered styles
        if ( ! empty( $wp_styles->registered ) ) {
            foreach ( $wp_styles->registered as $handle => $style ) {
                if ( strpos( $style->src, 'font-awesome' ) !== false || strpos( $style->src, 'fontawesome' ) !== false ) {
                    wp_dequeue_style( $handle );
                    wp_deregister_style( $handle );
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
     * Remove Google Fonts meta tags and preconnect
     */
    public function remove_google_fonts_meta() {
        // Remove any actions that might add Google Fonts preconnect
        remove_action( 'wp_head', 'wp_resource_hints', 2 );
    }
}
