# Third-Party Notices

This project includes or loads the following third-party software, fonts, data, and services.

## Bootstrap

- Use: UI CSS and JavaScript loaded from jsDelivr CDN in `route-calculation-help-for-accounting/media/calculator.html`.
- Version referenced: 5.3.3.
- License: MIT License.
- Source: https://getbootstrap.com/
- License information: https://getbootstrap.com/docs/5.3/about/license/

## jsPDF

- Use: PDF generation library loaded from cdnjs in `route-calculation-help-for-accounting/media/calculator.html`.
- Version referenced: 2.5.1.
- License: MIT License.
- Source: https://github.com/parallax/jsPDF
- License information: https://github.com/parallax/jsPDF/blob/master/LICENSE

## Noto Sans

- Use: Bundled fonts for PDF generation.
- Files:
  - `route-calculation-help-for-accounting/media/fonts/NotoSans-Regular.ttf`
  - `route-calculation-help-for-accounting/media/fonts/NotoSans-Bold.ttf`
  - `route-calculation-help-for-accounting/media/fonts/NotoSans-OFL-1.1.txt`
- License: SIL Open Font License 1.1.
- Source: https://github.com/googlefonts/noto-fonts
- Notice: The fonts may be bundled, embedded, redistributed, and sold with software under the OFL terms. The fonts must not be sold by themselves, and the OFL copyright and license notice must remain with redistributed copies.

## geoBoundaries Administrative Boundary Data

- Use: Country boundary polygons used for route/country distance splitting.
- Files:
  - `route-calculation-help-for-accounting/media/geoBoundaries-SVN-ADM0.geojson`
  - `route-calculation-help-for-accounting/media/slovenia_polygon_precise.js`
  - `route-calculation-help-for-accounting/media/geoBoundaries-ITA-ADM0_simplified.geojson`
  - `route-calculation-help-for-accounting/media/geoBoundaries-HRV-ADM0_simplified.geojson`
  - `route-calculation-help-for-accounting/media/geoBoundaries-AUT-ADM0_simplified.geojson`
  - `route-calculation-help-for-accounting/media/geoBoundaries-DEU-ADM0_simplified.geojson`
  - `route-calculation-help-for-accounting/media/geoBoundaries-HUN-ADM0.geojson`
- Source: https://www.geoboundaries.org/
- Source API: https://www.geoboundaries.org/api.html

Country/source license metadata from geoBoundaries should be reviewed when redistributing these files:

- Slovenia: Public Domain. Source metadata: geoBoundaries/Wikipedia, `gbOpen/SVN/ADM0`, boundary ID `SVN-ADM0-8885693`, source metadata URL `https://www.geoboundaries.org/api/current/gbOpen/SVN/ADM0/`.
- Italy: Creative Commons Attribution 3.0 License.
- Croatia: Open Data Commons Open Database License 1.0.
- Austria: Creative Commons Attribution-ShareAlike 2.0.
- Germany: Data license Germany - Attribution - Version 2.0.
- Hungary: CC0 1.0 Universal public domain dedication.

Attribution and share-alike/database-license requirements may apply depending on the country file. Keep the geoBoundaries source, original data source, license, and source URLs with redistributed copies.

## Google Maps Platform

- Use: Maps JavaScript API, Routes API, Places API, and optional Geocoding API access from `route-calculation-help-for-accounting/media/calculator.html`.
- Service URL used by the application: `https://maps.googleapis.com/maps/api/js`
- Terms: https://cloud.google.com/maps-platform/terms
- Notice: Google Maps Platform is a third-party service, not bundled project code. Users must provide their own Google Maps API key, enable the required APIs, configure billing if required by Google, and comply with Google Maps Platform terms, including restrictions on caching, copying, or deriving data from Google Maps content.

## Joomla

- Use: The project is a Joomla site module and installer plugin.
- License: Joomla is distributed under the GNU General Public License.
- Source: https://www.joomla.org/
- License information: https://docs.joomla.org/Joomla_Licenses
