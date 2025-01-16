# WP Referrer Tracker

A WordPress plugin that tracks and analyzes referrer information, providing detailed insights about traffic sources, mediums, and campaigns. Perfect for marketing analytics and lead tracking.

## Features

- **Advanced Traffic Source Detection**
  - Differentiates between organic and paid traffic
  - Supports all major search engines
  - Identifies social media platforms
  - Detects email providers
  - Tracks referral websites

- **Multiple Form Plugin Support**
  - WPForms
  - Contact Form 7
  - Gravity Forms
  - Generic HTML Forms

- **Smart Implementation**
  - Fully automatic code insertion
  - Automatic backup creation
  - Safe code updates
  - Plugin-specific optimizations
  - Custom field prefix support

- **Comprehensive Traffic Analysis**
  - Source identification (Google, Facebook, Twitter, etc.)
  - Medium categorization (organic, cpc, social, email, referral)
  - Campaign tracking (UTM parameters)
  - Paid traffic detection

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-referrer-tracker`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WP Referrer Tracker to configure

## Configuration

In the WordPress admin panel, go to Settings > WP Referrer Tracker to configure the plugin:

1. **Auto-insert Hidden Fields**: Enable/disable automatic insertion of tracking fields.
2. **Field Prefix**: Set a custom prefix for field names (default: wrt_).
3. **Form Plugin**: Select your form plugin to get specific implementation code:
   - WPForms
   - Contact Form 7
   - Gravity Forms
   - Generic HTML Forms
4. **Generate Code**: Enable this option to display the JavaScript code.
5. **Auto Insert Code**: Enable this option to automatically insert/update the code in your theme's functions.php file.

### Implementation Methods

You can implement the tracking in three ways:

1. **Fully Automatic Implementation** (Recommended):
   - Select your form plugin in the settings
   - Enable "Auto Insert Code"
   - The plugin will automatically:
     * Insert the required code in functions.php
     * Create a backup of your original file
     * Update the code when settings change
     * Remove the code when disabled

2. **Semi-Automatic Implementation**:
   - Select your form plugin in the settings
   - Enable "Generate Code"
   - Copy the generated code to your theme's functions.php file manually

3. **Manual Implementation**:
   Follow the instructions below based on your form plugin:

#### WPForms

1. Add Hidden fields to your form:
   - {prefix}source
   - {prefix}medium
   - {prefix}campaign
   - {prefix}referrer

2. In Advanced Field Options, set JavaScript value:
```javascript
return getReferrerValue('source');   // For source field
return getReferrerValue('medium');   // For medium field
return getReferrerValue('campaign'); // For campaign field
return getReferrerValue('referrer'); // For referrer field
```

#### Contact Form 7

Add these hidden fields to your form:
```
[hidden wrt_source class:js-wrt-source ""]
[hidden wrt_medium class:js-wrt-medium ""]
[hidden wrt_campaign class:js-wrt-campaign ""]
[hidden wrt_referrer class:js-wrt-referrer ""]
```

#### Gravity Forms

1. Add Hidden fields to your form
2. Enable "Allow field to be populated dynamically"
3. Set Parameter Names:
   - wrt_source
   - wrt_medium
   - wrt_campaign
   - wrt_referrer

#### Generic HTML Forms

Add hidden fields to your form:
```html
<input type="hidden" name="wrt_source" id="wrt_source">
<input type="hidden" name="wrt_medium" id="wrt_medium">
<input type="hidden" name="wrt_campaign" id="wrt_campaign">
<input type="hidden" name="wrt_referrer" id="wrt_referrer">
```

### Safety Features

The plugin includes several safety features for automatic code insertion:

1. **Automatic Backups**:
   - Creates a backup of functions.php before any modification
   - Backup files are named with timestamp (e.g., functions.php.backup-2025-01-16-123456)

2. **Permission Checks**:
   - Verifies if functions.php is writable
   - Shows warnings if permissions are incorrect

3. **Safe Updates**:
   - Validates code before insertion
   - Maintains existing functionality
   - Clean removal when disabled

## Changelog

### 1.3.0
- Added automatic code insertion in functions.php
- Added automatic backup system for functions.php
- Added safety checks for file modifications
- Added detailed error messages and warnings
- Improved documentation with safety features
- Added new configuration option for auto-insertion

### 1.2.0
- Added support for multiple form plugins:
  - WPForms integration
  - Contact Form 7 integration
  - Gravity Forms integration
  - Generic HTML forms support
- Added plugin-specific code generation
- Improved settings page with form plugin selection
- Added comprehensive documentation for each form plugin

### 1.1.0
- Enhanced paid vs organic traffic detection
- Added comprehensive detection for multiple ad platforms:
  - Google Ads (gclid)
  - Facebook Ads (fbclid)
  - Microsoft Ads (msclkid)
  - TikTok Ads (ttclid)
  - Twitter Ads (twclid)
- Improved detection of social media paid traffic
- Added detection of internal referrers
- Added support for international search engine domains
- Added detection for email providers
- Improved documentation

### 1.0.0
- Initial release
- Basic referrer tracking
- UTM parameter support
- Form field auto-insertion
- JavaScript API

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please visit [our website](https://www.webmanagerservice.es) or create an issue in our GitHub repository.
