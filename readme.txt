=== Better HubSpot for Gravity Forms ===
Contributors: Big Sea, Soben, bloqhead
Donate link: http://bigseadesign.com/
Tags: hubspot, gravity, forms, submit, submission, lead, api, gravity forms
Requires at least: 3.5
Tested up to: 3.9
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.

== Description ==

If you’ve got a WordPress site that uses Gravity Forms, you’ve probably already spent time designing them to match your site and function the way you need - responsive, beautiful forms.  Now, you or your client wants to integrate HubSpot and you need a way to get those contacts into your lead management funnel - well here’s your solution!

* Authenticate using oAuth or add your HubSpot API credentials
* Create a form in HubSpot with fields that match all (or any part) of your fields in Gravity Forms
* Profit! 

== Installation ==

1. Upload the files to the /wp-content/plugins/gravityforms-hubspot/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Forms->Settings->HubSpot, and provide valid credentials to connect to your HubSpot Account either via oAuth (recommended) or API Key
1. Go to Forms->HubSpot and make your first connection!

*This plugin provides many checks for a proper version of Gravity Forms, as well as HubSpot API validation. Notices will appear at the top of the Admin panel until these issues are resolved or the plugin is deactivated.*

== Frequently Asked Questions ==

= Is my Hub ID required? =

Yes. Your Hub ID is required to connect to oAuth, and for including the Analytics Tracking javascript, if you check the box for us to provide it (*HubSpot for Wordpress, their official app, already includes this*)

*If you are going to connect via API Key, and not include the tracking script, we do not need your Hub ID.*

= Where do I find my HubSpot Hub ID? =

After logging into your account on HubSpot.com, your Hub ID can be found with the product version at the bottom of your HubSpot Dashboard in the footer.

= Where do I get a HubSpot API Key? =

Fill out the form on the following link, and click "Get My API Key": https://app.hubspot.com/keys/get -- You will receive an email containing your API Key, once approved.

= Why do I keep losing my oAuth connection? =

HubSpot's oAuth API requires a new token roughly every 8 hours. If no one visits the website for more than an 8 hours period, this script can't get a newer token, and has to be re-validated. We are still working on a way to resolve this. However, at this point it will likely require a server CRON being set up.

**We highly recommend you use oAuth: it's more secure, and you can safely (and easily) revoke access at any time through HubSpot.**

== Screenshots ==

1. The settings page
2. The page for handling Gravity Forms to HubSpot form connections.
3. An example of the "Connection" Page between Gravity Forms and HubSpot

== Changelog ==

= 1.1.3 =
* undefined variable issue found by kingchills, resolved.
* clarified the oAuth token invalid warning for when it's encountered (due to the 8 hour limit that HubSpot implements)
* decreased the amount of time between token renewals to try to not encounter that invalid token issue.

= 1.1.2 =
* HubSpot_Exception class missing. This fixes that. So weird, though, because not a single example of the HubSpot API class requires this class in their codes.

= 1.1 =
* Fixed issue that prevented users from creating new Connections.
* Added some behind the scenes logging for HubSpot to know how many active users we have using this plugin. Privacy Policy to come.

= 1.0 =
* Tightened up code to release it as version 1.0
* Brought back support to Gravity Forms 1.6

= 0.7.1 =
* Updated the requirements for Gravity Forms to a minimum of 1.7.x, due to GFFormsModule not being available to v1.6.4, and we use this for the Connectivity. I will find a way around this.
* Updated FAQ to properly show where the API Key can be found in HubSpot.

= 0.7 =
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

= 1.1 =
Vital change. Fixes the ability to create new connections.

= 0.7 =
Introduces oAuth. Highly recommend switching to oAuth (preferred by HubSpot)

= 0.6 =
Fix for Settings page references on the site.

= 0.5 =
Initial Release.
