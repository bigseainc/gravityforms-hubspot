<?php
	
	/*
		Package: Wordpress
		Sub Package: BSD Hubspot for Gravity Forms
		
		Shared functionality between the two files.
	*/

	class bsdGFHubspotBase {

		// Variables
		public static function getAPIKey () {
			return get_option("gf_bsdhubspot_api_key");
		} // function
		public static function getPortalID () {
			return get_option("gf_bsdhubspot_portal_id");
		} // function
		public static function getAppDomain () {
			return get_option("gf_bsdhubspot_app_domain");
		} // function
		public static function getValidationStatus () {
			return (get_option("gf_bsdhubspot_api_validated") == "yes");
		} // function
		public static function includeAnalyticsCode () {
			return (get_option("gf_bsdhubspot_include_analytics") == "yes");
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
		public static function saveConnection ( $gravityform_id, $hubspot_id, $incoming_data, $connection_id=FALSE ) {
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
		public static function deleteConnection ( $id ) {
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
		public static function _gravityforms_valid_version () {
			if(class_exists("GFCommon")){
				$is_correct_version = version_compare(GFCommon::$version, BSD_GF_HUBSPOT_MIN_GFVERSION, ">=");
				return $is_correct_version;
			} 
			else {
				return FALSE;
			}
		} // function
	} // class
?>