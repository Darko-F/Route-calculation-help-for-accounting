<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.routecalculationupdatekey
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 * Adds the shared subscriber key to Route Calculation Help update downloads.
 */
class PlgInstallerRoutecalculationupdatekey extends CMSPlugin
{
    protected $autoloadLanguage = true;

    /**
     * Appends the configured key before Joomla downloads the update package.
     *
     * @param   mixed  $url      Package download URL or Joomla event.
     * @param   array  $headers  Request headers.
     *
     * @return  boolean
     */
    public function onInstallerBeforePackageDownload(&$url, &$headers = [])
    {
        $event = is_object($url) && method_exists($url, 'getUrl') ? $url : null;

        if ($event !== null) {
            $url = $event->getUrl();
        }

        $componentParams = ComponentHelper::getParams('com_rcha_documents');
        $key = trim((string) $componentParams->get('download_key', ''));

        // Keep existing installations working until the old plugin key is
        // manually copied into the component's shared Options.
        if ($key === '') {
            $key = trim((string) $this->params->get('download_key', ''));
        }

        if ($key === '' || !is_string($url) || $url === '') {
            return true;
        }

        $uri = new Uri($url);
        $host = strtolower((string) $uri->getHost());
        $file = (string) $uri->getVar('file', '');
        $isLicenseEndpoint = $uri->getVar('option') === 'com_vmupdatekeymanager'
            && $uri->getVar('task') === 'download.get';

        if ($host !== 'shop.topoweryou.com' || !$isLicenseEndpoint) {
            return true;
        }

        if (!preg_match('/^pkg_route_calculation_help_for_accounting_v\d+\.\d+\.\d+\.zip$/', $file)) {
            return true;
        }

        if ((string) $uri->getVar('key', '') === '' && (string) $uri->getVar('dlid', '') === '') {
            $uri->setVar('key', $key);
            $url = (string) $uri;

            if ($event !== null && method_exists($event, 'updateUrl')) {
                $event->updateUrl($url);
            }
        }

        return true;
    }
}
