<?php
/**
 * Full Page Cache — purge engine.
 *
 * A page cache is only as good as its invalidation. Serving stale content is
 * worse than serving slow content, so the bias throughout this class is towards
 * purging a little too much rather than a little too little.
 *
 * The related-URL logic — when a post changes, which *other* pages did that
 * change affect — is modelled on LiteSpeed Cache's purge tag system, which is
 * the most thoroughly battle-tested version of this problem in the WordPress
 * ecosystem. LiteSpeed resolves those relationships into cache *tags* for its
 * server to match; we resolve them into concrete URLs, because our entries are
 * files on disk. The taxonomy of relationships is theirs; the implementation is
 * ours.
 *
 * @package MBRPE
 * @since   2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBRPE_Page_Cache_Purge {

	/**
	 * How many paginated pages deep to follow when purging an archive.
	 *
	 * Archives grow without limit; walking every page of a ten-year blog on
	 * every post save would be its own performance problem. Twenty covers the
	 * pages anyone actually reaches.
	 */
	const PAGINATION_DEPTH = 20;

	/**
	 * Single instance.
	 *
	 * @var MBRPE_Page_Cache_Purge
	 */
	private static $instance = null;

	/**
	 * Post IDs already purged this request, to collapse the several hooks that
	 * fire for a single save into one pass.
	 *
	 * @var array
	 */
	private static $purged_posts = array();

	/**
	 * Get instance.
	 *
	 * @return MBRPE_Page_Cache_Purge
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — register every event that invalidates cached HTML.
	 */
	private function __construct() {
		// ---- Content ----------------------------------------------------
		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );
		foreach ( array( 'edit_post', 'delete_post', 'wp_trash_post', 'untrash_post', 'clean_post_cache' ) as $hook ) {
			add_action( $hook, array( $this, 'on_post_event' ) );
		}
		add_action( 'attachment_updated', array( $this, 'on_post_event' ) );

		// ---- Comments ---------------------------------------------------
		// A new comment changes the post, its feed, and any "recent comments"
		// block on every other page — hence the archive purge inside.
		foreach ( array( 'comment_post', 'edit_comment', 'trashed_comment', 'untrashed_comment', 'deleted_comment' ) as $hook ) {
			add_action( $hook, array( $this, 'on_comment_event' ) );
		}
		add_action( 'wp_set_comment_status', array( $this, 'on_comment_event' ) );

		// ---- Taxonomy ---------------------------------------------------
		add_action( 'created_term', array( $this, 'on_term_event' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'on_term_event' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'on_term_deleted' ), 10, 4 );

		// ---- Site-wide structure ----------------------------------------
		// These change every page at once, so they take the blunt instrument.
		add_action( 'switch_theme', array( __CLASS__, 'purge_all_theme' ) );
		add_action( 'customize_save_after', array( __CLASS__, 'purge_all_customizer' ) );
		add_action( 'wp_update_nav_menu', array( __CLASS__, 'purge_all_menu' ) );
		add_action( 'update_option_sidebars_widgets', array( __CLASS__, 'purge_all_widgets' ) );
		add_action( 'activated_plugin', array( __CLASS__, 'purge_all_plugin' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'purge_all_plugin' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'purge_all_upgrade' ) );

		foreach ( array( 'permalink_structure', 'blogname', 'blogdescription', 'home', 'siteurl', 'show_on_front', 'page_on_front', 'page_for_posts', 'posts_per_page', 'date_format', 'time_format' ) as $option ) {
			add_action( 'update_option_' . $option, array( __CLASS__, 'purge_all_option' ) );
		}

		// ---- Users (author archives, bylines) ---------------------------
		add_action( 'profile_update', array( $this, 'on_user_event' ) );
		add_action( 'deleted_user', array( $this, 'on_user_event' ) );

		// ---- WooCommerce ------------------------------------------------
		// Stock movements change price/availability markup on product pages and
		// on every archive the product appears in.
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_wc_stock' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'on_wc_stock' ) );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'on_post_event' ) );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'on_post_event' ) );

		// ---- Manual purge routes ----------------------------------------
		// Used CSS (Mode A) and Mode B announce each URL they have just
		// (re)generated. Both already stop the provisional render being stored
		// (MBRPE_Page_Cache::defer()), so this is belt and braces: it clears an
		// entry written by an earlier version, or by a request that raced the
		// generation.
		add_action( 'mbrpe_usedcss_purge_url', array( __CLASS__, 'purge_generated_url' ) );
		add_action( 'mbrpe_modeb_purge_url', array( __CLASS__, 'purge_generated_url' ) );

		add_action( 'admin_post_mbrpe_purge_cache', array( $this, 'handle_purge_request' ) );
		add_action( 'wp_ajax_mbrpe_purge_cache', array( $this, 'ajax_purge_cache' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_purge_notice' ) );
	}

	// =====================================================================
	// Event handlers
	// =====================================================================

	/**
	 * Purge on publish, unpublish, or any transition involving a live state.
	 *
	 * Drafts editing drafts are ignored — nothing cached could be affected.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post.
	 * @return void
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		$this->purge_post( $post->ID );
	}

	/**
	 * Generic post-touched handler.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_post_event( $post_id ) {
		$this->purge_post( (int) $post_id );
	}

	/**
	 * Comment activity on a post.
	 *
	 * @param int|string $comment_id Comment ID (or status, on wp_set_comment_status).
	 * @return void
	 */
	public function on_comment_event( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}
		$this->purge_post( (int) $comment->comment_post_ID );
	}

	/**
	 * Term created or edited.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function on_term_event( $term_id, $tt_id = 0, $taxonomy = '' ) {
		if ( ! $taxonomy ) {
			return;
		}
		$link = get_term_link( (int) $term_id, $taxonomy );
		if ( ! is_wp_error( $link ) ) {
			self::purge_url( $link, true );
		}
	}

	/**
	 * Term deleted.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @param mixed  $deleted  Deleted term object.
	 * @return void
	 */
	public function on_term_deleted( $term_id, $tt_id, $taxonomy, $deleted ) {
		// The term's own URL is gone; the archives that listed it are not.
		self::purge_all( 'term-deleted' );
	}

	/**
	 * User profile changed — purge their author archive and the front page.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_user_event( $user_id ) {
		$link = get_author_posts_url( (int) $user_id );
		if ( $link ) {
			self::purge_url( $link, true );
		}
		self::purge_front();
	}

	/**
	 * WooCommerce stock object handler.
	 *
	 * @param mixed $product WC_Product or ID.
	 * @return void
	 */
	public function on_wc_stock( $product ) {
		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
			$this->purge_post( (int) $product->get_id() );
			return;
		}
		$this->purge_post( (int) $product );
	}

	// =====================================================================
	// Purge operations
	// =====================================================================

	/**
	 * Purge a post and everything its change could have altered.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function purge_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return;
		}

		// Several hooks fire per save; do the work once.
		if ( isset( self::$purged_posts[ $post_id ] ) ) {
			return;
		}
		self::$purged_posts[ $post_id ] = true;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Revisions, autosaves and nav menu items are never cached and never
		// affect anything that is.
		if ( in_array( $post->post_type, array( 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'user_request' ), true ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type_object( $post->post_type );
		if ( ! $post_type || ! $post_type->public ) {
			return;
		}

		$urls = array();

		// 1. The post itself.
		$permalink = get_permalink( $post_id );
		if ( $permalink ) {
			$urls[] = array( $permalink, true ); // recursive: paginated posts use /2/.
		}

		$opts = mbrpe()->get_options( 'cache' );

		// A blunt option for sites where anything else is guesswork — heavily
		// cross-linked designs, "related posts everywhere" themes.
		if ( ! empty( $opts['purge_all_on_edit'] ) ) {
			self::purge_all( 'post-edit-full' );
			return;
		}

		// 2. Front page and blog index.
		$urls[] = array( home_url( '/' ), true );
		$posts_page = (int) get_option( 'page_for_posts' );
		if ( $posts_page ) {
			$link = get_permalink( $posts_page );
			if ( $link ) {
				$urls[] = array( $link, true );
			}
		}

		// 3. Every term the post belongs to, across every taxonomy.
		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $taxonomy->name );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$urls[] = array( $link, true );
				}
			}
		}

		// 4. Post type archive.
		$archive = get_post_type_archive_link( $post->post_type );
		if ( $archive ) {
			$urls[] = array( $archive, true );
		}

		// 5. Author archive.
		if ( ! empty( $post->post_author ) ) {
			$link = get_author_posts_url( (int) $post->post_author );
			if ( $link ) {
				$urls[] = array( $link, true );
			}
		}

		// 6. Date archives — year, month, day.
		$timestamp = strtotime( $post->post_date_gmt );
		if ( $timestamp ) {
			$y = gmdate( 'Y', $timestamp );
			$m = gmdate( 'm', $timestamp );
			$d = gmdate( 'd', $timestamp );
			foreach ( array( get_year_link( $y ), get_month_link( $y, $m ), get_day_link( $y, $m, $d ) ) as $link ) {
				if ( $link ) {
					$urls[] = array( $link, true );
				}
			}
		}

		// 7. Adjacent posts, because "previous/next" navigation names them.
		$previous = get_previous_post();
		$next     = get_next_post();
		foreach ( array( $previous, $next ) as $sibling ) {
			if ( $sibling instanceof WP_Post ) {
				$link = get_permalink( $sibling->ID );
				if ( $link ) {
					$urls[] = array( $link, false );
				}
			}
		}

		// 8. Feeds.
		if ( ! empty( $opts['cache_feeds'] ) ) {
			$urls[] = array( get_bloginfo( 'rss2_url' ), false );
			$urls[] = array( get_post_comments_feed_link( $post_id ), false );
		}

		/**
		 * Filter the URLs purged when a post changes.
		 *
		 * Each entry is array( $url, $recursive ).
		 *
		 * @since 2.0.0
		 * @param array $urls    URL/recursive pairs.
		 * @param int   $post_id Post ID.
		 */
		$urls = apply_filters( 'mbrpe_cache_purge_post_urls', $urls, $post_id );

		$seen = array();
		foreach ( $urls as $entry ) {
			list( $url, $recursive ) = $entry;
			if ( ! $url || isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;
			self::purge_url( $url, $recursive );
		}

		self::touch_last_purge();

		/**
		 * Fires after a post's related URLs have been purged.
		 *
		 * @since 2.0.0
		 * @param int   $post_id Post ID.
		 * @param array $urls    URLs purged.
		 */
		do_action( 'mbrpe_cache_purged_post', $post_id, $urls );
	}

	/**
	 * Purge a single URL.
	 *
	 * Deletes every variant of that one page — desktop, mobile, query-string
	 * keyed, gzip siblings — by clearing the files in its directory. Child
	 * directories are left alone unless $recursive is true, because /blog/'s
	 * children are entirely separate pages.
	 *
	 * @param string $url       Absolute URL.
	 * @param bool   $recursive Also purge /page/2/, /page/3/ … beneath it.
	 * @return int Entries removed.
	 */
	public static function purge_url( $url, $recursive = false ) {
		$dir = self::url_to_dir( $url );
		if ( '' === $dir ) {
			return 0;
		}

		$removed = self::clear_dir_files( $dir );

		if ( $recursive ) {
			$removed += self::purge_pagination( $dir );
		}

		return $removed;
	}

	/**
	 * Purge one URL after Used CSS or Mode B has generated for it.
	 *
	 * @since 2.1.2
	 * @param string $url Absolute URL.
	 * @return void
	 */
	public static function purge_generated_url( $url ) {
		if ( is_string( $url ) && '' !== $url ) {
			self::purge_url( $url );
		}
	}

	/**
	 * Purge the front page and its pagination.
	 *
	 * @return int
	 */
	public static function purge_front() {
		return self::purge_url( home_url( '/' ), true );
	}

	/**
	 * Purge every cached page on the site.
	 *
	 * @param string $reason Short label, recorded for the admin tab.
	 * @return int Entries removed.
	 */
	public static function purge_all( $reason = 'manual' ) {
		$root = MBRPE_Page_Cache::cache_dir();
		if ( ! is_dir( $root ) ) {
			return 0;
		}

		$removed = 0;

		// Clear only the per-scheme trees, so config.php, index.php and the
		// guard .htaccess at the root survive.
		foreach ( array( 'http', 'https' ) as $scheme ) {
			$dir = $root . '/' . $scheme;
			if ( is_dir( $dir ) ) {
				$removed += MBRPE_Page_Cache::rmdir_recursive( $dir, true );
			}
		}

		self::touch_last_purge( $reason );
		delete_transient( 'mbrpe_cache_stats' );

		/**
		 * Fires after the whole page cache has been cleared.
		 *
		 * @since 2.0.0
		 * @param int    $removed Entries removed.
		 * @param string $reason  Why.
		 */
		do_action( 'mbrpe_cache_purged_all', $removed, $reason );

		return $removed;
	}

	// Named wrappers, so the recorded reason is useful in the admin tab.

	/** @return void */
	public static function purge_all_theme() {
		self::purge_all( 'theme-switched' ); }
	/** @return void */
	public static function purge_all_customizer() {
		self::purge_all( 'customizer-saved' ); }
	/** @return void */
	public static function purge_all_menu() {
		self::purge_all( 'menu-updated' ); }
	/** @return void */
	public static function purge_all_widgets() {
		self::purge_all( 'widgets-updated' ); }
	/** @return void */
	public static function purge_all_plugin() {
		self::purge_all( 'plugin-toggled' ); }
	/** @return void */
	public static function purge_all_upgrade() {
		self::purge_all( 'update-installed' ); }
	/** @return void */
	public static function purge_all_option() {
		self::purge_all( 'site-option-changed' ); }

	// =====================================================================
	// Path helpers
	// =====================================================================

	/**
	 * Map an absolute URL to its cache directory.
	 *
	 * Returns '' for anything outside this site, so a filter returning a foreign
	 * URL cannot make us delete an unrelated tree.
	 *
	 * @param string $url Absolute URL.
	 * @return string Absolute directory path, or ''.
	 */
	public static function url_to_dir( $url ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return '';
		}

		$host = strtolower( $parts['host'] );
		if ( ! empty( $parts['port'] ) && ! in_array( (int) $parts['port'], array( 80, 443 ), true ) ) {
			$host .= ':' . (int) $parts['port'];
		}
		$host = preg_replace( '/[^a-z0-9.:_-]/', '', $host );
		if ( '' === $host ) {
			return '';
		}

		$path = isset( $parts['path'] ) ? rawurldecode( $parts['path'] ) : '/';
		if ( false !== strpos( $path, '..' ) || false !== strpos( $path, "\0" ) ) {
			return '';
		}
		$path = '/' . ltrim( preg_replace( '#/+#', '/', $path ), '/' );

		if ( ! preg_match( '#^[A-Za-z0-9/_.~-]*$#', $path ) ) {
			$path = '/__h/' . md5( $path ) . '/';
		}

		$scheme = ( isset( $parts['scheme'] ) && 'https' === $parts['scheme'] ) ? 'https' : 'http';

		// A URL may have been cached under either scheme (an http→https move
		// mid-life, a proxy that reports differently). Returning the directory
		// for the URL's own scheme is right for writes; purges call this twice.
		return MBRPE_Page_Cache::cache_dir() . '/' . $scheme . '/' . $host . '/' . trim( $path, '/' );
	}

	/**
	 * Delete the cache files directly inside a directory, leaving child
	 * directories (which are other pages) intact.
	 *
	 * Purges both scheme trees, since a site that moved to HTTPS may still hold
	 * entries written under http.
	 *
	 * @param string $dir Directory as returned by url_to_dir().
	 * @return int Entries removed.
	 */
	private static function clear_dir_files( $dir ) {
		$root    = MBRPE_Page_Cache::cache_dir();
		$removed = 0;

		// Build the sibling path under the other scheme.
		$candidates = array( $dir );
		if ( 0 === strpos( $dir, $root . '/https/' ) ) {
			$candidates[] = $root . '/http/' . substr( $dir, strlen( $root . '/https/' ) );
		} elseif ( 0 === strpos( $dir, $root . '/http/' ) ) {
			$candidates[] = $root . '/https/' . substr( $dir, strlen( $root . '/http/' ) );
		}

		foreach ( $candidates as $candidate ) {
			$real      = realpath( $candidate );
			$real_root = realpath( $root );
			if ( ! $real || ! $real_root ) {
				continue;
			}
			// Trailing separator, so a sibling directory whose name merely
			// begins with the cache root cannot satisfy the prefix test.
			if ( 0 !== strpos(
				trailingslashit( wp_normalize_path( $real ) ),
				trailingslashit( wp_normalize_path( $real_root ) )
			) ) {
				continue;
			}

			$files = @scandir( $real ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $files ) {
				continue;
			}

			foreach ( $files as $file ) {
				if ( '.' === $file || '..' === $file ) {
					continue;
				}
				$target = $real . '/' . $file;
				if ( is_dir( $target ) ) {
					continue;
				}
				if ( '.html' !== substr( $file, -5 ) && '.gz' !== substr( $file, -3 ) ) {
					continue;
				}
				if ( @unlink( $target ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					if ( '.html' === substr( $file, -5 ) ) {
						++$removed;
					}
				}
			}
		}

		return $removed;
	}

	/**
	 * Purge /page/2/ … beneath a directory.
	 *
	 * Walks until it finds a gap rather than blindly hitting PAGINATION_DEPTH,
	 * so the common case (three pages of archive) costs three stat calls.
	 *
	 * @param string $dir Base directory.
	 * @return int Entries removed.
	 */
	private static function purge_pagination( $dir ) {
		$removed = 0;
		$base    = $dir . '/page';

		if ( ! is_dir( $base ) ) {
			return 0;
		}

		for ( $page = 2; $page <= self::PAGINATION_DEPTH; $page++ ) {
			$target = $base . '/' . $page;
			if ( ! is_dir( $target ) ) {
				break;
			}
			$removed += self::clear_dir_files( $target );
		}

		return $removed;
	}

	// =====================================================================
	// Manual purge routes
	// =====================================================================

	/**
	 * Handle the toolbar purge links.
	 *
	 * @return void
	 */
	public function handle_purge_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'mbr-performance' ) );
		}
		check_admin_referer( 'mbrpe_purge_cache' );

		$scope = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : 'all';

		if ( 'url' === $scope && ! empty( $_GET['url'] ) ) {
			$url = esc_url_raw( wp_unslash( $_GET['url'] ) );
			// Only ever purge URLs belonging to this site.
			if ( 0 === strpos( $url, home_url() ) ) {
				$count = self::purge_url( $url, true );
			} else {
				$count = 0;
			}
		} else {
			$count = self::purge_all( 'toolbar' );
		}

		$back = wp_get_referer();
		if ( ! $back ) {
			$back = admin_url( 'admin.php?page=mbr-performance&tab=cache' );
		}

		wp_safe_redirect( add_query_arg( 'mbrpe_purged', (int) $count, $back ) );
		exit;
	}

	/**
	 * AJAX purge, for the buttons on the Cache tab.
	 *
	 * @return void
	 */
	public function ajax_purge_cache() {
		check_ajax_referer( 'mbrpe_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'mbr-performance' ) ) );
		}

		$count = self::purge_all( 'admin' );

		wp_send_json_success(
			array(
				/* translators: %d: number of cached pages cleared */
				'message' => sprintf(
					_n( 'Cleared %d cached page.', 'Cleared %d cached pages.', $count, 'mbr-performance' ),
					number_format_i18n( $count )
				),
				'deleted' => $count,
				'stats'   => MBRPE_Page_Cache::stats( true ),
			)
		);
	}

	/**
	 * Confirmation notice after a toolbar purge.
	 *
	 * @return void
	 */
	public function maybe_show_purge_notice() {
		if ( ! isset( $_GET['mbrpe_purged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$count = (int) $_GET['mbrpe_purged']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of cached pages cleared */
					_n( 'Page cache purged — %d page cleared.', 'Page cache purged — %d pages cleared.', $count, 'mbr-performance' ),
					number_format_i18n( $count )
				)
			)
		);
	}

	// =====================================================================
	// Bookkeeping
	// =====================================================================

	/**
	 * Record when and why the cache was last purged.
	 *
	 * @param string $reason Reason label.
	 * @return void
	 */
	private static function touch_last_purge( $reason = '' ) {
		update_option(
			'mbrpe_cache_last_purge',
			array(
				'time'   => time(),
				'reason' => $reason,
			),
			false
		);
		delete_transient( 'mbrpe_cache_stats' );
	}
}
