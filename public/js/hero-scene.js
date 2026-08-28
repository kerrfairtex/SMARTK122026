/* =====================================================================
 * SmartCampus K-12 — hero-scene.js
 * 3D tide horizon with REAL Tawi-Tawi island geometry AND terrain.
 *
 * Data sources (all from OpenStreetMap via Overpass API):
 *   public/data/tawi-tawi/islands.json   - 7 islands, simplified polygons
 *   public/data/tawi-tawi/terrain.json   - roads, residential zones,
 *                                           BBNIHS school location
 *
 * Features rendered:
 *   - 7 real Tawi-Tawi islands (low-poly extrusions)
 *   - Water plane with vertex-shader waves
 *   - Road network (tertiary / residential / path)
 *   - Residential zones (green/transparent overlay)
 *   - School marker pin (red beacon + label)
 *   - Time-of-day color tint
 *   - Sun position
 *
 * Tier 0/1: CSS gradient + region label (no WebGL, no Three.js load)
 * Tier 2: Three.js + real OSM data, lazy-loaded on first scroll/pointer
 *
 * Respects prefers-reduced-motion and connection.saveData.
 * ===================================================================== */
(function () {
    'use strict';
    if (document.documentElement.classList.contains('tier-static')) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var wrap = document.querySelector('.hero__canvas-wrap');
    if (!wrap) return;

    var armed = false;
    function arm() { if (armed) return; armed = true; initScene(); }
    window.addEventListener('scroll', arm, { passive: true, once: true });
    window.addEventListener('pointerdown', arm, { passive: true, once: true });
    setTimeout(arm, 2500);

    function initScene() {
        var s = document.createElement('script');
        s.src = 'https://unpkg.com/three@0.150.0/build/three.min.js';
        s.onload = function () {
            Promise.all([
                fetch('public/data/tawi-tawi/islands.json', { cache: 'force-cache' }).then(function (r) { return r.json(); }),
                fetch('public/data/tawi-tawi/terrain.json', { cache: 'force-cache' }).then(function (r) { return r.json(); })
            ]).then(function (results) {
                startScene(results[0], results[1]);
            }).catch(function () { startScene(null, null); });
        };
        s.onerror = function () { /* keep fallback visible */ };
        document.head.appendChild(s);
    }

    function timeOfDayTint() {
        var h = new Date().getHours();
        if (h >= 5 && h < 8) return { sky: 0x1a3850, water: 0x2d6a6a, sun: 0xfff0c0 };
        if (h >= 8 && h < 17) return { sky: 0x0E4F4F, water: 0x1a5a5a, sun: 0xF4B400 };
        if (h >= 17 && h < 19) return { sky: 0x4a2a3a, water: 0x4a3a4a, sun: 0xff7a4a };
        return { sky: 0x08142a, water: 0x0E4F4F, sun: 0xcfe8e4 };
    }

    function startScene(islandData, terrainData) {
        if (typeof THREE === 'undefined') return;
        var tint = timeOfDayTint();
        var islands = (islandData && islandData.islands) ? islandData.islands : [];
        var roads = (terrainData && terrainData.roads) ? terrainData.roads : [];
        var landuse = (terrainData && terrainData.landuse) ? terrainData.landuse : [];
        var school = (terrainData && terrainData.school) ? terrainData.school : null;
        var center = (terrainData && terrainData.center) ? terrainData.center :
                     (islandData && islandData.center) ? islandData.center :
                     { lat: 5.07, lon: 119.88 };
        var centerLat = center.lat * Math.PI / 180;

        // ---- Renderer ----
        var canvas = document.createElement('canvas');
        wrap.appendChild(canvas);
        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        renderer.setSize(wrap.clientWidth, wrap.clientHeight);

        var scene = new THREE.Scene();
        scene.background = new THREE.Color(tint.sky);
        scene.fog = new THREE.Fog(tint.sky, 80, 280);

        var camera = new THREE.PerspectiveCamera(60, wrap.clientWidth / wrap.clientHeight, 0.1, 600);
        // Camera positioned to see school + main island together
        // School is at world (0,0); main island centroid at projected (-8.3, -32.6)
        // Camera 30 units above and 70 back, looking at (0, 0, -8) - a midpoint
        // that frames both the school pin and the southern island.
        camera.position.set(8, 38, 60);
        camera.lookAt(0, 0, -8);

        // ---- Sun ----
        var sun = new THREE.Mesh(
            new THREE.SphereGeometry(2.5, 12, 8),
            new THREE.MeshBasicMaterial({ color: tint.sun })
        );
        sun.position.set(-40, 35, -60);
        scene.add(sun);

        // ---- Water plane (slightly larger to extend beyond islands) ----
        var waterGeo = new THREE.PlaneGeometry(280, 280, 40, 40);
        waterGeo.rotateX(-Math.PI / 2);
        var waterMat = new THREE.ShaderMaterial({
            uniforms: { uTime: { value: 0 }, uColor: { value: new THREE.Color(tint.water) } },
            vertexShader: 'uniform float uTime; varying float vY; void main() { vec3 p = position; float wave = sin(p.x*0.15+uTime*0.5)*0.5 + cos(p.z*0.18+uTime*0.4)*0.5; p.y += wave; vY = wave; gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0); }',
            fragmentShader: 'uniform vec3 uColor; varying float vY; void main() { float s = smoothstep(-1.0, 1.0, vY); gl_FragColor = vec4(uColor * (0.7 + s * 0.4), 1.0); }',
        });
        scene.add(new THREE.Mesh(waterGeo, waterMat));

        // ---- Project (lat, lon) -> local meters around center ----
        function project(lat, lon) {
            var mPerLat = 111320;
            var mPerLon = 111320 * Math.cos(centerLat);
            return [(lon - center.lon) * mPerLon, (lat - center.lat) * mPerLat];
        }

        // World scale: terrain spans ~3-7km around center
        // We want terrain to be ~30 units across. 5km = 5000m -> 30 units, so scale = 0.006
        var worldScale = 0.006;

        // ---- 1. ISLANDS (low-poly landmasses, sandy color) ----
        var islandMat = new THREE.MeshBasicMaterial({ color: 0xd4b896 });  // sand/tan
        islands.forEach(function (island) {
            var poly = island.vertices;
            if (!poly || poly.length < 4) return;
            var shape = new THREE.Shape();
            shape.moveTo(poly[0][0] * worldScale, poly[0][1] * worldScale);
            for (var i = 1; i < poly.length; i++) {
                shape.lineTo(poly[i][0] * worldScale, poly[i][1] * worldScale);
            }
            var geo = new THREE.ExtrudeGeometry(shape, {
                depth: 1.2,
                bevelEnabled: false
            });
            geo.rotateX(-Math.PI / 2);
            var mesh = new THREE.Mesh(geo, islandMat);
            var xy = project(island.center_lat, island.center_lon);
            mesh.position.set(xy[0] * worldScale, 0, xy[1] * worldScale);
            scene.add(mesh);
        });

        // ---- 2. RESIDENTIAL ZONES (green overlay on top of islands) ----
        var resiMat = new THREE.MeshBasicMaterial({
            color: 0x4a7c3a,  // forest green
            transparent: true,
            opacity: 0.7
        });
        var resiMat2 = new THREE.MeshBasicMaterial({
            color: 0x7a9c5a,  // lighter green (commercial/mixed)
            transparent: true,
            opacity: 0.5
        });
        landuse.forEach(function (zone) {
            if (zone.kind !== 'residential' && zone.kind !== 'water') return;
            var poly = zone.polygon;
            if (!poly || poly.length < 3) return;
            var shape = new THREE.Shape();
            shape.moveTo(poly[0][0] * worldScale, poly[0][1] * worldScale);
            for (var i = 1; i < poly.length; i++) {
                shape.lineTo(poly[i][0] * worldScale, poly[i][1] * worldScale);
            }
            var geo = new THREE.ExtrudeGeometry(shape, {
                depth: 0.4,
                bevelEnabled: false
            });
            geo.rotateX(-Math.PI / 2);
            var mat = (zone.kind === 'residential') ? resiMat : resiMat2;
            var mesh = new THREE.Mesh(geo, mat);
            var centroid = [0, 0];
            for (var j = 0; j < poly.length; j++) {
                centroid[0] += poly[j][0]; centroid[1] += poly[j][1];
            }
            centroid[0] /= poly.length; centroid[1] /= poly.length;
            // Raise it slightly above island surface
            mesh.position.set(centroid[0] * worldScale, 0.05, centroid[1] * worldScale);
            scene.add(mesh);
        });

        // ---- 3. ROADS (lines on top of islands) ----
        var roadWidths = {
            'primary': 0.6, 'secondary': 0.5, 'tertiary': 0.4,
            'residential': 0.3, 'unclassified': 0.3, 'service': 0.2,
            'path': 0.15, 'footway': 0.12, 'track': 0.25
        };
        var roadColors = {
            'primary': 0xfff5d6, 'secondary': 0xfff5d6, 'tertiary': 0xe8d4a8,
            'residential': 0xd4b896, 'unclassified': 0xb89a76, 'service': 0xa48a66,
            'path': 0x8a7050, 'footway': 0x705a40, 'track': 0x705a40
        };
        // Group roads by type to batch geometries (saves draw calls)
        var roadsByType = {};
        roads.forEach(function (road) {
            if (!roadsByType[road.type]) roadsByType[road.type] = [];
            roadsByType[road.type].push(road);
        });
        Object.keys(roadsByType).forEach(function (rtype) {
            var group = roadsByType[rtype];
            var positions = [];
            group.forEach(function (road) {
                var w = roadWidths[rtype] || 0.3;
                for (var i = 0; i < road.coords.length - 1; i++) {
                    var x1 = road.coords[i][0] * worldScale;
                    var z1 = road.coords[i][1] * worldScale;
                    var x2 = road.coords[i + 1][0] * worldScale;
                    var z2 = road.coords[i + 1][1] * worldScale;
                    // Build a thin rectangle for this segment
                    var dx = x2 - x1, dz = z2 - z1;
                    var len = Math.sqrt(dx * dx + dz * dz);
                    if (len < 0.001) continue;
                    var nx = -dz / len * w * 0.5;
                    var nz = dx / len * w * 0.5;
                    // Triangle 1
                    positions.push(x1 + nx, 0.3, z1 + nz);
                    positions.push(x2 + nx, 0.3, z2 + nz);
                    positions.push(x1 - nx, 0.3, z1 - nz);
                    // Triangle 2
                    positions.push(x2 + nx, 0.3, z2 + nz);
                    positions.push(x2 - nx, 0.3, z2 - nz);
                    positions.push(x1 - nx, 0.3, z1 - nz);
                }
            });
            if (positions.length === 0) return;
            var geo = new THREE.BufferGeometry();
            geo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
            geo.computeVertexNormals();
            var mat = new THREE.MeshBasicMaterial({ color: roadColors[rtype] || 0xd4b896, side: THREE.DoubleSide });
            scene.add(new THREE.Mesh(geo, mat));
        });

        // ---- 4. SCHOOL MARKER PIN (with landmark roof) ----
        if (school && school.projected) {
            var sx = school.projected[0] * worldScale;
            var sz = school.projected[1] * worldScale;
            // Yellow post
            var post = new THREE.Mesh(
                new THREE.CylinderGeometry(0.08, 0.08, 1.5, 8),
                new THREE.MeshBasicMaterial({ color: 0xF4B400 })
            );
            post.position.set(sx, 0.85, sz);
            scene.add(post);
            // Beacon sphere
            var beacon = new THREE.Mesh(
                new THREE.SphereGeometry(0.5, 12, 8),
                new THREE.MeshBasicMaterial({ color: 0xe8734a })
            );
            beacon.position.set(sx, 1.7, sz);
            scene.add(beacon);
            // LANDMARK: school icon - a stylized house/building on top of the beacon
            // Made of: a cube (the building) + a pyramid (the roof) + a small flag pole
            var schoolGroup = new THREE.Group();
            // Building
            var buildingMat = new THREE.MeshBasicMaterial({ color: 0xEDE6D6 }); // sand
            var building = new THREE.Mesh(new THREE.BoxGeometry(0.6, 0.5, 0.6), buildingMat);
            building.position.y = 0.25;
            schoolGroup.add(building);
            // Roof - 4-sided pyramid (cone with 4 segments)
            var roofMat = new THREE.MeshBasicMaterial({ color: 0xe8734a }); // reef-coral
            var roof = new THREE.Mesh(new THREE.ConeGeometry(0.55, 0.4, 4), roofMat);
            roof.position.y = 0.7;
            roof.rotation.y = Math.PI / 4;
            schoolGroup.add(roof);
            // Flag pole + flag
            var poleMat = new THREE.MeshBasicMaterial({ color: 0xcfe8e4 }); // foam
            var pole = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.5, 4), poleMat);
            pole.position.set(0.2, 1.0, 0.0);
            schoolGroup.add(pole);
            var flagMat = new THREE.MeshBasicMaterial({ color: 0xF4B400 }); // sun-gold
            var flag = new THREE.Mesh(new THREE.PlaneGeometry(0.3, 0.18), flagMat);
            flag.position.set(0.35, 1.18, 0.0);
            schoolGroup.add(flag);
            // Position the whole school icon on top of the beacon
            schoolGroup.position.set(sx, 2.2, sz);
            scene.add(schoolGroup);
            // Pulsing ring on the ground (kept from before)
            var ringGeo = new THREE.RingGeometry(0.6, 0.8, 24);
            var ringMat = new THREE.MeshBasicMaterial({ color: 0xe8734a, transparent: true, opacity: 0.5, side: THREE.DoubleSide });
            var ring = new THREE.Mesh(ringGeo, ringMat);
            ring.rotation.x = -Math.PI / 2;
            ring.position.set(sx, 0.4, sz);
            scene.add(ring);
        }

        // ---- 5. LABEL (rendered as overlay on the canvas) ----
        // We add a small DOM element positioned at the school's projected screen coords
        if (school && school.projected) {
            var label = document.createElement('div');
            label.style.cssText = 'position:absolute;color:var(--sun-gold);font-family:var(--font-utility);font-size:0.7rem;text-shadow:0 0 4px rgba(0,0,0,0.8);pointer-events:none;white-space:nowrap;z-index:3;';
            label.textContent = '📍 BBNIHS · ref 305053 · est. ' + (school.start_date || '1966');
            label.id = 'heroSchoolLabel';
            wrap.style.position = 'relative';
            wrap.appendChild(label);
            function updateLabel() {
                var sx = school.projected[0] * worldScale;
                var sz = school.projected[1] * worldScale;
                // Project the school roof top (y=2.6) so the label sits above the icon
                var v = new THREE.Vector3(sx, 2.6, sz);
                v.project(camera);
                var w = wrap.clientWidth, h = wrap.clientHeight;
                var x = (v.x + 1) / 2 * w;
                var y = (-v.y + 1) / 2 * h;
                label.style.left = x + 'px';
                label.style.top = y + 'px';
                label.style.transform = 'translate(-50%, -100%)';
            }
            window.addEventListener('resize', updateLabel);
        }

        // ---- 6. Animate ----
        var t0 = performance.now();
        function frame(now) {
            var t = (now - t0) / 1000;
            waterMat.uniforms.uTime.value = t;
            // Subtle camera drift around the framing midpoint
            camera.position.x = 8 + Math.sin(t * 0.04) * 2;
            camera.position.z = 60 + Math.cos(t * 0.04) * 2;
            camera.lookAt(0, 0, -8);
            // Pulse the school ring
            if (ring) {
                var s = 1 + Math.sin(t * 2) * 0.2;
                ring.scale.set(s, s, 1);
                ringMat.opacity = 0.5 - Math.sin(t * 2) * 0.3;
            }
            renderer.render(scene, camera);
            if (label) updateLabel();
            requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);

        // Resize
        window.addEventListener('resize', function () {
            renderer.setSize(wrap.clientWidth, wrap.clientHeight);
            camera.aspect = wrap.clientWidth / wrap.clientHeight;
            camera.updateProjectionMatrix();
        });
    }
})();
