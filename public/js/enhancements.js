/* =====================================================================
 * SmartCampus K-12 — enhancements.js
 * Page-level enhancements: count-up, floating contact, search, clock,
 * wizard submit. Spec Part 3 §3 (count-up), PR-2 (search), PR-3
 * (floating contact), hero clock.
 * ===================================================================== */
(function () {
    'use strict';

    // ---------- Count-up (Part 3 §3 + §2) ----------
    if ('IntersectionObserver' in window) {
        var counterIo = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    animateCount(e.target);
                    counterIo.unobserve(e.target);
                }
            });
        }, { threshold: 0.4 });
        document.querySelectorAll('.counter__number[data-target]').forEach(function (el) { counterIo.observe(el); });
    } else {
        document.querySelectorAll('.counter__number[data-target]').forEach(animateCount);
    }
    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-target'), 10);
        if (!target) return;
        var dur = Math.min(1200, 700 + Math.log10(Math.max(10, target)) * 200);
        var t0 = performance.now();
        function step(now) {
            var t = Math.min(1, (now - t0) / dur);
            var eased = 1 - Math.pow(1 - t, 3);
            el.textContent = Math.floor(eased * target).toLocaleString('en-PH');
            if (t < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('en-PH');
        }
        requestAnimationFrame(step);
    }

    // ---------- Floating contact (PR-3, removed in v1.7 - the personal
    // phone number was here, and the design now uses the contact section
    // routing table for all enquiries) ----------
    // The block below is a no-op; the elements no longer exist in the
    // DOM but the JS stays safe in case of cached pages.
    var fcLaunch = document.getElementById('fcLaunch');
    var fcContact = document.getElementById('floatContact');
    if (fcLaunch && fcContact) {
        function openFc() { fcContact.classList.add('open'); fcLaunch.setAttribute('aria-expanded', 'true'); }
        function closeFc() { fcContact.classList.remove('open'); fcLaunch.setAttribute('aria-expanded', 'false'); }
        function toggleFc() { fcContact.classList.contains('open') ? closeFc() : openFc(); }
        fcLaunch.addEventListener('click', function (e) { e.stopPropagation(); toggleFc(); });
        document.addEventListener('click', function (e) {
            if (fcContact.classList.contains('open') && !fcContact.contains(e.target)) closeFc();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeFc(); });
    }

    // ---------- Search (PR-2, preserved) ----------
    var searchDialog = document.getElementById('searchDialog');
    var searchInput = document.getElementById('searchInput');
    var searchResults = document.getElementById('searchResults');
    var searchLaunch = document.getElementById('searchLaunch');
    if (searchDialog && searchInput && searchResults) {
        var index = buildIndex();
        var activeIdx = 0;
        function buildIndex() {
            var items = [];
            document.querySelectorAll('section[id]').forEach(function (s) {
                var h2 = s.querySelector('h2');
                if (!h2) return;
                items.push({ id: s.id, type: 'section', label: h2.textContent.trim(), desc: 'Jump to section' });
            });
            // FAQ-like from history timeline
            document.querySelectorAll('.history-timeline__item').forEach(function (li, i) {
                var t = (li.querySelector('.history-timeline__year') || {}).textContent || '';
                var x = (li.querySelector('.history-timeline__text') || {}).textContent || '';
                items.push({ id: 'about', type: 'history', label: t + ' ' + x.slice(0, 60), desc: 'School history' });
            });
            return items;
        }
        function render(q) {
            searchResults.innerHTML = '';
            activeIdx = 0;
            if (!q || q.length < 1) { searchResults.innerHTML = '<li class="empty">Start typing&hellip;</li>'; return; }
            var ql = q.toLowerCase();
            var matches = index.filter(function (it) {
                return it.label.toLowerCase().indexOf(ql) !== -1 || it.desc.toLowerCase().indexOf(ql) !== -1;
            });
            if (!matches.length) { searchResults.innerHTML = '<li class="empty">No matches</li>'; return; }
            matches.forEach(function (m, i) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                if (i === 0) li.setAttribute('aria-selected', 'true');
                li.innerHTML = '<span class="type">' + m.type + '</span><span>' + m.label + '</span>';
                li.addEventListener('click', function () { go(m); });
                searchResults.appendChild(li);
            });
        }
        function go(m) {
            searchDialog.close();
            var t = document.getElementById(m.id);
            if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        function open() {
            if (searchDialog.open) return;
            searchDialog.showModal();
            setTimeout(function () { searchInput.focus(); searchInput.select(); }, 50);
            render('');
        }
        if (searchLaunch) searchLaunch.addEventListener('click', open);
        searchInput.addEventListener('input', function () { render(searchInput.value); });
        searchInput.addEventListener('keydown', function (e) {
            var items = Array.prototype.slice.call(searchResults.querySelectorAll('li[role="option"]'));
            if (!items.length) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = (activeIdx + 1) % items.length; highlight(items, activeIdx); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = (activeIdx - 1 + items.length) % items.length; highlight(items, activeIdx); }
            else if (e.key === 'Enter') { e.preventDefault(); var m = index.filter(function (it) { return it.label === items[activeIdx].querySelector('span:nth-child(2)').textContent; })[0]; if (m) go(m); }
            else if (e.key === 'Escape') { searchDialog.close(); }
        });
        function highlight(items, i) { items.forEach(function (l, j) { l.setAttribute('aria-selected', j === i ? 'true' : 'false'); }); }
        // Global shortcut
        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && document.activeElement && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault(); open();
            }
        });
    }

    // ---------- Hero clock (Asia/Manila) ----------
    var clockEl = document.getElementById('clockTime');
    var heroClock = document.getElementById('heroClock');
    if (clockEl) {
        function tick() {
            var d = new Date();
            var manila = d.toLocaleTimeString('en-GB', { timeZone: 'Asia/Manila', hour12: false });
            clockEl.textContent = manila;
            if (heroClock) heroClock.setAttribute('datetime', d.toISOString());
        }
        tick();
        setInterval(tick, 1000);
    }

    // ---------- Wizard submit (Part 3 §6) ----------
    var wizardForm = document.getElementById('wizardForm');
    if (wizardForm) {
        wizardForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var result = document.getElementById('wizardResult');
            var submitBtn = document.getElementById('wizardSubmit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('btn--loading');
                submitBtn.textContent = 'Submitting...';
            }
            if (result) result.innerHTML = '<p class="form-note" role="status">Submitting your application&hellip;</p>';
            var data = {};
            new FormData(wizardForm).forEach(function (v, k) { data[k] = v; });
            fetch('enroll_api.php?action=submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!result) return;
                if (res.ok && res.j.success && res.j.ref) {
                    // Build success state with reference and status-check link
                    var statusUrl = 'enroll_status.php?ref=' + encodeURIComponent(res.j.ref);
                    result.innerHTML = '<div class="submission-success" role="alert">' +
                        '<div class="submission-success__icon" aria-hidden="true">&#10003;</div>' +
                        '<h3 class="submission-success__title">Application Submitted Successfully</h3>' +
                        '<p class="submission-success__ref">Application Reference: <strong>' + res.j.ref + '</strong></p>' +
                        '<p class="submission-success__status">Status: Submitted</p>' +
                        '<p class="submission-success__help">Please save your application reference. You can use it to check your enrollment status.</p>' +
                        '<a href="' + statusUrl + '" class="btn btn--primary submission-success__link">Check Application Status</a>' +
                        '</div>';
                    // Re-enable submit for another application
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn--loading');
                        submitBtn.textContent = 'Submit Application';
                    }
                } else {
                    // Build error state with actionable message
                    var errorMsg = res.j.error || res.j.message || 'Submission failed. Please try again.';
                    var errorDetail = '';
                    if (res.j.error && res.j.error.includes('Missing required field')) {
                        errorDetail = '<p class="submission-error__detail">Please review the highlighted information and try again.</p>';
                    } else if (res.j.error && (res.j.error.includes('not currently open') || res.j.error.includes('closed'))) {
                        errorDetail = '<p class="submission-error__detail">Enrollment is currently closed. Please contact the school for assistance.</p>';
                    } else if (res.j.error && res.j.error.includes('rate limit')) {
                        errorDetail = '<p class="submission-error__detail">Too many attempts. Please wait a moment and try again.</p>';
                    }
                    result.innerHTML = '<div class="submission-error" role="alert">' +
                        '<div class="submission-error__icon" aria-hidden="true">&#10007;</div>' +
                        '<h3 class="submission-error__title">We could not submit your application</h3>' +
                        '<p class="submission-error__message">' + errorMsg + '</p>' +
                        errorDetail +
                        '<p class="submission-error__help">Your information has been preserved. Please correct the highlighted fields and try again.</p>' +
                        '</div>';
                }
            })
            .catch(function () {
                if (result) result.innerHTML = '<div class="submission-error" role="alert">' +
                    '<div class="submission-error__icon" aria-hidden="true">&#10007;</div>' +
                    '<h3 class="submission-error__title">Service Temporarily Unavailable</h3>' +
                    '<p class="submission-error__message">Your application could not be submitted because the service is temporarily unavailable.</p>' +
                    '<p class="submission-error__help">Please try again. If the problem persists, you may submit physically at the school.</p>' +
                    '</div>';
                // Re-enable submit button on network error so user can retry
                if (submitBtn) submitBtn.disabled = false;
            });
        });

        // Show submit button on the last step
        var lastStep = wizardForm.querySelector('[data-wizard-step="4"]');
        var submitBtn = document.getElementById('wizardSubmit');
        if (lastStep && submitBtn) {
            var obs = new MutationObserver(function () {
                if (!lastStep.hidden) submitBtn.style.display = '';
                else submitBtn.style.display = 'none';
            });
            obs.observe(lastStep, { attributes: true, attributeFilter: ['hidden'] });
        }
    }
})();
