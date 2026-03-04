=== ReferrerTracker ===
Contributors: referrertracker
Tags: analytics, tracking, utm, wpforms, contact-form-7
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds the ReferrerTracker tracking script to your site and helps populate hidden tracking fields in supported form plugins.

== Description ==

This plugin loads the ReferrerTracker script in your site head and configures it with your API Key.

It also includes a small compatibility bridge for WPForms so ReferrerTracker can fill fields when WPForms applies CSS classes to field wrappers instead of the actual input.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/referrertracker/` directory, or install it as a ZIP from WordPress.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings -> ReferrerTracker and set your API Key.

== Configuration ==

1. Navigate to Settings -> ReferrerTracker.
2. Set:
   - API Key (required)
   - Cookie Duration (days) (optional, max 90)
   - Debug (optional)

== Capturing data in forms ==

ReferrerTracker fills fields in this order:

1. By ID (recommended): IDs with the `rt-` prefix (e.g. `rt-source`, `rt-gclid`)
2. By name: exact names like `rt_source`, `rt_gclid`, etc.
3. By class: classes like `js-rt-source`, `js-rt-gclid`, etc.

See the ReferrerTracker docs for the full reference table.

== WPForms ==

WPForms often applies "Field CSS Class" on a wrapper element, not the `<input>`.
This plugin includes a bridge that copies `js-rt-*` classes from wrappers to the actual input so ReferrerTracker can fill fields.

Steps:

1. In WPForms, add Hidden Fields for the parameters you want to capture.
2. For each hidden field, set its CSS Class (Advanced tab) to one of:

- js-rt-source
- js-rt-medium
- js-rt-campaign
- js-rt-content
- js-rt-term
- js-rt-referrer
- js-rt-landing-page
- js-rt-gclid
- js-rt-fbclid
- js-rt-msclkid
- js-rt-ttclid
- js-rt-li-fat-id
- js-rt-twclid
- js-rt-epik
- js-rt-rdt-cid

== Contact Form 7 ==

If you already have hidden fields with ReferrerTracker IDs/names/classes, it should work without additional changes.

== Gravity Forms ==

Gravity Forms can populate fields dynamically using a "Parameter Name".

This plugin adds server-side support for Gravity Forms by reading ReferrerTracker cookies and providing them as dynamic values.

Steps:

1. Add Hidden Fields for the parameters you want to capture.
2. For each hidden field, enable "Allow field to be populated dynamically".
3. Set the "Parameter Name" to either:

- `rt_source`, `rt_medium`, `rt_campaign`, `rt_content`, `rt_term`
- `rt_referrer`, `rt_landing_page`
- `rt_gclid`, `rt_fbclid`, `rt_msclkid`, `rt_ttclid`, `rt_li_fat_id`, `rt_twclid`, `rt_epik`, `rt_rdt_cid`

Or, alternatively, you can use the ReferrerTracker class-style names and the plugin will map them:

- `js-rt-source`, `js-rt-medium`, `js-rt-campaign`, `js-rt-content`, `js-rt-term`
- `js-rt-referrer`, `js-rt-landing-page`
- `js-rt-gclid`, `js-rt-fbclid`, `js-rt-msclkid`, `js-rt-ttclid`, `js-rt-li-fat-id`, `js-rt-twclid`, `js-rt-epik`, `js-rt-rdt-cid`

== Updates (GitHub Releases) ==

This plugin can be updated from GitHub Releases.

Typical flow:

1. Bump the plugin version in `referrertracker.php`.
2. Create a GitHub Release with a tag like `v0.1.1`.
3. The repository GitHub Action will automatically attach a ZIP asset with the correct plugin folder structure.

== Changelog ==

= 0.1.6 =
* Security hardening (sanitized cookie reads, safer handling of admin query args).
* Release ZIP build improvements (exclude hidden files).
* Update readme headers.

= 0.1.5 =
* Add i18n support and Spanish (es_ES) translations.

= 0.1.4 =
* Add tabbed instructions for supported form plugins on the settings page.
* Automate release ZIP attachment via GitHub Actions.

= 0.1.3 =
* Add Settings link in plugin list and redirect to settings after activation.

= 0.1.2 =
* Fix GitHub update package URL to avoid zipball download issues.

= 0.1.1 =
* Add Gravity Forms server-side dynamic population support via cookies.

= 0.1.0 =
* Initial release.
