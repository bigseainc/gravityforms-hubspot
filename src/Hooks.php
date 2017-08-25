<?php

namespace BigSea\GFHubSpot;

class Hooks {

    public static function admin_notices () {
        $gf_hubspot = gf_hubspot();
        $currentPage = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . strtok($_SERVER["REQUEST_URI"],'?');

        if ( !$gf_hubspot ) return;

        if ( !$gf_hubspot->authenticateHubSpot() ) {
            echo '<div class="notice notice-error">
                <p>HubSpot won\'t work for Gravity Forms until <a href="'.get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-hubspot').'">your settings</a> are successfully validated.</p>
            </div>';

            if (!isset($_SERVER['HTTPS'])) {
                if (strpos(self::_current_page(), 'page=gf_settings&subview=gravityforms-hubspot') !== FALSE) {
                    echo '<div class="notice notice-warning">
                        <p>HubSpot oAuth 2.0 requires an SSL certificate to authenticate. You do not appear to have an SSL secured admin panel.<br/>(If you have one installed correctly, you can ignore this message)</p>
                    </div>';
                }
            }
        }
    } // function


    public static function check_for_token_refresh () {
        $gf_hubspot = gf_hubspot();

        $tokenData = $gf_hubspot->getToken();
        if ($tokenData && isset($tokenData['refresh_token'])) {
            if (time() >= $tokenData['bsd_expires_in']) {
                Tracking::log('Token being refreshed');
                $gf_hubspot->refreshOAuthToken($tokenData['refresh_token']);
            }
        }
    } // function


    public static function check_for_oauth_response () {

        if ( isset($_GET['code']) && $_GET['trigger'] == 'hubspot_oauth' ) {
            // Get the token, and store it.
            $gf_hubspot = gf_hubspot();
            $gf_hubspot->getTokenFromAuthorizationCode($_GET['code']);
            wp_redirect( $gf_hubspot->getRedirectURI() );
            exit;
        } // endif
    } // function


    public static function addAnalyticsToFooter () {
        $gf_hubspot = gf_hubspot();

        $hub_id = $gf_hubspot->getSetting('hub_id');

        if ($hub_id && strlen($hub_id) > 0) {
            if ( !is_admin() ) {
                ?>
                <!-- Start of Async HubSpot Analytics Code -->
                <script type="text/javascript">
                    (function(d,s,i,r) {
                        if (d.getElementById(i)){return;}
                        var n=d.createElement(s),e=d.getElementsByTagName(s)[0];
                        n.id=i;n.src='//js.hs-analytics.net/analytics/'+(Math.ceil(new Date()/r)*r)+'/<?php echo $hub_id; ?>.js';
                        e.parentNode.insertBefore(n, e);
                    })(document,"script","hs-analytics",300000);
                </script>
                <!-- End of Async HubSpot Analytics Code -->
                <?php
            }
        } else {
            Tracking::log(__METHOD__ . '(): Hub ID missing, Analytics not included');
        }
    } // function


    private static function _current_page() {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        $pageURL;

        return $pageURL;
    }
} // class