<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_transport_accounting
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Topoweryou\Component\TransportAccounting\Administrator\View\Documents;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $company;
    public $minimax;

    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->company = $this->get('CompanyDetails');
        $this->minimax = $this->get('MinimaxSettings');

        if ($errors = $this->get('Errors')) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_TRANSPORT_ACCOUNTING_TITLE'), 'file-2');
        if (Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_transport_accounting')) {
            ToolbarHelper::deleteList('COM_TRANSPORT_ACCOUNTING_DELETE_CONFIRM', 'documents.delete');
        }
        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_transport_accounting')) {
            ToolbarHelper::preferences('com_transport_accounting');
        }

        parent::display($tpl);
    }
}
