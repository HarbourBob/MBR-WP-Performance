(function($) {
    'use strict';
    
    window.MbrCcConsent = {
        
        /**
         * Get current user consent.
         */
        getConsent: function(callback) {
            $.ajax({
                url: mbrCcConsent.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_get_consent',
                    nonce: mbrCcConsent.nonce
                },
                success: function(response) {
                    if (response.success && callback) {
                        callback(response.data.consent);
                    }
                }
            });
        },
        
        /**
         * Check if user has consent.
         */
        hasConsent: function(callback) {
            this.getConsent(function(consent) {
                if (callback) {
                    callback(consent && Object.keys(consent).length > 0);
                }
            });
        },
        
        /**
         * Check if specific category is allowed.
         */
        hasCategoryConsent: function(category, callback) {
            this.getConsent(function(consent) {
                if (callback) {
                    var allowed = consent && (consent.all === true || consent[category] === true);
                    callback(allowed);
                }
            });
        },
        
        /**
         * Revoke user consent.
         */
        revokeConsent: function(callback) {
            $.ajax({
                url: mbrCcConsent.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mbr_cc_revoke_consent',
                    nonce: mbrCcConsent.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Delete cookie
                        document.cookie = mbrCcConsent.cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                        
                        if (callback) {
                            callback(true);
                        }
                        
                        // Reload page
                        if (mbrCcConsent.reloadOnConsent) {
                            location.reload();
                        }
                    } else if (callback) {
                        callback(false);
                    }
                }
            });
        }
    };
    
})(jQuery);
