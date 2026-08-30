/* =====================================================================
 * SmartCampus K-12 — main.js
 * Tier detection · nav · a11y toggles · connection badge
 * PR-2 search and PR-3 floating button are loaded as separate modules.
 * ===================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;

    // ---------- Tier detection (Part 2) ----------
    function detectTier() {
        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (conn && (conn.saveData || /^(slow-2g|2g)$/.test(conn.effectiveType || ''))) {
            return 'static';
        }
        try {
            var c = document.createElement('canvas');
            var gl = c.getContext('webgl') || c.getContext('experimental-webgl');
            if (!gl) return 'css-only';
        } catch (e) { return 'css-only'; }
        return 'webgl';
    }
    function applyTier(tier) {
        if (tier === 'static' || tier === 'css-only') {
            root.classList.add('tier-static');
        }
    }
    applyTier(detectTier());

    // ---------- Accessibility toggles ----------
    var A11Y_KEYS = { hc: 'a11y_hc', dys: 'a11y_dys', large: 'a11y_large' };
    function readA11y() {
        try {
            return {
                hc: localStorage.getItem(A11Y_KEYS.hc) === '1',
                dys: localStorage.getItem(A11Y_KEYS.dys) === '1',
                large: localStorage.getItem(A11Y_KEYS.large) === '1'
            };
        } catch (e) { return { hc: false, dys: false, large: false }; }
    }
    function writeA11y(s) {
        try {
            localStorage.setItem(A11Y_KEYS.hc, s.hc ? '1' : '0');
            localStorage.setItem(A11Y_KEYS.dys, s.dys ? '1' : '0');
            localStorage.setItem(A11Y_KEYS.large, s.large ? '1' : '0');
        } catch (e) {}
    }
    function applyA11y(s) {
        root.classList.toggle('a11y-hc', s.hc);
        root.classList.toggle('a11y-dyslexic', s.dys);
        root.classList.toggle('a11y-large', s.large);
        // Per spec Part 2: a11y toggles gate the animated tier (reduce-motion friendly)
        if (s.hc || s.dys) applyTier('css-only');
        var btnHc = document.getElementById('a11yHc');
        var btnDys = document.getElementById('a11yDys');
        var btnLarge = document.getElementById('a11yLarge');
        if (btnHc) btnHc.setAttribute('aria-pressed', s.hc ? 'true' : 'false');
        if (btnDys) btnDys.setAttribute('aria-pressed', s.dys ? 'true' : 'false');
        if (btnLarge) btnLarge.setAttribute('aria-pressed', s.large ? 'true' : 'false');
    }
    function toggleA11y(key) {
        var s = readA11y();
        s[key] = !s[key];
        writeA11y(s);
        applyA11y(s);
    }
    ['a11yHc', 'a11yDys', 'a11yLarge'].forEach(function (id, i) {
        var k = ['hc', 'dys', 'large'][i];
        var b = document.getElementById(id);
        if (b) b.addEventListener('click', function () { toggleA11y(k); });
    });
    applyA11y(readA11y());

    // ---------- Connection badge ----------
    var connBadge = document.getElementById('connBadge');
    function updateConn() {
        if (!connBadge) return;
        if (navigator.onLine) {
            connBadge.setAttribute('data-state', 'online');
            connBadge.innerHTML = '<span class="dot"></span>Online';
        } else {
            connBadge.setAttribute('data-state', 'offline');
            connBadge.innerHTML = '<span class="dot"></span>Offline — saved locally';
        }
    }
    if (connBadge) {
        updateConn();
        window.addEventListener('online', updateConn);
        window.addEventListener('offline', updateConn);
    }

    // ---------- Smooth scroll for nav links ----------
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var id = a.getAttribute('href').slice(1);
            var t = document.getElementById(id);
            if (t) {
                e.preventDefault();
                t.scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (history.replaceState) history.replaceState(null, '', '#' + id);
            }
        });
    });

    // ---------- Active nav link via IntersectionObserver ----------
    var navLinks = document.querySelectorAll('.top-nav__link');
    var map = {};
    navLinks.forEach(function (a) {
        var id = a.getAttribute('href').slice(1);
        var t = document.getElementById(id);
        if (t) map[id] = a;
    });
    if (Object.keys(map).length && 'IntersectionObserver' in window) {
        var navIo = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                var a = map[e.target.id];
                if (!a) return;
                if (e.isIntersecting) {
                    navLinks.forEach(function (l) { l.classList.remove('is-active'); });
                    a.classList.add('is-active');
                }
            });
        }, { rootMargin: '-40% 0px -55% 0px' });
        Object.keys(map).forEach(function (id) { navIo.observe(document.getElementById(id)); });
    }
})();

// --- PWA Service Worker Registration ---
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/pwabuilder-sw.js')
      .then(function(registration) {
        console.log('SW registered:', registration.scope);
      })
      .catch(function(error) {
        console.log('SW registration failed:', error);
      });
  });
}
