<?php
    /**
     *  Start up all of our magic. Formerly in the root file but it's here now for more accurate launching (after GravityForms loads)
     *
     *  @since 2.0
     */

    // Big Sea Handlers
    require_once ( GF_HUBSPOT_PATH . 'library/class.gf-hubspot-tracking.php');
    require_once ( GF_HUBSPOT_PATH . 'library/class.gf-hubspot-base.php');
    require_once ( GF_HUBSPOT_PATH . 'library/class.gf-hubspot-cache.php');
    require_once ( GF_HUBSPOT_PATH . 'library/class.gf-hubspot-hooks.php');
    require_once ( GF_HUBSPOT_PATH . 'library/class.gf-hubspot.php');

    // HubSpot Libraries
    require_once ( GF_HUBSPOT_PATH . 'library/hubspot/class.forms.php');