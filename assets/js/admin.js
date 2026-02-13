/**
 * MBR WP Performance Admin JavaScript
 */

(function($) {
    'use strict';

    const MBR_WP_Performance_Admin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Reset to defaults
            $('.mbr-wp-performance-reset').on('click', this.resetSettings);
            
            // Database operations
            $('#clean-revisions').on('click', this.cleanRevisions);
            $('#scan-post-meta').on('click', this.scanPostMeta);
            $('#delete-post-meta').on('click', this.deletePostMeta);
            $('#scan-comment-meta').on('click', this.scanCommentMeta);
            $('#delete-comment-meta').on('click', this.deleteCommentMeta);
            $('#scan-relationships').on('click', this.scanRelationships);
            $('#delete-relationships').on('click', this.deleteRelationships);
            $('#scan-term-meta').on('click', this.scanTermMeta);
            $('#delete-term-meta').on('click', this.deleteTermMeta);
            $('#get-transient-stats').on('click', this.getTransientStats);
            $('#delete-expired-transients').on('click', this.deleteExpiredTransients);
            $('#delete-all-transients').on('click', this.deleteAllTransients);
            $('#optimize-tables').on('click', this.optimizeTables);
            $('#convert-innodb').on('click', this.convertToInnoDB);
            $('#repair-tables').on('click', this.repairTables);
            $('#get-db-info').on('click', this.getDatabaseInfo);
            
            // CSS operations
            $('#generate-critical-css').on('click', this.generateCriticalCSS);
            $('#scan-css').on('click', this.scanCSS);
            $('#clear-scan-data').on('click', this.clearScanData);
            
            // Font operations
            $('#download-fonts').on('click', this.downloadFonts);
            $('#download-manual-fonts').on('click', this.downloadManualFonts);
            
            // Clear font cache with multiple binding methods (same as test version that works)
            var self = this;
            var $clearButton = $('#clear-font-cache');
            console.log('Looking for #clear-font-cache button, found:', $clearButton.length, 'elements');
            
            if ($clearButton.length > 0) {
                console.log('Attaching click handler to clear font cache button');
                
                // Method 1: Direct click
                $clearButton.on('click', function(e) {
                    console.log('Clear font cache clicked (direct)');
                    self.clearFontCache.call(this, e);
                });
                
                // Method 2: Event delegation (backup)
                $(document).on('click', '#clear-font-cache', function(e) {
                    console.log('Clear font cache clicked (delegated)');
                    // Don't call twice if Method 1 worked
                });
                
                // Method 3: Native listener (backup)
                $clearButton[0].addEventListener('click', function(e) {
                    console.log('Clear font cache clicked (native)');
                    // Don't call twice if Method 1 worked
                });
            } else {
                console.error('ERROR: #clear-font-cache button not found in DOM!');
            }
        },

        /**
         * Show loading
         */
        showLoading: function($button) {
            $button.prop('disabled', true);
            $button.after('<span class="mbr-wp-performance-loading"></span>');
        },

        /**
         * Hide loading
         */
        hideLoading: function($button) {
            $button.prop('disabled', false);
            $button.next('.mbr-wp-performance-loading').remove();
        },

        /**
         * Show message
         */
        showMessage: function($container, message, type) {
            type = type || 'success';
            $container.html('<div class="mbr-wp-performance-message ' + type + '">' + message + '</div>');
        },

        /**
         * Reset settings
         */
        resetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm(mbrWpPerformance.i18n.confirmReset)) {
                return;
            }
            
            // Reset form
            $(this).closest('form')[0].reset();
            
            // Submit form
            $(this).closest('form').submit();
        },

        /**
         * Clean revisions
         */
        cleanRevisions: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#revision-stats');
            
            if (!confirm('Are you sure you want to delete excess revisions?')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clean_revisions',
                nonce: mbrWpPerformance.nonce,
                keep: $('#keep_revisions').val()
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan post meta
         */
        scanPostMeta: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#post-meta-stats');
            const $deleteButton = $('#delete-post-meta');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_post_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete post meta
         */
        deletePostMeta: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#post-meta-stats');
            
            if (!confirm('Are you sure you want to delete orphaned post meta?')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_post_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan comment meta
         */
        scanCommentMeta: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#comment-meta-stats');
            const $deleteButton = $('#delete-comment-meta');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_comment_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete comment meta
         */
        deleteCommentMeta: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#comment-meta-stats');
            
            if (!confirm('Are you sure you want to delete orphaned comment meta?')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_comment_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan relationships
         */
        scanRelationships: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#relationship-stats');
            const $deleteButton = $('#delete-relationships');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_relationships',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete relationships
         */
        deleteRelationships: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#relationship-stats');
            
            if (!confirm('Are you sure you want to delete orphaned relationships?')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_relationships',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan term meta
         */
        scanTermMeta: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#term-meta-stats');
            const $deleteButton = $('#delete-term-meta');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_term_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete term meta
         */
        deleteTermMeta: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#term-meta-stats');
            
            if (!confirm('Are you sure you want to delete orphaned term meta?')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_term_meta',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Get transient stats
         */
        getTransientStats: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#transient-stats');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_transient_stats',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete expired transients
         */
        deleteExpiredTransients: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#transient-stats');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_expired_transients',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Delete all transients
         */
        deleteAllTransients: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#transient-stats');
            
            if (!confirm('Are you sure? This may cause temporary performance drop.')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_delete_all_transients',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Optimize tables
         */
        optimizeTables: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#optimization-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_optimize_tables',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Convert to InnoDB
         */
        convertToInnoDB: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#innodb-status');
            
            if (!confirm('Are you sure you want to convert MyISAM tables to InnoDB? Test on a staging site first!')) {
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            $status.html('');
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_convert_innodb',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message || 'An error occurred', 'error');
                }
            }).fail(function(xhr, status, error) {
                MBR_WP_Performance_Admin.hideLoading($button);
                MBR_WP_Performance_Admin.showMessage($status, 'AJAX Error: ' + error, 'error');
                console.error('Convert InnoDB error:', xhr.responseText);
            });
        },

        /**
         * Repair tables
         */
        repairTables: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#repair-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_repair_tables',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Get database info
         */
        getDatabaseInfo: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#db-info');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_db_info',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    $status.html(response.data.html);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Generate critical CSS
         */
        generateCriticalCSS: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#critical-css-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_generate_critical_css',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    $('#critical_css').val(response.data.css);
                    MBR_WP_Performance_Admin.showMessage($status, 'Critical CSS generated successfully', 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Scan CSS
         */
        scanCSS: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#scan-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_css',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Clear scan data
         */
        clearScanData: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#scan-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clear_scan_data',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Download fonts
         */
        downloadFonts: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#font-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_download_fonts',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },
        
        /**
         * Download manual fonts
         */
        downloadManualFonts: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#manual-font-status');
            const manualFonts = $('#manual_fonts').val();
            
            if (!manualFonts) {
                MBR_WP_Performance_Admin.showMessage($status, 'Please enter fonts to download', 'error');
                return;
            }
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_download_manual_fonts',
                nonce: mbrWpPerformance.nonce,
                manual_fonts: manualFonts
            }, function(response) {
                MBR_WP_Performance_Admin.hideLoading($button);
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message, 'error');
                }
            });
        },

        /**
         * Clear font cache
         */
        clearFontCache: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(this);
            const $status = $('#clear-font-status');
            
            if (!confirm('Are you sure you want to delete ALL downloaded fonts and reset the configuration? This cannot be undone.')) {
                return;
            }
            
            // Clear any previous messages
            $status.html('');
            
            // Update button text and show loading
            const originalText = $button.text();
            $button.text('Clearing...').prop('disabled', true);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clear_font_cache',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                $button.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message + ' Reloading page...', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    MBR_WP_Performance_Admin.showMessage($status, response.data.message || 'An error occurred', 'error');
                }
            }).fail(function(xhr, status, error) {
                $button.text(originalText).prop('disabled', false);
                MBR_WP_Performance_Admin.showMessage($status, 'AJAX Error: ' + error, 'error');
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MBR_WP_Performance_Admin.init();
    });

})(jQuery);
