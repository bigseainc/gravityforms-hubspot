<?php
	/*
		Plugin Name: Hubspot for Gravity Forms
		Plugin URI: http://bigsea.co/
		Description: Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.
		Version: 3.0.1
		Author: Big Sea
		Author URI: http://bigsea.co
	*/

    // Constants
    define('GF_HUBSPOT_BASENAME', plugin_basename(__FILE__));
    define('GF_HUBSPOT_PATH', WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/");
    define('GF_HUBSPOT_VERSION', '3.0.1');

    // Start up the plugin after GravityForms is loaded.
    add_action( 'gform_loaded', array( 'GF_HubSpot_Bootstrap', 'load' ), 5 );

    // Activation hooks
    register_activation_hook( __FILE__, array('GF_HubSpot_Bootstrap', 'activate') );
    register_deactivation_hook( __FILE__, array('GF_HubSpot_Bootstrap', 'deactivate') );

    class GF_HubSpot_Bootstrap {

        public static function load(){
            // Let's get rolling!
            require_once( GF_HUBSPOT_PATH . 'vendor/autoload.php' );
            // BigSea\GFHubSpot\Tracking::log('Plugin Booted Up');
            GFAddOn::register( '\BigSea\GFHubSpot\GF_HubSpot' );
        } // function

        /**
         *  activate ()
         *
         *      Activate the plugin, installing any necessary functionality.
         *
         *  @param none
         *  @return boolean
         */
        public static function activate () {
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
