<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_transport_accounting
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\TransportAccounting\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class DocumentmanagerField extends FormField
{
    protected $type = 'Documentmanager';

    protected function getInput()
    {
        $application = Factory::getApplication();
        $identity = $application->getIdentity();
        if (!ComponentHelper::isEnabled('com_transport_accounting')) {
            return '<div class="alert alert-info">' . htmlspecialchars(Text::_('MOD_TRANSPORT_ACCOUNTING_ADMIN_COMPONENT_REQUIRED'), ENT_QUOTES, 'UTF-8') . '</div>';
        }

        if (!$application->isClient('administrator') || !$identity || !$identity->authorise('core.manage', 'com_transport_accounting')) {
            return '<div class="alert alert-warning">' . htmlspecialchars(Text::_('JERROR_ALERTNOAUTHOR'), ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $url = Route::_('index.php?option=com_transport_accounting&view=documents');

        return '<input type="hidden" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . '" value="" />'
            . '<p class="text-muted">' . htmlspecialchars(Text::_('MOD_TRANSPORT_ACCOUNTING_ADMIN_COMPONENT_DESC'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<a class="btn btn-primary" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(Text::_('MOD_TRANSPORT_ACCOUNTING_ADMIN_OPEN_COMPONENT'), ENT_QUOTES, 'UTF-8')
            . '</a>';
    }
}
