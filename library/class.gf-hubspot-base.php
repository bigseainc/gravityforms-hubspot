<?php
    
    /*
        Package: Wordpress
        Sub Package: Gravity Forms HubSpot Add-On
        
        Shared functionality between the two files.
    */

    GFForms::include_feed_addon_framework();

    if ( !class_exists('GF_Hubspot_Base') ) :
    class GF_Hubspot_Base extends GFFeedAddOn {

        protected   $_hubspot;
        private     $_confirmed_hubspot_once;
        private     $_cache_group = 'gfhs_forms';

        /**
         *  refresh_oauth_token
         *
         *      Refreshes the oAuth token. Can do this REGARDLESS of validation steps.
         *
         *  @since 1.5
         *  @param string $refresh_token
         *  @return none
         */
        public function refresh_oauth_token ( $refresh_token=FALSE ) {
            if ( !$refresh_token ) {
                $token_details = $this->bsd_get('token_oauth');
                $refresh_token = $token_details['refresh_token'];
            }

            require_once ( GF_HUBSPOT_PATH . 'library/hubspot/class.auth.php');
            $api = new HubSpot_Auth(NULL, $this->bsd_get('hub_id'));
            $new_token = $api->refreshOAuthToken($refresh_token, GF_HUBSPOT_CLIENT_ID);

            if ( $new_token ) :
                // Example: {"portal_id":xxx,"expires_in":28799,"refresh_token":"yyy","access_token":"zzz"}
                $data = array (
                    'access_token' => $new_token->access_token,
                    'refresh_token' => $new_token->refresh_token,
                    'hs_expires_in' => $new_token->expires_in,
                    'bsd_expires_in' => (time() + (int)$new_token->expires_in) - 7200 // Let's do this a half hour earlier than too late.
                );
                $this->bsd_set( 'token_oauth', $data );
                return $data;
            endif;

            return FALSE;
        } // function

        /**
         *  Get the HubSpot Forms connection.
         *
         *  @param string $type (optional)
         *  @param string $token (optional)
         *  @return string if failure, object if success
         */
        public function get_connection ( $type=false, $token=false ) {
            if ( !class_exists('HubSpot_Forms') ) {
                GF_Hubspot_Tracking::log(__METHOD__ . '(): HubSpot Library Not Found. How in the what?');
                return 'HubSpot Library Not Found.';
            }

            if ( !$type ) {
                $type = $this->bsd_get('connection_type');
                if ( !$type ) {
                    $type = 'oauth';
                }
            }

            if ( !$token ) {
                $token = $this->bsd_get('token_'.$type);
            }

            if ( $type == 'oauth' && !is_array($token) ) {
                GF_Hubspot_Tracking::log('Invalid oAuth Token. This is usually a temporary issue.', $token);
                return 'Invalid oAuth token. This is usually a temporary issue.';
            }

            if ( $type == 'oauth' && time() > $token['bsd_expires_in'] ) {
                // We're dealing with oauth and the token is expired/about to expire. Let's renew.
                $token = $this->refresh_oauth_token( $token['refresh_token'] );
            }

            if ( !$this->_hubspot ) {
                if ( $type == 'oauth' ) {
                    $this->_hubspot = new HubSpot_Forms($token['access_token'], GF_HUBSPOT_CLIENT_ID);
                }
                else {
                    $this->_hubspot = new HubSpot_Forms($token);
                }
            }

            // Prevents too many API calls, but we do want to check at least ONCE every time the page loads, just to be sure.
            if ( !$this->_confirmed_hubspot_once ) {
                $forms = $this->_hubspot->get_forms();
                if ( isset($forms->status) && $forms->status == 'error' ) {
                    $this->_hubspot = null;
                    GF_Hubspot_Tracking::log('[HubSpot Error] ' . $forms->message);
                    return $forms->message;
                }
                $this->_confirmed_hubspot_once = true;
            }

            return $this->_hubspot;
        } // function
        

        /**
         *  Public Getter and Setter methods because I need to set this shit more often than the admin panel.
         */
        public function bsd_get ( $setting ) {
            return $this->get_plugin_setting($setting);
        } // function
        public function bsd_set ( $field, $value ) {
            $settings = $this->get_plugin_settings();

            // @todo validation? for now just trusting that it's only me adding this and that I'm doing it correctly.
            $settings[$field] = $value;

            return $this->update_plugin_settings( $settings );
        } // function


        /**
         *  authenticate ()
         *
         *      If token isn't included, let's check the one that's already stored.
         */
        public function authenticate( $type=false, $token=false ) {

            // No type provided, then we must have it stored.
            if ( !$type ) {
                $type = $this->bsd_get('connection_type');
                if ( !$type ) {
                    // We don't even have it STORED?? OMG. ok, there's no way we have enough data stored.
                    $this->bsd_set('connection_type', 'oauth');
                    $type = 'oauth';
                }
            }

            // We don't have a token provided, so let's assume we already have it stored.
            if ( !$token ) {
                $token = $this->bsd_get('token_' . $type );
                if ( !$token ) {
                    // still... not enough data stored, so of course it's not authenticated.
                    GF_Hubspot_Tracking::log('Token for "'.$type.'" Missing');
                    return false;
                }
            }

            // oAuth? No, not Hoth.
            if ( $type == 'oauth' && $this->bsd_get('hub_id') && !is_string($this->get_connection( $type, $token )) ) {
                return true;
            }
            
            // We must be dealing with API Keys.
            if ( $type == 'apikey' && !is_string($this->get_connection($type, $token)) ) {
                return true;
            }

            // Two primary reasons for getting here: Invalid credentials, or OMFG HOW ARE YOU ACCESSING THIS WITHOUT OAUTH/API SELECTED
            GF_Hubspot_Tracking::log('Invalid Credentials');
            return false;
        } // function

        /**
            CALLS TO HUBSPOT
         */

        protected function _get_forms () {
            $cache = new GF_Hubspot_Cache();

            $transient_name = 'forms';

            $data = $cache->get( $transient_name );
            if ( GF_HUBSPOT_DEBUG || !$data ) {
                $data = $this->_hubspot->get_forms();

                GF_Hubspot_Tracking::log(__METHOD__ . '(): Forms Received From HubSpot', $data);
                // Only store if data returned is valid JSON
                if ( $data ) {
                    $cache->set( $transient_name, $data );
                }
            }
            else {
                GF_Hubspot_Tracking::log(__METHOD__ . '(): Forms collected from CACHE');
            }

            return $data;
        } // function

        protected function _get_form ( $guid ) {
            if ( !$guid ) return false;

            $cache = new GF_Hubspot_Cache();

            $transient_name = 'form_' . $guid;
            $data = $cache->get( $transient_name );
            if ( GF_HUBSPOT_DEBUG || !$data ) {
                $data = $this->_hubspot->get_form_by_id( $guid );

                GF_Hubspot_Tracking::log(__METHOD__ . '(): Form ['.$guid.'] details collected from HubSpot ', $data );
                // Only store if data returned is valid JSON
                if ( $data ) {
                    $cache->set( $transient_name, $data );
                }
            }
            else {
                GF_Hubspot_Tracking::log(__METHOD__ . '(): Form ['.$guid.'] details collected from CACHE');
            }

            return $data;
        } // function

    } // class
    endif;
