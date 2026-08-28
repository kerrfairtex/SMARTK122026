"""Douglas-Peucker simplification for roads. Reduces 123KB to ~30KB."""
import json, math

with open('roads.json') as f:
    data = json.load(f)

def perpendicular_distance(point, line_start, line_end):
    """Distance from point to line segment (line_start, line_end)."""
    if line_start == line_end:
        return math.hypot(point[0] - line_start[0], point[1] - line_start[1])
    x0, y0 = point
    x1, y1 = line_start
    x2, y2 = line_end
    num = abs((y2 - y1) * x0 - (x2 - x1) * y0 + x2 * y1 - y2 * x1)
    den = math.hypot(y2 - y1, x2 - x1)
    return num / den

def douglas_peucker(points, tolerance):
    """Recursive Douglas-Peucker. Returns simplified points list."""
    if len(points) < 3:
        return points
    # Find point with max distance
    max_d = 0
    idx = 0
    for i in range(1, len(points) - 1):
        d = perpendicular_distance(points[i], points[0], points[-1])
        if d > max_d:
            max_d = d
            idx = i
    if max_d > tolerance:
        left = douglas_peucker(points[:idx + 1], tolerance)
        right = douglas_peucker(points[idx:], tolerance)
        return left[:-1] + right
    return [points[0], points[-1]]

# Tolerance: 3 meters (per the spec, "1-3 m tolerance" for hero visualization)
TOLERANCE = 3.0

original_total = 0
simplified_total = 0
for road in data['roads']:
    coords = road['coords']
    original_total += len(coords)
    simplified = douglas_peucker(coords, TOLERANCE)
    if len(simplified) < 2:
        simplified = coords  # don't lose short roads
    road['coords'] = simplified
    simplified_total += len(simplified)

print(f'roads: {original_total} -> {simplified_total} points ({100 * (1 - simplified_total / original_total):.1f}% reduction)')

with open('roads.json', 'w') as f:
    json.dump(data, f, indent=1)
import os
print(f'roads.json: {os.path.getsize("roads.json")} bytes')
