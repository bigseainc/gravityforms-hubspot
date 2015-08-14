<?php

$upload_dir = wp_upload_dir();

// Constants that can be controlled by each individual site.
if ( !defined('GF_HUBSPOT_DEBUG') ) define('GF_HUBSPOT_DEBUG', false);
if ( !defined('GF_HUBSPOT_DEBUG_ECHO') ) define('GF_HUBSPOT_DEBUG_ECHO', false);
if ( !defined('GF_HUBSPOT_DEBUG_LOG_PATH') ) define('GF_HUBSPOT_DEBUG_LOG_PATH', $upload_dir['basedir']);


class GF_Hubspot_Tracking {

    private static $message;

    public static function log () {
        if ( !GF_HUBSPOT_DEBUG ) return;

        $messages = func_get_args();
        foreach ( $messages as $message ) :
            self::$message = $message;

            self::show();
            self::write();
        endforeach;
        
    } // function

    private static function show () {
        if ( !GF_HUBSPOT_DEBUG_ECHO ) return;

        var_dump ('[Gravity Forms HubSpot]', self::$message);
    } // function

    private static function write () {
        if ( !GF_HUBSPOT_DEBUG_LOG_PATH ) return;

        $file_name = 'gf-hubspot.log';
        $file_path = GF_HUBSPOT_DEBUG_LOG_PATH . '/' . $file_name;

        $data = self::$message;
        if ( is_array($data) || is_object($data) ) {
            $data = serialize($data);
        }

        // Write to a log file
        $output = '[' . date('Y-m-d H:i:s') . '] ' .$data . PHP_EOL;
        file_put_contents($file_path, $output, FILE_APPEND | LOCK_EX);

    } // function

} // 