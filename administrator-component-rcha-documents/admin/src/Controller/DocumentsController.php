<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

namespace Topoweryou\Component\RchaDocuments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Throwable;

class DocumentsController extends AdminController
{
    public function getModel($name = 'Documents', $prefix = 'Administrator', $config = ['ignore_request' => false])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function delete(): void
    {
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $application = $this->app;

        if (!$application->getIdentity()->authorise('core.delete', 'com_rcha_documents')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $ids = (array) $this->input->post->get('cid', [], 'array');
        ArrayHelper::toInteger($ids);
        $ids = array_values(array_filter(array_unique($ids)));

        try {
            if (!$ids) {
                throw new \RuntimeException(Text::_('COM_RCHA_DOCUMENTS_NO_SELECTION'));
            }

            $count = $this->getModel()->deleteDocuments($ids);
            $application->enqueueMessage(Text::plural('COM_RCHA_DOCUMENTS_N_DOCUMENTS_DELETED', $count), 'success');
        } catch (Throwable $exception) {
            $application->enqueueMessage($exception->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_rcha_documents&view=documents', false));
    }

    public function recordPayment(): void
    {
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $application = $this->app;
        if (!$application->getIdentity()->authorise('core.edit', 'com_rcha_documents')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        try {
            $this->getModel()->recordPayment(
                $this->input->post->getInt('invoice_id'),
                $this->input->post->getString('payment_date'),
                $this->input->post->getFloat('amount'),
                $this->input->post->getCmd('payment_method', 'bank_transfer'),
                $this->input->post->getString('payment_reference'),
                $this->input->post->getString('payment_note')
            );
            $application->enqueueMessage(Text::_('COM_RCHA_DOCUMENTS_PAYMENT_RECORDED'), 'success');
        } catch (Throwable $exception) {
            $application->enqueueMessage($exception->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_rcha_documents&view=documents', false));
    }
}
