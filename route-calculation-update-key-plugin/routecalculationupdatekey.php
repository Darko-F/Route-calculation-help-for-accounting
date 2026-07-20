<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.routecalculationupdatekey
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 * Adds the subscriber download key to Route Calculation Help update downloads.
 */
class PlgInstallerRoutecalculationupdatekey extends CMSPlugin
{
    /**
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Appends the configured key before Joomla downloads the update package.
     *
     * @param   string  $url      Package download URL.
     * @param   array   $headers  Request headers.
     *
     * @return  boolean
     */
    public function onInstallerBeforePackageDownload(&$url, &$headers = [])
    {
        $event = is_object($url) && method_exists($url, 'getUrl') ? $url : null;

        if ($event !== null) {
            $url = $event->getUrl();
        }

        $key = trim((string) $this->params->get('download_key', ''));

        if ($key === '' || !is_string($url) || $url === '') {
            return true;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        if ($host !== 'builder.topoweryou.com' || strpos($path, '/routecalculationhelp/files/routecalculationhelp/downloads/download.php') === false) {
            return true;
        }

        $uri = new Uri($url);
        $file = (string) $uri->getVar('file', '');

        $allowedPackagePattern = '/^pkg_route_calculation_help_for_accounting_v\d+\.\d+\.\d+\.zip$/';

        if (!preg_match($allowedPackagePattern, $file)) {
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
