/**
 * MBR Performance Admin JavaScript
 */

(function($) {
    'use strict';

    const MBRPE_Admin = {
        
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
            $('.mbr-performance-reset').on('click', this.resetSettings);
            
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
            $button.after('<span class="mbr-performance-loading"></span>');
        },

        /**
         * Hide loading
         */
        hideLoading: function($button) {
            $button.prop('disabled', false);
            $button.next('.mbr-performance-loading').remove();
        },

        /**
         * Show message
         */
        showMessage: function($container, message, type) {
            type = type || 'success';
            $container.html('<div class="mbr-performance-message ' + type + '">' + message + '</div>');
        },

        /**
         * Reset settings
         */
        resetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm(mbrpeData.i18n.confirmReset)) {
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clean_revisions',
                nonce: mbrpeData.nonce,
                keep: $('#keep_revisions').val()
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_post_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_post_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_comment_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_comment_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_relationships',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_relationships',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_term_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, 'Found: ' + response.data.count + ' orphaned entries', 'success');
                    if (response.data.count > 0) {
                        $deleteButton.prop('disabled', false);
                    }
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_term_meta',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                    $button.prop('disabled', true);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_transient_stats',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_expired_transients',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_delete_all_transients',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_optimize_tables',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            $status.html('');
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_convert_innodb',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message || 'An error occurred', 'error');
                }
            }).fail(function(xhr, status, error) {
                MBRPE_Admin.hideLoading($button);
                MBRPE_Admin.showMessage($status, 'AJAX Error: ' + error, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_repair_tables',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_db_info',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    $status.html(response.data.html);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_scan_css',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clear_scan_data',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_download_fonts',
                nonce: mbrpeData.nonce
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
                MBRPE_Admin.showMessage($status, 'Please enter fonts to download', 'error');
                return;
            }
            
            MBRPE_Admin.showLoading($button);
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_download_manual_fonts',
                nonce: mbrpeData.nonce,
                manual_fonts: manualFonts
            }, function(response) {
                MBRPE_Admin.hideLoading($button);
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message, 'success');
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message, 'error');
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
            
            $.post(mbrpeData.ajaxUrl, {
                action: 'mbrpe_clear_font_cache',
                nonce: mbrpeData.nonce
            }, function(response) {
                $button.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    MBRPE_Admin.showMessage($status, response.data.message + ' Reloading page...', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    MBRPE_Admin.showMessage($status, response.data.message || 'An error occurred', 'error');
                }
            }).fail(function(xhr, status, error) {
                $button.text(originalText).prop('disabled', false);
                MBRPE_Admin.showMessage($status, 'AJAX Error: ' + error, 'error');
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MBRPE_Admin.init();
    });

})(jQuery);
