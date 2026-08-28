"""Regenerate all Tawi-Tawi data files with a single coord convention.

Convention:
  - School (BBNIHS, lat=5.07401, lon=119.88748) is the origin (0, 0).
  - Every feature uses METERS from the school, in (x, z) tuples where:
      +x = east (positive longitude direction)
      +z = south (positive latitude direction, in local-tangent plane)
  - School itself is at (0, 0).

This is the "single coordinate reference" the spec asks for.
"""
import json
import math

# School origin (from terrain.json, OSM way 616063878)
SCHOOL_LAT = 5.0740123
SCHOOL_LON = 119.8874807
M_PER_LAT = 111320


def project_to_xz(lat, lon):
    """Project (lat, lon) to (x, z) meters from school.
    +x = east, +z = south.
    """
    m_per_lon = 111320 * math.cos(math.radians(SCHOOL_LAT))
    x = (lon - SCHOOL_LON) * m_per_lon
    z = (lat - SCHOOL_LAT) * M_PER_LAT  # +z = +lat = south in this projection
    return x, z


# ---- Load raw data ----
print('Loading raw OSM exports...')
with open('coastlines-raw.json') as f:
    cw = json.load(f)
with open('roads-raw.json') as f:
    roads_raw = json.load(f)
with open('landuse-raw.json') as f:
    landuse_raw = json.load(f)
with open('school-raw.json') as f:
    school_raw = json.load(f)

# Build global node index
node_index = {}
for src in [cw, roads_raw, landuse_raw, school_raw]:
    for el in src.get('elements', []):
        if el['type'] == 'node' and 'lat' in el:
            node_index[el['id']] = (el['lat'], el['lon'])
ways_by_id = {}
for src in [cw, roads_raw, landuse_raw]:
    for el in src.get('elements', []):
        if el['type'] == 'way':
            ways_by_id[el['id']] = el
print(f'  nodes: {len(node_index)}, ways: {len(ways_by_id)}')

# ---- 1. School ----
print('\n1. school.json')
school_marker = {
    'name': 'Batu-batu National High School',
    'ref': '305053',
    'start_date': '1966',
    'operator': 'Panglima Sugala District of Tawi-Tawi Schools Division, DepEd',
    'lat': SCHOOL_LAT,
    'lon': SCHOOL_LON,
    'x': 0.0,
    'z': 0.0,
}
with open('school.json', 'w') as f:
    json.dump(school_marker, f, indent=1)
print(f'  saved school.json: {school_marker["name"]} ref={school_marker["ref"]}')

# ---- 2. Islands (coastlines) ----
# Group connected ways into components, simplify, project to school-local xz
print('\n2. islands.json')
from collections import defaultdict
node_to_ways = defaultdict(list)
for w in ways_by_id.values():
    for nid in w.get('nodes', []):
        node_to_ways[nid].append(w['id'])
visited = set()
components = []
for w in ways_by_id.values():
    if w['id'] in visited: continue
    comp = []
    stack = [w['id']]
    while stack:
        wid = stack.pop()
        if wid in visited: continue
        visited.add(wid)
        comp.append(wid)
        for nid in ways_by_id[wid].get('nodes', []):
            for nb in node_to_ways.get(nid, []):
                if nb not in visited: stack.append(nb)
    if len(comp) > 1:
        components.append(comp)
print(f'  connected way components: {len(components)}')

islands = []
for i, comp in enumerate(components):
    pts = set()
    for wid in comp:
        for nid in ways_by_id[wid].get('nodes', []):
            if nid in node_index:
                pts.add(node_index[nid])
    pts = list(pts)
    if len(pts) < 6: continue
    # Convert each (lat, lon) point to (x, z) in school-local space
    xz = [project_to_xz(lat, lon) for (lat, lon) in pts]
    # Compute centroid
    cx = sum(p[0] for p in xz) / len(xz)
    cz = sum(p[1] for p in xz) / len(xz)
    # Translate to be centroided
    centered = [(p[0] - cx, p[1] - cz) for p in xz]
    # Sort by angle around centroid
    centered.sort(key=lambda p: math.atan2(p[1], p[0]))
    # Simplify: take every Nth
    target = min(18, max(8, len(centered) // 4))
    step = max(1, len(centered) // target)
    simplified = centered[::step]
    if len(simplified) < 6:
        simplified = centered
    poly = [[round(p[0], 1), round(p[1], 1)] for p in simplified]
    if poly and poly[0] != poly[-1]:
        poly.append(poly[0])
    # Compute extent
    extent = max((abs(p[0]) for p in poly), default=0) + max((abs(p[1]) for p in poly), default=0)
    islands.append({
        'id': f'island_{i}',
        'centroid_x': round(cx, 1),
        'centroid_z': round(cz, 1),
        'extent_m': round(extent, 0),
        'vertices': poly,  # island-local xz, centroided to 0,0
    })
islands.sort(key=lambda x: -x['extent_m'])
with open('islands.json', 'w') as f:
    json.dump({
        'school': {'lat': SCHOOL_LAT, 'lon': SCHOOL_LON},
        'bbox': {'S': 4.9, 'W': 119.7, 'N': 5.3, 'E': 120.0},
        'coordinateSystem': 'local-tangent-plane',
        'units': 'meters',
        'origin': 'BBNIHS (0, 0)',
        'convention': 'x=east, z=south (positive latitude)',
        'islands': islands,
        'source': 'OpenStreetMap (overpass-api.de) - natural=coastline',
        'attribution': 'Map data (c) OpenStreetMap contributors',
    }, f, indent=1)
print(f'  saved islands.json: {len(islands)} islands')

# ---- 3. Roads ----
print('\n3. roads.json')
roads = []
for el in roads_raw['elements']:
    if el['type'] != 'way': continue
    tags = el.get('tags', {})
    hw = tags.get('highway', 'unclassified')
    if hw not in ('residential', 'tertiary', 'secondary', 'unclassified', 'service', 'path', 'footway', 'track', 'primary'):
        continue
    coords_xz = []
    for nid in el.get('nodes', []):
        if nid in node_index:
            x, z = project_to_xz(*node_index[nid])
            coords_xz.append([round(x, 0), round(z, 0)])
    if len(coords_xz) >= 2:
        roads.append({
            'type': hw,
            'name': tags.get('name', None),
            'ref': tags.get('ref', None),
            'coords': coords_xz,  # school-local xz
        })
with open('roads.json', 'w') as f:
    json.dump({
        'school': {'lat': SCHOOL_LAT, 'lon': SCHOOL_LON},
        'coordinateSystem': 'local-tangent-plane',
        'units': 'meters',
        'origin': 'BBNIHS (0, 0)',
        'convention': 'x=east, z=south (positive latitude)',
        'roads': roads,
        'source': 'OpenStreetMap (overpass-api.de) - highway=*',
        'attribution': 'Map data (c) OpenStreetMap contributors',
    }, f, indent=1)
print(f'  saved roads.json: {len(roads)} ways')

# ---- 4. Landuse / natural ----
print('\n4. landuse.json')
zones = []
for el in landuse_raw['elements']:
    if el['type'] != 'way': continue
    tags = el.get('tags', {})
    lu = tags.get('landuse', '')
    nat = tags.get('natural', '')
    if lu == 'residential':
        kind = 'residential'
    elif nat == 'water':
        kind = 'water'
    elif nat == 'coastline':
        continue  # already in islands.json
    else:
        continue
    coords_xz = []
    for nid in el.get('nodes', []):
        if nid in node_index:
            x, z = project_to_xz(*node_index[nid])
            coords_xz.append([round(x, 0), round(z, 0)])
    if len(coords_xz) >= 3:
        # Simplify
        if len(coords_xz) > 20:
            step = len(coords_xz) // 20
            coords_xz = coords_xz[::step]
        if coords_xz[0] != coords_xz[-1]:
            coords_xz.append(coords_xz[0])
        zones.append({
            'kind': kind,
            'polygon': coords_xz,  # school-local xz
        })
with open('landuse.json', 'w') as f:
    json.dump({
        'school': {'lat': SCHOOL_LAT, 'lon': SCHOOL_LON},
        'coordinateSystem': 'local-tangent-plane',
        'units': 'meters',
        'origin': 'BBNIHS (0, 0)',
        'convention': 'x=east, z=south (positive latitude)',
        'zones': zones,
        'source': 'OpenStreetMap (overpass-api.de) - landuse=*, natural=*',
        'attribution': 'Map data (c) OpenStreetMap contributors',
    }, f, indent=1)
print(f'  saved landuse.json: {len(zones)} zones')

# ---- 5. meta.json ----
print('\n5. meta.json')
# Compute extents
def xz_extent(records, key_path):
    xs, zs = [], []
    for r in records:
        v = r
        for k in key_path:
            v = v[k]
        for p in v:
            xs.append(p[0])
            zs.append(p[1])
    if not xs:
        return None
    return {
        'min_x': min(xs), 'max_x': max(xs),
        'min_z': min(zs), 'max_z': max(zs),
    }

roads_ext = xz_extent(roads, ['coords'])
landuse_ext = xz_extent(zones, ['polygon'])
# Islands: vertices in island-local space, so use centroid extents
island_cx = [i['centroid_x'] for i in islands]
island_cz = [i['centroid_z'] for i in islands]
islands_ext = {
    'min_x': min(island_cx), 'max_x': max(island_cx),
    'min_z': min(island_cz), 'max_z': max(island_cz),
} if islands else None

meta = {
    'version': 1,
    'source': 'OpenStreetMap via Overpass API',
    'school': {'lat': SCHOOL_LAT, 'lon': SCHOOL_LON, 'name': school_marker['name'], 'ref': school_marker['ref']},
    'bbox_meters': {
        'islands': islands_ext,
        'roads': roads_ext,
        'landuse': landuse_ext,
    },
    'units': 'meters',
    'coordinateSystem': 'local-tangent-plane',
    'origin': 'BBNIHS (0, 0)',
    'convention': 'x=east, z=south (positive latitude)',
    'worldScale': 0.006,  # 1 km = 6 Three.js units
    'features': {
        'islands': len(islands),
        'roads': len(roads),
        'landuse_zones': len(zones),
        'schools': 1,
    },
    'attribution': 'Map data (c) OpenStreetMap contributors',
}
with open('meta.json', 'w') as f:
    json.dump(meta, f, indent=1)
print(f'  saved meta.json')

print('\n=== summary ===')
print(f'  islands: {len(islands)}, extents: {islands_ext}')
print(f'  roads: {len(roads)}, extents: {roads_ext}')
print(f'  landuse: {len(zones)}, extents: {landuse_ext}')

# Print file sizes
import os
for fn in ['islands.json', 'roads.json', 'landuse.json', 'school.json', 'meta.json']:
    sz = os.path.getsize(fn)
    print(f'  {fn}: {sz} bytes')
