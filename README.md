# Route calculation help for accounting

Route calculation help for accounting is a Joomla 6 site module for taxi and passenger transfer invoicing. It calculates route distance, estimates the split between the country of origin and foreign countries, prepares invoice text, and exports invoice data for PDF and accouting program as is Minimax XML workflows.

## Features

- Google Maps route calculation with pickup, drop-off, extra stops, and return trip support
- Country-of-origin / foreign-country kilometer split
- Gross price, net price, VAT, and taxable base calculation
- Saved customer details and reusable invoice history in Joomla database tables
- PDF invoice generation
- Minimax XML export
- Separate administrator component for paginated invoice and pro forma management
- Invoice payment tracking with unpaid, partially paid, and paid statuses
- Unicode payment-confirmation PDFs with payment history and remaining balance
- Full-value Minimax invoice exports that preserve correct receivables for partial-payment reconciliation
- Configurable company details, origin-country accounts, and foreign-country accounts
- English and Slovenian Joomla language files

## Demo

Demo: https://builder.topoweryou.com/routecalculationhelp/

## Requirements

- Joomla 6
- PHP version supported by Joomla 6
- MySQL or MariaDB supported by Joomla 6
- Google Maps JavaScript API key
- Google Maps APIs enabled for the key:
  - Maps JavaScript API
  - Routes API
  - Places API
  - Geocoding API, if using Google reverse geocoding split mode

## Updates

Subscribers receive extension updates through the Joomla updater. A subscriber
download key is required for private update downloads.

After purchase, install and enable the update-key plugin, then enter your
subscriber key in:

```text
System -> Manage -> Plugins -> Installer - Route Calculation Help update key
```

The plugin appends the subscriber key when Joomla downloads the combined suite
update package.

## Default Configuration

The included configuration is currently ready for Slovenia, Italy, Croatia, Austria, Germany, and Hungary, with editable base-country settings, foreign-country settings, VAT rates, PDF notes, and revenue accounts.

Additional countries can be added on demand when you need another border polygon, VAT setup, invoice text, or account mapping.

## Configuration on Demand

On request, we provide configuration adjusted to your needs. Get in touch at: https://topoweryou.com/services

## Installation

For a new installation, install the combined suite ZIP package in Joomla:

1. Go to Joomla Administrator.
2. Open System -> Install -> Extensions.
3. Upload `pkg_route_calculation_help_for_accounting_v1.6.0.zip`.
4. Open Content -> Site Modules.
5. Create or open Route calculation help for accounting.
6. Open Components -> Route calculation help -> Options, then enter the shared settings. Each module can independently choose global or module settings for Google Maps, company/PDF, Minimax, and countries.
7. Publish the module in the desired position.

The suite installs the site module, administrator-only document management
component, and subscriber update-key installer plugin. Enable the plugin and
enter the subscriber key before using protected updates. Install and update the
suite only through the combined package so all three extensions remain on the
same compatible release. Document management is available under Joomla
Administrator → Components → Route calculation help.

## Source Package

The source code is in:

```text
route-calculation-help-for-accounting/
```

The supported installable suite is distributed as:

```text
pkg_route_calculation_help_for_accounting_vx.x.x.zip
```

## Security

Do not commit real Google API keys. Configure the key in the Joomla component Options and restrict it in Google Cloud to your website domain.

## License

GNU General Public License version 2 or later. See `route-calculation-help-for-accounting/LICENSE.txt`.

Author: Darko Fatur

Copyright (C) 2026 topoweryou.com
