<?php
	/*
		Plugin Name: Better Hubspot for Gravity Forms
		Plugin URI: http://bigseadesign.com/
		Description: This Gravity Forms add-on sends entry submission data to the HubSpot Customer Forms API.
		Version: 0.7
		Author: Big Sea
		Author URI: http://bigseadesign.com
	*/

	define('BSD_GF_HUBSPOT_BASENAME', plugin_basename(__FILE__));
	define('BSD_GF_HUBSPOT_PATH', WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/");
	define('BSD_GF_HUBSPOT_URL', plugins_url(basename(dirname(__FILE__))) . "/");
	define('BSD_GF_HUBSPOT_PLUGIN_NAME', 'HubSpot for Gravity Forms');
	define('BSD_GF_HUBSPOT_VERSION', '0.7');
	define('BSD_GF_HUBSPOT_MIN_GFVERSION', "1.6");
	define('BSD_GF_HUBSPOT_MIN_WPVERSION', "3.7");
	define('BSD_GF_HUBSPOT_CLIENT_ID', 'bc2af989-d201-11e3-9bdd-cfa2d230ed01');

	global $wpdb;
	define('BSD_GF_HUBSPOT_TABLE', $wpdb->prefix . "rg_hubspot_connections");
	define('BSD_GF_HUBSPOT_FORMFIELD_BASE', 'hsfield_');

	require_once ( BSD_GF_HUBSPOT_PATH . 'library/base.php');
	require_once ( BSD_GF_HUBSPOT_PATH . 'library/admin.php');
	require_once ( BSD_GF_HUBSPOT_PATH . 'library/hubspot/class.forms.php');

	register_activation_hook( __FILE__, array('bsdGFHubspot', 'activate') );

	class bsdGFHubspot extends bsdGFHubspotBase {

		/**
		 *	initialize ()
		 *	
		 *		Set up the HubSpot plugin for Gravity Forms. Check plugin status to verify we're able to do work.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function initalize () {

			if ( is_admin () ) {
				bsdGFHubspotAdmin::startup();
			}

			if(!self::_gravityforms_valid_version()){
				// We obviously need gravity forms
				return;
			}

			if ( !is_admin() && self::getValidationStatus() ) {

				if ( self::includeAnalyticsCode() ) {
					add_action("wp_footer", array("bsdGFHubspot", "_hubspot_add_analytics"), 10 );
				}
			
				add_action("gform_after_submission", array("bsdGFHubspot", "_gravityforms_submission"), 10, 2);
			}

		} // function


		/**
		 *
		 *
		 */
		public static function _hubspot_add_analytics () {
			if ( !self::getValidationStatus() ) {
				// Nothing to do here. No valid Hubspot credentials.
				return;
			}

			?>
			<!-- Start of Async HubSpot Analytics Code -->
			<script type="text/javascript">
				(function(d,s,i,r) {
					if (d.getElementById(i)){return;}
					var n=d.createElement(s),e=d.getElementsByTagName(s)[0];
					n.id=i;n.src='//js.hs-analytics.net/analytics/'+(Math.ceil(new Date()/r)*r)+'/<?php echo self::getPortalID(); ?>.js';
					e.parentNode.insertBefore(n, e);
				})(document,"script","hs-analytics",300000);
			</script>
			<!-- End of Async HubSpot Analytics Code -->
			<?php
		} // function


		/**
		 *	_gravityforms_submission ()
		 *
		 *		If there's a gravity form submission, let's see if we have a matching Hubspot Connection
		 *
		 *	@param array $entry
		 *	@param array $form
		 */
		public static function _gravityforms_submission ( $entry, $form ) {

			if ( !self::getValidationStatus() ) {
				// Nothing to do here. No valid Hubspot credentials.
				return;
			}

			if ( !($connections = self::getConnections($form['id'], 'gravityforms_id')) ) {
				// We have nothing saved that's related to this Form. So we can ignore it.
				return;
			}

			$forms_api = self::getHubSpotFormsInstance();

			// Let's go through all of the connections we have for this form.
			foreach ( $connections as $connection ) :

				// The HS Field : GF Field relationships
				$hs_to_gf = $connection->form_data['connections']; // redundant chris is redundant.

				// Go through all of the fields, and get the form entry that relates to them.
				$form_fields = array ();
				foreach ( $hs_to_gf as $hs => $gf ) {
					$form_fields[$hs] = $entry[$gf];
				}

				// Compile all of this data into what we need for the Form Submission
				$hubspotutk = $_COOKIE['hubspotutk'];
				$ip_addr = $_SERVER['REMOTE_ADDR']; //IP address too.
				$hs_context = array(
					'hutk' => $hubspotutk,
					'ipAddress' => $ip_addr,
					'pageUrl' => site_url(),
					'pageName' => $connection->form_data['gravity_form_title']
				);
				$hs_context_json = json_encode($hs_context);

				// Try to send the form.
				$result = $forms_api->submit_form(self::getPortalID(), $connection->hubspot_id, $form_fields, $hs_context);

				if ( !$result ) {
					// @todo write to an error log.
				}

			endforeach;
		} // function


		/**
		 *	activate ()
		 *
		 *		Activate the plugin, installing any necessary functionality.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function activate () {
			// Check if the table exists, if it doesn't let's make it.			
			$sql = "CREATE TABLE ".BSD_GF_HUBSPOT_TABLE." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				gravityforms_id varchar(255) NOT NULL,
				hubspot_id varchar(255) NOT NULL,
				form_data text NULL,
				UNIQUE KEY id (id)
			);";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		} // function

		/**************************************************************************
			Private Functions
		**************************************************************************/

	} // class

	add_action ( 'init',  array ( 'bsdGFHubspot', 'initalize') );


