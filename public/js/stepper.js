/* =====================================================================
 * SmartCampus K-12 — stepper.js
 * Two steppers unified: the 7-state Status Timeline (which is a real
 * sequence per the spec, so number/order is justified), and the 5-step
 * Start-Your-Enrollment Wizard. Same visual language: dot-and-line.
 * ===================================================================== */
(function () {
    'use strict';

    // ---------- Status timeline draw-in (SVG path) ----------
    var timelineSvg = document.querySelector('.status-bar');
    if (timelineSvg) {
        // CSS handles the visual styling. JS only injects the active step class
        // based on the current admissions status (fetched from /enroll_api.php?action=config).
        fetch('enroll_api.php?action=config', { cache: 'no-store' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.period) return;
                var states = ['Submitted', 'Under Review', 'Documents Needed', 'Verified', 'Approved', 'Enrolled', 'Rejected'];
                var current = data.period.status === 'Open' ? 'Submitted' : 'Submitted';
                // Highlight Submitted as active when enrollment is Open
                var steps = timelineSvg.querySelectorAll('.status-step');
                steps.forEach(function (s) {
                    var label = (s.getAttribute('data-step') || '').trim();
                    if (label === current) s.classList.add('status-step--active');
                    else if (states.indexOf(label) < states.indexOf(current)) s.classList.add('status-step--done');
                });
            })
            .catch(function () { /* silent — status is decorative */ });
    }

    // ---------- Wizard (5-step enrollment form) ----------
    // Schema: data-step panels, .wizard__step indicators, .wizard__progress bar
    var wizard = document.querySelector('.wizard');
    if (!wizard) return;

    var steps = Array.prototype.slice.call(wizard.querySelectorAll('[data-wizard-step]'));
    var dots = Array.prototype.slice.call(wizard.querySelectorAll('.wizard__step'));
    var progress = wizard.querySelector('.wizard__progress');
    var current = 0;
    var total = steps.length;

    function show(n) {
        if (n < 0 || n >= total) return;
        current = n;
        steps.forEach(function (s, i) {
            s.hidden = (i !== n);
        });
        dots.forEach(function (d, i) {
            d.classList.toggle('wizard__step--active', i === n);
            d.classList.toggle('wizard__step--done', i < n);
        });
        if (progress) {
            var pct = total > 1 ? (n / (total - 1)) * 100 : 0;
            progress.style.setProperty('--progress', pct + '%');
        }
    }

    var nextBtn = wizard.querySelector('[data-wizard-next]');
    var prevBtn = wizard.querySelector('[data-wizard-prev]');
    if (nextBtn) nextBtn.addEventListener('click', function () { show(current + 1); });
    if (prevBtn) prevBtn.addEventListener('click', function () { show(current - 1); });

    // Mark a step done when its required fields are filled (lightweight validation)
    steps.forEach(function (panel, i) {
        var dot = dots[i];
        if (!dot) return;
        panel.addEventListener('change', function () {
            var required = panel.querySelectorAll('[required]');
            var allFilled = Array.prototype.every.call(required, function (r) { return r.value && r.value.trim() !== ''; });
            if (allFilled) dot.classList.add('wizard__step--done');
        });
    });

    show(0);
})();
