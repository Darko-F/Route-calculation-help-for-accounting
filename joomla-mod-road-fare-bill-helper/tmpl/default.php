<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_road_fare_bill_helper
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

$document = Factory::getApplication()->getDocument();
$calculatorUrl = Uri::root(true) . '/modules/mod_road_fare_bill_helper/media/calculator.html?v=1.2.16';
$ajaxUrl = Route::_('index.php?option=com_ajax&module=road_fare_bill_helper&format=json', false);
$tokenName = Session::getFormToken();
$tokenValue = '1';
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
    'config' => $frontendConfig,
];
$frameName = json_encode($frameConfig, $jsonFlags);
$frameId = 'road_fare_bill_helper_frame_' . (int) $module->id;
?>
<iframe
  id="<?php echo htmlspecialchars($frameId, ENT_QUOTES, 'UTF-8'); ?>"
  class="road_fare_bill_helper-frame"
  title="<?php echo htmlspecialchars(Text::_('MOD_ROAD_FARE_BILL_HELPER'), ENT_QUOTES, 'UTF-8'); ?>"
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
    if (data.type !== 'roadFareBillHelperHeight') return;
    var height = Number(data.height);
    if (!height || height < 600) return;
    frame.style.height = Math.ceil(height) + 'px';
  });
})();
</script>
