# Avans and final-invoice XML

An advance is money received before the transport is performed. Record it with
**Payment type → Avans** in Components → Transport Accounting. The amount entered
includes DDV. The default advance DDV rate is **9.5%**; Options and the payment form
allow 0%, 5%, 9.5% and 22%. Existing payment records remain ordinary payments.

## Settings

In component Options → Minimax, configure:

- **Received advances account**: defaults to `2308`, Minimax's account for advances
  managed as open items. Use the same account when booking the advance bank receipt.
- **VAT liability account for advances**: your output-DDV account for the selected
  advance rate.
- **Advance VAT clearing account**: your separate account for DDV on advances.
  The advance debits this account; the final invoice credits it. It must differ
  from the received-advance and output-DDV accounts. No account number is guessed.
- Existing customer receivable, revenue, country VAT and additional-cost accounts
  must also be configured for the final invoice.

The rate and three advance accounts are saved with each payment. Subsequent
settings changes do not change that advance or its reversal. The administrator
final export uses global Minimax accounts; the calculator uses its configured
global/module accounts for the service, while preserving the advance snapshots.

## Workflow

1. Save the invoice with its service date and customer details. Existing pro forma
   documents must be converted to an invoice before recording payments.
2. Record the received amount as **Avans**, using its actual payment date. A future
   payment date or a date after the first service is rejected. For a same-day
   payment, select Avans only if received before the service; dates have no time
   of day. Multiple advances are supported, up to the invoice's gross total.
3. Download **AV XML 1**, **AV XML 2**, etc. (both languages) and import it through Minimax **Uvoz iz XML**.
   This is an `IR` journal with `DDVGlava/Avans=D`, the advance date, taxable base
   and DDV. Its accounting lines record advance DDV against the clearing account.
   It does not duplicate the bank receipt or recognize service revenue.
4. In Minimax, book the advance bank receipt against the received-advance account.
   Use `(invoice number)-AV-(payment sequence)` as the matching reference, as written in
   the XML. Each button downloads its corresponding recorded advance.
5. Download **Zaključni XML** (Slovenian) / **Final XML** (English). It recognizes
   the full service revenue, deducts the gross advances from the customer
   receivable, reverses their saved DDV postings, and reports the difference in
   DDV bases/tax. It debits each gross advance on its saved account with `(invoice number)-AV-(payment sequence)`
   so that the bank advance can be closed. A fully advanced invoice has no
   remaining customer receivable; zero net DDV rows are omitted.
6. Record the remaining payment as **Payment after service**. Match that bank
   receipt in Minimax against the invoice number. The final XML still contains
   the post-advance receivable, even if locally marked paid, so it can be matched.

The front-page invoice history, full customer list and invoice browser also offer
**Invoice XML** on invoices without advances and **AV XML 1**, **AV XML 2**, etc. (both languages) for each recorded advance.
Invoices with advances show a separate **Zaključni XML** / **Final XML** button that includes all saved advance deductions. Ordinary payments after service do not create additional advance XML buttons. Downloads refresh the
saved payment data and leave the currently open calculator/draft unchanged.
Ordinary partial payments are not reclassified as advances; pro formas have no
XML buttons until converted to invoices.

For example, a EUR 1,095 invoice (EUR 1,000 base + EUR 95 DDV) with a EUR 219 advance
at 9.5% records EUR 19 advance DDV. The final XML reports EUR 800 base and EUR 76
DDV, with EUR 876 customer receivable and EUR 219 advance to clear. Across both
journals the revenue is EUR 1,000 and output DDV is EUR 95.

Import each advance XML and the final XML **once**. Re-downloading does not create
a correction, and the application does not know whether Minimax has imported a
file. Do not also import the original full invoice. Existing imports or incorrectly
classified historical payments need reconciliation before using this workflow.
Invoices with recorded advances cannot be edited in the calculator, to preserve
their accounting basis. Downloads reload payment data to avoid stale browser data.

## Verification and limits

Run `node tests/minimax-payments.test.cjs` and `php tests/record-advance.php`.
For XML fixtures, set `MINIMAX_FIXTURES_DIR=/tmp/minimax-fixtures` when running the
JavaScript tests, then validate those files with the official XSD below.

Tests cover balanced journals, partial/full/multiple advances, configurable rates,
cent rounding, additional costs including DDV, foreign-country amounts, saved
settings, invalid dates/accounts and overpayments. Sample advance/final documents
for all supported rates were validated against the downloaded official XSD.
The changes have not been tested in a live Joomla installation or imported into
a live Minimax organisation. Schema validity does not establish account setup or
automatic matching in a particular organisation. The journals supply matching
accounts/references; reconciliation happens in Minimax.

## Official references reviewed

- [Advance invoices](https://help.minimax.si/help/izdani-racun-za-predplacilo)
- [Final invoice based on an advance](https://help.minimax.si/help/izdani-koncni-racun-na-podlagi-predplacila)
- [Organisation settings and advance accounts](https://help.minimax.si/help/nastavitve-organizacije-osnovne-nastavitve)
- [Journal XML import](https://help.minimax.si/help/priprava-datoteke-xml-za-uvoz-temeljnic-izdanih-racunov)
- [Official journal-import XSD](https://moj.minimax.si/SI/CommonWeb/documents/schemas/miniMAXUvozKnjigovodstvo.xsd)

The XSD defines `Avans` as a one-character field and supports `VezaZaPlacilo` on
journal lines. It does not enumerate the accepted values of the Avans flag;
`D` follows Minimax's affirmative-value convention and requires live-import
verification. Cross-file automatic `Zapiranja` are not generated, since Minimax
journal-entry identifiers are not available in this application.

Advance journal header, Listina, both VAT line descriptions and the filename use the same invoice-local reference, e.g. `TA-26-0007-AV-1` (`.xml` for the file). Sequence follows advance insertion ID order within the invoice (ordinary payments are excluded), not the global payment ID or editable payment date. References longer than the Minimax 30-character matching-reference limit are rejected without truncation. Existing imports with previous references must not be imported again under the new names.

Final XML export is also available before the service date, including when payment is received in full beforehand. The export retains the saved service and posting dates and deducts all recorded advances.
