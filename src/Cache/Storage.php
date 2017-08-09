<?php

/*
    Package: Wordpress
    Sub Package: Gravity Forms HubSpot Add-On
    
    Custom Caching because GRRRR, WP
*/

namespace BigSea\GFHubSpot\Cache;

class Storage {

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
        $this->_manifest = new Manifest($manifestFile);
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
        $currentTime    = new \DateTime();
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
            \BigSea\GFHubSpot\Tracking::log('Could not create cache directory', $this->_cachePath );            
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