<?php

/**
 *		Big Sea Tracking functionality
 *
 *			HubSpot requires us to track our users, so this helps us with this process.
 */

if ( !class_exists('BSDTracking') ) :
class BSDTracking {

	private $URL = 'http://bigseadesign.com/gfhubspot-tracking.php';

	function __construct () {
		// Set up anything that's needed all the time

	} // function

	/**
	 *	trigger ()
	 *
	 *		Trigger the tracking for an event.
	 *		
	 */
	public function trigger ( $trigger, $data=FALSE, $message=FALSE ) {
		/*
			$valid_triggers = array (
					'entry_submitted',
					'forms_requested',
					'validated_oauth',
					'validated_apikey',
					'activated_plugin',
					'deactivated_plugin',
					'error_log'
				);
		*/
		if ( !is_array($data) ) {
			$data = array ();
		}

		$data['site_name'] 		= get_bloginfo('name');
		$data['site_url']			= get_bloginfo('wpurl');
		$data['admin_email']		= get_bloginfo('admin_email');

		$result = $this->_call( $trigger, $data, $message );
		return json_decode ( $result );
	} // function

	/**
	 *	_call ()
	 *
	 *		Let's make the call to the server! yay.
	 *
	 *	@param string $trigger
	 *	@param mixed $data
	 */
	private function _call ( $trigger, $data=FALSE, $message=FALSE ) {
		$post_data = array (
				'trigger' 	=> $trigger,
				'data'		=> json_encode($data),
				'message'	=> $message,
			);

		// borrowed from Andy Langton: http://andylangton.co.uk/
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->URL );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		} else {
			// Handle the useragent like we are Google Chrome
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.X.Y.Z Safari/525.13.');
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Populate the data for POST
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

		// Try to make the call
		$result=curl_exec($ch);
		$info=curl_getinfo($ch);
		curl_close($ch);
		
		return $result;
	} // function

} // class
endif;
