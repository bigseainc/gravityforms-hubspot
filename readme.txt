=== Better HubSpot for Gravity Forms ===
Contributors: Big Sea, Soben, bloqhead
Donate link: http://bigseadesign.com/
Tags: hubspot, gravity, forms, submit, submission, lead, api, gravity forms
Requires at least: 3.5
Tested up to: 3.9
Stable tag: 0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This Gravity Forms add-on sends entry submission data to the HubSpot Customer Forms API

== Description ==

This Gravity Forms add-on sends entry submission data to the HubSpot Customer Forms API. Requires a HubSpot account and API/oAuth access. Plugin is in active development by the Big Sea team.

== Installation ==

1. Upload the files to the /wp-content/plugins/gravityforms-hubspot/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Forms->Settings->HubSpot, and provide valid credentials to connect to your HubSpot Account either via oAuth (recommended) or API Key
1. Go to Forms->HubSpot and make your first connection!

*This plugin provides many checks for a proper version of Gravity Forms, as well as HubSpot API validation. Notices will appear at the top of the Admin panel until these issues are resolved or the plugin is deactivated.*

== Frequently Asked Questions ==

= Is my Hub ID required? =

Sort of. If you plan to connect to HubSpot via oAuth, yes, we do need your Hub ID. Also, if you want us to provide the HubSpot Analytics Tracking code in your theme, we'd need your Hub ID as well.

If you're going to connect via API Key, we do not need your Hub ID.

= Where do I find my HubSpot Hub ID? =

After logging into your account on HubSpot.com, your Hub ID can be found with the product version at the bottom of your HubSpot Dashboard in the footer.

= Where do I find my HubSpot API Key? =

While logged into your account on HubSpot.com, click your name on the top right corner of the page, and go to Settings. On the left sidebar click "API Access" and then click "Generate Token!"

Copy and Paste the generated Token to the API Key field in Forms->Settings->HubSpot in the Wordpress Admin.

== Screenshots ==

1. The settings page
2. The page for handling Gravity Forms to HubSpot form connections.

== Changelog ==

=0.7=
* Rewrote HubSpot API connectivity to support oAuth capability
* Added support for HubSpot oAuth. If you already have an account set up with the API Key, it will continue to work. oAuth is optional (but highly recommended)

= 0.6.1 =
* Cleaned up references to Settings/Connections pages for future expansion

= 0.6 =
* Fixed references to Settings page. Added link to it via WP-Admin/Plugins

= 0.5 =
* Integration with Forms HubSpot API
* Connections add/edit/delete
* Tracking Analytics can be included in footer, if requested

== Upgrade Notice ==

= 0.7 =
Introduces oAuth. Highly recommend switching to oAuth (preferred by HubSpot)

= 0.6 =
Fix for Settings page references on the site.

= 0.5 =
Initial Release.
