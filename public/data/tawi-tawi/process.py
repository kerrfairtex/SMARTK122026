"""Simplify each island to <=16 vertices, output compact JSON."""
import json
from collections import defaultdict

with open('coastlines-raw.json') as f:
    data = json.load(f)
elements = data['elements']
nodes = {el['id']: (el['lat'], el['lon']) for el in elements if el['type'] == 'node'}
ways_by_id = {el['id']: el for el in elements if el['type'] == 'way'}

# Re-run BFS for components
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

# Build each island's vertex set (lat/lon)
islands = []
for i, comp in enumerate(components):
    pts = set()
    for wid in comp:
        for nid in ways_by_id[wid].get('nodes', []):
            if nid in nodes:
                pts.add(nodes[nid])
    pts = list(pts)
    if len(pts) < 6: continue
    # Bounding box center
    lats = [p[0] for p in pts]
    lons = [p[1] for p in pts]
    cy, cx = sum(lats)/len(lats), sum(lons)/len(lons)
    # Convert to local meters (rough, good enough for visualization)
    import math
    m_per_lat = 111320
    m_per_lon = 111320 * math.cos(math.radians(cy))
    # 1 = land, 0 = sea, build a footprint: sort points by angle around centroid
    angle_pts = []
    for (lat, lon) in pts:
        dy = (lat - cy) * m_per_lat
        dx = (lon - cx) * m_per_lon
        ang = math.atan2(dy, dx)
        angle_pts.append((ang, dx, dy, lat, lon))
    angle_pts.sort()
    # Simplify: keep every Nth point to target 12-16 vertices
    target = min(16, max(8, len(angle_pts) // 4))
    step = max(1, len(angle_pts) // target)
    simplified = angle_pts[::step]
    if len(simplified) < 6:
        simplified = angle_pts
    # Output as local meters
    poly = [[round(dx, 1), round(dy, 1)] for (ang, dx, dy, lat, lon) in simplified]
    # close the polygon
    if poly and poly[0] != poly[-1]:
        poly.append(poly[0])
    islands.append({
        'name': f'island_{i}',
        'center_lat': round(cy, 5),
        'center_lon': round(cx, 5),
        'extent_m': round(max(abs(p[0]) for p in poly) + max(abs(p[1]) for p in poly), 0),
        'vertices': poly,
    })

# Sort by extent, keep top N
islands.sort(key=lambda x: -x['extent_m'])
print(f'final islands: {len(islands)}')
for isl in islands:
    print(f"  {isl['name']:10s}  center=({isl['center_lat']}, {isl['center_lon']})  extent={isl['extent_m']}m  vertices={len(isl['vertices'])}")

with open('islands.json', 'w') as f:
    json.dump({
        'bbox': {'S': 4.9, 'W': 119.7, 'N': 5.3, 'E': 120.0},
        'center': {'lat': 5.07, 'lon': 119.88},
        'islands': islands,
        'source': 'OpenStreetMap (overpass-api.de) - natural=coastline',
        'attribution': 'Map data (c) OpenStreetMap contributors',
    }, f, indent=1)
print('saved islands.json')
