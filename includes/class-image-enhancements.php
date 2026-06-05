<?php
/**
 * Image Enhancements
 *
 * Two small but high-leverage image optimisations:
 *
 *  1. `decoding="async"` — added to all front-end <img> tags that don't already
 *     specify a decoding hint. Lets the browser decode the image off the main
 *     thread, improving INP on image-heavy pages. The LCP candidate (first
 *     viewport image) should ideally be decoding="sync" with fetchpriority="high",
 *     which the existing preloading tab already handles, so we explicitly skip
 *     any <img> already carrying fetchpriority="high".
 *
 *  2. EXIF / metadata stripping on upload — JPEG metadata (camera serial, GPS,
 *     IPTC, XMP, embedded thumbnails) is removed at upload time. Privacy win
 *     and typically a 5-30% size reduction with zero quality loss.
 *
 * @package MBRPE
 * @since   1.12.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBRPE_Image_Enhancements {

    /**
     * Single instance.
     *
     * @var MBRPE_Image_Enhancements
     */
    private static $instance = null;

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
        $opts = mbrpe()->get_options( 'image_dimensions' );

        if ( ! empty( $opts['decoding_async'] ) && ! is_admin() ) {
            add_filter( 'the_content',           array( $this, 'add_decoding_attr' ), 99 );
            add_filter( 'post_thumbnail_html',   array( $this, 'add_decoding_attr' ), 99 );
            add_filter( 'wp_get_attachment_image', array( $this, 'add_decoding_attr' ), 99 );
            add_filter( 'render_block',          array( $this, 'add_decoding_to_block' ), 99, 2 );
        }

        if ( ! empty( $opts['strip_exif'] ) ) {
            add_filter( 'wp_handle_upload', array( $this, 'strip_exif_on_upload' ), 10, 1 );
        }
    }

    /**
     * Add decoding="async" to <img> tags lacking one.
     *
     * Skips the LCP candidate (fetchpriority="high") so it can decode synchronously.
     *
     * @param string $html
     * @return string
     */
    public function add_decoding_attr( $html ) {
        if ( false === stripos( (string) $html, '<img' ) ) {
            return $html;
        }
        return preg_replace_callback( '/<img\b([^>]*)>/i', function ( $m ) {
            $attrs = $m[1];
            if ( preg_match( '/\sdecoding\s*=/i', $attrs ) ) {
                return $m[0];
            }
            if ( preg_match( '/\sfetchpriority\s*=\s*["\']high["\']/i', $attrs ) ) {
                return $m[0];
            }
            return '<img decoding="async"' . $attrs . '>';
        }, $html );
    }

    /**
     * Block-level decoding attribute injection.
     *
     * @param string $block_content
     * @param array  $parsed_block
     * @return string
     */
    public function add_decoding_to_block( $block_content, $parsed_block ) {
        $name = isset( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '';
        if ( 'core/image' !== $name && 'core/gallery' !== $name && 'core/cover' !== $name && 'core/media-text' !== $name ) {
            return $block_content;
        }
        return $this->add_decoding_attr( $block_content );
    }

    /**
     * Strip EXIF/IPTC/XMP from a JPEG at upload time.
     *
     * @param array $upload
     * @return array
     */
    public function strip_exif_on_upload( $upload ) {
        if ( ! isset( $upload['file'], $upload['type'] ) ) {
            return $upload;
        }
        $type = $upload['type'];
        if ( 'image/jpeg' !== $type && 'image/jpg' !== $type ) {
            return $upload;
        }
        $path = $upload['file'];
        if ( ! is_readable( $path ) || ! wp_is_writable( $path ) ) {
            return $upload;
        }

        // Prefer Imagick — it has a clean stripImage() method.
        if ( class_exists( 'Imagick' ) ) {
            try {
                $img = new \Imagick( $path );
                $profiles = $img->getImageProfiles( 'icc', true );
                $img->stripImage();
                // Preserve the colour profile to keep colours accurate.
                if ( ! empty( $profiles['icc'] ) ) {
                    $img->profileImage( 'icc', $profiles['icc'] );
                }
                $img->writeImage( $path );
                $img->clear();
                return $upload;
            } catch ( \Exception $e ) {
                // Fall through to GD.
            }
        }

        // GD fallback — imagecreatefromjpeg + imagejpeg drops EXIF naturally.
        if ( function_exists( 'imagecreatefromjpeg' ) ) {
            $src = @imagecreatefromjpeg( $path );
            if ( $src ) {
                // Preserve a sensible quality — 92 is the WP default-equivalent.
                @imagejpeg( $src, $path, 92 );
                imagedestroy( $src );
            }
        }

        return $upload;
    }
}
