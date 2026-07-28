<?php
/**
 * Critical CSS (XL): hand-pasted, per-context critical CSS with full-sheet
 * async fallback.
 *
 * The admin pastes critical CSS — produced by a proper viewport-aware external
 * generator — into per-context slots (front page, single, shop, …). On each
 * front-end request the most specific matching slot is inlined in the head and
 * the original stylesheets are deferred. Generation is deliberately external;
 * this module only stores, resolves and applies.
 *
 * This is an XL-only feature: it is never present in the wp.org build.
 *
 * Arbitration: when this module is active for a request (a slot matched and the
 * feature is enabled), it owns CSS delivery for that page. Used CSS, Async CSS
 * and Combine CSS all stand down via self::is_active(), so only one strategy
 * ever rewrites a given page.
 *
 * @package MBR_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Critical_CSS {

    /**
     * @var MBRPE_Critical_CSS
     */
    private static $instance;

    /**
     * Whether resolution has run for this request.
     *
     * @var bool
     */
    private $resolved = false;

    /**
     * Resolved critical CSS for this request, or null.
     *
     * @var string|null
     */
    private $css = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $css = $this->css_opts();

        // Disabled: register nothing. is_active() then stays false, so every
        // other CSS feature behaves exactly as it would without XL.
        if ( empty( $css['critical_css'] ) ) {
            return;
        }

        // Priority 0: resolve before Used CSS (template_redirect, pri 1) and
        // before Async/Combine fire, so the arbitration signal is ready.
        add_action( 'template_redirect', array( $this, 'resolve' ), 0 );
        add_action( 'wp_head', array( $this, 'inline_critical' ), 2 );
        add_filter( 'style_loader_tag', array( $this, 'defer_styles' ), 10, 4 );
    }

    /**
     * The CSS sub-array of the unified options.
     *
     * @return array
     */
    private function css_opts() {
        $opts = get_option( 'mbrpe_options', array() );
        return isset( $opts['css'] ) && is_array( $opts['css'] ) ? $opts['css'] : array();
    }

    // --- Resolution --------------------------------------------------------

    /**
     * Resolve the critical CSS block for this request. Runs once.
     */
    public function resolve() {
        if ( $this->resolved ) {
            return;
        }
        $this->resolved = true;

        if ( ! $this->should_run() ) {
            return;
        }

        $css   = $this->css_opts();
        $slots = isset( $css['critical_slots'] ) && is_array( $css['critical_slots'] ) ? $css['critical_slots'] : array();
        if ( empty( $slots ) ) {
            return;
        }

        $contexts = self::contexts();
        $best     = null;
        $best_pri = PHP_INT_MAX;

        foreach ( $slots as $slot ) {
            if ( empty( $slot['enabled'] ) || empty( $slot['css'] ) || empty( $slot['context'] ) ) {
                continue;
            }
            $id = $slot['context'];
            if ( ! isset( $contexts[ $id ] ) ) {
                continue;
            }
            list( $priority, $matcher ) = $contexts[ $id ];
            if ( $priority < $best_pri && is_callable( $matcher ) && call_user_func( $matcher ) ) {
                $best     = $slot;
                $best_pri = $priority;
            }
        }

        $this->css = $best ? (string) $best['css'] : null;
    }

    /**
     * @return bool
     */
    public function has_css() {
        return ! empty( $this->css );
    }

    /**
     * Arbitration signal: is critical CSS owning this request?
     *
     * Used by Used CSS, Async CSS and Combine CSS to stand down. Safe to call
     * at any time; only true once a slot has resolved for the request.
     *
     * @return bool
     */
    public static function is_active() {
        return self::instance()->has_css();
    }

    /**
     * Guards: only run on real, public, non-preview front-end views.
     *
     * @return bool
     */
    private function should_run() {
        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || is_trackback() ) {
            return false;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return false;
        }
        if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
            return false;
        }
        if ( self::is_builder_context() ) {
            return false;
        }

        $css  = $this->css_opts();
        $skip = ! isset( $css['critical_skip_logged_in'] ) ? true : (bool) $css['critical_skip_logged_in'];
        if ( $skip && is_user_logged_in() ) {
            return false;
        }

        return (bool) apply_filters( 'mbrpe_critical_css_should_run', true );
    }

    /**
     * Registered contexts: id => array( int priority, callable matcher ).
     * Lower priority number = more specific = wins.
     *
     * @return array
     */
    public static function contexts() {
        $contexts = array(
            'page'        => array( 20, static function () { return is_page(); } ),
            'single_post' => array( 20, static function () { return is_singular( 'post' ); } ),
            'front_page'  => array( 30, static function () { return is_front_page(); } ),
            'blog_home'   => array( 35, static function () { return is_home(); } ),
            'search'      => array( 45, static function () { return is_search(); } ),
            'not_found'   => array( 45, static function () { return is_404(); } ),
            'archive'     => array( 50, static function () { return is_archive(); } ),
            'singular'    => array( 60, static function () { return is_singular(); } ),
            'global'      => array( 100, '__return_true' ),
        );

        if ( class_exists( 'WooCommerce' ) ) {
            $contexts['product']  = array( 12, static function () { return function_exists( 'is_product' ) && is_product(); } );
            $contexts['cart']     = array( 12, static function () { return function_exists( 'is_cart' ) && is_cart(); } );
            $contexts['checkout'] = array( 12, static function () { return function_exists( 'is_checkout' ) && is_checkout(); } );
            $contexts['shop']     = array( 18, static function () { return function_exists( 'is_shop' ) && is_shop(); } );
        }

        return apply_filters( 'mbrpe_critical_css_contexts', $contexts );
    }

    /**
     * Context labels for the admin dropdown (id => label).
     *
     * @return array
     */
    public static function context_labels() {
        $labels = array(
            'front_page'  => __( 'Front page', 'mbr-performance' ),
            'blog_home'   => __( 'Blog posts index', 'mbr-performance' ),
            'page'        => __( 'Pages', 'mbr-performance' ),
            'single_post' => __( 'Single posts', 'mbr-performance' ),
            'singular'    => __( 'Any singular (catch-all)', 'mbr-performance' ),
            'archive'     => __( 'Archives', 'mbr-performance' ),
            'search'      => __( 'Search results', 'mbr-performance' ),
            'not_found'   => __( '404 page', 'mbr-performance' ),
            'global'      => __( 'Global (fallback)', 'mbr-performance' ),
        );

        if ( class_exists( 'WooCommerce' ) ) {
            $labels['shop']     = __( 'Shop', 'mbr-performance' );
            $labels['product']  = __( 'Single product', 'mbr-performance' );
            $labels['cart']     = __( 'Cart', 'mbr-performance' );
            $labels['checkout'] = __( 'Checkout', 'mbr-performance' );
        }

        return apply_filters( 'mbrpe_critical_css_context_labels', $labels );
    }

    /**
     * Detect Elementor (and other builder) edit/preview contexts. A true result
     * is cached for the request; false is recomputed so a later, more reliable
     * signal can still catch it.
     *
     * @return bool
     */
    public static function is_builder_context() {
        static $is_true = false;
        if ( $is_true ) {
            return true;
        }

        $is = false;

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['elementor-preview'] ) ) {
            $is = true;
        } elseif ( isset( $_REQUEST['action'] ) && 0 === strpos( sanitize_key( wp_unslash( $_REQUEST['action'] ) ), 'elementor' ) ) {
            $is = true;
        } elseif ( isset( $_GET['preview'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['preview'] ) ) ) {
            $is = true;
        } elseif ( isset( $_GET['fl_builder'] ) || ( ! empty( $_GET['bricks'] ) && 'run' === sanitize_key( wp_unslash( $_GET['bricks'] ) ) ) ) {
            $is = true;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( ! $is && class_exists( '\Elementor\Plugin' ) ) {
            $elementor = \Elementor\Plugin::$instance;
            if ( $elementor ) {
                if ( isset( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
                    $is = true;
                } elseif ( isset( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
                    $is = true;
                }
            }
        }

        $is = (bool) apply_filters( 'mbrpe_critical_css_is_builder_context', $is );
        if ( $is ) {
            $is_true = true;
        }
        return $is;
    }

    // --- Output ------------------------------------------------------------

    /**
     * Inline the resolved critical CSS early in <head>.
     */
    public function inline_critical() {
        if ( ! $this->has_css() || self::is_builder_context() ) {
            return;
        }
        $css = self::sanitise_css( $this->css );
        echo '<style id="mbrpe-critical-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitised CSS, printed inside a style element.
    }

    /**
     * Defer the original stylesheets when critical CSS is inlined this request.
     *
     * @param string $tag
     * @param string $handle
     * @param string $href
     * @param string $media
     * @return string
     */
    public function defer_styles( $tag, $handle, $href, $media ) {
        if ( ! $this->has_css() ) {
            return $tag; // interlock: no critical CSS => leave sheets render-blocking.
        }
        if ( self::is_builder_context() ) {
            return $tag;
        }
        if ( 'print' === $media ) {
            return $tag;
        }
        if ( in_array( $handle, array( 'admin-bar', 'dashicons' ), true ) ) {
            return $tag;
        }
        if ( $this->is_excluded( $handle, $href ) ) {
            return $tag;
        }

        $media  = $media ? $media : 'all';
        $css    = $this->css_opts();
        $method = isset( $css['critical_defer_method'] ) ? $css['critical_defer_method'] : 'preload';

        if ( 'media' === $method ) {
            $deferred = sprintf(
                '<link rel="stylesheet" id="%1$s-css" href="%2$s" media="print" onload="this.media=\'%3$s\';this.onload=null">',
                esc_attr( $handle ),
                esc_url( $href ),
                esc_attr( $media )
            );
        } else {
            $deferred = sprintf(
                '<link rel="preload" as="style" id="%1$s-css" href="%2$s" media="%3$s" onload="this.onload=null;this.rel=\'stylesheet\'">',
                esc_attr( $handle ),
                esc_url( $href ),
                esc_attr( $media )
            );
        }

        $deferred .= sprintf(
            '<noscript><link rel="stylesheet" href="%1$s" media="%2$s"></noscript>',
            esc_url( $href ),
            esc_attr( $media )
        );

        return $deferred;
    }

    /**
     * @param string $handle
     * @param string $href
     * @return bool
     */
    private function is_excluded( $handle, $href ) {
        $css = $this->css_opts();
        $raw = isset( $css['critical_exclude'] ) ? (string) $css['critical_exclude'] : '';
        $list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) );

        $excluded = false;
        if ( in_array( $handle, $list, true ) ) {
            $excluded = true;
        } else {
            foreach ( $list as $needle ) {
                if ( '' !== $needle && false !== strpos( $href, $needle ) ) {
                    $excluded = true;
                    break;
                }
            }
        }
        return (bool) apply_filters( 'mbrpe_critical_css_exclude_style', $excluded, $handle, $href );
    }

    // --- Admin: sanitise + render ------------------------------------------

    /**
     * Sanitise pasted critical CSS. Not HTML, so not wp_kses'd; the one real
     * escape vector (breaking out of <style>) and control chars are stripped.
     *
     * @param string $raw
     * @return string
     */
    public static function sanitise_css( $raw ) {
        $css = (string) $raw;
        $css = str_ireplace( array( '</style', '<script', '</script' ), '', $css );
        $css = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $css );
        return trim( $css );
    }

    /**
     * Sanitise the critical-CSS portion of the posted CSS options. Returns only
     * the critical_* keys, to be merged into the sanitised CSS options.
     *
     * @param array $options Raw posted css options.
     * @return array
     */
    public static function sanitize( $options ) {
        $out = array();

        $out['critical_css']             = ! empty( $options['critical_css'] );
        $out['critical_skip_logged_in']  = ! empty( $options['critical_skip_logged_in'] );

        $method = isset( $options['critical_defer_method'] ) ? sanitize_key( $options['critical_defer_method'] ) : 'preload';
        $out['critical_defer_method']    = in_array( $method, array( 'preload', 'media' ), true ) ? $method : 'preload';

        $out['critical_exclude']         = isset( $options['critical_exclude'] ) ? sanitize_textarea_field( $options['critical_exclude'] ) : '';

        $slots_out = array();
        if ( isset( $options['critical_slots'] ) && is_array( $options['critical_slots'] ) ) {
            $contexts = self::contexts();
            foreach ( $options['critical_slots'] as $row ) {
                if ( ! is_array( $row ) ) {
                    continue;
                }
                $context = isset( $row['context'] ) ? sanitize_key( $row['context'] ) : '';
                $css     = isset( $row['css'] ) ? self::sanitise_css( $row['css'] ) : '';
                if ( ! isset( $contexts[ $context ] ) || '' === $css ) {
                    continue; // unknown context or empty slot => drop.
                }
                $slots_out[] = array(
                    'label'   => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '',
                    'context' => $context,
                    'enabled' => ! empty( $row['enabled'] ),
                    'css'     => $css,
                );
            }
        }
        $out['critical_slots'] = $slots_out;

        return $out;
    }

    /**
     * Render the Critical CSS section for the CSS settings tab.
     *
     * @param array $css_options Current css options.
     */
    public static function render_settings( $css_options ) {
        $enabled      = ! empty( $css_options['critical_css'] );
        $defer_method = isset( $css_options['critical_defer_method'] ) ? $css_options['critical_defer_method'] : 'preload';
        $skip_logged  = ! isset( $css_options['critical_skip_logged_in'] ) ? true : (bool) $css_options['critical_skip_logged_in'];
        $exclude      = isset( $css_options['critical_exclude'] ) ? $css_options['critical_exclude'] : '';
        $slots        = isset( $css_options['critical_slots'] ) && is_array( $css_options['critical_slots'] ) ? $css_options['critical_slots'] : array();
        $contexts     = self::context_labels();

        if ( empty( $slots ) ) {
            $slots = array(
                array(
                    'label'   => 'Global',
                    'context' => 'global',
                    'enabled' => true,
                    'css'     => '',
                ),
            );
        }
        ?>
        <div class="mbr-performance-section">
            <h2><?php esc_html_e( 'Critical CSS (XL)', 'mbr-performance' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Paste critical CSS generated by a viewport-aware tool (Penthouse, Critical, corewebvitals.io, etc.) into the matching slot. The most specific matching slot is inlined and the original stylesheets are deferred. When a slot matches a page, critical CSS owns that page and Used CSS / Async / Combine stand down on it.', 'mbr-performance' ); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="critical_css"><?php esc_html_e( 'Enable Critical CSS', 'mbr-performance' ); ?></label></th>
                    <td>
                        <input type="checkbox" name="mbrpe_options[css][critical_css]" id="critical_css" value="1" <?php checked( $enabled ); ?>>
                        <p class="description"><?php esc_html_e( 'Inline critical CSS and defer the original stylesheets on pages that have a matching slot.', 'mbr-performance' ); ?></p>
                    </td>
                </tr>
                <tr class="mbr-performance-child-row">
                    <th scope="row"><?php esc_html_e( 'Defer method', 'mbr-performance' ); ?></th>
                    <td>
                        <label><input type="radio" name="mbrpe_options[css][critical_defer_method]" value="preload" <?php checked( $defer_method, 'preload' ); ?>> <?php esc_html_e( 'Preload swap (recommended)', 'mbr-performance' ); ?></label><br>
                        <label><input type="radio" name="mbrpe_options[css][critical_defer_method]" value="media" <?php checked( $defer_method, 'media' ); ?>> <?php esc_html_e( 'media="print" swap (more compatible fallback)', 'mbr-performance' ); ?></label>
                    </td>
                </tr>
                <tr class="mbr-performance-child-row">
                    <th scope="row"><label for="critical_skip_logged_in"><?php esc_html_e( 'Skip logged-in users', 'mbr-performance' ); ?></label></th>
                    <td>
                        <input type="checkbox" name="mbrpe_options[css][critical_skip_logged_in]" id="critical_skip_logged_in" value="1" <?php checked( $skip_logged ); ?>>
                        <p class="description"><?php esc_html_e( 'Recommended. Avoids deferring stylesheets inside page-builder editors and the admin bar.', 'mbr-performance' ); ?></p>
                    </td>
                </tr>
                <tr class="mbr-performance-child-row">
                    <th scope="row"><label for="critical_exclude"><?php esc_html_e( 'Exclude from deferral', 'mbr-performance' ); ?></label></th>
                    <td>
                        <textarea name="mbrpe_options[css][critical_exclude]" id="critical_exclude" rows="3" class="large-text code" placeholder="<?php esc_attr_e( 'One handle or URL fragment per line', 'mbr-performance' ); ?>"><?php echo esc_textarea( $exclude ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Stylesheets matching these handles or URL fragments stay render-blocking (e.g. icon fonts the critical CSS assumes are present).', 'mbr-performance' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Critical CSS slots', 'mbr-performance' ); ?></h3>
            <p class="description"><?php esc_html_e( 'The most specific matching slot wins; Global is the fallback. Empty slots are discarded on save.', 'mbr-performance' ); ?></p>

            <table class="widefat mbrpe-critical-slots" id="mbrpe-critical-slots">
                <thead>
                    <tr>
                        <th style="width:240px;"><?php esc_html_e( 'Slot', 'mbr-performance' ); ?></th>
                        <th><?php esc_html_e( 'Critical CSS', 'mbr-performance' ); ?></th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    foreach ( $slots as $slot ) {
                        self::render_slot_row( (string) $i, $slot, $contexts );
                        $i++;
                    }
                    ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="mbrpe-critical-add"><?php esc_html_e( '+ Add slot', 'mbr-performance' ); ?></button></p>

            <template id="mbrpe-critical-template">
                <?php self::render_slot_row( '__INDEX__', array( 'label' => '', 'context' => 'page', 'enabled' => true, 'css' => '' ), $contexts ); ?>
            </template>

            <style>
                .mbrpe-critical-slots td { vertical-align: top; }
                .mbrpe-critical-slots textarea { width: 100%; min-height: 120px; font-family: monospace; }
            </style>
            <script>
            ( function () {
                var table = document.getElementById( 'mbrpe-critical-slots' );
                var add   = document.getElementById( 'mbrpe-critical-add' );
                var tpl   = document.getElementById( 'mbrpe-critical-template' );
                if ( ! table || ! add || ! tpl ) { return; }
                var tbody = table.querySelector( 'tbody' );
                var n = tbody.querySelectorAll( 'tr.mbrpe-critical-slot' ).length;
                add.addEventListener( 'click', function ( e ) {
                    e.preventDefault();
                    var holder = document.createElement( 'tbody' );
                    holder.innerHTML = tpl.innerHTML.replace( /__INDEX__/g, 'new_' + n ).trim();
                    n++;
                    var row = holder.querySelector( 'tr.mbrpe-critical-slot' );
                    if ( row ) { tbody.appendChild( row ); }
                } );
                tbody.addEventListener( 'click', function ( e ) {
                    var btn = e.target.closest ? e.target.closest( '.mbrpe-critical-remove' ) : null;
                    if ( ! btn ) { return; }
                    e.preventDefault();
                    var row = btn.closest( 'tr.mbrpe-critical-slot' );
                    if ( row ) { row.parentNode.removeChild( row ); }
                } );
            }() );
            </script>
        </div>
        <?php
    }

    /**
     * Render a single slot row.
     *
     * @param string $index
     * @param array  $slot
     * @param array  $contexts
     */
    private static function render_slot_row( $index, $slot, $contexts ) {
        $label   = isset( $slot['label'] ) ? $slot['label'] : '';
        $context = isset( $slot['context'] ) ? $slot['context'] : 'global';
        $enabled = ! empty( $slot['enabled'] );
        $css     = isset( $slot['css'] ) ? $slot['css'] : '';
        $base    = 'mbrpe_options[css][critical_slots][' . $index . ']';
        ?>
        <tr class="mbrpe-critical-slot">
            <td>
                <p><input type="text" name="<?php echo esc_attr( $base ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Label', 'mbr-performance' ); ?>"></p>
                <p>
                    <select name="<?php echo esc_attr( $base ); ?>[context]">
                        <?php foreach ( $contexts as $key => $name ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $context, $key ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p><label><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[enabled]" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'Enabled', 'mbr-performance' ); ?></label></p>
            </td>
            <td>
                <textarea name="<?php echo esc_attr( $base ); ?>[css]" rows="5" placeholder="<?php esc_attr_e( 'Paste the generated critical CSS here', 'mbr-performance' ); ?>"><?php echo esc_textarea( $css ); ?></textarea>
            </td>
            <td>
                <button type="button" class="button-link delete mbrpe-critical-remove"><?php esc_html_e( 'Remove', 'mbr-performance' ); ?></button>
            </td>
        </tr>
        <?php
    }
}
