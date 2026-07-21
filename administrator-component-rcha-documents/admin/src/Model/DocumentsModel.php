<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

namespace Topoweryou\Component\RchaDocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
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
            'paid_amount',
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
        $paymentStatus = $application->getUserStateFromRequest($context . '.filter.payment_status', 'filter_payment_status', '', 'cmd');
        $limit = $application->getUserStateFromRequest($context . '.list.limit', 'limit', 50, 'uint');

        $this->setState('filter.search', trim($search));
        $this->setState('filter.document_type', in_array($type, ['invoice', 'proforma'], true) ? $type : '');
        $this->setState('filter.payment_status', in_array($paymentStatus, ['unpaid', 'partially_paid', 'paid'], true) ? $paymentStatus : '');
        $this->setState('list.limit', in_array((int) $limit, [25, 50, 100], true) ? (int) $limit : 50);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $paidExpression = '(SELECT COALESCE(SUM(' . $db->quoteName('p.amount') . '), 0) FROM '
            . $db->quoteName('#__route_calculation_help_for_accounting_invoice_payments', 'p')
            . ' WHERE ' . $db->quoteName('p.invoice_id') . ' = ' . $db->quoteName('a.id') . ')';
        $query = $db->getQuery(true)
            ->select($db->quoteName([
                'a.id', 'a.invoice_number', 'a.document_type', 'a.document_status',
                'a.customer_code', 'a.customer_name', 'a.customer_address', 'a.total_amount',
                'a.converted_invoice_id', 'a.converted_invoice_number', 'a.payload_json', 'a.created_at',
            ]))
            ->select($paidExpression . ' AS ' . $db->quoteName('paid_amount'))
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

        $paymentStatus = (string) $this->getState('filter.payment_status');
        if ($paymentStatus !== '') {
            $query->where($db->quoteName('a.document_type') . ' = ' . $db->quote('invoice'));
            if ($paymentStatus === 'unpaid') {
                $query->where($paidExpression . ' <= 0.004');
            } elseif ($paymentStatus === 'partially_paid') {
                $query->where($paidExpression . ' > 0.004')
                    ->where($paidExpression . ' < (' . $db->quoteName('a.total_amount') . ' - 0.004)');
            } elseif ($paymentStatus === 'paid') {
                $query->where($paidExpression . ' >= (' . $db->quoteName('a.total_amount') . ' - 0.004)');
            }
        }

        $ordering = $this->state->get('list.ordering', 'a.created_at');
        $direction = strtoupper((string) $this->state->get('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $query->order($db->escape($ordering) . ' ' . $direction);

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();
        if (!$items) {
            return $items;
        }

        $ids = array_map(static fn ($item) => (int) $item->id, $items);
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'invoice_id', 'payment_date', 'amount', 'payment_method', 'payment_reference', 'note', 'created_at']))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoice_payments'))
            ->where($db->quoteName('invoice_id') . ' IN (' . implode(',', $ids) . ')')
            ->order($db->quoteName('payment_date') . ' ASC')
            ->order($db->quoteName('id') . ' ASC');
        $paymentsByInvoice = [];
        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $payment) {
            $paymentsByInvoice[(int) $payment->invoice_id][] = $payment;
        }

        foreach ($items as $item) {
            $item->payments = $paymentsByInvoice[(int) $item->id] ?? [];
            $item->paid_amount = min(round((float) $item->total_amount, 2), round((float) $item->paid_amount, 2));
            $item->remaining_amount = max(0, round((float) $item->total_amount - $item->paid_amount, 2));
            $item->payment_status = $item->paid_amount <= 0.004
                ? 'unpaid'
                : ($item->remaining_amount <= 0.004 ? 'paid' : 'partially_paid');
            $payload = json_decode((string) ($item->payload_json ?? ''), true);
            $payload = is_array($payload) ? $payload : [];
            $customer = is_array($payload['customer'] ?? null) ? $payload['customer'] : [];
            $item->due_date = (string) ($payload['due_date'] ?? '');
            $item->customer_postcode = (string) ($customer['customer_postcode'] ?? '');
            $item->customer_city = (string) ($customer['customer_city'] ?? '');
        }

        return $items;
    }

    public function getCompanyDetails(): array
    {
        $params = ComponentHelper::getParams('com_rcha_documents');
        $signatureImageUrl = trim((string) $params->get('pdf_signature_image_url', 'podpis-transparent.png'));
        if ($signatureImageUrl !== '' && !preg_match('#^(?:https?:)?//#i', $signatureImageUrl) && !str_starts_with($signatureImageUrl, 'data:')) {
            if (defined('JPATH_ROOT') && str_starts_with($signatureImageUrl, rtrim(JPATH_ROOT, '/') . '/')) {
                $signatureImageUrl = substr($signatureImageUrl, strlen(rtrim(JPATH_ROOT, '/')) + 1);
            }
            if (!str_starts_with($signatureImageUrl, '/')) {
                $siteRoot = rtrim(Uri::root(true), '/');
                $signatureImageUrl = preg_match('#^(?:images|media|modules)/#i', $signatureImageUrl)
                    ? $siteRoot . '/' . ltrim($signatureImageUrl, '/')
                    : $siteRoot . '/modules/mod_route_calculation_help_for_accounting/media/' . ltrim($signatureImageUrl, '/');
            }
        }

        return [
            'name' => (string) $params->get('company_name', ''),
            'address' => (string) $params->get('company_address', ''),
            'postcode_city' => (string) $params->get('company_postcode_city', ''),
            'tax_number' => (string) $params->get('company_tax_number', ''),
            'iban' => (string) $params->get('company_iban', ''),
            'email' => (string) $params->get('company_email', ''),
            'phone' => (string) $params->get('company_phone', ''),
            'footer' => (string) $params->get('pdf_footer_text', ''),
            'signature_image_url' => $signatureImageUrl,
        ];
    }

    public function recordPayment(int $invoiceId, string $paymentDate, float $amount, string $method, string $reference, string $note): int
    {
        if ($invoiceId < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) {
            throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_PAYMENT_INVALID'));
        }
        [$year, $month, $day] = array_map('intval', explode('-', $paymentDate));
        if (!checkdate($month, $day, $year)) {
            throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_PAYMENT_INVALID'));
        }
        $methods = ['bank_transfer', 'cash', 'card', 'other'];
        $method = in_array($method, $methods, true) ? $method : 'other';
        $reference = mb_substr(trim($reference), 0, 255);
        $note = mb_substr(trim($note), 0, 2000);
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_PAYMENT_AMOUNT_INVALID'));
        }

        $db = $this->getDatabase();
        $db->transactionStart();
        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'document_type', 'total_amount']))
                ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
                ->where($db->quoteName('id') . ' = ' . $invoiceId);
            $invoice = $db->setQuery((string) $query . ' FOR UPDATE')->loadAssoc();
            if (!$invoice || ($invoice['document_type'] ?? '') !== 'invoice') {
                throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_PAYMENT_INVOICE_ONLY'));
            }

            $query = $db->getQuery(true)
                ->select('COALESCE(SUM(' . $db->quoteName('amount') . '), 0)')
                ->from($db->quoteName('#__route_calculation_help_for_accounting_invoice_payments'))
                ->where($db->quoteName('invoice_id') . ' = ' . $invoiceId);
            $paid = round((float) $db->setQuery($query)->loadResult(), 2);
            $remaining = max(0, round((float) $invoice['total_amount'] - $paid, 2));
            if ($remaining <= 0.004) {
                throw new RuntimeException(Text::_('COM_RCHA_DOCUMENTS_PAYMENT_ALREADY_PAID'));
            }
            if ($amount > $remaining + 0.004) {
                throw new RuntimeException(Text::sprintf('COM_RCHA_DOCUMENTS_PAYMENT_EXCEEDS_REMAINING', number_format($remaining, 2, ',', '.')));
            }
            if (abs($amount - $remaining) <= 0.004) {
                $amount = $remaining;
            }

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__route_calculation_help_for_accounting_invoice_payments'))
                ->columns($db->quoteName(['invoice_id', 'payment_date', 'amount', 'payment_method', 'payment_reference', 'note', 'created_by', 'created_at']))
                ->values(implode(',', [
                    $invoiceId,
                    $db->quote($paymentDate),
                    $db->quote(number_format($amount, 2, '.', '')),
                    $db->quote($method),
                    $db->quote($reference),
                    $db->quote($note),
                    (int) Factory::getApplication()->getIdentity()->id,
                    $db->quote(Factory::getDate()->toSql()),
                ]));
            $db->setQuery($query)->execute();
            $paymentId = (int) $db->insertid();
            $db->transactionCommit();
        } catch (Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }

        return $paymentId;
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
                ->delete($db->quoteName('#__route_calculation_help_for_accounting_invoice_payments'))
                ->where($db->quoteName('invoice_id') . ' IN (' . $idList . ')');
            $db->setQuery($query)->execute();

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
