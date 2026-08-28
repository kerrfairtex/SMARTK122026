/* =====================================================================
 * SmartCampus K-12 — reveal.js
 * Tier 1 scroll-reveal via IntersectionObserver. The single bold motion
 * is the hero scene; everything else is restrained fade-rise.
 * ===================================================================== */
(function () {
    'use strict';
    if (!('IntersectionObserver' in window)) {
        // No IO support: show everything immediately.
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-in'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.classList.add('is-in');
                io.unobserve(e.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
})();
