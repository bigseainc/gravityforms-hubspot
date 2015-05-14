<?php
	/*
		Plugin Name: Better Hubspot for Gravity Forms
		Plugin URI: http://bigseadesign.com/
		Description: Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.
		Version: 1.6
		Author: Big Sea
		Author URI: http://bigseadesign.com
	*/

	global $wpdb; // required for our table name constant

	// Constants
	define('BSD_GF_HUBSPOT_BASENAME', plugin_basename(__FILE__));
	define('BSD_GF_HUBSPOT_PATH', WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/");
	define('BSD_GF_HUBSPOT_URL', plugins_url(basename(dirname(__FILE__))) . "/");
	define('BSD_GF_HUBSPOT_PLUGIN_NAME', 'Better HubSpot for Gravity Forms');
	define('BSD_GF_HUBSPOT_VERSION', '1.6');
	define('BSD_GF_HUBSPOT_MIN_GFVERSION', "1.6");
	define('BSD_GF_HUBSPOT_MIN_WPVERSION', "3.7");
	define('BSD_GF_HUBSPOT_CLIENT_ID', 'bc2af989-d201-11e3-9bdd-cfa2d230ed01');
	define('BSD_GF_HUBSPOT_TABLE', $wpdb->prefix . "rg_hubspot_connections");
	define('BSD_GF_HUBSPOT_FORMFIELD_BASE', 'hsfield_');

	// Ones that can be controlled by each individual site.
	if ( !defined('BSD_GF_HUBSPOT_ALLOW_TRACKING') ) define('BSD_GF_HUBSPOT_ALLOW_TRACKING', FALSE);
	if ( !defined('BSD_GF_HUBSPOT_DEBUG') ) define('BSD_GF_HUBSPOT_DEBUG', FALSE);

	// Important Files
	require_once ( BSD_GF_HUBSPOT_PATH . 'library/base.php');
	require_once ( BSD_GF_HUBSPOT_PATH . 'library/admin.php');
	require_once ( BSD_GF_HUBSPOT_PATH . 'library/bigsea.php');
	require_once ( BSD_GF_HUBSPOT_PATH . 'library/hubspot/class.forms.php');

	// Hooks to Startup Plugin
	add_action ( 'init',  array ( 'bsdGFHubspot', 'initalize') );
	register_activation_hook( __FILE__, array('bsdGFHubspot', 'activate') );
	register_deactivation_hook( __FILE__, array('bsdGFHubspot', 'deactivate') );

	if ( !class_exists('bsdGFHubspot') ) :
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

			// Revalidate the HubSpot Creds every 6 hours
			$last_validated = get_option('gf_bsdhubspot_last_validated');
			if ( !$last_validated || $last_validated <= strtotime('6 hours ago') ) {
				self::_hubspot_validate_credentials();
				update_option('gf_bsdhubspot_last_validated', time());
			}

			if ( is_admin () ) {
				bsdGFHubspotAdmin::startup();
			}

			// If we don't have gravity forms, it's completely irrelevant what we do below.
			if(!self::_gravityforms_valid_version()){
				return;
			}

			var_dump ( wp_next_scheduled( 'bsd_gfhs_oauth_cron' )  );

			// Set up the CRON if the user is choosing oauth renewal
			add_action( 'bsd_gfhs_oauth_cron', array("bsdGFHubSpot", "cron_oauth") );
			add_filter( 'cron_schedules', array("bsdGFHubspot", "crons_add_schedule") );
			if ( self::getConnectionType() == 'oauth' ) {
				if ( ! wp_next_scheduled( 'bsd_gfhs_oauth_cron' ) ) {
					wp_schedule_event( time(), 'fourhours', 'bsd_gfhs_oauth_cron' );
				}
			}
			else {
				// Get the timestamp for the next event.
				wp_clear_scheduled_hook( 'bsd_gfhs_oauth_cron' );
			}

			// If we're visiting the front end of the site, and we have valid HubSpot credentials to work with... Let's move forward.
			if ( !is_admin() && self::getValidationStatus() ) {
				// If the user wanted analytics, let's load that up.
				if ( self::includeAnalyticsCode() ) {
					add_action("wp_footer", array("bsdGFHubspot", "hubspot_add_analytics"), 10 );
				}
			
				add_action("gform_after_submission", array("bsdGFHubspot", "gravityforms_submission"), 10, 2);
			}

		} // function
 
		public static function crons_add_schedule ( $schedules ) {
			// Adds once weekly to the existing schedules.
			$schedules['fourhours'] = array(
				'interval' => 14400,
				'display' => __( 'Every 4 Hours' )
			);
			return $schedules;
		}


		/**
		 * cron_oauth ()
		 *
		 *		Actually setting up WP Cron... good lord am I an idiot sometimes.
		 *
		 *	@param none
		 *	@return none
		 *	@since 1.6
		 */
		public static function cron_oauth () {
			bsdGFHubspot::refresh_oauth_token();
			update_option('gf_bsdhubspot_last_validated', time());
		} // function


		/**
		 * hubspot_add_analytics ()
		 *
		 *		Load the HubSpot Analytics Javascript, if the user requested it.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function hubspot_add_analytics () {
			if ( !self::getValidationStatus() || !self::includeAnalyticsCode () ) {
				// Nothing to do here. No valid Hubspot credentials, or the user didn't want this. Redundancy check
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
		 *	gravityforms_submission ()
		 *
		 *		If there's a gravity form submission, let's see if we have a matching Hubspot Connection
		 *
		 *	@param array $entry
		 *	@param array $form
		 */
		public static function gravityforms_submission ( $entry, $form ) {
			$tracking = new BSDTracking ();

			if ( !self::getValidationStatus() ) {
				// Nothing to do here. No valid Hubspot credentials.
				return;
			}

			if ( !($connections = self::getConnections($form['id'], 'gravityforms_id')) ) {
				// We have nothing saved that's related to this Form. So we can ignore it.
				return;
			}

			$forms_api = self::getHubSpotFormsInstance();

			if ( !$forms_api ) {
				$tracking->trigger('error_log', $forms_api, 'Could not connect to HubSpot API');
				return;
			}

			if ( BSD_GF_HUBSPOT_DEBUG ) echo '<pre>';

			// Let's go through all of the connections we have for this form.
			foreach ( $connections as $connection ) :

				// The HS Field : GF Field relationships
				$hs_to_gf = $connection->form_data['connections']; // redundant chris is redundant.

				// Go through all of the fields, and get the form entry that relates to them.
				$form_fields = array ();
				
				foreach ( $hs_to_gf as $hs => $gf ) {
					// @since 1.1.4 (2014-08-04), modified to support arrays stored in the field
					// @since 1.3.1 (2014-12-01) reworked to solve PHP warning.
					if ( is_array( $gf ) ) {
						if ( BSD_GF_HUBSPOT_DEBUG ) var_dump (isset($gf['gf_field_name']), $entry[$gf['gf_field_name']]);
						if ( isset($gf['gf_field_name']) && (isset($entry[$gf['gf_field_name']]) || isset($entry[$gf['gf_field_name'].'.1']))) {
							// Straight field to field grab
							$form_fields[$hs] = self::_processFieldForHubSpot($entry, $gf);
						}
					}
					elseif ( isset($entry[$gf] ) ) {
						$form_fields[$hs] = self::_processFieldForHubSpot($entry, $gf);
					}
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

				if ( BSD_GF_HUBSPOT_DEBUG ) var_dump (self::getPortalID(), $connection->hubspot_id, $form_fields, $hs_context);

				if ( BSD_GF_HUBSPOT_DEBUG ) echo '</pre>';

				// Try to send the form.
				$result = $forms_api->submit_form(self::getPortalID(), $connection->hubspot_id, $form_fields, $hs_context);
				$tracking->trigger('entry_submitted', $result);

				$data = array (
					'hubspot_id'		=> $connection->hubspot_id,
					'entry_data' 		=> $form_fields,
					'local_context'	=> $hs_context,
				);

				if ( !$result ) {
					$tracking->trigger('error_log', $result, 'Could not submit data to HubSpot');
				}

			endforeach;

		} // function


		/**
		 *	activate ()
		 *
		 *		Activate the plugin, installing any necessary functionality.
		 *
		 *	@param none
		 *	@return boolean
		 */
		public static function activate () {
			$old_version = get_option('gf_bsdhubspot_plugin_version');
			if ( $old_version === FALSE ) $old_version = '0';
			// if we need to update something, per version: if ( version_compare($old_version, BSD_GF_HUBSPOT_VERSION, "<") )

			// Any time we're activating the plugin, we need to make sure this table is still there. Vital to functionality.
			$sql = "CREATE TABLE ".BSD_GF_HUBSPOT_TABLE." (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				gravityforms_id varchar(255) NOT NULL,
				hubspot_id varchar(255) NOT NULL,
				form_data text NULL,
				UNIQUE KEY id (id)
			);";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			// If we're dealing with anything older than 0.7, and doesn't have a ConnectionType set.
			if ( self::getConnectionType() === FALSE ) {
				if ( version_compare($old_version, '0.7', "<") ) {
					self::setConnectionType('apikey');
				}
				else {
					self::setConnectionType('oauth');
				}
			} // endif

			// Update our tracking variable to this version
			update_option('gf_bsdhubspot_plugin_version', BSD_GF_HUBSPOT_VERSION);

			$tracking = new BSDTracking ();
			$tracking->trigger('activated_plugin');
		} // function

		public static function deactivate () {
			$tracking = new BSDTracking ();
			$tracking->trigger('deactivated_plugin');
			wp_clear_scheduled_hook( 'bsd_gfhs_oauth_cron' );
		}

	} // class
	endif;
