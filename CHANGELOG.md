# Changelog

## 1.5.1 - 2025-03-18
### Fixed
- Solucionado problema con cookies duplicadas eliminando cookies existentes antes de crear nuevas
- Corregido problema con la cookie de campaign que faltaba, ahora se establece como 'none' por defecto
- Mejorada la detección de campos en WPForms para que funcione con IDs específicos (8, 9, 10, 11)
- Eliminada la creación de cookies con prefijo wrt_ para evitar duplicados

### Added
- Añadido mejor soporte para depuración con mensajes más detallados
- Implementada verificación final para campos vacíos después de 2 segundos
- Aumentado el tiempo de actualización de campos a 10 segundos

### Changed
- Mejorada la función JavaScript para detectar campos por nombre e ID
- Actualizada la forma en que se pasan los valores al JavaScript
- Optimizada la función set_cookies para ser más robusta

## 1.5.0 - 2025-03-18
### Added
- WPForms integration for hidden fields
- Special handling for WPForms in JavaScript to ensure values are properly populated
- Event listener for WPForms form submission
- Improved documentation for WPForms implementation

### Changed
- Updated plugin description to include WPForms
- Enhanced auto fields functionality to handle WPForms hidden fields
- Improved field detection and value assignment in JavaScript

## 1.4.2 - Previous Release
- Initial release with Contact Form 7 support
