<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

namespace Topoweryou\Component\RchaDocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use RuntimeException;
use Throwable;

class DocumentsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] = $config['filter_fields'] ?? [
            'invoice_number', 'a.invoice_number',
            'document_type', 'a.document_type',
            'customer_name', 'a.customer_name',
            'total_amount', 'a.total_amount',
            'document_status', 'a.document_status',
            'created_at', 'a.created_at',
        ];

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.created_at', $direction = 'DESC'): void
    {
        parent::populateState($ordering, $direction);

        $application = Factory::getApplication();
        $context = $this->context;
        $search = $application->getUserStateFromRequest($context . '.filter.search', 'filter_search', '', 'string');
        $type = $application->getUserStateFromRequest($context . '.filter.document_type', 'filter_document_type', '', 'cmd');
        $limit = $application->getUserStateFromRequest($context . '.list.limit', 'limit', 50, 'uint');

        $this->setState('filter.search', trim($search));
        $this->setState('filter.document_type', in_array($type, ['invoice', 'proforma'], true) ? $type : '');
        $this->setState('list.limit', in_array((int) $limit, [25, 50, 100], true) ? (int) $limit : 50);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName([
                'a.id', 'a.invoice_number', 'a.document_type', 'a.document_status',
                'a.customer_code', 'a.customer_name', 'a.total_amount',
                'a.converted_invoice_id', 'a.converted_invoice_number', 'a.created_at',
            ]))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices', 'a'));

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $search = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(' . implode(' OR ', [
                $db->quoteName('a.invoice_number') . ' LIKE ' . $search,
                $db->quoteName('a.customer_name') . ' LIKE ' . $search,
                $db->quoteName('a.customer_code') . ' LIKE ' . $search,
                $db->quoteName('a.converted_invoice_number') . ' LIKE ' . $search,
            ]) . ')');
        }

        $type = (string) $this->getState('filter.document_type');
        if (in_array($type, ['invoice', 'proforma'], true)) {
            $query->where($db->quoteName('a.document_type') . ' = ' . $db->quote($type));
        }

        $ordering = $this->state->get('list.ordering', 'a.created_at');
        $direction = strtoupper((string) $this->state->get('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $query->order($db->escape($ordering) . ' ' . $direction);

        return $query;
    }

    public function deleteDocuments(array $ids): int
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), static fn ($id) => $id > 0));
        if (!$ids) {
            throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_NO_SELECTION'));
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $idList = implode(',', $ids);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'invoice_number', 'document_type', 'source_document_id', 'converted_invoice_id']))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
            ->where($db->quoteName('id') . ' IN (' . $idList . ')');
        $documents = $db->setQuery($query)->loadAssocList() ?: [];

        if (count($documents) !== count($ids)) {
            throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_NOT_FOUND'));
        }

        $selected = array_fill_keys($ids, true);
        foreach ($documents as $document) {
            $convertedId = (int) ($document['converted_invoice_id'] ?? 0);
            if (($document['document_type'] ?? '') === 'proforma' && $convertedId > 0 && !isset($selected[$convertedId])) {
                throw new RuntimeException(Text::sprintf('COM_RCHA_DOCUMENTS_DELETE_CONVERTED_ERROR', (string) $document['invoice_number']));
            }
        }

        $sourceIds = [];
        foreach ($documents as $document) {
            if ((int) ($document['source_document_id'] ?? 0) > 0) {
                $sourceIds[] = (int) $document['source_document_id'];
            }
        }

        $db->transactionStart();
        try {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
                ->where($db->quoteName('id') . ' IN (' . $idList . ')');
            $db->setQuery($query)->execute();
            $deleted = (int) $db->getAffectedRows();

            if ($sourceIds) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
                    ->set($db->quoteName('document_status') . ' = ' . $db->quote('open'))
                    ->set($db->quoteName('converted_invoice_id') . ' = NULL')
                    ->set($db->quoteName('converted_invoice_number') . ' = ' . $db->quote(''))
                    ->where($db->quoteName('id') . ' IN (' . implode(',', array_unique($sourceIds)) . ')');
                $db->setQuery($query)->execute();
            }

            $db->transactionCommit();
        } catch (Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }

        return $deleted;
    }
}
