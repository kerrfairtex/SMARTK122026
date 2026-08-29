<?php
/**
 * About Section Editor (Tier 3, auth via shared-secret token)
 *
 * Lets the school edit the public landing page's mission / vision /
 * values_intro copy. Authentication: HMAC-signed URLs scoped to a
 * section and an expiry timestamp.
 *
 * Workflow:
 *   1. The school visits /about_edit.php?token=<ABOUT_EDIT_TOKEN>
 *      (master token required; lists sections with "Generate edit link").
 *   2. They click "Edit Mission" — the page POSTs (with the same token)
 *      and redirects to a signed URL like:
 *        /about_edit.php?section=mission&ts=...&exp=...&sig=...
 *      where sig = hex( hash_hmac( 'sha256',
 *                                    'edit|mission|<ts>|<exp>',
 *                                    $token ) )[:32]
 *   3. The signed URL displays a <textarea> with current body.
 *   4. They type new content and submit. The POST includes the same
 *      ts/exp/sig params plus a new body. If the signature still
 *      validates AND the expiry timestamp is in the future, the row
 *      is updated and a "Saved" page is shown.
 *
 * Security:
 *   - Master token is stored in the school config table row titled
 *     'ABOUT_EDIT_TOKEN'. Minting a signed link requires presenting it
 *     (same as /admin/?token=). The signed URL only carries a 32-hex
 *     HMAC derived from it — not a signing oracle for anonymous visitors.
 *   - The signature is scoped (section, ts, exp), so a leak in
 *     screenshots / shared links only works until the expiry
 *     timestamp (default 7 days).
 *   - The same body is logged to access_log with the requester's IP.
 *
 * This file is one of two entry points:
 *   - GET  /about_edit.php?token=...   (landing page: list sections)
 *   - POST /about_edit.php             (landing: generate signed link; token required)
 *   - GET  /about_edit.php?signed...   (editor form)
 *   - POST /about_edit.php?signed...   (save body)
 */

require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Cache-Control: no-store');

function db_conn() {
    static $c = null;
    if ($c === null) {
        $c = db_start(false);
        if ($c === false) {
            throw new Exception('db');
        }
    }
    return $c;
}

function load_token() {
    static $cached = null;
    if ($cached !== null) return $cached;
    try {
        $conn = db_conn();
        $res = @pg_query($conn, "SELECT config_value FROM config WHERE title = 'ABOUT_EDIT_TOKEN' LIMIT 1");
        $row = $res ? pg_fetch_assoc($res) : null;
        $cached = $row ? $row['config_value'] : null;
    } catch (Throwable $t) {
        $cached = null;
    }
    return $cached;
}

function make_sig($section, $ts, $exp, $token) {
    $msg = 'edit|' . $section . '|' . $ts . '|' . $exp;
    return substr(hash_hmac('sha256', $msg, $token), 0, 32);
}

function verify_sig($section, $ts, $exp, $sig, $token) {
    if (!$token || !$section || !$ts || !$exp || !$sig) return false;
    if ((int)$exp < time()) return false;  // expired
    $expected = make_sig($section, (int)$ts, (int)$exp, $token);
    return hash_equals($expected, $sig);
}

/** Caller must present ABOUT_EDIT_TOKEN to mint links (not a public signing oracle). */
function provided_master_token() {
    return (string)($_POST['token'] ?? $_GET['token'] ?? '');
}

function master_token_ok($provided = null) {
    $token = load_token();
    if ($provided === null) {
        $provided = provided_master_token();
    }
    return $token && $provided !== '' && hash_equals($token, $provided);
}

function audit_save($conn, $section, $ip) {
    @pg_query(
        $conn,
        "INSERT INTO kerrfairtex.access_log
            (syear, username, profile, ip_address, user_agent, status, created_at, updated_at)
         VALUES (2026, '', '', '" . pg_escape_string($conn, substr($ip, 0, 50)) . "', '" .
            pg_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)) . "', '" .
            pg_escape_string($conn, 'about_edit:' . $section) . "', now(), now())"
    );
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ----- POST: generate signed link OR save body -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = load_token();
    if (!$token) {
        http_response_code(500);
        echo 'ABOUT_EDIT_TOKEN not configured. Aborting.';
        exit;
    }

    // Case A: save body (must have signed params in $_GET and body in $_POST)
    if (isset($_GET['section'], $_GET['ts'], $_GET['exp'], $_GET['sig'])
        && hash_equals('save', $_POST['op'] ?? '')) {
        $section = preg_replace('/[^a-z_]/', '', strtolower((string)$_GET['section']));
        $ts  = (int)$_GET['ts'];
        $exp = (int)$_GET['exp'];
        $sig = (string)$_GET['sig'];
        if (!verify_sig($section, $ts, $exp, $sig, $token)) {
            http_response_code(403);
            echo '<!doctype html><meta charset="utf-8"><title>403</title><p>Invalid or expired edit link.</p>';
            exit;
        }
        $body = (string)($_POST['body'] ?? '');
        $body = substr($body, 0, 8000);  // hard cap
        try {
            $conn = db_conn();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $r = @pg_query(
                $conn,
                "UPDATE kerrfairtex.about_content
                 SET body = '" . pg_escape_string($conn, $body) . "',
                     updated_at = now(),
                     updated_by = '" . pg_escape_string($conn, substr($ip, 0, 50)) . "'
                 WHERE section = '" . pg_escape_string($conn, $section) . "'"
            );
            if (!$r) {
                http_response_code(500);
                echo 'Could not save.';
                exit;
            }
            audit_save($conn, $section, $ip);
            // Redirect to landing page fragment so the change is visible
            $fragment = ($section === 'values_intro') ? '#about' : '#about';
            header('Location: /#about?saved=' . urlencode($section));
            exit;
        } catch (Throwable $t) {
            http_response_code(503);
            echo 'DB error.';
            exit;
        }
    }

    // Case B: generate a signed link (requires master token; form posts section)
    if (!master_token_ok()) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>403</title><p>Valid master token required to generate edit links.</p>';
        exit;
    }
    $section = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['section'] ?? '')));
    if (!in_array($section, ['mission', 'vision', 'values_intro'], true)) {
        http_response_code(400);
        echo 'Unknown section';
        exit;
    }
    $now = time();
    $exp = $now + 7 * 86400;  // 7 days
    $ts  = $now;
    $sig = make_sig($section, $ts, $exp, $token);
    $url = '/about_edit.php?section=' . urlencode($section)
         . '&ts=' . $ts . '&exp=' . $exp . '&sig=' . $sig;
    header('Location: ' . $url);
    exit;
}

// ----- GET with signed params: show editor form -----
if (isset($_GET['section'], $_GET['ts'], $_GET['exp'], $_GET['sig'])) {
    $token = load_token();
    if (!$token) {
        http_response_code(500);
        echo 'ABOUT_EDIT_TOKEN not configured.';
        exit;
    }
    $section = preg_replace('/[^a-z_]/', '', strtolower((string)$_GET['section']));
    $ts  = (int)$_GET['ts'];
    $exp = (int)$_GET['exp'];
    $sig = (string)$_GET['sig'];
    if (!verify_sig($section, $ts, $exp, $sig, $token)) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>403</title><p>Invalid or expired edit link. Generate a new one from <a href="/about_edit.php">/about_edit.php</a>.</p>';
        exit;
    }
    // Load current body
    $body = '';
    try {
        $conn = db_conn();
        $r = @pg_query(
            $conn,
            "SELECT body FROM kerrfairtex.about_content WHERE section = '" .
            pg_escape_string($conn, $section) . "'"
        );
        $row = $r ? pg_fetch_assoc($r) : null;
        $body = $row ? (string)$row['body'] : '';
    } catch (Throwable $t) {
        // Fall through with empty body; form will still render.
    }
    $title = ucfirst(str_replace('_', ' ', $section));
    $qs = http_build_query([
        'section' => $section,
        'ts'  => $ts,
        'exp' => $exp,
        'sig' => $sig,
    ]);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Edit ' . h($title) . ' &mdash; SmartCampus</title>'
       . '<style>body{font-family:system-ui,-apple-system,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;color:#0f172a;}'
       . 'h1{font-size:1.4rem;}textarea{width:100%;min-height:240px;font:1rem ui-monospace,monospace;padding:0.6rem;border:1px solid #cbd5e1;border-radius:6px;}'
       . '.meta{color:#475569;font-size:0.85rem;margin-bottom:1rem;}'
       . '.actions{margin-top:1rem;display:flex;gap:0.6rem;}'
       . 'button{background:#1d4ed8;color:#fff;border:0;padding:0.55rem 1rem;border-radius:6px;font:inherit;cursor:pointer;}'
       . 'a.btn{background:#e2e8f0;color:#0f172a;text-decoration:none;padding:0.55rem 1rem;border-radius:6px;}</style>'
       . '<h1>Edit ' . h($title) . '</h1>'
       . '<p class="meta">This link expires ' . h(date('Y-m-d H:i', $exp)) . '. Changes are public immediately.</p>'
       . '<form method="post" action="/about_edit.php?' . h($qs) . '">'
       . '<input type="hidden" name="op" value="save">'
       . '<textarea name="body" placeholder="Type the new ' . h($title) . ' text&hellip;">' . h($body) . '</textarea>'
       . '<div class="actions">'
       . '<button type="submit">Save</button>'
       . '<a class="btn" href="/#about">Cancel</a>'
       . '</div>'
       . '</form>';
    exit;
}

// ----- GET: landing page (list editable sections; master token required) -----
$now = time();
$token = load_token();
$token_configured = !!$token;
$authed = master_token_ok();
$master = provided_master_token();

echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
   . '<title>About Editor &mdash; SmartCampus</title>'
   . '<style>body{font-family:system-ui,-apple-system,sans-serif;max-width:560px;margin:2rem auto;padding:0 1rem;color:#0f172a;}'
   . 'h1{font-size:1.3rem;}p{color:#475569;}.btn{display:inline-block;background:#1d4ed8;color:#fff;border:0;padding:0.55rem 1rem;border-radius:6px;text-decoration:none;font:inherit;cursor:pointer;margin:0.25rem 0.5rem 0.25rem 0;}'
   . '.btn.ghost{background:#e2e8f0;color:#0f172a;}'
   . '.row{margin:1rem 0;padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;}'
   . '.label{font-weight:600;display:block;margin-bottom:0.5rem;}'
   . '.warn{color:#b91c1c;}</style>'
   . '<h1>About Editor</h1>'
   . '<p>Generate a signed edit link for any section below. Each link is good for 7 days and works only for that section.</p>';

if (!$token_configured) {
    echo '<p class="warn">ABOUT_EDIT_TOKEN is not set in the <code>config</code> table. Run a SQL INSERT to set it before editing.</p>';
} elseif (!$authed) {
    echo '<p class="warn">Open this page with <code>?token=</code> set to the ABOUT_EDIT_TOKEN value (same secret as the admin snapshot).</p>';
} else {
    foreach (['mission', 'vision', 'values_intro'] as $s) {
        $title = ucfirst(str_replace('_', ' ', $s));
        echo '<div class="row">'
           . '<span class="label">' . h($title) . ' <code>(' . h($s) . ')</code></span>'
           . '<form method="post" style="display:inline">'
           . '<input type="hidden" name="token" value="' . h($master) . '">'
           . '<input type="hidden" name="section" value="' . h($s) . '">'
           . '<button class="btn" type="submit">Generate edit link</button>'
           . '</form>'
           . '<a class="btn ghost" href="/#about" target="_blank" rel="noopener">Preview public page</a>'
           . '</div>';
    }
    echo '<p style="margin-top:2rem;font-size:0.85rem;color:#64748b;">Bookmark the generated link on a school laptop. To rotate, change the <code>ABOUT_EDIT_TOKEN</code> value in the <code>config</code> table.</p>';
}
echo '<p style="margin-top:2rem;font-size:0.85rem;color:#64748b;">Last token status check: ' . h(date('Y-m-d H:i:s', $now)) . '</p>';
