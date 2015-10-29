<?php

class GF_Hubspot_Cache {

    private $_cachePath;
    private $_cacheTimeout;

    public function __construct () {
        $this->_cachePath       = apply_filters( 'gfhs_cache_path', GF_HUBSPOT_PATH . 'cache/' );
        $this->_cacheTimeout    = apply_filters( 'gfhs_cache_timeout', HOUR_IN_SECONDS );
    } // function

    public function get ( $name ) {
        return false;
    } // function

    public function set ( $name, $data ) {
        // do nothing currently.
    } // function

    private function _calculateTimeout () {
        return (time() + (int)$_cacheTimeout);
    } // function

    private function _cacheTimedOut ( $cacheTime ) {
        return (time() >= $cacheTime);
    } // function

    private function _updateCacheManifest ( $name ) {

    } // function

    private function _checkCacheManifest ( $name ) {

    } // function
} // class