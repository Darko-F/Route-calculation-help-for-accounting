# Road Fare Bill Helper

Road Fare Bill Helper is a Joomla 6 site module for taxi and passenger transfer invoicing. It calculates route distance, estimates the split between the country of origin and foreign countries, prepares invoice text, and exports invoice data for PDF and Minimax XML workflows.

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

Demo: https://builder.topoweryou.com/roadbillfarehelper/

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

## Default Configuration

The included default configuration is prepared for Slovenia-based invoicing, with editable settings for foreign countries and revenue accounts. You can adapt the labels, accounts, VAT rates, and country split rules for another country of origin.

## Installation

Install the ZIP package in Joomla:

1. Go to Joomla Administrator.
2. Open System -> Install -> Extensions.
3. Upload the module ZIP package.
4. Open Content -> Site Modules.
5. Create or open Road Fare Bill Helper.
6. Enter the Google Maps API key and company settings.
7. Publish the module in the desired position.

## Source Package

The source code is in:

```text
joomla-mod-road-fare-bill-helper/
```

The installable ZIP package is distributed separately.

## Security

Do not commit real Google API keys. Configure the key in the Joomla module options and restrict it in Google Cloud to your website domain.

## License

GNU General Public License version 2 or later. See `joomla-mod-road-fare-bill-helper/LICENSE.txt`.

Copyright (C) 2026 topoweryou.com
