<?php

/*
    Package: Wordpress
    Sub Package: Gravity Forms HubSpot Add-On
    
    Custom Caching because GODDAMNIT, WP
*/

class GF_Hubspot_Manifest {
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
        $date = new DateTime ();
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
            GF_Hubspot_Tracking::log('Caching is not Enabled. Folder is not writable.');
            return false;
        }

        while ( !feof( $handle ) ) {
            $line = fgets($handle);
            if ( trim($line) == '' ) continue;
            
            list($guid, $timeToExpiration) = explode('::', $line);
            $this->_manifestContents[$guid] = new DateTime(date('Y-m-d H:i:s', trim($timeToExpiration)));
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

class GF_Hubspot_Cache {

    private $_cachePath;
    private $_cacheLimit;
    private $_manifest;

    private $_cacheRecordName;

    public function __construct () {
        $upload_dir = wp_upload_dir();
        $default_cache_path = $upload_dir['basedir'] . '/gf_hubspot_cache';
        
        $this->_cacheLimit = apply_filters( 'gfhs_cache_limit', 900 );
        $this->_cachePath = trailingslashit(apply_filters( 'gfhs_cache_path', $default_cache_path ));
        
        $this->_checkCachePathDirectoryAndCreateIfNeeded();

        $manifestFile = $this->_cachePath . '.manifest';
        $this->_manifest = new GF_Hubspot_Manifest($manifestFile);
    } // function

    public function get ( $name ) {
        $this->_cacheRecordName = $name;

        if ( $this->_cacheTimedOut() ) return false;

        return $this->_getCacheFileContents();
    } // function

    public function set ( $name, $data ) {
        $this->_cacheRecordName = $name;

        if ( $this->_setCacheFileContents( $data ) ) {
            // Only save the Manifest Record if the file was created successfully.
            $this->_manifest->updateManifestRecord( $name, $this->_cacheLimit );
            return true;
        }

        return false;
    } // function

    private function _cacheTimedOut () {
        $currentTime    = new DateTime();
        $cacheTime      = $this->_manifest->getManifestRecord($this->_cacheRecordName);

        // It's timed out if cacheTime doesn't exist or it's not a DateTime object.
        if ( is_bool($cacheTime) ) return true;
        if ( !is_a($cacheTime, 'DateTime') ) return true;

        // Timed out if the cacheTime is in the past.
        return ($cacheTime <= $currentTime);
    } // function

    private function _cacheFilePath () {
        return $this->_cachePath . $this->_cacheRecordName;
    }

    private function _cacheFileMissing() {
        if ( !file_exists( $this->_cacheFilePath() ) ) {
            $this->_manifest->removeManifestRecord($this->_cacheRecordName);
            return true;
        }

        return false;
    }

    private function _checkCachePathDirectoryAndCreateIfNeeded () {
        if ( wp_mkdir_p( $this->_cachePath ) !== TRUE ) {
            GF_Hubspot_Tracking::log('Could not create cache directory', $this->_cachePath );            
        }
    }

    private function _getCacheFileContents () {
        if ( $this->_cacheFileMissing() ) return false;

        return json_decode ( file_get_contents ( $this->_cacheFilePath() ) );
    }

    private function _setCacheFileContents ( $data ) {
        return file_put_contents( $this->_cacheFilePath(), json_encode($data) );
    }
} // class