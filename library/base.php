<?php
	
	/*
		Package: Wordpress
		Sub Package: BSD Hubspot for Gravity Forms
		
		Shared functionality between the two files.
	*/

	class bsdGFHubspotBase {

		// Variables -- GET
		public static function getAPIKey () {
			return get_option("gf_bsdhubspot_api_key");
		} // function
		public static function getConnectionType () {
			$type = get_option("gf_bsdhubspot_connection_type");
			if ( !$type ) return 'oauth';
			return $type;
		} // function
		public static function getOAuthTokenArray () {
			$array = get_option("gf_bsdhubspot_oauth_token");
			if ( !$array || !is_array($array) ) return FALSE;

			// Let's make sure we don't need to refresh the token
			if ( time() > $array['bsd_expires_in'] ) {
				// We need to refresh the token. How?
				require_once ( BSD_GF_HUBSPOT_PATH . 'library/hubspot/class.auth.php');
				$api = new HubSpot_Auth(self::getAPIKey(), self::getPortalID());
				$new_token = $api->refreshOAuthToken($array['refresh_token'], BSD_GF_HUBSPOT_CLIENT_ID);
				if ( !isset( $new_token->access_token ) ) {
					self::setValidationStatus("no");
					return FALSE;
				}

				// Example: {"portal_id":xxx,"expires_in":28799,"refresh_token":"yyy","access_token":"zzz"}
				$data = array (
					'access_token' => $new_token->access_token,
					'refresh_token' => $new_token->refresh_token,
					'hs_expires_in' => $new_token->expires_in,
					'bsd_expires_in' => (time() + (int)$new_token->expires_in) - 1800 // Let's do this a half hour earlier than too late.
				);
				self::setOAuthToken( $data );
			} // endif

			return $array;
		} // function
		public static function getOAuthToken () {
			$array = self::getOAuthTokenArray();
			if ( !$array || !is_array($array) ) return FALSE;
			return $array['access_token'];
		} // function
		public static function getPortalID () {
			return get_option("gf_bsdhubspot_portal_id");
		} // function
		public static function getValidationStatus () {
			return (get_option("gf_bsdhubspot_api_validated") == "yes");
		} // function
		public static function includeAnalyticsCode () {
			return (get_option("gf_bsdhubspot_include_analytics") == "yes");
		} // function

		// Variables -- SET
		public static function setAPIKey ( $var ) {
			return update_option("gf_bsdhubspot_api_key", $var);
		} // function
		public static function setConnectionType ( $var ) {
			if ( $var != 'oauth' && $var != 'apikey') {
				$var = 'oauth';
			}
			return update_option("gf_bsdhubspot_connection_type", $var);
		} // function
		public static function setIncludeAnalytics ( $var ) {
			if ( $var != 'yes' && $var != 'no') {
				$var = 'yes';
			}
			return update_option("gf_bsdhubspot_include_analytics", $var );
		} // function
		public static function setOAuthToken ( $var ) {
			return update_option("gf_bsdhubspot_oauth_token", $var);
		} // function
		public static function setPortalID ( $var ) {
			return update_option("gf_bsdhubspot_portal_id", $var);
		} // function
		public static function setValidationStatus ( $var ) {
			if ( $var != 'yes' && $var != 'no') {
				$var = 'yes';
			}
			return update_option("gf_bsdhubspot_api_validated", $var );
		} // function


		public static function getHubSpotFormsInstance () {
			// Let's find out what mode we're in.
			$connection_type = self::getConnectionType();

			$last_tracked_update = get_option('gf_bsdhubspot_last_track');
			if ( !$last_tracked_update || $last_tracked_update <= strtotime('24 hours ago') ) {
				$tracking = new BSDTracking ();
				$tracking->trigger('forms_requested');
				update_option('gf_bsdhubspot_last_track', time());
			}

			if ( $connection_type == 'oauth' ) {
				// return oAUTH version.
				return new HubSpot_Forms(self::getOAuthToken(), BSD_HUBSPOT_CLIENT_ID);
			}

			// Return the API KEY version
			return new HubSpot_Forms(self::getAPIKey());
		} // function
		

		/**
		 *	getConnections ()
		 *
		 *		Get the list of connections, or a single connection, if requested.
		 *
		 *	@param int $id (optional)
		 *	@return bool|array
		 */
		public static function getConnections ($id=FALSE, $field='id') {
			global $wpdb;

			$sql = "SELECT * FROM ".BSD_GF_HUBSPOT_TABLE."";
			if ( $id ) {
				$sql .= " WHERE `".$field."` = %s";
			}
			$sql .= " ORDER BY `id` desc";

			$results = $wpdb->get_results($wpdb->prepare($sql, $id));

			if ( !is_array($results) || count($results) == 0 ) {
				return FALSE;
			}

			$output = array ();
			foreach ( $results as $result ) {
				$result->form_data = unserialize($result->form_data);
				$output[] = $result;
			}

			return $output;
		} // function


		/**
		 *	saveConnection ()
		 *
		 *		Save the Connection
		 *
		 *	@param string $gravityform_id
		 *	@param string $hubspot_id
		 *	@param mixed $data
		 *	@param int $connection_id (optional)
		 *	@return bool|int
		 */
		protected static function _saveConnection ( $gravityform_id, $hubspot_id, $incoming_data, $connection_id=FALSE ) {
			global $wpdb;

			if ( !is_string($gravityform_id) ) $gravityform_id = (string)$gravityform_id;
			if ( !is_string($hubspot_id) ) $hubspot_id = (string)$hubspot_id;
			if ( !is_string($incoming_data) ) $incoming_data = serialize($incoming_data);

			if ( !$connection_id ) {
				$data = array (
					"gravityforms_id" => $gravityform_id,
					"hubspot_id" => $hubspot_id,
					"form_data" => $incoming_data
				);

				// We're inserting.
				if ( $wpdb->insert(BSD_GF_HUBSPOT_TABLE, $data) ) {
					return $wpdb->insert_id;
				}
			}
			else {

				$connection = self::getConnections($connection_id);
				$connection = $connection[0];
				if ( serialize($connection->form_data) == $incoming_data ) {
					// No Changes, no point to save to the DB for this, and Wordpress seems to stop it. WTF.
					return TRUE;
				}

				$sql = "
					UPDATE ".BSD_GF_HUBSPOT_TABLE." 
						SET 
							gravityforms_id = %s, 
							hubspot_id = %s, 
							form_data = %s 
						WHERE 
							`id` = %s 
						LIMIT 1
				";

				if ( $wpdb->query($wpdb->prepare($sql, $gravityform_id, $hubspot_id, $incoming_data, $connection_id )) ) {
					return $connection_id;
				}
			}

			return FALSE;
		} // function

		/**
		 *	deleteConnection ()
		 *
		 *		Remove the Connection
		 *
		 *	@param int $id
		 *	@return bool
		 */
		protected static function _deleteConnection ( $id ) {
			global $wpdb;

			$where = array (
					"id" => $id
				);

			if ( $wpdb->delete(BSD_GF_HUBSPOT_TABLE, $where) ) {
				return TRUE;
			}

			return FALSE;
		} // function


		/**
		 *	_gravityforms_valid_version ()
		 *	
		 *		Verify that Gravity Forms is a valid version
		 *
		 *	@param none
		 *	@return boolean
		 */
		protected static function _gravityforms_valid_version () {
			if(class_exists("GFCommon") && class_exists('GFFormsModel')){
				$is_correct_version = version_compare(GFCommon::$version, BSD_GF_HUBSPOT_MIN_GFVERSION, ">=");
				return $is_correct_version;
			} 
			else {
				return FALSE;
			}
		} // function

		/**
		 *	_hubspot_attempt_connection ()
		 *
		 *	@param string $key
		 *	@param boolean $user_oauth
		 *	@return bool|string
		 */
		protected static function _hubspot_attempt_connection ( $key, $use_oauth=FALSE ) {

			$forms_api = new HubSpot_Forms($key, $use_oauth);

			$forms = $forms_api->get_forms();
			if ( isset($forms->status) && $forms->status == 'error' ) {
				return $forms->message;
			}

			return TRUE;
		} // function

		protected static function _hubspot_validate_credentials ( $echo=FALSE, $setting_connection_type=FALSE, $setting_portal_id=FALSE ) {
			if ( !$setting_connection_type ) {
				$setting_connection_type = self::getConnectionType();
				$setting_portal_id = self::getPortalID();
			}

			$data_validated = TRUE;
			$tracking = new BSDTracking();

			if ( $setting_connection_type == 'oauth' ) {
					// Portal ID is required for oAuth, so, if one wasn't set, let's show message.
					if ( $setting_portal_id == '' ) {
						if ( $echo ) echo '<div class="error fade"><p>Portal ID is required for oAuth.</p></div>';
					}
					else {
						// Validate the oAUTH, folk.
						$oauth_token = self::getOAuthToken();
						if ( $oauth_token && $oauth_token != '' ) {
							$api_check = self::_hubspot_attempt_connection($oauth_token, BSD_GF_HUBSPOT_CLIENT_ID);
							if ( $api_check === TRUE ) {
								$data_validated = TRUE;
								self::setValidationStatus("yes");
								$tracking->trigger('validated_oauth');
							}
						}
						else {
							if ( $echo ) echo '<div class="error fade"><p>API Error: '.$api_check.'</p></div>';
						}
					}
				}
				elseif ( $setting_connection_type == 'apikey' ) {
					$api_check = self::_hubspot_attempt_connection($setting_api_key);
					if ( $api_check === TRUE ) {
						// if it's validated, let's mark it as such
						$data_validated = TRUE;
						self::setValidationStatus("yes");
						$tracking->trigger('validated_apikey');
					}
					else {
						if ( $echo ) echo '<div class="error fade"><p>API Error: '.$api_check.'</p></div>';
					}
				}

				if ( !$data_validated ) {
					self::setValidationStatus("no");
				}
				return $data_validated;
		} // function

	} // class


