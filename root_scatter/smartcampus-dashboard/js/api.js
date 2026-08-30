/**
 * api.js — Centralized API client for SmartCampus dashboard.
 *
 * All calls go through the single API_BASE constant below.
 * Change this one line to switch between local dev and production.
 *
 * Uses credentials: 'include' so session cookies are sent cross-origin
 * (requires the backend to set SameSite=None; Secure on the session cookie).
 */
(function () {
  'use strict';

  // ── Configuration ──────────────────────────────────────────────
  // Production: the Render backend URL
  // Local dev:   change to your local PHP server URL
  window.SMARTCAMPUS = window.SMARTCAMPUS || {};
  window.SMARTCAMPUS.API_BASE =
    window.SMARTCAMPUS.API_BASE ||
    'https://smartcampk12.onrender.com';

  // ── CSRF token (injected server-side as <meta> or via BOOT object) ──
  var TOKEN = window.SMARTCAMPUS.TOKEN || null;

  // ── Course period context (injected server-side) ──
  var COURSE_PERIOD_ID = window.SMARTCAMPUS.COURSE_PERIOD_ID || 0;
  var PERIOD_ID = window.SMARTCAMPUS.PERIOD_ID || 0;

  // ── Low-level fetch wrapper ────────────────────────────────────
  function callService(modfunc, params, method) {
    params = params || {};
    method = method || 'GET';

    var url = window.SMARTCAMPUS.API_BASE +
      '/Modules.php?modname=SmartCampus/Ajax.php&modfunc=' +
      encodeURIComponent(modfunc);

    var headers = { 'Content-Type': 'application/x-www-form-urlencoded' };

    if (method === 'GET') {
      Object.keys(params).forEach(function (k) {
        url += '&' + k + '=' + encodeURIComponent(params[k]);
      });
      return fetch(url, {
        method: 'GET',
        credentials: 'include',
        headers: headers
      }).then(handleResponse);
    }

    // POST
    var body = new URLSearchParams(
      Object.assign({ token: TOKEN }, flatten(params))
    );
    return fetch(url, {
      method: 'POST',
      credentials: 'include',
      headers: headers,
      body: body
    }).then(handleResponse);
  }

  // ── Helpers ────────────────────────────────────────────────────
  function flatten(obj) {
    var out = {};
    Object.keys(obj).forEach(function (k) {
      if (typeof obj[k] === 'object' && obj[k] !== null) {
        Object.keys(obj[k]).forEach(function (sub) {
          out['marks[' + sub + ']'] = obj[k][sub];
        });
      } else {
        out[k] = obj[k];
      }
    });
    return out;
  }

  function handleResponse(res) {
    if (!res.ok) {
      return res.text().then(function (body) {
        throw new Error('HTTP ' + res.status + (body ? ': ' + body : ''));
      });
    }
    var ct = res.headers.get('content-type') || '';
    if (ct.indexOf('application/json') === -1) {
      return res.text().then(function (body) {
        throw new Error('Expected JSON, got: ' + body.substring(0, 200));
      });
    }
    return res.json();
  }

  // ── Public API ─────────────────────────────────────────────────
  window.SMARTCAMPUS.api = {
    call: callService,

    portalStats: function () {
      return callService('portal_stats');
    },

    attendanceCodes: function () {
      return callService('attendance_codes');
    },

    attendanceList: function (coursePeriodId, periodId) {
      return callService('attendance_list', {
        course_period_id: coursePeriodId || COURSE_PERIOD_ID,
        period_id: periodId || PERIOD_ID
      });
    },

    attendanceSave: function (marks, coursePeriodId, periodId) {
      return callService('attendance_save', {
        course_period_id: coursePeriodId || COURSE_PERIOD_ID,
        period_id: periodId || PERIOD_ID,
        marks: marks
      }, 'POST');
    },

    disciplineList: function () {
      return callService('discipline_list');
    },

    enrollmentList: function () {
      return callService('enrollment_list');
    }
  };
})();