# Referrer Tracker for Forms
Contributors: marcalorri
Tags: forms, tracking, analytics, referrer, utm
Tested up to: 6.8
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Track and analyze where your form submissions come from by automatically adding hidden fields that capture referrer information.

== Description ==

Referrer Tracker helps you track and analyze where your form submissions are coming from by automatically adding hidden fields to your forms that capture referrer information.

= Key Features =

* Automatic referrer tracking
* UTM parameter parsing
* Multiple form plugin support (Contact Form 7, WPForms, Gravity Forms)
* Cookie-based tracking
* Debug logging

= Supported Form Plugins =

* Contact Form 7 (with auto-insert)
* WPForms (with auto-handling)
* Gravity Forms
* Generic HTML Forms

## Key Features

- Tracks referrer information (source, medium, campaign and referrer URL)
- Stores data in cookies
- Automatically fills hidden fields in Contact Form 7, WPForms and Gravity Forms
- Support for UTM parameters (source, medium, campaign)
- Support for multiple form plugins
- Easy integration
- No advanced programming required

## How are tracking values obtained?

The plugin follows this priority order to fill tracking fields in all compatible forms:

| Priority | Data Source                |
|----------|---------------------------|
| 1        | UTM parameters in URL     |
| 2        | Typo correction (e.g. `urm_medium`) |
| 3        | Cookies                   |
| 4        | Default values            |

Example: If `utm_source` exists in the URL, that value will be used. If not, it will look in cookies, and if it doesn't exist there either, the default value will be used (`direct`, `none`, etc).

## Visual example of inserted fields

### Contact Form 7

The following hidden fields are added automatically (you can see them in the form source code):

```html
[hidden rt_source class:js-rt-source "" default:"google"]
[hidden rt_medium class:js-rt-medium "" default:"cpc"]
[hidden rt_campaign class:js-rt-campaign "" default:"promo2025"]
[hidden rt_referrer class:js-rt-referrer "" default:"https://example.com"]
```

### WPForms

In the WPForms editor, add hidden fields with the following names and classes:

- **Field Name**: rt_source, rt_medium, rt_campaign, rt_referrer
- **CSS Classes**: js-rt-source, js-rt-medium, js-rt-campaign, js-rt-referrer

The plugin will automatically populate the values.

### Gravity Forms

Hidden fields are automatically added if not present:

- **Label**: Source, Medium, Campaign, Referrer
- **Name**: rt_source, rt_medium, rt_campaign, rt_referrer
- **CSS Class**: js-rt-source, js-rt-medium, js-rt-campaign, js-rt-referrer

## Automatic/Manual insertion options

- **Automatic**: By default, the plugin adds and fills hidden fields automatically in all compatible forms if the option is enabled in settings.
- **Manual**: If you prefer to manage fields manually, disable the automatic insertion option in settings and add the hidden fields following the examples above.

## Debug and logs

You can enable or disable debug mode from the plugin settings. When active, detected values and possible issues are logged in the WordPress error log.

## Project Structure

The plugin has been reorganized to improve maintainability and separation of responsibilities:

- **admin/**: Contains functionality related to the WordPress administration panel.
  - `class-admin.php`: Manages all administrative functions, including the configuration page.

- **includes/**: Contains the main plugin classes.
  - `class-referrer-tracker.php`: Main class that initializes all components.
  - **core/**: Contains the main functionalities.
    - `class-tracker.php`: Manages referrer tracking, cookies and values.

- **integrations/**: Contains integrations with different form plugins.
  - `class-cf7.php`: Contact Form 7 integration.
  - `class-wpforms.php`: WPForms integration.
  - `class-gravity.php`: Gravity Forms integration.

- **js/**: Contains JavaScript files for client-side functionality.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/referrer-tracker`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Referrer Tracker for Forms to configure

== Configuration ==

1. Select your form plugin (WPForms, Contact Form 7, etc.)
2. Configure your field prefix (default: rt_)
{{ ... }}
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
   [hidden rt_source class:js-rt-source ""]
   [hidden rt_medium class:js-rt-medium ""]
   [hidden rt_campaign class:js-rt-campaign ""]
   [hidden rt_referrer class:js-rt-referrer ""]
   ```

= WPForms =

1. **Automatic Implementation**:
   * Enable "Auto-insert Hidden Fields" in plugin settings
   * Add the hidden fields as described below, and the plugin will automatically handle populating them

2. **Manual Implementation**:
   * Go to your form editor
   * Add 4 "Hidden Field" elements from the "Fancy Fields" section
   * Configure each field with these exact settings:
     * **Field Label**: Source, Medium, Campaign, Referrer
     * **Field Name**: rt_source, rt_medium, rt_campaign, rt_referrer
     * **Default Value**: Leave empty (the plugin will populate it)
     * **CSS Classes**: js-rt-source, js-rt-medium, js-rt-campaign, js-rt-referrer

= Gravity Forms =

1. Go to your form editor
2. Add 4 "Hidden" fields
3. Configure each field:
   * Source: name=rt_source, class=js-rt-source
   * Medium: name=rt_medium, class=js-rt-medium
   * Campaign: name=rt_campaign, class=js-rt-campaign
   * Referrer: name=rt_referrer, class=js-rt-referrer

= Generic HTML Forms =

Add these hidden fields to your form:
```html
<input type="hidden" name="rt_source" class="js-rt-source" value="">
<input type="hidden" name="rt_medium" class="js-rt-medium" value="">
<input type="hidden" name="rt_campaign" class="js-rt-campaign" value="">
<input type="hidden" name="rt_referrer" class="js-rt-referrer" value="">
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

= 1.5.2 =
* Complete code reorganization to improve maintainability
* Separation of functionalities into specific classes
* Creation of modular integrations for each form plugin
* Improvement of directory structure

= 1.5.1 =
* Updated plugin version
* Added support for multiple cookie prefixes
* Improved advanced debugging for troubleshooting

= 1.5.0 =
* Added WPForms integration for hidden fields
* Added special handling for WPForms in JavaScript
* Added event listener for WPForms form submission
* Improved documentation for WPForms implementation
* Updated plugin description to include WPForms
* Enhanced auto fields functionality to handle WPForms hidden fields

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

= 1.5.2 =
This version includes a complete code reorganization to improve maintainability. There are no functionality changes, but the internal structure has been significantly improved.

= 1.5.1 =
This version adds support for multiple cookie prefixes and improves advanced debugging. Upgrade recommended for all users.

== Privacy Policy ==

This plugin does not collect any personal information. It only stores technical information about the traffic source in cookies, such as referrer URL and UTM parameters.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please visit [our website](https://www.webmanagerservice.es) or create an issue in our GitHub repository.
