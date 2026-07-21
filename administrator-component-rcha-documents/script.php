<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

return new class implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if (!in_array($type, ['install', 'update'], true)) {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $table = $db->replacePrefix('#__route_calculation_help_for_accounting_invoices');
        $columns = [
            'customer_name' => ["varchar(255) NOT NULL DEFAULT ''", 'customer_code'],
            'customer_address' => ["varchar(512) NOT NULL DEFAULT ''", 'customer_name'],
            'vat_id' => ["varchar(64) NOT NULL DEFAULT ''", 'customer_address'],
            'document_type' => ["varchar(16) NOT NULL DEFAULT 'invoice'", 'invoice_number'],
            'document_status' => ["varchar(16) NOT NULL DEFAULT 'issued'", 'document_type'],
            'source_document_id' => ['int unsigned NULL DEFAULT NULL', 'document_status'],
            'converted_invoice_id' => ['int unsigned NULL DEFAULT NULL', 'source_document_id'],
            'converted_invoice_number' => ["varchar(64) NOT NULL DEFAULT ''", 'converted_invoice_id'],
        ];

        foreach ($columns as $column => [$definition, $after]) {
            $db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote($column));
            if (!$db->loadResult()) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName($column) . ' ' . $definition
                    . ' AFTER ' . $db->quoteName($after)
                )->execute();
            }
        }

        $indexes = [
            'idx_source_document_id' => 'UNIQUE KEY ' . $db->quoteName('idx_source_document_id') . ' (' . $db->quoteName('source_document_id') . ')',
            'idx_document_type_status' => 'KEY ' . $db->quoteName('idx_document_type_status') . ' (' . $db->quoteName('document_type') . ', ' . $db->quoteName('document_status') . ')',
        ];

        foreach ($indexes as $index => $definition) {
            $db->setQuery('SHOW INDEX FROM ' . $db->quoteName($table) . ' WHERE Key_name = ' . $db->quote($index));
            if (!$db->loadResult()) {
                $db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' ADD ' . $definition)->execute();
            }
        }

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

        $this->migrateModuleSettings($db);

        return true;
    }

    private function migrateModuleSettings(DatabaseInterface $db): void
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . ' = ' . $db->quote('mod_route_calculation_help_for_accounting'))
            ->order($db->quoteName('published') . ' DESC')
            ->order($db->quoteName('id') . ' ASC');
        $legacyJson = (string) ($db->setQuery($query, 0, 1)->loadResult() ?: '');
        if ($legacyJson === '') {
            return;
        }

        $query = $db->getQuery(true)
            ->select([$db->quoteName('extension_id'), $db->quoteName('params')])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_rcha_documents'));
        $extension = $db->setQuery($query)->loadObject();
        if (!$extension) {
            return;
        }

        $legacy = new Registry($legacyJson);
        $component = new Registry((string) ($extension->params ?: '{}'));
        $keys = [
            'google_maps_api_key',
            'base_country',
            'company_name',
            'company_address',
            'company_postcode_city',
            'company_tax_number',
            'company_registration_number',
            'company_iban',
            'company_email',
            'company_phone',
            'company_issue_city',
            'pdf_logo_image_url',
            'pdf_footer_text',
            'pdf_signature_image_url',
            'minimax_receivable_account',
            'minimax_base_country_standard_vat_account',
            'minimax_default_foreign_revenue_account',
            'minimax_country_accounts',
            'default_foreign_passenger_vat_rate',
            'countries',
        ];
        $changed = false;
        foreach ($keys as $key) {
            if (!$component->exists($key) && $legacy->exists($key)) {
                $component->set($key, $legacy->get($key));
                $changed = true;
            }
        }
        if (!$changed) {
            return;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = ' . $db->quote($component->toString('JSON')))
            ->where($db->quoteName('extension_id') . ' = ' . (int) $extension->extension_id);
        $db->setQuery($query)->execute();
    }
};
