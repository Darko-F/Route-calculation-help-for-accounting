# Version Summary

## Version 1.2.64

- Removed the separate manual base-country kilometre override, button, and split mode.
- Country-row recalculation now preserves edited kilometres and automatically adjusts remaining country rows to equal the total route.
- Removed the country-kilometre total mismatch warning and updated fallback guidance to use editable country rows.

## Version 1.2.63

- Added server-backed pagination to the Customers and Invoices modals.
- Both modals default to 50 rows and allow 25, 50, or 100 rows per page.
- Added Previous, Next, and numbered page controls for larger result sets.

## Version 1.2.62

- Added confirmed invoice deletion exclusively inside the Invoices modal.
- Added a calendar picker to the invoice date filter while preserving the visible `DD/MM/YYYY` format.

## Version 1.2.61

- Customers, Invoices, and All now scroll the Joomla page to the top of the calculator before opening their modal lists.

## Version 1.2.60

- Reordered the invoice workflow buttons to Save customer, Add to draft invoice, and Save invoice.
- Removed the redundant Load history button.
- Moved Customers and Invoices to a separate row below a divider.

## Version 1.2.59

- Moved Actions directly below Transfer details, followed by Results.
- Kept the initial customer invoice history at 25 records and added an All button that loads every invoice for that customer in a modal.
- Added an Invoices modal for searching all saved invoices by invoice number or saved date.
- Updated the Saved customers modal to open at the top of its content.

## Version 1.2.58

- Standardized all user-visible dates to `DD/MM/YYYY`, including service date, draft lines, invoice history, and PDF invoice dates.
- Kept database and Minimax XML date values in ISO `YYYY-MM-DD` format for compatibility.

## Version 1.2.57

- Removed the duplicate foreign-country and foreign-country VAT inputs from the transfer form.
- Kept country and VAT editing in the per-country split rows, which fast calculation populates automatically.
- Manual and fallback calculations now use an explicit custom-country row instead of silently assuming Italy.

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
