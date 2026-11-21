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

// Handle logout - redirect to public landing page after session destruction
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: /');
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

// Which view: enrollment applications (default) or enrolled students
$view = ($_GET['view'] ?? '') === 'students' ? 'students' : 'applications';

// CSRF token shared with enroll_api.php for staff actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

// School year label, e.g. 2026–2027
$syear = (int)($_SESSION['UserSyear'] ?? 0) ?: (int)(getenv('DEFAULT_SYEAR') ?: date('Y'));
$syear_label = $syear . '&ndash;' . ($syear + 1);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>SmartCampus K12 --- Dashboard</title>
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
<style>
:root{box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px);
--bg:var(--ink-deep);--panel:#0F1D2C;--panel2:#14263A;--border:#1F3247;--border2:#172839;
--text:var(--sand);--muted:#9DB0B4;--accent:var(--sun-gold);--blue:#8FB4D4;--teal:#4FD1C5;--green:#7CC9A0;--terra:var(--reef-coral)}
html{scroll-padding-top:env(safe-area-inset-top,0px)}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:var(--font-body);-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
button{font:inherit;cursor:pointer}
input,select{font:inherit}
.mono{font-family:var(--font-utility)}
.disp{font-family:var(--font-display)}
#navcb{display:none}
.hidden{display:none!important}

.topbar{display:none;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--panel);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:30}
.hamburger{width:34px;height:34px;border:1px solid var(--border);border-radius:8px;background:var(--panel2);display:flex;align-items:center;justify-content:center;color:var(--text)}
.shell{display:flex;min-height:100vh}
.sidebar{width:240px;flex-shrink:0;background:var(--panel);border-right:1px solid var(--border);padding:24px 16px;display:flex;flex-direction:column}
.side-brand{display:flex;align-items:center;gap:10px;margin-bottom:26px;padding:0 6px}
.brand-mark{width:36px;height:36px;border-radius:10px;background:var(--accent);display:flex;align-items:center;justify-content:center}
.brand-mark span{font-family:var(--font-utility);font-weight:600;font-size:13px;color:var(--bg)}
.brand-name{font-weight:600;font-size:14px}
.brand-sub{font-size:11px;color:var(--muted)}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;font-size:13.5px;font-weight:500;color:var(--foam);min-height:44px}
.nav-item:hover{background:var(--panel2);color:var(--text)}
.nav-item.active{background:rgba(244,180,0,.14);color:var(--text)}
.nav-divider{height:1px;background:var(--border);margin:12px 6px}
.nav-ext{color:var(--muted);font-size:12px}
.side-foot{margin-top:auto;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.role-badge{font-size:12.5px}
.role-badge b{color:var(--text)}
.icon-btn{width:36px;height:36px;border:none;background:none;color:var(--muted);display:flex;align-items:center;justify-content:center;border-radius:8px}
.icon-btn:hover{background:var(--panel2);color:var(--text)}
.scrim{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:55}
main{flex-grow:1;min-width:0;padding:26px 30px 40px}
.ic{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
.eyebrow{font-family:var(--font-utility);font-size:11px;letter-spacing:.08em;color:var(--accent);text-transform:uppercase;font-weight:500}
h1.page-title{font-family:var(--font-display);font-weight:700;font-size:25px;margin:6px 0 20px;letter-spacing:-.01em}
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.stat{background:var(--panel);border:1px solid var(--border);border-radius:10px;padding:14px 16px}
.stat b{font-family:var(--font-utility);font-weight:600;font-size:23px;display:block}
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
.pill{display:inline-block;padding:4px 10px;border-radius:999px;font-family:var(--font-utility);font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
.row-actions{display:flex;gap:4px}
.act{width:30px;height:30px;border:none;background:none;color:var(--muted);border-radius:6px;display:flex;align-items:center;justify-content:center}
.act:hover{background:var(--panel2);color:var(--text)}
.act.reject:hover{color:var(--terra)}
.act.delete:hover{color:var(--terra)}
.act.approve:hover{color:var(--teal)}
.pager{padding:12px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.pager span{font-family:var(--font-utility);font-size:11.5px;color:var(--muted)}
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
.modal-close:hover{background:var(--panel2);color:var(--text)}
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
.no-lrn{color:var(--accent);font-weight:600}
.title-row{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:8px 16px}
.btn-link{display:inline-flex;align-items:center;min-height:44px;color:var(--accent);font-size:13px;font-weight:600;text-decoration:underline;text-underline-offset:3px}
.notice{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;border:1px solid var(--border);border-left:3px solid var(--green);background:var(--panel);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px}
.notice.error{border-left-color:var(--terra)}
.notice code{font-family:var(--font-utility);background:var(--bg);padding:1px 6px;border-radius:4px;color:var(--accent)}
#grade-filter{background:var(--panel);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text);min-height:44px}
:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
@media (max-width:860px){
  .topbar{display:flex}
  .sidebar{position:fixed;top:0;left:0;height:100%;width:240px;transform:translateX(-100%);transition:transform .2s ease;z-index:50}
  #navcb:checked ~ #dashboard .sidebar{transform:translateX(0)}
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
        <a href="dashboard.php" class="nav-item<?php echo $view === 'applications' ? ' active" aria-current="page' : ''; ?>"><svg class="ic" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"></rect><line x1="8" y1="9" x2="16" y2="9"></line><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="13" y2="17"></line></svg>Enrollment</a>
        <a href="dashboard.php?view=students" class="nav-item<?php echo $view === 'students' ? ' active" aria-current="page' : ''; ?>"><svg class="ic" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20a6.5 6.5 0 0 1 13 0"></path><path d="M16 4.5a3.5 3.5 0 0 1 0 7"></path><path d="M18 14.5a6.5 6.5 0 0 1 3.5 5.5"></path></svg>Students</a>
        <a href="sf1.php" class="nav-item"><svg class="ic" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path><path d="M14 3v5h5"></path><line x1="9" y1="13" x2="15" y2="13"></line><line x1="9" y1="17" x2="15" y2="17"></line></svg>SF1 School Register</a>
        <a href="sf2.php" class="nav-item"><svg class="ic" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"></rect><line x1="4" y1="10" x2="20" y2="10"></line><line x1="9" y1="3" x2="9" y2="7"></line><line x1="15" y1="3" x2="15" y2="7"></line><path d="M9 15l2 2 4-4"></path></svg>SF2 Attendance</a>
<?php if ($role === 'admin'): ?>
        <a href="school_setup.php" class="nav-item"><svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"></path></svg>School Year Setup</a>
<?php endif; ?>
        <div class="nav-divider"></div>
        <div class="nav-ext" style="padding:4px 12px 2px;text-transform:uppercase;letter-spacing:.05em;font-size:10px">School records</div>
        <a href="index.php" class="nav-item" title="Opens the school's official RosarioSIS system"><svg class="ic" viewBox="0 0 24 24"><path d="M9 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-3"></path><path d="M14 4h6v6"></path><path d="M20 4l-9 9"></path></svg>School Information Office</a>
      </nav>
      <div class="side-foot">
        <div class="role-badge"><b id="roleBadge">Admin</b> &middot; BBNHS</div>
        <a href="dashboard.php?logout=1" id="logoutBtn" aria-label="Log out" class="icon-btn"><svg class="ic" viewBox="0 0 24 24"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"></path><line x1="21" y1="12" x2="10" y2="12"></line><path d="M16 7l5 5-5 5"></path></svg></a>
      </div>
    </aside>
    <main>
      <div id="notice" class="notice hidden" role="status" aria-live="polite"><div id="noticeText"></div><button class="icon-btn" id="noticeClose" type="button" aria-label="Dismiss"><svg class="ic" viewBox="0 0 24 24"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg></button></div>
<?php if ($view === 'students'): ?>
      <div class="eyebrow">Students &middot; <?php echo $syear_label; ?></div>
      <div class="title-row">
        <h1 class="page-title disp"><?php echo $role === 'admin' ? 'Enrolled Students' : 'My Students'; ?></h1>
        <a class="btn-link" href="Modules.php?modname=Students/Student.php&amp;search_modfunc=list">Full student records in RosarioSIS &rarr;</a>
      </div>

      <div class="stats" id="stats"></div>

      <form class="toolbar" id="filterForm">
        <div class="search-wrap">
          <label class="sr-only" for="q">Search students</label>
          <svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
          <input id="q" type="search" placeholder="Search name, LRN, username, ID&hellip;" autocomplete="off">
        </div>
        <div>
          <label class="sr-only" for="grade-filter">Filter by grade</label>
          <select id="grade-filter"><option value="0">All grades</option></select>
        </div>
        <button class="btn-primary" type="submit">Filter</button>
        <button class="btn-ghost" id="resetBtn" type="button">Reset</button>
      </form>

      <div class="table-card">
        <div class="table-scroll">
          <table>
            <caption class="sr-only">Enrolled students</caption>
            <thead><tr>
              <th scope="col">Student ID</th><th scope="col">LRN</th><th scope="col">Name</th><th scope="col">Grade</th><th scope="col">Username</th><th scope="col">Enrolled on</th><th scope="col">Application</th>
            </tr></thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
<?php else: ?>
      <div class="eyebrow">Enrollment &middot; <?php echo $syear_label; ?></div>
      <h1 class="page-title disp">Enrollment Applications</h1>

      <div class="stats" id="stats"></div>

      <form class="toolbar" id="filterForm">
        <div class="search-wrap">
          <label class="sr-only" for="q">Search applications</label>
          <svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
          <input id="q" type="search" placeholder="Search ref, LRN, learner, guardian&hellip;" autocomplete="off">
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
        <button class="btn-primary" type="submit">Filter</button>
        <button class="btn-ghost" id="resetBtn" type="button">Reset</button>
      </form>

      <div class="table-card">
        <div class="table-scroll">
          <table>
            <caption class="sr-only">Enrollment applications</caption>
            <thead><tr>
              <th scope="col">Ref</th><th scope="col">Learner</th><th scope="col">Guardian</th><th scope="col">Contact</th><th scope="col">Status</th><th scope="col">Submitted</th><th scope="col">Actions</th>
            </tr></thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
<?php endif; ?>
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
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="mName">
      <div class="modal-top">
        <div><div class="learner-name disp" id="mName" style="font-size:17px"></div><div class="learner-grade" id="mGrade"></div></div>
        <button class="modal-close" id="modalClose" aria-label="Close"><svg class="ic" viewBox="0 0 24 24"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg></button>
      </div>
      <div class="mrow"><span>Reference</span><span class="mono" id="mRef"></span></div>
      <div class="mrow"><span>LRN</span><span class="mono" id="mLrn"></span></div>
      <div class="mrow"><span>Guardian</span><span id="mGuardian"></span></div>
      <div class="mrow"><span>Contact</span><span class="mono" id="mContact"></span></div>
      <div class="mrow"><span>Submitted</span><span class="mono" id="mDate"></span></div>
      <div class="timeline" id="mTimeline"></div>
      <div class="modal-actions" id="mActions"></div>
    </div>
  </div>
</div>

<script>
const ROLE = <?php echo json_encode($role); ?>;
const TOKEN = <?php echo json_encode($token); ?>;
const VIEW = <?php echo json_encode($view); ?>;
const IS_ADMIN = ROLE === 'admin';
const label = IS_ADMIN ? 'Admin' : 'Teacher';
document.getElementById('roleBadge').textContent = label;
document.getElementById('roleBadgeTop').textContent = label;

const $ = id => document.getElementById(id);
const tbody = $('tbody');
const COLS = 7;

// Applicant-supplied text must never be injected as HTML.
function esc(v) {
  return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}

function fmtDate(d) {
  if (!d) return '—';
  const dt = new Date(String(d).replace(' ', 'T'));
  if (isNaN(dt.getTime())) return '—';
  return dt.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
}

function messageRow(text) {
  tbody.innerHTML = '<tr class="empty-row"><td colspan="' + COLS + '">' + esc(text) + '</td></tr>';
}

function showNotice(html, isError) {
  $('noticeText').innerHTML = html;
  $('notice').classList.toggle('error', !!isError);
  $('notice').classList.remove('hidden');
}
$('noticeClose').addEventListener('click', () => $('notice').classList.add('hidden'));

// GET/POST to the API; throws with the server's message on failure.
async function api(params, post) {
  const opts = post ? {method: 'POST', body: new URLSearchParams(Object.assign({token: TOKEN}, params))} : {};
  const url = 'enroll_api.php' + (post ? '' : '?' + new URLSearchParams(params));
  const res = await fetch(url, Object.assign({credentials: 'same-origin'}, opts));
  if (res.status === 401) { window.location.href = 'view_students_application.php'; throw new Error('Session expired.'); }
  let data = null;
  try { data = await res.json(); } catch (e) { data = null; }
  if (!res.ok || !data || data.success === false) throw new Error((data && data.error) || 'Request failed. Please try again.');
  return data;
}

let page = 1, totalPages = 1, search = '';
const perPage = 10;

function renderPager(total) {
  $('pagerInfo').textContent = 'Page ' + page + ' of ' + totalPages + ' · ' + total + ' total';
  $('prevBtn').disabled = page <= 1;
  $('nextBtn').disabled = page >= totalPages;
}

function stat(value, text, color) {
  return '<div class="stat"><b' + (color ? ' style="color:' + color + '"' : '') + '>' + esc(value) + '</b><span>' + esc(text) + '</span></div>';
}

let debounce;
$('q').addEventListener('input', e => {
  clearTimeout(debounce);
  debounce = setTimeout(() => { search = e.target.value.trim(); page = 1; load(); }, 300);
});
$('filterForm').addEventListener('submit', e => { e.preventDefault(); search = $('q').value.trim(); page = 1; load(); });
$('prevBtn').addEventListener('click', () => { if (page > 1) { page--; load(); } });
$('nextBtn').addEventListener('click', () => { if (page < totalPages) { page++; load(); } });

/* ---------------- Students view ---------------- */
let gradeId = '0', gradesLoaded = false;

async function loadStudents() {
  messageRow('Loading students…');
  try {
    const data = await api({action: 'students', search: search, grade_id: gradeId, page: page, per_page: perPage});
    page = data.page; totalPages = data.total_pages;
    if (!gradesLoaded) {
      $('grade-filter').insertAdjacentHTML('beforeend', data.grades.map(g => '<option value="' + g.id + '">' + esc(g.title) + '</option>').join(''));
      gradesLoaded = true;
    }
    $('stats').innerHTML = stat(data.total_count, gradeId !== '0' || search ? 'Matching students' : 'Enrolled students', 'var(--green)') +
      stat(data.grades.length, 'Grade levels');
    if (!data.students.length) {
      messageRow(search || gradeId !== '0' ? 'No students match your search or filter.' :
        IS_ADMIN ? 'No students enrolled yet. Approve an application and choose “Enroll” to add one.' :
        'No students are scheduled in your classes yet.');
    } else {
      tbody.innerHTML = data.students.map(s => '<tr>' +
        '<td class="mono">' + esc(s.id) + '</td>' +
        '<td class="mono">' + (s.lrn ? esc(s.lrn) : '<span class="no-lrn">None</span>') + '</td>' +
        '<td><div class="learner-name">' + esc(s.name) + '</div></td>' +
        '<td>' + esc(s.grade || '—') + '</td>' +
        '<td class="mono">' + esc(s.username || '—') + '</td>' +
        '<td class="mono" style="color:var(--muted);font-size:11.5px">' + fmtDate(s.enrolled_on) + '</td>' +
        '<td class="mono" style="color:var(--muted);font-size:11.5px">' + esc(s.application_ref || '—') + '</td>' +
        '</tr>').join('');
    }
    renderPager(data.total_count);
  } catch (e) {
    messageRow('Could not load students: ' + e.message);
  }
}

/* ---------------- Applications view ---------------- */
const STATUS_META = {
  submitted: {label: 'Submitted', c: 'var(--blue)', bg: 'rgba(143,180,212,.16)'},
  under_review: {label: 'Under Review', c: 'var(--accent)', bg: 'rgba(244,180,0,.16)'},
  approved: {label: 'Approved', c: 'var(--teal)', bg: 'rgba(79,209,197,.16)'},
  enrolled: {label: 'Enrolled', c: 'var(--green)', bg: 'rgba(124,201,160,.18)'},
  rejected: {label: 'Rejected', c: 'var(--terra)', bg: 'rgba(201,131,110,.18)'}
};
const ICON = {
  view: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle>',
  approve: '<path d="M5 12.5l4.5 4.5L19 7"></path>',
  reject: '<line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line>',
  delete: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M6 6l1 14h10l1-14"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>'
};
let apps = [], statusFilter = 'all';

function pill(status) {
  const m = STATUS_META[status] || STATUS_META.submitted;
  return '<span class="pill" style="background:' + m.bg + ';color:' + m.c + '">' + m.label + '</span>';
}

function iconBtn(act, a, labelText, cls) {
  return '<button type="button" class="act ' + (cls || '') + '" data-act="' + act + '" data-id="' + a.id + '" aria-label="' + labelText + ' ' + esc(a.ref) + '" title="' + labelText + '"><svg class="ic" viewBox="0 0 24 24" style="width:15px;height:15px">' + ICON[act] + '</svg></button>';
}

// Marked "enrolled" by the old button, which never created the student.
function needsStudentRecord(a) { return a.status === 'enrolled' && !a.student_id; }

function actionsFor(a) {
  let html = iconBtn('view', a, 'View');
  if (!IS_ADMIN) return html;
  if (a.status === 'submitted' || a.status === 'under_review') {
    html += iconBtn('approve', a, 'Approve', 'approve') + iconBtn('reject', a, 'Reject', 'reject');
  } else if (a.status === 'approved') {
    html += '<button type="button" class="act" data-act="enroll" data-id="' + a.id + '" style="width:auto;padding:0 10px;font-size:11px;font-weight:600;color:var(--green)">Enroll</button>';
  } else if (needsStudentRecord(a)) {
    html += '<button type="button" class="act" data-act="enroll" data-id="' + a.id + '" style="width:auto;padding:0 10px;font-size:11px;font-weight:600;color:var(--accent)">Create student record</button>';
  }
  if (a.status === 'approved' || a.status === 'rejected') html += iconBtn('delete', a, 'Delete', 'delete');
  return html;
}

async function loadApps() {
  messageRow('Loading applications…');
  try {
    const data = await api({action: 'list', search: search, status: statusFilter, page: page, per_page: perPage});
    apps = data.applications; page = data.page; totalPages = data.total_pages;
    const c = data.stats;
    $('stats').innerHTML = stat(c.total, 'Total', 'var(--accent)') + stat(c.submitted + c.under_review, 'Pending') +
      stat(c.approved, 'Approved', 'var(--teal)') + stat(c.enrolled, 'Enrolled', 'var(--green)') + stat(c.rejected, 'Rejected', 'var(--terra)');
    if (!apps.length) {
      messageRow(search || statusFilter !== 'all' ? 'No applications match your search or filter.' : 'No applications yet. They appear here when families submit the online form.');
    } else {
      tbody.innerHTML = apps.map(a => '<tr>' +
        '<td class="mono">' + esc(a.ref) + '</td>' +
        '<td><div class="learner-name">' + esc(a.name) + '</div><div class="learner-grade">' + esc(a.grade) + ' · ' +
          (a.lrn ? 'LRN ' + esc(a.lrn) : '<span class="no-lrn">No LRN</span>') + '</div></td>' +
        '<td>' + esc(a.guardian) + '</td>' +
        '<td class="mono" style="color:var(--muted);font-size:11.5px">' + esc(a.contact) + '</td>' +
        '<td>' + pill(a.status) + '</td>' +
        '<td class="mono" style="color:var(--muted);font-size:11.5px">' + fmtDate(a.date) + '</td>' +
        '<td><div class="row-actions">' + actionsFor(a) + '</div></td>' +
        '</tr>').join('');
    }
    renderPager(data.total_count);
  } catch (e) {
    messageRow('Could not load applications: ' + e.message);
  }
}

function findApp(id) { return apps.find(a => a.id === Number(id)); }

let lastFocus = null;
function openModal(id) {
  const a = findApp(id);
  if (!a) return;
  $('mName').textContent = a.name || '';
  $('mGrade').textContent = a.grade || '';
  $('mRef').textContent = a.ref || '';
  $('mLrn').textContent = a.lrn || 'None given';
  $('mGuardian').textContent = a.guardian || '';
  $('mContact').textContent = a.contact || '';
  $('mDate').textContent = fmtDate(a.date);
  let tl;
  if (a.status === 'rejected') {
    tl = '<div class="tstep done"><div class="tdot"></div>Submitted</div><div class="tstep current" style="color:var(--terra)"><div class="tdot" style="background:var(--terra)"></div>Rejected</div>';
  } else {
    const stages = ['submitted', 'approved', 'enrolled'];
    const idx = stages.indexOf(a.status === 'under_review' ? 'submitted' : a.status);
    tl = stages.map((s, i) => '<div class="tstep ' + (i < idx ? 'done' : i === idx ? 'current' : '') + '"><div class="tdot"></div>' + STATUS_META[s].label + '</div>').join('');
  }
  $('mTimeline').innerHTML = tl;
  let actions = '';
  if (IS_ADMIN && (a.status === 'submitted' || a.status === 'under_review')) {
    actions = '<button type="button" class="m-approve" data-act="approve" data-id="' + a.id + '">Approve</button><button type="button" class="m-reject" data-act="reject" data-id="' + a.id + '">Reject</button>';
  } else if (IS_ADMIN && (a.status === 'approved' || needsStudentRecord(a))) {
    actions = '<button type="button" class="m-enroll" data-act="enroll" data-id="' + a.id + '" style="flex:none;width:100%">' +
      (a.status === 'approved' ? 'Enroll as student' : 'Create student record') + '</button>';
  }
  $('mActions').innerHTML = actions;
  lastFocus = document.activeElement;
  $('modal').classList.add('open');
  $('modalClose').focus();
}

function closeModal() {
  if (!$('modal').classList.contains('open')) return;
  $('modal').classList.remove('open');
  if (lastFocus) lastFocus.focus();
}

// What happened to the guardian's text message.
function smsNote(status) {
  const notes = {
    sent: 'Guardian notified by SMS.',
    dry_run: 'SMS test mode: message logged, not sent (no SMS API key configured).',
    skipped: 'No SMS sent: the guardian contact is not a mobile number.',
    failed: 'The SMS to the guardian could not be sent.'
  };
  return notes[status] ? ' <span style="color:var(--muted)">' + notes[status] + '</span>' : '';
}

async function runAction(act, id, btn) {
  const a = findApp(id);
  const name = a ? a.name : 'this learner';
  const confirmText = {
    approve: 'Approve the application for ' + name + '?',
    reject: 'Reject the application for ' + name + '?',
    enroll: (a && needsStudentRecord(a) ? 'Create the missing student record for ' + name + '?' :
      'Enroll ' + name + ' as a student?') + ' This creates their student record and login.',
    delete: 'Delete this application permanently? This cannot be undone.'
  }[act];
  if (!confirm(confirmText)) return;
  if (btn) btn.disabled = true;
  try {
    const data = await api({action: act, application_id: id}, true);
    closeModal();
    if (act === 'enroll') {
      const s = data.student;
      showNotice('<div><strong>' + esc(s.name) + '</strong> is now enrolled as student #' + esc(s.student_id) +
        (s.section ? ' in section ' + esc(s.section) : '') +
        '. Login: <code>' + esc(s.username) + '</code> · temporary password: <code>' + esc(s.password) + '</code>' +
        '<br><span style="color:var(--muted)">Give these to the guardian now — the password is not shown again.</span> ' +
        '<a href="dashboard.php?view=students" style="color:var(--accent);text-decoration:underline">View students</a>' + smsNote(data.sms) + '</div>');
    } else {
      showNotice(esc(data.message || ('Application ' + (a ? a.ref : '') + ' ' + (act === 'approve' ? 'approved' : 'rejected') + '.')) + smsNote(data.sms));
    }
    await loadApps();
  } catch (e) {
    showNotice(esc(e.message), true);
    if (btn) btn.disabled = false;
  }
}

function load() { return VIEW === 'students' ? loadStudents() : loadApps(); }

if (VIEW === 'students') {
  $('grade-filter').addEventListener('change', e => { gradeId = e.target.value; page = 1; load(); });
  $('resetBtn').addEventListener('click', () => {
    search = ''; gradeId = '0'; page = 1;
    $('q').value = ''; $('grade-filter').value = '0';
    load();
  });
} else {
  $('status-filter').addEventListener('change', e => { statusFilter = e.target.value; page = 1; load(); });
  $('resetBtn').addEventListener('click', () => {
    search = ''; statusFilter = 'all'; page = 1;
    $('q').value = ''; $('status-filter').value = 'all';
    load();
  });
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-act]');
    if (!btn) return;
    if (btn.dataset.act === 'view') openModal(btn.dataset.id);
    else runAction(btn.dataset.act, btn.dataset.id, btn);
  });
  $('modalClose').addEventListener('click', closeModal);
  $('modal').addEventListener('click', e => { if (e.target.id === 'modal') closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
}

load();
</script>
</body>
</html>
