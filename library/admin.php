<?php
	/*
		Package: Wordpress
		Sub Package: BSD HubSpot for Gravity Forms
		
		Admin Panel related functionality
	*/

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
			add_action('admin_notices', array('bsdGFHubspotAdmin', '_show_plugin_messages'), 10);
			add_filter( 'plugin_action_links_' . BSD_GF_HUBSPOT_BASENAME, array("bsdGFHubspotAdmin", "_show_extra_links") );

			// Stylesheet and Javascript, if any
			wp_register_style ( 'bsd_gf_hubspot_css', BSD_GF_HUBSPOT_PATH.'assets/style.css', array(), BSD_GF_HUBSPOT_VERSION );
			wp_enqueue_style ( 'bsd_gf_hubspot_css' );

			if ( bsdGFHubspotAdmin::_gravityforms_status(FALSE) ) {
				RGForms::add_settings_page("HubSpot", array("bsdGFHubspotAdmin", "html_page_settings"));

				add_filter ('gform_addon_navigation', array("bsdGFHubspotAdmin", "_gravityforms_add_submenus") );
			}
		} // function

		/**
		 *
		 *
		 *
		 */
		public static function _show_extra_links ( $links ) {
			$settings_link = '<a href="'. get_admin_url(null, 'admin.php?page=gf_settings&subview=HubSpot') .'">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 *	_show_plugin_messages ()
		 *	
		 *		Show the error messages if we're missing HubSpot or Gravity Forms
		 *
		 *	@param none
		 *	@return none
		 */
		public static function _show_plugin_messages () {
			
			if ( count ( $_POST ) > 0 ) {
				// We have POST data, so we might have a submission related to us. Let's check
				self::_save_settings();
			}

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
			if(!empty($_POST["gf_bsdhubspot_submit"])) {
				$setting_portal_id = stripslashes($_POST["gf_bsdhubspot_portal_id"]);
				$setting_app_domain = stripslashes($_POST["gf_bsdhubspot_app_domain"]);
				$setting_api_key = stripslashes($_POST["gf_bsdhubspot_api_key"]);
				$setting_include_analytics = isset($_POST["gf_bsdhubspot_include_analytics"]);
			} else {
				$setting_portal_id = self::getPortalID();
				$setting_app_domain = self::getAppDomain();
				$setting_api_key = self::getAPIKey();
				$setting_include_analytics = self::includeAnalyticsCode();
			}

			$validated = self::getValidationStatus();
			$valid_status = '<i class="fa fa-times gf_keystatus_invalid"></i>';
			if ( $validated ) {
				$valid_status = '<i class="fa fa-check gf_keystatus_valid"></i>';
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
										<?php echo $valid_status; ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="gf_bsdhubspot_api_key">HubSpot API Key</label></th>
									<td>
										<input type="text" style="width:350px" class="code pre" name="gf_bsdhubspot_api_key" value="<?php echo $setting_api_key; ?>" />
										<?php echo $valid_status; ?>
									</td>
								</tr>
								<?php /* ?>
								<tr>
									<th scope="row"><label for="gf_bsdhubspot_app_domain">HubSpot Application Domain</label></th>
									<td>
										<input type="text" style="width:350px" class="code pre" name="gf_bsdhubspot_app_domain" value="<?php echo $setting_app_domain; ?>" />
										<?php echo $valid_status; ?>
									</td>
								</tr> 
								<?php //*/ ?>
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
				<style type="text/css">
					.ul-square li { list-style: square!important; }
					.ol-decimal li { list-style: decimal!important; }
				</style>
				<div class="wrap">
					<?php
						if ( !isset($_GET['sub']) || $_GET['sub'] != 'make_connection' ) : 
							
							echo '<h2><span>HubSpot > Gravity Forms</span></h2>';

							if ( $_GET['sub'] == 'delete_connection' ) {
								if ( self::deleteConnection($_GET['connection_id'])) {
									echo '<div class="updated fade"><p>Connection deleted successfully!</p></div>';
								}
								else {
									echo '<div class="error fade"><p>Something went wrong. Either Invalid Connection ID, or unable to connect to Database.</p></div>';
								}
							}

							if ( !self::getValidationStatus() ) {
								echo '<div id="message" class="error"><p>Please provide valid HubSpot Credentials on the <a href="'.get_admin_url(null, "admin.php?page=gf_settings&subview=HubSpot").'">Gravity Forms > Settings > HubSpot</a> page.</p></div>';
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

		public static function html_connections_make () {

			$error = FALSE;
			$forms_api = new HubSpot_Forms(self::getAPIKey());
			$connection_id = FALSE;

			if ( !self::getValidationStatus() ) {
				echo '<div class="error fade"><p>Invalid HubSpot Credentials. Please verify your credentials on the <a href="'.get_admin_url(null, "admin.php?page=gf_settings&subview=HubSpot").'">Gravity Forms > Settings > HubSpot</a> page.</p></div>';
				self::html_connections_list();
				return;
			}
			elseif ( isset($_GET['connection_id']) ) {
				// let's get the Connection data via the Database
				// @todo
				$connection_id = $_GET['connection_id'];

				$connection = self::getConnections($connection_id);
				$connection = $connection[0];

				if ( empty($_POST) ) {
					foreach ( $connection->form_data['connections'] as $hs => $gf ) {
						if ( !isset($_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$hs] ) ) {
							$_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$hs] = $gf;
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

			if ( isset ($_POST['gf_bsdhubspot_connections']) ) {
				// We have a submission with the connection form fields. Let's try to process that.
				if ( self::_validate_connection( $forms_api ) === TRUE ) {
					// Let's try to save the data! WOOHOO.
					$hubspot_form = $forms_api->get_form_by_id($hubspot_id);
					$gravity_form = GFFormsModel::get_form_meta($gravityform_id);
					$hubspot_to_gf_connections = array ();
					if ( is_array($hubspot_form->fields) ) : foreach ( $hubspot_form->fields as $field ) : 
						$hubspot_to_gf_connections[$field->name] = $_POST[BSD_GF_HUBSPOT_FORMFIELD_BASE.$field->name];
					endforeach; endif;

					$data_to_save = array (
						'hubspot_form_title' => $hubspot_form->name,
						'gravity_form_title' => $gravity_form['title'],
						'connections' => $hubspot_to_gf_connections,
					);

					if ( !($connection_id = self::saveConnection ( $gravityform_id, $hubspot_id, $data_to_save, $connection_id )) ) {
						echo '<div class="error fade"><p>We could not save the Connection for an unknown reason. Please try again.</p></div>';
					}
					else {
						echo '<div class="updated fade"><p>Connection saved successfully!</p></div>';
						self::html_connections_list();
						return;
					}
				}
			}

			echo '<h3>Match the fields in your HubSpot Form to the fields in your Gravity Form.</h3>';

			echo '<p><a href="admin.php?page=bsdgfhubspot_forms">&laquo; Back to HubSpot Connections</a></p>';

			// Get the GF Form
			$gravity_fields = array ();
			$gravity_form = GFFormsModel::get_form_meta($gravityform_id);
			if ( !$gravity_form || count($gravity_form['fields']) == 0 ) {
				echo '<div class="error fade"><p>Gravity Form has no Fields. Please create some fields and try to make a Connection again.</p></div>';
			}
			else {
				// Get all of these fields in an array (so we can properly tag the right ones as 'active')
				foreach ( $gravity_form['fields'] as $field ) :
					if ( is_array($field['inputs']) ) {
						//echo '<pre>';var_dump ( $field );echo '</pre>';
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
								<td><label for="<?php echo $field_slug; ?>"><?php echo $field->label; ?><?php echo ( $field->required ? ' <span class="required">*</span>' : ''); ?></a></td>
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
									$edit_url = 'admin.php?page=bsdgfhubspot_forms&sub=make_connection&connection_id=' . $connection->id;
									$delete_url = 'admin.php?page=bsdgfhubspot_forms&sub=delete_connection&connection_id=' . $connection->id;
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
		}

		public static function _form_connection_make () {
			$gf_forms = RGFormsModel::get_forms();
			$gf_form_count = RGFormsModel::get_form_count();

			$hs_forms = self::_hubspot_get_forms();
			$hs_form_count = count($hs_forms);
			?>
			<form method="post" action="?page=bsdgfhubspot_forms&sub=make_connection">
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

			if ( isset($_POST['gf_bsdhubspot_update']) ) {
				check_admin_referer("update", "gf_bsdhubspot_update");
				$setting_portal_id = stripslashes($_POST["gf_bsdhubspot_portal_id"]);
				$setting_app_domain = stripslashes($_POST["gf_bsdhubspot_app_domain"]);
				$setting_api_key = stripslashes($_POST["gf_bsdhubspot_api_key"]);
				$setting_include_analytics = (isset($_POST["gf_bsdhubspot_include_analytics"]) ? "yes" : "no");
				update_option("gf_bsdhubspot_portal_id", $setting_portal_id);
				update_option("gf_bsdhubspot_app_domain", $setting_app_domain);
				update_option("gf_bsdhubspot_api_key", $setting_api_key);
				update_option("gf_bsdhubspot_include_analytics", $setting_include_analytics);

				if ( $echo ) echo '<div class="updated fade"><p>Settings Saved Successfully</p></div>';

				// Let's validate the data.
				$api_check = self::_hubspot_validate_credentials($setting_api_key, $setting_portal_id);
				if ( $api_check === TRUE ) {
					// if it's validated, let's mark it as such
					update_option("gf_bsdhubspot_api_validated", "yes");
				}
				else {
					update_option("gf_bsdhubspot_api_validated", "no");
					if ( $echo ) echo '<div class="error fade"><p>API Error: '.$api_check.'</p></div>';
				}

				return TRUE;
			}

			return FALSE;
		} // function

		/**
		 *	_validate_connection ()
		 *
		 *		Validates the form data
		 *
		 *	@param object $forms_api (optional)
		 *	@param bool $echo (optional)
		 *	@return bool|string
		 */
		private static function _validate_connection ( $forms_api=FALSE, $echo=TRUE ) {
			if ( !$forms_api ) $forms_api = new HubSpot_Forms(self::getAPIKey());

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
		 *	_hubspot_validate_credentials ()
		 *
		 *	@param string $HAPIKey
		 *	@param string $portal_id
		 *	@return boolean
		 */
		private static function _hubspot_validate_credentials ( $HAPIKey, $portal_id ) {
			$forms_api = new HubSpot_Forms($HAPIKey);

			$forms = $forms_api->get_forms();
			if ( isset($forms->status) && $forms->status == 'error' ) {
				return $forms->message;
			}

			return TRUE;
		} // function

		public static function _hubspot_get_forms () {

			$forms_api = new HubSpot_Forms(self::getAPIKey());

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
			$setting_portal_id = self::getPortalID();
			$setting_app_domain = self::getAppDomain();
			$setting_api_key = self::getAPIKey();

			if ( !$setting_portal_id || $setting_portal_id == '' || !$setting_api_key || $setting_api_key == '' ) {
				$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - HubSpot Credentials are Missing. Please go to the <a href="'.get_admin_url(null, "admin.php?page=gf_settings&subview=HubSpot").'">Forms > Settings > HubSpot</a> page to supply valid HubSpot credentials.</p>';
			}
			elseif ( !self::getValidationStatus() ) {
				// Show message if the hubspot credentials are INVALID (can't connect to API)
				$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - Invalid HubSpot credentials. Please provide valid HubSpot credentials. Plugin will not work correctly until valid credentials provided.</p>';
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

			if(!class_exists('RGForms')) {
				if(file_exists(WP_PLUGIN_DIR.'/gravityforms/gravityforms.php')) {
					$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - Gravity Forms is installed but not active. <strong>Activate Gravity Forms</strong> to use this plugin.</p>';
				} else {
					$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - <a href="http://gravityforms.com">Gravity Forms</a> is required. You do not have the Gravity Forms plugin enabled.</p>';
				}
			}

			if ( !isset($message) && !bsdGFHubspotAdmin::_gravityforms_valid_version() ) {
				$message = '<p><strong>'.BSD_GF_HUBSPOT_PLUGIN_NAME.'</strong> - A minimum version of '.BSD_GF_HUBSPOT_MIN_GFVERSION.' of Gravity Forms is required to run the HubSpot Addon. Please upgrade Gravity Forms.</p>';
			}

			if ( isset ( $message ) ) {
				if ( $echo ) echo '<div id="message" class="error">'.$message.'</div>';
				return FALSE;
			}

			return TRUE;
		} // function


		/**
		 *	_gravityforms_add_submenus ()
		 *	
		 *		Adds any submenus
		 *
		 *	@param array $menus
		 *	@return array
		 */
		public static function _gravityforms_add_submenus ( $menus ) {

			$menus[] = array (
				"name" => "bsdgfhubspot_forms", 
				"label" => "HubSpot", 
				"callback" => array ("bsdGFHubspotAdmin", "html_page_connections"), 
				"permission" => "gform_full_access"
			);

			return $menus;
		} // function

	} // class
?>