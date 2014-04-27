=== Better HubSpot for Gravity Forms ===
Contributors: Soben, bloqhead
Donate link: http://bigseadesign.com/
Tags: hubspot, gravity, forms, submit, submission, lead, api, gravity forms
Requires at least: 3.5
Tested up to: 3.8
Stable tag: 0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This Gravity Forms add-on sends entry submission data to the HubSpot Customer Forms API

== Description ==

This Gravity Forms add-on sends entry submission data to the HubSpot Customer Forms API. Requires a HubSpot account and API access.

Plugin is in active development.

= Coming Up =
1. oAuth integration

== Installation ==

1. Upload the files to the /wp-content/plugins/gravityforms-hubspot/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Forms->Settings->HubSpot, and provide your Hub ID and API Key
1. Go to Forms->HubSpot and make your first connection!

*This plugin provides many checks for a proper version of Gravity Forms, as well as HubSpot API validation. Notices will appear at the top of the Admin panel until these issues are resolved or the plugin is deactivated.*

== Frequently Asked Questions ==

= Is my Hub ID required? =

No, only your API Key is required. However, if you want this plugin to provide the HubSpot Tracking Analytics script automatically for you, one must be provided in order to properly do so.

= Where do I find my HubSpot Hub ID? =

After logging into your account on HubSpot.com, your Hub ID can be found with the product version at the bottom of your HubSpot Dashboard in the footer.

= Where do I find my HubSpot API Key? =

While logged into your account on HubSpot.com, click your name on the top right corner of the page, and go to Settings. On the left sidebar click "API Access" and then click "Generate Token!"

Copy and Paste the generated Token to the API Key field in Forms->Settings->HubSpot in the Wordpress Admin.

== Screenshots ==

1. The settings page
2. The page for handling Gravity Forms to HubSpot form connections.

== Changelog ==

= 0.5 =
* Integration with Forms HubSpot API
* Connections add/edit/delete
* Tracking Analytics can be included in footer, if requested

== Upgrade Notice ==

= 0.5 =
Initial Release.
