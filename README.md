# Referrer Tracker for Forms and CMS
Contributors: marcalorri
Tags: forms, tracking, analytics, referrer, utm
Tested up to: 6.7
Stable tag: 1.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Referrer Tracker for Forms and CMS helps you track and analyze where your form submissions are coming from by automatically adding hidden fields to your forms that capture referrer information.

== Description ==

WP Referrer Tracker helps you track and analyze where your form submissions are coming from by automatically adding hidden fields to your forms that capture referrer information.

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

## Características

- Rastrea información del referente (fuente, medio, campaña y URL del referente)
- Almacena los datos en cookies para su uso posterior
- Rellena automáticamente campos ocultos en Contact Form 7, WPForms y Gravity Forms
- Soporte para parámetros UTM (source, medium, campaign)
- Fácil integración con cualquier formulario de WordPress
- Soporte para múltiples prefijos de cookies para compatibilidad
- Depuración avanzada para solucionar problemas

## Estructura del Proyecto

El plugin ha sido reorganizado para mejorar la mantenibilidad y separación de responsabilidades:

- **admin/**: Contiene la funcionalidad relacionada con el panel de administración de WordPress.
  - `class-admin.php`: Gestiona todas las funciones administrativas, incluyendo la página de configuración.

- **includes/**: Contiene las clases principales del plugin.
  - `class-referrer-tracker.php`: Clase principal que inicializa todos los componentes.
  - **core/**: Contiene las funcionalidades principales.
    - `class-tracker.php`: Gestiona el seguimiento de referentes, cookies y valores.

- **integrations/**: Contiene las integraciones con diferentes plugins de formularios.
  - `class-cf7.php`: Integración con Contact Form 7.
  - `class-wpforms.php`: Integración con WPForms.
  - `class-gravity.php`: Integración con Gravity Forms.

- **js/**: Contiene los archivos JavaScript para la funcionalidad del lado del cliente.
  - `referrer-tracker.js`: Maneja el seguimiento de referentes en el navegador.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/referrer-tracker`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Referrer Tracker for Forms and CMS to configure

== Configuration ==

1. Select your form plugin (WPForms, Contact Form 7, etc.)
2. Configure your field prefix (default: rt_)
3. For Contact Form 7 and WPForms: Enable "Auto-insert/handle Hidden Fields" to automatically add and manage tracking fields
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
* Reorganización completa del código para mejorar la mantenibilidad
* Separación de funcionalidades en clases específicas
* Creación de integraciones modulares para cada plugin de formularios
* Mejora de la estructura de directorios

= 1.5.1 =
* Actualizado la versión del plugin
* Agregado soporte para múltiples prefijos de cookies
* Mejorada la depuración avanzada para solucionar problemas

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
Esta versión incluye una reorganización completa del código para mejorar la mantenibilidad. No hay cambios en la funcionalidad, pero la estructura interna ha sido mejorada significativamente.

= 1.5.1 =
This version adds support for multiple cookie prefixes and improves advanced debugging. Upgrade recommended for all users.

== Privacy Policy ==

This plugin does not collect any personal information. It only stores technical information about the traffic source in cookies, such as referrer URL and UTM parameters.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please visit [our website](https://www.webmanagerservice.es) or create an issue in our GitHub repository.
