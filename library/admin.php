<?php
	/*
		Package: Wordpress
		Sub Package: BSD HubSpot for Gravity Forms
		
		Admin Panel related functionality
	*/

	if ( !class_exists('bsdGFHubspotAdmin') ) :
	class bsdGFHubspotAdmin extends bsdGFHubspotBase {

		/**
		 *	startup ()
		 *	
		 *		Admin Panel is being viewed, let's handle everything related to viewing the admin panel
		 *
		 *	@param none
		 *	@return none
		 */
		public static function startup () {

			// Hooks
			add_action('admin_notices', array('bsdGFHubspotAdmin', 'show_plugin_messages'), 10);
			add_filter( 'plugin_action_links_' . BSD_GF_HUBSPOT_BASENAME, array("bsdGFHubspotAdmin", "show_extra_links") );
			if ( bsdGFHubspotAdmin::_gravityforms_status(FALSE) ) {
				// These are pretty specific to Gravity Forms being active, only run if we have Gravity Forms available.
				RGForms::add_settings_page("HubSpot", array("bsdGFHubspotAdmin", "html_page_settings"));
				add_filter ('gform_addon_navigation', array("bsdGFHubspotAdmin", "gravityforms_add_submenus") );
			}

			// enqueue Stylesheets and Javascript
			wp_register_style ( 'bsd_gf_hubspot_css', BSD_GF_HUBSPOT_URL . 'assets/style.css', array(), BSD_GF_HUBSPOT_VERSION );
			wp_enqueue_style ( 'bsd_gf_hubspot_css' );
			wp_register_script ( 'bsd_gf_hubspot_jquery', BSD_GF_HUBSPOT_URL . 'assets/scripts.js', array('jquery'), BSD_GF_HUBSPOT_VERSION, TRUE );
			wp_enqueue_script ( 'bsd_gf_hubspot_jquery' );

		} // function

		public static function checkForOAuthToken () {
			if ( isset($_GET['access_token']) 
					&& isset($_GET['page']) && $_GET['page'] == 'gf_settings' 
					&& isset($_GET['subview']) && $_GET['subview'] == 'HubSpot' ) {
				// access_token, refresh_token, expires_in
				$data = array (
					'access_token' => $_GET['access_token'],
					'refresh_token' => $_GET['refresh_token'],
					'hs_expires_in' => $_GET['expires_in'],
					'bsd_expires_in' => (time() + (int)$_GET['expires_in']) - 1800, // will run a half hour earlier than required
				);

				// Store this data
				self::setOAuthToken( $data );

				// Let's make sure it's ACCURATE data.
				$api_check = self::_hubspot_attempt_connection($_GET['access_token'], BSD_GF_HUBSPOT_CLIENT_ID);
				if ( $api_check === TRUE ) {
					$data_validated = TRUE;
					self::setValidationStatus("yes");
				}
				else {
					self::setValidationStatus("no");
				}

			}
		} // function

		/**
			HOOK FUNCTIONS
		**/

		/**
		 * show_extra_links ()
		 *
		 *		Show the 'settings' link for the HubSpot GF plugin when viewing the Plugins page.
		 *
		 *	@param array $links
		 *	@return array
		 */
		public static function show_extra_links ( $links ) {
			$settings_link = '<a href="'. self::_get_settings_page_url() .'">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 *	show_plugin_messages ()
		 *	
		 *		Run through all vital checks, showing the error messages if we're missing HubSpot or Gravity Forms, or if the Save failed.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function show_plugin_messages () {

			// Check if gravity forms is active or not. If it's not, then it'll show ONLY that message.
			if ( self::_gravityforms_status () ) {

				// If Gravity Forms IS installed, let's find out if we have valid hubspot credentials.
				self::_hubspot_status ();
			}

		} // function


		/**
			ADMIN PAGES
		**/


		/**
		 *	html_page_settings ()
		 *	
		 *		Settings Page for HubSpot credentials
		 *
		 *	@param none
		 *	@return none
		 */
		public static function html_page_settings () {

			// Always checking for the oAuth tokens that might come in.
			// 	@todo Really need to find a way to remove them from the URL afterwards
			self::checkForOAuthToken();

			if ( count ( $_POST ) > 0 ) {
				// We have POST data, so we might have a submission related to us. Let's check
				self::_save_settings();
			}

			if ( !empty($_POST["gf_bsdhubspot_submit"]) ) {
				$setting_portal_id = stripslashes($_POST["gf_bsdhubspot_portal_id"]);
				$setting_api_key = stripslashes($_POST["gf_bsdhubspot_api_key"]);
				$setting_connection_type = stripslashes($_POST["gf_bsdhubspot_connection_type"]);
				$setting_include_analytics = isset($_POST["gf_bsdhubspot_include_analytics"]);
			} else {
				$setting_portal_id = self::getPortalID();
				$setting_api_key = self::getAPIKey();
				$setting_connection_type = self::getConnectionType();
				$setting_include_analytics = self::includeAnalyticsCode();
			}

			// Check up on the validation status of the data provided.
			$validated = self::getValidationStatus();
			$apikey_valid_status = '<i class="fa fa-times gf_keystatus_invalid"></i>';
			if ( $validated && $setting_connection_type == 'apikey' ) {
				$apikey_valid_status = '<i class="fa fa-check gf_keystatus_valid"></i>';
			}

			if ( $setting_portal_id != '' ) {
				$authorize_url = 'https://app.hubspot.com/auth/authenticate'
											. '?client_id=' . BSD_GF_HUBSPOT_CLIENT_ID
											. '&portalId=' . $setting_portal_id
											. '&scope=leads-rw+offline'
											. '&redirect_uri=' . urlencode(self::_get_settings_page_url());
			}
			else {
				$authorize_url = FALSE;
			}

			?>
				<div class="wrap bsd_hubspot">

					<h3><span>HubSpot Account Information</span></h3>

					<form method="post" action="">
						<?php wp_nonce_field("update", "gf_bsdhubspot_update") ?>

						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row"><label for="gf_bsdhubspot_portal_id">HubSpot Hub ID</label> </th>
									<td>
										<input type="text" style="width:350px" class="code pre" name="gf_bsdhubspot_portal_id" value="<?php echo $setting_portal_id; ?>"/>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="gf_bsdhubspot_connection_type">Connect to HubSpot via</label></th>
									<td id="connection_types">
										<p><label><input type="radio" name="gf_bsdhubspot_connection_type" value="oauth" <?php echo ($setting_connection_type == 'oauth' ? 'checked' : '');?> /> oAuth</label></p>
										<p><label><input type="radio" name="gf_bsdhubspot_connection_type" value="apikey" <?php echo ($setting_connection_type == 'apikey' ? 'checked' : '');?> /> API</label></p>
									</td>
								</tr>
								<tr class="connection_type_section connect_via_oauth">
									<th scope="row"><label for="gf_bsdhubspot_oauth_key">Authenticate with HubSpot</label></th>
									<td>
										<?php if ( $authorize_url ) : ?>
											<?php if ( $setting_connection_type == 'oauth' ) : ?>
												<a class="button" href="<?php echo $authorize_url; ?>">Click Here to Authenticate</a>
												<?php if ( $validated ) : ?>
													<p class="description small">You are currently authenticated with HubSpot <i class="fa fa-check gf_keystatus_valid"></i></p>
												<?php endif; ?>
											<?php else: ?>
												<p class="error">Click Save before continuing with oAuth Validation.</p>
											<?php endif; ?>
										<?php else : ?>
											<p class="error">Requires the Hub ID first. Enter your Hub ID above and click save.</p>
										<?php endif; ?>
									</td>
								</tr>
								<tr class="connection_type_section connect_via_apikey">
									<th scope="row"><label for="gf_bsdhubspot_api_key">HubSpot API Key</label></th>
									<td>
										<input type="text" style="width:350px" class="code pre" name="gf_bsdhubspot_api_key" value="<?php echo $setting_api_key; ?>" />
										<?php echo $apikey_valid_status; ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="gf_bsdhubspot_include_analytics">Include HubSpot Analytics JS?</label></th>
									<td>
										<input type="checkbox" value="yes" name="gf_bsdhubspot_include_analytics" <?php echo ($setting_include_analytics == "yes" ? 'checked' : ''); ?> />
										<span class="small description text">Only check this box if you have not already included the HubSpot Analytics tracking code on your site.</span>
									</td>
								</tr>
								<tr>
									<td colspan="2" ><input type="submit" name="gf_bsdhubspot_submit" class="submit button-primary" value="Save Settings" /></td>
								</tr>
							</tbody>
						</table>
					</form>

				</div>
				<!-- [end] .wrap -->
			<?php
		} // function

		/**
		 *	html_page_connections ()
		 *	
		 *		Admin page for handling both the list of the connections and create new connections.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function html_page_connections () {
			?>
				<div class="wrap">
					<?php
						if ( !isset($_GET['sub']) || $_GET['sub'] != 'make_connection' ) : 
							
							echo '<h2><span>HubSpot > Gravity Forms</span></h2>';

							if ( !isset($_GET['sub']) && $_GET['sub'] == 'delete_connection' ) {
								if ( self::_deleteConnection($_GET['connection_id'])) {
									echo '<div class="updated fade"><p>Connection deleted successfully!</p></div>';
								}
								else {
									echo '<div class="error"><p>Something went wrong. Either Invalid Connection ID, or unable to connect to Database.</p></div>';
								}
							}

							if ( !self::getValidationStatus() ) {
								echo '<div id="message" class="error"><p>Please provide valid HubSpot Credentials on the <a href="'.self::_get_settings_page_url().'">Gravity Forms > Settings > HubSpot</a> page.</p></div>';
							}
							else {
								self::html_connections_list();
							}

						 else : 
						 	echo '<h2><span>Match HubSpot Fields to Gravity Forms</span></h2>';

							self::html_connections_make();
						endif; 
					?>

				</div>
				<!-- [end] .wrap -->
			<?php
		} // function


		/**
		 *	html_connections_make ()
		 *
		 *		Attempt to connect the Gravity Form to the HubSpot form, per user selection.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function html_connections_make () {

			$error = FALSE;
			$connection_id = FALSE;

			if ( !self::getValidationStatus() ) {
				echo '<div class="error fade"><p>Invalid HubSpot Credentials. Please verify your credentials on the <a href="'.self::_get_settings_page_url().'">Gravity Forms > Settings > HubSpot</a> page.</p></div>';
				self::html_connections_list();
				return;
			}
			elseif ( isset($_GET['connection_id']) ) {
				// let's get the Connection data via the Database
				$connection_id = $_GET['connection_id'];

				$connection = self::getConnections($connection_id);
				$connection = $connection[0];

				if ( empty($_POST) ) {
					foreach ( $connection->form_data['connections'] as $hs => $gf ) {
						if ( !isset($_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$hs] ) ) {
							// @since 1.1.4 2014-08-04, adds check if this field is an array or not (allows for TYPES)
							$_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$hs] = (is_array($gf) ? $gf['gf_field_name'] : $gf);
						}
					}
				}

				if ( !$connection ) {
					echo '<div class="error fade"><p>Invalid Connection ID</p></div>';
					self::html_connections_list();
					return;
				}

				$gravityform_id = $connection->gravityforms_id;
				$hubspot_id = $connection->hubspot_id;
			}
			elseif ( count($_POST) > 0 ) {
				// We are getting the data from a POST submission instead.
				$gravityform_id = $_POST['gravityform_id'];
				$hubspot_id = $_POST['hubspot_id'];
			}
			else {
				echo '<div class="error fade"><p>Missing Connection Data. Please try again.</p></div>';
				self::html_connections_list();
				return;
			}

			$forms_api = self::getHubSpotFormsInstance();

			if ( isset ($_POST['gf_bsdhubspot_connections']) ) {
				// We have a submission with the connection form fields. Let's try to process that.
				if ( self::_validate_formtoform_connection( $forms_api ) === TRUE ) {
					// Let's try to save the data! WOOHOO.
					$hubspot_form = $forms_api->get_form_by_id($hubspot_id);
					$gravity_form = RGFormsModel::get_form_meta($gravityform_id);
					$hubspot_to_gf_connections = array ();
					if ( is_array($hubspot_form->fields) ) : foreach ( $hubspot_form->fields as $field ) : 
						if ( version_compare(BSD_GF_HUBSPOT_VERSION, '1.1.3', ">") ) { 
							$hubspot_to_gf_connections[$field->name] = array(
								'hs_field_type' => $field->type,
								'gf_field_name' => $_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$field->name]
								);
						}
						else {
							// Support for <= 1.1.3
							$hubspot_to_gf_connections[$field->name] = $_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$field->name];
						}
					endforeach; endif;

					$data_to_save = array (
						'hubspot_form_title' => $hubspot_form->name,
						'gravity_form_title' => $gravity_form['title'],
						'connections' => $hubspot_to_gf_connections,
					);

					if ( !($connection_id = self::_saveConnection ( $gravityform_id, $hubspot_id, $data_to_save, $connection_id )) ) {
						echo '<div class="error fade"><p>We could not save the Connection for an unknown reason. Please try again.</p></div>';
						$tracking = new BSDTracking ();
						$tracking->trigger('error_log', $result, 'Could not submit data to HubSpot');
					}
					else {
						echo '<div class="updated fade"><p>Connection saved successfully!</p></div>';
						self::html_connections_list();
						return;
					}
				}
			}

			echo '<h3>Match the fields in your HubSpot Form to the fields in your Gravity Form.</h3>';

			echo '<p><a href="'.self::_get_connections_page_url().'">&laquo; Back to HubSpot Connections</a></p>';

			// Get the GF Form
			$gravity_fields = array ();
			$gravity_form = GFFormsModel::get_form_meta($gravityform_id);
			if ( !$gravity_form || count($gravity_form['fields']) == 0 ) {
				echo '<div class="error fade"><p>Gravity Form has no Fields. Please create some fields and try to make a Connection again.</p></div>';
			}
			else {
				// Get all of these fields in an array (so we can properly tag the right ones as 'active')
				foreach ( $gravity_form['fields'] as $field ) :
					if ( $field['type'] != 'checkbox' && $field['type'] != 'radio' && is_array($field['inputs']) ) {
						foreach ( $field['inputs'] as $input ) {
							$gravity_fields[(string)$input['id']] = $field['label'] . ' ('.$input['label'].')';
						}
					}
					else {
						$gravity_fields[(string)$field['id']] = $field['label'];
					}
				endforeach;
			}

			// and the Hubspot Form.
			$hubspot_fields = $forms_api->get_form_fields($hubspot_id);

			if ( !$hubspot_fields || count($hubspot_fields) == 0 ) {
				echo '<div class="error fade"><p>HubSpot Form has no Fields. Please create some fields and try to make a Connection again.</p></div>';
				self::_form_connection_make();
				return;
			}
			
			?>
			<form method="post" action="">
				<?php wp_nonce_field("update", "gf_bsdhubspot_connections"); ?>
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
							<td><h3>HubSpot Field</h3></td>
							<td><h3>Gravity Form Field</h3></td>
						</tr>
					</thead>

					<tbody>
						<?php foreach ( $hubspot_fields as $field ) : $field_slug = BSD_GF_HUBSPOT_FORMFIELD_BASE.$field->name; ?>
							<tr>
								<td><label for="<?php echo $field_slug; ?>"><?php echo $field->label; ?> <?php echo ( $field->required ? ' <span class="required">*</span>' : ''); ?></td>
								<td>
									<select name="<?php echo $field_slug; ?>">
										<option value="">&nbsp;</option>
										<?php foreach ( $gravity_fields as $key => $value ) : 
											$selected = '';
											if ( isset($_POST[$field_slug]) && $_POST[$field_slug] == $key ) {
												$selected = 'selected';
											}
										?>
											<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<input style="margin: 20px 10px;" class="button-primary gfbutton" type="submit" value="Save Connection" />
				<input type="hidden" name="gravityform_id" value="<?php echo $gravityform_id; ?>" />
				<input type="hidden" name="hubspot_id" value="<?php echo $hubspot_id; ?>" />
				<input type="hidden" name="connection_id" value="<?php echo $connection_id; ?>" />
			</form>

			<?php
			
		} // function


		/**
		 *	html_connections_list ()
		 *
		 *		Show the primary connections page, which is showing the ability to create a connection and to edit existing connections.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function html_connections_list () {

			?>
			<h3>Create a new Form Connection to HubSpot:</h3>
			<?php self::_form_connection_make(); ?>

			<h3>Forms Connected to HubSpot:</h3>
			<form method="post" action="">
				<?php wp_nonce_field("update", "gf_bsdhubspot_form_connections") ?>

				<table class="widefat fixed" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" name="form_bulk_check_all" onclick="jQuery('.bsdgf_checkbox').attr('checked', this.checked);" /></th>
							<th scope="col" id="title" class="manage-column column-title" style="cursor:pointer;">Gravity Form Title</th>
							<th scope="col" id="title" class="manage-column column-title" style="cursor:pointer;">HubSpot Form Title</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" name="form_bulk_check_all" onclick="jQuery('.bsdgf_checkbox').attr('checked', this.checked);" /></th>
							<th scope="col" id="title" class="manage-column column-title" style="cursor:pointer;">Gravity Form Title</th>
							<th scope="col" id="title" class="manage-column column-title" style="cursor:pointer;">HubSpot Form Title</th>
						</tr>
					</tfoot>

					<tbody class="list:user user-list">
						<?php
							$connections = self::getConnections();

							if ( $connections && count ( $connections ) > 0 ) :
								foreach ( $connections as $connection ) :
									$edit_url = self::_get_connections_page_url('make_connection', $connection->id);
									$delete_url = self::_get_connections_page_url('delete_connection', $connection->id);
								?>
									<tr valign="top" data-id="1">
										<th scope="row" class="check-column"><input type="checkbox" name="form[]" value="1" class="bsdgf_checkbox"/></th>
										<td class="column-title">
											<strong><a class="row-title" href="<?php echo $edit_url; ?>" title="Edit"><?php echo $connection->form_data['gravity_form_title']; ?></a></strong>
											<div class="row-actions">
												<span class="gf_form_toolbar_editor">
													<a class='gf_toolbar_active' title='Edit this Connection' href='<?php echo $edit_url; ?>' target=''> Edit</a> | 
												</span>
												<span class="trash">
													<a onclick='return confirm("Are you sure you want to delete this?")' title='Remove this Connection' href='<?php echo $delete_url; ?>' target=''> Delete</a>
												</span>
											</div>
										</td>
										<td><?php echo $connection->form_data['hubspot_form_title']; ?></td>
									</tr>
								<?php
								endforeach;
							else :
								?>
								<tr>
									<td colspan="3">
										<h4>No Connections made yet.</h4>
									</td>
								</tr>
								<?php
							endif; 
						?>
					</tbody>
				</table>
			</form>
			<?php
		} // function


		/**
		 *	_form_connections_make ()
		 *
		 *		The form for making form connections.
		 *
		 *	@param none
		 *	@return none
		 */
		public static function _form_connection_make () {
			$gf_forms = RGFormsModel::get_forms();
			$gf_form_count = RGFormsModel::get_form_count();

			$hs_forms = self::_hubspot_get_forms();
			$hs_form_count = count($hs_forms);
			?>
			<form method="post" action="<?php echo self::_get_connections_page_url('make_connection'); ?>">
				<table>
					<thead>
						<tr>
							<td><strong>Gravity Form</strong></td>
							<td><strong>HubSpot Form</strong></td>
							<td>&nbsp;</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<select name="gravityform_id">
									<?php
										if ( $gf_form_count > 0 ) : foreach ( $gf_forms as $form ) :
									?>
										<option value="<?php echo $form->id; ?>"><?php echo $form->title; ?></option>
									<?php endforeach; else : ?>
										<option value="">No Forms Found</option>
									<?php endif; ?>
								</select>
							</td>
							<td>
								<select name="hubspot_id">
									<?php
										if ( $hs_form_count > 0 ) : foreach ( $hs_forms as $form ) :
									?>
										<option value="<?php echo $form->guid; ?>"><?php echo $form->name; ?></option>
									<?php endforeach; else : ?>
										<option value="">No Forms Found</option>
									<?php endif; ?>
								</select>
							</td>
							<td><input type="submit" value="Connect Forms" name="gf_bsdhubspot_submit" /></td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php
		} // function


		/**
			INTERNAL PRIVATE FUNCTIONS
		**/

		/**
		 *	_get_settings_page_url / _get_connections_page_url
		 *
		 *	@param none
		 *	@return string
		 */
		private static function _get_settings_page_url () {
			return get_admin_url(null, 'admin.php?page=gf_settings&subview=HubSpot');
		} // function
		private static function _get_connections_page_url ( $sub=FALSE, $connection_id=FALSE) {
			$url = get_admin_url(null, 'admin.php?page=bsdgfhubspot_forms' );

			// &sub=make_connection&connection_id=
			if ( $sub ) {
				$url .= '&sub='.$sub;
				if ( $connection_id ) {
					$url .= '&connection_id=' . $connection_id;
				}
			}

			return $url;
		} // function


		/**
			FORM SUBMISSION HANDLING
		**/

		/**
		 * _save_settings ()
		 *
		 *		Returns an array if there's a message to display. Otherwise, returns FALSE for no messages.
		 *
		 *	@param none
		 *	@return bool
		 */
		private static function _save_settings ( $echo=TRUE ) {

			$tracking = new BSDTracking ();

			if ( isset($_POST['gf_bsdhubspot_update']) ) {
				check_admin_referer("update", "gf_bsdhubspot_update");
				$setting_portal_id = stripslashes($_POST["gf_bsdhubspot_portal_id"]);
				$setting_api_key = stripslashes($_POST["gf_bsdhubspot_api_key"]);
				$setting_include_analytics = (isset($_POST["gf_bsdhubspot_include_analytics"]) ? "yes" : "no");
				$setting_connection_type = stripslashes($_POST["gf_bsdhubspot_connection_type"]);
				self::setPortalID($setting_portal_id);
				self::setAPIKey($setting_api_key);
				self::setIncludeAnalytics($setting_include_analytics);
				self::setConnectionType($setting_connection_type);

				if ( $echo ) echo '<div class="updated fade"><p>Settings Saved Successfully</p></div>';

				// Let's try validating the data.
				self::_hubspot_validate_credentials($echo, $setting_connection_type, $setting_portal_id);

				return TRUE;
			}

			return FALSE;
		} // function

		/**
		 *	_validate_formtoform_connection ()
		 *
		 *		Validates the form data
		 *
		 *	@param object $forms_api (optional)
		 *	@param bool $echo (optional)
		 *	@return bool|string
		 */
		private static function _validate_formtoform_connection ( $forms_api=FALSE, $echo=TRUE ) {
			if ( !$forms_api ) $forms_api = self::getHubSpotFormsInstance();

			$error = '';

			$gravityform_id = $_POST['gravityform_id'];
			$hubspot_id = $_POST['hubspot_id'];

			// Do have have ANY useful fields? It's stupid to make a connection with NO fields
			$filled_count = 0;
			foreach ( $_POST as $key => $value ) :
				if ( substr($key, 0, strlen(BSD_GF_HUBSPOT_FORMFIELD_BASE)) == BSD_GF_HUBSPOT_FORMFIELD_BASE && $value != '' ) {
					$filled_count++;
				}
			endforeach;
			if ( $filled_count == 0 ) {
				$error = '<p>We need fields from Gravity Forms to connect to in order for this to be a useful endeavor.</p>';
				if ( $echo ) echo '<div class="error fade">'.$error.'</div>';
				return $error;
			}

			// Validate the fields first.
			$hubspot_fields = $forms_api->get_form_fields($hubspot_id);
			if ( !$hubspot_fields || count($hubspot_fields) == 0 ) {
				$error = '<p>How the hell did you even get here? [bsdhubspot/library/admin.php]</p>';
				if ( $echo ) echo '<div class="error fade">'.$error.'</div>';
				return $error;
			}

			// Go through each of the hubspot fields, checking to see if the item is required and if it actually had a field assigned to it.
			foreach ( $hubspot_fields as $field ) : 
				if ( $field->required && $_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$field->name] == '' ) {
					$error .= '<p>'.$field->label.' in HubSpot is REQUIRED and needs a Gravity Form field connected to it.</p>';
				}
			endforeach;
			
			if ( $error != '' ) {
				if ( $echo ) echo '<div class="error fade">'.$error.'</div>';
				return $error;
			}

			return TRUE;

		} // function

		/**
			HUBSPOT FUNCTIONS
		**/


		/**
		 *	_hubspot_get_forms ()
		 *
		 *		Returns the list of Forms from Hubspot
		 *
		 *	@param none
		 *	@return array
		 */
		private static function _hubspot_get_forms () {

			$forms_api = self::getHubSpotFormsInstance();

			$forms = $forms_api->get_forms();
			if ( isset($forms->status) && $forms->status == 'error' ) {
				return array();
			}

			return $forms;

		} // function

		/**
		 *	_hubspot_status ()
		 *	
		 *		Make sure we have HubSpot credentials provided
		 *
		 *	@param boolean $echo (optional)
		 *	@return boolean
		 */
		private static function _hubspot_status ( $echo=TRUE ) {
			// Show message if we're missing HubSpot credentials
			$validation_status = self::getValidationStatus();
			$connection_type = self::getConnectionType();
			if ( !$validation_status && $connection_type == 'apikey' ) {
				// Show message if the hubspot credentials are INVALID (can't connect to API)
				$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - HubSpot API Key Missing. Please provide valid HubSpot credentials. Plugin will not work correctly until valid credentials provided.</p>';
			}
			elseif ( !$validation_status && $connection_type == 'oauth' ) {
				// Show message if the hubspot credentials are INVALID (can't connect to API)
				$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - HubSpot oAuth Connection Invalid. Please connect to HubSpot. Plugin will not work correctly until valid credentials provided.</p>';
			}

			// If we have a message, let's show it.
			if ( isset ( $message ) ) {
				if ( $echo ) echo '<div id="message" class="error">'.$message.'</div>';
				return FALSE;
			}

			return TRUE;
		} // function

		/**
			GRAVITY FORMS FUNCTIONS
		**/

		/**
		 *	_gravityforms_status ()
		 *	
		 *		Make sure this plugin is being used with a proper version of Gravity Forms
		 *
		 *	@param boolean $echo (optional)
		 *	@return boolean
		 */
		private static function _gravityforms_status ( $echo=TRUE ) {

			$message = '';

			if(!class_exists('RGForms')) {
				if(file_exists(WP_PLUGIN_DIR.'/gravityforms/gravityforms.php')) {
					$message .= '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - Gravity Forms is installed but not active. <strong>Activate Gravity Forms</strong> to use this plugin.</p>';
				} else {
					$message .= '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - <a href="http://gravityforms.com">Gravity Forms</a> is required. You do not have the Gravity Forms plugin enabled.</p>';
				}
			}

			if ( !self::_gravityforms_valid_version() ) {
				$message .= '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - A minimum version of '.BSD_GF_HUBSPOT_MIN_GFVERSION.' of Gravity Forms is required to run the HubSpot Addon. Please update Gravity Forms.</p>';
			}

			if ( $message != '' ) {
				if ( $echo ) echo '<div id="message" class="error">'.$message.'</div>';
				return FALSE;
			}

			return TRUE;
		} // function


		/**
		 *	gravityforms_add_submenus ()
		 *	
		 *		Adds any submenus
		 *
		 *	@param array $menus
		 *	@return array
		 */
		public static function gravityforms_add_submenus ( $menus ) {

			$menus[] = array (
				"name" => "bsdgfhubspot_forms", 
				"label" => "HubSpot", 
				"callback" => array ("bsdGFHubspotAdmin", "html_page_connections"), 
				"permission" => "gform_full_access"
			);

			return $menus;
		} // function

	} // class
	endif;
