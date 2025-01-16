=== WP Referrer Tracker ===
Contributors: marcalorri
Tags: forms, tracking, analytics, referrer, utm
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Track and categorize referrer information in WordPress forms. Supports WPForms, Contact Form 7, Gravity Forms, and generic HTML forms.

== Description ==

WP Referrer Tracker helps you track and analyze where your form submissions are coming from by automatically adding hidden fields to your forms that capture referrer information.

= Key Features =

* Automatic referrer tracking
* UTM parameter parsing
* Multiple form plugin support
* Cookie-based tracking
* Debug logging

= Supported Form Plugins =

* Contact Form 7 (with auto-insert)
* WPForms
* Gravity Forms
* Generic HTML Forms

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-referrer-tracker`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WP Referrer Tracker to configure

== Configuration ==

1. Select your form plugin (WPForms, Contact Form 7, etc.)
2. Configure your field prefix (default: wrt_)
3. For Contact Form 7: Enable "Auto-insert Hidden Fields" to automatically add tracking fields
4. Save changes

== Usage ==

= Contact Form 7 =

Two ways to implement:

1. **Automatic Implementation**:
   * Enable "Auto-insert Hidden Fields" in plugin settings
   * Fields will be added automatically to all CF7 forms

2. **Manual Implementation**:
   Add these hidden fields to your form:
   ```
   [hidden wrt_source class:js-wrt-source ""]
   [hidden wrt_medium class:js-wrt-medium ""]
   [hidden wrt_campaign class:js-wrt-campaign ""]
   [hidden wrt_referrer class:js-wrt-referrer ""]
   ```

= WPForms =

1. Go to your form editor
2. Add 4 "Hidden Field" elements
3. Configure each field:
   * Source: name=wrt_source, class=js-wrt-source
   * Medium: name=wrt_medium, class=js-wrt-medium
   * Campaign: name=wrt_campaign, class=js-wrt-campaign
   * Referrer: name=wrt_referrer, class=js-wrt-referrer

= Gravity Forms =

1. Go to your form editor
2. Add 4 "Hidden" fields
3. Configure each field:
   * Source: name=wrt_source, class=js-wrt-source
   * Medium: name=wrt_medium, class=js-wrt-medium
   * Campaign: name=wrt_campaign, class=js-wrt-campaign
   * Referrer: name=wrt_referrer, class=js-wrt-referrer

= Generic HTML Forms =

Add these hidden fields to your form:
```html
<input type="hidden" name="wrt_source" class="js-wrt-source" value="">
<input type="hidden" name="wrt_medium" class="js-wrt-medium" value="">
<input type="hidden" name="wrt_campaign" class="js-wrt-campaign" value="">
<input type="hidden" name="wrt_referrer" class="js-wrt-referrer" value="">
```

== Frequently Asked Questions ==

= What information is tracked? =

The plugin tracks:
* Traffic sources (Google, Facebook, Twitter, etc.)
* Traffic mediums (organic, cpc, social, email, referral)
* Campaign information from UTM parameters
* Original referrer URL

= Is this GDPR compliant? =

Yes. The plugin only tracks basic referrer information that is already available to your website. No personal information is collected or stored.

== Changelog ==

= 1.4.2 =
* Fixed Auto-insert Hidden Fields functionality for Contact Form 7
* Improved cookie handling and value detection
* Added detailed debug logging
* Enhanced field value updates
* Updated documentation with debugging instructions

= 1.4.1 =
* Added detailed implementation instructions
* Improved field value handling
* Added debug logging support
* Enhanced error prevention

= 1.4.0 =
* Complete architectural overhaul
* Switched to dynamic code injection
* Removed file system modifications
* Added proper WordPress hooks

== Upgrade Notice ==

= 1.4.2 =
This version fixes the Auto-insert Hidden Fields functionality and improves value detection. Upgrade recommended for all users.

== Privacy Policy ==

This plugin does not collect any personal information. It only stores technical information about the traffic source in cookies, such as referrer URL and UTM parameters.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please visit [our website](https://www.webmanagerservice.es) or create an issue in our GitHub repository.
