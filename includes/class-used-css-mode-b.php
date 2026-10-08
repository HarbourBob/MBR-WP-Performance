<?php
/**
 * Used CSS Mode B: per-template critical CSS that genuinely removes the
 * unused stylesheets.
 *
 * Mode A (class-used-css.php) caches per URL and keeps the original sheets,
 * loading them asynchronously behind the inlined critical CSS. That is the safe
 * variant — a rule the static pass misses still arrives a beat later — but it
 * means the bytes are still downloaded, and on a large site the per-URL cache
 * grows one entry per page.
 *
 * Mode B is the aggressive counterpart, and it differs in three ways:
 *
 *   1. It caches per *template* (front page, single post, page, archive, shop,
 *      product, …) rather than per URL, so a site with ten thousand posts has
 *      one cache entry for "single post", not ten thousand.
 *   2. It *removes* the analysed stylesheets from the delivered HTML instead of
 *      deferring them. The bytes genuinely stop being fetched.
 *   3. Because a wrong drop is therefore permanent, it learns each template
 *      from several URLs and keeps the union of what they used, rather than
 *      trusting a single page.
 *
 * Removal is the whole point and also the whole risk, so the module is
 * deliberately conservative about *what* it will remove. A stylesheet is only
 * ever dropped from a page if that exact sheet was analysed during learning.
 * Anything else — a sheet a plugin enqueues on only some pages of the template,
 * a sheet carrying an @import, a print or narrow-media sheet, an excluded
 * sheet, anything external — is left exactly as WordPress emitted it. The
 * failure mode of that rule is a page that keeps a stylesheet it did not need,
 * which costs a request; the failure mode of the opposite rule is an unstyled
 * page.
 *
 * Arbitration: Critical CSS (hand-pasted) still owns any page with a matching
 * slot. Below that, Mode B owns CSS delivery, and Mode A / Async CSS / Combine
 * CSS all stand down — Mode A does not even register when Mode B is enabled.
 *
 * Off by default. Staging-first. See the roadmap item M4/4a.
 *
 * @package MBR_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Used_CSS_Mode_B {

	/**
	 * Cache directory under /uploads. Deliberately distinct from Mode A's, so
	 * the two caches can never be mistaken for one another and either can be
	 * cleared without touching the other.
	 */
	const DIRNAME = 'mbr-performance-usedcss-b';

	/**
	 * Sidecar format version. Bumped if the JSON shape ever changes, so an old
	 * sidecar is discarded rather than misread.
	 */
	const META_VERSION = 2;

	/**
	 * Default number of distinct URLs sampled per template before the template
	 * is considered learned.
	 *
	 * Three is the useful minimum: one page tells you what that page used, two
	 * start to separate template chrome from page content, three catches most
	 * of the "this listing happened to have no pagination" class of mistake.
	 */
	const DEFAULT_SAMPLES = 3;

	/**
	 * Query argument that disables Mode B for a single request, so the original
	 * stylesheets can be compared against the optimised output without turning
	 * the feature off site-wide.
	 */
	const BYPASS_ARG = 'mbrpe_modeb';

	/**
	 * @var MBRPE_Used_CSS_Mode_B
	 */
	private static $instance;

	/**
	 * Cached CSS-tab options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Template key resolved for this request ('' before template_redirect).
	 *
	 * @var string
	 */
	private $template = '';

	/**
	 * Resolved cache paths for this request.
	 *
	 * @var string
	 */
	private $cache_file = '';
	private $meta_file  = '';

	/**
	 * Sidecar metadata for this request's template.
	 *
	 * @var array
	 */
	private $meta = array();

	/**
	 * Whether this request is buffering in order to learn.
	 *
	 * @var bool
	 */
	private $learning = false;

	/**
	 * Absolute URL of the request, kept for the post-generation cache purge.
	 *
	 * @var string
	 */
	private $current_url = '';

	/**
	 * Rendered HTML captured by capture_buffer() on a learning request.
	 *
	 * @since 2.1.2
	 * @var string
	 */
	private $captured = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$opts          = get_option( 'mbrpe_options', array() );
		$this->options = isset( $opts['css'] ) && is_array( $opts['css'] ) ? $opts['css'] : array();

		if ( ! self::is_enabled() ) {
			return;
		}

		// Invalidation. Mode B is deliberately blunter here than Mode A: Mode A
		// purges only the edited post's own URL, because a stale Mode A page
		// merely inlines slightly wrong critical CSS and still loads the full
		// sheets behind it. A stale Mode B template has removed stylesheets, so
		// the same mistake renders an unstyled page. Editing content can add
		// markup — a new block, a new widget, a shortcode — that needs CSS the
		// template has never seen, so every content save drops the whole cache
		// and lets the templates relearn. Regeneration is automatic and there
		// are only a handful of templates, so the cost is small and the failure
		// it prevents is not.
		add_action( 'save_post', array( __CLASS__, 'purge_all_on_change' ), 10, 1 );
		add_action( 'switch_theme', array( __CLASS__, 'purge_all' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'purge_all' ) );
		add_action( 'activated_plugin', array( __CLASS__, 'purge_all' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'purge_all' ) );
		add_action( 'customize_save_after', array( __CLASS__, 'purge_all' ) );

		add_action( 'template_redirect', array( $this, 'on_template_redirect' ), 1 );
	}

	/**
	 * Is Mode B switched on?
	 *
	 * Read straight from options rather than from instance state, because Mode
	 * A and the CSS optimisation layer both need to ask this while deciding
	 * whether to register their own hooks — which happens before, or instead
	 * of, this class being constructed.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$opts = get_option( 'mbrpe_options', array() );
		return ! empty( $opts['css']['modeb_enabled'] );
	}

	// --- Options -----------------------------------------------------------

	/**
	 * How many distinct URLs to sample per template.
	 *
	 * Single-URL templates (see single_url_templates()) always return 1: they
	 * can never produce a second distinct sample, so asking for more would
	 * leave them reported as "still learning" forever.
	 *
	 * @return int
	 */
	private function sample_target() {
		if ( '' !== (string) $this->template && self::is_single_url_template( $this->template ) ) {
			return 1;
		}
		$n = isset( $this->options['modeb_samples'] ) ? (int) $this->options['modeb_samples'] : self::DEFAULT_SAMPLES;
		return max( 1, min( 10, $n ) );
	}

	/**
	 * Templates that only ever resolve to one URL, and so can only ever yield
	 * one sample however much traffic they get.
	 *
	 * Samples are keyed on the request path with the query string stripped, so
	 * a static front page is only ever "/", and WooCommerce's cart and
	 * checkout are each a single page. A front page that lists latest posts is
	 * deliberately NOT included: /page/2/, /page/3/ and so on are genuine
	 * distinct URLs with their own pagination markup, so the normal target
	 * still applies there.
	 *
	 * @since 2.1.4
	 * @return string[]
	 */
	public static function single_url_templates() {
		$ids = array( 'cart', 'checkout' );
		if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) > 0 ) {
			$ids[] = 'front_page';
		}
		return (array) apply_filters( 'mbrpe_modeb_single_url_templates', $ids );
	}

	/**
	 * Whether a template id is single-URL.
	 *
	 * @since 2.1.4
	 * @param string $template Template id.
	 * @return bool
	 */
	public static function is_single_url_template( $template ) {
		return in_array( (string) $template, self::single_url_templates(), true );
	}

	/**
	 * Split a textarea option into trimmed, non-empty lines.
	 *
	 * @param string $key Option key within the css section.
	 * @return string[]
	 */
	private function lines( $key ) {
		$raw = isset( $this->options[ $key ] ) ? (string) $this->options[ $key ] : '';
		$out = array();
		foreach ( preg_split( '/[\r\n,]+/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}
		return $out;
	}

	/**
	 * Stylesheet URL fragments that must never be removed.
	 *
	 * Deliberately merges Mode B's own "keep these sheets" list with the
	 * existing shared exclusion list, so a user who already excluded a
	 * stylesheet from optimisation does not have to say so twice.
	 *
	 * @return string[]
	 */
	private function sheet_exclusions() {
		return array_merge( $this->lines( 'exclude_optimization' ), $this->lines( 'modeb_keep_sheets' ) );
	}

	/**
	 * The user's manual selector safelist — the guard rail for Mode B.
	 *
	 * These are added on top of the engine's built-in safelist of commonly
	 * JS-toggled classes. This is where a user puts the selectors that only
	 * ever appear after an interaction: a cookie banner injected on scroll, a
	 * cart drawer, a plugin's modal.
	 *
	 * @return string[]
	 */
	private function safelist() {
		return $this->lines( 'modeb_safelist' );
	}

	// --- Templates ---------------------------------------------------------

	/**
	 * The template taxonomy, id => array( int priority, callable matcher ).
	 * Lower priority number = more specific = wins.
	 *
	 * Deliberately mirrors MBRPE_Critical_CSS::contexts() so the two features
	 * describe a site the same way and a user reading the plugin does not have
	 * to hold two vocabularies. It is defined here rather than borrowed so
	 * that Mode B has no hard dependency on the Critical CSS module being
	 * present, and so the two can diverge if they ever need to.
	 *
	 * Search and 404 are absent on purpose: both are excluded from Mode B
	 * entirely (see should_skip), because their markup varies with the query
	 * rather than the template.
	 *
	 * @return array
	 */
	public static function templates() {
		$templates = array(
			// front_page and blog_home must outrank 'page': a static front page
			// (and the posts page on some setups) also satisfies is_page(), so
			// with page at 20 they were swallowed into "Pages" and never learned
			// as templates of their own.
			'front_page'  => array( 10, static function () { return is_front_page(); } ),
			'blog_home'   => array( 15, static function () { return is_home(); } ),
			'page'        => array( 20, static function () { return is_page(); } ),
			'single_post' => array( 20, static function () { return is_singular( 'post' ); } ),
			'archive'     => array( 50, static function () { return is_archive(); } ),
			'singular'    => array( 60, static function () { return is_singular(); } ),
			'global'      => array( 100, '__return_true' ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$templates['product']  = array( 12, static function () { return function_exists( 'is_product' ) && is_product(); } );
			$templates['cart']     = array( 12, static function () { return function_exists( 'is_cart' ) && is_cart(); } );
			$templates['checkout'] = array( 12, static function () { return function_exists( 'is_checkout' ) && is_checkout(); } );
			$templates['shop']     = array( 18, static function () { return function_exists( 'is_shop' ) && is_shop(); } );
		}

		return apply_filters( 'mbrpe_modeb_templates', $templates );
	}

	/**
	 * Human labels for the admin table.
	 *
	 * @return array
	 */
	public static function template_labels() {
		$labels = array(
			'front_page'  => __( 'Front page', 'mbr-performance' ),
			'blog_home'   => __( 'Blog posts index', 'mbr-performance' ),
			'page'        => __( 'Pages', 'mbr-performance' ),
			'single_post' => __( 'Single posts', 'mbr-performance' ),
			'singular'    => __( 'Other singular content', 'mbr-performance' ),
			'archive'     => __( 'Archives', 'mbr-performance' ),
			'shop'        => __( 'Shop', 'mbr-performance' ),
			'product'     => __( 'Single product', 'mbr-performance' ),
			'cart'        => __( 'Cart', 'mbr-performance' ),
			'checkout'    => __( 'Checkout', 'mbr-performance' ),
			'global'      => __( 'Everything else', 'mbr-performance' ),
		);
		return apply_filters( 'mbrpe_modeb_template_labels', $labels );
	}

	/**
	 * Resolve the current request to its most specific template id.
	 *
	 * @return string Template id, or '' if nothing matched (should not happen —
	 *                'global' is a catch-all — but a filter could remove it).
	 */
	private function resolve_template() {
		$best     = '';
		$best_pri = PHP_INT_MAX;

		foreach ( self::templates() as $id => $spec ) {
			if ( ! is_array( $spec ) || count( $spec ) < 2 ) {
				continue;
			}
			list( $priority, $matcher ) = $spec;
			if ( $priority < $best_pri && is_callable( $matcher ) && call_user_func( $matcher ) ) {
				$best     = (string) $id;
				$best_pri = (int) $priority;
			}
		}

		return $best;
	}

	// --- Request handling --------------------------------------------------

	/**
	 * Contexts where Mode B must neither serve nor learn.
	 *
	 * Mirrors Mode A's guards. Search and 404 are excluded because their output
	 * is driven by the query rather than by a template, so one sample would not
	 * represent the next request.
	 *
	 * @return bool
	 */
	private function should_skip() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
			return true;
		}
		// Logged-in users get the admin bar and other per-user CSS, which would
		// pollute a shared template cache — and, worse, a logged-out visitor
		// would then be served CSS learned with the admin bar present.
		if ( is_user_logged_in() ) {
			return true;
		}
		if ( is_404() || is_search() || is_feed() || is_preview() || is_embed() || is_robots() || is_trackback() ) {
			return true;
		}
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return true;
		}
		// Explicit per-request escape hatch, so the optimised page and the
		// original can be compared side by side without switching the feature
		// off site-wide.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ self::BYPASS_ARG ] ) && 'off' === sanitize_key( wp_unslash( $_GET[ self::BYPASS_ARG ] ) ) ) {
			return true;
		}

		// Query arguments generally mean the markup is not the template's
		// default — a filtered archive, a paged listing, a search refinement —
		// so those requests are left alone. Campaign and click-ID parameters
		// are the exception: they change nothing about what WordPress renders,
		// and skipping them would quietly exclude every ad and newsletter
		// landing from the optimisation, which is usually the traffic that
		// matters most.
		if ( $this->has_meaningful_query() ) {
			return true;
		}

		// Page builders / front-end editors.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) {
			return true;
		}
		if ( isset( $_GET['fl_builder'] ) || ( ! empty( $_GET['bricks'] ) && 'run' === $_GET['bricks'] ) ) {
			return true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return true;
		}
		if ( defined( 'SHOW_CT_BUILDER' ) ) {
			return true;
		}

		return ! (bool) apply_filters( 'mbrpe_modeb_should_run', true );
	}

	/**
	 * Does this request carry any query argument that could change the markup?
	 *
	 * @return bool
	 */
	private function has_meaningful_query() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET ) || ! is_array( $_GET ) ) {
			return false;
		}

		$inert = array(
			'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
			'utm_id', 'utm_source_platform', 'gclid', 'gbraid', 'wbraid', 'dclid',
			'fbclid', 'msclkid', 'ttclid', 'twclid', 'igshid', 'mc_cid', 'mc_eid',
			'_ga', '_gl', 'ref', 'source', 'yclid', 'li_fat_id', 'epik',
			self::BYPASS_ARG,
			// The Performance Doctor busts the page cache with a timestamp so
			// it measures a fresh render. Treating that as a "real" query
			// argument would make Mode B stand down for every scan, so the
			// Doctor would report the unoptimised page and go on recommending
			// fixes for stylesheets that visitors are no longer being sent.
			'mbrpe_doctor',
		);

		/**
		 * Query arguments Mode B treats as not affecting rendered markup.
		 *
		 * Adding an argument here tells Mode B that a request carrying it can
		 * be served the template's learned CSS. Only add arguments that your
		 * theme and plugins genuinely ignore.
		 *
		 * @param string[] $inert Argument names.
		 */
		$inert = (array) apply_filters( 'mbrpe_modeb_inert_query_args', $inert );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$remaining = array_diff_key( $_GET, array_flip( $inert ) );

		return ! empty( $remaining );
	}

	/**
	 * Decide, once per request, whether to serve a learned template or spend
	 * this request learning one.
	 */
	public function on_template_redirect() {
		if ( $this->should_skip() ) {
			return;
		}

		// Critical CSS (hand-pasted) owns any page with a matching slot.
		if ( class_exists( 'MBRPE_Critical_CSS' ) && MBRPE_Critical_CSS::is_active() ) {
			return;
		}

		$this->template = $this->resolve_template();
		if ( '' === $this->template ) {
			return;
		}

		$dir = self::get_dir();
		if ( ! is_array( $dir ) ) {
			return;
		}

		$key              = self::cache_key( $this->template );
		$this->cache_file = $dir['path'] . '/' . $key . '.css';
		$this->meta_file  = $dir['path'] . '/' . $key . '.json';
		$this->meta       = self::read_meta( $this->meta_file );

		$have_css = ! empty( $this->meta['sheets'] ) && is_array( $this->meta['sheets'] );
		$url_key  = self::url_key( $this->request_path() );
		$sampled  = isset( $this->meta['urls'][ $url_key ] );
		$learned  = (int) ( isset( $this->meta['samples'] ) ? $this->meta['samples'] : 0 ) >= $this->sample_target();

		if ( $have_css && ( $sampled || $learned ) ) {
			ob_start( array( $this, 'serve_buffer' ) );
			return;
		}

		// LEARN: serve this request completely untouched, then analyse it once
		// the visitor has their page. Nothing is removed on a learning request,
		// so a visitor never sees a half-optimised page.
		$this->learning    = true;
		$this->current_url = $this->current_url();

		// A learning render is served untouched. Storing it in the page cache
		// would freeze the unoptimised page and stop the template learning,
		// because cache hits never reach PHP.
		if ( class_exists( 'MBRPE_Page_Cache' ) ) {
			MBRPE_Page_Cache::defer( 'used-css-mode-b-learning' );
		}

		// Capture via callback, then analyse after WordPress has flushed every
		// buffer — see MBRPE_Used_CSS::on_template_redirect() for why
		// ob_get_clean() at shutdown analysed the wrong buffer.
		ob_start( array( $this, 'capture_buffer' ) );
		add_action( 'shutdown', array( $this, 'learn_after_response' ), PHP_INT_MAX );
	}

	/**
	 * Output callback for a learning request: record the HTML, pass it on
	 * untouched.
	 *
	 * @since 2.1.2
	 * @param string $buffer Chunk of output.
	 * @return string Unchanged.
	 */
	public function capture_buffer( $buffer ) {
		if ( $this->learning ) {
			$this->captured .= $buffer;
		}
		return $buffer;
	}

	/**
	 * Normalised path of the current request, used as the per-URL sample key.
	 *
	 * @return string
	 */
	private function request_path() {
		$path = '/';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$path = wp_parse_url( $uri, PHP_URL_PATH );
			$path = $path ? $path : '/';
		}
		$path = strtolower( rtrim( $path, '/' ) );
		return '' === $path ? '/' : $path;
	}

	/**
	 * Absolute URL of the current request.
	 *
	 * @return string
	 */
	private function current_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url( '/' );
		}
		return home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	}

	// --- Serving -----------------------------------------------------------

	/**
	 * Rewrite the finished page: inline the template's CSS in place of the
	 * first stylesheet it replaces, and delete the rest of the sheets it covers.
	 *
	 * Substituting the *first* removed link, rather than printing at the top of
	 * the head, is what keeps the cascade honest. A theme that prints an inline
	 * <style> between two stylesheets expects that inline block to lose to the
	 * sheet below it; printing our inlined CSS at wp_head priority 2 would move
	 * every one of those rules above the inline block and silently invert the
	 * result. Dropping it exactly where the first sheet stood preserves the
	 * original order.
	 *
	 * @param string $html Finished page HTML.
	 * @return string
	 */
	public function serve_buffer( $html ) {
		if ( '' === $html || '' === $this->cache_file ) {
			return $html;
		}

		$covered = isset( $this->meta['sheets'] ) && is_array( $this->meta['sheets'] ) ? $this->meta['sheets'] : array();
		if ( empty( $covered ) ) {
			return $html; // Learned nothing removable; leave the page alone.
		}

		$dir = dirname( $this->cache_file );

		// Protect <noscript> blocks so a fallback <link> inside one — possibly
		// left by another async layer — is never treated as a live stylesheet.
		$shelf = array();
		$html  = preg_replace_callback(
			'#<noscript\b[^>]*>.*?</noscript>#is',
			function ( $m ) use ( &$shelf ) {
				$key           = '<!--mbrpe-b-ns-' . count( $shelf ) . '-->';
				$shelf[ $key ] = $m[0];
				return $key;
			},
			$html
		);

		// Each covered sheet is replaced by its own used CSS, exactly where its
		// link stood. Two rules make this safe:
		//
		//   1. Position — every sheet's rules stay at their original place in
		//      the cascade, so an un-covered sheet between two covered ones
		//      keeps winning and losing exactly as it did before.
		//   2. Presence — a link is only removed if that sheet's used CSS file
		//      exists. If it is missing for any reason (a purge mid-request, a
		//      failed write, disk full) the link is left alone. Removal without
		//      its replacement is the one thing that produces an unstyled page.
		$loaded = array();
		$result = preg_replace_callback(
			'#<link\b[^>]*\brel=(["\'])stylesheet\1[^>]*>#i',
			function ( $m ) use ( $covered, $dir, &$loaded ) {
				$tag = $m[0];

				if ( ! preg_match( '/\bhref=(["\'])(.*?)\1/i', $tag, $h ) ) {
					return $tag;
				}
				$href = self::normalise_href( trim( html_entity_decode( $h[2] ) ) );
				if ( '' === $href || ! isset( $covered[ $href ]['file'] ) ) {
					// Not a sheet this template learned — leave it exactly as
					// WordPress emitted it, still render-blocking.
					return $tag;
				}

				if ( isset( $loaded[ $href ] ) ) {
					return ''; // Same sheet linked twice; already inlined once.
				}

				$file = $dir . '/' . basename( (string) $covered[ $href ]['file'] );
				if ( ! is_file( $file ) || ! is_readable( $file ) ) {
					return $tag;
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the plugin's own cached CSS from wp-uploads.
				$css = file_get_contents( $file );
				if ( false === $css ) {
					return $tag;
				}
				$loaded[ $href ] = true;

				if ( '' === trim( $css ) ) {
					return ''; // Analysed, and nothing in it is used by this template.
				}
				return "\n<style class=\"mbrpe-used-css-b\" data-src=\"" . esc_attr( basename( $href ) ) . '">' . $css . "</style>\n";
			},
			$html
		);

		if ( null === $result ) {
			$result = $html;
		}

		if ( ! empty( $shelf ) ) {
			$result = strtr( $result, $shelf );
		}

		return $result;
	}

	// --- Learning ----------------------------------------------------------

	/**
	 * After the page has shipped, analyse it and fold what it used into the
	 * template's cache.
	 */
	public function learn_after_response() {
		if ( ! $this->learning ) {
			return;
		}
		// WordPress normally flushes every buffer at shutdown priority 1. If
		// something has unhooked that, flush here so the capture is complete
		// (ob_end_flush() returns false on a non-removable buffer, which ends
		// the loop).
		while ( ob_get_level() > 0 && @ob_end_flush() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			continue;
		}
		$this->learning = false;

		$html           = $this->captured;
		$this->captured = '';

		// The page has already been sent by the buffer flush. Release the
		// connection (PHP-FPM) before doing the heavy parsing work.
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}

		if ( '' === $html || '' === $this->cache_file ) {
			return;
		}

		// A lock stops two simultaneous first-hits both analysing the same
		// template and racing each other's sidecar write.
		$lock = $this->cache_file . '.lock';
		if ( file_exists( $lock ) && ( time() - (int) filemtime( $lock ) ) < 180 ) {
			return;
		}
		MBRPE_CSS_Optimizations::write_file( $lock, (string) time() );

		try {
			$this->learn_from_html( $html );
		} catch ( \Throwable $e ) {
			// Never let analysis affect the already-sent page.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'MBRPE Used CSS Mode B learning failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		if ( file_exists( $lock ) ) {
			wp_delete_file( $lock );
		}
	}

	/**
	 * Analyse one rendered page and merge its findings into the template cache.
	 *
	 * @param string $html Rendered page HTML.
	 */
	private function learn_from_html( $html ) {
		require_once MBRPE_PLUGIN_DIR . 'includes/vendor/autoload.php';
		require_once MBRPE_PLUGIN_DIR . 'includes/class-used-css-engine.php';
		require_once MBRPE_PLUGIN_DIR . 'includes/class-used-css-engine-b.php';

		$on_page = $this->analysable_sheets( $html );
		if ( empty( $on_page ) ) {
			return;
		}

		// Re-read the sidecar now rather than trusting the copy taken at
		// template_redirect: a concurrent request may have written a newer one
		// between then and now, and losing its samples would quietly undo work.
		$meta     = self::read_meta( $this->meta_file );
		$previous = isset( $meta['sheets'] ) && is_array( $meta['sheets'] ) ? $meta['sheets'] : array();

		$engine = new MBRPE_Used_CSS_Engine_B();
		$engine->add_safelist( $this->safelist() );
		$engine->set_union( isset( $meta['union'] ) && is_array( $meta['union'] ) ? array_fill_keys( $meta['union'], true ) : array() );
		$engine->load_html( $html );

		// Work list: every sheet on this page, plus every sheet an earlier
		// sample covered that this page happens not to load.
		//
		// The second half is the fix for pages breaking after they had been
		// learned. Previously each pass rewrote the template's CSS from only
		// the sheets on the *current* page, while the covered list kept every
		// sheet ever seen. A sheet that only an earlier sample loaded (an
		// Elementor post-123.css, a widget plugin's sheet) stayed on the
		// removal list with its rules gone from the inlined CSS — so the next
		// visit to that earlier page was served with the sheet stripped and
		// nothing in its place. Every covered sheet is now re-analysed on every
		// pass; the union carries the selectors earlier pages kept, so their
		// rules survive even though this page's DOM never uses them.
		$work = array();
		foreach ( $on_page as $src => $media ) {
			$key = self::normalise_href( $src );
			if ( '' !== $key ) {
				$work[ $key ] = array( 'src' => $src, 'media' => $media );
			}
		}
		foreach ( $previous as $key => $info ) {
			if ( ! isset( $work[ $key ] ) && is_array( $info ) && ! empty( $info['src'] ) ) {
				$work[ $key ] = array(
					'src'   => (string) $info['src'],
					'media' => isset( $info['media'] ) ? (string) $info['media'] : 'all',
				);
			}
		}

		$dir     = dirname( $this->cache_file );
		$prefix  = preg_replace( '/\.css$/', '', basename( $this->cache_file ) );
		$covered = array();
		$bytes   = 0;

		foreach ( $work as $key => $item ) {
			$src  = $item['src'];
			$path = MBRPE_CSS_Optimizations::url_to_path( $src );
			if ( '' === $path || ! is_readable( $path ) ) {
				continue; // Gone from disk — no longer covered, so never removed.
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local stylesheet from disk for analysis.
			$css = file_get_contents( $path );
			if ( false === $css || '' === $css ) {
				continue;
			}

			// A sheet that pulls in another sheet cannot be removed safely: the
			// engine drops @import (invalid once inlined mid-document), and with
			// the original deleted the imported file would never arrive.
			if ( self::has_import( $css ) ) {
				continue;
			}

			// Absolutise url()s against the sheet's own location so they still
			// resolve once the CSS is inlined into the document.
			$css = MBRPE_CSS_Optimizations::rewrite_css_urls( $css, $src );
			$res = $engine->analyse( $css, $src );
			$out = trim( (string) $res['used_css'] );

			if ( '' !== $out ) {
				// A media="screen" sheet only applied to screens; inlining it
				// bare would leak its rules into print. Restore the context.
				if ( 'screen' === $item['media'] ) {
					$out = '@media screen{' . $out . '}';
				}
				$out = MBRPE_CSS_Optimizations::minify_css_string( $out );
			}

			$file = $prefix . '.' . substr( md5( $key ), 0, 12 ) . '.css';
			// An empty file is meaningful: "analysed, nothing used, safe to drop".
			if ( ! MBRPE_CSS_Optimizations::write_file( $dir . '/' . $file, $out ) ) {
				continue; // No replacement on disk means no removal.
			}

			$covered[ $key ] = array(
				'src'   => $src,
				'media' => $item['media'],
				'file'  => $file,
			);
			$bytes += strlen( $out );
		}

		// Tidy per-sheet files for sheets that dropped out of coverage.
		foreach ( $previous as $key => $info ) {
			if ( ! isset( $covered[ $key ] ) && is_array( $info ) && ! empty( $info['file'] ) ) {
				$stale = $dir . '/' . basename( (string) $info['file'] );
				if ( is_file( $stale ) ) {
					wp_delete_file( $stale );
				}
			}
		}

		if ( empty( $covered ) ) {
			return;
		}

		// Record this URL as a contributor, so the next distinct URL of this
		// template is spent learning rather than being served a set it never
		// had a say in.
		$urls  = isset( $meta['urls'] ) && is_array( $meta['urls'] ) ? $meta['urls'] : array();
		$urls[ self::url_key( $this->request_path() ) ] = time();

		self::write_meta(
			$this->meta_file,
			array(
				'v'          => self::META_VERSION,
				'template'   => $this->template,
				'urls'       => $urls,
				'samples'    => count( $urls ),
				'target'     => $this->sample_target(),
				'sheets'     => $covered,
				'union'      => array_keys( $engine->get_union() ),
				'union_full' => $engine->union_full(),
				'bytes'      => $bytes,
				'built'      => time(),
			)
		);

		// Purge just this URL from the page cache so the next hit re-renders
		// through PHP with the optimised CSS.
		$this->purge_page_cache( $this->current_url );
	}

	/**
	 * Stylesheets on this page that Mode B is willing to analyse and later
	 * remove, in document order, as href => media.
	 *
	 * Everything rejected here keeps its <link> on the front end forever, so
	 * the bar for inclusion is deliberately high.
	 *
	 * @param string $html Rendered HTML.
	 * @return array<string,string>
	 */
	private function analysable_sheets( $html ) {
		$sheets = array();
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $sheets;
		}

		$dom  = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$exclusions = $this->sheet_exclusions();

		foreach ( $dom->getElementsByTagName( 'link' ) as $link ) {
			$rel = strtolower( $link->getAttribute( 'rel' ) );
			if ( false === strpos( $rel, 'stylesheet' ) ) {
				continue;
			}

			$href = trim( $link->getAttribute( 'href' ) );
			if ( '' === $href ) {
				continue;
			}

			// Only plain screen sheets. A print sheet does not block render and
			// is cheap to leave; a narrow media query ("(max-width:600px)")
			// would have to be reconstructed around the extracted rules, and
			// getting that subtly wrong is exactly the kind of failure that is
			// invisible on the developer's monitor.
			$media = strtolower( trim( $link->getAttribute( 'media' ) ) );
			if ( ! in_array( $media, array( '', 'all', 'screen' ), true ) ) {
				continue;
			}

			// The admin bar and Dashicons are per-user furniture rather than
			// template CSS.
			if ( false !== strpos( $href, 'wp-includes/css/dashicons' ) || false !== strpos( $href, 'admin-bar' ) ) {
				continue;
			}

			// External or unresolvable — cannot be read, so cannot be replaced.
			if ( '' === MBRPE_CSS_Optimizations::url_to_path( $href ) ) {
				continue;
			}

			foreach ( $exclusions as $needle ) {
				if ( false !== strpos( $href, $needle ) ) {
					continue 2;
				}
			}

			$sheets[ $href ] = ( 'screen' === $media ) ? 'screen' : 'all';
		}

		return $sheets;
	}

	/**
	 * Does a stylesheet contain a live @import?
	 *
	 * Checked against the raw text with comments stripped, so a commented-out
	 * example in a theme's stylesheet header does not disqualify the sheet.
	 *
	 * @param string $css Raw stylesheet text.
	 * @return bool
	 */
	private static function has_import( $css ) {
		$stripped = preg_replace( '#/\*.*?\*/#s', '', $css );
		if ( null === $stripped ) {
			$stripped = $css; // Pathological input; fail safe by testing raw.
		}
		return (bool) preg_match( '/@import\b/i', $stripped );
	}

	// --- Cache plumbing ----------------------------------------------------

	/**
	 * Cache key for a template.
	 *
	 * Includes the plugin version and the installed plugin/theme fingerprint,
	 * so replacing a plugin over SFTP — which fires no WordPress hook at all —
	 * still retires the cache rather than serving CSS analysed against files
	 * that no longer exist.
	 *
	 * @param string $template Template id.
	 * @return string
	 */
	private static function cache_key( $template ) {
		$epoch = class_exists( 'MBRPE_Used_CSS' ) ? MBRPE_Used_CSS::asset_epoch() : '';
		return sanitize_key( $template ) . '.' . substr( md5( $template . '|' . MBRPE_VERSION . '|' . $epoch ), 0, 12 );
	}

	/**
	 * Stable key for a sampled URL path.
	 *
	 * @param string $path Normalised path.
	 * @return string
	 */
	private static function url_key( $path ) {
		return substr( md5( $path ), 0, 16 );
	}

	/**
	 * Normalise a stylesheet href for comparison: drop the query string, so a
	 * changing ?ver= does not make a learned sheet look like a new one.
	 *
	 * @param string $href
	 * @return string
	 */
	private static function normalise_href( $href ) {
		$href = preg_replace( '/[?#].*$/', '', (string) $href );
		return is_string( $href ) ? trim( $href ) : '';
	}

	/**
	 * Ensure the Mode B cache directory exists; return path+url or ''.
	 *
	 * @return array|string
	 */
	public static function get_dir() {
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
			return '';
		}
		$path = trailingslashit( $upload['basedir'] ) . self::DIRNAME;
		$url  = trailingslashit( $upload['baseurl'] ) . self::DIRNAME;
		if ( ! is_dir( $path ) ) {
			if ( ! wp_mkdir_p( $path ) ) {
				return '';
			}
			MBRPE_CSS_Optimizations::write_file( $path . '/index.html', '' );
		}
		return array(
			'path' => untrailingslashit( $path ),
			'url'  => untrailingslashit( $url ),
		);
	}

	/**
	 * Read a template sidecar, returning an empty array if it is missing,
	 * unreadable, malformed, or written by an older format.
	 *
	 * @param string $file Sidecar path.
	 * @return array
	 */
	private static function read_meta( $file ) {
		if ( '' === $file || ! is_readable( $file ) ) {
			return array();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the plugin's own sidecar from wp-uploads.
		$raw = file_get_contents( $file );
		if ( false === $raw || '' === $raw ) {
			return array();
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || ! isset( $data['v'] ) || self::META_VERSION !== (int) $data['v'] ) {
			return array();
		}
		return $data;
	}

	/**
	 * Write a template sidecar.
	 *
	 * @param string $file Sidecar path.
	 * @param array  $data Metadata.
	 * @return bool
	 */
	private static function write_meta( $file, array $data ) {
		$json = wp_json_encode( $data );
		if ( false === $json ) {
			return false;
		}
		return MBRPE_CSS_Optimizations::write_file( $file, $json );
	}

	/**
	 * Delete every Mode B cache file.
	 *
	 * @return int Templates removed.
	 */
	public static function purge_all() {
		$dir = self::get_dir();
		if ( ! is_array( $dir ) ) {
			return 0;
		}
		$count = 0;
		foreach ( (array) glob( $dir['path'] . '/*.css' ) as $file ) {
			if ( is_file( $file ) && false !== wp_delete_file( $file ) ) {
				$count++;
			}
		}
		foreach ( (array) glob( $dir['path'] . '/*.json' ) as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		foreach ( (array) glob( $dir['path'] . '/*.lock' ) as $file ) {
			wp_delete_file( $file );
		}
		// Force the plugin/theme fingerprint to be recalculated, so a manual
		// purge cannot be undone by a stale epoch.
		if ( class_exists( 'MBRPE_Used_CSS' ) ) {
			delete_transient( MBRPE_Used_CSS::EPOCH_TRANSIENT );
		}
		return $count;
	}

	/**
	 * save_post handler — purge everything, skipping revisions and autosaves.
	 *
	 * @param int $post_id Post being saved.
	 */
	public static function purge_all_on_change( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		// save_post fires for every post type, including private plumbing —
		// nav menu items, form entries, logs, orders, Elementor library items,
		// customizer changesets. Wiping every template on each of those meant
		// a busy site could never finish learning. Only content a visitor can
		// reach changes template markup.
		$post = get_post( $post_id );
		if ( ! $post || 'auto-draft' === $post->post_status ) {
			return;
		}
		$type = get_post_type_object( $post->post_type );
		if ( ! $type || ! $type->public ) {
			return;
		}
		self::purge_all();
	}

	/**
	 * Purge a single URL from the host's full-page cache so the next request
	 * re-renders through PHP. Mirrors Mode A's handling.
	 *
	 * @param string $url Absolute URL to purge.
	 */
	private function purge_page_cache( $url ) {
		if ( '' === $url ) {
			return;
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache( $url );
		}
		/**
		 * Fires after Mode B learns a URL, so other page caches can purge just
		 * that URL and let the optimised render be re-cached.
		 *
		 * @param string $url Absolute URL that was just learned.
		 */
		do_action( 'mbrpe_modeb_purge_url', $url );
	}

	/**
	 * Per-template cache status, for the CSS tab.
	 *
	 * @return array List of rows: template, label, samples, target, bytes,
	 *               built, sheets, union, union_full.
	 */
	public static function cache_status() {
		$dir = self::get_dir();
		if ( ! is_array( $dir ) ) {
			return array();
		}

		$labels = self::template_labels();
		$rows   = array();

		foreach ( (array) glob( $dir['path'] . '/*.json' ) as $file ) {
			$meta = self::read_meta( $file );
			if ( empty( $meta['template'] ) ) {
				continue;
			}
			$bytes = (int) ( isset( $meta['bytes'] ) ? $meta['bytes'] : 0 );

			$id = (string) $meta['template'];

			// Work the target out live for single-URL templates rather than
			// trusting the sidecar: sidecars written before 2.1.4 recorded the
			// general target (e.g. 4) for the front page, which it can never
			// reach, so it would otherwise read "still learning" until the
			// cache was cleared.
			$single = self::is_single_url_template( $id );
			$target = $single
				? 1
				: (int) ( isset( $meta['target'] ) ? $meta['target'] : self::DEFAULT_SAMPLES );

			$rows[] = array(
				'template'   => $id,
				'label'      => isset( $labels[ $id ] ) ? $labels[ $id ] : $id,
				'samples'    => (int) ( isset( $meta['samples'] ) ? $meta['samples'] : 0 ),
				'target'     => $target,
				'single_url' => $single,
				'bytes'      => $bytes,
				'built'      => (int) ( isset( $meta['built'] ) ? $meta['built'] : 0 ),
				'sheets'     => count( isset( $meta['sheets'] ) && is_array( $meta['sheets'] ) ? $meta['sheets'] : array() ),
				'union'      => count( isset( $meta['union'] ) && is_array( $meta['union'] ) ? $meta['union'] : array() ),
				'union_full' => ! empty( $meta['union_full'] ),
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return strcmp( $a['label'], $b['label'] );
			}
		);

		return $rows;
	}

	/**
	 * Totals for the CSS tab summary line.
	 *
	 * @return array{templates:int,learned:int,bytes:int}
	 */
	public static function cache_stats() {
		$out = array(
			'templates' => 0,
			'learned'   => 0,
			'bytes'     => 0,
		);
		foreach ( self::cache_status() as $row ) {
			$out['templates']++;
			$out['bytes'] += $row['bytes'];
			if ( $row['samples'] >= $row['target'] ) {
				$out['learned']++;
			}
		}
		return $out;
	}
}
