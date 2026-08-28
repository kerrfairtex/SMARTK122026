"""Layout audit: check for common layout issues in the served HTML."""
import urllib.request, ssl, re
ctx = ssl.create_default_context()

r = urllib.request.urlopen('https://smartcampk12.onrender.com/', context=ctx, timeout=20)
html = r.read().decode('utf-8')

# 1. Check hero canvas wrap is positioned correctly
canvas_section = re.search(r'<section class="hero"[^>]*>(.*?)</section>', html, re.DOTALL)
if canvas_section:
    body = canvas_section.group(0)
    print('=== Hero section structure ===')
    print(f'  has hero__fallback: {"hero__fallback" in body}')
    print(f'  has hero__canvas-wrap: {"hero__canvas-wrap" in body}')
    print(f'  has hero__content: {"hero__content" in body}')
    print(f'  has hero__title: {"hero__title" in body}')
    print(f'  has hero__clock: {"hero__clock" in body}')
    print(f'  has hero__actions: {"hero__actions" in body}')

# 2. Check CSS positioning
r_css = urllib.request.urlopen('https://smartcampk12.onrender.com/public/css/components.css', context=ctx, timeout=15)
css = r_css.read().decode('utf-8')
print()
print('=== CSS layout checks ===')
for needle, label in [
    ('.hero {', 'hero section rule'),
    ('position: relative', 'positioning context'),
    ('.hero__canvas-wrap {', 'canvas wrap rule'),
    ('z-index:', 'z-indexing'),
    ('overflow: hidden', 'overflow control'),
    ('inset: 0', 'canvas full-bleed'),
    ('.hero__fallback {', 'fallback rule'),
    ('z-index: -1', 'fallback behind'),
]:
    found = needle in css
    print(f"  [{('OK' if found else 'MISS'):>4}]  {label}  needle: {needle!r}")

# 3. Check the photo strip styles
print()
print('=== Photo strip styles ===')
for needle in ['.photo-strip {', '.photo-strip figure', 'aspect-ratio:', 'figure p {', 'figure.unverified', 'figcaption.unverified-tag', 'figure:hover p', 'figure:focus-within p']:
    found = needle in css
    print(f"  [{('OK' if found else 'MISS'):>4}]  {needle}")

# 4. Check the JS label positioning
r_js = urllib.request.urlopen('https://smartcampk12.onrender.com/public/js/hero-scene.js', context=ctx, timeout=15)
js = r_js.read().decode('utf-8')
print()
print('=== Hero scene JS checks ===')
for needle, label in [
    ('label.style.left', 'label x positioning'),
    ('label.style.top', 'label y positioning'),
    ('updateLabel', 'label update function'),
    ('v.project(camera)', '3D->2D projection'),
    ('heroSchoolLabel', 'label element id'),
    ('school.projected', 'school position usage'),
    ('worldScale = 0.006', 'world scale constant'),
    ('camera.position.set(0, 60, 90)', 'camera initial position'),
    ('camera.lookAt(0, 0, 0)', 'camera look at origin'),
]:
    found = needle in js
    print(f"  [{('OK' if found else 'MISS'):>4}]  {label}  needle: {needle!r}")

# 5. Check: does the label have z-index and pointer-events?
print()
print('=== Label CSS-in-JS ===')
m = re.search(r'label\.style\.cssText\s*=\s*\'([^\']+)\'', js)
if m:
    print(f'  cssText: {m.group(1)}')
    for prop in ['z-index', 'pointer-events', 'position']:
        if prop in m.group(1):
            print(f"    [OK]  {prop}")

# 6. Quick rendering order check
print()
print('=== Render order (script load) ===')
scripts = re.findall(r'<script[^>]+src="([^"]+)"', html)
for s in scripts:
    print(f'  {s}')

# 7. Most important: check the LAYOUT of the page sections
sections = re.findall(r'<section id="([^"]+)"[^>]*>', html)
print(f'\n=== Sections: {len(sections)} ===')
for s in sections:
    print(f'  #{s}')
