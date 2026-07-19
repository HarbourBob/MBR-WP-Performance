(function($) {
    'use strict';
    
    var MbrGoogleACM = {
        
        acString: '',
        
        init: function() {
            this.loadACString();
            this.bindEvents();
            this.initializeGoogleACM();
        },
        
        initializeGoogleACM: function() {
            var self = this;
            
            // Set up Google ACM
            window.googlefc = window.googlefc || {};
            window.googlefc.ccpa = window.googlefc.ccpa || {};
            
            // Apply initial AC String
            if (this.acString) {
                this.applyACString(this.acString);
            }
        },
        
        loadACString: function() {
            // Load AC String from cookie
            this.acString = this.getCookie('mbr_cc_ac_string') || '';
        },
        
        buildACString: function(consent) {
            // Build Additional Consent String from consent data
            var consentedProviders = [];
            
            if (consent.all || consent.marketing) {
                // If marketing is consented, include all Google ATP providers
                // In production, you'd get the actual list from settings
                consentedProviders = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            }
            
            if (consentedProviders.length === 0) {
                return '';
            }
            
            // AC String format: 1~{provider_ids}
            return '1~' + consentedProviders.join('.');
        },
        
        applyACString: function(acString) {
            // Apply AC String to Google tags
            if (typeof window.gtag === 'function') {
                window.gtag('consent', 'update', {
                    'ad_user_data': acString ? 'granted' : 'denied',
                    'ad_personalization': acString ? 'granted' : 'denied'
                });
            }
            
            // Update Google FC (Funding Choices)
            if (window.googlefc && window.googlefc.callbackQueue) {
                window.googlefc.ccpa = window.googlefc.ccpa || {};
                window.googlefc.ccpa.addtlConsent = acString;
            }
            
            // Store AC String
            this.saveACString(acString);
        },
        
        saveACString: function(acString) {
            this.acString = acString;
            
            // Save to cookie
            var expires = new Date();
            expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000));
            
            var domain = '';
            if (typeof mbrCcConsent !== 'undefined' && mbrCcConsent.cookieDomain) {
                domain = '; domain=' + mbrCcConsent.cookieDomain;
            }
            
            document.cookie = 'mbr_cc_ac_string=' + acString + 
                            '; expires=' + expires.toUTCString() + 
                            domain + 
                            '; path=/; SameSite=Lax';
        },
        
        bindEvents: function() {
            var self = this;
            
            // When consent is saved, update AC String
            $(document).on('mbr_cc_consent_saved', function(e, consent) {
                var acString = self.buildACString(consent);
                self.applyACString(acString);
            });
        },
        
        getCookie: function(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },
        
        // Public API
        getACString: function() {
            return this.acString;
        },
        
        isProviderConsented: function(providerId) {
            if (!this.acString) {
                return false;
            }
            
            var parts = this.acString.split('~');
            if (parts.length !== 2) {
                return false;
            }
            
            var providers = parts[1].split('.').map(function(id) {
                return parseInt(id, 10);
            });
            
            return providers.indexOf(providerId) !== -1;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if (typeof mbrCcGoogleACM !== 'undefined' && mbrCcGoogleACM.enabled) {
            MbrGoogleACM.init();
        }
    });
    
    // Expose API
    window.MbrGoogleACM = MbrGoogleACM;
    
})(jQuery);
