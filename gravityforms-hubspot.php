<?php
	/*
		Plugin Name: Hubspot for Gravity Forms
		Plugin URI: http://bigseadesign.com/
		Description: Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.
		Version: 2.3.1
		Author: Big Sea
		Author URI: http://bigseadesign.com
	*/

    // Constants
    define('GF_HUBSPOT_BASENAME', plugin_basename(__FILE__));
    define('GF_HUBSPOT_PATH', WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/");
    define('GF_HUBSPOT_VERSION', '2.3.1');

    // Start up the plugin after GravityForms is loaded.
    add_action( 'gform_loaded', array( 'GF_HubSpot_Bootstrap', 'load' ), 5 );

    // Activation hooks
    register_activation_hook( __FILE__, array('GF_HubSpot_Bootstrap', 'activate') );
    register_deactivation_hook( __FILE__, array('GF_HubSpot_Bootstrap', 'deactivate') );

    class GF_HubSpot_Bootstrap {

        public static function load(){
            // Let's get rolling!
            require_once( GF_HUBSPOT_PATH . 'library/startup.php' );
            GF_Hubspot_Tracking::log('Plugin Booted Up');
            GFAddOn::register( 'GF_Hubspot' );
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

            // if we need to update something, per version: if ( version_compare($old_version, GF_HUBSPOT_VERSION, "<") )
            if ( version_compare($old_version, '2.0', "<") ) {
                // Try to do all of the things we can possibly do to help with the migration
                set_transient('gf_hubspot_needs_migration', $old_version, 0);
            }

            update_option('gf_bsdhubspot_plugin_version', GF_HUBSPOT_VERSION);

        } // function

        public static function deactivate () {
            wp_clear_scheduled_hook( 'bsd_gfhs_oauth_cron' );
            wp_clear_scheduled_hook( 'gravityforms_hubspot_oauth_cron');
        } // function

    } // class

    function gf_hubspot() {
        return GF_Hubspot::get_instance();
    }
