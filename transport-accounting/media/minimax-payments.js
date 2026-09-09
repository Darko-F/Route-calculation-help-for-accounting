/* Minimax journal exports. GPL-2.0-or-later. Amounts are calculated in integer euro cents. */
(function (root) {
  'use strict';
  const ns = 'https://moj.minimax.si/SI/CommonWeb/documents/schemas/miniMAXUvozKnjigovodstvo';
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' }[c]));
  const tag = (name, value) => `<${name}>${escape(value)}</${name}>`;
  const cents = value => {
    if (!Number.isFinite(Number(value))) throw new Error('Invalid money amount.');
    return Math.round((Number(value) + 1e-9) * 100);
  };
  const money = value => (value / 100).toFixed(2);
  const code = rate => ({ 0: 'N', 5: '0', 9.5: 'Z', 22: 'S' })[Number(rate)];
  const required = (value, label, max = 30) => {
    value = String(value ?? '').trim();
    if (!value || value.length > max) throw Object.assign(new Error(`${label}: required, maximum ${max} characters.`), { minimaxLabel: label, minimaxMax: max });
    return value;
  };
  const date = value => {
    const s = String(value || '').slice(0, 10);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(s) || !Number.isFinite(Date.parse(s)) || new Date(s).toISOString().slice(0, 10) !== s) throw new Error('Invalid document date.');
    return s;
  };
  function customer(doc) {
    const c = doc.payload.customer || {};
    const id = required(c.customer_code || doc.customer_code, 'Customer code', 10);
    if (!/^[A-Za-z0-9#._-]+$/.test(id)) throw new Error('Invalid customer code.');
    const country = String(c.customer_country_code || 'SI').toUpperCase();
    if (!/^[A-Z]{2}$/.test(country)) throw new Error('Invalid customer country.');
    return { id, xml: '<Stranke><Stranka>' + tag('Sifra', id)
      + tag('Naziv', required(c.customer_name, 'Customer name', 250))
      + tag('Naslov', required(c.customer_address, 'Customer address', 250)) + tag('KraticaDrzave', country)
      + tag('PostnaStevilka', required(c.customer_postcode, 'Customer postcode'))
      + tag('NazivPoste', required(c.customer_city, 'Customer city', 250))
      + tag('DavcniZavezanec', c.vat_id ? 'D' : 'N') + (c.vat_id ? tag('IdentifikacijskaStevilka', required(c.vat_id, 'VAT ID', 14)) : '')
      + '</Stranka></Stranke>' };
  }
  function line(account, debit, credit, postingDate, description, customerId = '', reference = '', dueDate = postingDate) {
    return { debit, credit, xml: '<VrsticaTemeljnice>' + tag('DatumKnjizbe', postingDate)
      + tag('OpisVrsticeTemeljnice', description) + tag('SifraKonta', required(account, 'Konto'))
      + (customerId ? tag('SifraStranke', customerId) + tag('DatumZapadlosti', dueDate) + tag('DatumOpravljanja', postingDate) + tag('VezaZaPlacilo', required(reference, 'Payment reference')) : '')
      + tag('SifraDenarneEnote', 'EUR') + tag('ZnesekVBremeVDenarniEnoti', money(debit))
      + tag('ZnesekVDobroVDenarniEnoti', money(credit)) + tag('ZnesekVBremeVDomaciDenarniEnoti', money(debit))
      + tag('ZnesekVDobroVDomaciDenarniEnoti', money(credit)) + '</VrsticaTemeljnice>' };
  }
  function ddv(customerId, reference, postingDate, issueDate, amounts, advance = false) {
    const rates = Object.entries(amounts).filter(([, a]) => a.base || a.vat);
    if (!rates.length) return '';
    return '<DDV><DDVVrstica><DDVGlava>' + tag('DatumDDV', postingDate) + tag('KnjigaDDV', 'IR')
      + tag('VrstaObracunaDDV', 'PP') + tag('DatumKnjizenjaDDV', postingDate) + tag('SifraStranke', customerId)
      + tag('Listina', reference) + tag('DatumListine', issueDate)
      + (advance ? tag('DatumPlacila', postingDate) : '') + tag('DatumOpravljanja', postingDate)
      + (advance ? tag('Avans', 'D') : '') + tag('VrstaPorocanja', 'Obračun')
      + '</DDVGlava><DDVStopnje>' + rates.map(([rate, a]) => '<DDVStopnja>' + tag('SifraStopnjeDDV', rate)
        + tag('StoritevOsnova', money(a.base)) + tag('StoritevDDV', money(a.vat)) + '</DDVStopnja>').join('')
      + '</DDVStopnje></DDVVrstica></DDV>';
  }
  function wrap(c, reference, postingDate, tax, lines) {
    if (lines.reduce((sum, l) => sum + l.debit - l.credit, 0) !== 0) throw new Error('Minimax journal is not balanced.');
    return `<?xml version="1.0" encoding="utf-8"?>\n<miniMAXUvozKnjigovodstvo xmlns="${ns}" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="${ns} ${ns}.xsd">`
      + c.xml + '<Temeljnice><Temeljnica><GlavaTemeljnice>' + tag('SifraVrsteTemeljnice', 'IR')
      + tag('DatumTemeljnice', postingDate) + tag('OpisGlaveTemeljnice', reference) + '</GlavaTemeljnice>'
      + tax + '<VrsticeTemeljnice>' + lines.map(l => l.xml).join('') + '</VrsticeTemeljnice></Temeljnica></Temeljnice></miniMAXUvozKnjigovodstvo>';
  }
  function paymentNumber(doc, paymentId) {
    const payments = (doc.payments || []).filter(payment => payment.advance_json).sort((a, b) => Number(a.id) - Number(b.id));
    const index = payments.findIndex(p => Number(p.id) === Number(paymentId));
    if (index < 0) throw new Error('Advance not found.');
    return index + 1;
  }
  function paymentReference(doc, paymentId) {
    return required(`${doc.invoice_number}-AV-${paymentNumber(doc, paymentId)}`, 'Payment reference');
  }
  function advanceData(payment, invoiceNumber = '', sequence = 1) {
    const a = typeof payment.advance_json === 'string' ? JSON.parse(payment.advance_json || 'null') : payment.advance_json;
    if (!a || !code(a.rate)) throw new Error('Missing or invalid saved advance settings.');
    const gross = cents(payment.amount), base = cents(gross / 100 / (1 + Number(a.rate) / 100));
    if (gross <= 0) throw new Error('Advance must be positive.');
    if (!Number.isSafeInteger(Number(payment.id)) || Number(payment.id) < 1) throw new Error('Invalid advance ID.');
    required(a.advanceAccount, 'Advance konto');
    if (gross !== base) {
      required(a.vatAccount, 'Advance VAT konto'); required(a.clearingAccount, 'Advance VAT clearing konto');
      if (new Set([a.advanceAccount, a.vatAccount, a.clearingAccount]).size !== 3) throw new Error('Advance, VAT and VAT clearing accounts must differ.');
    }
    const reference = invoiceNumber ? required(`${invoiceNumber}-AV-${sequence}`, 'Payment reference') : '';
    return { ...a, gross, base, vat: gross - base, code: code(a.rate), reference,
      paymentReference: reference,
      date: date(payment.date || payment.payment_date) };
  }
  function advance(doc, paymentId) {
    const p = doc.payments.find(p => Number(p.id) === Number(paymentId));
    if (!p || !p.advance_json) throw new Error('Advance not found.');
    const a = advanceData(p, required(doc.invoice_number, 'Invoice number'), paymentNumber(doc, p.id)), c = customer(doc), lines = [];
    // The bank receipt is posted separately to the gross advance account.
    // This journal records advance VAT, using a separate VAT clearing account.
    if (a.vat) {
      lines.push(line(a.clearingAccount, a.vat, 0, a.date, a.reference));
      lines.push(line(a.vatAccount, 0, a.vat, a.date, a.reference));
    }
    return wrap(c, a.reference, a.date,
      ddv(c.id, a.reference, a.date, a.date, { [a.code]: { base: a.base, vat: a.vat } }, true), lines);
  }
  function invoice(doc, config, today) {
    const payload = doc.payload, d = payload.calculated_data || {}, c = customer(doc);
    const ref = required(doc.invoice_number, 'Invoice number');
    const segments = Array.isArray(d.countrySegments) ? d.countrySegments : [];
    const serviceDate = date(segments.map(s => s.serviceDate).filter(Boolean).sort().at(-1) || payload.service_date);
    const issueDate = date(payload.issue_date || doc.created_at), dueDate = date(payload.due_date || issueDate);
    const total = cents(doc.invoice_total), advances = doc.payments.filter(p => p.advance_json).map(p => advanceData(p, ref, paymentNumber(doc, p.id)));
    const grossAdvance = advances.reduce((sum, a) => sum + a.gross, 0);
    if (total <= 0 || grossAdvance > total || advances.some(a => a.date > serviceDate)) throw new Error('Invalid advance total or service date.');
    const revenue = {}, vat = {}, tax = {};
    const add = (map, account, amount) => { if (amount) { account = required(account, 'Konto'); map[account] = (map[account] || 0) + amount; } };
    const addTax = (rate, base, amount) => {
      if (!rate) throw new Error('Unsupported Minimax VAT rate.');
      const row = tax[rate] ||= { base: 0, vat: 0 }; row.base += base; row.vat += amount;
    };
    const baseCountry = String(d.baseCountry || config.baseCountry || 'SI').toUpperCase();
    const country = key => config.countryAccounts?.[key] || {};
    const baseAccounts = country(baseCountry);
    const baseRate = segments.find(s => (s.isBaseCountry ?? s.isSlovenia) && cents(s.base) > 0)?.vatRate ?? d.vatRate ?? 0;
    const baseAmount = cents(d.taxableBaseSlovenia || 0), baseVat = cents(d.vatAmount || 0);
    const baseCodes = new Set(segments.filter(s => (s.isBaseCountry ?? s.isSlovenia) && cents(s.base) > 0).map(s => code(s.vatRate)));
    if (baseCodes.size > 1) throw new Error('Mixed base-country VAT rates require separate invoice entries.');
    add(revenue, baseAccounts.revenueAccount, baseAmount);
    add(vat, baseCountry === 'SI' && code(baseRate) === 'S' ? config.baseCountryStandardVatAccount : baseAccounts.vatAccount, baseVat);
    if (baseAmount || baseVat) addTax(baseCountry === 'SI' ? code(baseRate) : 'N', baseAmount, baseCountry === 'SI' ? baseVat : 0);
    let foreign = segments.filter(s => !(s.isBaseCountry ?? s.isSlovenia) && cents(s.base) > 0);
    if (!foreign.length && cents(d.outsideSloveniaBase || 0)) foreign = [{ countryValue: 'OTHER', base: d.outsideSloveniaBase, vatAmount: d.outsideVatAmount }];
    for (const s of foreign) {
      const accounts = country(s.countryValue), base = cents(s.base), amount = cents(s.vatAmount || 0);
      add(revenue, accounts.revenueAccount || config.defaultForeignRevenueAccount, base); add(vat, accounts.vatAccount, amount); addTax('N', base, 0);
    }
    for (const cost of d.deductions || []) {
      if (cents(cost.amount || 0) <= 0) continue;
      const rateCode = cost.minimaxVatCode || code(cost.vatRate || 0);
      const rate = ({ S: 22, Z: 9.5, '0': 5, N: 0 })[rateCode];
      if (rate === undefined) throw new Error('Unsupported additional-cost VAT rate.');
      const gross = cents(cost.amount), base = cents(Number(cost.amount) / (1 + rate / 100)), amount = gross - base;
      add(revenue, config.additionalCostRevenueAccount || baseAccounts.revenueAccount, base);
      add(vat, rateCode === 'S' ? config.baseCountryStandardVatAccount : baseAccounts.vatAccount, amount);
      addTax(baseCountry === 'SI' ? rateCode : 'N', base, baseCountry === 'SI' ? amount : 0);
    }
    // Never silently absorb a material mismatch between the saved invoice and its lines.
    const credits = Object.values(revenue).reduce((s, a) => s + a, 0) + Object.values(vat).reduce((s, a) => s + a, 0);
    const diff = total - credits;
    if (Math.abs(diff) > 2) throw new Error('Saved invoice lines do not match the invoice total.');
    if (diff) {
      const account = Object.keys(revenue)[0], rate = Object.keys(tax)[0];
      if (!account || !rate) throw new Error('Missing invoice lines.');
      revenue[account] += diff; tax[rate].base += diff;
    }
    const lines = [];
    if (total > grossAdvance) lines.push(line(config.receivableAccount, total - grossAdvance, 0, serviceDate, ref, c.id, ref, dueDate));
    for (const a of advances) {
      // Match this gross debit against the separately imported bank receipt.
      lines.push(line(a.advanceAccount, a.gross, 0, serviceDate, `${ref} - ${a.reference}`, c.id, a.paymentReference, dueDate));
      if (a.vat) {
        lines.push(line(a.vatAccount, a.vat, 0, serviceDate, `${ref} - poračun DDV ${a.reference}`));
        lines.push(line(a.clearingAccount, 0, a.vat, serviceDate, `${ref} - poračun DDV ${a.reference}`));
      }
      addTax(a.code, -a.base, -a.vat);
    }
    for (const [account, amount] of Object.entries(revenue)) lines.push(line(account, 0, amount, serviceDate, `${ref} - storitev`));
    for (const [account, amount] of Object.entries(vat)) lines.push(line(account, 0, amount, serviceDate, `${ref} - DDV`));
    return wrap(c, ref, serviceDate, ddv(c.id, ref, serviceDate, issueDate, tax), lines);
  }
  function translateError(error, translate = message => message) {
    if (error.minimaxLabel) {
      return translate('{label}: required, maximum {max} characters.')
        .replace('{label}', translate(error.minimaxLabel)).replace('{max}', error.minimaxMax);
    }
    return translate(error.message);
  }
  root.TransportAccountingMinimax = { advance, invoice, advanceData, paymentNumber, paymentReference, translateError };
  if (typeof module !== 'undefined') module.exports = root.TransportAccountingMinimax;
})(typeof globalThis !== 'undefined' ? globalThis : this);
