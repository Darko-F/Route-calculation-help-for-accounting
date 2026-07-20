<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_route_calculation_help_for_accounting
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

$document = Factory::getApplication()->getDocument();
$languageTag = Factory::getApplication()->getLanguage()->getTag();
$calculatorVersion = (string) (filemtime(__DIR__ . '/../media/calculator.html') ?: '1.2.39');
$calculatorUrl = Uri::root(true) . '/modules/mod_route_calculation_help_for_accounting/media/calculator.html?v=' . rawurlencode($calculatorVersion);
$ajaxUrl = Uri::root() . 'index.php?option=com_ajax&module=route_calculation_help_for_accounting&format=json';
$administratorDocumentsUrl = Uri::root() . 'administrator/index.php?option=com_rcha_documents&view=documents';
$tokenName = Session::getFormToken();
$tokenValue = '1';
$resolvePdfImageUrl = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '' || preg_match('#^(?:https?:)?//#i', $value) || str_starts_with($value, 'data:')) {
        return $value;
    }
    if (defined('JPATH_ROOT') && str_starts_with($value, rtrim(JPATH_ROOT, '/') . '/')) {
        $value = substr($value, strlen(rtrim(JPATH_ROOT, '/')) + 1);
    }
    if (str_starts_with($value, '/')) {
        return $value;
    }
    $siteRoot = rtrim(Uri::root(true), '/');
    if (preg_match('#^(?:images|media|modules)/#i', $value)) {
        return $siteRoot . '/' . ltrim($value, '/');
    }
    return $siteRoot . '/modules/mod_route_calculation_help_for_accounting/media/' . ltrim($value, '/');
};
$calculatorTextKeys = [
    'Taxi Transfer VAT Route Calculator' => 'CALCULATOR_TITLE',
    'Route calculation help for accounting' => 'CALCULATOR_NAV_TITLE',
    'Fast Slovenia route split' => 'CALCULATOR_NAV_SUBTITLE',
    'Calculates route distance, Slovenia km, outside-Slovenia km, gross price, net price, Slovenian VAT and invoice text.' => 'CALCULATOR_INTRO',
    'Google Maps status' => 'CALCULATOR_GOOGLE_MAPS_STATUS',
    'Loading Google Maps...' => 'CALCULATOR_LOADING_GOOGLE_MAPS',
    'The Google Maps API key is configured in the module Options tab.' => 'CALCULATOR_API_KEY_HELP',
    'Transfer details' => 'CALCULATOR_TRANSFER_DETAILS',
    'Pickup' => 'CALCULATOR_PICKUP',
    'Drop-off' => 'CALCULATOR_DROPOFF',
    'Additional places' => 'CALCULATOR_ADDITIONAL_PLACES',
    'Add place' => 'CALCULATOR_ADD_PLACE',
    'Return trip to pickup' => 'CALCULATOR_RETURN_TRIP',
    'Additional costs (deduct from gross price)' => 'CALCULATOR_ADDITIONAL_COSTS',
    'Add cost' => 'CALCULATOR_ADD_COST',
    'Clear' => 'CALCULATOR_CLEAR',
    'Total deductions:' => 'CALCULATOR_TOTAL_DEDUCTIONS',
    'Final price with VAT (€)' => 'CALCULATOR_GROSS_PRICE',
    'Enter final price or price without tax charged to the customer. Other price will be calculated automatically.' => 'CALCULATOR_GROSS_PRICE_HELP',
    'Only price without VAT (€)' => 'CALCULATOR_NET_PRICE',
    'Base country VAT rate (%)' => 'CALCULATOR_SLOVENIAN_VAT_RATE',
    'Base - {country} VAT rate (%)' => 'CALCULATOR_BASE_COUNTRY_VAT_RATE',
    'Italy' => 'CALCULATOR_ITALY',
    'Croatia' => 'CALCULATOR_CROATIA',
    'Austria' => 'CALCULATOR_AUSTRIA',
    'Germany' => 'CALCULATOR_GERMANY',
    'Hungary' => 'CALCULATOR_HUNGARY',
    'Other / custom' => 'CALCULATOR_OTHER_CUSTOM',
    'Country VAT split' => 'CALCULATOR_COUNTRY_VAT_SPLIT',
    'Add country' => 'CALCULATOR_ADD_COUNTRY',
    'Edit any country km, VAT rate, account and PDF note. Recalculation automatically adjusts the remaining country km to the total route.' => 'CALCULATOR_COUNTRY_VAT_SPLIT_HELP',
    'Recalculate country VAT split' => 'CALCULATOR_RECALCULATE_COUNTRIES',
    'Country split method' => 'CALCULATOR_SPLIT_METHOD',
    'Fast: embedded Slovenia border polygon' => 'CALCULATOR_SPLIT_FAST',
    'Slower: Google reverse geocoding' => 'CALCULATOR_SPLIT_GEOCODE',
    'Fast method is instant. Country kilometres remain editable after calculation.' => 'CALCULATOR_SPLIT_HELP',
    'Sampling accuracy' => 'CALCULATOR_SAMPLING_ACCURACY',
    'Fast / rough: every 10 km' => 'CALCULATOR_SAMPLE_10',
    'Normal: every 5 km' => 'CALCULATOR_SAMPLE_5',
    'Accurate and fast: every 2 km' => 'CALCULATOR_SAMPLE_2',
    'Very accurate: every 1 km' => 'CALCULATOR_SAMPLE_1',
    'High: every 500 m' => 'CALCULATOR_SAMPLE_05',
    'Waiting for Google Maps...' => 'CALCULATOR_WAITING_GOOGLE_MAPS',
    'Actions' => 'CALCULATOR_ACTIONS',
    'Calculate route and VAT split' => 'CALCULATOR_CALCULATE_ROUTE',
    'Map' => 'CALCULATOR_MAP',
    'Driving route' => 'CALCULATOR_DRIVING_ROUTE',
    'Results' => 'CALCULATOR_RESULTS',
    'Total route distance' => 'CALCULATOR_TOTAL_ROUTE_DISTANCE',
    'Estimated km in Slovenia' => 'CALCULATOR_ESTIMATED_SI_KM',
    'Estimated km in {country}' => 'CALCULATOR_ESTIMATED_BASE_COUNTRY_KM',
    'Estimated km outside Slovenia' => 'CALCULATOR_ESTIMATED_OUTSIDE_KM',
    'Total price without VAT' => 'CALCULATOR_TOTAL_NET_PRICE',
    'Slovenian taxable base' => 'CALCULATOR_SLOVENIAN_TAXABLE_BASE',
    '{country} taxable base' => 'CALCULATOR_BASE_COUNTRY_TAXABLE_BASE',
    'Outside Slovenia part' => 'CALCULATOR_OUTSIDE_PART',
    'Slovenian VAT' => 'CALCULATOR_SLOVENIAN_VAT',
    '{country} VAT' => 'CALCULATOR_BASE_COUNTRY_VAT',
    'Outside Slovenia VAT' => 'CALCULATOR_OUTSIDE_VAT',
    'Total invoice amount' => 'CALCULATOR_TOTAL_INVOICE_AMOUNT',
    'Total VAT' => 'CALCULATOR_TOTAL_VAT',
    'Additional costs' => 'CALCULATOR_RESULTS_ADDITIONAL_COSTS',
    '{base} + tax {rate}% ({tax}) = {gross}' => 'CALCULATOR_RESULTS_COST_DETAIL',
    'Additional costs total' => 'CALCULATOR_RESULTS_ADDITIONAL_COSTS_TOTAL',
    'Base: {base} / Tax: {tax} / Total: {total}' => 'CALCULATOR_RESULTS_COST_TOTAL_DETAIL',
    'Invoice / Customer' => 'CALCULATOR_INVOICE_CUSTOMER',
    'Invoice no' => 'CALCULATOR_INVOICE_NO',
    'Generate' => 'CALCULATOR_GENERATE',
    'Sifra Stranke' => 'CALCULATOR_SIFRA_STRANKE',
    'Service date' => 'CALCULATOR_SERVICE_DATE',
    'Due date' => 'CALCULATOR_DUE_DATE',
    'Customer name' => 'CALCULATOR_CUSTOMER_NAME',
    'Address' => 'CALCULATOR_ADDRESS_POST',
    'Postcode' => 'CALCULATOR_CUSTOMER_POSTCODE',
    'City' => 'CALCULATOR_CUSTOMER_CITY',
    'Country code' => 'CALCULATOR_CUSTOMER_COUNTRY_CODE',
    'ID za DDV kupca: XX1234' => 'CALCULATOR_CUSTOMER_VAT_ID',
    'Save customer' => 'CALCULATOR_SAVE_DETAILS',
    'Customers' => 'CALCULATOR_CUSTOMERS',
    'Invoices' => 'CALCULATOR_INVOICES',
    'Add payment' => 'CALCULATOR_ADD_PAYMENT',
    'Payment confirmation' => 'CALCULATOR_PAYMENT_CONFIRMATION',
    'Unpaid' => 'CALCULATOR_PAYMENT_UNPAID',
    'Partially paid' => 'CALCULATOR_PAYMENT_PARTIALLY_PAID',
    'Paid' => 'CALCULATOR_PAYMENT_PAID',
    'Add to draft Invoice' => 'CALCULATOR_ADD_TO_DRAFT',
    'Save Invoice' => 'CALCULATOR_SAVE_INVOICE_HISTORY',
    'Auto generated invoice text' => 'CALCULATOR_AUTO_INVOICE_TEXT',
    'English' => 'CALCULATOR_ENGLISH',
    'Slovenian' => 'CALCULATOR_SLOVENIAN',
    'Generate PDF / Save' => 'CALCULATOR_GENERATE_PDF',
    'Generate Predračun / Save' => 'CALCULATOR_GENERATE_PROFORMA_PDF',
    'PDF' => 'CALCULATOR_GENERATE_HISTORY_PDF',
    '__PDF_FILENAME_PREFIX__' => 'PDF_FILENAME_PREFIX',
    '__PROFORMA_PDF_FILENAME_PREFIX__' => 'PROFORMA_PDF_FILENAME_PREFIX',
    '__XML_FILENAME_PREFIX__' => 'XML_FILENAME_PREFIX',
    'Export minimax XML' => 'CALCULATOR_EXPORT_MINIMAX_XML',
    'Load saved customer' => 'CALCULATOR_LOAD_SAVED_CUSTOMER',
    'Saved customers' => 'CALCULATOR_SAVED_CUSTOMERS',
    'Load' => 'CALCULATOR_LOAD',
    'Delete' => 'CALCULATOR_DELETE',
    'Close' => 'CALCULATOR_CLOSE',
    'Search by customer name or code' => 'CALCULATOR_SEARCH_CUSTOMER',
    'Start typing...' => 'CALCULATOR_START_TYPING',
    'e.g. Ljubljana Airport' => 'CALCULATOR_PLACEHOLDER_PICKUP',
    'e.g. Venice Airport' => 'CALCULATOR_PLACEHOLDER_DROPOFF',
    'Invoice no (e.g. 26-0007)' => 'CALCULATOR_PLACEHOLDER_INVOICE_OLD',
    'Invoice no (e.g. RCHA-26-0001)' => 'CALCULATOR_PLACEHOLDER_INVOICE',
    'Customer code (Sifra)' => 'CALCULATOR_PLACEHOLDER_CUSTOMER_CODE',
    'Generated file name (e.g. racun_2026_2026-06-18)' => 'CALCULATOR_PLACEHOLDER_FILE_NAME',
    'Custom invoice message (optional)' => 'CALCULATOR_PLACEHOLDER_CUSTOM_MESSAGE',
    'Add stop or place' => 'CALCULATOR_ADD_STOP_OR_PLACE',
    'Remove' => 'CALCULATOR_REMOVE',
    'Description (e.g. parking)' => 'CALCULATOR_PLACEHOLDER_ADJUSTMENT_DESC',
    'Amount including VAT (€)' => 'CALCULATOR_ADJUSTMENT_GROSS_AMOUNT',
    'Tax %' => 'CALCULATOR_ADJUSTMENT_TAX',
    'Additional cost VAT' => 'CALCULATOR_ADJUSTMENT_VAT',
    '22% S - General' => 'CALCULATOR_ADJUSTMENT_VAT_GENERAL',
    '9.5% Z - Reduced' => 'CALCULATOR_ADJUSTMENT_VAT_REDUCED',
    '0% N - Non-taxable' => 'CALCULATOR_ADJUSTMENT_VAT_CUSTOM',
    'Google Maps API key is missing. Enter it in the module Options tab.' => 'CALCULATOR_API_KEY_MISSING',
    'Map cannot load until the module API key is configured.' => 'CALCULATOR_MAP_CANNOT_LOAD',
    'Google Maps loaded successfully.' => 'CALCULATOR_MAPS_LOADED_SUCCESS',
    'Google Maps loaded. Enter pickup and drop-off, then calculate.' => 'CALCULATOR_MAPS_LOADED_READY',
    'Google Maps failed to load. Check API key, billing and enabled APIs.' => 'CALCULATOR_MAPS_FAILED',
    'Google Maps Routes library failed to load. Enable Routes API for this key.' => 'CALCULATOR_ROUTES_FAILED_ENABLE',
    'Google Maps Routes library failed to load. Check API key, billing and enabled APIs.' => 'CALCULATOR_ROUTES_FAILED_CHECK',
    'Google Maps Routes library is not loaded yet.' => 'CALCULATOR_ROUTES_NOT_LOADED',
    'Pickup and drop-off are required.' => 'CALCULATOR_PICKUP_DROPOFF_REQUIRED',
    'Routes API supports a maximum of 25 additional places.' => 'CALCULATOR_MAX_STOPS',
    'Enter a valid final price or price without tax.' => 'CALCULATOR_VALID_GROSS_PRICE',
    'Calculating route...' => 'CALCULATOR_CALCULATING_ROUTE',
    'Route error: no route found.' => 'CALCULATOR_ROUTE_NO_ROUTE',
    'Domestic Slovenia route detected. Outside Slovenia set to 0 km.' => 'CALCULATOR_DOMESTIC_ROUTE',
    'Done. Fast Slovenia border polygon split used.' => 'CALCULATOR_FAST_SPLIT_DONE',
    'Using slower Google reverse geocoding method...' => 'CALCULATOR_GEOCODING_SPLIT_STARTED',
    'Done. Google reverse geocoding split used.' => 'CALCULATOR_GEOCODING_SPLIT_DONE',
    'Route calculated, but country boundaries could not be loaded. Edit the country rows and recalculate.' => 'CALCULATOR_BOUNDARIES_FAILED_EDIT_COUNTRIES',
    'Route calculated, but Google Geocoding split failed: {error}. Edit the country rows and recalculate.' => 'CALCULATOR_GEOCODING_SPLIT_FAILED',
    'Route error: {error}. Check that Routes API is enabled for this Google key.' => 'CALCULATOR_ROUTE_ERROR_CHECK_KEY',
    'VAT rate changed. Totals recalculated.' => 'CALCULATOR_VAT_RATE_CHANGED',
    'Price changed. Totals recalculated.' => 'CALCULATOR_PRICE_CHANGED',
    'Database save is available only inside the Joomla module package.' => 'CALCULATOR_DATABASE_ONLY_JOOMLA',
    'Joomla database request failed.' => 'CALCULATOR_DATABASE_REQUEST_FAILED',
    'Sifra Stranke is required.' => 'CALCULATOR_SIFRA_REQUIRED',
    'Customer name is required.' => 'CALCULATOR_CUSTOMER_NAME_REQUIRED',
    'Saving customer details...' => 'CALCULATOR_SAVING_CUSTOMER',
    'Customer details saved for' => 'CALCULATOR_CUSTOMER_SAVED_FOR',
    'Loading saved customers...' => 'CALCULATOR_LOADING_CUSTOMERS',
    'No saved customers found.' => 'CALCULATOR_NO_SAVED_CUSTOMERS',
    'No matching customers found.' => 'CALCULATOR_NO_MATCHING_CUSTOMERS',
    'customers found. Select one to load.' => 'CALCULATOR_CUSTOMERS_FOUND_SUFFIX',
    'customer found. Select one to load.' => 'CALCULATOR_CUSTOMER_FOUND_SUFFIX',
    'Warning: Delete this saved customer? The customer will be removed, but invoice history will stay saved. This cannot be undone.' => 'CALCULATOR_DELETE_CUSTOMER_CONFIRM',
    'Deleting customer...' => 'CALCULATOR_DELETING_CUSTOMER',
    'Customer deleted.' => 'CALCULATOR_CUSTOMER_DELETED',
    'Enter Sifra Stranke to load a customer.' => 'CALCULATOR_ENTER_SIFRA_LOAD',
    'Loading customer details...' => 'CALCULATOR_LOADING_CUSTOMER',
    'Customer details loaded.' => 'CALCULATOR_CUSTOMER_LOADED',
    'Calculate a route before saving invoice history.' => 'CALCULATOR_CALCULATE_BEFORE_SAVE_HISTORY',
    'Invoice number is required.' => 'CALCULATOR_INVOICE_NUMBER_REQUIRED',
    'Generating invoice number...' => 'CALCULATOR_GENERATING_INVOICE_NUMBER',
    'Invoice number generated.' => 'CALCULATOR_INVOICE_NUMBER_GENERATED',
    'Saving invoice history...' => 'CALCULATOR_SAVING_INVOICE_HISTORY',
    'Invoice history saved.' => 'CALCULATOR_INVOICE_HISTORY_SAVED',
    'Invoice history' => 'CALCULATOR_INVOICE_HISTORY',
    'All' => 'CALCULATOR_ALL',
    'All invoices for this customer' => 'CALCULATOR_ALL_CUSTOMER_INVOICES',
    'Customer' => 'CALCULATOR_CUSTOMER',
    'Loading all invoices...' => 'CALCULATOR_LOADING_ALL_INVOICES',
    'Loading invoices...' => 'CALCULATOR_LOADING_INVOICES',
    '{count} invoices found.' => 'CALCULATOR_INVOICES_FOUND',
    'Showing the latest {count} invoices. Filter to search all.' => 'CALCULATOR_LATEST_INVOICES',
    'Choose date' => 'CALCULATOR_CHOOSE_DATE',
    'Warning: Delete invoice {invoiceNumber}? This cannot be undone.' => 'CALCULATOR_DELETE_INVOICE_CONFIRM',
    'Deleting invoice...' => 'CALCULATOR_DELETING_INVOICE',
    'Invoice deleted.' => 'CALCULATOR_INVOICE_DELETED',
    'Open predračun' => 'CALCULATOR_PROFORMA_OPEN',
    'Converted to {invoiceNumber}' => 'CALCULATOR_PROFORMA_CONVERTED_TO',
    'Create Invoice' => 'CALCULATOR_CREATE_INVOICE',
    'Create a new invoice from predračun {documentNumber}?' => 'CALCULATOR_CREATE_INVOICE_CONFIRM',
    'Predračun cannot be exported to Minimax. Create the invoice first.' => 'CALCULATOR_PROFORMA_NO_MINIMAX',
    'Predračun PDF generated.' => 'CALCULATOR_PROFORMA_PDF_GENERATED',
    'Per page' => 'CALCULATOR_PER_PAGE',
    'Showing {shown} of {total} customers.' => 'CALCULATOR_SHOWING_CUSTOMERS',
    'Showing {shown} of {total} invoices.' => 'CALCULATOR_SHOWING_INVOICES',
    'Pagination' => 'CALCULATOR_PAGINATION',
    'Previous' => 'CALCULATOR_PREVIOUS',
    'Next' => 'CALCULATOR_NEXT',
    'Filter by invoice / order number' => 'CALCULATOR_FILTER_INVOICE',
    'Document type' => 'CALCULATOR_DOCUMENT_TYPE',
    'All documents' => 'CALCULATOR_ALL_DOCUMENTS',
    'Invoices only' => 'CALCULATOR_INVOICES_ONLY',
    'Pro forma invoices only' => 'CALCULATOR_PROFORMAS_ONLY',
    'Invoice Name' => 'CALCULATOR_INVOICE_NAME',
    'Price' => 'CALCULATOR_PRICE',
    'Date' => 'CALCULATOR_DATE',
    'No invoice history matches this filter.' => 'CALCULATOR_NO_INVOICE_HISTORY_MATCH',
    'No saved invoice history for this customer. Use "Generate PDF / Save" after calculating a route to save the invoice and generate its PDF.' => 'CALCULATOR_NO_SAVED_INVOICE_HISTORY',
    'Select or save a customer before loading invoice history.' => 'CALCULATOR_SELECT_CUSTOMER_HISTORY',
    'Loading invoice history...' => 'CALCULATOR_LOADING_INVOICE_HISTORY',
    'Saved invoice payload is invalid.' => 'CALCULATOR_SAVED_PAYLOAD_INVALID',
    'Saved invoice has no calculated route data.' => 'CALCULATOR_SAVED_NO_ROUTE_DATA',
    'Saved invoice restored. PDF and XML can be generated without recalculating the route.' => 'CALCULATOR_SAVED_INVOICE_RESTORED',
    'Calculate a route first, then generate PDF.' => 'CALCULATOR_CALCULATE_BEFORE_PDF',
    'PDF generated.' => 'CALCULATOR_PDF_GENERATED',
    'Calculate a route first, then export Minimax XML.' => 'CALCULATOR_CALCULATE_BEFORE_XML',
    'Enter Slovenia SifraKonta in the module options before exporting Minimax XML.' => 'CALCULATOR_ENTER_SI_ACCOUNT_XML',
    'Enter SifraKonta in every outside country split row before exporting Minimax XML.' => 'CALCULATOR_ENTER_OUTSIDE_ACCOUNT_XML',
    'Configure the VAT rate for every used country in the module options before exporting Minimax XML.' => 'CALCULATOR_CONFIGURE_COUNTRY_VAT_RATE_XML',
    'Enter the Minimax customer receivable account in the module options before exporting Minimax XML.' => 'CALCULATOR_ENTER_RECEIVABLE_ACCOUNT_XML',
    'Enter the base-country VAT liability account in the module options before exporting Minimax XML.' => 'CALCULATOR_ENTER_BASE_COUNTRY_VAT_ACCOUNT_XML',
    'Enter the foreign VAT liability account in the module options before exporting Minimax XML.' => 'CALCULATOR_ENTER_FOREIGN_VAT_ACCOUNT_XML',
    'Minimax XML exported.' => 'CALCULATOR_XML_EXPORTED',
    'Full invoice exported to Minimax. Reconcile the partial payment through a Minimax bank statement or journal.' => 'CALCULATOR_XML_EXPORTED_PARTIAL_PAYMENT',
    'Full invoice exported to Minimax. Reconcile the recorded payment through a Minimax bank statement or journal.' => 'CALCULATOR_XML_EXPORTED_RECORDED_PAYMENT',
    'Edit country rows' => 'CALCULATOR_EDIT_COUNTRY_ROWS',
    'Calculate a route before recalculating country rows.' => 'CALCULATOR_CALCULATE_BEFORE_COUNTRY_ROWS',
    'Country VAT split recalculated. Remaining km adjusted to route total.' => 'CALCULATOR_COUNTRY_SPLIT_RECALCULATED',
    'Enter at least one country row with km.' => 'CALCULATOR_ENTER_COUNTRY_ROW_KM',
    'Country' => 'CALCULATOR_COUNTRY',
    'Country name' => 'CALCULATOR_COUNTRY_NAME',
    'Slovenia' => 'CALCULATOR_SLOVENIA',
    'Outside Slovenia' => 'CALCULATOR_OUTSIDE_SLOVENIA',
    'Km' => 'CALCULATOR_KM',
    'VAT %' => 'CALCULATOR_VAT_PERCENT',
    'SifraKonta' => 'CALCULATOR_SIFRAKONTA_SHORT',
    'PDF note for this country' => 'CALCULATOR_PDF_NOTE_COUNTRY',
    'Optional text printed under the invoice table for this country' => 'CALCULATOR_PDF_NOTE_COUNTRY_HELP',
    ' / {count} countries' => 'CALCULATOR_COUNTRY_COUNT_SUFFIX',
    'VAT ' => 'CALCULATOR_VAT_PREFIX',
    ' not subject to Slovenian VAT' => 'CALCULATOR_NOT_SUBJECT_SI_VAT',
    'Racun st.: {invoiceNo}' => 'PDF_INVOICE_NO',
    'PREDRACUN' => 'PDF_PROFORMA_TITLE',
    'Predracun st.: {invoiceNo}' => 'PDF_PROFORMA_NO',
    'Datum izdaje: {issuePlace}{issueDate}' => 'PDF_ISSUE_DATE',
    'Datum opr. storitve: {serviceDate}' => 'PDF_SERVICE_DATE',
    'Rok placila: {dueDate}' => 'PDF_DUE_DATE',
    'ID za DDV: {taxNumber}' => 'PDF_VAT_ID',
    'IBAN st.: {iban}' => 'PDF_IBAN',
    'Maticna st.: {registrationNumber}' => 'PDF_REGISTRATION_NUMBER',
    'E-posta: {email}' => 'PDF_EMAIL',
    'Telefon: {phone}' => 'PDF_PHONE',
    'Opis' => 'PDF_DESCRIPTION',
    'Cena' => 'PDF_PRICE',
    'Kolicina' => 'PDF_QUANTITY',
    'Enota' => 'PDF_UNIT',
    'DDV' => 'PDF_VAT',
    'Znesek' => 'PDF_AMOUNT',
    'Prevoz {route}' => 'PDF_TRANSFER_ROUTE',
    '{routeTitle}, del poti Slovenija ({km} km)' => 'PDF_SLOVENIA_ROUTE_PART',
    '{routeTitle}, del poti izven Slovenije ({km} km)' => 'PDF_OUTSIDE_ROUTE_PART',
    '{routeTitle}, country part {country} ({km} km)' => 'PDF_COUNTRY_ROUTE_PART',
    'country part {country} ({km} km)' => 'PDF_COUNTRY_PART',
    'kos' => 'PDF_PIECE',
    'Dodatni strosek' => 'PDF_ADDITIONAL_COST',
    'Skupaj' => 'PDF_TOTAL',
    'Za placilo' => 'PDF_TO_PAY',
    'Davcna stopnja' => 'PDF_TAX_RATE',
    'Osnova za DDV' => 'PDF_TAX_BASE',
    'Znesek z DDV' => 'PDF_AMOUNT_WITH_VAT',
    'Pri placilu se sklicujte na stevilko SI00 {paymentReference}.' => 'PDF_PAYMENT_REFERENCE',
    'Za del poti izven Slovenije je uporabljen DDV {outsideVatRate}%, skladno z izbrano drzavo opravljanja storitve.' => 'PDF_OUTSIDE_VAT_NOTE',
    'The place of supply is outside Slovenia under Article 28(1) of the Slovenian VAT Act (ZDDV-1) - (Slovenian) VAT is not charged.' => 'PDF_FOREIGN_TRANSPORT_VAT_EXEMPT',
    'Croatian VAT number: OIB39387192794. 25% VAT charged; we are VAT registered.' => 'PDF_CROATIA_VAT_NOTE',
    'Austrian VAT number: AT686058199. 10% VAT charged; we are VAT registered.' => 'PDF_AUSTRIA_VAT_NOTE',
    'German VAT number: 053/659/50131. 19% VAT charged; we are VAT registered.' => 'PDF_GERMANY_VAT_NOTE',
    'Zahvaljujemo se vam za vase zaupanje in se veselimo nadaljnjega sodelovanja!' => 'PDF_THANK_YOU',
    'Zig in podpis:' => 'PDF_STAMP_SIGNATURE',
    'International passenger transfer {pickup} → {dropoff}.\nTotal route: {totalKm}. Estimated Slovenia part: {siKm}. Outside Slovenia: {outsideKm}.\nSlovenian VAT is calculated only on the part of the route performed in Slovenia, proportionate to kilometres driven in Slovenia.\nTaxable base Slovenia: {taxableBase}. Outside Slovenia part: {outsideBase}.{outsideVatText} VAT {vatRate}%: {vatAmount}. Total: {totalAmount}.' => 'INVOICE_TEXT_EN',
    ' Outside VAT {outsideVatRate}%: {outsideVatAmount}.' => 'INVOICE_TEXT_EN_OUTSIDE_VAT',
    'Mednarodni prevoz potnikov {pickup} → {dropoff}.\nSkupna pot: {totalKm}. Ocenjen del poti po Sloveniji: {siKm}. Del poti izven Slovenije: {outsideKm}.\nDDV je obračunan samo od dela poti, opravljenega na ozemlju Slovenije, sorazmerno glede na kilometre v Sloveniji.\nDavčna osnova Slovenija: {taxableBase}. Del izven Slovenije: {outsideBase}.{outsideVatText} DDV {vatRate}%: {vatAmount}. Skupaj: {totalAmount}.' => 'INVOICE_TEXT_SL',
    ' DDV izven: {outsideVatRate}%: {outsideVatAmount}.' => 'INVOICE_TEXT_SL_OUTSIDE_VAT',
    'Passenger transfer {pickup} → {dropoff}.\nTotal route: {totalKm}. VAT {vatRate}%: {vatAmount}. Total: {totalAmount}.' => 'INVOICE_TEXT_EN_DOMESTIC',
    'Prevoz potnikov {pickup} → {dropoff}.\nSkupna pot: {totalKm}. DDV {vatRate}%: {vatAmount}. Skupaj: {totalAmount}.' => 'INVOICE_TEXT_SL_DOMESTIC',
];
$calculatorTranslations = [];
foreach ($calculatorTextKeys as $sourceText => $keySuffix) {
    $constant = 'MOD_ROUTE_CALCULATION_HELP_FOR_ACCOUNTING_' . $keySuffix;
    $translation = Text::_($constant);
    $calculatorTranslations[$sourceText] = $translation;
    $calculatorTranslations[$constant] = $translation;
}
$normalizeOptionArray = static function ($value): array {
    if (is_string($value)) {
        return (array) (json_decode($value, true) ?: []);
    }
    if (is_object($value)) {
        return method_exists($value, 'toArray') ? (array) $value->toArray() : get_object_vars($value);
    }
    return (array) $value;
};
$countryConfig = [];
foreach ($normalizeOptionArray($params->get('countries', [])) as $countryRow) {
    $countryRow = $normalizeOptionArray($countryRow);
    if (isset($countryRow['country'])) {
        $countryRow = $normalizeOptionArray($countryRow['country']);
    }
    $countryCode = strtoupper(trim((string) ($countryRow['country_code'] ?? '')));
    if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        continue;
    }
    $countryConfig[$countryCode] = [
        'name' => trim((string) ($countryRow['country_name'] ?? $countryCode)),
        'vatNumber' => trim((string) ($countryRow['vat_number'] ?? '')),
        'vatRate' => $countryRow['vat_rate'] ?? '',
        'pdfNote' => trim((string) ($countryRow['pdf_note'] ?? '')),
    ];
}
$minimaxCountryAccounts = [];
foreach ($normalizeOptionArray($params->get('minimax_country_accounts', [])) as $accountRow) {
    $accountRow = $normalizeOptionArray($accountRow);
    if (isset($accountRow['minimax_account'])) {
        $accountRow = $normalizeOptionArray($accountRow['minimax_account']);
    }
    $countryCode = strtoupper(trim((string) ($accountRow['country_code'] ?? '')));
    if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        continue;
    }
    $minimaxCountryAccounts[$countryCode] = [
        'revenueAccount' => trim((string) ($accountRow['revenue_account'] ?? '')),
        'vatAccount' => trim((string) ($accountRow['vat_account'] ?? '')),
    ];
}

$frontendConfig = [
    'googleMapsApiKey' => (string) $params->get('google_maps_api_key', ''),
    'baseCountry' => (string) $params->get('base_country', 'SI'),
    'company' => [
        'name' => (string) $params->get('company_name', 'Example Transfer Ltd.'),
        'address' => (string) $params->get('company_address', 'Example Street 1'),
        'postcodeCity' => (string) $params->get('company_postcode_city', '1000 Example City'),
        'taxNumber' => (string) $params->get('company_tax_number', 'EX12345678'),
        'registrationNumber' => (string) $params->get('company_registration_number', '1234567890'),
        'iban' => (string) $params->get('company_iban', 'EX001234567890123456'),
        'email' => (string) $params->get('company_email', 'office@example.com'),
        'phone' => (string) $params->get('company_phone', '+386 00 000 000'),
        'issueCity' => (string) $params->get('company_issue_city', 'Example City'),
        'logoImageUrl' => $resolvePdfImageUrl($params->get('pdf_logo_image_url', '')),
        'footerText' => (string) $params->get('pdf_footer_text', ''),
        'signatureImageUrl' => $resolvePdfImageUrl($params->get('pdf_signature_image_url', 'podpis-transparent.png')),
    ],
    'minimax' => [
        'receivableAccount' => (string) $params->get('minimax_receivable_account', ''),
        'baseCountryStandardVatAccount' => (string) $params->get('minimax_base_country_standard_vat_account', ''),
        'defaultForeignRevenueAccount' => (string) $params->get('minimax_default_foreign_revenue_account', ''),
        'countryAccounts' => $minimaxCountryAccounts,
    ],
    'defaultForeignPassengerVatRate' => $params->get('default_foreign_passenger_vat_rate', ''),
    'countries' => $countryConfig,
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$frameConfig = [
    'ajaxUrl' => $ajaxUrl,
    'administratorDocumentsUrl' => $administratorDocumentsUrl,
    'tokenName' => $tokenName,
    'tokenValue' => $tokenValue,
    'languageTag' => $languageTag,
    'config' => $frontendConfig,
    'translations' => $calculatorTranslations,
];
$frameName = json_encode($frameConfig, $jsonFlags);
$frameId = 'route_calculation_help_for_accounting_frame_' . (int) $module->id;
?>
<iframe
  id="<?php echo htmlspecialchars($frameId, ENT_QUOTES, 'UTF-8'); ?>"
  class="route_calculation_help_for_accounting-frame"
  title="<?php echo htmlspecialchars(Text::_('MOD_ROUTE_CALCULATION_HELP_FOR_ACCOUNTING'), ENT_QUOTES, 'UTF-8'); ?>"
  src="<?php echo htmlspecialchars($calculatorUrl, ENT_QUOTES, 'UTF-8'); ?>"
  name="<?php echo htmlspecialchars($frameName, ENT_QUOTES, 'UTF-8'); ?>"
  scrolling="no"
  style="width: 100%; min-height: 100vh; border: 0; overflow: hidden;"
  loading="lazy"></iframe>
<script>
(function () {
  var frame = document.getElementById(<?php echo json_encode($frameId, $jsonFlags); ?>);
  if (!frame) return;

  window.addEventListener('message', function (event) {
    if (event.source !== frame.contentWindow) return;
    var data = event.data || {};
    if (data.type === 'routeCalculationHelpForAccountingScrollToTop') {
      var frameTop = frame.getBoundingClientRect().top + window.pageYOffset;
      window.scrollTo({ top: Math.max(0, frameTop), behavior: 'smooth' });
      return;
    }
    if (data.type !== 'routeCalculationHelpForAccountingHeight') return;
    var height = Number(data.height);
    if (!height || height < 600) return;
    frame.style.height = Math.ceil(height) + 'px';
  });
})();
</script>
