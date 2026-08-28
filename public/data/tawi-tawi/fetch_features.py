"""Query OSM for roads, landuse, natural, and the school location."""
import urllib.request, urllib.parse, json, ssl, time
ctx = ssl.create_default_context()

# Tighter bbox around Batu-Batu school
S, W, N, E = 5.04, 119.85, 5.10, 119.92  # ~7km x 7km

# 1. Roads
roads_q = f'''
[out:json][timeout:60];
(
  way["highway"]({S},{W},{N},{E});
);
out body;
>;
out skel qt;
'''
# 2. Landuse / natural (forests, residential, schools)
landuse_q = f'''
[out:json][timeout:60];
(
  way["landuse"]({S},{W},{N},{E});
  way["natural"~"water|coastline|wood|tree_row"]({S},{W},{N},{E});
  way["leisure"="park"]({S},{W},{N},{E});
  way["amenity"~"school|college|university"]({S},{W},{N},{E});
);
out body;
>;
out skel qt;
'''
# 3. School location (if any)
school_q = f'''
[out:json][timeout:30];
(
  node["amenity"="school"]({S},{W},{N},{E});
  way["amenity"="school"]({S},{W},{N},{E});
);
out center;
'''

def fetch(q, label):
    url = 'https://overpass-api.de/api/interpreter?data=' + urllib.parse.quote(q)
    print(f'Fetching {label}...')
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'BBNIHS-SmartCampus/1.0'})
            with urllib.request.urlopen(req, context=ctx, timeout=90) as r:
                data = json.loads(r.read().decode('utf-8'))
            print(f'  OK ({len(data.get("elements", []))} elements)')
            return data
        except Exception as e:
            print(f'  attempt {attempt+1}: {e}')
            time.sleep(2)
    return {'elements': []}

roads = fetch(roads_q, 'roads')
time.sleep(2)
landuse = fetch(landuse_q, 'landuse/natural')
time.sleep(2)
school = fetch(school_q, 'school')

# Save raw
with open('roads-raw.json', 'w') as f: json.dump(roads, f)
with open('landuse-raw.json', 'w') as f: json.dump(landuse, f)
with open('school-raw.json', 'w') as f: json.dump(school, f)

# Summary
def count_by_tag(elements, key):
    counts = {}
    for el in elements:
        tags = el.get('tags', {})
        v = tags.get(key, '?')
        counts[v] = counts.get(v, 0) + 1
    return counts

print('\n--- Roads by highway type ---')
for t, n in sorted(count_by_tag(roads['elements'], 'highway').items(), key=lambda x: -x[1])[:15]:
    print(f'  {t}: {n}')

print('\n--- Landuse by type ---')
for t, n in sorted(count_by_tag(landuse['elements'], 'landuse').items(), key=lambda x: -x[1])[:10]:
    print(f'  {t}: {n}')

print('\n--- Natural features ---')
for t, n in sorted(count_by_tag(landuse['elements'], 'natural').items(), key=lambda x: -x[1])[:10]:
    print(f'  {t}: {n}')

print('\n--- School nodes/ways ---')
print(f'  nodes: {sum(1 for e in school["elements"] if e["type"] == "node")}')
print(f'  ways: {sum(1 for e in school["elements"] if e["type"] == "way")}')
for e in school['elements']:
    tags = e.get('tags', {})
    name = tags.get('name', tags.get('name:en', '?'))
    print(f'  {e["type"]} {e["id"]}: name={name!r} tags={tags}')
