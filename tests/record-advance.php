<?php
// Standalone payment-model tests with a database test double; no Joomla installation required.
namespace Joomla\CMS\MVC\Model {
    class ListModel {
        public $db;
        public function __construct($config = []) {}
        public function getDatabase() { return $this->db; }
    }
}
namespace Joomla\CMS\Component {
    class ComponentHelper {
        public static $settings = [];
        public static function getParams($name) {
            return new class {
                public function get($key, $default = null) { return ComponentHelper::$settings[$key] ?? $default; }
            };
        }
    }
}
namespace Joomla\CMS\Language {
    class Text {
        public static function _($key) { return $key; }
        public static function sprintf($key, ...$args) { return $key; }
    }
}
namespace Joomla\CMS {
    class Factory {
        public static function getDate() {
            return new class {
                public function format($format) { return '2026-09-09'; }
                public function toSql() { return '2026-09-09 12:00:00'; }
            };
        }
        public static function getApplication() {
            return new class { public function getIdentity() { return (object) ['id' => 7]; } };
        }
    }
}
namespace {
    define('_JEXEC', 1);
    if (!function_exists('mb_substr')) { function mb_substr($s, $start, $length) { return substr($s, $start, $length); } }
    if (!function_exists('mb_strlen')) { function mb_strlen($s) { return strlen($s); } }
    require __DIR__ . '/../administrator-component-transport-accounting/admin/src/Model/DocumentsModel.php';
    class Query {
        public $columns = [], $values = '';
        public function __call($name, $args) {
            if ($name === 'columns') $this->columns = $args[0];
            if ($name === 'values') $this->values = $args[0];
            return $this;
        }
        public function __toString() { return 'SELECT invoice'; }
    }
    class Database {
        public $paid = 0, $insert, $query, $rolledBack = false, $committed = false;
        public $invoice;
        public function __construct() {
            $this->invoice = ['id' => 1, 'document_type' => 'invoice', 'total_amount' => 1095,
                'payload_json' => json_encode(['service_date' => '2026-09-10'])];
        }
        public function getQuery($new) { return new Query; }
        public function quoteName($name) { return $name; }
        public function quote($value) { return "'" . str_replace("'", "''", $value) . "'"; }
        public function setQuery($query) { $this->query = $query; return $this; }
        public function loadAssoc() { return $this->invoice; }
        public function loadResult() { return $this->paid; }
        public function transactionStart() {}
        public function transactionCommit() { $this->committed = true; }
        public function transactionRollback() { $this->rolledBack = true; }
        public function execute() { $this->insert = $this->query; }
        public function insertid() { return 17; }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    function model() {
        $model = new \Topoweryou\Component\TransportAccounting\Administrator\Model\DocumentsModel;
        $model->db = new Database; return $model;
    }
    function rejected($call, $key) {
        try { $call(); } catch (\RuntimeException $e) {
            check(str_contains($e->getMessage(), $key), 'Unexpected error: ' . $e->getMessage()); return;
        }
        throw new \RuntimeException('Expected rejection: ' . $key);
    }
    \Joomla\CMS\Component\ComponentHelper::$settings = [
        'minimax_advance_account' => '2308', 'minimax_advance_vat_account' => '26001',
        'minimax_advance_vat_clearing_account' => '295-test',
    ];
    $m = model();
    check($m->recordPayment(1, '2026-09-02', 219, 'bank_transfer', 'REF', '', 'advance') === 17, 'Payment ID');
    check($m->db->committed, 'Advance committed');
    check(str_contains($m->db->insert->values, '"rate":9.5'), 'Default rate snapshot');
    check(str_contains($m->db->insert->values, '"advanceAccount":"2308"'), 'Account snapshot');
    \Joomla\CMS\Component\ComponentHelper::$settings['minimax_advance_vat_account'] = '2308';
    \Joomla\CMS\Component\ComponentHelper::$settings['minimax_advance_vat_clearing_account'] = '1950';
    rejected(fn () => model()->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance'), 'ADVANCE_ACCOUNT_DUPLICATE');
    \Joomla\CMS\Component\ComponentHelper::$settings['minimax_advance_vat_account'] = '26001';
    $m = model(); $m->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance');
    check($m->db->committed, 'Distinct configured accounts accepted');
    $m = model(); $m->recordPayment(1, '2026-09-02', 219, 'bank_transfer', '', '', 'advance', 22);
    check(str_contains($m->db->insert->values, '"rate":22'), 'Per-payment rate');
    $m = model(); $m->recordPayment(1, '2026-09-09', 876, 'bank_transfer', '', '');
    check(str_ends_with($m->db->insert->values, ',NULL'), 'Ordinary payment remains ordinary');
    rejected(fn () => model()->recordPayment(1, '2026-02-30', 100, '', '', '', 'advance'), 'PAYMENT_INVALID');
    rejected(fn () => model()->recordPayment(1, '2026-09-11', 100, '', '', '', 'advance'), 'ADVANCE_DATE_INVALID');
    rejected(fn () => model()->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance', 12), 'ADVANCE_RATE_INVALID');
    rejected(fn () => model()->recordPayment(1, '2026-09-02', INF, '', '', '', 'advance'), 'PAYMENT_AMOUNT_INVALID');
    $m = model(); $m->db->paid = 1000;
    rejected(fn () => $m->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance'), 'PAYMENT_EXCEEDS_REMAINING');
    check($m->db->rolledBack && !$m->db->insert, 'Overpayment rolled back without insert');
    $m = model(); $m->db->invoice['document_type'] = 'proforma';
    rejected(fn () => $m->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance'), 'PAYMENT_INVOICE_ONLY');
    \Joomla\CMS\Component\ComponentHelper::$settings['minimax_advance_vat_clearing_account'] = '';
    rejected(fn () => model()->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance'), 'ADVANCE_ACCOUNT_MISSING');
    \Joomla\CMS\Component\ComponentHelper::$settings['minimax_advance_vat_clearing_account'] = '2308';
    rejected(fn () => model()->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance'), 'ADVANCE_ACCOUNT_DUPLICATE');
    \Joomla\CMS\Component\ComponentHelper::$settings['minimax_advance_vat_clearing_account'] = str_repeat('1', 31);
    rejected(fn () => model()->recordPayment(1, '2026-09-02', 100, '', '', '', 'advance'), 'ADVANCE_ACCOUNT_TOO_LONG');
    echo "Advance payment model tests passed.\n";
}
