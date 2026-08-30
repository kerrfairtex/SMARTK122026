/**
 * main.js — SmartCampus dashboard UI logic.
 *
 * Reads configuration from window.SMARTCAMPUS (set by the hosting page
 * or inlined by the server). Falls back to defaults for local dev.
 */
(function () {
  'use strict';

  // ── Configuration ──────────────────────────────────────────────
  var BOOT = window.SMARTCAMPUS || {
    API_BASE: 'https://smartcampk12.onrender.com',
    TOKEN: null,
    COURSE_PERIOD_ID: 0,
    PERIOD_ID: 0
  };

  var api = window.SMARTCAMPUS.api;

  // ── DOM refs ───────────────────────────────────────────────────
  var $ = function (id) { return document.getElementById(id); };
  var errorBanner = $('errorBanner');
  var toastEl = $('toast');

  // ── Toast / error helpers ──────────────────────────────────────
  function showToast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    setTimeout(function () { toastEl.classList.remove('show'); }, 2600);
  }

  function showError(msg) {
    errorBanner.textContent = msg;
    errorBanner.style.display = 'block';
  }

  function clearError() {
    errorBanner.style.display = 'none';
    errorBanner.textContent = '';
  }

  function setKpiLoading(id) {
    var el = $(id);
    if (el) {
      el.classList.add('loading');
      el.textContent = 'Loading…';
    }
  }

  // ── Bootstrap ──────────────────────────────────────────────────
  function init() {
    // Show course period context
    var cpLabel = $('cpLabel');
    if (cpLabel) {
      cpLabel.textContent = BOOT.COURSE_PERIOD_ID || '—';
    }

    // Load all data
    clearError();
    loadPortalStats();
    loadAttendance();
    loadDiscipline();
    loadEnrollment();
  }

  // ── Portal stats ───────────────────────────────────────────────
  function loadPortalStats() {
    setKpiLoading('kpiEnrolled');
    setKpiLoading('kpiReferrals');

    api.portalStats()
      .then(function (data) {
        var enrolled = $('kpiEnrolled');
        var referrals = $('kpiReferrals');
        if (enrolled) {
          enrolled.classList.remove('loading');
          enrolled.textContent = data.totalEnrolled != null ? data.totalEnrolled : '—';
        }
        if (referrals) {
          referrals.classList.remove('loading');
          referrals.textContent = data.referralsThisYear != null ? data.referralsThisYear : '—';
        }
      })
      .catch(function (err) {
        showError('Could not load portal stats: ' + err.message);
        var enrolled = $('kpiEnrolled');
        var referrals = $('kpiReferrals');
        if (enrolled) { enrolled.classList.remove('loading'); enrolled.textContent = '—'; }
        if (referrals) { referrals.classList.remove('loading'); referrals.textContent = '—'; }
      });
  }

  // ── Attendance ─────────────────────────────────────────────────
  var currentMarks = {};
  var attendanceCodes = [];

  function loadAttendance() {
    var attTable = $('attTable');
    if (!attTable) return;

    api.attendanceCodes()
      .then(function (data) {
        attendanceCodes = data.codes || [];
        return api.attendanceList(BOOT.COURSE_PERIOD_ID, BOOT.PERIOD_ID);
      })
      .then(function (data) {
        renderAttendanceTable(data.learners || []);
      })
      .catch(function (err) {
        if (attTable) {
          attTable.innerHTML =
            '<tr><td colspan="3" style="color:var(--coral)">Could not load attendance: ' +
            err.message + '</td></tr>';
        }
      });
  }

  function renderAttendanceTable(learners) {
    var attTable = $('attTable');
    if (!attTable) return;

    var rows = '<tr><th>Learner</th><th>ID #</th><th>Mark</th></tr>';

    if (learners.length === 0) {
      rows = '<tr><td colspan="3" style="color:var(--text-3)">No learners scheduled in this course period.</td></tr>';
      attTable.innerHTML = rows;
      return;
    }

    learners.forEach(function (l) {
      var defaultCode = attendanceCodes.find(function (c) { return c.DEFAULT_CODE === 'Y'; });
      var current = l.ATTENDANCE_CODE || (defaultCode ? defaultCode.id : null);
      currentMarks[l.STUDENT_ID] = current;

      var markButtons = attendanceCodes.map(function (c) {
        var sel = String(current) === String(c.ID) ? 'selected' : '';
        return '<button class="att-btn ' + sel + '" data-student="' + l.STUDENT_ID +
          '" data-code="' + c.ID + '" title="' + (c.TITLE || '') + '">' +
          (c.STATE_CODE || c.SHORT_NAME || c.TITLE) + '</button>';
      }).join('');

      rows += '<tr><td>' + (l.FIRST_NAME || '') + ' ' + (l.LAST_NAME || '') +
        '</td><td>' + (l.ID_NUMBER || '—') + '</td><td>' + markButtons + '</td></tr>';
    });

    attTable.innerHTML = rows;

    // Wire up mark buttons
    attTable.querySelectorAll('.att-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var sid = btn.dataset.student;
        var code = btn.dataset.code;
        attTable.querySelectorAll('.att-btn[data-student="' + sid + '"]').forEach(function (b) {
          b.classList.remove('selected');
        });
        btn.classList.add('selected');
        currentMarks[sid] = code;
      });
    });
  }

  // ── Discipline ─────────────────────────────────────────────────
  function loadDiscipline() {
    var discTable = $('discTable');
    if (!discTable) return;

    api.disciplineList()
      .then(function (data) {
        var rows = '<tr><th>Learner</th><th>Date</th><th>Category</th></tr>';
        var referrals = data.referrals || [];

        if (referrals.length === 0) {
          rows = '<tr><td colspan="3" style="color:var(--text-3)">No referrals on file.</td></tr>';
        } else {
          referrals.forEach(function (r) {
            rows += '<tr><td>' + (r.FIRST_NAME || '') + ' ' + (r.LAST_NAME || '') +
              '</td><td>' + (r.REFERRAL_DATE || '—') + '</td><td>' +
              (r.CATEGORY_1 || '—') + '</td></tr>';
          });
        }
        discTable.innerHTML = rows;
      })
      .catch(function (err) {
        if (discTable) {
          discTable.innerHTML =
            '<tr><td colspan="3" style="color:var(--coral)">Could not load referrals: ' +
            err.message + '</td></tr>';
        }
      });
  }

  // ── Enrollment ─────────────────────────────────────────────────
  function loadEnrollment() {
    var enrollTable = $('enrollTable');
    if (!enrollTable) return;

    api.enrollmentList()
      .then(function (data) {
        var rows = '<tr><th>Learner</th><th>Grade</th><th>Start</th><th>Status</th></tr>';
        var enrollments = data.enrollments || [];

        if (enrollments.length === 0) {
          rows = '<tr><td colspan="4" style="color:var(--text-3)">No enrollments on file.</td></tr>';
        } else {
          enrollments.forEach(function (e) {
            var status = e.END_DATE ? 'Withdrawn' : 'Active';
            rows += '<tr><td>' + (e.FIRST_NAME || '') + ' ' + (e.LAST_NAME || '') +
              '</td><td>' + (e.GRADE_ID || '—') + '</td><td>' +
              (e.START_DATE || '—') + '</td><td>' + status + '</td></tr>';
          });
        }
        enrollTable.innerHTML = rows;
      })
      .catch(function (err) {
        if (enrollTable) {
          enrollTable.innerHTML =
            '<tr><td colspan="4" style="color:var(--coral)">Could not load enrollments: ' +
            err.message + '</td></tr>';
        }
      });
  }

  // ── Attendance save ────────────────────────────────────────────
  function initSaveButton() {
    var saveBtn = $('saveAttBtn');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving…';

      api.attendanceSave(currentMarks, BOOT.COURSE_PERIOD_ID, BOOT.PERIOD_ID)
        .then(function (res) {
          showToast('Attendance saved for ' + (res.saved || 0) + ' learners');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Submit attendance';
        })
        .catch(function (err) {
          showError('Save failed: ' + err.message);
          saveBtn.disabled = false;
          saveBtn.textContent = 'Submit attendance';
        });
    });
  }

  // ── Init on DOM ready ──────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      init();
      initSaveButton();
    });
  } else {
    init();
    initSaveButton();
  }
})();