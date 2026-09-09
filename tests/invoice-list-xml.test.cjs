const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const html = fs.readFileSync(require('node:path').join(__dirname, '../transport-accounting/media/calculator.html'), 'utf8');
function extract(name) {
  const start = html.search(new RegExp(`(?:async )?function ${name}\\(`));
  assert(start >= 0);
  const end = html.slice(start + 1).search(/\n(?:async )?function \w+\(/);
  return html.slice(start, start + 1 + end);
}
function setup(rows) {
  const messages = [], downloads = [], calls = [];
  const modalStatus = {};
  const context = {
    t: text => text, xmlEscape: text => text, Number, JSON, Blob,
    MINIMAX_CONFIG: { receivableAccount: '1200' }, getTodayIsoDate: () => '2026-09-09',
    getInvoiceXmlFileName: ref => ref + '.xml',
    invoiceFromList: (source, index) => rows[index],
    postJoomlaAjax: async (method, params) => { calls.push({ method, params }); return { invoices: rows }; },
    showDatabaseStatus: (message, type) => messages.push({ message, type }),
    document: {
      getElementById: id => id === 'invoiceBrowserStatus' ? modalStatus : null,
      body: { appendChild: () => {} },
      createElement: () => ({ click() { downloads.push(this.download); }, remove() {} })
    },
    URL: { createObjectURL: () => 'blob:test', revokeObjectURL: () => {} }, setTimeout: fn => fn(),
    TransportAccountingMinimax: {
      paymentNumber: require('../transport-accounting/media/minimax-payments.js').paymentNumber,
      paymentReference: require('../transport-accounting/media/minimax-payments.js').paymentReference,
      invoice: data => { calls.push({ invoice: data }); return '<final/>'; },
      advance: (data, id) => { calls.push({ advance: data, id }); return '<avans/>'; },
      translateError: error => error.message
    }
  };
  vm.createContext(context);
  vm.runInContext(extract('documentActionButtonsHtml') + '\n' + extract('exportSavedInvoiceXml'), context);
  return { context, messages, downloads, calls, modalStatus };
}
function invoice(payments = []) {
  return { id: 2, customer_id: 4, invoice_number: 'TA-26-0002', document_type: 'invoice',
    total_amount: 1000, remaining_amount: 700, current_customer_code: 'CURRENT',
    payload_json: JSON.stringify({ customer: { customer_code: 'OLD' } }), payments };
}
test('each invoice gets XML, every recorded advance gets its own button, proformas get none', () => {
  const { context } = setup([]);
  for (const source of ['history', 'customer', 'browser']) {
    const row = context.documentActionButtonsHtml(invoice([{ id: 11, advance_json: '{}' }, { id: 12, advance_json: '{}' }, { id: 13 }]), source, 3);
    assert(row.includes(`exportSavedInvoiceXml('${source}', 3, 0, this)`));
    assert(row.includes('Final XML'));
    assert(!row.includes('>Invoice XML</button>'));
    assert(row.includes('Avans XML 1'));
    assert(row.includes('Avans XML 2'));
    assert(row.includes(`exportSavedInvoiceXml('${source}', 3, 11, this)`));
    assert(row.includes(`exportSavedInvoiceXml('${source}', 3, 12, this)`));
    assert(!row.includes(`exportSavedInvoiceXml('${source}', 3, 13, this)`));
  }
  assert(!context.documentActionButtonsHtml({ document_type: 'proforma' }, 'history', 0).includes('exportSavedInvoiceXml'));
});
test('row downloads use refreshed data and latest customer code for the selected invoice', async () => {
  const row = invoice(), env = setup([row]), button = {};
  env.context.postJoomlaAjax = async () => ({ invoices: [{ ...row, payments: [{ id: 11, advance_json: '{}' }] }] });
  await env.context.exportSavedInvoiceXml('history', 0, 0, button);
  const data = env.calls.find(call => call.invoice).invoice;
  assert.equal(data.payments.length, 1);
  assert.equal(data.payload.customer.customer_code, 'CURRENT');
  assert.equal(data.invoice_total, 1000);
  assert.deepEqual(env.downloads, ['TA-26-0002-final.xml']);
  assert.equal(button.disabled, false);
  assert.equal(env.messages[0].type, 'success');
});
test('avans download selects the requested payment and uses a distinct filename', async () => {
  const env = setup([invoice([{ id: 11, advance_json: '{}' }])]);
  await env.context.exportSavedInvoiceXml('browser', 0, 11, {});
  assert.equal(env.calls.find(call => call.advance).id, 11);
  assert.deepEqual(env.downloads, ['TA-26-0002-AV-1.xml']);
  assert.match(env.modalStatus.textContent, /Avans XML exported/);
});
test('failed refresh prevents download, reports error and restores the button', async () => {
  const env = setup([invoice()]), button = {};
  env.context.postJoomlaAjax = async () => { throw new Error('Connection failed'); };
  await env.context.exportSavedInvoiceXml('history', 0, 0, button);
  assert.equal(env.downloads.length, 0);
  assert.equal(env.messages[0].type, 'danger');
  assert.equal(button.disabled, false);
});

test('unpaid and ordinary paid invoices retain whole Invoice XML without final or advance buttons', () => {
  const { context } = setup([]);
  for (const payments of [[], [{ id: 7, amount: 300 }], [{ id: 7, amount: 1000 }]]) {
    const row = context.documentActionButtonsHtml(invoice(payments), 'history', 0);
    assert(row.includes('>Invoice XML</button>'));
    assert(!row.includes('Final XML'));
    assert(!row.includes('Avans XML'));
  }
});
