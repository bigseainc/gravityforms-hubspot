<?php
    
namespace BigSea\GFHubSpot;

\GFForms::include_feed_addon_framework();

class Base extends \GFFeedAddOn {

    protected   $hubspot = null;
    protected   $tokenDetailsCache = [];

    const TOKEN_SETTING_FIELD = 'token_oauth2';

    public function getHubSpot ()
    {
        if (!$this->hubspot) {
            $token = GF_HUBSPOT_CLIENT_ID;
            $storedToken = $this->getToken();
            if (is_array($storedToken)) {
                $token = $storedToken['access_token'];
            }

            $this->hubspot = new \SevenShores\Hubspot\Factory([
              'key'      => $token,
              'oauth2'    => true
            ]);
        }

        return $this->hubspot;
    }

    /**
     *  refresh_oauth_token
     *
     *      Refreshes the oAuth token. Can do this REGARDLESS of validation steps.
     *
     *  @since 1.5
     *  @param string $refresh_token
     *  @return none
     */
    public function refreshOAuthToken ($originalToken = false, $grantType = 'refresh_token')
    {
        if (!$originalToken) {
            $originalTokenData = $this->getToken();
            if (!$originalTokenData || !isset($originalTokenData['refresh_token']) || !$originalTokenData['refresh_token']) {
                throw new \Exception('Refresh Token Missing');
            }
            return $this->refreshOAuthToken($originalTokenData['refresh_token']);
        }

        $this->getHubSpot();
        if (!$this->hubspot) {
          throw new \Exception('Missing HubSpot');
        }

        $oauth = $this->hubspot->oAuth2();

        $receivedToken = null;
        try {
            switch ($grantType) {
              case 'authorization_code' :
                $receivedToken = $oauth->getTokensByCode(GF_HUBSPOT_CLIENT_ID, GF_HUBSPOT_CLIENT_SECRET, $this->getRedirectURI(), $originalToken);

                break;
              case 'refresh_token' :
              default :
                $receivedToken = $oauth->getTokensByRefresh(GF_HUBSPOT_CLIENT_ID, GF_HUBSPOT_CLIENT_SECRET, $originalToken);
                break;
            }
        } catch (\Exception $e) {
            Tracking::log('Could not update Token', $e->getMessage());
            $this->updateSetting(self::TOKEN_SETTING_FIELD, null);
            return false;
        }

        $dateToRefresh = strtotime("+{$receivedToken->data->expires_in} seconds");
        $data = array (
            'access_token' => $receivedToken->data->access_token,
            'refresh_token' => $receivedToken->data->refresh_token,
            'hs_expires_in' => $dateToRefresh,
            'bsd_expires_in' => ($dateToRefresh) - 1800, // will run a half hour earlier than required
        );

        Tracking::log('oAuth Token Refreshed', $data);

        // Store this data
        $this->updateSetting(self::TOKEN_SETTING_FIELD, $data);
    } // function

    public function getRedirectURI() {
        $redirect_uri = get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-hubspot&trigger=hubspot_oauth');
        $redirect_uri = str_replace('http://', 'https://', $redirect_uri);

        return $redirect_uri;
    }

    public function getToken()
    {
        return $this->getSetting(self::TOKEN_SETTING_FIELD);
    }

    public function getTokenFromAuthorizationCode ($code = false)
    {
        return $this->refreshOAuthToken($code,'authorization_code');
    }

    public function getTokenInfoFromHubSpot($token = null) {
        try {
            if (!$token) {
                $token = $this->getToken();
                $token = $token['access_token'];
            }

            if (isset($this->tokenDetailsCache[$token])) {
                return $this->tokenDetailsCache[$token];
            }

            $this->getHubSpot();
            $tokenDetails = $this->hubspot->oAuth2()->getAccessTokenInfo($token);
        } catch (\Exception $e) {
            Tracking::log('Could not get Token Information', $token, $e->getMessage());
            return null;
        }

        $this->tokenDetailsCache[$token] = $tokenDetails->data;
        return $tokenDetails->data;
    }

    public function getPortalID() {
        $details = $this->getTokenInfoFromHubSpot();

        if ($details) {
            return $details->hub_id;
        }

        return 000000;
    }

    /**
     *  Public Getter and Setter methods because I need to set this shit more often than the admin panel.
     */
    public function getSetting($setting)
    {
        return $this->get_plugin_setting($setting);
    } // function
    public function updateSetting( $field, $value )
    {
        $settings = $this->get_plugin_settings();
        $settings[$field] = $value;

        return $this->update_plugin_settings($settings);
    } // function


    /**
     *  authenticate ()
     *
     *      If token isn't included, let's check the one that's already stored.
     */
    public function authenticateHubSpot($token = false)
    {
        $tokenDetails = $this->getTokenInfoFromHubSpot($token);

        if (!$tokenDetails || !$tokenDetails->user) {
            return false;
        }

        $dateExpires = strtotime("+{$tokenDetails->expires_in} seconds");
        if (time() > $dateExpires) {
            return false;
        }

        return true;
    } // function

    /**
        CALLS TO HUBSPOT
     */

    protected function _getForms ()
    {
        $cache = new Cache\Storage();
        $transient_name = 'forms';
        $formsResponse = $cache->get( $transient_name );

        if ( GF_HUBSPOT_DEBUG || !$formsResponse ) {
            $formsResponse = array();
            try {
                $forms = $this->hubspot->forms()->all();

                if ($forms && $forms->data) {
                    $formsResponse = $forms->data;
                    $cache->set( $transient_name, $formsResponse );
                }
            } catch (\Exception $e) {
                Tracking::log('Could not get Forms', $e->getMessage());
            }
        }

        return $formsResponse;
    } // function

    protected function getHubSpotContextCookie($form) {
        $hubspotutk     = isset($_COOKIE['hubspotutk']) ? $_COOKIE['hubspotutk'] : null;
        $ip_addr        = $_SERVER['REMOTE_ADDR']; //IP address too.
        $hs_context     = array(
            'hutk'      => $hubspotutk,
            'ipAddress' => $ip_addr,
            'pageUrl'   => apply_filters( 'gf_hubspot_context_url', site_url() ),
            'pageName'  => apply_filters( 'gf_hubspot_context_name', rgars($form, 'title') ),
        );
        if ( rgars ( $feed, 'meta/disableCookie' ) == 1 ) {
            unset($hs_context['hutk']);
        }

        return $hs_context;
    }

    protected function _getForm ($guid)
    {
        if (!$guid) {
            return array();
        }

        $cache = new Cache\Storage();
        $transient_name = 'form_' . $guid;
        $formData = $cache->get( $transient_name );
        
        if ( GF_HUBSPOT_DEBUG || !$formData ) {
            $formData = array();
            try {
                $form = $this->hubspot->forms()->getById($guid);
                if ($form && $form->data) {
                    $formData = $form->data;
                    $cache->set( $transient_name, $formData );
                }
            } catch (\Exception $e) {
                Tracking::log("Could not get Form [{$guid}]", $e->getMessage());
            }
        }

        return $formData;
    } // function
} // class
