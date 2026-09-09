const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const exporter = require('../transport-accounting/media/minimax-payments.js');
const config = {
  receivableAccount: '1200', baseCountryStandardVatAccount: '26000',
  additionalCostRevenueAccount: '7602',
  countryAccounts: { SI: { revenueAccount: '7601', vatAccount: '26001' }, AT: { revenueAccount: '7620', vatAccount: '260AT' } }
};
const snapshot = { rate: 9.5, advanceAccount: '2308', vatAccount: '26001', clearingAccount: '295-test' };
function fixture() {
  return {
    invoice_number: 'TA-26-0001', invoice_total: 1095, created_at: '2026-09-01',
    payload: {
      issue_date: '2026-09-01', service_date: '2026-09-09', due_date: '2026-09-24',
      customer: { customer_code: 'C1', customer_name: 'Kupec & družba', customer_address: 'Ulica 1', customer_postcode: '1000', customer_city: 'Ljubljana', customer_country_code: 'SI' },
      calculated_data: { baseCountry: 'SI', taxableBaseSlovenia: 1000, vatAmount: 95, vatRate: 9.5, countrySegments: [], deductions: [] }
    },
    payments: [{ id: 17, date: '2026-09-02', amount: 219, advance_json: JSON.stringify(snapshot) }]
  };
}
const value = (xml, tag) => (xml.match(new RegExp(`<${tag}>(.*?)</${tag}>`)) || [])[1];
function lines(xml) {
  return [...xml.matchAll(/<VrsticaTemeljnice>(.*?)<\/VrsticaTemeljnice>/g)].map(([, row]) => ({
    account: value(row, 'SifraKonta'), reference: value(row, 'VezaZaPlacilo'),
    debit: Math.round(Number(value(row, 'ZnesekVBremeVDenarniEnoti')) * 100),
    credit: Math.round(Number(value(row, 'ZnesekVDobroVDenarniEnoti')) * 100)
  }));
}
const balance = xml => lines(xml).reduce((s, l) => s + l.debit - l.credit, 0);
const sumAccount = (xml, account) => lines(xml).filter(l => l.account === account).reduce((s, l) => s + l.debit - l.credit, 0);
test('advance records 19 DDV with avans flag and payment date, without re-posting bank receipt', () => {
  const xml = exporter.advance(fixture(), 17);
  assert.equal(value(xml, 'Avans'), 'D');
  assert.equal(value(xml, 'DatumDDV'), '2026-09-02');
  assert.equal(value(xml, 'Listina'), 'TA-26-0001-AV-1');
  assert.equal(value(xml, 'OpisGlaveTemeljnice'), 'TA-26-0001-AV-1');
  assert.equal(value(xml, 'OpisVrsticeTemeljnice'), 'TA-26-0001-AV-1');
  assert(!xml.includes('AV-17'));
  assert.equal(value(xml, 'StoritevOsnova'), '200.00');
  assert.equal(value(xml, 'StoritevDDV'), '19.00');
  assert.equal(balance(xml), 0);
  assert.equal(sumAccount(xml, '295-test'), 1900);
  assert.equal(sumAccount(xml, '26001'), -1900);
  assert.equal(lines(xml).length, 2);
  assert.match(xml, /Kupec &amp; družba/);
});
test('final invoice deducts gross advance and DDV, clears advance and VAT clearing accounts with bank receipt', () => {
  const doc = fixture(), advance = exporter.advance(doc, 17), xml = exporter.invoice(doc, config, '2026-09-10');
  assert.equal(value(xml, 'StoritevOsnova'), '800.00');
  assert.equal(value(xml, 'StoritevDDV'), '76.00');
  assert.equal(sumAccount(xml, '1200'), 87600);
  assert.equal(sumAccount(xml, '7601'), -100000);
  assert.equal(sumAccount(xml, '2308') - 21900, 0); // Bank receipt credit.
  assert.equal(sumAccount(xml, '295-test') + sumAccount(advance, '295-test'), 0);
  assert.equal(sumAccount(xml, '26001') + sumAccount(advance, '26001'), -9500);
  assert.equal(lines(xml).find(l => l.account === '2308').reference, 'TA-26-0001-AV-1');
  assert(!xml.includes('AV-17'));
  assert.equal(lines(xml).find(l => l.account === '1200').reference, 'TA-26-0001');
  assert.equal(balance(xml), 0);
  doc.payments.push({ id: 18, date: '2026-09-10', amount: 876, advance_json: null });
  assert.equal(exporter.invoice(doc, config, '2026-09-10'), xml, 'recording balance payment must not erase the receivable needed for bank matching');
});
test('full advance leaves no receivable or duplicate DDV on final invoice', () => {
  const doc = fixture(); doc.payments[0].amount = 1095;
  const xml = exporter.invoice(doc, config, '2026-09-09');
  assert.equal(sumAccount(xml, '1200'), 0);
  assert.equal(xml.includes('<DDV>'), false);
  assert.equal(balance(xml), 0);
});
test('overlong payment references are rejected instead of silently changing the unified name', () => {
  const doc = fixture(); doc.invoice_number = 'TA-2026-CUSTOMER-INVOICE-00001';
  assert.throws(() => exporter.advance(doc, 17), /maximum 30/);
});
test('advance names use invoice-local insertion order even when payments arrive sorted by date', () => {
  const doc = fixture();
  doc.payments.unshift({ ...doc.payments[0], id: 200, date: '2026-09-01' });
  const xml = exporter.advance(doc, 200);
  for (const field of ['OpisGlaveTemeljnice', 'Listina', 'OpisVrsticeTemeljnice']) {
    assert.equal(value(xml, field), 'TA-26-0001-AV-2');
  }
  assert.equal(exporter.paymentReference(doc, 17), 'TA-26-0001-AV-1');
});
test('multiple advances preserve saved rates and amounts, with cent rounding', () => {
  const doc = fixture(); doc.payments[0].amount = 300;
  doc.payments.push({ id: 18, date: '2026-09-03', amount: 122, advance_json: { ...snapshot, rate: 22, vatAccount: '26000' } });
  const a = exporter.advanceData(doc.payments[0]);
  assert.equal(a.base, 27397); assert.equal(a.vat, 2603);
  const xml = exporter.invoice(doc, { ...config, advanceVatRate: 0 }, '2026-09-10');
  assert.equal(sumAccount(xml, '1200'), 67300);
  assert.equal(sumAccount(xml, '2308'), 42200);
  assert.equal(balance(xml), 0);
  assert.match(xml, /<StoritevDDV>-22.00<\/StoritevDDV>/);
  assert.equal(value(exporter.advance(doc, 18), 'SifraStopnjeDDV'), 'S');
});
test('zero-rate advance and mixed foreign route plus gross additional costs', () => {
  const doc = fixture(); doc.payments[0].advance_json = { ...snapshot, rate: 0 };
  doc.invoice_total = 1327;
  doc.payload.calculated_data.outsideSloveniaBase = 100;
  doc.payload.calculated_data.outsideVatAmount = 10;
  doc.payload.calculated_data.countrySegments = [{ countryValue: 'AT', isBaseCountry: false, base: 100, vatAmount: 10 }];
  doc.payload.calculated_data.deductions = [{ amount: 122, minimaxVatCode: 'S', vatRate: 22 }];
  const xml = exporter.invoice(doc, config, '2026-09-10');
  assert.equal(sumAccount(xml, '7620'), -10000);
  assert.equal(sumAccount(xml, '260AT'), -1000);
  assert.equal(sumAccount(xml, '7602'), -10000);
  assert.equal(sumAccount(xml, '26000'), -2200);
  assert.equal(balance(xml), 0);
  assert.equal(lines(exporter.advance(doc, 17)).length, 0);
});
test('refuses invalid dates, invalid amounts, missing accounts and overpayment', () => {
  const doc = fixture();
  doc.payload.service_date = '2026-02-30';
  assert.throws(() => exporter.invoice(doc, config, '2026-09-08'), /Invalid document date/);
  doc.payload.service_date = '2026-09-09';
  assert.throws(() => exporter.invoice(doc, {...config, receivableAccount: ''}, '2026-09-10'), /Konto/);
  doc.payments[0].amount = 2000;
  assert.throws(() => exporter.invoice(doc, config, '2026-09-10'), /Invalid advance total/);
  doc.payments[0].amount = 'bad';
  assert.throws(() => exporter.advance(doc, 17), /Invalid money/);
  doc.payments[0].amount = 219; doc.invoice_total = 2000;
  assert.throws(() => exporter.invoice(doc, config, '2026-09-10'), /do not match/);
});
// Optional fixtures for XSD validation: MINIMAX_FIXTURES_DIR=/tmp/... node --test ...
if (process.env.MINIMAX_FIXTURES_DIR) {
  const dir = process.env.MINIMAX_FIXTURES_DIR; fs.mkdirSync(dir, {recursive:true});
  for (const rate of [0, 5, 9.5, 22]) {
    const doc = fixture(); doc.payments[0].advance_json = { ...snapshot, rate };
    fs.writeFileSync(path.join(dir, `advance-${rate}.xml`), exporter.advance(doc, 17));
    fs.writeFileSync(path.join(dir, `final-${rate}.xml`), exporter.invoice(doc, config, '2026-09-10'));
  }
  const doc = fixture(); doc.payments[0].amount = 1095;
  fs.writeFileSync(path.join(dir, 'final-fully-advanced.xml'), exporter.invoice(doc, config, '2026-09-10'));
}

test('ordinary payments do not consume advance numbers or change the final journal', () => {
  const doc = fixture();
  const before = exporter.invoice(doc, config, '2026-09-09');
  doc.payments.push({ id: 1, amount: 876, date: '2026-09-09' });
  assert.equal(exporter.paymentReference(doc, 17), 'TA-26-0001-AV-1');
  assert.equal(exporter.invoice(doc, config, '2026-09-09'), before);
});
test('without an advance the XML posts the full invoice even after full payment', () => {
  const doc = fixture(); doc.payments = [{ id: 1, amount: 1095, date: '2026-09-09' }];
  const xml = exporter.invoice(doc, config, '2026-09-09');
  assert.equal(sumAccount(xml, '1200'), 109500);
  assert.equal(sumAccount(xml, '2308'), 0);
  assert.equal(balance(xml), 0);
});

test('final XML can be generated before service, retaining service dates and advance deductions', () => {
  for (const advanceAmount of [219, 1095]) {
    const doc = fixture(); doc.payments[0].amount = advanceAmount;
    const xml = exporter.invoice(doc, config, '2026-09-03');
    assert.equal(xml, exporter.invoice(doc, config, '2026-09-10'));
    assert.equal(value(xml, 'DatumTemeljnice'), '2026-09-09');
    assert.equal(sumAccount(xml, '1200'), Math.round((1095 - advanceAmount) * 100));
    assert.equal(balance(xml), 0);
  }
});
