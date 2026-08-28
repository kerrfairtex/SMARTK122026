"""Process roads + landuse + school into compact terrain.json for 3D."""
import json, math
from collections import defaultdict

with open('coastlines-raw.json') as f: cw = json.load(f)
with open('roads-raw.json') as f: roads = json.load(f)
with open('landuse-raw.json') as f: landuse = json.load(f)
with open('school-raw.json') as f: school = json.load(f)

# Build a global node index from all data
node_index = {}
for src in [cw, roads, landuse, school]:
    for el in src.get('elements', []):
        if el['type'] == 'node' and 'lat' in el and 'lon' in el:
            node_index[el['id']] = (el['lat'], el['lon'])

# Center on BBNIHS (from the school query)
school_way = next((e for e in school['elements'] if e.get('tags', {}).get('name', '').lower().startswith('batu-batu national')), None)
if school_way and 'center' in school_way:
    sc = school_way['center']
    center_lat, center_lon = sc['lat'], sc['lon']
elif school_way and 'nodes' in school_way:
    # average of node positions
    coords = [node_index[n] for n in school_way['nodes'] if n in node_index]
    if coords:
        center_lat = sum(c[0] for c in coords) / len(coords)
        center_lon = sum(c[1] for c in coords) / len(coords)
    else:
        center_lat, center_lon = 5.07, 119.88
else:
    center_lat, center_lon = 5.07, 119.88

print(f'BBNIHS center: ({center_lat}, {center_lon})')

# Project to local meters
def project(lat, lon, clat=center_lat, clon=center_lon):
    mPerLat = 111320
    mPerLon = 111320 * math.cos(math.radians(clat))
    return [(lon - clon) * mPerLon, (lat - clat) * mPerLat]

# ---- 1. Roads ----
road_lines = []
for el in roads['elements']:
    if el['type'] != 'way': continue
    tags = el.get('tags', {})
    hw = tags.get('highway', 'unclassified')
    if hw not in ('residential', 'tertiary', 'secondary', 'unclassified', 'service', 'path', 'footway', 'track', 'primary'):
        continue
    coords = []
    for nid in el.get('nodes', []):
        if nid in node_index:
            x, y = project(*node_index[nid])
            coords.append([round(x, 0), round(y, 0)])
    if len(coords) >= 2:
        # Reduce long roads
        if len(coords) > 30:
            step = len(coords) // 30
            coords = coords[::step]
        road_lines.append({'type': hw, 'coords': coords})

print(f'roads: {len(road_lines)}')

# ---- 2. Landuse / nature (residential, forest, etc.) ----
# We have mostly coastlines and residential blocks
landuse_zones = []
for el in landuse['elements']:
    if el['type'] != 'way': continue
    tags = el.get('tags', {})
    lu = tags.get('landuse', '')
    nat = tags.get('natural', '')
    if lu == 'residential':
        kind = 'residential'
    elif nat == 'coastline':
        continue  # coastlines are in coastlines-raw
    elif nat == 'water':
        kind = 'water'
    else:
        continue
    coords = []
    for nid in el.get('nodes', []):
        if nid in node_index:
            x, y = project(*node_index[nid])
            coords.append([round(x, 0), round(y, 0)])
    if len(coords) >= 3:
        # Simplify
        if len(coords) > 20:
            step = len(coords) // 20
            coords = coords[::step]
        landuse_zones.append({'kind': kind, 'polygon': coords})

print(f'landuse zones: {len(landuse_zones)}')

# ---- 3. School location ----
school_marker = None
if school_way:
    if 'center' in school_way:
        school_marker = {'lat': school_way['center']['lat'], 'lon': school_way['center']['lon']}
    else:
        coords = [node_index[n] for n in school_way['nodes'] if n in node_index]
        if coords:
            school_marker = {
                'lat': sum(c[0] for c in coords) / len(coords),
                'lon': sum(c[1] for c in coords) / len(coords),
            }
if school_marker:
    school_marker['projected'] = project(school_marker['lat'], school_marker['lon'])
    school_marker['name'] = school_way.get('tags', {}).get('name', 'Batu-Batu National High School')
    school_marker['ref'] = school_way.get('tags', {}).get('ref', '305053')
    school_marker['start_date'] = school_way.get('tags', {}).get('start_date', '1966')
    print(f'school marker: {school_marker["name"]} ref={school_marker["ref"]}')

# Save
out = {
    'center': {'lat': round(center_lat, 5), 'lon': round(center_lon, 5)},
    'bbox': {'S': 5.04, 'W': 119.85, 'N': 5.10, 'E': 119.92},
    'roads': road_lines,
    'landuse': landuse_zones,
    'school': school_marker,
    'sources': {
        'roads': 'OpenStreetMap (overpass-api.de) - highway=*',
        'landuse': 'OpenStreetMap - landuse=*, natural=water',
        'school': 'OpenStreetMap - amenity=school (way 616063878, ref 305053)',
    },
    'attribution': 'Map data (c) OpenStreetMap contributors',
}
with open('terrain.json', 'w') as f:
    json.dump(out, f, separators=(',', ':'))
size = len(json.dumps(out, separators=(',', ':')))
print(f'\nterrain.json size: {size} bytes')
print(f'  roads: {len(road_lines)}')
print(f'  landuse: {len(landuse_zones)}')
print(f'  school: {"yes" if school_marker else "no"}')
