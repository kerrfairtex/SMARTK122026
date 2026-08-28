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

    // ---------- Floating contact (PR-3, preserved) ----------
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
            if (result) result.innerHTML = '<p class="form-note">Submitting&hellip;</p>';
            var data = {};
            new FormData(wizardForm).forEach(function (v, k) { data[k] = v; });
            fetch('enroll_api.php?action=draft_finalize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!result) return;
                if (res.ok && res.j.ok) {
                    result.innerHTML = '<p style="color: #4ade80;">Application received. Reference: <strong>' + (res.j.ref || '—') + '</strong></p>';
                } else {
                    result.innerHTML = '<p style="color: var(--reef-coral);">' + (res.j.error || 'Submission failed. Please try again.') + '</p>';
                }
            })
            .catch(function () {
                if (result) result.innerHTML = '<p style="color: var(--reef-coral);">Network error. Please try again or submit at the school.</p>';
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
