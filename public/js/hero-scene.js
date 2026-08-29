/* =====================================================================
 * SmartCampus K-12 — hero-scene.js  (production)
 * 3D tide horizon with real Tawi-Tawi island geometry + terrain.
 *
 * Data sources (all in /public/data/tawi-tawi/, school-local x/z):
 *   school.json    BBNIHS location (at origin 0,0)
 *   islands.json   7 islands, simplified polygons
 *   roads.json     85 ways, Douglas-Peucker simplified to 985 pts
 *   landuse.json   4 zones (residential + water)
 *   meta.json      extents + counts
 *
 * Coord convention: +x=east, +z=south (lat), in meters from school.
 *
 * Tier 0/1: CSS gradient + region label (no WebGL, no Three.js load)
 * Tier 2:   Three.js + real OSM data, lazy-loaded on first scroll/pointer
 *
 * Respects prefers-reduced-motion AND navigator.connection.saveData.
 * Exposes window.SmartCampusHero = { init, destroy } for SPA lifecycles.
 * ===================================================================== */
(function () {
  'use strict';

  var DATA_BASE = '/public/data/tawi-tawi';
  var THREE_SRC = 'https://unpkg.com/three@0.150.0/build/three.min.js';
  var FETCH_TIMEOUT_MS = 8000;

  var state = { armed: false, destroyed: false, scriptEl: null, cleanupFns: [] };

  function shouldSkip() {
    if (document.documentElement.classList.contains('tier-static')) return true;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return true;
    var conn = navigator.connection || navigator.webkitConnection || navigator.mozConnection;
    if (conn && conn.saveData) return true;
    if (conn && /^(slow-2g|2g)$/.test(conn.effectiveType || '')) return true;
    return false;
  }

  function fetchJSON(url) {
    var controller = ('AbortController' in window) ? new AbortController() : null;
    var timer = controller ? setTimeout(function () { controller.abort(); }, FETCH_TIMEOUT_MS) : null;
    return fetch(url, { cache: 'force-cache', signal: controller && controller.signal })
      .then(function (r) {
        if (timer) clearTimeout(timer);
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      });
  }

  function loadThree() {
    return new Promise(function (resolve, reject) {
      if (window.THREE) { resolve(window.THREE); return; }
      if (state.scriptEl) {
        state.scriptEl.addEventListener('load', function () { resolve(window.THREE); });
        state.scriptEl.addEventListener('error', reject);
        return;
      }
      var s = document.createElement('script');
      s.src = THREE_SRC;
      s.crossOrigin = 'anonymous';
      s.onload = function () { resolve(window.THREE); };
      s.onerror = function () { console.error('SmartCampusHero: three.js CDN load failed', THREE_SRC); reject(new Error('three.js failed at ' + THREE_SRC)); };
      document.head.appendChild(s);
      state.scriptEl = s;
    });
  }

  function timeOfDayTint() {
    var h = new Date().getHours();
    if (h < 8)  return { sky: 0x2a4a68, water: 0x3a8a8a, sun: 0xfff0c0 };
    if (h < 17) return { sky: 0x1a6b6b, water: 0x2fa3a3, sun: 0xF4B400 };
    if (h < 19) return { sky: 0x5a3a4a, water: 0x6a5468, sun: 0xff7a4a };
    return { sky: 0x102a42, water: 0x1a6b6b, sun: 0xcfe8e4 };
  }

  function computeWorldScale(meta) {
    // Pick world scale so the visible bbox fits in ~32 units.
    var b = (meta && meta.bbox_meters && meta.bbox_meters.roads) || null;
    if (!b) return 0.006;
    var spanX = Math.abs(b.max_x - b.min_x);
    var spanZ = Math.abs(b.max_z - b.min_z);
    var maxSpan = Math.max(spanX, spanZ);
    if (!isFinite(maxSpan) || maxSpan <= 0) return 0.006;
    return 32 / maxSpan;
  }

  function trackBounds(b, x, z) {
    if (x < b.minX) b.minX = x;
    if (x > b.maxX) b.maxX = x;
    if (z < b.minZ) b.minZ = z;
    if (z > b.maxZ) b.maxZ = z;
  }

  function frameCamera(camera, bounds) {
    if (!isFinite(bounds.minX)) {
      camera.position.set(6, 20, 34);
      camera.lookAt(0, 0, -6);
      return;
    }
    var cx = (bounds.minX + bounds.maxX) / 2;
    var cz = (bounds.minZ + bounds.maxZ) / 2;
    var spanX = Math.max(bounds.maxX - bounds.minX, 8);
    var spanZ = Math.max(bounds.maxZ - bounds.minZ, 8);
    var span = Math.max(spanX, spanZ);
    camera.position.set(cx + span * 0.12, span * 0.5, cz + span * 0.65);
    camera.lookAt(cx, 0, cz - span * 0.05);
  }

  function startScene(wrap, meta, islandData, roadData, landuseData, schoolData) {
    if (typeof THREE === 'undefined') { console.error('SmartCampusHero: THREE undefined — WebGL/canvas unavailable'); return; }
    if (wrap.clientWidth === 0 || wrap.clientHeight === 0) return;
    var disposers = [];
    var tint = timeOfDayTint();
    var islands = (islandData && islandData.islands) || [];
    var roads = (roadData && roadData.roads) || [];
    var landuse = (landuseData && landuseData.zones) || [];
    var school = schoolData || { x: 0, z: 0, name: 'BBNIHS', ref: '305053', start_date: '1966' };
    var worldScale = computeWorldScale(meta);

    // Renderer + scene
    var canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    wrap.appendChild(canvas);
    var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.setSize(wrap.clientWidth, wrap.clientHeight);
    var scene = new THREE.Scene();
    scene.background = new THREE.Color(tint.sky);
    scene.fog = new THREE.Fog(tint.sky, 40, 180);
    var camera = new THREE.PerspectiveCamera(60, wrap.clientWidth / wrap.clientHeight, 0.1, 600);
    var bounds = { minX: Infinity, maxX: -Infinity, minZ: Infinity, maxZ: -Infinity };

    // Sun
    var sun = new THREE.Mesh(
      new THREE.SphereGeometry(2.5, 12, 8),
      new THREE.MeshBasicMaterial({ color: tint.sun })
    );
    sun.position.set(-40, 35, -60);
    scene.add(sun);
    disposers.push(function () { sun.geometry.dispose(); sun.material.dispose(); });

    // Water (vertex-shader wave)
    var waterGeo = new THREE.PlaneGeometry(320, 320, 40, 40);
    waterGeo.rotateX(-Math.PI / 2);
    var waterMat = new THREE.ShaderMaterial({
      uniforms: { uTime: { value: 0 }, uColor: { value: new THREE.Color(tint.water) } },
      vertexShader: 'uniform float uTime; varying float vY; void main() { vec3 p = position; float wave = sin(p.x*0.15+uTime*0.5)*0.5 + cos(p.z*0.18+uTime*0.4)*0.5; p.y += wave; vY = wave; gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0); }',
      fragmentShader: 'uniform vec3 uColor; varying float vY; void main() { float s = smoothstep(-1.0, 1.0, vY); gl_FragColor = vec4(uColor * (0.85 + s * 0.55), 1.0); }'
    });
    scene.add(new THREE.Mesh(waterGeo, waterMat));
    disposers.push(function () { waterGeo.dispose(); waterMat.dispose(); });

    // Islands (sandy low-poly extrusions at their xz positions)
    var islandMat = new THREE.MeshBasicMaterial({ color: 0xd4b896 });
    islands.forEach(function (island) {
      var poly = island.vertices;
      if (!poly || poly.length < 4) return;
      var shape = new THREE.Shape();
      shape.moveTo(poly[0][0] * worldScale, poly[0][1] * worldScale);
      for (var i = 1; i < poly.length; i++) shape.lineTo(poly[i][0] * worldScale, poly[i][1] * worldScale);
      var geo = new THREE.ExtrudeGeometry(shape, { depth: 1.2, bevelEnabled: false });
      geo.rotateX(-Math.PI / 2);
      var mesh = new THREE.Mesh(geo, islandMat);
      var px = (island.centroid_x || 0) * worldScale;
      var pz = (island.centroid_z || 0) * worldScale;
      mesh.position.set(px, 0, pz);
      scene.add(mesh);
      disposers.push(function () { geo.dispose(); });
      trackBounds(bounds, px, pz);
    });
    disposers.push(function () { islandMat.dispose(); });

    // Landuse zones (forest green on top of islands)
    var resiMat = new THREE.MeshBasicMaterial({ color: 0x4a7c3a, transparent: true, opacity: 0.7 });
    var resiMat2 = new THREE.MeshBasicMaterial({ color: 0x7a9c5a, transparent: true, opacity: 0.5 });
    landuse.forEach(function (zone) {
      if (zone.kind !== 'residential' && zone.kind !== 'water') return;
      var poly = zone.polygon;
      if (!poly || poly.length < 3) return;
      var shape = new THREE.Shape();
      shape.moveTo(poly[0][0] * worldScale, poly[0][1] * worldScale);
      for (var j = 1; j < poly.length; j++) shape.lineTo(poly[j][0] * worldScale, poly[j][1] * worldScale);
      var geo = new THREE.ExtrudeGeometry(shape, { depth: 0.4, bevelEnabled: false });
      geo.rotateX(-Math.PI / 2);
      var mat = zone.kind === 'residential' ? resiMat : resiMat2;
      var mesh = new THREE.Mesh(geo, mat);
      var cx = 0, cz = 0;
      for (var k = 0; k < poly.length; k++) { cx += poly[k][0]; cz += poly[k][1]; }
      cx /= poly.length; cz /= poly.length;
      mesh.position.set(cx * worldScale, 0.05, cz * worldScale);
      scene.add(mesh);
      disposers.push(function () { geo.dispose(); });
      trackBounds(bounds, cx * worldScale, cz * worldScale);
    });
    disposers.push(function () { resiMat.dispose(); resiMat2.dispose(); });

    // Roads (batched by type, drawn as thin rectangles on top of land)
    var roadWidths = { primary: 0.6, secondary: 0.5, tertiary: 0.4, residential: 0.3, unclassified: 0.3, service: 0.2, path: 0.15, footway: 0.12, track: 0.25 };
    var roadColors = { primary: 0xfff5d6, secondary: 0xfff5d6, tertiary: 0xe8d4a8, residential: 0xd4b896, unclassified: 0xb89a76, service: 0xa48a66, path: 0x8a7050, footway: 0x705a40, track: 0x705a40 };
    var byType = {};
    roads.forEach(function (r) { (byType[r.type] = byType[r.type] || []).push(r); });
    Object.keys(byType).forEach(function (rt) {
      var positions = [];
      byType[rt].forEach(function (road) {
        var w = roadWidths[rt] || 0.3;
        for (var i = 0; i < road.coords.length - 1; i++) {
          var x1 = road.coords[i][0] * worldScale, z1 = road.coords[i][1] * worldScale;
          var x2 = road.coords[i+1][0] * worldScale, z2 = road.coords[i+1][1] * worldScale;
          var dx = x2 - x1, dz = z2 - z1;
          var len = Math.sqrt(dx*dx + dz*dz);
          if (len < 0.001) continue;
          var nx = -dz / len * w * 0.5, nz = dx / len * w * 0.5;
          positions.push(x1+nx, 0.3, z1+nz, x2+nx, 0.3, z2+nz, x1-nx, 0.3, z1-nz);
          positions.push(x2+nx, 0.3, z2+nz, x2-nx, 0.3, z2-nz, x1-nx, 0.3, z1-nz);
        }
      });
      if (!positions.length) return;
      var geo = new THREE.BufferGeometry();
      geo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
      var mat = new THREE.MeshBasicMaterial({ color: roadColors[rt] || 0xd4b896, side: THREE.DoubleSide });
      scene.add(new THREE.Mesh(geo, mat));
      disposers.push(function () { geo.dispose(); mat.dispose(); });
    });

    // School landmark (yellow post + coral beacon + sand building + coral roof + gold flag)
    var ring = null, ringMat = null, label = null;
    var sx = school.x * worldScale, sz = school.z * worldScale;
    trackBounds(bounds, sx, sz);
    var post = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.08, 1.5, 8), new THREE.MeshBasicMaterial({ color: 0xF4B400 }));
    post.position.set(sx, 0.85, sz);
    scene.add(post);
    var beacon = new THREE.Mesh(new THREE.SphereGeometry(0.5, 12, 8), new THREE.MeshBasicMaterial({ color: 0xe8734a }));
    beacon.position.set(sx, 1.7, sz);
    scene.add(beacon);
    var grp = new THREE.Group();
    grp.add(new THREE.Mesh(new THREE.BoxGeometry(0.6, 0.5, 0.6), new THREE.MeshBasicMaterial({ color: 0xEDE6D6 })));
    grp.children[grp.children.length-1].position.y = 0.25;
    grp.add(new THREE.Mesh(new THREE.ConeGeometry(0.55, 0.4, 4), new THREE.MeshBasicMaterial({ color: 0xe8734a })));
    grp.children[grp.children.length-1].position.y = 0.7;
    grp.children[grp.children.length-1].rotation.y = Math.PI / 4;
    grp.add(new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.5, 4), new THREE.MeshBasicMaterial({ color: 0xcfe8e4 })));
    grp.children[grp.children.length-1].position.set(0.2, 1.0, 0.0);
    grp.add(new THREE.Mesh(new THREE.PlaneGeometry(0.3, 0.18), new THREE.MeshBasicMaterial({ color: 0xF4B400 })));
    grp.children[grp.children.length-1].position.set(0.35, 1.18, 0.0);
    grp.position.set(sx, 2.2, sz);
    scene.add(grp);
    disposers.push(function () { grp.traverse(function (m) { if (m.geometry) m.geometry.dispose(); if (m.material) m.material.dispose(); }); post.geometry.dispose(); post.material.dispose(); beacon.geometry.dispose(); beacon.material.dispose(); });

    // Pulsing ground ring
    var ringGeo = new THREE.RingGeometry(0.6, 0.8, 24);
    ringMat = new THREE.MeshBasicMaterial({ color: 0xe8734a, transparent: true, opacity: 0.5, side: THREE.DoubleSide });
    ring = new THREE.Mesh(ringGeo, ringMat);
    ring.rotation.x = -Math.PI / 2;
    ring.position.set(sx, 0.4, sz);
    scene.add(ring);
    disposers.push(function () { ringGeo.dispose(); ringMat.dispose(); });

    // DOM label projected onto the canvas
    label = document.createElement('div');
    label.style.cssText = 'position:absolute;color:var(--sun-gold,#F4B400);font-family:var(--font-utility,monospace);font-size:0.7rem;text-shadow:0 0 4px rgba(0,0,0,0.9);pointer-events:none;white-space:nowrap;z-index:3;';
    label.textContent = 'BBNIHS - ref ' + (school.ref || '305053') + ' - est. ' + (school.start_date || '1966');
    label.setAttribute('aria-hidden', 'true');
    wrap.style.position = wrap.style.position || 'relative';
    wrap.appendChild(label);

    // Camera framing from data bounds
    frameCamera(camera, bounds);

    // ResizeObserver
    function handleResize() {
      var w = wrap.clientWidth, h = wrap.clientHeight;
      if (w === 0 || h === 0) return;
      renderer.setSize(w, h);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
      updateLabel();
    }
    var ro = ('ResizeObserver' in window) ? new ResizeObserver(handleResize) : null;
    if (ro) ro.observe(wrap); else window.addEventListener('resize', handleResize);
    setTimeout(handleResize, 300);
    setTimeout(handleResize, 1000);

    function updateLabel() {
      if (!label) return;
      if (window.matchMedia && window.matchMedia('(max-width: 860px)').matches) {
        label.style.display = 'none';
        return;
      }
      label.style.display = '';
      var v = new THREE.Vector3(sx, 2.6, sz);
      v.project(camera);
      var w = wrap.clientWidth, h = wrap.clientHeight;
      var x = (v.x + 1) / 2 * w;
      var y = (-v.y + 1) / 2 * h;
      // Clamp so the label can never sit outside the canvas bounds, even
      // if the projected 3D point falls off-frame after a camera change.
      var pad = 8;
      x = Math.max(pad, Math.min(w - pad, x));
      y = Math.max(pad, Math.min(h - pad, y));
      label.style.left = x + 'px';
      label.style.top = y + 'px';
      label.style.transform = 'translate(-50%, -100%)';
    }

    // Animate
    var t0 = performance.now();
    var rafId = null;
    var camMid = { x: camera.position.x, z: camera.position.z };
    function frame(now) {
      var t = (now - t0) / 1000;
      waterMat.uniforms.uTime.value = t;
      camera.position.x = camMid.x + Math.sin(t * 0.04) * 2;
      camera.position.z = camMid.z + Math.cos(t * 0.04) * 2;
      camera.lookAt(bounds.minX + (bounds.maxX-bounds.minX)/2, 0, bounds.minZ + (bounds.maxZ-bounds.minZ)/2);
      if (ring) {
        var s = 1 + Math.sin(t * 2) * 0.2;
        ring.scale.set(s, s, 1);
        ringMat.opacity = 0.5 - Math.sin(t * 2) * 0.3;
      }
      renderer.render(scene, camera);
      updateLabel();
      rafId = requestAnimationFrame(frame);
    }
    rafId = requestAnimationFrame(frame);

    // Teardown
    var teardown = function () {
      cancelAnimationFrame(rafId);
      if (ro) ro.disconnect(); else window.removeEventListener('resize', handleResize);
      disposers.forEach(function (fn) { try { fn(); } catch (e) {} });
      renderer.dispose();
      if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
      if (label && label.parentNode) label.parentNode.removeChild(label);
    };
    state.cleanupFns.push(teardown);
  }

  function init(rootEl) {
    if (shouldSkip()) return;
    var wrap = rootEl || document.querySelector('.hero__canvas-wrap');
    if (!wrap) return;
    if (state.armed) return;
    state.armed = true;

    function arm() { teardownArmTriggers(); startInit(); }
    function startInit() {
      Promise.all([
        loadThree(),
        fetchJSON(DATA_BASE + '/meta.json').catch(function () { return null; }),
        fetchJSON(DATA_BASE + '/islands.json').catch(function () { return null; }),
        fetchJSON(DATA_BASE + '/roads.json').catch(function () { return null; }),
        fetchJSON(DATA_BASE + '/landuse.json').catch(function () { return null; }),
        fetchJSON(DATA_BASE + '/school.json').catch(function () { return null; })
      ]).then(function (results) {
        startScene(wrap, results[1], results[2], results[3], results[4], results[5]);
      }).catch(function () { /* keep fallback visible */ });
    }

    // --- Mobile check: small viewport arms immediately ---
    var isMobile = window.matchMedia && window.matchMedia('(max-width: 720px)').matches;

    var onScroll = function () { arm(); };
    var onPointer = function () { arm(); };
    var timeoutId = null;

    function teardownArmTriggers() {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('pointerdown', onPointer);
      if (timeoutId) clearTimeout(timeoutId);
    }

    if (isMobile) {
      // Skip the scroll/pointer wait entirely. Defer to the next paint
      // (rAF) instead of firing perfectly synchronously, so the fallback
      // gradient still paints first and the page doesn't jank on load.
      requestAnimationFrame(function () { arm(); });
    } else {
      // Desktop: unchanged behavior — first of scroll, pointerdown, or 2.5s
      timeoutId = setTimeout(arm, 2500);
      window.addEventListener('scroll', onScroll, { passive: true, once: true });
      window.addEventListener('pointerdown', onPointer, { passive: true, once: true });
    }

    state.cleanupFns.push(teardownArmTriggers);
  }

  function destroy() {
    state.destroyed = true;
    state.cleanupFns.forEach(function (fn) { try { fn(); } catch (e) {} });
    state.cleanupFns = [];
    state.armed = false;
  }

  window.SmartCampusHero = { init: init, destroy: destroy };
  document.addEventListener('DOMContentLoaded', function () { init(); });
  if (document.readyState !== 'loading') init();
})();
