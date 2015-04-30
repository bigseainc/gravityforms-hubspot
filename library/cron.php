<?php
	/**
	 *	HubSpot for Gravity Forms
	 *		Server Side CRON job.
	 *
	 *		Since HubSpot requires every 8 hours for token renewal, we suggest every 6 hours for the CRON job.
	 *
	 *		Assumes your Wordpress Core is installed in the Site Root by default. If that's not the case, you must correct line 20.
	 *
	 *		If you're unsure of the path to this file, un-comment line 17, and run this file in the browser
	 *			http://yourwebsite.com/wp-content/plugins/gravityforms-hubspot/library/cron.php
	 */		

	//	Suggested Usage:
	//			0 */6 * * * /path/to/wp-content/plugins/gravityforms-hubspot/library/cron.php

	$path = dirname( __FILE__ );
	// echo $path;
	echo '<pre>';
	var_dump( 'Running Script');
	var_dump ( $path );

	require_once( dirname( dirname( dirname( dirname( $path ) ) ) ) . '/wp-load.php' );

	var_dump( 'OK YAY. Call the function');

	// Probably redundant, but hey, I want to make sure this runs :)
	// Our internal Wordpress-powered CRON runs every 6 hours, IF a user visits. This one runs every time the CRON runs, regardless of users visiting.
	bsdGFHubspot::refresh_oauth_token();
	update_option('gf_bsdhubspot_last_validated', time());

	echo '</pre>';