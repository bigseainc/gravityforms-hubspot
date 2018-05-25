<?php

namespace BigSea\GFHubSpot;

\GFForms::include_feed_addon_framework();
define('GF_HUBSPOT_CLIENT_ID', 'bc2af989-d201-11e3-9bdd-cfa2d230ed01');
define('GF_HUBSPOT_CLIENT_SECRET', '9e931048-3baf-4786-9ce2-3227adec020a');

// Actions
add_action('init', array('\BigSea\GFHubSpot\Hooks', 'check_for_token_refresh'), 1);
add_action('admin_init', array('\BigSea\GFHubSpot\Hooks', 'check_for_oauth_response'), 1);
add_action('admin_notices', array('\BigSea\GFHubSpot\Hooks', 'admin_notices'));

// Filters
class GF_HubSpot extends Base {

    protected $_version = GF_HUBSPOT_VERSION;
    protected $_min_gravityforms_version = '1.9.12';
    protected $_slug = 'gravityforms-hubspot';
    protected $_path = 'gravityforms-hubspot/gravityforms-hubspot.php';
    protected $_full_path = __FILE__;
    protected $_url = 'http://bigsea.co';
    protected $_title = 'Gravity Forms HubSpot Add-On';
    protected $_short_title = 'HubSpot';
    protected $_enable_rg_autoupgrade = false;
    protected $api = null;
    protected $_new_custom_fields = array();
    private static $_instance = null;

    protected $_capabilities_settings_page = 'gravityforms_hubspot';
    protected $_capabilities_form_settings = 'gravityforms_hubspot';
    protected $_capabilities_uninstall     = 'gravityforms_hubspot_uninstall';

    /* Members plugin integration */
    protected $_capabilities = array( 'gravityforms_hubspot', 'gravityforms_hubspot_uninstall' );

    /**
     * Get instance of this class.
     * 
     * @access public
     * @static
     * @return $_instance
     */ 
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new self;
        }

        return self::$_instance;
    } // function

    /**
     * Register needed styles.
     * 
     * @access public
     * @return array $styles
     */
    public function styles() {            
        $styles = array(
            array(
                'handle'  => 'gform_hubspot_form_settings_css',
                'src'     => $this->get_base_url() . '/../assets/style.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array( 'admin_page' => array( 'form_settings' ) ),
                )
            )
        );
        
        return array_merge( parent::styles(), $styles );
    } // function

    /**
     * Add hook for Javascript analytics tracking.
     * 
     * @access public
     * @return void
     */
    public function init_frontend() {
        
        parent::init_frontend();
        
        if ( $this->get_plugin_setting( 'include_js' ) == '1' ) {
            add_action( 'wp_footer', array( 'BigSea\GFHubSpot\Hooks', 'addAnalyticsToFooter' ) );
        }
    } // function


    /**
     * Process feed.
     * 
     * @access public
     * @param array $feed
     * @param array $entry
     * @param array $form
     * @return void
     */
    public function process_feed( $feed, $entry, $form ) {
        if (apply_filters( 'gf_hubspot_conditional_not_met', false, $feed, $entry, $form)) {
            return;
        }
        
        $feed = apply_filters( 'gf_hubspot_process_feed', $feed, $entry, $form );

        // Make sure we have a fresh connection.
        $this->getHubSpot();

        // Let's get the HubSpot Form!
        $form_id = rgars ( $feed, 'meta/formID' );
        $hubspot_form = $this->_getForm( $form_id );
        
        // We are definitely in a good ground moving forward!
        Tracking::log(__METHOD__ . '(): Feed Processing for Form "'.$hubspot_form->name.'" ('.$form_id.')');

        $fieldMap = $this->get_field_map_fields( $feed, 'fieldMap' );

        $data_to_hubspot = array ();
        foreach ( $hubspot_form->formFieldGroups as $fieldGroup ) {
            foreach ($fieldGroup->fields as $field) {
                if ( !isset ( $fieldMap[$field->name] ) ) {
                    continue;
                }

                $gf_field = \GFFormsModel::get_field( $form, $fieldMap[$field->name]);

                $gf_field_value = $this->get_field_value( $form, $entry, $fieldMap[$field->name] );
                if ( $field->required && !$gf_field_value ) {
                    Tracking::log(__METHOD__ . '(): Required field "'.$field->label.'" missing.', $field, $gf_field_value);
                    $this->add_feed_error( 
                        'Required field "'.$field->label.'" for form "'.$hubspot_form->name.'" ['.$form_id.'] missing.', 
                        $feed, 
                        $entry,
                        $form 
                    );
                    return;
                }

                $data_to_hubspot[$field->name] = $this->_get_field_formatted_for_hubspot($field->type, $gf_field_value, $gf_field);
            }
        }
        
        $data_to_hubspot = apply_filters( 'gf_hubspot_data_outgoing', $data_to_hubspot, $form, $feed );

        // With all of the data organized now, let's get the HubSpot call ready.
        $data_to_hubspot['hs_context'] = json_encode($this->getHubSpotContextCookie($form, $feed));

        // Try to send the form.
        try {
            $result = $this->hubspot->forms()->submit($this->getPortalID(), $form_id, $data_to_hubspot);
            $status_code = $result->getStatusCode();

            if ( in_array($status_code, array(200, 204, 302)) ) {
                // Success!
                // 200 - they don't use, but watching anyways.
                // 204 - success and nothing returned
                // 302 - success, but including a redirect (we're ignoring)

                Tracking::log(__METHOD__ . '(): Form Successfully submitted ['.$form_id.']', $data_to_hubspot);
                return;
            }

            // Shouldn't make it here, but if we do, let's log it.
            Tracking::log(__METHOD__ . '(): Form Feed could not be sent to HubSpot ['.$form_id.']', $result, $status_code);
            $this->add_feed_error( 
                'HubSpot rejected the submission with an error '.$status_code.' for "'.$hubspot_form->name.'" ['.$form_id.'].', 
                $feed, 
                $entry,
                $form 
            );
        } catch (\Exception $e) {
            Tracking::log(__METHOD__ . '(): Form Submission Failed ['.$form_id.']', $e->getMessage());
            $this->add_feed_error( 
                'HubSpot caused an unknown error for "'.$hubspot_form->name.'" ['.$form_id.']: ' . $e->getMessage(), 
                $feed,
                $entry,
                $form 
            );
        }
    } // function

    /**
        Admin Panel - Plugin Settings
     */

    /**
     * Setup plugin settings fields.
     * 
     * @access public
     * @param none
     * @return array
     */
    public function plugin_settings_fields() {
                        
        return array(
            array(
                'title'       => '',
                'description' => '<p><a href="http://www.hubspot.com/" target="_blank">HubSpot</a> is an inbound marketing tool to track visitors to your website and convert them into customers and promoters.</p>',
                'fields'      => array(
                   array(
                        "label"             => "oAuth Validation",
                        "type"              => "oauth_validation",
                        "name"              => "oauth_code",
                    ),
                ),
            ),
            array(
                'title'       => __( 'Analytics Tracking', 'gravityforms-hubspot' ),
                'description' => '<p>' . __( 'HubSpot Javascript analytics tracking is a required feature in order for HubSpot API to work correctly. However, if you have already included this via the <a href="https://wordpress.org/plugins/hubspot-tracking-code/" target="_blank">HubSpot for Wordpress</a> Plugin, or within your theme code itself, you do not need to check this box.', 'gravityforms-hubspot' ) . '</p><p><strong>Including Javascript Requires the Hub ID</strong></p>',
                'fields' => array(
                     array(
                        'name'              => 'hub_id',
                        'label'             => esc_html__( 'Hub ID', 'gravityforms-hubspot' ),
                        'type'              => 'text',
                        'class'             => 'medium',
                    ),
                    array(
                        'name'              => 'include_js',
                        'label'             => 'Include HubSpot Analytics JS?',
                        'type'              => 'checkbox',
                        'choices'           => array(
                             array(
                                 'name'     => 'include_js',
                                 'label'    => '',
                             )
                         ),
                    ),
                    array(
                        'type'              => 'save',
                        'messages'          => array(
                            'success' => __( 'HubSpot settings have been updated.', 'gravityforms-hubspot' )
                        ),
                    ),
                )
            )
        ); 
    } // function

    public function settings_oauth_validation () {
        $this->getHubSpot();
        $scopes = array (
            'forms',
            'contacts',
        );
        $authorize_url = $this->hubspot->oAuth2()->getAuthUrl(GF_HUBSPOT_CLIENT_ID, $this->getRedirectURI(), $scopes);
   
        echo '<a class="button" href="'.$authorize_url.'">Click Here to Authenticate</a>';

        // Seriously, only way I can get this output to work. Which is lame. But yea, hidden field, because all I want is the button and paragraph text above.
        $this->settings_hidden(
            array(
                'default_value' => null,
                'name'          => 'token_oauth2'
            )
        );

        if ( self::authenticateHubSpot() ) :
            $tokenDetails = self::getTokenInfoFromHubSpot();
            echo '<p class="description small">You are currently authenticated with HubSpot <i class="fa fa-check gf_keystatus_valid"></i></p>';
            echo "
                <ul>
                    <li><strong>Hub:</strong> {$tokenDetails->hub_domain} [{$tokenDetails->hub_id}]</li>
                     <li><strong>User:</strong> {$tokenDetails->user}</li>
                </ul>
            ";
        endif;
    } // function

    /**
        FEED SETTINGS
     */

    /**
     * Setup fields for feed settings.
     * 
     * @access public
     * @return array
     */
    public function feed_settings_fields() {

        $settings = parent::feed_settings_fields();
        
        // Build base fields array.
        $base_fields = array(
            'title'  => '',
            'name' => 'baseFields',
            'fields' => array(
                array(
                    'name'           => 'feedName',
                    'label'          => __( 'Feed Name', 'gravityforms-hubspot' ),
                    'type'           => 'text',
                    'required'       => true,
                    'tooltip'        => '<h6>'. __( 'Name', 'gravityforms-hubspot' ) .'</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityforms-hubspot' )
                ),
                array(
                    'name'           => 'formID',
                    'label'          => __( 'HubSpot Form', 'gravityforms-hubspot' ),
                    'type'           => 'select',
                    'required'       => true,
                    'onchange'       => "jQuery(this).parents('form').submit();",
                    'choices'        => $this->list_hubspot_forms()
                ),
                array(
                    'name'           => 'additionalOptions',
                    'label'          => __( 'Additional Options', 'gravityforms-hubspot' ),
                    'type'           => 'checkbox',
                    'choices'        => array(
                                         array(
                                             'label'         => 'Disable Cookie Tracking',
                                             'name'          => 'disableCookie',
                                             'tooltip'       => '<h6>'. __( 'Disable Cookie', 'gravityforms-hubspot' ) .'</h6>' . __( 'When disabled, every submission from the same browser creates a new contact.', 'gravityforms-hubspot' ),
                                             'default_value' => 0,

                                         ),
                                    )
                )
            )
        );

        // Build form fields array
        $form_fields = array (
            'title' => __( 'Form Connections', 'gravityforms-hubspot' ),
            'name' => 'formConnections',
            'dependency' => array( 'field' => 'formID', 'values' => '_notempty_' ),
            'fields' => array (
                array(
                    'name'           => 'fieldMap',
                    'label'          => __( 'HubSpot to Gravity Forms', 'gravityforms-hubspot' ),
                    'type'           => 'field_map',
                    'field_map'      => $this->list_hubspot_form_fields(),
                    'tooltip'        => '<h6>'. __( 'Map Fields', 'gravityforms-hubspot' ) .'</h6>' . __( 'Select which Gravity Form fields pair with their respective HubSpot fields.', 'gravityforms-hubspot' )
                ),
            ),
        );

        // Build conditional logic fields array.
        $conditional_fields = array(
            'title'      => __( 'Feed Conditional Logic', 'gravityforms-hubspot' ),
            'name' => 'conditionaLogic',
            'dependency' => array( 'field' => 'formID', 'values' => '_notempty_' ),
            'fields'     => array(
                array(
                    'name'    => 'conditionalLogic',
                    'label'   => esc_html__( 'Conditional Logic', 'gravityforms' ),
                    'type'    => 'feed_condition',
                    'tooltip' => '<h6>' . esc_html__( 'Conditional Logic', 'gravityforms' ) . '</h6>' . esc_html__( 'When conditions are enabled, form submissions will only be sent to the payment gateway when the conditions are met. When disabled, all form submissions will be sent to the payment gateway.', 'gravityforms' )
                ),   
            ),
        );

        return array ( $base_fields, $form_fields, $conditional_fields );
        
    } // function

    /**
        FEED LIST
     */

    /**
     * Setup columns for feed list table.
     * 
     * @access public
     * @return array
     */
    public function feed_list_columns() {
        
        return array(
            'feedName' => __( 'Name', 'gravityforms-hubspot' ),
            'formID'   => __( 'HubSpot Form', 'gravityforms-hubspot')
        );
        
    } // function

    /**
     * Get value for action feed list column.
     * 
     * @access public
     * @param array $feed
     * @return string $action
     */
    public function get_column_value_formID( $feed ) {
        $form_details = $this->_getForm( rgars( $feed, 'meta/formID' ) );

        return isset($form_details->name) ? $form_details->name : 'Form Not Found';
        
    } // function


    /**
        HUBSPOT TO GRAVITYFORMS SETTINGS
     */
    protected function list_hubspot_forms ( $include_empty=true ) {
        $output = array();

        if ( !$this->authenticateHubSpot() ) {
            $output[] = array (
                'label' => 'No Forms Found. Please make sure HubSpot is Activated',
                'value' => '',
            );
            return $output; // make sure $this->hubspot is set and active.
        }

        if ( $include_empty ) {
            $output[] = array (
                'label' => 'Select Form',
                'value' => '',
            );
        }

        $forms = $this->_getForms();
        foreach ( $forms as $form ) :
            $output[] = array(
                'label'         => $form->name,
                'value'         => $form->guid,
            );
        endforeach;

        return $output;
    } // function

    protected function list_hubspot_form_fields ( ) {
        $form_guid = $this->_get_feed_current_form_id ();
        
        $output = array ();

        if ( !$this->authenticateHubSpot() ) {
            return $output; // make sure $this->hubspot is set and active.
        }

        $response = $this->_getForm( $form_guid );
        if ( is_object($response) && is_array($response->formFieldGroups) ) {
            foreach ( $response->formFieldGroups as $fieldGroup ) {
                foreach ($fieldGroup->fields as $field) {
                    $field_label = $field->name;
                    if (trim($field->label) != '') {
                        $field_label = $field->label;
                    }
                    $output[] = array (
                        'label'     => $field_label,
                        'name'      => $field->name,
                        'required'  => $field->required,
                        'value'     => $field->name,
                    );
                }
            }
        }

        return $output;
    } // function

    private function _get_feed_current_form_id () {
        $feed = $this->get_current_feed();
        $posted_settings = $this->get_posted_settings();

        // Posted Settings override the Feed Setting.
        if ( $form_id = rgars($posted_settings, 'formID') ) {
            // We have a POST setting
            return $form_id;
        }

        if ( $form_id = rgars($feed, 'meta/formID') ) {
            // We have a saved setting
            return $form_id;
        }

        return false;
    } // function


    private function _get_field_formatted_for_hubspot ( $hs_field_type, $data, $gf_field ) {
        $data = $this->preParseGravityFormData($data, $gf_field);

        switch ( $hs_field_type ) {
            case 'date' :
                return strtotime($data) * 1000;
                break;
            case 'enumeration' :
                // We're expecting multiple pieces of data
                if ( !is_array( $data ) ) {
                    $data = explode(', ', $data);
                    foreach ( $data as &$content ) {
                        $content = trim($content);
                    }
                }

                return implode(';', $data);
                break;
            default :
                return $data;
        }
    } // function

    /**
     * preParseGravityFormData
     * 
     *  We know that some of the data types coming from Gravity Forms will need to be manipulated. 
     *      In this case, we're converting the Field type into a semi-colon separated string.
     */
    private function preParseGravityFormData($data, $gf_field)
    {
        // Currently does nothing. We have no special situations to worry about.

        return $data;
    }

} // class