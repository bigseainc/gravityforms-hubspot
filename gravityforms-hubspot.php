<?php
	/*
		Plugin Name: Hubspot for Gravity Forms
		Plugin URI: http://bigsea.co/
		Description: Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.
		Version: 3.0.2
		Author: Big Sea
		Author URI: http://bigsea.co
	*/

    // Constants
    define('GF_HUBSPOT_BASENAME', plugin_basename(__FILE__));
    define('GF_HUBSPOT_PATH', WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/");
    define('GF_HUBSPOT_VERSION', '3.0.2');
    if ( !defined('GF_HUBSPOT_DEBUG') ) define('GF_HUBSPOT_DEBUG', false);

    // Start up the plugin after GravityForms is loaded.
    add_action( 'gform_loaded', array( 'GF_HubSpot_Bootstrap', 'load' ), 5 );

    // Activation hooks
    register_activation_hook( __FILE__, array('GF_HubSpot_Bootstrap', 'activate') );
    register_deactivation_hook( __FILE__, array('GF_HubSpot_Bootstrap', 'deactivate') );

    class GF_HubSpot_Bootstrap {

        const MESSAGE_REQUIRED = 'HubSpot for Gravity Forms requires PHP 5.5 or newer, with cURL enabled.';

        public static function load() {
            if (self::meetsMinimumRequirements()) {
                // Let's get rolling!
                require_once( GF_HUBSPOT_PATH . 'vendor/autoload.php' );
                // BigSea\GFHubSpot\Tracking::log('Plugin Booted Up');
                GFAddOn::register( '\BigSea\GFHubSpot\GF_HubSpot' );
            } else {
                add_action( 'admin_notices', array('GF_HubSpot_Bootstrap', 'adminNotices') );
            }
        } // function

        private static function meetsMinimumRequirements() {
            if (version_compare(PHP_VERSION, '5.5.0', "<")) {
                return false;
            }

            if (!function_exists('curl_reset')) {
                return false;
            }

            return true;
        }

        public static function adminNotices() {
            if (!self::meetsMinimumRequirements()) {
                echo '<div class="notice notice-error">
                    <p>'.self::MESSAGE_REQUIRED.'</p>
                </div>';
            }
        }

        /**
         *  activate ()
         *
         *      Activate the plugin, installing any necessary functionality.
         *
         *  @param none
         *  @return boolean
         */
        public static function activate () {
            if (!self::meetsMinimumRequirements()) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die( self::MESSAGE_REQUIRED );
            }

            $old_version = get_option('gf_bsdhubspot_plugin_version');
            if ( $old_version === FALSE ) $old_version = GF_HUBSPOT_VERSION; // We don't need to migrate, they've never installed.

            // if we need to update something, per version:
            // if ( version_compare($old_version, $version_to_match, "<") )

            update_option('gf_bsdhubspot_plugin_version', GF_HUBSPOT_VERSION);

        } // function

        public static function deactivate () {
            wp_clear_scheduled_hook( 'bsd_gfhs_oauth_cron' );
            wp_clear_scheduled_hook( 'gravityforms_hubspot_oauth_cron');
        } // function

    } // class

    function gf_hubspot() {
        return \BigSea\GFHubSpot\GF_HubSpot::get_instance();
    }
