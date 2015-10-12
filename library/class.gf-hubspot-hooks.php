<?php

// Actions
add_action('admin_init', array('GF_Hubspot_Hooks', 'check_for_oauth_response'));
add_action('admin_init', array('GF_Hubspot_Hooks', 'check_for_pre_v2_settings'));
add_action('admin_notices', array('GF_Hubspot_Hooks', 'admin_notices'));

if ( get_transient ('gf_hubspot_needs_migration') ) {
    require_once ( GF_HUBSPOT_PATH . 'library/class.gf-hubspot-migration.php' );
    add_action('wp_loaded', array('GF_Hubspot_Migration', 'process_migrations'));
}

// Filters



class GF_Hubspot_Hooks {


    public static function admin_notices () {
        $gf_hubspot = gf_hubspot();

        if ( !$gf_hubspot ) return;

        if ( !$gf_hubspot->authenticate() ) {
            echo '<div class="error">
                <p>HubSpot won\'t work for Gravity Forms until <a href="'.get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-hubspot').'">your settings</a> are successfully validated.</p>
            </div>';
        }
    } // function


    public static function check_for_oauth_response () {

        if ( isset($_GET['access_token']) && $_GET['trigger'] == 'hubspot_oauth' ) {
            // access_token, refresh_token, expires_in
            $data = array (
                'access_token' => $_GET['access_token'],
                'refresh_token' => $_GET['refresh_token'],
                'hs_expires_in' => $_GET['expires_in'],
                'bsd_expires_in' => (time() + (int)$_GET['expires_in']) - 1800, // will run a half hour earlier than required
            );

            GF_Hubspot_Tracking::log('oAuth Response Incoming', $data);

            // Let's make sure this data is valid
            $gf_hubspot = gf_hubspot();

            // Store this data
            $gf_hubspot->bsd_set('token_oauth', $data);

            // Let's make sure it's ACCURATE data.
            return $gf_hubspot->authenticate('oauth', $_GET['access_token']);

        } // endif
    } // function


    public static function check_for_pre_v2_settings () {
        $ignore_notification_this_load = false;

        // Check for Triggers and run them.
        if ( isset($_GET['trigger']) && $_GET['trigger'] == 'gf_hubspot_migrate_v2' ) {
            // Run v2's Upgrade Process
            $ignore_notification_this_load = true;
            set_transient('gf_hubspot_needs_migration', '0.9', 0);
            wp_redirect(get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-hubspot')); exit();
        }
        if ( isset($_GET['trigger']) && $_GET['trigger'] == 'gf_hubspot_dismiss_v2_migration_message' ) {
            // Ignore the upgrade message (booo!)
            set_transient('gf_hubspot_dismiss_v2_migration_message', true);
        }

        if ( !$ignore_notification_this_load 
            && get_option('gf_bsdhubspot_connection_type') 
            && !get_transient('gf_hubspot_dismiss_v2_migration_message') ) {
            
            add_action('admin_notices', array('GF_Hubspot_Hooks', 'notification_admin_migration_continue'));
        }
    } // function

    public static function notification_admin_migration_continue () {
        $trigger_migration = get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-hubspot&trigger=gf_hubspot_migrate_v2');
        $trigger_dismissal = get_admin_url(null, '?trigger=gf_hubspot_dismiss_v2_migration_message');
            echo '
                <div class="update-nag">
                    <p>Your original HubSpot for Gravity Forms settings can be migrated to v2.x <a href="'.$trigger_migration.'">Click here</a> to migrate your settings. Or <a href="'.$trigger_dismissal.'">dismiss this message</a>.</p>
                </div>
            ';
    } // function


    public static function add_analytics_tracking_to_footer () {
        $gf_hubspot = gf_hubspot();

        if ( ($hub_id = $gf_hubspot->bsd_get('hub_id')) && !is_admin() ) :
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
        endif;
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