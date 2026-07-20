<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$canDelete = Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_rcha_documents');
?>
<form action="<?php echo Route::_('index.php?option=com_rcha_documents&view=documents'); ?>" method="post" name="adminForm" id="adminForm">
  <div class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
      <label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
      <input type="search" name="filter_search" id="filter_search" class="form-control"
        value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
        placeholder="<?php echo Text::_('COM_RCHA_DOCUMENTS_SEARCH_PLACEHOLDER'); ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="filter_document_type"><?php echo Text::_('COM_RCHA_DOCUMENTS_TYPE'); ?></label>
      <select name="filter_document_type" id="filter_document_type" class="form-select" onchange="this.form.submit()">
        <option value=""><?php echo Text::_('COM_RCHA_DOCUMENTS_ALL_TYPES'); ?></option>
        <option value="invoice"<?php echo $this->state->get('filter.document_type') === 'invoice' ? ' selected' : ''; ?>><?php echo Text::_('COM_RCHA_DOCUMENTS_INVOICE'); ?></option>
        <option value="proforma"<?php echo $this->state->get('filter.document_type') === 'proforma' ? ' selected' : ''; ?>><?php echo Text::_('COM_RCHA_DOCUMENTS_PROFORMA'); ?></option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label>
      <select name="limit" id="limit" class="form-select" onchange="this.form.submit()">
        <?php foreach ([25, 50, 100] as $limit) : ?>
          <option value="<?php echo $limit; ?>"<?php echo (int) $this->state->get('list.limit') === $limit ? ' selected' : ''; ?>><?php echo $limit; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
      <a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_rcha_documents&view=documents&filter_search=&filter_document_type='); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RCHA_DOCUMENTS_NUMBER', 'a.invoice_number', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RCHA_DOCUMENTS_TYPE', 'a.document_type', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RCHA_DOCUMENTS_CUSTOMER', 'a.customer_name', $listDirn, $listOrder); ?></th>
            <th class="text-end"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RCHA_DOCUMENTS_AMOUNT', 'a.total_amount', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RCHA_DOCUMENTS_DATE', 'a.created_at', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.document_status', $listDirn, $listOrder); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) :
            $isProforma = $item->document_type === 'proforma';
            $isConverted = $isProforma && (int) $item->converted_invoice_id > 0;
            $customer = trim((string) $item->customer_name) ?: (string) $item->customer_code;
            $status = $isConverted
                ? Text::sprintf('COM_RCHA_DOCUMENTS_CONVERTED_TO', (string) $item->converted_invoice_number)
                : Text::_('COM_RCHA_DOCUMENTS_STATUS_' . strtoupper((string) $item->document_status));
        ?>
          <tr>
            <td class="text-center"><?php echo $canDelete ? HTMLHelper::_('grid.id', $i, $item->id) : ''; ?></td>
            <td><strong><?php echo $this->escape($item->invoice_number); ?></strong></td>
            <td><span class="badge bg-<?php echo $isProforma ? 'info text-dark' : 'primary'; ?>"><?php echo Text::_($isProforma ? 'COM_RCHA_DOCUMENTS_PROFORMA' : 'COM_RCHA_DOCUMENTS_INVOICE'); ?></span></td>
            <td><?php echo $this->escape($customer); ?></td>
            <td class="text-end"><?php echo number_format((float) $item->total_amount, 2, ',', '.'); ?> EUR</td>
            <td><?php echo $item->created_at ? HTMLHelper::_('date', $item->created_at, 'd/m/Y') : ''; ?></td>
            <td><?php echo $this->escape($status); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
          <tr><td colspan="7" class="text-center py-4"><?php echo Text::_('COM_RCHA_DOCUMENTS_NO_DOCUMENTS'); ?></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
    <div><?php echo $this->pagination->getPagesCounter(); ?></div>
    <div><?php echo $this->pagination->getPagesLinks(); ?></div>
  </div>
  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>">
  <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
  <?php echo HTMLHelper::_('form.token'); ?>
</form>
