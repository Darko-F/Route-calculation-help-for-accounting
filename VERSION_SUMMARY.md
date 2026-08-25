# Version Summary

## Update distribution

- Moved the public Joomla update feed to `https://shop.topoweryou.com/files/updatesxml/transport-accounting-package.xml`.
- Routed protected package downloads through the VirtueMart Update Key Manager, backed by `vmfiles/salefiles/routecalculationhelp`.
- Centralized the subscriber update key in the Transport Accounting component's global Options alongside the Google Maps API key.

## Transport Accounting 2.0.0

- Renamed the complete Joomla suite to **Transport Accounting**.
- Replaced the package, module, component, and installer-plugin identifiers with `pkg_transport_accounting`, `mod_transport_accounting`, `com_transport_accounting`, and `plg_installer_transportaccountingupdatekey`.
- Renamed PHP namespaces, language constants, ACL assets, AJAX routes, form paths, database tables, iframe integration identifiers, and build artifacts to match the new identity.
- Changed newly generated invoice numbers to the `TA-YY-NNNN` format and pro forma numbers to `PR-TA-YY-NNNN`; existing historical invoice numbers should remain unchanged when records are migrated manually.
- Added JED-recognizable GPL license notices to every packaged PHP file reported by the checker.
- Changed the JED/package listing name to **Transport Accounting** and retained the intentional same-origin calculator iframe architecture.
- This is a clean-break identity change without automatic migration from the former Joomla identifiers. Back up the database and manually copy required historical customers, invoices, drafts, and payments before removing the former extension.
- Set the suite package, module, administrator component, and installer update-key plugin to version 2.0.0.

## Suite Package 1.6.14

- Added a selectable invoice issue date alongside the service date and payment due date, defaulting to the current date as before.
- Persisted and restored the selected issue date for invoices and used it in generated PDFs and Minimax XML exports.
- Kept backward compatibility for saved invoices without an explicit issue date by falling back to their original creation date.
- Added an additional-cost treatment option: costs are added on top of the entered gross price by default, while the previous included-in-gross calculation remains available.
- Preserved the old included-in-gross behavior when restoring invoices saved before this option existed.
- Updated the enclosed module to version 1.6.13; the component remains at 1.2.3 and the installer update helper at 1.0.5.

## Suite Package 1.6.13

- Kept Minimax country accounts in the component's global Options and changed Joomla module-specific Minimax settings into optional overrides.
- Merged module country-account overrides into global country accounts by two-letter ISO code; blank module account fields inherit their global values.
- Added a separate Minimax revenue account for additional costs in global Options and as an optional Joomla module override.
- Posted taxable and non-taxable additional costs to their configured revenue account while retaining the base-country revenue account as a backward-compatible fallback.
- Updated the enclosed versions to module 1.6.12, component 1.2.3, and installer update helper 1.0.5.

## Suite Package 1.6.12

- Added a shared **Subscriber update key** field directly below the Google Maps API key in component Options.
- Updated the installer helper to read the shared component key while retaining the old plugin parameter as a transition fallback.
- Existing subscribers should note their old plugin key before updating and enter it into **Components -> Transport Accounting -> Options** after installing 1.6.12.
- Updated the enclosed versions to module 1.6.11, component 1.2.2, and installer update helper 1.0.5.

## Suite Package 1.6.11

- Loading a saved invoice now also restores its additional-cost rows, amounts, and VAT presets into the calculator form.
- Added an Update Invoice action for loaded invoices, preserving the original invoice number, issue timestamp, conversion links, and payments while saving corrected route, customer, text, date, VAT, and additional-cost data.
- Updated the enclosed versions to module 1.6.11, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.10

- Consolidated local and Minimax customer codes into the single **Customer code** field.
- Removed the separate Minimax customer-code input and all runtime persistence, loading, search, and export dependencies on it.
- Minimax XML now uses the current customer code exactly as entered and validates Minimax's 10-character and supported-character requirements at export time.
- Restored historical invoice codes remain final fallbacks; the current saved customer code and same-customer form value retain priority.
- Updated the enclosed versions to module 1.6.10, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.9

- Removed historical local and Minimax customer codes from the invoice-restoration source order.
- Invoice restoration now uses the invoice table's database `customer_id` first and refreshes both code fields directly from that exact current customer record.
- Invoice lists include the customer's current local code alongside the current Minimax code, avoiding old payload values while the refresh completes.
- Updated the enclosed versions to module 1.6.9, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.8

- XML export now always reloads a saved customer's current Minimax code, even when the loaded invoice contains a different non-empty historical code.
- The current customer database value overrides the invoice payload and the value previously displayed by the loaded invoice.
- XML export stops safely if the current customer record cannot be loaded, instead of exporting a potentially stale customer code.
- Updated the enclosed versions to module 1.6.8, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.7

- XML export now reloads the selected customer's saved Minimax code when an older invoice leaves the form value empty.
- If no dedicated Minimax code is saved, XML can use the local customer code when it satisfies Minimax's 10-character and supported-character rules.
- Relabeled the local customer code as optional so it is clearly distinguished from the dedicated Minimax customer code.
- Updated the enclosed versions to module 1.6.7, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.6

- Loading a saved invoice now restores pickup, drop-off, additional places, and the return-trip selection into the route form.
- Restores the saved country split rows, kilometres, VAT rates, Minimax accounts, and country PDF notes.
- New invoices store the raw drop-off, stop list, and return-trip state for exact restoration; older invoices reconstruct them from the saved route description.
- Updated the enclosed versions to module 1.6.6, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.5

- Restoring an invoice now reads the current Minimax customer code from the customer record instead of relying only on the historical invoice payload.
- Older invoices created before the Minimax code field was added can therefore be exported after the code is added to the existing customer.
- Preserved a currently loaded Minimax code when restoring a historical invoice for the same customer.
- Updated the enclosed versions to module 1.6.5, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.4

- Added a separate customer code specifically for Minimax, independent from the module's editable local customer code.
- Minimax XML now uses only the exact configured Minimax customer code for both `Sifra` and `SifraStranke`; database-ID codes such as `DB11` are no longer generated.
- XML export stops with a clear message when the Minimax customer code is empty, instead of creating a duplicate customer in Minimax.
- Enforced uniqueness for non-empty local and Minimax customer codes while continuing to allow multiple customers with either code empty.
- Updated the enclosed versions to module 1.6.4, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.3

- Moved Minimax customer master data before journal entries so account 1200 can resolve the imported or existing customer.
- Preserved `VrstaObracunaDDV=PP` (`Prevoz potnikov`) while correcting customer-first XML processing so Minimax can validate it with the resolved customer.
- Added the invoice reference as `VezaZaPlacilo` on the customer-receivable line.
- Updated the enclosed versions to module 1.6.3, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.2

- Corrected Minimax XML exports so customers with an empty business code are included with a stable XML-only code based on their database customer ID.
- Automatically saves a new code-less customer before XML export so a database identity is available.
- Aligned the exported customer field-length validation with the published Minimax XSD.
- Updated the enclosed versions to module 1.6.2, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.1

- Made the customer code editable and optional while retaining automatic generation from the VAT ID.
- Changed customer identity, invoice history, draft services, loading, editing, and deletion to use the unique database customer ID.
- Allowed multiple customers with no customer code while continuing to reject duplicate non-empty customer codes.
- Allowed Minimax XML export without requiring a business customer code.
- Expanded stored route descriptions and draft labels to support invoices with many additional stops without database truncation errors.
- Added a tracked updater package and a repeatable release-build script so update feeds cannot deploy without their matching downloadable ZIP.
- Updated the enclosed versions to module 1.6.1, component 1.2.1, and installer update-key plugin 1.0.4.

## Suite Package 1.6.0

- Renamed the administrator component to Transport Accounting, with a localized Slovenian name.
- Moved the Google Maps key, base country, company/PDF identity, country VAT configuration, and Minimax accounts from individual module instances into the component's shared Options.
- Added independent global/module choices for Google Maps, company/PDF, Minimax, and country settings, while keeping global settings as each default.
- Enabled language-assigned modules to use their own translated PDF footer while continuing to share settings such as the global Google Maps API key.
- Moved Base country into the Countries tab in both global and module settings.
- Corrected the package update feed client so Joomla 6 associates the 1.6.0 update with the installed suite package.
- Added an upgrade migration that copies existing values from the first published module into component Options without overwriting component values already saved there.
- Updated the module and administrator payment-confirmation code to read the same component-wide settings.
- Updated the enclosed versions to module 1.6.0, component 1.2.0, and installer update-key plugin 1.0.4.

## Suite Package 1.5.0

- Added administrator payment tracking for invoices with Unpaid / Neplačano, Partially paid / Delno plačano, and Paid / Plačano states.
- Added payment date, amount, method, reference, and note history without changing the invoice's original payment due date.
- Added Unicode Payment Confirmation / Potrdilo o plačilu PDFs with customer and invoice details, payment history, paid and remaining amounts, configured footer text, and a prominent PAID / PLAČANO label.
- Kept payment confirmations outside the invoice-number sequence and documented that they do not replace required FURS fiscal confirmation for cash or card payments.
- Kept Minimax XML at the full original invoice value for paid and partially paid invoices; payments are reconciled separately through a Minimax bank statement or journal.
- Prevented long payment methods, references, and notes from overlapping adjacent columns in confirmation PDFs.
- Added payment status and administrator Add payment links to the front-end Računi / Invoices browser; payment-confirmation PDFs automatically follow Joomla's active site language.
- Updated the enclosed versions to module 1.5.0, Transport Accounting component 1.1.0, and installer update-key plugin 1.0.4.
- Localized the prominent paid label to PAID in English confirmations and PLAČANO in Slovenian confirmations.
- Added the configured signature label and signature image to payment-confirmation PDFs.

## Suite Package 1.4.2

- Made the combined suite the single supported installation and update unit.
- Removed individual update feeds and update-server registrations from the module, component, and plugin to prevent duplicate or incompatible partial updates.
- Updated the enclosed versions to module 1.4.1, Transport Accounting component 1.0.1, and installer update-key plugin 1.0.4.
- Retained only the combined-suite update feed in the `updates` folder.

## Suite Package 1.4.1

- Combined the site module 1.4.0, Transport Accounting component 1.0.0, and installer update-key plugin 1.0.3 into one Joomla installation ZIP.
- Kept plugin enablement and subscriber-key configuration under Joomla's plugin manager.
- Added a dedicated Joomla update feed for future combined-suite upgrades.

## Version 1.4.0

- Moved invoice and Predračun / pro forma deletion out of the module editor into the dedicated administrator-only Transport Accounting component.
- Added server-side search, invoice/pro forma filtering, sortable columns, and 25/50/100-row pagination; only the current page is loaded from the database.
- Added Joomla component `core.manage`, `core.delete`, and `core.admin` ACL rules plus POST-only CSRF-protected bulk deletion.
- Preserved conversion integrity: a converted pro forma and its linked invoice can be deleted together, and deleting only the invoice reopens the source pro forma.
- Replaced the module's database-backed management field with a lightweight link, so opening any module instance no longer loads document records.
- Added a combined Joomla package for installing the site module and administrator component together, plus separate component updates.

## Version 1.3.5

- Reduced the maximum PDF logo dimensions by 15%, from 32 × 12 mm to 27.2 × 10.2 mm, while preserving its aspect ratio.
- Removed invoice and pro forma deletion controls from the public calculator and disabled the public AJAX deletion route.
- Added an administrator-only Document Management tab to the module editor with search, invoice/pro forma filtering, status, customer, date, amount, and deletion controls.
- Protected administrator deletion with Joomla `core.delete` permission checks and CSRF tokens.
- Preserved Predračun conversion links: converted pro forma invoices require their linked invoice to be deleted first; deleting that invoice reopens the source pro forma invoice.

## Version 1.3.4

- Added a document-type filter to the all-invoices browser with All documents, Invoices only, and Pro forma invoices only options.
- Applied document-type filtering in the database query so result totals and pagination remain accurate.
- Added English and Slovenian labels for the new filter.

## Version 1.3.3

- Translated all English `Predračun` labels to `Pro forma invoice`, including actions, statuses, confirmations, PDF title, document number, and filename.
- Changed the PDF logo option to a Joomla Media selector and normalized selected site paths, absolute URLs, filesystem paths below the Joomla root, and module-media-relative filenames.
- Made the configured centered footer more visible with a separator, larger text, and safer bottom-page spacing; moved the signature upward to reserve footer space.

## Version 1.3.2

- Added saved Predračun documents with their own automatic `PR-TA-YY-NNNN` number sequence.
- Added a dedicated Predračun PDF action and Slovenian/English PDF labels, including a prominent `PREDRAČUN` heading and `Predračun št.` number.
- Predračun PDFs omit the stamp/signature and cannot be exported to Minimax.
- Added one-click conversion from an open Predračun to a separately numbered `TA-YY-NNNN` invoice while retaining the source/conversion link in history.
- Added open/converted Predračun status badges and guarded conversions against duplicate invoices.
- Added configurable invoice header logo and centered Unicode-safe footer text, including support for `š`, `č`, and `ć`.

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

- Minimax XML filenames now use the currently displayed invoice number, for example `temeljnica-TA-26-0008.xml`, without saving the invoice or advancing its number.
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

- PDF filenames now use the saved invoice number, producing names such as `racun-TA-26-0003.pdf` in Slovenian and `invoice-TA-26-0003.pdf` in English.
- Moved the localized PDF filename prefixes into the English and Slovenian language files, with a safe English fallback for future translations.
- Strengthened customer and invoice deletion confirmations in modal windows with explicit irreversible-action warnings in English and Slovenian.

## Version 1.2.66

- Renamed the combined invoice action to Generate PDF / Save in English and Ustvari PDF / Shrani in Slovenian.
- Every press now saves a new invoice; when the displayed `TA-YY-NNNN` number already exists, the server assigns the next available database number before generating the PDF.

## Version 1.2.65

- Changed automatic invoice numbering to the yearly `TA-YY-NNNN` format, starting at `TA-26-0001` for 2026 and resetting the sequence for each new year.
- The next invoice number is loaded from saved database records when the calculator opens and rechecked when an automatically numbered invoice is saved.
- Invoice numbers with custom suffixes separated by a hyphen or whitespace, such as `TA-26-0007-(custom text)` or `TA-26-0007 (custom text)`, now advance the sequence to `TA-26-0008`.
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
- Updated Joomla update server and download URLs to the `/transportaccounting/files/transportaccounting/` paths.
- Updated protected downloads `.htaccess` so `download.php` is reachable while ZIPs and key files stay blocked from direct access.

## Installer Plugin 1.0.2

- Updated update server and package download URLs to the corrected server paths.
- Updated download-key injection to support the corrected protected download endpoint.
- Added support for Joomla event-style package download URL updates.

## Installer Plugin 1.0.3

- Added subscriber-key support for protected Transport Accounting component and combined suite package downloads.

## Update Server

- Version update XML now points to `transport_accounting_v1.2.39.zip`.
- Installer plugin update XML now points to `plg_installer_transportaccountingupdatekey_v1.0.2.zip`.
- `download.php` accepts valid keys through `key` or `dlid` and can run from inside the `downloads/` directory.
