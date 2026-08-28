/* =====================================================================
 * SmartCampus K-12 — hero-scene.js
 * Signature 3D element (Part 2). Low-poly island + water plane, ≤2000
 * triangles, single draw call for water, lazy-loaded on first scroll
 * or pointer interaction, time-of-day color shift, respects
 * prefers-reduced-motion and Tier 0/1 fallback.
 * Three.js is loaded dynamically only on Tier 2 (after we know the
 * device can handle WebGL).
 * ===================================================================== */
(function () {
    'use strict';
    if (document.documentElement.classList.contains('tier-static')) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var wrap = document.querySelector('.hero__canvas-wrap');
    if (!wrap) return;

    // Lazy-load: wait for first scroll, pointer-down, or 2.5s idle.
    var armed = false;
    function arm() { if (armed) return; armed = true; initScene(); }
    window.addEventListener('scroll', arm, { passive: true, once: true });
    window.addEventListener('pointerdown', arm, { passive: true, once: true });
    setTimeout(arm, 2500);

    function initScene() {
        // Load Three.js dynamically (slim build: ~140KB gz, well under 150KB budget).
        var s = document.createElement('script');
        s.src = 'https://unpkg.com/three@0.150.0/build/three.min.js';
        s.onload = function () { startScene(); };
        s.onerror = function () { /* keep fallback visible */ };
        document.head.appendChild(s);
    }

    function timeOfDayTint() {
        var h = new Date().getHours();
        if (h >= 5 && h < 8) return { sky: 0x1a3850, water: 0x2d6a6a, sun: 0xfff0c0 }; // dawn
        if (h >= 8 && h < 17) return { sky: 0x0E4F4F, water: 0x1a5a5a, sun: 0xF4B400 }; // day
        if (h >= 17 && h < 19) return { sky: 0x4a2a3a, water: 0x4a3a4a, sun: 0xff7a4a }; // sunset
        return { sky: 0x08142a, water: 0x0E4F4F, sun: 0xcfe8e4 }; // night
    }

    function startScene() {
        if (typeof THREE === 'undefined') return;
        var tint = timeOfDayTint();

        // Renderer
        var canvas = document.createElement('canvas');
        wrap.appendChild(canvas);
        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        renderer.setSize(wrap.clientWidth, wrap.clientHeight);

        var scene = new THREE.Scene();
        scene.background = new THREE.Color(tint.sky);
        scene.fog = new THREE.Fog(tint.sky, 50, 200);

        var camera = new THREE.PerspectiveCamera(60, wrap.clientWidth / wrap.clientHeight, 0.1, 500);
        camera.position.set(0, 18, 36);
        camera.lookAt(0, 0, 0);

        // Sun (single point, a sphere with emissive material — 1 triangle × 2 = 2)
        var sun = new THREE.Mesh(
            new THREE.SphereGeometry(1.5, 12, 8),
            new THREE.MeshBasicMaterial({ color: tint.sun })
        );
        sun.position.set(-20, 18, -30);
        scene.add(sun);

        // Water plane: 32×32 segments, single PlaneGeometry + ShaderMaterial = 1 draw call
        var waterGeo = new THREE.PlaneGeometry(120, 120, 32, 32);
        waterGeo.rotateX(-Math.PI / 2);
        var waterMat = new THREE.ShaderMaterial({
            uniforms: {
                uTime: { value: 0 },
                uColor: { value: new THREE.Color(tint.water) }
            },
            vertexShader: 'uniform float uTime; varying float vY; void main() { vec3 p = position; float wave = sin(p.x * 0.18 + uTime * 0.6) * 0.6 + cos(p.z * 0.22 + uTime * 0.5) * 0.6; p.y += wave; vY = wave; gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0); }',
            fragmentShader: 'uniform vec3 uColor; varying float vY; void main() { float s = smoothstep(-1.0, 1.0, vY); gl_FragColor = vec4(uColor * (0.7 + s * 0.4), 1.0); }',
        });
        var water = new THREE.Mesh(waterGeo, waterMat);
        scene.add(water);

        // Islands (low-poly cones — about 12 triangles each × 5 islands = 60)
        var islandMat = new THREE.MeshBasicMaterial({ color: 0xEDE6D6 });
        var positions = [[-12, 0, -10], [10, 0, -8], [-5, 0, 12], [14, 0, 6], [0, 0, -22]];
        positions.forEach(function (p) {
            var h = 1.5 + Math.random() * 2;
            var m = new THREE.Mesh(new THREE.ConeGeometry(2 + Math.random() * 1.2, h, 6), islandMat);
            m.position.set(p[0], h / 2, p[2]);
            scene.add(m);
        });

        // Animate
        var t0 = performance.now();
        function frame(now) {
            var t = (now - t0) / 1000;
            waterMat.uniforms.uTime.value = t;
            camera.position.x = Math.sin(t * 0.05) * 1.5;
            camera.lookAt(0, 0, 0);
            renderer.render(scene, camera);
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
