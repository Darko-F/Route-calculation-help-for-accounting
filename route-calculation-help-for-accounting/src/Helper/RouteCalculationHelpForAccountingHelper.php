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
        $invoiceNumber = trim((string) ($payload['invoice_number'] ?? ''));
        $autoInvoiceNumber = !empty($payload['invoice_number_auto']);

        if ($invoiceNumber === '') {
            $invoiceNumber = self::nextInvoiceNumber((int) Factory::getDate()->format('Y'));
            $payload['invoice_number'] = $invoiceNumber;
        }

        self::ensureInvoiceCustomerColumns();

        if (self::invoiceExists($invoiceNumber)) {
            if ($autoInvoiceNumber && preg_match('/^RCHA-(\d{4})-\d+$/', $invoiceNumber, $matches)) {
                $invoiceNumber = self::nextInvoiceNumber((int) $matches[1]);
                $payload['invoice_number'] = $invoiceNumber;
            } else {
                throw new RuntimeException('Invoice number "' . $invoiceNumber . '" already exists in invoice history. Use a different invoice number to save a new record.');
            }
        }

        $customer = self::saveCustomer($customerPayload);
        $data = is_array($payload['calculated_data'] ?? null) ? $payload['calculated_data'] : [];
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $db = self::db();
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $columns = [
            'customer_id', 'customer_code', 'customer_name', 'customer_address', 'vat_id',
            'invoice_number', 'output_file_name', 'pickup', 'dropoff',
            'total_km', 'slovenia_km', 'outside_slovenia_km', 'taxable_base_slovenia',
            'outside_slovenia_base', 'vat_rate', 'vat_amount', 'outside_vat_rate',
            'outside_vat_amount', 'total_amount', 'payload_json', 'created_by', 'created_at'
        ];
        $values = [
            (int) $customer['id'],
            $db->quote($customer['customer_code']),
            $db->quote((string) ($customerPayload['customer_name'] ?? '')),
            $db->quote((string) ($customerPayload['customer_address'] ?? '')),
            $db->quote((string) ($customerPayload['vat_id'] ?? '')),
            $db->quote($invoiceNumber),
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
            self::num($data['totalAmount'] ?? 0),
            $db->quote($payloadJson),
            $userId,
            $db->quote($now),
        ];

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
        $db->setQuery($query)->execute();
        $invoiceId = (int) $db->insertid();

        return [
            'invoice_id' => $invoiceId,
            'invoice' => [
                'customer_code' => $customer['customer_code'],
                'customer_name' => (string) ($customerPayload['customer_name'] ?? ''),
                'customer_address' => (string) ($customerPayload['customer_address'] ?? ''),
                'vat_id' => (string) ($customerPayload['vat_id'] ?? ''),
                'invoice_number' => $invoiceNumber,
                'total_amount' => self::num($data['totalAmount'] ?? 0),
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

        if ($year < 2000 || $year > 9999) {
            $year = (int) Factory::getDate()->format('Y');
        }

        $invoiceNumber = self::nextInvoiceNumber($year);

        return ['invoice_number' => $invoiceNumber, 'year' => $year];
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
            self::num($data['totalAmount'] ?? 0),
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
            'customer_code', 'customer_name', 'customer_address', 'vat_id', 'outside_country',
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
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = $db->quoteName('created_at') . ' LIKE ' . $db->quote($date . '%');
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'customer_code', 'customer_name', 'customer_address', 'vat_id', 'invoice_number', 'total_amount', 'payload_json', 'created_at']))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'));
        foreach ($conditions as $condition) {
            $query->where($condition);
        }

        $query->order($db->quoteName('created_at') . ' DESC');
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
            $query->setLimit(25);
        }
        $invoices = $db->setQuery($query)->loadAssocList();

        return ['invoices' => $invoices ?: [], 'total' => $paginate ? $total : count($invoices ?: []), 'page' => $page, 'per_page' => $perPage];
    }

    public function deleteInvoiceAjax()
    {
        Session::checkToken('post') or throw new RuntimeException('Invalid Joomla session token.');
        $payload = self::payload();
        $invoiceId = (int) ($payload['id'] ?? 0);

        if ($invoiceId < 1) {
            throw new RuntimeException('A valid invoice ID is required.');
        }

        self::ensureTables();
        $db = self::db();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
            ->where($db->quoteName('id') . ' = ' . $invoiceId);
        $db->setQuery($query)->execute();

        if ((int) $db->getAffectedRows() < 1) {
            throw new RuntimeException('Invoice was not found.');
        }

        return ['deleted_invoice_id' => $invoiceId];
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

        self::ensureCustomerAddressColumn();
        $db = self::db();
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $existingId = self::customerId($customerCode);
        $data = [
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'customer_address' => trim((string) ($payload['customer_address'] ?? '')),
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

    private static function nextInvoiceNumber(int $year): string
    {
        self::ensureTables();
        $db = self::db();
        $prefix = 'RCHA-' . $year . '-';
        $query = $db->getQuery(true)
            ->select($db->quoteName('invoice_number'))
            ->from($db->quoteName('#__route_calculation_help_for_accounting_invoices'))
            ->where($db->quoteName('invoice_number') . ' LIKE ' . $db->quote($prefix . '%'));
        $existing = $db->setQuery($query)->loadColumn() ?: [];
        $maxSequence = 0;

        foreach ($existing as $invoiceNumber) {
            if (preg_match('/^RCHA-' . preg_quote((string) $year, '/') . '-(\d+)$/', (string) $invoiceNumber, $matches)) {
                $maxSequence = max($maxSequence, (int) $matches[1]);
            }
        }

        $sequence = $maxSequence + 1;
        do {
            $candidate = $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::invoiceExists($candidate));

        return $candidate;
    }

    private static function ensureCustomerAddressColumn(): void
    {
        self::ensureTables();
        $db = self::db();
        $table = $db->replacePrefix('#__route_calculation_help_for_accounting_customers');
        $db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote('customer_address'));

        if ($db->loadResult()) {
            return;
        }

        $db->setQuery(
            'ALTER TABLE ' . $db->quoteName($table)
            . ' ADD COLUMN ' . $db->quoteName('customer_address')
            . " varchar(512) NOT NULL DEFAULT '' AFTER " . $db->quoteName('customer_name')
        )->execute();
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
}
