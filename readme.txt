=== HubSpot for Gravity Forms ===
Contributors: Big Sea, Soben, bloqhead
Donate link: http://bigsea.co/
Tags: hubspot, gravity, forms, submit, submission, lead, api, gravity forms
Requires at least: 3.5
Tested up to: 4.9.6
Stable tag: 4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily integrate your Gravity Forms with HubSpot forms! Match up field-for-field so you can harness the power of HubSpot.

== Description ==

If you’ve got a WordPress site that uses Gravity Forms, you’ve probably already spent time designing them to match your site and function the way you need - responsive, beautiful forms.  Now, you or your client wants to integrate HubSpot and you need a way to get those contacts into your lead management funnel - well here’s your solution!

* Authenticate using oAuth
* Create a form in HubSpot with fields that match all (or any part) of your fields in Gravity Forms
* Profit!

*Minimum System Requirements*

* PHP 5.5 or greater
* SSL Certificate

== Installation ==

1. If you already have the plugin installed, please go to the 'Plugins' menu in WordPress and deactivate it.
1. Upload the files to the /wp-content/plugins/gravityforms-hubspot/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Forms->Settings->HubSpot, and provide valid credentials to connect to your HubSpot Account via oAuth
1. Go to a Form's Settings->HubSpot section and make your first connection!

*This plugin provides many checks for a proper version of Gravity Forms, as well as HubSpot oAuth validation. Notices will appear at the top of the Admin panel until these issues are resolved or the plugin is deactivated.*

== Frequently Asked Questions ==

= Is my Hub ID required? =

No. Your HubSpot Hub ID is only required if you are using our plugin to include the Analytics Tracking javascript, if you check the box for us to provide it (*"HubSpot Tracking Code for Wordpress", their official plugin, already includes analytics*, and/or some theme developers will have already included it in your code)

= Where do I find my HubSpot Hub ID? =

After logging into your account on HubSpot.com, your Hub ID can be found with the product version at the bottom of your HubSpot Dashboard in the footer, or by looking at the Top Left corner of the screen.

= What field types are supported? or Why does my X field not carry over to HubSpot? =

We currently only support "date", "string", and "enumeration" types from HubSpot, but we'll work on adding more in the future.

"Enumeration" type is the HubSpot format for multiple checkboxes, and similar fields of collection multiple pieces of data at once in a single field.

= Why do you now have minimum system requirements? =

HubSpot's PHP based library is deprecated, and does not support HubSpot's new oAuth 2.0 standard. We chose to switch to an existing PHP library that does support oAuth 2.0, but this library uses new PHP features that are only available in PHP 5.5 and above.

On top of that, HubSpot's oAuth 2.0 connectivity REQUIRES a secured URL to redirect back to, for added security. As we do not handle your authentication request and it happens purely within the plugin itself, your website now requires an SSL certificate.

= What happened to my ability to use an API Key? =

We kept the API Key feature in earlier versions of our plugin as a "just in case"... HubSpot's switch to oAuth 2.0 required such an overhaul of the plugin, that we have chosen to drop support for it.

== Screenshots ==

1. The Location of your HubSpot settings on a per-form basis. It can be found by hovering over the "Settings" text, or clicking settings and then clicking "HubSpot"
1. The Form's list of HubSpot Feeds
1. A glimpse at the settings for the HubSpot Feed connectivity
1. HubSpot settings found in Forms > Settings > HubSpot

== Changelog ==

= 4.0 =
* [New] Support for "File Upload" field in Gravity Forms. Takes the resulting upload URLs and send them to HubSpot in a semi-colon delimited list.
* [New] `apply_filters( 'gf_hubspot_conditional_not_met', false, $feed, $entry, $form);` Allows you to skip the feed on programmatic conditions.
* [New] Conditional Logic now possible during feed management
* [NOTICE] Redirections set in HubSpot upon success can cause conflicts. We are trying to show a heads up now to make sure you check your forms :)

= 3.0.4 =
* [FIX] missing variable for hubspot context fixed in Base.php

= 3.0.3 =
* [FIX] PHP warning when including HubSpot analytics is now resolved. (kudos mattkeys for finding [and resolving] this)

= 3.0.2 =
* [FIX] Minor PHP warnings related to missing constants, and not properly checking `isset`
* [NOTICE] Added additional checks to 1) Prevent activation of plugin if missing PHP 5.5 or greater, and 2) if plugin is already active, show warning on Admin pages, and not load in the rest of the plugin.

= 3.0.1 =
* [FIX] Cache file had PHP error
* [FIX] Fixed issue in which token would get lost during save action for Analytics.

= 3.0.0 =
* [NEW] Support for HubSpot's switch to oAuth 2.0
* [NOTICE] Added minimum system requirements. Complete overhaul of structure into a Composer library.

= 2.3.5 =
* [NOTICE] Giving everyone a heads up to a potential site-breaking fix coming soon due to HubSpot's API changes.

= 2.3.4 =
* [FIX] Sometimes, if the Label is blank from HubSpot, there's no way to determine what field is what. This resolves that by falling back to the field's slug.

= 2.3.3 =
* [NEW] `apply_filters( 'gf_hubspot_data_outgoing', $data, $GFform, $feedData );` Allows you to change the data that's being sent to HubSpot. Be mindful of formats that HubSpot expects (A list of items must be semi-colon separated, for instance). An array of Key => Value where Key is the HubSpot field slug, and the Value is what will be sent to HubSpot for that field.
* [NEW] `apply_filters( 'gf_hubspot_forms_incoming', $forms );` Array of Form objects received from HubSpot. If you wanted to go through to track with some custom settings. Changes do not get cached.
* [NEW] `apply_filters( 'gf_hubspot_form_incoming', $form, $formID );` Single Form Object received form HubSpot. Changes do not get cached
* [NEW] `apply_filters( 'gf_hubspot_form_{formID}_incoming', $form );` Run immediately after gf_hubspot_form_incoming. Single Form Object received form HubSpot. Changes do not get cached. So you can specifically work on a single form (instead of potentially any)

This release brought you to by request of more flexibility/access

= 2.3.2 =
* [FIX] Extra check to make sure the $response I have is an actual object. Clearly there's a larger issue at play here, though, if this is being encountered at that stage of the game? Short term solution for now. 
* [NEW] New filter "gf_hubspot_process_feed": Allows manipulation of the Feed data prior to processing and sending to HubSpot. Suggested by Nathan Marks.

= 2.3.1 =
* [FIX] Values that include a comma (such as $60,000) no longer get broken when being sent to HubSpot for Enumeration-based fields. However, values for enumeration fields (checkboxes, select, radio) still will encounter this issue if ', ' (with a space) is used for their value. Working on a more permanent fix. (found by forthesakeofreason)
* [WARN] Removed the cron.php file as it's no longer needed for continual oAuth re-validation.

= 2.3 =
* [NEW] Two new filters, for changing the HubSpot Context that gets sent to HubSpot. The Page Name and URL default to the Form Name, and the site URL, by default. You can override these with `add_filter` and using the hooks `gf_hubspot_context_url` and `gf_hubspot_context_name` respectively. (thanks to Robert for the suggestion)

= 2.2 =
* [NEW] Continuing to bring things more inline with HubSpot's own embeds. Now includes the ability to not include the tracking cookie (In instances where you want duplicate entries in HubSpot for the same user, like during Trade Shows).

= 2.1.5 =
* [FIX] Caching script had a whitespace bug that was causing "non well-formed numeric value" errors. (found by alexdef)

= 2.1.4 =
* [FIX] Support for php 5.2 for the caching...

= 2.1.3 =
* [UPDATE] Moved the cache file into the /uploads folder and out of the plugin.

= 2.1.2 =
* [FIX] Supressing the warning for the cache file opening, if it doesn't exist, as it'll exist soon enough.

= 2.1.1 =
* [FIX] Removed var_dump that shouldn't have been there anymore.
* [FIX] Check to see if I have write ability before trying to write/view new cache.
* [WARN] If cache doesn't appear to be working, check the /cache/ folder in the plugin, set the permissions to 755.

= 2.1 = 
* [FIX] Rewrote the caching to not rely on WP Transient API. Rolled our own solution.

= 2.0.4 =
* [NEW] Added improvements to the HubSpot calls that should solve some Security errors that some users get.

= 2.0.3 =
* [FIX] Sent the wrong arguments to GravityForm's feed_error function for a rare case that configuration is not correct for a Gravity Forms field being matched with HubSpot.

Thanks to Wordpress.org user "Theorem_US" for finding this issues. (https://wordpress.org/support/topic/php-error-when-submitting-form)

= 2.0.2 =
* [FIX] More downgrading of my code due to users with older versions of PHP.

= 2.0.1 =
* [FIX] Potential issue noticed by 'dsaro' for add_action hooks where some PHP/WP versions won't support anonymous functions. Hoping this fixes it for them, as I can't replicate, but working on a solid assumption.
* [FIX] Potential fix for the issue first noticed by 'nickvillaume' in regards to SSL error message during cURL call to HubSpot.

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

= 3.0.0 =
Support for HubSpot's new oAuth 2.0, Minimum requirements PHP5.5 and SSL Certificate
After updating, you will need to re-authenticate your HubSpot account.

= 2.3 =
Filters galore!

= 2.2 =
New feature! Disabling Tracking Cookie is now possible on a per-feed basis.

= 2.1 =
Caching no longer problematic! Rolled our own caching solution that stores in the gravityforms-hubspot/cache directory

= 2.0 =
Reworked! More efficient than ever! We will migrate your settings, but please take a look and confirm your settings. :)

= 1.4 =
Enumeration fieldtype support

= 1.3 =
API Key method bug fixes

= 1.2 =
Bug Fixes

= 1.1 =
Vital change. Fixes the ability to create new connections.

= 0.7 =
Introduces oAuth. Highly recommend switching to oAuth (preferred by HubSpot)

= 0.6 =
Fix for Settings page references on the site.

= 0.5 =
Initial Release.
