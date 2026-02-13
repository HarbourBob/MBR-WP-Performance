<?php
/**
 * Core Optimizations
 *
 * @package MBR_WP_Performance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core Optimizations class
 */
class MBR_WP_Performance_Core_Optimizations {

    /**
     * Single instance
     *
     * @var MBR_WP_Performance_Core_Optimizations
     */
    private static $instance = null;

    /**
     * Options
     *
     * @var array
     */
    private $options = array();

    /**
     * Get instance
     *
     * @return MBR_WP_Performance_Core_Optimizations
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->options = mbr_wp_performance()->get_options( 'core' );
        $this->init_optimizations();
    }

    /**
     * Initialize optimizations
     */
    private function init_optimizations() {
        // Scripts & Styles
        if ( $this->get_option( 'disable_emojis' ) ) {
            $this->disable_emojis();
        }
        
        if ( $this->get_option( 'disable_dashicons' ) ) {
            $this->disable_dashicons();
        }
        
        if ( $this->get_option( 'disable_embeds' ) ) {
            $this->disable_embeds();
        }
        
        if ( $this->get_option( 'remove_jquery_migrate' ) ) {
            $this->remove_jquery_migrate();
        }
        
        if ( $this->get_option( 'remove_global_styles' ) ) {
            $this->remove_global_styles();
        }
        
        if ( $this->get_option( 'separate_block_styles' ) ) {
            $this->separate_block_styles();
        }
        
        // WordPress Features
        if ( $this->get_option( 'disable_xmlrpc' ) ) {
            $this->disable_xmlrpc();
        }
        
        if ( $this->get_option( 'hide_wp_version' ) ) {
            $this->hide_wp_version();
        }
        
        if ( $this->get_option( 'remove_rsd_link' ) ) {
            remove_action( 'wp_head', 'rsd_link' );
        }
        
        if ( $this->get_option( 'remove_shortlink' ) ) {
            remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        }
        
        if ( $this->get_option( 'disable_rss_feeds' ) ) {
            $this->disable_rss_feeds();
        }
        
        if ( $this->get_option( 'remove_rss_feed_links' ) ) {
            remove_action( 'wp_head', 'feed_links', 2 );
            remove_action( 'wp_head', 'feed_links_extra', 3 );
        }
        
        if ( $this->get_option( 'disable_self_pingbacks' ) ) {
            add_action( 'pre_ping', array( $this, 'disable_self_pingbacks' ) );
        }
        
        // REST API
        $rest_api_mode = $this->get_option( 'rest_api_mode', 'default' );
        if ( 'default' !== $rest_api_mode ) {
            $this->control_rest_api( $rest_api_mode );
        }
        
        if ( $this->get_option( 'remove_rest_api_links' ) ) {
            remove_action( 'wp_head', 'rest_output_link_wp_head' );
            remove_action( 'template_redirect', 'rest_output_link_header', 11 );
        }
        
        // Third-Party Scripts
        if ( $this->get_option( 'disable_google_maps' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_google_maps' ), 99 );
        }
        
        if ( $this->get_option( 'disable_password_strength' ) ) {
            add_action( 'wp_print_scripts', array( $this, 'disable_password_strength' ), 100 );
        }
        
        // Comments
        if ( $this->get_option( 'disable_comments' ) ) {
            $this->disable_comments();
        }
        
        if ( $this->get_option( 'remove_comment_urls' ) ) {
            add_filter( 'comment_form_default_fields', array( $this, 'remove_comment_url_field' ) );
        }
        
        // Editor & Content Management
        $heartbeat_mode = $this->get_option( 'heartbeat_mode', 'default' );
        if ( 'default' !== $heartbeat_mode ) {
            $this->control_heartbeat( $heartbeat_mode );
        }
        
        $heartbeat_frequency = $this->get_option( 'heartbeat_frequency' );
        if ( $heartbeat_frequency && 15 != $heartbeat_frequency ) {
            add_filter( 'heartbeat_settings', array( $this, 'heartbeat_frequency' ) );
        }
        
        $post_revisions = $this->get_option( 'post_revisions', 'default' );
        if ( 'default' !== $post_revisions ) {
            $this->limit_post_revisions( $post_revisions );
        }
        
        $autosave_interval = $this->get_option( 'autosave_interval' );
        if ( $autosave_interval && 60 != $autosave_interval ) {
            $this->set_autosave_interval( $autosave_interval );
        }
        
        // Advanced Performance
        $lazy_load_mode = $this->get_option( 'lazy_load_mode', 'default' );
        if ( 'default' !== $lazy_load_mode ) {
            $this->control_lazy_loading( $lazy_load_mode );
        }
        
        if ( $this->get_option( 'remove_query_strings' ) ) {
            add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 10, 2 );
            add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 10, 2 );
        }
        
        $preload_resources = $this->get_option( 'preload_resources' );
        if ( ! empty( $preload_resources ) ) {
            add_action( 'wp_head', array( $this, 'preload_resources' ), 1 );
        }
        
        if ( $this->get_option( 'disable_woocommerce_scripts' ) && class_exists( 'WooCommerce' ) ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'disable_woocommerce_scripts' ), 99 );
        }
    }

    /**
     * Get option value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function get_option( $key, $default = false ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    /**
     * Disable emojis
     */
    private function disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        
        add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
        add_filter( 'wp_resource_hints', array( $this, 'disable_emojis_dns_prefetch' ), 10, 2 );
    }

    /**
     * Disable emojis in TinyMCE
     *
     * @param array $plugins
     * @return array
     */
    public function disable_emojis_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
            return array_diff( $plugins, array( 'wpemoji' ) );
        }
        return array();
    }

    /**
     * Remove emoji DNS prefetch
     *
     * @param array $urls
     * @param string $relation_type
     * @return array
     */
    public function disable_emojis_dns_prefetch( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
            $urls = array_diff( $urls, array( $emoji_svg_url ) );
        }
        return $urls;
    }

    /**
     * Disable Dashicons
     */
    private function disable_dashicons() {
        if ( ! is_admin() && ! is_user_logged_in() ) {
            add_action( 'wp_enqueue_scripts', function() {
                wp_dequeue_style( 'dashicons' );
                wp_deregister_style( 'dashicons' );
            }, 100 );
        }
    }

    /**
     * Disable embeds
     */
    private function disable_embeds() {
        add_action( 'init', function() {
            // Remove the REST API endpoint
            remove_action( 'rest_api_init', 'wp_oembed_register_route' );
            
            // Turn off oEmbed auto discovery
            add_filter( 'embed_oembed_discover', '__return_false' );
            
            // Don't filter oEmbed results
            remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
            
            // Remove oEmbed discovery links
            remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
            
            // Remove oEmbed-specific JavaScript from the front-end and back-end
            remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        }, 9999 );
    }

    /**
     * Remove jQuery Migrate
     */
    private function remove_jquery_migrate() {
        add_action( 'wp_default_scripts', function( $scripts ) {
            if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
                $script = $scripts->registered['jquery'];
                if ( $script->deps ) {
                    $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
                }
            }
        } );
    }

    /**
     * Remove global styles
     */
    private function remove_global_styles() {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
    }

    /**
     * Separate block styles
     */
    private function separate_block_styles() {
        add_action( 'wp_enqueue_scripts', function() {
            wp_dequeue_style( 'wp-block-library' );
            wp_dequeue_style( 'wp-block-library-theme' );
        }, 100 );
    }

    /**
     * Disable XML-RPC
     */
    private function disable_xmlrpc() {
        add_filter( 'xmlrpc_enabled', '__return_false' );
        
        // Completely disable XML-RPC
        add_filter( 'wp_headers', function( $headers ) {
            if ( isset( $headers['X-Pingback'] ) ) {
                unset( $headers['X-Pingback'] );
            }
            return $headers;
        } );
    }

    /**
     * Hide WordPress version
     */
    private function hide_wp_version() {
        remove_action( 'wp_head', 'wp_generator' );
        add_filter( 'the_generator', '__return_empty_string' );
    }

    /**
     * Disable RSS feeds
     */
    private function disable_rss_feeds() {
        add_action( 'do_feed', array( $this, 'disable_feed' ), 1 );
        add_action( 'do_feed_rdf', array( $this, 'disable_feed' ), 1 );
        add_action( 'do_feed_rss', array( $this, 'disable_feed' ), 1 );
        add_action( 'do_feed_rss2', array( $this, 'disable_feed' ), 1 );
        add_action( 'do_feed_atom', array( $this, 'disable_feed' ), 1 );
        add_action( 'do_feed_rss2_comments', array( $this, 'disable_feed' ), 1 );
        add_action( 'do_feed_atom_comments', array( $this, 'disable_feed' ), 1 );
    }

    /**
     * Disable feed callback
     */
    public function disable_feed() {
        wp_die(
            esc_html__( 'No feed available, please visit our homepage.', 'mbr-wp-performance' ),
            '',
            array( 'response' => 410 )
        );
    }

    /**
     * Disable self pingbacks
     *
     * @param array $links
     */
    public function disable_self_pingbacks( &$links ) {
        $home = get_option( 'home' );
        foreach ( $links as $l => $link ) {
            if ( 0 === strpos( $link, $home ) ) {
                unset( $links[ $l ] );
            }
        }
    }

    /**
     * Control REST API
     *
     * @param string $mode
     */
    private function control_rest_api( $mode ) {
        if ( 'disable_non_admin' === $mode ) {
            add_filter( 'rest_authentication_errors', function( $result ) {
                if ( ! empty( $result ) ) {
                    return $result;
                }
                if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
                    return new WP_Error(
                        'rest_disabled',
                        __( 'REST API disabled for non-administrators.', 'mbr-wp-performance' ),
                        array( 'status' => 401 )
                    );
                }
                return $result;
            } );
        } elseif ( 'disable_logged_out' === $mode ) {
            add_filter( 'rest_authentication_errors', function( $result ) {
                if ( ! empty( $result ) ) {
                    return $result;
                }
                if ( ! is_user_logged_in() ) {
                    return new WP_Error(
                        'rest_disabled',
                        __( 'REST API disabled for logged-out users.', 'mbr-wp-performance' ),
                        array( 'status' => 401 )
                    );
                }
                return $result;
            } );
        }
    }

    /**
     * Disable Google Maps
     */
    public function disable_google_maps() {
        $scripts = wp_scripts();
        foreach ( $scripts->registered as $script ) {
            if ( strpos( $script->src, 'maps.google.com' ) !== false || strpos( $script->src, 'maps.googleapis.com' ) !== false ) {
                wp_dequeue_script( $script->handle );
                wp_deregister_script( $script->handle );
            }
        }
    }

    /**
     * Disable password strength meter
     */
    public function disable_password_strength() {
        if ( wp_script_is( 'zxcvbn-async', 'enqueued' ) ) {
            wp_dequeue_script( 'zxcvbn-async' );
        }
        if ( wp_script_is( 'password-strength-meter', 'enqueued' ) ) {
            wp_dequeue_script( 'password-strength-meter' );
        }
    }

    /**
     * Disable comments
     */
    private function disable_comments() {
        // Close comments on the front-end
        add_filter( 'comments_open', '__return_false', 20, 2 );
        add_filter( 'pings_open', '__return_false', 20, 2 );
        
        // Hide existing comments
        add_filter( 'comments_array', '__return_empty_array', 10, 2 );
        
        // Remove comments page in menu
        add_action( 'admin_menu', function() {
            remove_menu_page( 'edit-comments.php' );
        } );
        
        // Redirect any user trying to access comments page
        add_action( 'admin_init', function() {
            global $pagenow;
            if ( 'edit-comments.php' === $pagenow ) {
                wp_safe_redirect( admin_url() );
                exit;
            }
        } );
        
        // Remove comments metabox from dashboard
        add_action( 'admin_init', function() {
            remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
        } );
        
        // Remove comments links from admin bar
        add_action( 'init', function() {
            if ( is_admin_bar_showing() ) {
                remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
            }
        } );
    }

    /**
     * Remove comment URL field
     *
     * @param array $fields
     * @return array
     */
    public function remove_comment_url_field( $fields ) {
        if ( isset( $fields['url'] ) ) {
            unset( $fields['url'] );
        }
        return $fields;
    }

    /**
     * Control Heartbeat
     *
     * @param string $mode
     */
    private function control_heartbeat( $mode ) {
        if ( 'disable' === $mode ) {
            add_action( 'init', function() {
                wp_deregister_script( 'heartbeat' );
            }, 1 );
        } elseif ( 'allow_posts' === $mode ) {
            add_filter( 'heartbeat_settings', function( $settings ) {
                global $pagenow;
                if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
                    $settings['suspension'] = 'disable';
                }
                return $settings;
            } );
        }
    }

    /**
     * Set heartbeat frequency
     *
     * @param array $settings
     * @return array
     */
    public function heartbeat_frequency( $settings ) {
        $frequency = $this->get_option( 'heartbeat_frequency', 15 );
        $settings['interval'] = $frequency;
        return $settings;
    }

    /**
     * Limit post revisions
     *
     * @param string $limit
     */
    private function limit_post_revisions( $limit ) {
        if ( 'disable' === $limit ) {
            if ( ! defined( 'WP_POST_REVISIONS' ) ) {
                define( 'WP_POST_REVISIONS', false );
            }
        } else {
            $num = absint( $limit );
            if ( ! defined( 'WP_POST_REVISIONS' ) && $num > 0 ) {
                define( 'WP_POST_REVISIONS', $num );
            }
        }
    }

    /**
     * Set autosave interval
     *
     * @param int $interval
     */
    private function set_autosave_interval( $interval ) {
        if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {
            define( 'AUTOSAVE_INTERVAL', absint( $interval ) );
        }
    }

    /**
     * Control lazy loading
     *
     * @param string $mode
     */
    private function control_lazy_loading( $mode ) {
        if ( 'disable' === $mode ) {
            add_filter( 'wp_lazy_loading_enabled', '__return_false' );
        } elseif ( 'enhanced' === $mode ) {
            add_filter( 'wp_get_loading_optimization_attributes', function( $attrs ) {
                if ( isset( $attrs['loading'] ) && 'lazy' === $attrs['loading'] ) {
                    $attrs['loading'] = 'lazy';
                    // Add earlier threshold
                    if ( ! isset( $attrs['data-lazy-threshold'] ) ) {
                        $attrs['data-lazy-threshold'] = '500px';
                    }
                }
                return $attrs;
            }, 10, 1 );
        }
    }

    /**
     * Remove query strings from static resources
     *
     * @param string $src
     * @return string
     */
    public function remove_query_strings( $src ) {
        if ( strpos( $src, '?ver=' ) !== false ) {
            $src = remove_query_arg( 'ver', $src );
        }
        return $src;
    }

    /**
     * Preload resources
     */
    public function preload_resources() {
        $resources = $this->get_option( 'preload_resources' );
        if ( empty( $resources ) ) {
            return;
        }
        
        $urls = array_filter( array_map( 'trim', explode( "\n", $resources ) ) );
        
        foreach ( $urls as $url ) {
            // Determine resource type
            $type = 'style';
            if ( preg_match( '/\.(woff2?|ttf|otf|eot)$/i', $url ) ) {
                $type = 'font';
            } elseif ( preg_match( '/\.js$/i', $url ) ) {
                $type = 'script';
            }
            
            $crossorigin = ( 'font' === $type ) ? ' crossorigin' : '';
            
            printf(
                '<link rel="preload" href="%s" as="%s"%s>' . "\n",
                esc_url( $url ),
                esc_attr( $type ),
                $crossorigin
            );
        }
    }

    /**
     * Disable WooCommerce scripts on non-shop pages
     */
    public function disable_woocommerce_scripts() {
        if ( function_exists( 'is_woocommerce' ) && ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
            // Dequeue WooCommerce styles
            wp_dequeue_style( 'woocommerce-general' );
            wp_dequeue_style( 'woocommerce-layout' );
            wp_dequeue_style( 'woocommerce-smallscreen' );
            
            // Dequeue WooCommerce scripts
            wp_dequeue_script( 'wc-cart-fragments' );
            wp_dequeue_script( 'woocommerce' );
            wp_dequeue_script( 'wc-add-to-cart' );
        }
    }
}
