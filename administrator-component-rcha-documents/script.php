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

        return true;
    }
};
