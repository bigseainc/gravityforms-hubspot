=== HubSpot for Gravity Forms ===
Contributors: Big Sea, Soben, bloqhead
Donate link: http://bigseadesign.com/
Tags: hubspot, gravity, forms, submit, submission, lead, api, gravity forms
Requires at least: 3.5
Tested up to: 4.2.4
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.

== Description ==

If you’ve got a WordPress site that uses Gravity Forms, you’ve probably already spent time designing them to match your site and function the way you need - responsive, beautiful forms.  Now, you or your client wants to integrate HubSpot and you need a way to get those contacts into your lead management funnel - well here’s your solution!

* Authenticate using oAuth or add your HubSpot API credentials
* Create a form in HubSpot with fields that match all (or any part) of your fields in Gravity Forms
* Profit! 

== Installation ==

1. If you already have the plugin installed, please go to the 'Plugins' menu in WordPress and deactivate it.
1. Upload the files to the /wp-content/plugins/gravityforms-hubspot/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Forms->Settings->HubSpot, and provide valid credentials to connect to your HubSpot Account either via oAuth (recommended) or API Key
1. Go to a Form's Settings->HubSpot section and make your first connection!

*This plugin provides many checks for a proper version of Gravity Forms, as well as HubSpot API validation. Notices will appear at the top of the Admin panel until these issues are resolved or the plugin is deactivated.*

== Frequently Asked Questions ==

= Is my Hub ID required? =

Yes. Your Hub ID is required to connect to oAuth, and for including the Analytics Tracking javascript, if you check the box for us to provide it (*"HubSpot Tracking Code for Wordpress", their official plugin, already includes analytics*, and/or some theme developers will have already included it in your code)

*If you are going to connect via API Key, and not include the tracking script, we do not need your Hub ID.*

= Where do I find my HubSpot Hub ID? =

After logging into your account on HubSpot.com, your Hub ID can be found with the product version at the bottom of your HubSpot Dashboard in the footer, or by looking at the Top Left corner of the screen.

= Where do I get a HubSpot API Key? =

Fill out the form on the following link while logged into HubSpot, and click "Get My API Key": https://app.hubspot.com/keys/get -- You will receive an email containing your API Key, once approved.

= My "Date" field in HubSpot isn't getting the correct date? =

This was due to HubSpot expecting a time in milliseconds, and not actually sending us a code saying that it failed for that reason. As such, we have added support for some Field Types (text, date, and enumeration) in v1.1.4 ... After updating, if you go edit your HubSpot Connections (via Forms > HubSpot), edit the existing one, and click "save" ... it will now support Date, and properly carry over.

We currently only support "date", "string", and "enumeration" types from HubSpot, but we'll work on adding more in the future.

"Enumeration" type is the HubSpot format for multiple checkboxes, and similar fields of collection multiple pieces of data at once in a single field.

== Screenshots ==

new screenshots coming soon.

== Changelog ==

= 2.0 =
* [NEW] Reworked the whole plugin to use the GFAddon Framework
    * Settings for each Form has been moved into the Form's "Settings" section. 
* [NEW] Migration Assistant for moving from v1.6.2 to v2.0
    * All versions of data from pre v1.1.4 to v1.6.2 all get migrated safely.
* [REMOVED] Cron! The oAuth handling is now how it should be, and cron script is no longer needed.

** Removed support for less than v1.1.4 ... This update no longer needs the checks as the migration assistant will take care of the discrepancies :) **

= 1.6.2 =
* [FIX] Deleting connections now works again. Thanks to 'samureyed' for discovering this bug.

= 1.6 =
* [UPDATE] If user selects 'oAuth', WP Cron is scheduled. Otherwise, API Token is ok.

= 1.5.1 =
* [FIX] $wpdb->prepare instance where $id isn't set
* [FIX] extra check for $_GET['sub'] in the admin panel.

Thanks to Wordpress.org user "anu" for finding and debugging these issues. (https://wordpress.org/support/topic/php-warnings-patch-attached)

= 1.5 =
* [NEW] Server Side CRON script works. Follow instructions in library/cron.php if you want to set this up for oAuth.

= 1.4 =
* [NEW] Added error log tracking so we can watch errors and try to fix them early.
* [NEW] Added support for "Enumeration" fields from HubSpot. Multiple Checkboxes for a single field are now supported.
* [OTHER] Related: ICONS for repo! woo.

= 1.3.1 =
* [FIX] PHP warnings (https://wordpress.org/support/topic/debug-errors-8)
* [BETA] Includes CRON file for keeping oAuth API key up to date (requires server-side CRON configuration. BETA. USE AT OWN RISK)

= 1.3 =
* [FIX] API Key method for the Settings page now works 100% again.
* [FIX] Cleaned up code, bug fixes.

= 1.2 =
* Still learning my versioning techniques. 1.1.4 should've probably have been 1.2, so this is making it as such.
* Wrapped all of the classes with a if ( class_exists ), as redudancy (per robspurlock), just in case. 

= 1.1.4.1 =
* OMG idiot. Left a var_dump out.... sorry folks.

= 1.1.4 =
* HubSpot "date" selector fields requires a unix timestamp in milliseconds. So, this begins the start of support for specialized field types from HubSpot.

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

= 2.0 =
Reworked! More efficient than ever! We will migrate your settings, but please take a look and confirm your settings. :)

= 1.6.2 =
Fixes ability to delete connections.

= 1.4 =
Enumeration fieldtype support

= 1.3.1 =
PHP Warning fixes

= 1.3 =
API Key method bug fixes

= 1.2 =
Bug Fixes

= 1.1.4 =
The beginning of support for field types from HubSpot. "Date" and "String" field types only supported formats.

= 1.1 =
Vital change. Fixes the ability to create new connections.

= 0.7 =
Introduces oAuth. Highly recommend switching to oAuth (preferred by HubSpot)

= 0.6 =
Fix for Settings page references on the site.

= 0.5 =
Initial Release.
