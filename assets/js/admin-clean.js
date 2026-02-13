/**
 * MBR WP Performance Admin JavaScript - Clean Rebuild
 */

console.log('==========================================');
console.log('MBR WP Performance JS FILE IS LOADING...');
console.log('==========================================');

(function($) {
    'use strict';
    
    console.log('Inside IIFE - jQuery available:', typeof $ !== 'undefined');

    const MBR_WP_Performance_Admin = {
        
        /**
         * Initialize
         */
        init: function() {
            console.log('MBR_WP_Performance_Admin.init() called');
            this.bindEvents();
            console.log('MBR_WP_Performance_Admin.init() complete');
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            var self = this;
            
            // CRITICAL: Prevent form submission while debug message is showing
            $('.mbr-wp-performance-form').on('submit', function(e) {
                if (window.mbrDebugActive) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Cannot save settings while debug message is displayed. Please read the message first.');
                    return false;
                }
            });
            
            // Reset to defaults
            $('.mbr-wp-performance-reset').on('click', function(e) { self.resetSettings.call(this, e); });
            
            // Database operations
            $('#clean-revisions').on('click', function(e) { self.cleanRevisions.call(this, e); });
            $('#scan-post-meta').on('click', function(e) { self.scanPostMeta.call(this, e); });
            $('#delete-post-meta').on('click', function(e) { self.deletePostMeta.call(this, e); });
            $('#scan-comment-meta').on('click', function(e) { self.scanCommentMeta.call(this, e); });
            $('#delete-comment-meta').on('click', function(e) { self.deleteCommentMeta.call(this, e); });
            $('#scan-relationships').on('click', function(e) { self.scanRelationships.call(this, e); });
            $('#delete-relationships').on('click', function(e) { self.deleteRelationships.call(this, e); });
            $('#scan-term-meta').on('click', function(e) { self.scanTermMeta.call(this, e); });
            $('#delete-term-meta').on('click', function(e) { self.deleteTermMeta.call(this, e); });
            $('#get-transient-stats').on('click', function(e) { self.getTransientStats.call(this, e); });
            $('#delete-expired-transients').on('click', function(e) { self.deleteExpiredTransients.call(this, e); });
            $('#delete-all-transients').on('click', function(e) { self.deleteAllTransients.call(this, e); });
            $('#optimize-tables').on('click', function(e) { self.optimizeTables.call(this, e); });
            $('#convert-innodb').on('click', function(e) { self.convertToInnoDB.call(this, e); });
            $('#repair-tables').on('click', function(e) { self.repairTables.call(this, e); });
            $('#get-db-info').on('click', function(e) { self.getDatabaseInfo.call(this, e); });
            
            // CSS operations
            $('#generate-critical-css').on('click', function(e) { self.generateCriticalCSS.call(this, e); });
            $('#scan-css').on('click', function(e) { self.scanCSS.call(this, e); });
            $('#clear-scan-data').on('click', function(e) { self.clearScanData.call(this, e); });
            
            // Font operations
            $('#download-manual-fonts').on('click', function(e) { self.downloadManualFonts.call(this, e); });
            $('#clear-font-cache').on('click', function(e) { self.clearFontCache.call(this, e); });
        },

        /**
         * Show loading spinner
         */
        showLoading: function($button) {
            $button.prop('disabled', true);
            $button.after('<span class="mbr-wp-performance-loading"></span>');
        },

        /**
         * Hide loading spinner
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
            var cssClass = type === 'success' ? 'notice-success' : 'notice-error';
            $container.html('<div class="notice ' + cssClass + ' inline"><p>' + message + '</p></div>');
        },

        /**
         * Reset settings to defaults
         */
        resetSettings: function(e) {
            e.preventDefault();
            if (!confirm(mbrWpPerformance.i18n.confirmReset)) {
                return;
            }
            // Reset form - let WordPress handle this naturally
            window.location.href = window.location.href + '&reset=1';
        },

        /**
         * Clear font cache - TESTED AND WORKING
         */
        clearFontCache: function(e) {
            console.log('STEP 1: clearFontCache function called');
            e.preventDefault();
            e.stopPropagation();
            console.log('STEP 2: preventDefault called');
            
            // SET FLAG TO PREVENT FORM SUBMISSION
            window.mbrDebugActive = true;
            
            var $button = $(this);
            var $status = $('#clear-font-status');
            console.log('STEP 3: Button and status elements found');
            
            if (!confirm('Are you sure you want to delete ALL downloaded fonts and reset the configuration? This cannot be undone.')) {
                console.log('STEP 4: User cancelled');
                window.mbrDebugActive = false;
                return;
            }
            console.log('STEP 5: User confirmed, proceeding...');
            
            // PREVENT ANY PAGE RELOADS/NAVIGATION
            window.onbeforeunload = function() {
                console.log('BLOCKED: Page tried to reload/navigate!');
                return "Debug message is being displayed. Are you sure you want to leave?";
            };
            console.log('STEP 6: Reload blocker installed');
            
            $status.html('');
            var originalText = $button.text();
            $button.text('Clearing...').prop('disabled', true);
            console.log('STEP 7: Button updated, sending AJAX...');
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_clear_font_cache',
                nonce: mbrWpPerformance.nonce
            }, function(response) {
                console.log('STEP 8: AJAX response received');
                console.log('Response:', response);
                
                $button.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    console.log('STEP 9: Success!');
                    console.log('=== CLEAR FONT CACHE DEBUG ===');
                    console.log(response.data.message);
                    console.log('=== END DEBUG ===');
                    
                    // Show in a BIG obvious div that can't be missed
                    $status.html('<div style="background: #d4edda; border: 3px solid #28a745; padding: 30px; margin: 20px 0; font-size: 14px;"><strong style="color: green; font-size: 18px;">✓ CACHE CLEARED!</strong><br><br><div style="font-family: monospace; white-space: pre-wrap; background: white; padding: 15px; border: 1px solid #ccc;">' + response.data.message + '</div><br><br><strong style="color: red; font-size: 16px;">⚠️ READ THE MESSAGE ABOVE - DO NOT RELOAD YET!</strong><br><br><button type="button" onclick="window.mbrDebugActive=false; window.onbeforeunload=null; location.reload();" class="button button-primary" style="font-size: 16px; padding: 10px 20px;">I Have Read It - Reload Page Now</button></div>');
                    
                    console.log('STEP 10: Message displayed, function complete');
                } else {
                    console.error('STEP 9: Error response:', response.data.message);
                    window.mbrDebugActive = false;
                    $status.html('<div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px;"><strong>Error:</strong> ' + (response.data.message || 'An error occurred') + '</div>');
                }
            }).fail(function(xhr, status, error) {
                console.error('STEP 8: AJAX FAILED');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR:', xhr);
                
                window.mbrDebugActive = false;
                $button.text(originalText).prop('disabled', false);
                $status.html('<div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px;"><strong>AJAX Error:</strong> ' + error + '</div>');
            });
            
            console.log('STEP 11: AJAX call initiated, waiting for response...');
        },

        /**
         * Download manual fonts
         */
        downloadManualFonts: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#manual-font-status');
            var manualFonts = $('#manual_fonts').val();
            
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
         * Clean revisions
         */
        cleanRevisions: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#revision-stats');
            
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
            var $button = $(this);
            var $status = $('#post-meta-stats');
            var $deleteButton = $('#delete-post-meta');
            
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
            var $button = $(this);
            var $status = $('#post-meta-stats');
            
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
            var $button = $(this);
            var $status = $('#comment-meta-stats');
            var $deleteButton = $('#delete-comment-meta');
            
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
            var $button = $(this);
            var $status = $('#comment-meta-stats');
            
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
            var $button = $(this);
            var $status = $('#relationship-stats');
            var $deleteButton = $('#delete-relationships');
            
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
            var $button = $(this);
            var $status = $('#relationship-stats');
            
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
            var $button = $(this);
            var $status = $('#term-meta-stats');
            var $deleteButton = $('#delete-term-meta');
            
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
            var $button = $(this);
            var $status = $('#term-meta-stats');
            
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
            var $button = $(this);
            var $status = $('#transient-stats');
            
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
            var $button = $(this);
            var $status = $('#transient-stats');
            
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
            var $button = $(this);
            var $status = $('#transient-stats');
            
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
            var $button = $(this);
            var $status = $('#optimization-status');
            
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
            var $button = $(this);
            var $status = $('#innodb-status');
            
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
            });
        },

        /**
         * Repair tables
         */
        repairTables: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#repair-status');
            
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
            var $button = $(this);
            var $status = $('#db-info');
            
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
            var $button = $(this);
            var $status = $('#critical-css-status');
            
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
            var $button = $(this);
            var $status = $('#scan-status');
            
            MBR_WP_Performance_Admin.showLoading($button);
            
            $.post(mbrWpPerformance.ajaxUrl, {
                action: 'mbr_wp_performance_scan_css',
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
         * Clear scan data
         */
        clearScanData: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $status = $('#scan-status');
            
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
        }
    };

    // Initialize on document ready
    console.log('Registering document.ready handler...');
    $(document).ready(function() {
        console.log('DOCUMENT READY FIRED!');
        MBR_WP_Performance_Admin.init();
        console.log('After init call');
    });
    
    console.log('End of IIFE');

})(jQuery);

console.log('==========================================');
console.log('MBR WP Performance JS FILE LOADED!');
console.log('==========================================');
