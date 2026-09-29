<?php
/**
 * MBR Performance Doctor tab.
 *
 * @package MBR_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mbr-performance-tab-content mbr-performance-doctor">

	<div class="mbr-performance-card">
		<h2><?php esc_html_e( 'MBR Performance Doctor', 'mbr-performance' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Rather than guess which optimisations a site needs, let the Doctor look at a real page and tell you — in priority order — which settings will actually help, and which you can leave alone. It analyses what is blocking the page from rendering and points you straight at the right toggle.', 'mbr-performance' ); ?>
		</p>

		<p>
			<label for="mbr-doctor-url"><strong><?php esc_html_e( 'Page to analyse', 'mbr-performance' ); ?></strong></label><br>
			<input type="url" id="mbr-doctor-url" class="regular-text code"
				value="<?php echo esc_url( home_url( '/' ) ); ?>"
				placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
			<button type="button" class="button button-primary" id="mbr-run-doctor">
				<?php esc_html_e( 'Run analysis', 'mbr-performance' ); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e( 'A single page can be misleading — a lean landing page and a busy blog template need different things. Scan your key templates at once and the Doctor will tell you which fixes are site-wide and which are page-specific.', 'mbr-performance' ); ?>
		</p>
		<p>
			<button type="button" class="button button-secondary" id="mbr-run-doctor-site">
				<?php esc_html_e( 'Scan key templates', 'mbr-performance' ); ?>
			</button>
			<span class="description"><?php esc_html_e( 'Auto-detects home, blog, a post, a page, and WooCommerce shop/product where present.', 'mbr-performance' ); ?></span>
		</p>
	</div>

	<div id="mbr-doctor-results" class="mbr-performance-doctor-results" hidden></div>

</div>
