# Version Summary

## Version 1.3.1

- Updated Minimax XML to the namespace used by the current official XSD.
- Corrected the Minimax VAT accounting type to `PP` (passenger transport) for both domestic and international passenger-transport invoices.
- Kept foreign net bases outside Slovenian VAT while posting foreign VAT exclusively to each country's configured VAT-liability account.
- Normalized additional costs to Minimax `S` (22%), `Z` (9.5%), or `N` (0% non-taxable), including safe handling of legacy saved costs.
- Added export guards for unsupported or mixed Slovenian passenger-transport VAT rates and retained balanced journal totals across domestic, foreign, and additional-cost lines.
- Made the PDF use the per-route country note edited in the calculator, with the module setting retained as its default.
- Corrected fast country splitting so detected foreign kilometres are retained even when the country has not been added to module settings; unknown countries fall back to an editable Other/custom row.
- Removed endpoint-only domestic-route detection so routes that leave and re-enter the base country are split using the full route geometry.

## Version 1.3.0

- Reorganized Joomla module settings into dedicated Options, Minimax, and Countries tabs.
- Added an empty, repeatable Minimax country-account list for revenue and VAT liability accounts, with no hardcoded runtime account fallbacks.
- Added configurable customer receivables, base-country standard-rate VAT, and default foreign revenue accounts; country rows can override the default foreign revenue account.
- Added configurable country VAT numbers, VAT rates, and PDF notes with `{countryName}`, `{vatNumber}`, and `{vatRate}` placeholders.
- Added a configurable default foreign passenger-transport VAT rate, with optional per-country overrides and correct handling of an explicit zero rate.
- Added an empty, accounting-program-independent Countries list for future foreign VAT registrations and kept all Minimax-specific fields exclusively in the Minimax tab.
- Corrected foreign Minimax journal entries so net revenue and foreign VAT liabilities are booked separately while the foreign net base remains outside Slovenian VAT.
- Preserved Slovenian `S/Z/N` Minimax reporting when Slovenia is the base country and prevented foreign base-country VAT from being reported as Slovenian output VAT.
- Added export validation that blocks Minimax XML when a required country rate or accounting account is missing.
- Made invoice-number allocation resilient to simultaneous saves by retrying after a unique-number conflict.
- Bundled Bootstrap and jsPDF with their licenses so PDF and UI functions no longer depend on third-party CDNs.

## Version 1.2.76

- Corrected Minimax XML cent rounding so domestic, international, multi-country, and additional-cost journal entries always balance to the invoice total.
- Saved invoice-history and draft-line totals now include VAT-inclusive additional costs, matching Results, PDF, and XML totals.
- Added Minimax XML customer postcode, city, and two-letter country-code fields with persistence and required field/length validation.
- Minimax XML now uses local dates, the service date for accounting/service fields, the invoice issue date for document fields, and the actual due date for `DatumZapadlosti`.
- Added an editable Due date field with a calendar picker and a default of 15 days after the invoice issue date; saved and historical invoices retain their due dates.
- Corrected Minimax VAT-code and VAT-account selection when the editable base-country VAT rate is changed.
- Strengthened money rounding and Minimax XML validation, including safe customer codes and exact field limits.

## Version 1.2.75

- Additional-cost amounts are now treated as VAT-inclusive gross values; the taxable base and VAT are extracted from the entered total across results, PDFs, drafts, and Minimax XML.
- Additional-cost VAT is now selected as `22% S` by default, `9.5% Z`, or a custom rate.
- Minimax XML now includes additional costs under `S` (general), `Z` (reduced), or `N` (custom/non-taxable), with matching journal and invoice totals.
- Reduced the quick invoice history displayed under a customer to the 7 most recent invoices; the All view and full invoice browser remain unrestricted.
- Shortened the dedicated historical invoice action label to `PDF` in both English and Slovenian without changing its no-save behavior.

## Version 1.2.72

- Minimax XML filenames now use the currently displayed invoice number, for example `temeljnica-RCHA-26-0008.xml`, without saving the invoice or advancing its number.
- Added an independently configurable XML filename prefix in both English and Slovenian language INI files, with `temeljnica` as the default in both languages.

## Version 1.2.71

- Added a dedicated Invoice PDF action to every Invoice History view so a saved invoice can be reproduced without creating a new invoice, advancing its number, or writing another database record.
- Historical PDFs retain the saved invoice number and original issue date, with the due date calculated from that original date.
- Added English `Invoice PDF` and Slovenian `PDF računa` labels while keeping the main Generate PDF / Save action unchanged for new invoices.

## Version 1.2.70

- Minimax XML now imports every route portion outside the selected base country as `N` (`Neobdavčeno`) with zero VAT, while PDFs retain the configured foreign-country VAT calculation.
- Foreign gross amounts are booked as non-taxable revenue in XML so the journal remains balanced, and foreign VAT-account entries are no longer generated.

## Version 1.2.69

- Minimax XML now adds `<VrstaObracunaDDV>PS</VrstaObracunaDDV>` when a route contains kilometres outside the configured base country and omits it for base-country-only routes.
- Added a manual tax-rate field to additional costs, with taxable cost bases and VAT included in regular and draft PDF line items, totals, and VAT summaries.
- Expanded Results with a per-cost breakdown showing description, base, tax rate, tax amount, and gross amount.
- Added combined additional-cost totals, total invoice VAT, invoice-wide net total, and invoice-wide final amount to Results.

## Version 1.2.68

- Removed the obsolete generated output filename field displayed below Service date.
- Added a calendar button for selecting Service date while preserving the visible `DD/MM/YYYY` format and ISO database/XML values.
- Removed remaining invoice and draft dependencies on the deleted output filename field.

## Version 1.2.67

- PDF filenames now use the saved invoice number, producing names such as `racun-RCHA-26-0003.pdf` in Slovenian and `invoice-RCHA-26-0003.pdf` in English.
- Moved the localized PDF filename prefixes into the English and Slovenian language files, with a safe English fallback for future translations.
- Strengthened customer and invoice deletion confirmations in modal windows with explicit irreversible-action warnings in English and Slovenian.

## Version 1.2.66

- Renamed the combined invoice action to Generate PDF / Save in English and Ustvari PDF / Shrani in Slovenian.
- Every press now saves a new invoice; when the displayed `RCHA-YY-NNNN` number already exists, the server assigns the next available database number before generating the PDF.

## Version 1.2.65

- Changed automatic invoice numbering to the yearly `RCHA-YY-NNNN` format, starting at `RCHA-26-0001` for 2026 and resetting the sequence for each new year.
- The next invoice number is loaded from saved database records when the calculator opens and rechecked when an automatically numbered invoice is saved.
- Invoice numbers with custom suffixes separated by a hyphen or whitespace, such as `RCHA-26-0007-(custom text)` or `RCHA-26-0007 (custom text)`, now advance the sequence to `RCHA-26-0008`.
- Removed the separate Save Invoice button and replaced Generate PDF with Invoice PDF / Save, which saves the invoice before generating its PDF.
- Applied the combined save-and-PDF workflow to regular and draft invoices while allowing saved invoices to be downloaded again without duplicate records.
- Added a SHA-256 package checksum to the Joomla update feed for update integrity verification.

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
