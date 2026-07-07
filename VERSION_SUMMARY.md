# Version Summary

## Version 1.2.56

- Added a defensive UI rule to hide any legacy draft XML export button so only the bottom XML export button is visible.

## Version 1.2.55

- Removed the duplicate draft XML export button and made the bottom XML export button handle draft invoices.

## Version 1.2.54

- Added configurable PDF stamp/signature image URL with bundled signature image as the default.
- Updated PDF generation to load the configured signature image for generated and draft invoices.

## Version 1.2.39

- Added configurable base country support for Slovenia, Italy, Croatia, Austria, Germany, and Hungary.
- Added country boundary GeoJSON files and automatic country split support for the supported countries.
- Added Germany VAT/default account/PDF note support and updated Austria/Croatia tax notes.
- Updated calculator pricing so final price with VAT or price without VAT can be entered, with the other value calculated automatically.
- Updated calculator layout: final price, price without VAT, and base-country VAT are full-width inputs.
- Added dynamic base-country labels and map defaults based on the selected base country.
- Updated invoice/PDF/XML behavior to use base-country logic instead of hardcoded Slovenia where applicable.
- Updated English and Slovenian translation strings.
- Updated Joomla update server and download URLs to the `/routecalculationhelp/files/routecalculationhelp/` paths.
- Updated protected downloads `.htaccess` so `download.php` is reachable while ZIPs and key files stay blocked from direct access.

## Installer Plugin 1.0.2

- Updated update server and package download URLs to the corrected server paths.
- Updated download-key injection to support the corrected protected download endpoint.
- Added support for Joomla event-style package download URL updates.

## Update Server

- Version update XML now points to `route_calculation_help_for_accounting_v1.2.39.zip`.
- Installer plugin update XML now points to `plg_installer_routecalculationupdatekey_v1.0.2.zip`.
- `download.php` accepts valid keys through `key` or `dlid` and can run from inside the `downloads/` directory.
