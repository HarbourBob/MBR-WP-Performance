(function($) {
    'use strict';
    
    var MbrTcfHandler = {
        
        tcData: null,
        
        init: function() {
            this.setupTcfApi();
            this.bindEvents();
        },
        
        setupTcfApi: function() {
            var self = this;
            
            // Override __tcfapi with full implementation
            window.__tcfapi = function(command, version, callback, parameter) {
                if (command === 'ping') {
                    callback({
                        gdprApplies: mbrCcTcf.gdprApplies,
                        cmpLoaded: true,
                        cmpStatus: 'loaded',
                        displayStatus: self.getDisplayStatus(),
                        apiVersion: '2.0',
                        cmpVersion: mbrCcTcf.cmpVersion,
                        cmpId: mbrCcTcf.cmpId,
                        tcfPolicyVersion: mbrCcTcf.tcfPolicyVersion
                    }, true);
                } else if (command === 'getTCData') {
                    self.getTCData(callback, parameter);
                } else if (command === 'getVendorList') {
                    self.getVendorList(callback, parameter);
                } else if (command === 'addEventListener') {
                    self.addEventListener(callback);
                } else if (command === 'removeEventListener') {
                    self.removeEventListener(callback, parameter);
                }
            };
        },
        
        getDisplayStatus: function() {
            // Check if banner is currently visible
            if ($('#mbr-cc-banner').is(':visible')) {
                return 'visible';
            }
            
            // Check if consent has been given
            var consent = this.getCookie('mbr_cc_consent');
            if (consent) {
                return 'hidden';
            }
            
            return 'disabled';
        },
        
        getTCData: function(callback, vendorIds) {
            var self = this;
            var consent = this.getCookie('mbr_cc_consent');
            
            if (!consent) {
                // No consent yet
                callback({
                    tcString: '',
                    tcfPolicyVersion: mbrCcTcf.tcfPolicyVersion,
                    cmpId: mbrCcTcf.cmpId,
                    cmpVersion: mbrCcTcf.cmpVersion,
                    gdprApplies: mbrCcTcf.gdprApplies,
                    eventStatus: 'tcloaded',
                    cmpStatus: 'loaded',
                    listenerId: null,
                    isServiceSpecific: true,
                    useNonStandardTexts: false,
                    publisherCC: mbrCcTcf.publisherCC,
                    purposeOneTreatment: mbrCcTcf.purposeOneTreatment,
                    outOfBand: {
                        allowedVendors: {},
                        disclosedVendors: {}
                    },
                    purpose: {
                        consents: {},
                        legitimateInterests: {}
                    },
                    vendor: {
                        consents: {},
                        legitimateInterests: {}
                    },
                    specialFeatureOptins: {},
                    publisher: {
                        consents: {},
                        legitimateInterests: {},
                        customPurpose: {
                            consents: {},
                            legitimateInterests: {}
                        },
                        restrictions: {}
                    }
                }, true);
                return;
            }
            
            // Build TC Data from consent
            var consentData = JSON.parse(consent);
            var tcData = this.buildTCData(consentData);
            
            callback(tcData, true);
        },
        
        buildTCData: function(consentData) {
            // Convert MBR consent to TCF format
            var purposes = {};
            var vendors = {};
            
            // Map consent categories to TCF purposes
            if (consentData.marketing) {
                purposes[2] = true; // Select advertising
                purposes[3] = true; // Personalized advertising profiles
                purposes[4] = true; // Use personalized advertising
                purposes[7] = true; // Measure advertising
            }
            
            if (consentData.analytics) {
                purposes[8] = true; // Measure content performance
                purposes[9] = true; // Understand audiences
            }
            
            if (consentData.preferences) {
                purposes[5] = true; // Personalize content profiles
                purposes[6] = true; // Use personalized content
            }
            
            // Always include Purpose 1 (Store/access info) if any consent given
            if (consentData.all || consentData.marketing || consentData.analytics || consentData.preferences) {
                purposes[1] = true;
            }
            
            // Generate TC String (placeholder - would use IAB encoder in production)
            var tcString = this.generateTCString(purposes, vendors);
            
            return {
                tcString: tcString,
                tcfPolicyVersion: mbrCcTcf.tcfPolicyVersion,
                cmpId: mbrCcTcf.cmpId,
                cmpVersion: mbrCcTcf.cmpVersion,
                gdprApplies: mbrCcTcf.gdprApplies,
                eventStatus: 'useractioncomplete',
                cmpStatus: 'loaded',
                listenerId: null,
                isServiceSpecific: true,
                useNonStandardTexts: false,
                publisherCC: mbrCcTcf.publisherCC,
                purposeOneTreatment: mbrCcTcf.purposeOneTreatment,
                outOfBand: {
                    allowedVendors: {},
                    disclosedVendors: {}
                },
                purpose: {
                    consents: purposes,
                    legitimateInterests: {}
                },
                vendor: {
                    consents: vendors,
                    legitimateInterests: {}
                },
                specialFeatureOptins: {},
                publisher: {
                    consents: {},
                    legitimateInterests: {},
                    customPurpose: {
                        consents: {},
                        legitimateInterests: {}
                    },
                    restrictions: {}
                }
            };
        },
        
        generateTCString: function(purposes, vendors) {
            // In production, use IAB's official TC String encoder
            // This is a placeholder
            return 'COw4XqLOw4XqLAAAAAENAPCgAAAAAAAAAAAAAAAAAAAA';
        },
        
        getVendorList: function(callback, vendorListVersion) {
            // In production, fetch from IAB's GVL (Global Vendor List)
            // https://vendor-list.consensu.org/v2/vendor-list.json
            callback({
                vendorListVersion: 113,
                lastUpdated: new Date().toISOString(),
                vendors: {},
                purposes: {},
                specialPurposes: {},
                features: {},
                specialFeatures: {}
            }, true);
        },
        
        addEventListener: function(callback) {
            // Add event listener for TC changes
            var listenerId = Math.random();
            
            $(document).on('mbr_cc_consent_updated', function() {
                var consent = this.getCookie('mbr_cc_consent');
                if (consent) {
                    var consentData = JSON.parse(consent);
                    var tcData = this.buildTCData(consentData);
                    tcData.listenerId = listenerId;
                    tcData.eventStatus = 'useractioncomplete';
                    callback(tcData, true);
                }
            }.bind(this));
            
            return listenerId;
        },
        
        removeEventListener: function(callback, listenerId) {
            // Remove event listener
            callback(true);
        },
        
        bindEvents: function() {
            var self = this;
            
            // When consent is saved, trigger TCF update
            $(document).on('mbr_cc_consent_saved', function(e, consent) {
                self.updateTCData(consent);
                $(document).trigger('mbr_cc_consent_updated');
            });
        },
        
        updateTCData: function(consent) {
            this.tcData = this.buildTCData(consent);
            
            // Notify all TCF listeners
            if (typeof window.__tcfapi === 'function') {
                window.__tcfapi('getTCData', 2, function() {});
            }
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
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if (typeof mbrCcTcf !== 'undefined' && mbrCcTcf.enabled) {
            MbrTcfHandler.init();
        }
    });
    
})(jQuery);
