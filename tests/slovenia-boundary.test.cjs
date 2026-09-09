const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const media = path.join(__dirname, '../transport-accounting/media');
const html = fs.readFileSync(path.join(media, 'calculator.html'), 'utf8');
const boundary = JSON.parse(fs.readFileSync(path.join(media, 'gurs-slovenia-boundary.geojson'), 'utf8'));
function extract(name) {
  const start = html.search(new RegExp(`(?:async )?function ${name}\\(`));
  assert(start >= 0, name);
  const end = html.slice(start + 1).search(/\n(?:async )?function \w+\(/);
  return html.slice(start, start + 1 + end);
}
function setup(failed = [], bundled = true) {
  function LatLng(lat, lng) { this.lat = () => lat; this.lng = () => lng; }
  const rad = x => x * Math.PI / 180;
  const context = {
    console: { warn() {} }, t: text => text,
    fetch: async url => {
      const name = url.split('?')[0];
      if (failed.includes(name) || failed.includes('*')) throw new Error('Simulated fetch failure');
      return { ok: true, json: async () => JSON.parse(fs.readFileSync(path.join(media, name), 'utf8')) };
    },
    google: { maps: { LatLng, geometry: { spherical: {
      computeDistanceBetween(a, b) {
        const dlat = rad(b.lat() - a.lat()), dlng = rad(b.lng() - a.lng());
        return 6371000 * 2 * Math.asin(Math.sqrt(Math.sin(dlat / 2) ** 2 + Math.cos(rad(a.lat())) * Math.cos(rad(b.lat())) * Math.sin(dlng / 2) ** 2));
      },
      interpolate: (a, b, f) => new LatLng(a.lat() + f * (b.lat() - a.lat()), a.lng() + f * (b.lng() - a.lng()))
    } } } }
  };
  vm.createContext(context);
  vm.runInContext(fs.readFileSync(path.join(media, 'slovenia_boundary_gurs.js'), 'utf8'), context);
  vm.runInContext(`const SLOVENIA_BOUNDARIES = ${bundled ? 'SLOVENIA_GURS_POLYGONS' : '[]'};
    const polygonBoundsCache = new WeakMap(); let countryBoundaryPolygons = {}; let countryBoundaryPromise = null;
    ${html.match(/const COUNTRY_GEOJSON_FILES = \{[\s\S]*?\n\};/)[0]}
    ${['loadCountryBoundaries', 'extractGeoJsonPolygons', 'pointInPolygon', 'pointInBoundaryPolygons', 'getCountryCodeForPoint', 'isPointInSloveniaPolygon', 'isPointInDomesticSloveniaPolygon', 'estimateCountryMetersFast', 'estimateSloveniaMetersFast', 'toLatLng', 'getPointLat', 'getPointLng'].map(extract).join('\n')}`, context);
  return context;
}
test('full source precision and all holes are identical in primary and fallback data', () => {
  const context = setup(), actual = JSON.parse(vm.runInContext('JSON.stringify(SLOVENIA_GURS_POLYGONS)', context));
  const rings = boundary.features[0].geometry.coordinates;
  assert.equal(rings.length, 3);
  assert.equal(rings.reduce((s, r) => s + r.length, 0), 31071);
  assert.deepEqual(actual, [rings.map(r => r.map(([lng, lat]) => [lat, lng]))]);
  assert.equal(boundary.attribution.license, 'CC BY 4.0');
});
test('Koper, the port, Izola, Piran and Ljubljana classify as Slovenia; neighbouring cities do not', async () => {
  const context = setup(); await context.loadCountryBoundaries();
  for (const [name, lat, lng] of [['Koper centre',45.5481,13.7302],['Koper railway',45.539,13.737],['Koper port',45.560,13.743],['Izola',45.536,13.66],['Piran',45.528,13.568],['Ljubljana',46.0569,14.5058]]) {
    assert.equal(context.getCountryCodeForPoint(lat,lng),'SI',name);
    assert.equal(context.isPointInDomesticSloveniaPolygon(lat,lng),true,name);
  }
  for (const [name, lat, lng] of [['Trieste',45.6495,13.7768],['Zagreb',45.815,15.982],['Graz',47.071,15.439]]) {
    assert.notEqual(context.getCountryCodeForPoint(lat,lng),'SI',name);
    assert.equal(context.isPointInSloveniaPolygon(lat,lng),false,name);
  }
});
test('polygon and multipolygon holes remain excluded, rather than becoming separate country polygons', () => {
  const context = setup();
  const outer = [[0,0],[10,0],[10,10],[0,10],[0,0]], hole = [[3,3],[7,3],[7,7],[3,7],[3,3]];
  for (const geometry of [{ type:'Polygon',coordinates:[outer,hole] },{ type:'MultiPolygon',coordinates:[[outer,hole]] }]) {
    const polygons = context.extractGeoJsonPolygons(geometry);
    assert.equal(context.pointInBoundaryPolygons(1,1,polygons),true);
    assert.equal(context.pointInBoundaryPolygons(5,5,polygons),false);
    assert.equal(context.pointInBoundaryPolygons(12,12,polygons),false);
  }
});
test('sampled Ljubljana–Koper corridor stays fully Slovenian in Fast and fallback modes', async () => {
  const context = setup();
  // Representative corridor points, not a recorded Google driving route.
  const route = [[46.0569,14.5058],[45.811,14.31],[45.709,14.181],[45.683,14.016],[45.57,13.935],[45.552,13.886],[45.549,13.817],[45.547,13.761],[45.5481,13.7302]].map(([lat,lng]) => ({lat,lng}));
  const rows = await context.estimateCountryMetersFast(route,500);
  assert.equal(rows.length,1); assert.equal(rows[0].countryCode,'SI');
  assert(Math.abs(context.estimateSloveniaMetersFast(route,500)-rows[0].meters) < 0.001);
});
test('failed primary Slovenia fetch uses the same GURS boundary even if other countries load', async () => {
  const context = setup(['gurs-slovenia-boundary.geojson']); await context.loadCountryBoundaries();
  assert.equal(context.getCountryCodeForPoint(45.5481,13.7302),'SI');
});
test('bundled fallback survives all fetch failures, and missing fallback can use primary data', async () => {
  const fallback = setup(['*']); await fallback.loadCountryBoundaries();
  assert.equal(fallback.getCountryCodeForPoint(45.5481,13.7302),'SI');
  const primary = setup([],false); await primary.loadCountryBoundaries();
  assert.equal(primary.isPointInSloveniaPolygon(45.5481,13.7302),true);
  const missing = setup(['*'],false); await missing.loadCountryBoundaries();
  assert.throws(() => missing.estimateSloveniaMetersFast([],500), /could not be loaded/);
});
