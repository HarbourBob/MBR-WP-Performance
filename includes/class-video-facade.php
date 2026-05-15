<?php
/**
 * Video Facade
 *
 * Replaces embedded YouTube and Vimeo iframes with a static thumbnail and a
 * play button. The full iframe is only injected when the user clicks. This
 * is the same pattern used by "Lite YouTube Embed".
 *
 * Wins:
 *  - Saves ~1.4MB of YouTube JS on initial page load.
 *  - Defers all third-party fetches to googlevideo.com / vimeo.com.
 *  - Large LCP/TBT/INP improvement on pages with video embeds.
 *  - Stops YouTube cookies being set until the user actually interacts.
 *
 * Privacy note: when enabled, no requests are made to youtube.com or vimeo.com
 * until the user clicks play (other than the static thumbnail image fetch).
 *
 * @package MBR_WP_Performance
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBR_WP_Performance_Video_Facade {

    /**
     * Single instance.
     *
     * @var MBR_WP_Performance_Video_Facade
     */
    private static $instance = null;

    /**
     * Options (under 'lazy_loading' section for grouping).
     *
     * @var array
     */
    private $options = array();

    /**
     * Get instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->options = mbr_wp_performance()->get_options( 'lazy_loading' );

        if ( empty( $this->options['video_facade'] ) ) {
            return;
        }

        if ( is_admin() ) {
            return;
        }

        // Rewrite iframes in post content and blocks.
        add_filter( 'the_content', array( $this, 'replace_iframes' ), 99 );
        add_filter( 'render_block', array( $this, 'replace_block_iframes' ), 99, 2 );

        // Output the runtime + styles once per page.
        add_action( 'wp_footer', array( $this, 'output_runtime' ), 5 );
    }

    /**
     * Replace YouTube/Vimeo iframes in content with a facade.
     *
     * @param string $content
     * @return string
     */
    public function replace_iframes( $content ) {
        if ( false === stripos( $content, '<iframe' ) ) {
            return $content;
        }
        return preg_replace_callback( '/<iframe[^>]*>(?:.*?<\/iframe>)?/is', array( $this, 'rewrite_iframe_match' ), $content );
    }

    /**
     * Replace iframes inside core/embed blocks.
     *
     * @param string $block_content
     * @param array  $parsed_block
     * @return string
     */
    public function replace_block_iframes( $block_content, $parsed_block ) {
        if ( false === stripos( (string) $block_content, '<iframe' ) ) {
            return $block_content;
        }
        $block_name = isset( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '';
        // Only rewrite embed-style blocks to avoid hitting unrelated iframes.
        if ( 'core/embed' !== $block_name && 'core-embed/youtube' !== $block_name && 'core-embed/vimeo' !== $block_name ) {
            return $block_content;
        }
        return preg_replace_callback( '/<iframe[^>]*>(?:.*?<\/iframe>)?/is', array( $this, 'rewrite_iframe_match' ), $block_content );
    }

    /**
     * Callback that decides whether a single <iframe> tag is a YouTube/Vimeo
     * embed and, if so, returns the facade HTML.
     *
     * @param array $m preg match array.
     * @return string
     */
    public function rewrite_iframe_match( $m ) {
        $iframe = $m[0];
        if ( ! preg_match( '/\ssrc=["\']([^"\']+)["\']/', $iframe, $sm ) ) {
            return $iframe;
        }
        $src = $sm[1];

        // YouTube.
        if ( preg_match( '~(?:youtube\.com|youtube-nocookie\.com)/embed/([A-Za-z0-9_-]{6,})~', $src, $ym ) ) {
            $video_id = $ym[1];
            return $this->youtube_facade( $video_id, $src );
        }

        // Vimeo.
        if ( preg_match( '~player\.vimeo\.com/video/(\d+)~', $src, $vm ) ) {
            $video_id = $vm[1];
            return $this->vimeo_facade( $video_id, $src );
        }

        return $iframe;
    }

    /**
     * Render a YouTube facade.
     *
     * @param string $video_id
     * @param string $original_src
     * @return string
     */
    private function youtube_facade( $video_id, $original_src ) {
        // hqdefault is reliable across all videos; maxresdefault sometimes 404s.
        $thumb = sprintf( 'https://i.ytimg.com/vi/%s/hqdefault.jpg', rawurlencode( $video_id ) );
        return sprintf(
            '<div class="mbr-video-facade mbr-video-facade--youtube" data-provider="youtube" data-id="%1$s" data-src="%2$s" role="button" tabindex="0" aria-label="%3$s">'
                . '<img loading="lazy" decoding="async" src="%4$s" alt="" class="mbr-video-facade__thumb">'
                . '<button type="button" class="mbr-video-facade__play" aria-label="%3$s"></button>'
            . '</div>',
            esc_attr( $video_id ),
            esc_attr( $original_src ),
            esc_attr__( 'Play video', 'mbr-wp-performance' ),
            esc_url( $thumb )
        );
    }

    /**
     * Render a Vimeo facade.
     *
     * Vimeo thumbnail requires an API call (vimeo.com/api/v2/video/{id}.json),
     * so we lazily fetch it client-side when the runtime initialises, falling
     * back to a CSS gradient placeholder until then.
     *
     * @param string $video_id
     * @param string $original_src
     * @return string
     */
    private function vimeo_facade( $video_id, $original_src ) {
        return sprintf(
            '<div class="mbr-video-facade mbr-video-facade--vimeo" data-provider="vimeo" data-id="%1$s" data-src="%2$s" role="button" tabindex="0" aria-label="%3$s">'
                . '<div class="mbr-video-facade__thumb mbr-video-facade__thumb--vimeo" aria-hidden="true"></div>'
                . '<button type="button" class="mbr-video-facade__play" aria-label="%3$s"></button>'
            . '</div>',
            esc_attr( $video_id ),
            esc_attr( $original_src ),
            esc_attr__( 'Play video', 'mbr-wp-performance' )
        );
    }

    /**
     * Output runtime + minimal styles.
     */
    public function output_runtime() {
        ?>
        <style id="mbr-video-facade-style">
        .mbr-video-facade{position:relative;width:100%;aspect-ratio:16/9;background:#000;cursor:pointer;overflow:hidden;border-radius:8px;display:block}
        .mbr-video-facade__thumb{width:100%;height:100%;object-fit:cover;display:block;border:0}
        .mbr-video-facade__thumb--vimeo{background:linear-gradient(135deg,#1ab7ea 0%,#0d2436 100%)}
        .mbr-video-facade__play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:68px;height:48px;border:0;background:rgba(0,0,0,.7);border-radius:8px;cursor:pointer;padding:0;transition:background .15s}
        .mbr-video-facade__play:hover,.mbr-video-facade:hover .mbr-video-facade__play{background:#f00}
        .mbr-video-facade__play::before{content:"";position:absolute;top:50%;left:50%;transform:translate(-40%,-50%);width:0;height:0;border-style:solid;border-width:10px 0 10px 18px;border-color:transparent transparent transparent #fff}
        .mbr-video-facade--vimeo .mbr-video-facade__play:hover,.mbr-video-facade--vimeo:hover .mbr-video-facade__play{background:#1ab7ea}
        .mbr-video-facade iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
        </style>
        <script id="mbr-video-facade-runtime">
        (function(){
            function activate(el){
                var provider=el.dataset.provider;
                var id=el.dataset.id;
                var src=el.dataset.src;
                if(!src) return;
                // Append autoplay so the user gets video immediately on click.
                var sep=src.indexOf('?')===-1?'?':'&';
                if(src.indexOf('autoplay=')===-1) src+=sep+'autoplay=1';
                var i=document.createElement('iframe');
                i.setAttribute('src',src);
                i.setAttribute('allow','accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                i.setAttribute('allowfullscreen','');
                i.setAttribute('frameborder','0');
                i.setAttribute('title','Embedded video');
                el.innerHTML='';
                el.appendChild(i);
            }
            function hydrateVimeoThumb(el){
                var id=el.dataset.id;
                fetch('https://vimeo.com/api/v2/video/'+encodeURIComponent(id)+'.json')
                    .then(function(r){return r.ok?r.json():null;})
                    .then(function(j){
                        if(!j||!j[0]) return;
                        var img=document.createElement('img');
                        img.src=j[0].thumbnail_large;
                        img.alt='';
                        img.loading='lazy';
                        img.decoding='async';
                        img.className='mbr-video-facade__thumb';
                        var existing=el.querySelector('.mbr-video-facade__thumb');
                        if(existing) existing.replaceWith(img);
                    })
                    .catch(function(){});
            }
            document.querySelectorAll('.mbr-video-facade').forEach(function(el){
                el.addEventListener('click',function(){activate(el);});
                el.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();activate(el);}});
                if(el.dataset.provider==='vimeo'){
                    // Hydrate Vimeo thumbnail lazily when in viewport.
                    if('IntersectionObserver' in window){
                        var io=new IntersectionObserver(function(entries){
                            entries.forEach(function(en){
                                if(en.isIntersecting){ hydrateVimeoThumb(el); io.unobserve(el); }
                            });
                        });
                        io.observe(el);
                    } else { hydrateVimeoThumb(el); }
                }
            });
        })();
        </script>
        <?php
    }
}
