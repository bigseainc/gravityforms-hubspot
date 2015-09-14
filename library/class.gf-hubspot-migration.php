<?php

class GF_Hubspot_Migration {

    private static $_v2_table_name;
    private static $_v2_formfield_base;

    public function process_migrations ( $version=false ) {
        // We're not on our first loop, let's get the version they're currently on.
        if ( !$version ) {
            $version = get_transient ('gf_hubspot_needs_migration');

            // Honestly I should never be missing a migration variable if this migration shit is loading, but... just in case.
            if ( !$version ) return true;
        }

        // 2.0 GFFeedAddon Migration
        if ( version_compare($version, '2.0', '<') ) {
            self::migrate_to_v2();
            set_transient ('gf_hubspot_needs_migration', 2.0);
            return self::process_migrations('2.0');
        }

        // We are 100% done, we don't need this variable anymore.
        delete_transient( 'gf_hubspot_needs_migration' );
        // Add admin success notification.
        add_action( 'admin_notices', array('GF_Hubspot_Migration', 'notice_successful_upgrade'));
        return true;
    } // function

    public function notice_successful_upgrade () {
        echo '
            <div class="updated">
                <p>HubSpot for Gravity Forms has been successfully updated and migrated. Please <a href="'.get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-hubspot').'">verify your settings</a> and connections before continuing.</p>
                <p>To learn more, visit our <a href="https://wordpress.org/plugins/gravityforms-hubspot/changelog/" target="_blank">Changelog</a>.</p>
            </div>
        ';
    } // function

    public function migrate_to_v2 () {
        GF_Hubspot_Tracking::log('Migration Assistance is running');

        global $wpdb;

        self::$_v2_table_name = $wpdb->prefix . "rg_hubspot_connections";
        self::$_v2_formfield_base = 'hsfield_';

        // Get the plugin instance
        $gf_hubspot = gf_hubspot();

        // Migrate the Settings
        $gf_hubspot->bsd_set('hub_id', get_option('gf_bsdhubspot_portal_id'));
        $gf_hubspot->bsd_set('connection_type', get_option('gf_bsdhubspot_connection_type'));
        $gf_hubspot->bsd_set('token_oauth', get_option('gf_bsdhubspot_oauth_token'));
        $gf_hubspot->bsd_set('token_apikey', get_option('gf_bsdhubspot_api_key'));
        $gf_hubspot->bsd_set('include_js', (get_option("gf_bsdhubspot_include_analytics") == "yes" ? 1 : 0));

        // Foreach existing connection, migrate over.
        $connections = self::pre_v2_connections();
        $count = 1;
        if ( $connections ) : 
            GF_Hubspot_Tracking::log('Migrating found connections.');
            foreach ( $connections as $connection ) :
            $name = 'Migrated Feed ' . $count;
            $gform_id = $connection->gravityforms_id;

            $meta = array (
                'feedName'  => $name,
                'formID'    => $connection->hubspot_id,
            );

            foreach ( $connection->form_data['connections'] as $hs_field_name => $data ) {
                $slug = 'fieldMap_' . $hs_field_name;
                if ( is_array($data) ) :
                    $meta[$slug] = $data['gf_field_name'];
                else :
                    $meta[$slug] = $data;
                endif;
            }

            $gf_hubspot->insert_feed($gform_id, $is_active=1, $meta);

            $count++;
        endforeach; endif;

        // Delete all of the older stuff.
        delete_option('gf_bsdhubspot_portal_id');
        delete_option('gf_bsdhubspot_connection_type');
        delete_option('gf_bsdhubspot_oauth_token');
        delete_option('gf_bsdhubspot_api_key');
        delete_option("gf_bsdhubspot_include_analytics");
        
        // Drop the table.
        $sql = "DROP TABLE IF EXISTS ".self::$_v2_table_name."";
        $wpdb->query( $sql );
    } // function

    public function pre_v2_connections () {
        global $wpdb;

        $sql = "SELECT * FROM ".self::$_v2_table_name."";
        $sql .= " ORDER BY `id` desc";

        $results = $wpdb->get_results($sql);

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

} // class