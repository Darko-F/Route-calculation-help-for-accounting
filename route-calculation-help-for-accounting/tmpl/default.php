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
$calculatorUrl = Uri::root(true) . '/modules/mod_route_calculation_help_for_accounting/media/calculator.html?v=1.2.25';
$ajaxUrl = Uri::root() . 'index.php?option=com_ajax&module=route_calculation_help_for_accounting&format=json';
$tokenName = Session::getFormToken();
$tokenValue = '1';
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
    'Price with VAT / final customer price (€)' => 'CALCULATOR_GROSS_PRICE',
    'Enter final price charged to the customer. Price without VAT is calculated automatically.' => 'CALCULATOR_GROSS_PRICE_HELP',
    'Calculated price without VAT (€)' => 'CALCULATOR_NET_PRICE',
    'Slovenian VAT rate (%)' => 'CALCULATOR_SLOVENIAN_VAT_RATE',
    'Outside Slovenia country' => 'CALCULATOR_OUTSIDE_COUNTRY',
    'Italy' => 'CALCULATOR_ITALY',
    'Croatia / Zagreb' => 'CALCULATOR_CROATIA_ZAGREB',
    'Austria' => 'CALCULATOR_AUSTRIA',
    'Germany' => 'CALCULATOR_GERMANY',
    'Other / custom' => 'CALCULATOR_OTHER_CUSTOM',
    'Outside Slovenia VAT rate (%)' => 'CALCULATOR_OUTSIDE_VAT_RATE',
    'Country defaults are editable before recalculation.' => 'CALCULATOR_COUNTRY_DEFAULTS_HELP',
    'Outside SifraKonta' => 'CALCULATOR_OUTSIDE_SIFRAKONTA',
    'Revenue account used for the outside-Slovenia XML line.' => 'CALCULATOR_OUTSIDE_SIFRAKONTA_HELP',
    'Custom country name' => 'CALCULATOR_CUSTOM_COUNTRY_NAME',
    'Country split method' => 'CALCULATOR_SPLIT_METHOD',
    'Fast: embedded Slovenia border polygon' => 'CALCULATOR_SPLIT_FAST',
    'Slower: Google reverse geocoding' => 'CALCULATOR_SPLIT_GEOCODE',
    'Manual Slovenia km only' => 'CALCULATOR_SPLIT_MANUAL',
    'Fast method is instant. Manual override is still possible.' => 'CALCULATOR_SPLIT_HELP',
    'Sampling accuracy' => 'CALCULATOR_SAMPLING_ACCURACY',
    'Fast / rough: every 10 km' => 'CALCULATOR_SAMPLE_10',
    'Normal: every 5 km' => 'CALCULATOR_SAMPLE_5',
    'Accurate and fast: every 2 km' => 'CALCULATOR_SAMPLE_2',
    'Very accurate: every 1 km' => 'CALCULATOR_SAMPLE_1',
    'High: every 500 m' => 'CALCULATOR_SAMPLE_05',
    'Manual Slovenia km override' => 'CALCULATOR_MANUAL_SI_KM',
    'If entered, this value can be used to recalculate the invoice exactly.' => 'CALCULATOR_MANUAL_SI_KM_HELP',
    'Waiting for Google Maps...' => 'CALCULATOR_WAITING_GOOGLE_MAPS',
    'Actions' => 'CALCULATOR_ACTIONS',
    'Calculate route and VAT split' => 'CALCULATOR_CALCULATE_ROUTE',
    'Recalculate with manual Slovenia km' => 'CALCULATOR_RECALCULATE_MANUAL',
    'Map' => 'CALCULATOR_MAP',
    'Driving route' => 'CALCULATOR_DRIVING_ROUTE',
    'Results' => 'CALCULATOR_RESULTS',
    'Total route distance' => 'CALCULATOR_TOTAL_ROUTE_DISTANCE',
    'Estimated km in Slovenia' => 'CALCULATOR_ESTIMATED_SI_KM',
    'Estimated km outside Slovenia' => 'CALCULATOR_ESTIMATED_OUTSIDE_KM',
    'Total price without VAT' => 'CALCULATOR_TOTAL_NET_PRICE',
    'Slovenian taxable base' => 'CALCULATOR_SLOVENIAN_TAXABLE_BASE',
    'Outside Slovenia part' => 'CALCULATOR_OUTSIDE_PART',
    'Slovenian VAT' => 'CALCULATOR_SLOVENIAN_VAT',
    'Outside Slovenia VAT' => 'CALCULATOR_OUTSIDE_VAT',
    'Total invoice amount' => 'CALCULATOR_TOTAL_INVOICE_AMOUNT',
    'Invoice / Customer' => 'CALCULATOR_INVOICE_CUSTOMER',
    'Invoice no' => 'CALCULATOR_INVOICE_NO',
    'Generate' => 'CALCULATOR_GENERATE',
    'Sifra Stranke' => 'CALCULATOR_SIFRA_STRANKE',
    'Service date' => 'CALCULATOR_SERVICE_DATE',
    'Customer name' => 'CALCULATOR_CUSTOMER_NAME',
    'Address, post' => 'CALCULATOR_ADDRESS_POST',
    'ID za DDV kupca: SIxxxx' => 'CALCULATOR_CUSTOMER_VAT_ID',
    'Save details' => 'CALCULATOR_SAVE_DETAILS',
    'Customers' => 'CALCULATOR_CUSTOMERS',
    'Save invoice history' => 'CALCULATOR_SAVE_INVOICE_HISTORY',
    'Load history' => 'CALCULATOR_LOAD_HISTORY',
    'Auto generated invoice text' => 'CALCULATOR_AUTO_INVOICE_TEXT',
    'English' => 'CALCULATOR_ENGLISH',
    'Slovenian' => 'CALCULATOR_SLOVENIAN',
    'Generate PDF' => 'CALCULATOR_GENERATE_PDF',
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
    'Used when Other / custom is selected' => 'CALCULATOR_CUSTOM_COUNTRY_HELP',
    'Optional, e.g. 94.5' => 'CALCULATOR_OPTIONAL_KM',
    'Invoice no (e.g. 26-0007)' => 'CALCULATOR_PLACEHOLDER_INVOICE_OLD',
    'Invoice no (e.g. RCHA-2026-001)' => 'CALCULATOR_PLACEHOLDER_INVOICE',
    'Customer code (Sifra)' => 'CALCULATOR_PLACEHOLDER_CUSTOMER_CODE',
    'Generated file name (e.g. racun_2026_2026-06-18)' => 'CALCULATOR_PLACEHOLDER_FILE_NAME',
    'Custom invoice message (optional)' => 'CALCULATOR_PLACEHOLDER_CUSTOM_MESSAGE',
    'Add stop or place' => 'CALCULATOR_ADD_STOP_OR_PLACE',
    'Remove' => 'CALCULATOR_REMOVE',
    'Description (e.g. parking)' => 'CALCULATOR_PLACEHOLDER_ADJUSTMENT_DESC',
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
    'Enter a valid gross price.' => 'CALCULATOR_VALID_GROSS_PRICE',
    'Calculating route...' => 'CALCULATOR_CALCULATING_ROUTE',
    'Route error: no route found.' => 'CALCULATOR_ROUTE_NO_ROUTE',
    'Domestic Slovenia route detected. Outside Slovenia set to 0 km.' => 'CALCULATOR_DOMESTIC_ROUTE',
    'Route calculated. Enter manual Slovenia km and click “Recalculate with manual Slovenia km”.' => 'CALCULATOR_ROUTE_MANUAL_PROMPT',
    'Manual Slovenia km used.' => 'CALCULATOR_MANUAL_KM_USED',
    'Done. Fast Slovenia border polygon split used.' => 'CALCULATOR_FAST_SPLIT_DONE',
    'Using slower Google reverse geocoding method...' => 'CALCULATOR_GEOCODING_SPLIT_STARTED',
    'Done. Google reverse geocoding split used.' => 'CALCULATOR_GEOCODING_SPLIT_DONE',
    'Route calculated, but Google Geocoding split failed: {error}. Use fast method or enter manual Slovenia km.' => 'CALCULATOR_GEOCODING_SPLIT_FAILED',
    'Route error: {error}. Check that Routes API is enabled for this Google key.' => 'CALCULATOR_ROUTE_ERROR_CHECK_KEY',
    'Calculate the route first, then enter manual Slovenia km.' => 'CALCULATOR_CALCULATE_FIRST_MANUAL',
    'Enter a valid Slovenia km number.' => 'CALCULATOR_VALID_SI_KM',
    'VAT rate changed. Totals recalculated.' => 'CALCULATOR_VAT_RATE_CHANGED',
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
    'Delete this saved customer? Invoice history will stay saved.' => 'CALCULATOR_DELETE_CUSTOMER_CONFIRM',
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
    'Filter by invoice / order number' => 'CALCULATOR_FILTER_INVOICE',
    'Invoice Name' => 'CALCULATOR_INVOICE_NAME',
    'Price' => 'CALCULATOR_PRICE',
    'Date' => 'CALCULATOR_DATE',
    'No invoice history matches this filter.' => 'CALCULATOR_NO_INVOICE_HISTORY_MATCH',
    'No saved invoice history for this customer. Use "Save invoice history" after calculating a route to store reusable route details.' => 'CALCULATOR_NO_SAVED_INVOICE_HISTORY',
    'Select or save a customer before loading invoice history.' => 'CALCULATOR_SELECT_CUSTOMER_HISTORY',
    'Loading invoice history...' => 'CALCULATOR_LOADING_INVOICE_HISTORY',
    'Saved invoice payload is invalid.' => 'CALCULATOR_SAVED_PAYLOAD_INVALID',
    'Saved invoice has no calculated route data.' => 'CALCULATOR_SAVED_NO_ROUTE_DATA',
    'Saved invoice restored. PDF and XML can be generated without recalculating the route.' => 'CALCULATOR_SAVED_INVOICE_RESTORED',
    'Calculate a route first, then generate PDF.' => 'CALCULATOR_CALCULATE_BEFORE_PDF',
    'PDF generated.' => 'CALCULATOR_PDF_GENERATED',
    'Calculate a route first, then export Minimax XML.' => 'CALCULATOR_CALCULATE_BEFORE_XML',
    'Enter Slovenia SifraKonta in the module options before exporting Minimax XML.' => 'CALCULATOR_ENTER_SI_ACCOUNT_XML',
    'Enter Outside SifraKonta before exporting Minimax XML.' => 'CALCULATOR_ENTER_OUTSIDE_ACCOUNT_XML',
    'Minimax XML exported.' => 'CALCULATOR_XML_EXPORTED',
    'Enter manually' => 'CALCULATOR_ENTER_MANUALLY',
    'VAT ' => 'CALCULATOR_VAT_PREFIX',
    ' not subject to Slovenian VAT' => 'CALCULATOR_NOT_SUBJECT_SI_VAT',
    'Racun st.: {invoiceNo}' => 'PDF_INVOICE_NO',
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
    'kos' => 'PDF_PIECE',
    'Dodatni strosek' => 'PDF_ADDITIONAL_COST',
    'Skupaj' => 'PDF_TOTAL',
    'Za placilo' => 'PDF_TO_PAY',
    'Davcna stopnja' => 'PDF_TAX_RATE',
    'Osnova za DDV' => 'PDF_TAX_BASE',
    'Znesek z DDV' => 'PDF_AMOUNT_WITH_VAT',
    'Pri placilu se sklicujte na stevilko SI00 {paymentReference}.' => 'PDF_PAYMENT_REFERENCE',
    'Za del poti izven Slovenije je uporabljen DDV {outsideVatRate}%, skladno z izbrano drzavo opravljanja storitve.' => 'PDF_OUTSIDE_VAT_NOTE',
    'Prevoz v tujini je oproscen obracuna DDV na podlagi prvega odstavka 28. clena ZDDV-1.' => 'PDF_FOREIGN_TRANSPORT_VAT_EXEMPT',
    'Zahvaljujemo se vam za vase zaupanje in se veselimo nadaljnjega sodelovanja!' => 'PDF_THANK_YOU',
    'Zig in podpis:' => 'PDF_STAMP_SIGNATURE',
    'International passenger transfer {pickup} → {dropoff}.\nTotal route: {totalKm}. Estimated Slovenia part: {siKm}. Outside Slovenia: {outsideKm}.\nSlovenian VAT is calculated only on the part of the route performed in Slovenia, proportionate to kilometres driven in Slovenia.\nTaxable base Slovenia: {taxableBase}. Outside Slovenia part: {outsideBase}.{outsideVatText} VAT {vatRate}%: {vatAmount}. Total: {totalAmount}.' => 'INVOICE_TEXT_EN',
    ' Outside VAT {outsideVatRate}%: {outsideVatAmount}.' => 'INVOICE_TEXT_EN_OUTSIDE_VAT',
    'Mednarodni prevoz potnikov {pickup} → {dropoff}.\nSkupna pot: {totalKm}. Ocenjen del poti po Sloveniji: {siKm}. Del poti izven Slovenije: {outsideKm}.\nDDV je obračunan samo od dela poti, opravljenega na ozemlju Slovenije, sorazmerno glede na kilometre v Sloveniji.\nDavčna osnova Slovenija: {taxableBase}. Del izven Slovenije: {outsideBase}.{outsideVatText} DDV {vatRate}%: {vatAmount}. Skupaj: {totalAmount}.' => 'INVOICE_TEXT_SL',
    ' DDV izven: {outsideVatRate}%: {outsideVatAmount}.' => 'INVOICE_TEXT_SL_OUTSIDE_VAT',
];
$calculatorTranslations = [];
foreach ($calculatorTextKeys as $sourceText => $keySuffix) {
    $constant = 'MOD_ROUTE_CALCULATION_HELP_FOR_ACCOUNTING_' . $keySuffix;
    $translation = Text::_($constant);
    $calculatorTranslations[$sourceText] = $translation;
    $calculatorTranslations[$constant] = $translation;
}
$frontendConfig = [
    'googleMapsApiKey' => (string) $params->get('google_maps_api_key', ''),
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
    ],
    'revenueAccounts' => [
        'slovenia' => (string) $params->get('revenue_account_slovenia', '7601'),
        'italy' => (string) $params->get('revenue_account_italy', '7602'),
        'croatia' => (string) $params->get('revenue_account_croatia', '7603'),
        'austria' => (string) $params->get('revenue_account_austria', '7604'),
        'germany' => (string) $params->get('revenue_account_germany', '7605'),
    ],
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$frameConfig = [
    'ajaxUrl' => $ajaxUrl,
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
    if (data.type !== 'routeCalculationHelpForAccountingHeight') return;
    var height = Number(data.height);
    if (!height || height < 600) return;
    frame.style.height = Math.ceil(height) + 'px';
  });
})();
</script>
