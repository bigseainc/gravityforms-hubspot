jQuery(document).ready(function() {
	$bsdhubspot = jQuery('.bsd_hubspot.wrap');

	if ( typeof $bsdhubspot.html() == 'undefined' ) {
		console.log( 'Missing BSD HubSpot Setting Data. That is super weird.' );
		return;
	} // endif

	// Handles the switcheroo on what section is visible.
	function switch_connection_type ( new_type ) {
		if ( new_type != 'oauth' && new_type != 'apikey' ) {
			new_type = 'oauth';
		}

		$bsdhubspot.find('tr.connection_type_section').addClass('inactive');
		$bsdhubspot.find('tr.connection_type_section.connect_via_' + new_type ).removeClass('inactive');
	} // function

	// Handle the initial switch ( we don't want to hide sections if JS doesn't work, do we?? )
	switch_connection_type ( $bsdhubspot.find('input[name=gf_bsdhubspot_connection_type]:checked').val() );
	// Watch all future changes.
	$bsdhubspot.find('#connection_types').on('change', 'input[type=radio]', function (e) {
		switch_connection_type ( jQuery(this).val() );
	}); // onChange

}); // document.ready