"""Refresh meta.json after roads simplification."""
import json

with open('roads.json') as f:
    roads = json.load(f)['roads']
with open('islands.json') as f:
    islands = json.load(f)['islands']
with open('landuse.json') as f:
    landuse = json.load(f)['zones']
with open('school.json') as f:
    school = json.load(f)

def xz_extent(records, key_path):
    xs, zs = [], []
    for r in records:
        v = r
        for k in key_path:
            v = v[k]
        for p in v:
            xs.append(p[0])
            zs.append(p[1])
    if not xs: return None
    return {'min_x': min(xs), 'max_x': max(xs), 'min_z': min(zs), 'max_z': max(zs)}

roads_ext = xz_extent(roads, ['coords'])
landuse_ext = xz_extent(landuse, ['polygon'])
island_cx = [i['centroid_x'] for i in islands]
island_cz = [i['centroid_z'] for i in islands]
islands_ext = {
    'min_x': min(island_cx), 'max_x': max(island_cx),
    'min_z': min(island_cz), 'max_z': max(island_cz),
} if islands else None

meta = {
    'version': 1,
    'source': 'OpenStreetMap via Overpass API',
    'school': {'lat': school['lat'], 'lon': school['lon'], 'name': school['name'], 'ref': school['ref']},
    'bbox_meters': {
        'islands': islands_ext,
        'roads': roads_ext,
        'landuse': landuse_ext,
    },
    'units': 'meters',
    'coordinateSystem': 'local-tangent-plane',
    'origin': 'BBNIHS (0, 0)',
    'convention': 'x=east, z=south (positive latitude)',
    'worldScale': 0.006,
    'features': {
        'islands': len(islands),
        'roads': len(roads),
        'landuse_zones': len(landuse),
        'schools': 1,
    },
    'attribution': 'Map data (c) OpenStreetMap contributors',
}
with open('meta.json', 'w') as f:
    json.dump(meta, f, indent=1)

print('=== meta.json ===')
import os
print(f'  size: {os.path.getsize("meta.json")} bytes')
print(f'  islands extents: {islands_ext}')
print(f'  roads extents: {roads_ext}')
print(f'  landuse extents: {landuse_ext}')
print()
print('=== file sizes ===')
total = 0
for fn in sorted(os.listdir('.')):
    if fn.endswith('.json') and not fn.endswith('-raw.json'):
        sz = os.path.getsize(fn)
        total += sz
        print(f'  {fn}: {sz} bytes')
print(f'  TOTAL: {total} bytes')
