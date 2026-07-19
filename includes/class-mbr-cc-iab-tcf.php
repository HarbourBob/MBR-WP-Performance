<?php
/**
 * IAB Transparency & Consent Framework v2.3 Integration
 *
 * @package MBR_Cookie_Consent
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * IAB TCF class.
 */
class MBR_CC_IAB_TCF {
    
    /**
     * Single instance.
     *
     * @var MBR_CC_IAB_TCF
     */
    private static $instance = null;
    
    /**
     * TCF API version.
     *
     * @var int
     */
    const TCF_VERSION = 2;
    
    /**
     * TCF API patch version.
     *
     * @var int
     */
    const TCF_POLICY_VERSION = 4;
    
    /**
     * CMP ID (Consent Management Platform ID).
     * This should be your registered CMP ID from IAB.
     *
     * @var int
     */
    const CMP_ID = 0; // Set to your registered CMP ID
    
    /**
     * CMP Version.
     *
     * @var int
     */
    const CMP_VERSION = 1;
    
    /**
     * Get instance.
     *
     * @return MBR_CC_IAB_TCF
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        // Output TCF API stub and initialization.
        add_action('wp_head', array($this, 'output_tcf_api_stub'), 1);
        
        // Enqueue TCF scripts.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tcf_scripts'));
    }
    
    /**
     * Output TCF API stub.
     * This must load BEFORE any ad tags.
     */
    public function output_tcf_api_stub() {
        if (!get_option('mbr_cc_iab_tcf_enabled', false)) {
            return;
        }
        
        ?>
        <!-- IAB TCF v2.3 API Stub -->
        <script>
        (function() {
            var queue = window.__tcfapi || [];
            var win = window;
            var doc = document;
            
            function addFrame() {
                if (!win.frames['__tcfapiLocator']) {
                    if (doc.body) {
                        var iframe = doc.createElement('iframe');
                        iframe.style.cssText = 'display:none';
                        iframe.name = '__tcfapiLocator';
                        doc.body.appendChild(iframe);
                    } else {
                        setTimeout(addFrame, 5);
                    }
                }
            }
            addFrame();
            
            function tcfAPIHandler() {
                var gdprApplies;
                var args = arguments;
                
                if (!args.length) {
                    return queue;
                } else if (args[0] === 'setGdprApplies') {
                    if (args.length > 3 && args[2] === 2 && typeof args[3] === 'boolean') {
                        gdprApplies = args[3];
                        if (typeof args[2] === 'function') {
                            args[2]('set', true);
                        }
                    }
                } else if (args[0] === 'ping') {
                    var retr = {
                        gdprApplies: gdprApplies,
                        cmpLoaded: false,
                        cmpStatus: 'loading',
                        displayStatus: 'hidden',
                        apiVersion: '<?php echo (int) self::TCF_VERSION; ?>.0',
                        cmpVersion: <?php echo (int) self::CMP_VERSION; ?>,
                        cmpId: <?php echo (int) self::CMP_ID; ?>
                    };
                    
                    if (typeof args[2] === 'function') {
                        args[2](retr, true);
                    }
                } else {
                    queue.push(args);
                }
            }
            
            function postMessageEventHandler(event) {
                var msgIsString = typeof event.data === 'string';
                var json = {};
                
                try {
                    if (msgIsString) {
                        json = JSON.parse(event.data);
                    } else {
                        json = event.data;
                    }
                } catch (ignore) {}
                
                var payload = json.__tcfapiCall;
                
                if (payload) {
                    window.__tcfapi(
                        payload.command,
                        payload.version,
                        function(retValue, success) {
                            var returnMsg = {
                                __tcfapiReturn: {
                                    returnValue: retValue,
                                    success: success,
                                    callId: payload.callId
                                }
                            };
                            if (msgIsString) {
                                returnMsg = JSON.stringify(returnMsg);
                            }
                            if (event.source && event.source.postMessage) {
                                event.source.postMessage(returnMsg, '*');
                            }
                        },
                        payload.parameter
                    );
                }
            }
            
            if (typeof win.__tcfapi !== 'function') {
                win.__tcfapi = tcfAPIHandler;
                win.addEventListener('message', postMessageEventHandler, false);
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Enqueue TCF scripts.
     */
    public function enqueue_tcf_scripts() {
        if (!get_option('mbr_cc_iab_tcf_enabled', false)) {
            return;
        }
        
        wp_enqueue_script(
            'mbr-cc-tcf',
            MBR_CC_PLUGIN_URL . 'assets/js/tcf-handler.js',
            array('jquery', 'mbr-cc-banner'),
            MBR_CC_VERSION,
            true
        );
        
        wp_localize_script('mbr-cc-tcf', 'mbrCcTcf', array(
            'gdprApplies' => $this->get_gdpr_applies(),
            'publisherCC' => get_option('mbr_cc_publisher_country_code', ''),
            'purposeOneTreatment' => get_option('mbr_cc_purpose_one_treatment', false),
            'cmpId' => self::CMP_ID,
            'cmpVersion' => self::CMP_VERSION,
            'tcfPolicyVersion' => self::TCF_POLICY_VERSION,
        ));
    }
    
    /**
     * Determine if GDPR applies.
     *
     * @return bool GDPR applies.
     */
    private function get_gdpr_applies() {
        // Check if geo-location detection is enabled.
        $gdpr_applies = get_option('mbr_cc_gdpr_applies', 'auto');
        
        if ($gdpr_applies === 'yes') {
            return true;
        } elseif ($gdpr_applies === 'no') {
            return false;
        }
        
        // Auto-detect based on user location (simplified).
        // In production, you'd use proper geo-IP detection.
        return true; // Default to GDPR applying for safety.
    }
    
    /**
     * Get IAB TCF purposes.
     *
     * @return array Purposes with descriptions.
     */
    public static function get_tcf_purposes() {
        return array(
            1 => array(
                'name' => 'Store and/or access information on a device',
                'description' => 'Cookies, device identifiers, or other information can be stored or accessed on your device for the purposes presented to you.',
                'illustration' => 'Most purposes in this notice rely on the storage or accessing of information from your device when you use an app or visit a website.',
            ),
            2 => array(
                'name' => 'Use limited data to select advertising',
                'description' => 'Advertising presented to you on this service can be based on limited data, such as the website or app you are using, your non-precise location, your device type or which content you are (or have been) interacting with.',
                'illustration' => 'A car manufacturer wants to promote its electric vehicles to environmentally conscious users living in the city after office hours.',
            ),
            3 => array(
                'name' => 'Create profiles for personalised advertising',
                'description' => 'Information about your activity on this service can be used to build or improve a profile about you for personalised advertising.',
                'illustration' => 'Certain characteristics about you are used to determine which advertising is presented to you.',
            ),
            4 => array(
                'name' => 'Use profiles to select personalised advertising',
                'description' => 'Advertising presented to you on this service can be based on your advertising profiles.',
                'illustration' => 'An advertiser wants to show users ads based on whether they have previously been shown certain ads.',
            ),
            5 => array(
                'name' => 'Create profiles to personalise content',
                'description' => 'Information about your activity on this service can be used to build or improve a profile about you for personalised content.',
                'illustration' => 'A video website builds a profile based on videos you watch.',
            ),
            6 => array(
                'name' => 'Use profiles to select personalised content',
                'description' => 'Content presented to you on this service can be based on your content personalisation profiles.',
                'illustration' => 'You read several articles on a news website and are shown articles based on your reading history.',
            ),
            7 => array(
                'name' => 'Measure advertising performance',
                'description' => 'Information regarding which advertising is presented to you and how you interact with it can be used to determine how well an advert has worked for you or other users.',
                'illustration' => 'An advertiser wants to measure whether ads were shown to real people, not bots.',
            ),
            8 => array(
                'name' => 'Measure content performance',
                'description' => 'Information regarding which content is presented to you and how you interact with it can be used to determine whether content has worked for you.',
                'illustration' => 'A video platform wants to count how many times a video has been watched to help determine which videos to show.',
            ),
            9 => array(
                'name' => 'Understand audiences through statistics or combinations of data from different sources',
                'description' => 'Reports can be generated based on the combination of data sets regarding your interactions with advertising or content.',
                'illustration' => 'An advertiser wants to understand the demographics of people engaging with ads.',
            ),
            10 => array(
                'name' => 'Develop and improve services',
                'description' => 'Information about your activity on this service can be used to improve it.',
                'illustration' => 'A technology platform improves its features based on user behavior.',
            ),
            11 => array(
                'name' => 'Use limited data to select content',
                'description' => 'Content presented to you on this service can be based on limited data.',
                'illustration' => 'News articles are shown based on your general location.',
            ),
        );
    }
    
    /**
     * Get IAB TCF special features.
     *
     * @return array Special features.
     */
    public static function get_tcf_special_features() {
        return array(
            1 => array(
                'name' => 'Use precise geolocation data',
                'description' => 'Your precise geolocation data can be used in support of one or more purposes.',
            ),
            2 => array(
                'name' => 'Actively scan device characteristics for identification',
                'description' => 'Your device can be identified based on a scan of your device\'s unique combination of characteristics.',
            ),
        );
    }
    
    /**
     * Generate TC String (Transparency & Consent String).
     *
     * @param array $consent Consent data.
     * @return string TC String.
     */
    public function generate_tc_string($consent) {
        // In production, this would generate a proper IAB TC String.
        // This is a placeholder - use IAB's official encoder library.
        return 'CO-placeholder-TC-String';
    }
}
