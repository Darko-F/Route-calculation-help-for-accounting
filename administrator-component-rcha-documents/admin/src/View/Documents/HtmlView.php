<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

namespace Topoweryou\Component\RchaDocuments\Administrator\View\Documents;

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

    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->company = $this->get('CompanyDetails');

        if ($errors = $this->get('Errors')) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_RCHA_DOCUMENTS_TITLE'), 'file-2');
        if (Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_rcha_documents')) {
            ToolbarHelper::deleteList('COM_RCHA_DOCUMENTS_DELETE_CONFIRM', 'documents.delete');
        }
        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_rcha_documents')) {
            ToolbarHelper::preferences('com_rcha_documents');
        }

        parent::display($tpl);
    }
}
