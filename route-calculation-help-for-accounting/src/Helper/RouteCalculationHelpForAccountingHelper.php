<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_route_calculation_help_for_accounting
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\RouteCalculationHelpForAccounting\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use RuntimeException;
use Throwable;

class RouteCalculationHelpForAccountingHelper
{
    public function saveCustomerAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customer = self::saveCustomer($payload);

        return ['customer' => $customer];
    }

    public function saveInvoiceAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerPayload = is_array($payload['customer'] ?? null) ? $payload['customer'] : [];
        $documentType = ($payload['document_type'] ?? '') === 'proforma' ? 'proforma' : 'invoice';
        $sourceDocumentId = $documentType === 'invoice' ? max(0, (int) ($payload['source_document_id'] ?? 0)) : 0;
        $invoiceNumber = trim((string) ($payload['invoice_number'] ?? ''));
        $automaticInvoiceYear = null;

        if ($invoiceNumber === '') {
            $automaticInvoiceYear = (int) Factory::getDate()->format('Y');
            $invoiceNumber = self::nextInvoiceNumber($automaticInvoiceYear, $documentType);
            $payload['invoice_number'] = $invoiceNumber;
        } elseif (preg_match($documentType === 'proforma' ? '/^PR-RCHA-(\d{2})-\d+$/' : '/^RCHA-(\d{2})-\d+(?:[-\s].+)?$/', $invoiceNumber, $matches)) {
            $currentYear = (int) Factory::getDate()->format('Y');
            $automaticInvoiceYear = ((int) floor($currentYear / 100) * 100) + (int) $matches[1];
        }

        if (mb_strlen($invoiceNumber) > 30) {
            throw new RuntimeException('Invoice number must be 30 characters or fewer for Minimax XML.');
        }

        self::ensureInvoiceCustomerColumns();

        if ($sourceDocumentId > 0) {
            $db = self::db();
            $sourceQuery = $db->getQuery(true)
                ->select($db->quoteName(['id', 'document_type', 'converted_invoice_id']))
                ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
                ->where($db->quoteName('id') . ' = ' . $sourceDocumentId);
            $sourceDocument = $db->setQuery($sourceQuery)->loadAssoc();
            if (!$sourceDocument || ($sourceDocument['document_type'] ?? '') !== 'proforma') {
                throw new RuntimeException('The selected predračun was not found.');
            }
            if ((int) ($sourceDocument['converted_invoice_id'] ?? 0) > 0) {
                throw new RuntimeException('This predračun has already been converted to an invoice.');
            }
        }

        if (self::invoiceExists($invoiceNumber)) {
            if ($automaticInvoiceYear !== null) {
                $invoiceNumber = self::nextInvoiceNumber($automaticInvoiceYear, $documentType);
                $payload['invoice_number'] = $invoiceNumber;
            } else {
                throw new RuntimeException('Invoice number "' . $invoiceNumber . '" already exists in invoice history. Use a different invoice number to save a new record.');
            }
        }

        $customer = self::saveCustomer($customerPayload);
        $data = is_array($payload['calculated_data'] ?? null) ? $payload['calculated_data'] : [];
        $db = self::db();
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $columns = [
            'customer_id', 'customer_code', 'customer_name', 'customer_address', 'vat_id',
            'invoice_number', 'document_type', 'document_status', 'source_document_id',
            'output_file_name', 'pickup', 'dropoff',
            'total_km', 'slovenia_km', 'outside_slovenia_km', 'taxable_base_slovenia',
            'outside_slovenia_base', 'vat_rate', 'vat_amount', 'outside_vat_rate',
            'outside_vat_amount', 'total_amount', 'payload_json', 'created_by', 'created_at'
        ];
        $attempt = 0;

        while (true) {
            $attempt++;
            $payload['invoice_number'] = $invoiceNumber;
            $payload['document_type'] = $documentType;
            $payload['source_document_id'] = $sourceDocumentId ?: null;
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $values = [
                (int) $customer['id'],
                $db->quote($customer['customer_code']),
                $db->quote((string) ($customerPayload['customer_name'] ?? '')),
                $db->quote((string) ($customerPayload['customer_address'] ?? '')),
                $db->quote((string) ($customerPayload['vat_id'] ?? '')),
                $db->quote($invoiceNumber),
                $db->quote($documentType),
                $db->quote($documentType === 'proforma' ? 'open' : 'issued'),
                $sourceDocumentId > 0 ? $sourceDocumentId : 'NULL',
                $db->quote((string) ($payload['output_file_name'] ?? '')),
                $db->quote((string) ($data['pickup'] ?? '')),
                $db->quote((string) ($data['dropoff'] ?? '')),
                self::num($data['totalKm'] ?? 0),
                self::num($data['sloveniaKm'] ?? 0),
                self::num($data['outsideSloveniaKm'] ?? 0),
                self::num($data['taxableBaseSlovenia'] ?? 0),
                self::num($data['outsideSloveniaBase'] ?? 0),
                self::num($data['vatRate'] ?? 0),
                self::num($data['vatAmount'] ?? 0),
                self::num($data['outsideVatRate'] ?? 0),
                self::num($data['outsideVatAmount'] ?? 0),
                self::num(self::invoiceTotal($data)),
                $db->quote($payloadJson),
                $userId,
                $db->quote($now),
            ];
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            try {
                $db->setQuery($query)->execute();
                break;
            } catch (Throwable $exception) {
                if ($automaticInvoiceYear === null || $attempt >= 5 || !self::isDuplicateKeyException($exception)) {
                    throw $exception;
                }

                $invoiceNumber = self::nextInvoiceNumber($automaticInvoiceYear, $documentType);
            }
        }

        $invoiceId = (int) $db->insertid();
        if ($sourceDocumentId > 0) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
                ->set($db->quoteName('document_status') . ' = ' . $db->quote('converted'))
                ->set($db->quoteName('converted_invoice_id') . ' = ' . $invoiceId)
                ->set($db->quoteName('converted_invoice_number') . ' = ' . $db->quote($invoiceNumber))
                ->where($db->quoteName('id') . ' = ' . $sourceDocumentId)
                ->where($db->quoteName('converted_invoice_id') . ' IS NULL');
            $db->setQuery($query)->execute();
        }

        return [
            'invoice_id' => $invoiceId,
            'invoice' => [
                'customer_code' => $customer['customer_code'],
                'customer_name' => (string) ($customerPayload['customer_name'] ?? ''),
                'customer_address' => (string) ($customerPayload['customer_address'] ?? ''),
                'vat_id' => (string) ($customerPayload['vat_id'] ?? ''),
                'invoice_number' => $invoiceNumber,
                'document_type' => $documentType,
                'document_status' => $documentType === 'proforma' ? 'open' : 'issued',
                'source_document_id' => $sourceDocumentId ?: null,
                'converted_invoice_id' => null,
                'converted_invoice_number' => '',
                'total_amount' => self::num(self::invoiceTotal($data)),
                'payload_json' => $payloadJson,
                'created_at' => $now,
            ],
        ];
    }

    public function nextInvoiceNumberAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $year = (int) ($payload['year'] ?? Factory::getDate()->format('Y'));
        $documentType = ($payload['document_type'] ?? '') === 'proforma' ? 'proforma' : 'invoice';

        if ($year < 2000 || $year > 9999) {
            $year = (int) Factory::getDate()->format('Y');
        }

        $invoiceNumber = self::nextInvoiceNumber($year, $documentType);

        return ['invoice_number' => $invoiceNumber, 'year' => $year, 'document_type' => $documentType];
    }

    public function addDraftLineAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerPayload = is_array($payload['customer'] ?? null) ? $payload['customer'] : [];
        $data = is_array($payload['calculated_data'] ?? null) ? $payload['calculated_data'] : [];

        if (!$data) {
            throw new RuntimeException('Calculate a route before adding a draft line.');
        }

        $customer = self::saveCustomer($customerPayload);
        self::ensureTables();

        $db = self::db();
        $now = Factory::getDate()->toSql();
        $serviceDate = trim((string) ($payload['service_date'] ?? ''));
        $projectRef = trim((string) ($payload['project_ref'] ?? ''));
        $lineLabel = trim((string) ($payload['line_label'] ?? ''));

        if ($lineLabel === '') {
            $lineLabel = trim((string) ($data['pickup'] ?? '') . ' - ' . (string) ($data['dropoff'] ?? ''));
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $columns = [
            'customer_id', 'customer_code', 'customer_name', 'project_ref', 'service_date',
            'line_label', 'total_amount', 'payload_json', 'created_by', 'created_at', 'updated_at',
        ];
        $values = [
            (int) $customer['id'],
            $db->quote($customer['customer_code']),
            $db->quote((string) ($customerPayload['customer_name'] ?? '')),
            $db->quote($projectRef),
            $serviceDate !== '' ? $db->quote($serviceDate) : 'NULL',
            $db->quote($lineLabel),
            self::num(self::invoiceTotal($data)),
            $db->quote($payloadJson),
            (int) Factory::getApplication()->getIdentity()->id,
            $db->quote($now),
            $db->quote($now),
        ];

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__route_calculation_help_for_accounting_invoice_draft_lines'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
        $db->setQuery($query)->execute();

        return [
            'draft_line_id' => (int) $db->insertid(),
            'draft_lines' => self::draftLines($customer['customer_code'], $projectRef),
        ];
    }

    public function listDraftLinesAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerCode = trim((string) ($payload['customer_code'] ?? ''));
        $projectRef = trim((string) ($payload['project_ref'] ?? ''));

        if ($customerCode === '') {
            throw new RuntimeException('Sifra Stranke is required.');
        }

        return ['draft_lines' => self::draftLines($customerCode, $projectRef)];
    }

    public function deleteDraftLineAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $id = (int) ($payload['id'] ?? 0);

        if ($id < 1) {
            throw new RuntimeException('Draft line id is required.');
        }

        self::ensureTables();
        $db = self::db();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__route_calculation_help_for_accounting_invoice_draft_lines'))
            ->where($db->quoteName('id') . ' = ' . $id);
        $db->setQuery($query)->execute();

        return ['deleted_id' => $id];
    }

    public function clearDraftLinesAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerCode = trim((string) ($payload['customer_code'] ?? ''));
        $projectRef = trim((string) ($payload['project_ref'] ?? ''));

        if ($customerCode === '') {
            throw new RuntimeException('Sifra Stranke is required.');
        }

        self::ensureTables();
        $db = self::db();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__route_calculation_help_for_accounting_invoice_draft_lines'))
            ->where($db->quoteName('customer_code') . ' = ' . $db->quote($customerCode))
            ->where($db->quoteName('project_ref') . ' = ' . $db->quote($projectRef));
        $db->setQuery($query)->execute();

        return ['deleted' => (int) $db->getAffectedRows()];
    }

    public function loadCustomerAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerCode = trim((string) ($payload['customer_code'] ?? ''));

        if ($customerCode === '') {
            throw new RuntimeException('Sifra Stranke is required.');
        }

        self::ensureCustomerAddressColumn();
        $db = self::db();
        $columns = [
            'customer_code', 'customer_name', 'customer_address', 'customer_postcode',
            'customer_city', 'customer_country_code', 'vat_id', 'outside_country',
            'custom_country_name', 'outside_vat_rate', 'outside_revenue_account',
            'custom_invoice_message',
        ];
        $query = $db->getQuery(true)
            ->select($db->quoteName($columns))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_customers'))
            ->where($db->quoteName('customer_code') . ' = ' . $db->quote($customerCode));
        $customer = $db->setQuery($query)->loadAssoc();

        if (!$customer) {
            throw new RuntimeException('No saved customer found for code ' . $customerCode . '.');
        }

        return ['customer' => $customer];
    }

    public function listCustomersAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $search = trim((string) ($payload['query'] ?? ''));
        $paginate = !empty($payload['paginate']);
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = (int) ($payload['per_page'] ?? 50);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 50;
        $db = self::db();
        self::ensureCustomerAddressColumn();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['customer_code', 'customer_name']))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_customers'));

        $condition = '';
        if ($search !== '') {
            $like = $db->quote('%' . $search . '%');
            $condition = '('
                . $db->quoteName('customer_name') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('customer_code') . ' LIKE ' . $like
                . ')';
            $query->where($condition);
        }

        $total = 0;
        if ($paginate) {
            $countQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__route_calculation_help_for_accounting_customers'));
            if ($condition !== '') {
                $countQuery->where($condition);
            }
            $total = (int) $db->setQuery($countQuery)->loadResult();
            $page = min($page, max(1, (int) ceil($total / $perPage)));
            $query->setLimit($perPage, ($page - 1) * $perPage);
        } else {
            $query->setLimit($search === '' ? 500 : 25);
        }

        $query->order($db->quoteName('customer_name') . ' ASC');
        $customers = $db->setQuery($query)->loadAssocList();

        return ['customers' => $customers ?: [], 'total' => $paginate ? $total : count($customers ?: []), 'page' => $page, 'per_page' => $perPage];
    }

    public function deleteCustomerAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerCode = trim((string) ($payload['customer_code'] ?? ''));

        if ($customerCode === '') {
            throw new RuntimeException('Sifra Stranke is required.');
        }

        self::ensureTables();
        $db = self::db();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__route_calculation_help_for_accounting_customers'))
            ->where($db->quoteName('customer_code') . ' = ' . $db->quote($customerCode));
        $db->setQuery($query)->execute();

        if ((int) $db->getAffectedRows() < 1) {
            throw new RuntimeException('No saved customer found for code ' . $customerCode . '.');
        }

        return ['deleted_customer_code' => $customerCode];
    }

    public function listInvoicesAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $customerCode = trim((string) ($payload['customer_code'] ?? ''));
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        $allCustomers = !empty($payload['all_customers']);
        $loadAll = !empty($payload['load_all']);
        $invoiceNumber = trim((string) ($payload['invoice_number'] ?? ''));
        $documentType = trim((string) ($payload['document_type'] ?? ''));
        $documentType = in_array($documentType, ['invoice', 'proforma'], true) ? $documentType : '';
        $date = trim((string) ($payload['date'] ?? ''));
        $paginate = !empty($payload['paginate']);
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = (int) ($payload['per_page'] ?? 50);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 50;

        if (!$allCustomers && $customerCode === '' && $customerName === '') {
            throw new RuntimeException('Sifra Stranke or customer name is required.');
        }

        $db = self::db();
        self::ensureInvoiceCustomerColumns();
        $filters = [];

        if (!$allCustomers && $customerCode !== '') {
            $filters[] = $db->quoteName('customer_code') . ' = ' . $db->quote($customerCode);
        }

        if (!$allCustomers && $customerCode === '' && $customerName !== '') {
            $filters[] = $db->quoteName('customer_name') . ' = ' . $db->quote($customerName);
            $filters[] = $db->quoteName('customer_name') . ' LIKE ' . $db->quote('%' . $customerName . '%');
        }

        $conditions = [];
        if ($filters) {
            $conditions[] = '(' . implode(' OR ', $filters) . ')';
        }
        if ($invoiceNumber !== '') {
            $conditions[] = $db->quoteName('invoice_number') . ' LIKE ' . $db->quote('%' . $invoiceNumber . '%');
        }
        if ($documentType !== '') {
            $conditions[] = $db->quoteName('document_type') . ' = ' . $db->quote($documentType);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = $db->quoteName('created_at') . ' LIKE ' . $db->quote($date . '%');
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName(['i.id', 'i.customer_code', 'i.customer_name', 'i.customer_address', 'i.vat_id', 'i.invoice_number', 'i.document_type', 'i.document_status', 'i.source_document_id', 'i.converted_invoice_id', 'i.converted_invoice_number', 'i.total_amount', 'i.payload_json', 'i.created_at']))
            ->select('(SELECT COALESCE(SUM(' . $db->quoteName('p.amount') . '), 0) FROM '
                . $db->quoteName('#__route_calculation_help_for_accounting_invoice_payments', 'p')
                . ' WHERE ' . $db->quoteName('p.invoice_id') . ' = ' . $db->quoteName('i.id') . ') AS ' . $db->quoteName('paid_amount'))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices', 'i'));
        foreach ($conditions as $condition) {
            $query->where($condition);
        }

        $query->order($db->quoteName('i.created_at') . ' DESC');
        $total = 0;
        if ($paginate) {
            $countQuery = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'));
            foreach ($conditions as $condition) {
                $countQuery->where($condition);
            }
            $total = (int) $db->setQuery($countQuery)->loadResult();
            $page = min($page, max(1, (int) ceil($total / $perPage)));
            $query->setLimit($perPage, ($page - 1) * $perPage);
        } elseif (!$loadAll) {
            $query->setLimit(7);
        }
        $invoices = $db->setQuery($query)->loadAssocList();

        $paymentsByInvoice = [];
        $invoiceIds = array_values(array_filter(array_map(static fn ($invoice) => (int) ($invoice['id'] ?? 0), $invoices ?: [])));
        if ($invoiceIds) {
            $paymentQuery = $db->getQuery(true)
                ->select($db->quoteName(['invoice_id', 'payment_date', 'amount', 'payment_method', 'payment_reference', 'note']))
                ->from($db->quoteName('#__route_calculation_help_for_accounting_invoice_payments'))
                ->where($db->quoteName('invoice_id') . ' IN (' . implode(',', $invoiceIds) . ')')
                ->order($db->quoteName('payment_date') . ' ASC')
                ->order($db->quoteName('id') . ' ASC');
            foreach ($db->setQuery($paymentQuery)->loadAssocList() ?: [] as $payment) {
                $paymentsByInvoice[(int) $payment['invoice_id']][] = [
                    'date' => (string) $payment['payment_date'],
                    'amount' => round((float) $payment['amount'], 2),
                    'method' => (string) $payment['payment_method'],
                    'reference' => (string) $payment['payment_reference'],
                    'note' => (string) $payment['note'],
                ];
            }
        }

        foreach ($invoices as &$invoice) {
            $totalAmount = round((float) ($invoice['total_amount'] ?? 0), 2);
            $paidAmount = min($totalAmount, round((float) ($invoice['paid_amount'] ?? 0), 2));
            $invoice['paid_amount'] = $paidAmount;
            $invoice['remaining_amount'] = max(0, round($totalAmount - $paidAmount, 2));
            $invoice['payment_status'] = $paidAmount <= 0.004
                ? 'unpaid'
                : ($invoice['remaining_amount'] <= 0.004 ? 'paid' : 'partially_paid');
            $invoice['payments'] = $paymentsByInvoice[(int) ($invoice['id'] ?? 0)] ?? [];
        }
        unset($invoice);

        return ['invoices' => $invoices ?: [], 'total' => $paginate ? $total : count($invoices ?: []), 'page' => $page, 'per_page' => $perPage];
    }

    public function deleteInvoiceAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        throw new RuntimeException('Invoice and pro forma deletion is available only in the Route calculation help administrator component.');
    }

    private static function saveCustomer(array $payload): array
    {
        $customerCode = trim((string) ($payload['customer_code'] ?? ''));
        $customerName = trim((string) ($payload['customer_name'] ?? ''));

        if ($customerCode === '') {
            throw new RuntimeException('Sifra Stranke is required.');
        }

        if ($customerName === '') {
            throw new RuntimeException('Customer name is required.');
        }

        if (mb_strlen($customerCode) > 10) {
            throw new RuntimeException('Customer code must be 10 characters or fewer for Minimax XML.');
        }

        if (mb_strlen($customerName) > 100) {
            throw new RuntimeException('Customer name must be 100 characters or fewer for Minimax XML.');
        }

        $customerAddress = trim((string) ($payload['customer_address'] ?? ''));
        $customerPostcode = trim((string) ($payload['customer_postcode'] ?? ''));
        $customerCity = trim((string) ($payload['customer_city'] ?? ''));
        $customerCountryCode = strtoupper(trim((string) ($payload['customer_country_code'] ?? 'SI')));
        if (mb_strlen($customerAddress) > 50 || mb_strlen($customerPostcode) > 30 || mb_strlen($customerCity) > 250) {
            throw new RuntimeException('Customer address data exceeds a Minimax XML field length.');
        }
        if (!preg_match('/^[A-Z]{2}$/', $customerCountryCode)) {
            throw new RuntimeException('Customer country code must contain exactly two letters.');
        }

        self::ensureCustomerAddressColumn();
        $db = self::db();
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $existingId = self::customerId($customerCode);
        $data = [
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'customer_address' => $customerAddress,
            'customer_postcode' => $customerPostcode,
            'customer_city' => $customerCity,
            'customer_country_code' => $customerCountryCode,
            'vat_id' => trim((string) ($payload['vat_id'] ?? '')),
            'outside_country' => trim((string) ($payload['outside_country'] ?? '')),
            'custom_country_name' => trim((string) ($payload['custom_country_name'] ?? '')),
            'outside_vat_rate' => self::num($payload['outside_vat_rate'] ?? 0),
            'outside_revenue_account' => trim((string) ($payload['outside_revenue_account'] ?? '')),
            'custom_invoice_message' => (string) ($payload['custom_invoice_message'] ?? ''),
            'updated_at' => $now,
        ];

        if ($existingId) {
            $sets = [];
            foreach ($data as $column => $value) {
                $sets[] = $db->quoteName($column) . ' = ' . (is_numeric($value) && $column === 'outside_vat_rate' ? $value : $db->quote($value));
            }

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__route_calculation_help_for_accounting_customers'))
                ->set($sets)
                ->where($db->quoteName('id') . ' = ' . (int) $existingId);
            $db->setQuery($query)->execute();
            $id = (int) $existingId;
        } else {
            $columns = array_merge(array_keys($data), ['created_by', 'created_at']);
            $values = [
                $db->quote($data['customer_code']),
                $db->quote($data['customer_name']),
                $db->quote($data['customer_address']),
                $db->quote($data['customer_postcode']),
                $db->quote($data['customer_city']),
                $db->quote($data['customer_country_code']),
                $db->quote($data['vat_id']),
                $db->quote($data['outside_country']),
                $db->quote($data['custom_country_name']),
                $data['outside_vat_rate'],
                $db->quote($data['outside_revenue_account']),
                $db->quote($data['custom_invoice_message']),
                $db->quote($data['updated_at']),
                $userId,
                $db->quote($now),
            ];
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__route_calculation_help_for_accounting_customers'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));
            $db->setQuery($query)->execute();
            $id = (int) $db->insertid();
        }

        return ['id' => $id, 'customer_code' => $customerCode];
    }

    private static function customerId(string $customerCode): int
    {
        $db = self::db();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_customers'))
            ->where($db->quoteName('customer_code') . ' = ' . $db->quote($customerCode));

        return (int) $db->setQuery($query)->loadResult();
    }

    private static function invoiceExists(string $invoiceNumber): bool
    {
        $db = self::db();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
            ->where($db->quoteName('invoice_number') . ' = ' . $db->quote($invoiceNumber));

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private static function isDuplicateKeyException(Throwable $exception): bool
    {
        do {
            if (in_array((int) $exception->getCode(), [1062, 23000], true)
                || preg_match('/duplicate entry|unique constraint|integrity constraint violation/i', $exception->getMessage())) {
                return true;
            }

            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);

        return false;
    }

    private static function draftLines(string $customerCode, string $projectRef): array
    {
        self::ensureTables();
        $db = self::db();
        $query = $db->getQuery(true)
            ->select($db->quoteName([
                'id', 'customer_code', 'customer_name', 'project_ref', 'service_date',
                'line_label', 'total_amount', 'payload_json', 'created_at', 'updated_at',
            ]))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoice_draft_lines'))
            ->where($db->quoteName('customer_code') . ' = ' . $db->quote($customerCode))
            ->where($db->quoteName('project_ref') . ' = ' . $db->quote($projectRef))
            ->order($db->quoteName('service_date') . ' ASC, ' . $db->quoteName('created_at') . ' ASC');

        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    private static function nextInvoiceNumber(int $year, string $documentType = 'invoice'): string
    {
        self::ensureTables();
        $db = self::db();
        $shortYear = substr(str_pad((string) $year, 4, '0', STR_PAD_LEFT), -2);
        $prefix = $documentType === 'proforma' ? 'PR-RCHA-' . $shortYear . '-' : 'RCHA-' . $shortYear . '-';
        $query = $db->getQuery(true)
            ->select($db->quoteName('invoice_number'))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
            ->where($db->quoteName('invoice_number') . ' LIKE ' . $db->quote($prefix . '%'));
        $existing = $db->setQuery($query)->loadColumn() ?: [];
        $maxSequence = 0;

        foreach ($existing as $invoiceNumber) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)(?:[-\s].+)?$/', (string) $invoiceNumber, $matches)) {
                $maxSequence = max($maxSequence, (int) $matches[1]);
            }
        }

        $sequence = $maxSequence + 1;
        do {
            $candidate = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::invoiceExists($candidate));

        return $candidate;
    }

    private static function ensureCustomerAddressColumn(): void
    {
        self::ensureTables();
        $db = self::db();
        $table = $db->replacePrefix('#__route_calculation_help_for_accounting_customers');
        $columns = [
            'customer_address' => ["varchar(512) NOT NULL DEFAULT ''", 'customer_name'],
            'customer_postcode' => ["varchar(30) NOT NULL DEFAULT ''", 'customer_address'],
            'customer_city' => ["varchar(250) NOT NULL DEFAULT ''", 'customer_postcode'],
            'customer_country_code' => ["varchar(2) NOT NULL DEFAULT 'SI'", 'customer_city'],
        ];

        foreach ($columns as $column => [$definition, $after]) {
            $db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote($column));
            if ($db->loadResult()) {
                continue;
            }
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD COLUMN ' . $db->quoteName($column) . ' ' . $definition
                . ' AFTER ' . $db->quoteName($after)
            )->execute();
        }
    }

    private static function ensureInvoiceCustomerColumns(): void
    {
        self::ensureTables();
        $db = self::db();
        $table = $db->replacePrefix('#__route_calculation_help_for_accounting_invoices');
        $columns = [
            'customer_name' => [
                'definition' => "varchar(255) NOT NULL DEFAULT ''",
                'after' => 'customer_code',
            ],
            'customer_address' => [
                'definition' => "varchar(512) NOT NULL DEFAULT ''",
                'after' => 'customer_name',
            ],
            'vat_id' => [
                'definition' => "varchar(64) NOT NULL DEFAULT ''",
                'after' => 'customer_address',
            ],
            'document_type' => [
                'definition' => "varchar(16) NOT NULL DEFAULT 'invoice'",
                'after' => 'invoice_number',
            ],
            'document_status' => [
                'definition' => "varchar(16) NOT NULL DEFAULT 'issued'",
                'after' => 'document_type',
            ],
            'source_document_id' => [
                'definition' => 'int unsigned NULL DEFAULT NULL',
                'after' => 'document_status',
            ],
            'converted_invoice_id' => [
                'definition' => 'int unsigned NULL DEFAULT NULL',
                'after' => 'source_document_id',
            ],
            'converted_invoice_number' => [
                'definition' => "varchar(64) NOT NULL DEFAULT ''",
                'after' => 'converted_invoice_id',
            ],
        ];

        foreach ($columns as $column => $config) {
            $db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote($column));

            if ($db->loadResult()) {
                continue;
            }

            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD COLUMN ' . $db->quoteName($column) . ' ' . $config['definition']
                . ' AFTER ' . $db->quoteName($config['after'])
            )->execute();
        }

        $db->setQuery('SHOW INDEX FROM ' . $db->quoteName($table) . ' WHERE Key_name = ' . $db->quote('idx_source_document_id'));
        if (!$db->loadResult()) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD UNIQUE KEY ' . $db->quoteName('idx_source_document_id')
                . ' (' . $db->quoteName('source_document_id') . ')'
            )->execute();
        }

        $db->setQuery('SHOW INDEX FROM ' . $db->quoteName($table) . ' WHERE Key_name = ' . $db->quote('idx_document_type_status'));
        if (!$db->loadResult()) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD KEY ' . $db->quoteName('idx_document_type_status')
                . ' (' . $db->quoteName('document_type') . ', ' . $db->quoteName('document_status') . ')'
            )->execute();
        }
    }

    private static function ensureTables(): void
    {
        static $ensured = false;

        if ($ensured) {
            return;
        }

        $db = self::db();
        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS " . $db->quoteName('#__route_calculation_help_for_accounting_customers') . " (
              " . $db->quoteName('id') . " int unsigned NOT NULL AUTO_INCREMENT,
              " . $db->quoteName('customer_code') . " varchar(64) NOT NULL,
              " . $db->quoteName('customer_name') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('customer_address') . " varchar(512) NOT NULL DEFAULT '',
              " . $db->quoteName('customer_postcode') . " varchar(30) NOT NULL DEFAULT '',
              " . $db->quoteName('customer_city') . " varchar(250) NOT NULL DEFAULT '',
              " . $db->quoteName('customer_country_code') . " varchar(2) NOT NULL DEFAULT 'SI',
              " . $db->quoteName('vat_id') . " varchar(64) NOT NULL DEFAULT '',
              " . $db->quoteName('outside_country') . " varchar(16) NOT NULL DEFAULT '',
              " . $db->quoteName('custom_country_name') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('outside_vat_rate') . " decimal(10,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('outside_revenue_account') . " varchar(32) NOT NULL DEFAULT '',
              " . $db->quoteName('custom_invoice_message') . " text NULL,
              " . $db->quoteName('created_by') . " int unsigned NOT NULL DEFAULT 0,
              " . $db->quoteName('created_at') . " datetime NOT NULL,
              " . $db->quoteName('updated_at') . " datetime NOT NULL,
              PRIMARY KEY (" . $db->quoteName('id') . "),
              UNIQUE KEY " . $db->quoteName('idx_customer_code') . " (" . $db->quoteName('customer_code') . "),
              KEY " . $db->quoteName('idx_created_by') . " (" . $db->quoteName('created_by') . ")
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS " . $db->quoteName('#__route_calculation_help_for_accounting_invoices') . " (
              " . $db->quoteName('id') . " int unsigned NOT NULL AUTO_INCREMENT,
              " . $db->quoteName('customer_id') . " int unsigned NOT NULL,
              " . $db->quoteName('customer_code') . " varchar(64) NOT NULL,
              " . $db->quoteName('customer_name') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('customer_address') . " varchar(512) NOT NULL DEFAULT '',
              " . $db->quoteName('vat_id') . " varchar(64) NOT NULL DEFAULT '',
              " . $db->quoteName('invoice_number') . " varchar(64) NOT NULL,
              " . $db->quoteName('document_type') . " varchar(16) NOT NULL DEFAULT 'invoice',
              " . $db->quoteName('document_status') . " varchar(16) NOT NULL DEFAULT 'issued',
              " . $db->quoteName('source_document_id') . " int unsigned NULL DEFAULT NULL,
              " . $db->quoteName('converted_invoice_id') . " int unsigned NULL DEFAULT NULL,
              " . $db->quoteName('converted_invoice_number') . " varchar(64) NOT NULL DEFAULT '',
              " . $db->quoteName('output_file_name') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('pickup') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('dropoff') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('total_km') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('slovenia_km') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('outside_slovenia_km') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('taxable_base_slovenia') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('outside_slovenia_base') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('vat_rate') . " decimal(10,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('vat_amount') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('outside_vat_rate') . " decimal(10,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('outside_vat_amount') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('total_amount') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('payload_json') . " mediumtext NULL,
              " . $db->quoteName('created_by') . " int unsigned NOT NULL DEFAULT 0,
              " . $db->quoteName('created_at') . " datetime NOT NULL,
              PRIMARY KEY (" . $db->quoteName('id') . "),
              UNIQUE KEY " . $db->quoteName('idx_invoice_number') . " (" . $db->quoteName('invoice_number') . "),
              UNIQUE KEY " . $db->quoteName('idx_source_document_id') . " (" . $db->quoteName('source_document_id') . "),
              KEY " . $db->quoteName('idx_document_type_status') . " (" . $db->quoteName('document_type') . ", " . $db->quoteName('document_status') . "),
              KEY " . $db->quoteName('idx_customer_id') . " (" . $db->quoteName('customer_id') . "),
              KEY " . $db->quoteName('idx_customer_code') . " (" . $db->quoteName('customer_code') . ")
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS " . $db->quoteName('#__route_calculation_help_for_accounting_invoice_draft_lines') . " (
              " . $db->quoteName('id') . " int unsigned NOT NULL AUTO_INCREMENT,
              " . $db->quoteName('customer_id') . " int unsigned NOT NULL,
              " . $db->quoteName('customer_code') . " varchar(64) NOT NULL,
              " . $db->quoteName('customer_name') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('project_ref') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('service_date') . " date NULL,
              " . $db->quoteName('line_label') . " varchar(512) NOT NULL DEFAULT '',
              " . $db->quoteName('total_amount') . " decimal(12,4) NOT NULL DEFAULT 0,
              " . $db->quoteName('payload_json') . " mediumtext NULL,
              " . $db->quoteName('created_by') . " int unsigned NOT NULL DEFAULT 0,
              " . $db->quoteName('created_at') . " datetime NOT NULL,
              " . $db->quoteName('updated_at') . " datetime NOT NULL,
              PRIMARY KEY (" . $db->quoteName('id') . "),
              KEY " . $db->quoteName('idx_customer_project') . " (" . $db->quoteName('customer_code') . ", " . $db->quoteName('project_ref') . "),
              KEY " . $db->quoteName('idx_customer_id') . " (" . $db->quoteName('customer_id') . ")
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS " . $db->quoteName('#__route_calculation_help_for_accounting_invoice_payments') . " (
              " . $db->quoteName('id') . " int unsigned NOT NULL AUTO_INCREMENT,
              " . $db->quoteName('invoice_id') . " int unsigned NOT NULL,
              " . $db->quoteName('payment_date') . " date NOT NULL,
              " . $db->quoteName('amount') . " decimal(12,2) NOT NULL,
              " . $db->quoteName('payment_method') . " varchar(32) NOT NULL DEFAULT 'bank_transfer',
              " . $db->quoteName('payment_reference') . " varchar(255) NOT NULL DEFAULT '',
              " . $db->quoteName('note') . " text NULL,
              " . $db->quoteName('created_by') . " int unsigned NOT NULL DEFAULT 0,
              " . $db->quoteName('created_at') . " datetime NOT NULL,
              PRIMARY KEY (" . $db->quoteName('id') . "),
              KEY " . $db->quoteName('idx_invoice_id_date') . " (" . $db->quoteName('invoice_id') . ", " . $db->quoteName('payment_date') . ")
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        $ensured = true;
    }

    private static function payload(): array
    {
        $raw = Factory::getApplication()->input->post->get('payload', '{}', 'raw');
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid payload.');
        }

        return $payload;
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    private static function num($value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private static function invoiceTotal(array $data): float
    {
        if (array_key_exists('invoiceTotalAmount', $data)) {
            return (float) $data['invoiceTotalAmount'];
        }

        $additionalCosts = array_key_exists('deductionsGrossTotal', $data)
            ? (float) $data['deductionsGrossTotal']
            : array_reduce(
                is_array($data['deductions'] ?? null) ? $data['deductions'] : [],
                static fn (float $sum, $cost): float => $sum + max(0, (float) (is_array($cost) ? ($cost['amount'] ?? 0) : 0)),
                0.0
            );

        return (float) ($data['totalAmount'] ?? 0) + $additionalCosts;
    }
}
