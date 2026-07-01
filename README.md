# Route calculation help for accounting

Route calculation help for accounting is a Joomla 6 site module for taxi and passenger transfer invoicing. It calculates route distance, estimates the split between the country of origin and foreign countries, prepares invoice text, and exports invoice data for PDF and accouting program as is Minimax XML workflows.

## Features

- Google Maps route calculation with pickup, drop-off, extra stops, and return trip support
- Country-of-origin / foreign-country kilometer split
- Gross price, net price, VAT, and taxable base calculation
- Saved customer details and reusable invoice history in Joomla database tables
- PDF invoice generation
- Minimax XML export
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

## Automatic Updates for Subscribers

The module manifest includes this Joomla update server:

```text
https://builder.topoweryou.com/routecalculationhelp/updates/route-calculation-help-for-accounting.xml
```

Private update downloads use the separate installer plugin:

```text
plg_installer_routecalculationupdatekey_vx.x.x.zip
```

The plugin can be installed before the customer has a subscription key. Install
and enable the plugin with an empty key, then enter the subscriber download key
after purchase in:

```text
System -> Manage -> Plugins -> Installer - Route Calculation Help update key
```

The plugin appends the key when Joomla downloads this module's update package.
The plugin also has its own update server:

```text
https://builder.topoweryou.com/routecalculationhelp/updates/routecalculationupdatekey.xml
```

On the update server, `download.php` validates package names by pattern and reads
allowed subscriber key hashes from either:

```text
DOWNLOAD_KEY_HASHES
download-keys.local.php
```

Use `download-keys.example.php` as the template for the local file. Keep
`download-keys.local.php` private; it is ignored by Git. Upload module and plugin
release zips into the server `downloads/` directory.

## Default Configuration

The included default configuration is prepared for Slovenia-based invoicing, with editable settings for foreign countries and revenue accounts. You can adapt the labels, accounts, VAT rates, and country split rules for another country of origin.

## Configuration on Demand

On request, we provide configuration adjusted to your needs. Get in touch at: https://topoweryou.com/services

## Installation

Install the ZIP package in Joomla:

1. Go to Joomla Administrator.
2. Open System -> Install -> Extensions.
3. Upload the module ZIP package.
4. Open Content -> Site Modules.
5. Create or open Route calculation help for accounting.
6. Enter the Google Maps API key and company settings.
7. Publish the module in the desired position.

## Source Package

The source code is in:

```text
route-calculation-help-for-accounting/
```

The installable ZIP package is distributed separately as:

```text
route_calculation_help_for_accounting_vx.x.xx.zip
```

## Security

Do not commit real Google API keys. Configure the key in the Joomla module options and restrict it in Google Cloud to your website domain.

## License

GNU General Public License version 2 or later. See `route-calculation-help-for-accounting/LICENSE.txt`.

Author: Darko Fatur

Copyright (C) 2026 topoweryou.com
