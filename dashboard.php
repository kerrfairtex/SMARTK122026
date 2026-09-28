<?php
/**
 * SMARTCAMPUS-K12 Dashboard
 *
 * Teacher/Admin enrollment dashboard. Requires authentication via view_students_application.php.
 * Shows live application data from kerrfairtex.enrollment_applications.
 *
 * @package SmartCampus
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';

// Security headers
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://accounts.google.com https://apis.google.com https://www.googletagmanager.com https://www.google-analytics.com https://ssl.google-analytics.com https://cdn.ampproject.org; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\' https://smartcampk12.onrender.com; frame-ancestors \'none\'; form-action \'self\'; base-uri \'self\'; object-src \'none\';');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: ' . 'strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Auth check - must be teacher or admin
$profile = $_SESSION['PROFILE'] ?? '';
$staff_id = $_SESSION['STAFF_ID'] ?? null;
if (!$staff_id || !in_array($profile, ['admin', 'teacher'])) {
    header('Location: view_students_application.php');
    exit;
}

// Role for JS
$role = $profile === 'admin' ? 'admin' : 'teacher';

// Get CSRF token for AJAX calls
$token = $_SESSION['token'] ?? '';
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>SmartCampus K12 --- Dashboard</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap">
<style>
:root{box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px);
--bg:#0E1013;--panel:#15181C;--panel2:#1A1D22;--border:#262A2F;--border2:#1D2025;
--text:#ECE9E4;--muted:#8B9096;--accent:#E8A33D;--blue:#8FB4D4;--teal:#4FD1C5;--green:#7CC9A0;--terra:#C9836A}
html{scroll-padding-top:env(safe-area-inset-top,0px)}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:'IBM Plex Sans',sans-serif;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
button{font:inherit;cursor:pointer}
input,select{font:inherit}
.mono{font-family:'IBM Plex Mono',monospace}
.disp{font-family:'Space Grotesk',sans-serif}
#navcb{display:none}
.hidden{display:none!important}
#topbar{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--panel);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:30}
.hamburger{width:34px;height:34px;border:1px solid var(--border);border-radius:8px;background:var(--panel2);display:flex;align-items:center;justify-content:center;color:var(--text)}
.shell{display:flex;min-height:100vh}
.sidebar{width:240px;flex-shrink:0;background:var(--panel);border-right:1px solid var(--border);padding:24px 16px;display:flex;flex-direction:column}
.side-brand{display:flex;align-items:center;gap:10px;margin-bottom:26px;padding:0 6px}
.brand-mark{width:36px;height:36px;border-radius:10px;background:var(--accent);display:flex;align-items:center;justify-content:center}
.brand-mark span{font-family:'IBM Plex Mono',monospace;font-weight:600;font-size:13px;color:var(--bg)}
.brand-name{font-weight:600;font-size:14px}
.brand-sub{font-size:11px;color:var(--muted)}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;font-size:13.5px;font-weight:500;color:#A7ACB2;min-height:44px}
.nav-item:hover{background:#1C2025;color:var(--text)}
.nav-item.active{background:rgba(232,163,61,.14);color:var(--text)}
.nav-divider{height:1px;background:var(--border);margin:12px 6px}
.nav-ext{color:var(--muted);font-size:12px}
.side-foot{margin-top:auto;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.role-badge{font-size:12.5px}
.role-badge b{color:var(--text)}
.icon-btn{width:36px;height:36px;border:none;background:none;color:var(--muted);display:flex;align-items:center;justify-content:center;border-radius:8px}
.icon-btn:hover{background:#1E2126;color:var(--text)}
.scrim{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:40}
main{flex-grow:1;min-width:0;padding:26px 30px 40px}
.ic{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
.eyebrow{font-family:'IBM Plex Mono',monospace;font-size:11px;letter-spacing:.08em;color:var(--accent);text-transform:uppercase;font-weight:500}
h1.page-title{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:25px;margin:6px 0 20px;letter-spacing:-.01em}
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.stat{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:14px 16px}
.stat b{font-family:'IBM Plex Mono',monospace;font-weight:600;font-size:23px;display:block}
.stat span{font-size:10.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
.toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px}
.search-wrap{position:relative;flex:1 1 220px;max-width:300px}
.search-wrap .ic{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted)}
#q{width:100%;background:var(--panel);border:1px solid var(--border);border-radius:8px;padding:10px 12px 10px 32px;color:var(--text);min-height:44px}
#status-filter{background:var(--panel);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text);min-height:44px;appearance:none}
.btn-primary{background:var(--accent);color:var(--bg);border:none;border-radius:8px;padding:10px 18px;font-weight:600;font-size:13px;min-height:44px}
.btn-ghost{background:none;border:none;color:var(--muted);font-size:13px;text-decoration:underline;min-height:44px}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}
.table-card{background:var(--panel);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.table-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:760px}
th{padding:11px 16px;text-align:left;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);background:var(--panel2);border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:11px 16px;border-bottom:1px solid var(--border2);vertical-align:middle;font-size:13px}
tr:last-child td{border-bottom:none}
.learner-name{font-weight:600;font-size:13.5px}
.learner-grade{font-size:11.5px;color:var(--muted)}
.pill{display:inline-block;padding:4px 10px;border-radius:999px;font-family:'IBM Plex Mono',monospace;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
.row-actions{display:flex;gap:4px}
.act{width:30px;height:30px;border:none;background:none;color:var(--muted);border-radius:6px;display:flex;align-items:center;justify-content:center}
.act:hover{background:#1E2126;color:var(--text)}
.act.reject:hover{color:var(--terra)}
.act.delete:hover{color:var(--reef-coral)}
.act.approve:hover{color:var(--teal)}
.pager{padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.pager span{font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--muted)}
.pager-btns{display:flex;gap:6px}
.pg-btn{width:30px;height:30px;border:1px solid var(--border);background:none;border-radius:6px;color:var(--muted);display:flex;align-items:center;justify-content:center}
.pg-btn:disabled{opacity:.35;cursor:default}
.pg-btn:not(:disabled):hover{color:var(--text);border-color:var(--accent)}
.empty-row td{text-align:center;color:var(--muted);padding:30px 16px}
#modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:60;align-items:center;justify-content:center;padding:20px}
#modal.open{display:flex}
.modal-card{width:100%;max-width:420px;max-height:90vh;overflow-y:auto;background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:26px}
.modal-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
.modal-close{width:32px;height:32px;border:none;background:none;color:var(--muted);border-radius:8px;display:flex;align-items:center;justify-content:center}
.modal-close:hover{background:#1E2126;color:var(--text)}
.mrow{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--border2);font-size:13px}
.mrow span:first-child{color:var(--muted)}
.timeline{display:flex;flex-direction:column;gap:8px;margin:16px 0}
.tstep{display:flex;align-items:center;gap:10px;font-size:12.5px}
.tdot{width:9px;height:9px;border-radius:50%;background:var(--border);flex-shrink:0}
.tstep.done .tdot{background:var(--teal)}
.tstep.current .tdot{background:var(--accent)}
.tstep.done,.tstep.current{color:var(--text)}
.tstep:not(.done):not(.current){color:var(--muted)}
.modal-actions{display:flex;gap:8px;margin-top:18px}
.modal-actions button{flex:1;border-radius:8px;padding:11px;font-size:13px;font-weight:600;border:1px solid var(--border);background:var(--bg);color:var(--text)}
.modal-actions .m-approve{background:var(--teal);color:#08221f;border:none}
.modal-actions .m-reject{background:none;color:var(--terra);border:1px solid var(--terra)}
.modal-actions .m-enroll{background:var(--green);color:#08221a;border:none}
.modal-actions .m-delete{background:none;color:var(--reef-coral);border:1px solid var(--reef-coral)}
@media (max-width:860px){
  .topbar{display:flex}
  .sidebar{position:fixed;top:0;left:0;height:100%;width:240px;transform:translateX(-100%);transition:transform .2s ease;z-index:50}
  #navcb:checked ~ .shell .sidebar{transform:translateX(0)}
  #navcb:checked ~ .scrim{display:block}
  .shell{flex-direction:column}
  main{padding:18px 16px 32px}
  .stats{grid-template-columns:repeat(2,1fr)}
  h1.page-title{font-size:21px}
}
</style>
</head>
<body>

<input type="checkbox" id="navcb">
<div id="dashboard">
  <div class="topbar">
    <label for="navcb" class="hamburger" aria-label="Open menu"><svg class="ic" viewBox="0 0 24 24"><line x1="4" y1="7" x2="20" y2="7"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="17" x2="20" y2="17"></line></svg></label>
    <div class="brand-name disp">SmartCampus K12</div>
    <span class="role-badge mono" id="roleBadgeTop"></span>
  </div>
  <div class="shell">
    <aside class="sidebar" id="sidebar">
      <div class="side-brand">
        <div class="brand-mark"><span>SK</span></div>
        <div><div class="brand-name disp">SmartCampus K12</div><div class="brand-sub">Batu-Batu NHS</div></div>
      </div>
      <nav>
        <a href="#" class="nav-item active"><svg class="ic" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"></rect><line x1="8" y1="9" x2="16" y2="9"></line><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="13" y2="17"></line></svg>Enrollment</a>
        <div class="nav-divider"></div>
        <div class="nav-ext" style="padding:4px 12px 2px;text-transform:uppercase;letter-spacing:.05em;font-size:10px">School records</div>
        <a href="index.php" class="nav-item" title="Opens the school's official RosarioSIS system"><svg class="ic" viewBox="0 0 24 24"><path d="M9 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-3"></path><path d="M14 4h6v6"></path><path d="M20 4l-9 9"></path></svg>School Information Office</a>
      </nav>
      <div class="side-foot">
        <div class="role-badge"><b id="roleBadge">Admin</b> &middot; BBNHS</div>
        <a href="index.php?modfunc=logout&token=<?php echo $_SESSION['token'] ?? ''; ?>" id="logoutBtn" aria-label="Log out" class="icon-btn"><svg class="ic" viewBox="0 0 24 24"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"></path><line x1="21" y1="12" x2="10" y2="12"></line><path d="M16 7l5 5-5 5"></path></svg></a>
      </div>
    </aside>
    <main>
      <div class="eyebrow">Enrollment &middot; 2026&ndash;2027</div>
      <h1 class="page-title disp">Enrollment Applications</h1>

      <div class="stats" id="stats"></div>

      <div class="toolbar">
        <div class="search-wrap">
          <label class="sr-only" for="q">Search applications</label>
          <svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
          <input id="q" type="text" placeholder="Search ref, learner, guardian&hellip;">
        </div>
        <div>
          <label class="sr-only" for="status-filter">Filter by status</label>
          <select id="status-filter">
            <option value="all">All statuses</option>
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="enrolled">Enrolled</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <button class="btn-primary" id="filterBtn" type="button">Filter</button>
        <button class="btn-ghost" id="resetBtn" type="button">Reset</button>
      </div>

      <div class="table-card">
        <div class="table-scroll">
          <table>
            <thead><tr>
              <th>Ref</th><th>Learner</th><th>Guardian</th><th>Contact</th><th>Status</th><th>Submitted</th><th>Actions</th>
            </tr></thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
        <div class="pager">
          <span id="pagerInfo"></span>
          <div class="pager-btns">
            <button class="pg-btn" id="prevBtn" type="button" aria-label="Previous page"><svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px"><path d="M14.5 5l-6 7 6 7"></path></svg></button>
            <button class="pg-btn" id="nextBtn" type="button" aria-label="Next page"><svg class="ic" viewBox="0 0 24 24" style="width:13px;height:13px"><path d="M9.5 5l6 7-6 7"></path></svg></button>
          </div>
        </div>
      </div>
    </main>
  </div>
  <label for="navcb" class="scrim" aria-hidden="true"></label>

  <div id="modal">
    <div class="modal-card">
      <div class="modal-top">
        <div><div class="learner-name disp" id="mName" style="font-size:17px"></div><div class="learner-grade" id="mGrade"></div></div>
        <button class="modal-close" id="modalClose" aria-label="Close"><svg class="ic" viewBox="0 0 24 24"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg></button>
      </div>
      <div class="mrow"><span>Reference</span><span class="mono" id="mRef"></span></div>
      <div class="mrow"><span>Guardian</span><span id="mGuardian"></span></div>
      <div class="mrow"><span>Contact</span><span class="mono" id="mContact"></span></div>
      <div class="mrow"><span>Submitted</span><span class="mono" id="mDate"></span></div>
      <div class="timeline" id="mTimeline"></div>
      <div class="modal-actions" id="mActions"></div>
    </div>
  </div>
</div>

<script>
// Role from PHP session
const ROLE = <?php echo json_encode($role); ?>;
const TOKEN = <?php echo json_encode($token); ?>;
document.body.dataset.role = ROLE;
const label = ROLE === 'admin' ? 'Admin' : 'Teacher';
document.getElementById('roleBadge').textContent = label;
document.getElementById('roleBadgeTop').textContent = label;

// Status metadata
const STATUS_META = {
  submitted: {label: 'Submitted', c: 'var(--blue)', bg: 'rgba(143,180,212,.16)'},
  under_review: {label: 'Under Review', c: 'var(--accent)', bg: 'rgba(232,163,61,.16)'},
  approved: {label: 'Approved', c: 'var(--teal)', bg: 'rgba(79,209,197,.16)'},
  enrolled: {label: 'Enrolled', c: 'var(--green)', bg: 'rgba(124,201,160,.18)'},
  rejected: {label: 'Rejected', c: 'var(--terra)', bg: 'rgba(201,131,110,.18)'}
};

// Live data from API
let apps = [];
let page = 1, perPage = 8, search = '', statusFilter = 'all';
let totalPages = 1;

// Fetch applications from API
async function fetchApps() {
  try {
    const params = new URLSearchParams({
      action: 'list',
      search: search,
      status: statusFilter,
      page: page,
      per_page: perPage,
      token: TOKEN
    });
    const res = await fetch('enroll_api.php?' + params);
    const data = await res.json();
    apps = data.applications || [];
    totalPages = data.total_pages || 1;
    renderStats();
    renderTable();
  } catch (e) {
    console.error('Failed to fetch applications:', e);
    document.getElementById('tbody').innerHTML = '<tr class="empty-row"><td colspan="7">Failed to load applications. Please try again.</td></tr>';
  }
}

function fmtDate(d) {
  if (!d) return '';
  const dt = new Date(d + 'T00:00:00');
  return dt.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
}

function filtered() {
  const s = search.trim().toLowerCase();
  return apps.filter(a => {
    const matchesSearch = !s || a.ref.toLowerCase().includes(s) || a.name.toLowerCase().includes(s) || a.guardian.toLowerCase().includes(s);
    const matchesStatus = statusFilter === 'all' || a.status === statusFilter;
    return matchesSearch && matchesStatus;
  });
}

function pill(status) {
  const m = STATUS_META[status] || STATUS_META.submitted;
  return '<span class="pill" style="background:' + m.bg + ';color:' + m.c + '">' + m.label + '</span>';
}

function actionsFor(a) {
  const isAdmin = document.body.dataset.role === 'admin';
  if (a.status === 'submitted' || a.status === 'under_review') {
    let html = '<button class="act" data-act="view" data-id="' + a.id + '" aria-label="View application"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';
    if (isAdmin) {
      html += '<button class="act approve admin-only" data-act="approve" data-id="' + a.id + '" aria-label="Approve application"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px"><path d="M5 12.5l4.5 4.5L19 7"></path></svg></button>';
      html += '<button class="act reject admin-only" data-act="reject" data-id="' + a.id + '" aria-label="Reject application"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg></button>';
    }
    return html;
  }
  if (a.status === 'approved') {
    let html = '<button class="act" data-act="view" data-id="' + a.id + '" aria-label="View application"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';
    if (isAdmin) {
      html += '<button class="act admin-only" data-act="enroll" data-id="' + a.id + '" aria-label="Mark enrolled" style="width:auto;padding:0 10px;font-size:11px;font-weight:600;color:var(--green)">Mark Enrolled</button>';
    }
    return html;
  }
  let html = '<button class="act" data-act="view" data-id="' + a.id + '" aria-label="View application"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>';
  // Add delete button for processed applications (admin only)
  if (isAdmin && ['approved', 'rejected', 'enrolled'].includes(a.status)) {
    html += '<button class="act delete admin-only" data-act="delete" data-id="' + a.id + '" aria-label="Delete application"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px"><path d="M3 6h18"></path><path d="M8 6V4a4 4 0 0 1 8 0v2"></path><line x1="5" y1="10" x2="19" y2="10"></line><path d="M10 14h4"></path><path d="M14" y1="14" x1="10v6a2 2 0 0 0 4 0v-6"></path></svg></button>';
  }
  return html;
}

function renderStats() {
  const c = {submitted: 0, under_review: 0, approved: 0, enrolled: 0, rejected: 0};
  apps.forEach(a => { if (c[a.status] !== undefined) c[a.status]++; });
  document.getElementById('stats').innerHTML =
    '<div class="stat"><b style="color:var(--accent)">' + apps.length + '</b><span>Total</span></div>' +
    '<div class="stat"><b>' + (c.submitted + c.under_review) + '</b><span>Submitted</span></div>' +
    '<div class="stat"><b style="color:var(--teal)">' + c.approved + '</b><span>Approved</span></div>' +
    '<div class="stat"><b style="color:var(--green)">' + c.enrolled + '</b><span>Enrolled</span></div>' +
    '<div class="stat"><b style="color:var(--terra)">' + c.rejected + '</b><span>Rejected</span></div>';
}

function renderTable() {
  const list = filtered();
  totalPages = Math.max(1, Math.ceil(list.length / perPage));
  if (page > totalPages) page = totalPages;
  const start = (page - 1) * perPage;
  const rows = list.slice(start, start + perPage);
  const tbody = document.getElementById('tbody');
  if (rows.length === 0) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No applications match your search or filter.</td></tr>';
  } else {
    tbody.innerHTML = rows.map(a => '<tr>' +
      '<td class="mono">' + (a.ref || '') + '</td>' +
      '<td><div class="learner-name">' + (a.name || '') + '</div><div class="learner-grade">Grade ' + (a.grade || '') + '</div></td>' +
      '<td>' + (a.guardian || '') + '</td>' +
      '<td class="mono" style="color:var(--muted);font-size:11.5px">' + (a.contact || '') + '</td>' +
      '<td>' + pill(a.status) + '</td>' +
      '<td class="mono" style="color:var(--muted);font-size:11.5px">' + fmtDate(a.date || '') + '</td>' +
      '<td><div class="row-actions">' + actionsFor(a) + '</div></td>' +
      '</tr>').join('');
  }
  document.getElementById('pagerInfo').textContent = 'Page ' + page + ' of ' + totalPages + ' &middot; ' + list.length + ' shown';
  document.getElementById('prevBtn').disabled = page <= 1;
  document.getElementById('nextBtn').disabled = page >= totalPages;
  applyRoleVisibility();
}

function applyRoleVisibility() {
  const isAdmin = document.body.dataset.role === 'admin';
  document.querySelectorAll('.admin-only').forEach(el => el.style.display = isAdmin ? '' : 'none');
}

function findApp(id) { return apps.find(a => a.id === Number(id)); }

function openModal(id) {
  const a = findApp(id);
  if (!a) return;
  document.getElementById('mName').textContent = a.name || '';
  document.getElementById('mGrade').textContent = 'Grade ' + (a.grade || '');
  document.getElementById('mRef').textContent = a.ref || '';
  document.getElementById('mGuardian').textContent = a.guardian || '';
  document.getElementById('mContact').textContent = a.contact || '';
  document.getElementById('mDate').textContent = fmtDate(a.date || '');
  const stages = ['submitted', 'approved', 'enrolled'];
  let tl = '';
  if (a.status === 'rejected') {
    tl = '<div class="tstep done"><div class="tdot"></div>Submitted</div><div class="tstep current" style="color:var(--terra)"><div class="tdot" style="background:var(--terra)"></div>Rejected</div>';
  } else {
    const idx = stages.indexOf(a.status === 'under_review' ? 'submitted' : a.status);
    tl = stages.map((s, i) => {
      const cls = i < idx ? 'done' : (i === idx ? 'current' : '');
      const label = s === 'submitted' ? 'Submitted' : s === 'approved' ? 'Approved' : 'Enrolled';
      return '<div class="tstep ' + cls + '"><div class="tdot"></div>' + label + '</div>';
    }).join('');
  }
  document.getElementById('mTimeline').innerHTML = tl;
  document.getElementById('mActions').innerHTML = modalActions(a);
  document.getElementById('modal').classList.add('open');
  applyRoleVisibility();
}

function modalActions(a) {
  const isAdmin = document.body.dataset.role === 'admin';
  if (!isAdmin) return '';
  if (a.status === 'submitted' || a.status === 'under_review') {
    return '<button class="m-approve admin-only" data-act="approve" data-id="' + a.id + '">Approve</button><button class="m-reject admin-only" data-act="reject" data-id="' + a.id + '">Reject</button>';
  }
  if (a.status === 'approved') {
    return '<button class="m-enroll admin-only" data-act="enroll" data-id="' + a.id + '" style="flex:none;width:100%">Mark Enrolled</button>';
  }
  return '';
}

function closeModal() { document.getElementById('modal').classList.remove('open'); }

function deleteApplication(id) {
  if (!confirm('Are you sure you want to delete this application? This action cannot be undone.')) return;
  const formData = new URLSearchParams();
  formData.append('action', 'delete');
  formData.append('application_id', id);
  formData.append('token', TOKEN);
  fetch('enroll_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Application deleted.');
        // Remove from list
        apps = apps.filter(a => a.id !== Number(id));
        renderStats();
        renderTable();
        closeModal();
      } else {
        alert(data.error || 'Failed to delete application.');
      }
    })
    .catch(e => {
      console.error('Delete failed:', e);
      alert('Failed to delete application.');
    });
}

async function setStatus(id, status) {
  try {
    const formData = new URLSearchParams();
    const actionMap = { 'approved': 'approve', 'rejected': 'reject', 'enrolled': 'enroll' };
    formData.append('action', actionMap[status] || status);
    formData.append('application_id', id);
    formData.append('token', TOKEN);

    const res = await fetch('enroll_api.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      const a = findApp(id);
      if (a) a.status = data.status || status;
      renderStats();
      renderTable();
      if (document.getElementById('modal').classList.contains('open')) openModal(id);
    }
  } catch (e) {
    console.error('Failed to update status:', e);
  }
}

document.addEventListener('click', e => {
  const btn = e.target.closest('[data-act]');
  if (!btn) return;
  const id = btn.dataset.id, act = btn.dataset.act;
  if (act === 'view') openModal(id);
  if (act === 'approve') setStatus(id, 'approved');
  if (act === 'reject') setStatus(id, 'rejected');
  if (act === 'enroll') setStatus(id, 'enrolled');
  if (act === 'delete') deleteApplication(id);
});

document.getElementById('modalClose').addEventListener('click', closeModal);
document.getElementById('modal').addEventListener('click', e => { if (e.target.id === 'modal') closeModal(); });
document.getElementById('q').addEventListener('input', e => { search = e.target.value; page = 1; renderTable(); });
document.getElementById('status-filter').addEventListener('change', e => { statusFilter = e.target.value; page = 1; renderTable(); });
document.getElementById('filterBtn').addEventListener('click', () => { page = 1; renderTable(); });
document.getElementById('resetBtn').addEventListener('click', () => {
  search = ''; statusFilter = 'all';
  document.getElementById('q').value = '';
  document.getElementById('status-filter').value = 'all';
  page = 1; renderTable();
});
document.getElementById('prevBtn').addEventListener('click', () => { if (page > 1) { page--; renderTable(); } });
document.getElementById('nextBtn').addEventListener('click', () => { page++; renderTable(); });

// Initial fetch
fetchApps();
</script>
</body>
</html>
