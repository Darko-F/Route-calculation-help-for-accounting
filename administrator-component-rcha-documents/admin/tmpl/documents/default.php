<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$identity = Factory::getApplication()->getIdentity();
$canDelete = $identity->authorise('core.delete', 'com_rcha_documents');
$canEdit = $identity->authorise('core.edit', 'com_rcha_documents');
$methodLabels = [
    'bank_transfer' => Text::_('COM_RCHA_DOCUMENTS_PAYMENT_METHOD_BANK_TRANSFER'),
    'cash' => Text::_('COM_RCHA_DOCUMENTS_PAYMENT_METHOD_CASH'),
    'card' => Text::_('COM_RCHA_DOCUMENTS_PAYMENT_METHOD_CARD'),
    'other' => Text::_('COM_RCHA_DOCUMENTS_PAYMENT_METHOD_OTHER'),
];
$pdfText = [];
foreach ([
    'PAYMENT_CONFIRMATION', 'INVOICE', 'CUSTOMER', 'INVOICE_TOTAL', 'PAYMENT_HISTORY',
    'PAYMENT_DATE', 'PAYMENT_AMOUNT', 'PAYMENT_METHOD', 'PAYMENT_REFERENCE', 'PAYMENT_NOTE',
    'TOTAL_PAID', 'REMAINING', 'UNPAID', 'PARTIALLY_PAID', 'PAID', 'DUE_DATE',
    'CONFIRMATION_STATEMENT', 'CONFIRMATION_GENERATED', 'CONFIRMATION_FILENAME_PREFIX',
    'CONFIRMATION_PAID_LABEL', 'CONFIRMATION_PARTIAL_LABEL',
] as $key) {
    $pdfText[$key] = Text::_('COM_RCHA_DOCUMENTS_' . $key);
}
$assetBase = rtrim(Uri::root(true), '/') . '/modules/mod_route_calculation_help_for_accounting/media';
?>
<form action="<?php echo Route::_('index.php?option=com_rcha_documents&view=documents'); ?>" method="post" name="adminForm" id="adminForm">
  <div class="row g-2 align-items-end mb-3">
    <div class="col-lg-3">
      <label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER'); ?></label>
      <input type="search" name="filter_search" id="filter_search" class="form-control"
        value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
        placeholder="<?php echo Text::_('COM_RCHA_DOCUMENTS_SEARCH_PLACEHOLDER'); ?>">
    </div>
    <div class="col-lg-2">
      <label class="form-label" for="filter_document_type"><?php echo Text::_('COM_RCHA_DOCUMENTS_TYPE'); ?></label>
      <select name="filter_document_type" id="filter_document_type" class="form-select" onchange="this.form.submit()">
        <option value=""><?php echo Text::_('COM_RCHA_DOCUMENTS_ALL_TYPES'); ?></option>
        <option value="invoice"<?php echo $this->state->get('filter.document_type') === 'invoice' ? ' selected' : ''; ?>><?php echo Text::_('COM_RCHA_DOCUMENTS_INVOICE'); ?></option>
        <option value="proforma"<?php echo $this->state->get('filter.document_type') === 'proforma' ? ' selected' : ''; ?>><?php echo Text::_('COM_RCHA_DOCUMENTS_PROFORMA'); ?></option>
      </select>
    </div>
    <div class="col-lg-2">
      <label class="form-label" for="filter_payment_status"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_STATUS'); ?></label>
      <select name="filter_payment_status" id="filter_payment_status" class="form-select" onchange="this.form.submit()">
        <option value=""><?php echo Text::_('COM_RCHA_DOCUMENTS_ALL_PAYMENT_STATUSES'); ?></option>
        <?php foreach (['unpaid', 'partially_paid', 'paid'] as $status) : ?>
          <option value="<?php echo $status; ?>"<?php echo $this->state->get('filter.payment_status') === $status ? ' selected' : ''; ?>><?php echo Text::_('COM_RCHA_DOCUMENTS_' . strtoupper($status)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-2">
      <label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label>
      <select name="limit" id="limit" class="form-select" onchange="this.form.submit()">
        <?php foreach ([25, 50, 100] as $limit) : ?>
          <option value="<?php echo $limit; ?>"<?php echo (int) $this->state->get('list.limit') === $limit ? ' selected' : ''; ?>><?php echo $limit; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
      <a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_rcha_documents&view=documents&filter_search=&filter_document_type=&filter_payment_status='); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
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
            <th><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_STATUS'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_RCHA_DOCUMENTS_DATE', 'a.created_at', $listDirn, $listOrder); ?></th>
            <th><?php echo Text::_('COM_RCHA_DOCUMENTS_ACTIONS'); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) :
            $isProforma = $item->document_type === 'proforma';
            $isConverted = $isProforma && (int) $item->converted_invoice_id > 0;
            $customer = trim((string) $item->customer_name) ?: (string) $item->customer_code;
            $documentStatus = $isConverted
                ? Text::sprintf('COM_RCHA_DOCUMENTS_CONVERTED_TO', (string) $item->converted_invoice_number)
                : Text::_('COM_RCHA_DOCUMENTS_STATUS_' . strtoupper((string) $item->document_status));
            $paymentStatus = (string) $item->payment_status;
            $paymentBadge = $paymentStatus === 'paid' ? 'success' : ($paymentStatus === 'partially_paid' ? 'warning text-dark' : 'secondary');
            $payments = [];
            foreach ($item->payments as $payment) {
                $method = (string) $payment->payment_method;
                $payments[] = [
                    'date' => (string) $payment->payment_date,
                    'amount' => round((float) $payment->amount, 2),
                    'method' => $method,
                    'method_label' => $methodLabels[$method] ?? $methodLabels['other'],
                    'reference' => (string) $payment->payment_reference,
                    'note' => (string) $payment->note,
                ];
            }
            $paymentDocument = [
                'id' => (int) $item->id,
                'invoice_number' => (string) $item->invoice_number,
                'customer' => $customer,
                'customer_address' => (string) $item->customer_address,
                'customer_postcode_city' => trim((string) $item->customer_postcode . ' ' . (string) $item->customer_city),
                'invoice_total' => round((float) $item->total_amount, 2),
                'paid_amount' => round((float) $item->paid_amount, 2),
                'remaining_amount' => round((float) $item->remaining_amount, 2),
                'payment_status' => $paymentStatus,
                'due_date' => (string) $item->due_date,
                'payments' => $payments,
            ];
            $paymentJson = htmlspecialchars(json_encode($paymentDocument, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
        ?>
          <tr>
            <td class="text-center"><?php echo $canDelete ? HTMLHelper::_('grid.id', $i, $item->id) : ''; ?></td>
            <td><strong><?php echo $this->escape($item->invoice_number); ?></strong><br><small class="text-muted"><?php echo $this->escape($documentStatus); ?></small></td>
            <td><span class="badge bg-<?php echo $isProforma ? 'info text-dark' : 'primary'; ?>"><?php echo Text::_($isProforma ? 'COM_RCHA_DOCUMENTS_PROFORMA' : 'COM_RCHA_DOCUMENTS_INVOICE'); ?></span></td>
            <td><?php echo $this->escape($customer); ?></td>
            <td class="text-end"><?php echo number_format((float) $item->total_amount, 2, ',', '.'); ?> EUR</td>
            <td>
              <?php if ($isProforma) : ?>
                <span class="text-muted">—</span>
              <?php else : ?>
                <span class="badge bg-<?php echo $paymentBadge; ?>"><?php echo Text::_('COM_RCHA_DOCUMENTS_' . strtoupper($paymentStatus)); ?></span><br>
                <small><?php echo Text::_('COM_RCHA_DOCUMENTS_PAID_AMOUNT'); ?>: <?php echo number_format((float) $item->paid_amount, 2, ',', '.'); ?> EUR</small><br>
                <small><?php echo Text::_('COM_RCHA_DOCUMENTS_REMAINING'); ?>: <?php echo number_format((float) $item->remaining_amount, 2, ',', '.'); ?> EUR</small>
              <?php endif; ?>
            </td>
            <td><?php echo $item->created_at ? HTMLHelper::_('date', $item->created_at, 'd/m/Y') : ''; ?></td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                <?php if (!$isProforma && $canEdit && (float) $item->remaining_amount > 0.004) : ?>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-payment-document="<?php echo $paymentJson; ?>" onclick="rchaOpenPaymentDialog(this)"><?php echo Text::_('COM_RCHA_DOCUMENTS_ADD_PAYMENT'); ?></button>
                <?php endif; ?>
                <?php if (!$isProforma && (float) $item->paid_amount > 0.004) : ?>
                  <button type="button" class="btn btn-sm btn-outline-success" data-payment-document="<?php echo $paymentJson; ?>" onclick="rchaPaymentConfirmationPdf(this)"><?php echo Text::_('COM_RCHA_DOCUMENTS_DOWNLOAD_CONFIRMATION'); ?></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?>
          <tr><td colspan="8" class="text-center py-4"><?php echo Text::_('COM_RCHA_DOCUMENTS_NO_DOCUMENTS'); ?></td></tr>
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

<dialog id="rcha-payment-dialog" class="border-0 rounded shadow p-0" style="width:min(720px, calc(100vw - 2rem));">
  <div class="p-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
      <div><h2 class="h4 mb-1"><?php echo Text::_('COM_RCHA_DOCUMENTS_ADD_PAYMENT'); ?></h2><div id="rcha-payment-document-number" class="text-muted"></div></div>
      <button type="button" class="btn-close" aria-label="<?php echo Text::_('COM_RCHA_DOCUMENTS_CLOSE'); ?>" onclick="document.getElementById('rcha-payment-dialog').close()"></button>
    </div>
    <form action="<?php echo Route::_('index.php?option=com_rcha_documents&task=documents.recordPayment'); ?>" method="post" id="rcha-payment-form">
      <input type="hidden" name="invoice_id" id="rcha-payment-invoice-id" value="">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="rcha-payment-date"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_DATE'); ?></label><input class="form-control" type="date" name="payment_date" id="rcha-payment-date" required></div>
        <div class="col-md-6"><label class="form-label" for="rcha-payment-amount"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_AMOUNT'); ?></label><div class="input-group"><input class="form-control" type="number" name="amount" id="rcha-payment-amount" min="0.01" step="0.01" required><span class="input-group-text">EUR</span></div></div>
        <div class="col-md-6"><label class="form-label" for="rcha-payment-method"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_METHOD'); ?></label><select class="form-select" name="payment_method" id="rcha-payment-method"><?php foreach ($methodLabels as $value => $label) : ?><option value="<?php echo $value; ?>"><?php echo $this->escape($label); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label" for="rcha-payment-reference"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_REFERENCE'); ?></label><input class="form-control" type="text" name="payment_reference" id="rcha-payment-reference" maxlength="255"></div>
        <div class="col-12"><label class="form-label" for="rcha-payment-note"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_NOTE'); ?></label><textarea class="form-control" name="payment_note" id="rcha-payment-note" rows="2" maxlength="2000"></textarea></div>
      </div>
      <div class="alert alert-warning mt-3 mb-2"><?php echo Text::_('COM_RCHA_DOCUMENTS_FURS_PAYMENT_WARNING'); ?></div>
      <div class="alert alert-info mb-3"><?php echo Text::_('COM_RCHA_DOCUMENTS_MINIMAX_PAYMENT_NOTE'); ?></div>
      <h3 class="h6"><?php echo Text::_('COM_RCHA_DOCUMENTS_PAYMENT_HISTORY'); ?></h3>
      <div id="rcha-payment-history" class="mb-3"></div>
      <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-secondary" onclick="document.getElementById('rcha-payment-dialog').close()"><?php echo Text::_('COM_RCHA_DOCUMENTS_CLOSE'); ?></button><button type="submit" class="btn btn-primary"><?php echo Text::_('COM_RCHA_DOCUMENTS_SAVE_PAYMENT'); ?></button></div>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </div>
</dialog>

<script src="<?php echo htmlspecialchars($assetBase . '/vendor/jspdf/jspdf.umd.min.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
const RCHA_PAYMENT_COMPANY = <?php echo json_encode($this->company, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;
const RCHA_PAYMENT_TEXT = <?php echo json_encode($pdfText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;
const RCHA_PAYMENT_FONT_BASE = <?php echo json_encode($assetBase . '/fonts/'); ?>;

function rchaPaymentData(button) {
  try { return JSON.parse(button.dataset.paymentDocument || '{}'); } catch (error) { return {}; }
}
function rchaEscape(value) {
  const node = document.createElement('div'); node.textContent = String(value ?? ''); return node.innerHTML;
}
function rchaMoney(value) { return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' EUR'; }
function rchaDate(value) {
  const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return match ? `${match[3]}/${match[2]}/${match[1]}` : String(value || '');
}
function rchaOpenPaymentDialog(button) {
  const data = rchaPaymentData(button), dialog = document.getElementById('rcha-payment-dialog');
  document.getElementById('rcha-payment-document-number').textContent = data.invoice_number || '';
  document.getElementById('rcha-payment-invoice-id').value = data.id || '';
  const today = new Date(), pad = value => String(value).padStart(2, '0');
  document.getElementById('rcha-payment-date').value = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;
  const amount = document.getElementById('rcha-payment-amount'); amount.value = Number(data.remaining_amount || 0).toFixed(2); amount.max = Number(data.remaining_amount || 0).toFixed(2);
  document.getElementById('rcha-payment-method').value = 'bank_transfer';
  document.getElementById('rcha-payment-reference').value = String(data.invoice_number || '').replace(/[^0-9]/g, '');
  document.getElementById('rcha-payment-note').value = '';
  const payments = Array.isArray(data.payments) ? data.payments : [];
  document.getElementById('rcha-payment-history').innerHTML = payments.length ? `<div class="table-responsive"><table class="table table-sm"><thead><tr><th>${rchaEscape(RCHA_PAYMENT_TEXT.PAYMENT_DATE)}</th><th>${rchaEscape(RCHA_PAYMENT_TEXT.PAYMENT_METHOD)}</th><th>${rchaEscape(RCHA_PAYMENT_TEXT.PAYMENT_REFERENCE)}</th><th class="text-end">${rchaEscape(RCHA_PAYMENT_TEXT.PAYMENT_AMOUNT)}</th></tr></thead><tbody>${payments.map(payment => `<tr><td>${rchaEscape(rchaDate(payment.date))}</td><td>${rchaEscape(payment.method_label)}</td><td>${rchaEscape(payment.reference)}</td><td class="text-end">${rchaEscape(rchaMoney(payment.amount))}</td></tr>`).join('')}</tbody></table></div>` : `<p class="text-muted">${rchaEscape(<?php echo json_encode(Text::_('COM_RCHA_DOCUMENTS_NO_PAYMENTS')); ?>)}</p>`;
  dialog.showModal();
}
async function rchaPdfFont(doc) {
  const files = [['NotoSans-Regular.ttf', 'normal'], ['NotoSans-Bold.ttf', 'bold']];
  for (const [file, style] of files) {
    const bytes = new Uint8Array(await (await fetch(RCHA_PAYMENT_FONT_BASE + file)).arrayBuffer());
    let binary = ''; for (let offset = 0; offset < bytes.length; offset += 32768) binary += String.fromCharCode(...bytes.subarray(offset, offset + 32768));
    doc.addFileToVFS(file, btoa(binary)); doc.addFont(file, 'NotoSans', style);
  }
  doc.setFont('NotoSans', 'normal');
}
async function rchaPaymentConfirmationPdf(button) {
  const data = rchaPaymentData(button);
  if (!window.jspdf?.jsPDF || !Array.isArray(data.payments) || !data.payments.length) return;
  const doc = new window.jspdf.jsPDF({ unit: 'mm', format: 'a4' }); await rchaPdfFont(doc);
  const right = doc.internal.pageSize.getWidth() - 16, company = RCHA_PAYMENT_COMPANY || {};
  let y = 18;
  doc.setFont('NotoSans', 'bold'); doc.setFontSize(17); doc.text(RCHA_PAYMENT_TEXT.PAYMENT_CONFIRMATION, 16, y); y += 10;
  doc.setFontSize(15); doc.setTextColor(data.payment_status === 'paid' ? 20 : 160, data.payment_status === 'paid' ? 125 : 95, 35);
  const statusLabel = data.payment_status === 'paid'
    ? RCHA_PAYMENT_TEXT.CONFIRMATION_PAID_LABEL
    : (data.payment_status === 'partially_paid' ? RCHA_PAYMENT_TEXT.CONFIRMATION_PARTIAL_LABEL : RCHA_PAYMENT_TEXT.UNPAID);
  doc.text(statusLabel, right, 18, { align: 'right' }); doc.setTextColor(0, 0, 0);
  doc.setFont('NotoSans', 'normal'); doc.setFontSize(9);
  [company.name, company.address, company.postcode_city, company.tax_number, company.iban, company.email, company.phone].filter(Boolean).forEach(line => { doc.text(String(line), right, y, { align: 'right' }); y += 4.5; });
  y = Math.max(y + 8, 55); doc.setFontSize(10);
  const detail = (label, value) => {
    doc.setFont('NotoSans', 'bold');
    const labelLines = doc.splitTextToSize(String(label || '') + ':', 70);
    doc.text(labelLines, 16, y);
    doc.setFont('NotoSans', 'normal');
    const valueLines = doc.splitTextToSize(String(value || '—'), 102);
    doc.text(valueLines, 92, y);
    y += Math.max(labelLines.length, valueLines.length, 1) * 4.5 + 2;
  };
  detail(RCHA_PAYMENT_TEXT.INVOICE, data.invoice_number); detail(RCHA_PAYMENT_TEXT.CUSTOMER, data.customer);
  if (data.customer_address || data.customer_postcode_city) {
    const addressLines = doc.splitTextToSize([data.customer_address, data.customer_postcode_city].filter(Boolean).join(', '), 102);
    doc.text(addressLines, 92, y); y += Math.max(addressLines.length, 1) * 4.5 + 2;
  }
  detail(RCHA_PAYMENT_TEXT.DUE_DATE, rchaDate(data.due_date)); detail(RCHA_PAYMENT_TEXT.INVOICE_TOTAL, rchaMoney(data.invoice_total));
  y += 5; doc.setFont('NotoSans', 'bold'); doc.text(RCHA_PAYMENT_TEXT.PAYMENT_HISTORY, 16, y); y += 7;
  const columns = { date: 16, method: 47, reference: 96, amountRight: right };
  doc.setFontSize(8); doc.text(RCHA_PAYMENT_TEXT.PAYMENT_DATE, columns.date, y); doc.text(RCHA_PAYMENT_TEXT.PAYMENT_METHOD, columns.method, y); doc.text(RCHA_PAYMENT_TEXT.PAYMENT_REFERENCE, columns.reference, y); doc.text(RCHA_PAYMENT_TEXT.PAYMENT_AMOUNT, columns.amountRight, y, { align: 'right' }); y += 3; doc.line(16, y, right, y); y += 6;
  doc.setFont('NotoSans', 'normal');
  data.payments.forEach(payment => {
    const methodLines = doc.splitTextToSize(String(payment.method_label || ''), 42);
    const referenceLines = doc.splitTextToSize(String(payment.reference || '—'), 55);
    const rowHeight = Math.max(methodLines.length, referenceLines.length, 1) * 4 + 2;
    const noteLines = payment.note ? doc.splitTextToSize(`${RCHA_PAYMENT_TEXT.PAYMENT_NOTE}: ${String(payment.note)}`, 174) : [];
    if (y + rowHeight + noteLines.length * 4 + 2 > 270) { doc.addPage(); y = 20; }
    doc.text(rchaDate(payment.date), columns.date, y); doc.text(methodLines, columns.method, y); doc.text(referenceLines, columns.reference, y); doc.text(rchaMoney(payment.amount), columns.amountRight, y, { align: 'right' }); y += rowHeight;
    if (noteLines.length) { doc.text(noteLines, 20, y); y += noteLines.length * 4 + 2; }
  });
  if (y > 245) { doc.addPage(); y = 20; }
  y += 4; doc.setFont('NotoSans', 'bold'); detail(RCHA_PAYMENT_TEXT.TOTAL_PAID, rchaMoney(data.paid_amount)); detail(RCHA_PAYMENT_TEXT.REMAINING, rchaMoney(data.remaining_amount));
  y += 6; doc.setFont('NotoSans', 'normal'); doc.setFontSize(9); doc.text(doc.splitTextToSize(RCHA_PAYMENT_TEXT.CONFIRMATION_STATEMENT, 175), 16, y);
  const footerY = doc.internal.pageSize.getHeight() - 16; doc.setFontSize(8); if (company.footer) doc.text(doc.splitTextToSize(String(company.footer), 150), doc.internal.pageSize.getWidth() / 2, footerY - 7, { align: 'center' }); doc.text(`${RCHA_PAYMENT_TEXT.CONFIRMATION_GENERATED}: ${rchaDate(new Date().toISOString().slice(0, 10))}`, 16, footerY);
  const safeNumber = String(data.invoice_number || '').replace(/[^A-Za-z0-9._-]+/g, '-'); doc.save(`${RCHA_PAYMENT_TEXT.CONFIRMATION_FILENAME_PREFIX}-${safeNumber}.pdf`);
}
</script>
