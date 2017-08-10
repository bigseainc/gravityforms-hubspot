<?php

/*
    Package: Wordpress
    Sub Package: Gravity Forms HubSpot Add-On
*/

namespace BigSea\GFHubSpot\Cache;

class Manifest {
    private $_manifestContents = array();
    private $_manifestFile = '';

    const DEFAULT_CACHE_LIMIT = 900; // 1 hour, in seconds

    public function __construct ( $file ) {
        $this->_manifestFile = $file;
        
        $this->_processManifest ();
    } // function


    public function getManifestRecord ( $guid ) {
        if ( isset ( $this->_manifestContents[$guid] ) ) return $this->_manifestContents[$guid];

        return false;
    } // function

    public function updateManifestRecord ( $guid, $timeout = self::DEFAULT_CACHE_LIMIT ) {
        $date = new \DateTime ();
        $date->modify("+{$timeout} second");
        $this->_manifestContents[$guid] = $date;

        $this->_saveManifestFile();
    } // function

    public function removeManifestRecord ( $guid ) {
        unset ( $this->_manifestContents[$guid] );
        $this->_saveManifestFile();
    }

    private function _processManifest () {
        $handle = @fopen( $this->_manifestFile, 'r' );
        if ( !$handle ) {
            \BigSea\GFHubSpot\Tracking::log('Caching is not Enabled. Folder is not writable.');
            return false;
        }

        while ( !feof( $handle ) ) {
            $line = fgets($handle);
            if ( trim($line) == '' ) continue;
            
            list($guid, $timeToExpiration) = explode('::', $line);
            $this->_manifestContents[$guid] = new \DateTime(date('Y-m-d H:i:s', trim($timeToExpiration)));
        }
        fclose($handle);
    }

    private function _saveManifestFile () {
        $handle = @fopen( $this->_manifestFile, 'w' );
        if ( !$handle ) return;
        
        foreach ( $this->_manifestContents as $key => $value) {
            if ( method_exists( $value, 'getTimestamp' ) ) {
                // PHP 5.3.x+
                fwrite($handle, $key . '::' . $value->getTimestamp() . PHP_EOL);
            }
            else {
                // PHP 5.2.x
                fwrite($handle, $key . '::' . $value->format('U') . PHP_EOL);
            }
        } // endforeach
        fclose($handle);

        return true;
    } // function
} // class