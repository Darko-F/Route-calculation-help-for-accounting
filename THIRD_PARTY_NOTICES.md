# Third-Party Notices

This project includes or loads the following third-party software, fonts, data, and services.

## Bootstrap

- Use: UI CSS and JavaScript bundled in `transport-accounting/media/vendor/bootstrap/`.
- Version: 5.3.3.
- License: MIT License.
- Bundled license: `transport-accounting/media/vendor/bootstrap/LICENSE`.
- Source: https://getbootstrap.com/
- License information: https://getbootstrap.com/docs/5.3/about/license/

## jsPDF

- Use: PDF generation library bundled in `transport-accounting/media/vendor/jspdf/`.
- Version: 2.5.1.
- License: MIT License.
- Bundled license: `transport-accounting/media/vendor/jspdf/LICENSE`.
- Source: https://github.com/parallax/jsPDF
- License information: https://github.com/parallax/jsPDF/blob/master/LICENSE

## Noto Sans

- Use: Bundled fonts for PDF generation.
- Files:
  - `transport-accounting/media/fonts/NotoSans-Regular.ttf`
  - `transport-accounting/media/fonts/NotoSans-Bold.ttf`
  - `transport-accounting/media/fonts/NotoSans-OFL-1.1.txt`
- License: SIL Open Font License 1.1.
- Source: https://github.com/googlefonts/noto-fonts
- Notice: The fonts may be bundled, embedded, redistributed, and sold with software under the OFL terms. The fonts must not be sold by themselves, and the OFL copyright and license notice must remain with redistributed copies.

## Slovenia National Boundary — GURS

- Provider: Surveying and Mapping Authority of the Republic of Slovenia (GURS).
- Dataset: National boundary polygon, land and sea (`državna meja — kopno in morje`), derived by the provider from `DTM_AU_DRZAVNAMEJA_L`.
- Source database timestamp: 2025-11-24T23:32:18Z. Downloaded: 2026-09-09.
- Source: https://geohub.gov.si/ags/rest/services/TEMELJNE_VSEBINE/GH_SLO_MEJA/MapServer/1466
- Terms: https://www.e-prostor.gov.si/en/access-to-geodetic-data/
- License: Creative Commons Attribution 4.0 International (CC BY 4.0), https://creativecommons.org/licenses/by/4.0/
- Files: `transport-accounting/media/gurs-slovenia-boundary.geojson` and `transport-accounting/media/slovenia_boundary_gurs.js`.
- Changes: requested WGS84/EPSG:4326 coordinates from the source service, reduced feature metadata, and reordered coordinates to `[lat, lng]` in the JavaScript fallback. All 31,071 vertices and both interior rings are retained; no simplification or border buffer was applied.
- Attribution is also embedded in both data files and displayed below the calculator map.

## geoBoundaries Administrative Boundary Data

- Use: Country boundary polygons used for route/country distance splitting.
- Files:
  - `transport-accounting/media/geoBoundaries-ITA-ADM0_simplified.geojson`
  - `transport-accounting/media/geoBoundaries-HRV-ADM0_simplified.geojson`
  - `transport-accounting/media/geoBoundaries-AUT-ADM0_simplified.geojson`
  - `transport-accounting/media/geoBoundaries-DEU-ADM0_simplified.geojson`
  - `transport-accounting/media/geoBoundaries-HUN-ADM0.geojson`
- Source: https://www.geoboundaries.org/
- Source API: https://www.geoboundaries.org/api.html

Country/source license metadata from geoBoundaries should be reviewed when redistributing these files:

- Italy: Creative Commons Attribution 3.0 License.
- Croatia: Open Data Commons Open Database License 1.0.
- Austria: Creative Commons Attribution-ShareAlike 2.0.
- Germany: Data license Germany - Attribution - Version 2.0.
- Hungary: CC0 1.0 Universal public domain dedication.

Attribution and share-alike/database-license requirements may apply depending on the country file. Keep the geoBoundaries source, original data source, license, and source URLs with redistributed copies.

## Google Maps Platform

- Use: Maps JavaScript API, Routes API, Places API, and optional Geocoding API access from `transport-accounting/media/calculator.html`.
- Service URL used by the application: `https://maps.googleapis.com/maps/api/js`
- Terms: https://cloud.google.com/maps-platform/terms
- Notice: Google Maps Platform is a third-party service, not bundled project code. Users must provide their own Google Maps API key, enable the required APIs, configure billing if required by Google, and comply with Google Maps Platform terms, including restrictions on caching, copying, or deriving data from Google Maps content.

## Joomla

- Use: The project is a Joomla site module and installer plugin.
- License: Joomla is distributed under the GNU General Public License.
- Source: https://www.joomla.org/
- License information: https://docs.joomla.org/Joomla_Licenses
