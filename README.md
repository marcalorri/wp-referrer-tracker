# WP Referrer Tracker

Track and categorize referrer information in WordPress forms. Supports WPForms, Contact Form 7, Gravity Forms, and generic HTML forms.

## Features

- Automatic referrer tracking
- UTM parameter parsing
- Multiple form plugin support
- Cookie-based tracking
- Debug logging

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-referrer-tracker`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WP Referrer Tracker to configure

## Configuration

1. Select your form plugin (WPForms, Contact Form 7, etc.)
2. Configure your field prefix (default: wrt_)
3. For Contact Form 7: Enable "Auto-insert Hidden Fields" to automatically add tracking fields
4. Save changes

The plugin will show you specific implementation instructions for your selected form plugin.

## Usage

### Contact Form 7

Two ways to implement:

1. **Automatic Implementation**:
   - Enable "Auto-insert Hidden Fields" in plugin settings
   - Fields will be added automatically to all CF7 forms

2. **Manual Implementation**:
   Add these hidden fields to your form:
```
[hidden wrt_source class:js-wrt-source ""]
[hidden wrt_medium class:js-wrt-medium ""]
[hidden wrt_campaign class:js-wrt-campaign ""]
[hidden wrt_referrer class:js-wrt-referrer ""]
```

Important notes for Contact Form 7:
1. The field names must use underscore (e.g., `wrt_source`)
2. The classes must use hyphen (e.g., `js-wrt-source`)
3. Leave the default value empty (`""`)

### WPForms

1. Go to your form editor
2. Add 4 "Hidden Field" elements
3. Configure each field:
   - Source: name=wrt_source, class=js-wrt-source
   - Medium: name=wrt_medium, class=js-wrt-medium
   - Campaign: name=wrt_campaign, class=js-wrt-campaign
   - Referrer: name=wrt_referrer, class=js-wrt-referrer

### Gravity Forms

1. Go to your form editor
2. Add 4 "Hidden" fields
3. Configure each field:
   - Source: name=wrt_source, class=js-wrt-source
   - Medium: name=wrt_medium, class=js-wrt-medium
   - Campaign: name=wrt_campaign, class=js-wrt-campaign
   - Referrer: name=wrt_referrer, class=js-wrt-referrer

### Generic HTML Forms

Add these hidden fields to your form:
```html
<input type="hidden" name="wrt_source" class="js-wrt-source" value="">
<input type="hidden" name="wrt_medium" class="js-wrt-medium" value="">
<input type="hidden" name="wrt_campaign" class="js-wrt-campaign" value="">
<input type="hidden" name="wrt_referrer" class="js-wrt-referrer" value="">
```

## Debugging

If fields are not being populated, check:

1. Enable WordPress debug logging:
   ```php
   // Add to wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Check `wp-content/debug.log` for messages starting with "WRT:"
   - Cookie setting/getting
   - Field updates
   - Referrer detection

## Changelog

### 1.4.2
- Fixed Auto-insert Hidden Fields functionality for Contact Form 7
- Improved cookie handling and value detection
- Added detailed debug logging
- Enhanced field value updates
- Updated documentation with debugging instructions

### 1.4.1
- Added detailed implementation instructions
- Improved field value handling
- Added debug logging support
- Enhanced error prevention

### 1.4.0
- Complete architectural overhaul
- Switched to dynamic code injection
- Removed file system modifications
- Added proper WordPress hooks

### 1.3.7
- Fixed Contact Form 7 field updates:
  - Corrected class name format (using hyphens)
  - Added debug logging for field updates
  - Fixed duplicate code injection
  - Added function existence check

### 1.3.6
- Completely rewrote Contact Form 7 integration:
  - Fixed field class naming convention
  - Simplified JavaScript code structure
  - Improved field value updates
  - Better error prevention
- Enhanced documentation:
  - Added detailed CF7 implementation notes
  - Clarified field naming requirements
  - Added important usage notes
  - Updated troubleshooting guide

### 1.3.5
- Fixed Contact Form 7 field value updates:
  - Changed class selector to match CF7 structure
  - Simplified DOM manipulation
  - Direct value updates on fields
  - Improved documentation for CF7 implementation
- Enhanced field handling:
  - More efficient field selection
  - Better class naming convention
  - Immediate value updates
  - Clearer implementation instructions

### 1.3.4
- Fixed script generation in functions.php:
  - Removed PHP output buffering
  - Using pure string concatenation
  - Fixed double wp_footer action
  - Fixed script variable handling
- Improved code stability:
  - No more PHP tag switching
  - Better escaping of quotes
  - More reliable script output
  - Fixed variable assignment issues

### 1.3.3
- Fixed PHP tag handling in functions.php:
  - Removed unnecessary PHP closing/opening tags
  - Added proper output buffering for scripts
  - Improved code structure and readability
  - Better integration with WordPress coding standards
- Enhanced code generation:
  - Clean PHP code output
  - Better script encapsulation
  - Improved error prevention
  - More reliable script injection

### 1.3.2
- Fixed critical issue with code placement in functions.php:
  - Added smart detection of last hook position
  - Improved handling of PHP closing tags
  - Better preservation of existing code structure
  - Fixed code truncation issues
- Enhanced code insertion logic:
  - Intelligent placement after last hook
  - Proper handling of file endings
  - Better backup validation
  - Improved error recovery

### 1.3.1
- Fixed critical bugs in automatic code insertion:
  - Fixed Contact Form 7 event listener (wpcf7:submit)
  - Added validation for getReferrerValue function
  - Improved code insertion in functions.php
  - Added proper PHP tag handling
- Enhanced safety features:
  - Added validation before backup creation
  - Improved error messages and warnings
  - Added code cleanup on uninstall
  - Better handling of existing code
- Added immediate form field updates on page load
- Improved code organization and readability

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
