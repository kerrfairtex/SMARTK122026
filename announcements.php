<?php
/**
 * Announcements (admin): News, Events and Advisories shown on the homepage.
 *
 * @package SmartCampus
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (empty($_SESSION['STAFF_ID']) || ($_SESSION['PROFILE'] ?? '') !== 'admin') {
    header('Location: ' . (empty($_SESSION['STAFF_ID']) ? 'view_students_application.php' : 'dashboard.php'));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

const ANNOUNCEMENT_CATEGORIES = ['news' => 'News', 'event' => 'Event', 'advisory' => 'Advisory'];

function ann_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** A POSTed field as a trimmed string ('' for arrays or missing). */
function ann_post(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** 'YYYY-MM-DD' or null; throws on a malformed date. */
function ann_date(string $value, string $label)
{
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new RuntimeException($label . ' is not a valid date.');
    }
    return $value;
}

$conn = db_start(false);
if ($conn === false) {
    http_response_code(503);
    exit('Database unavailable. Please try again shortly.');
}

$error = '';
$form = ['id' => 0, 'title' => '', 'category' => 'news', 'event_date' => '', 'expires_on' => '', 'body' => '', 'is_published' => true];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], ann_post('token'))) {
            throw new RuntimeException('Security token expired. Please reload the page.');
        }
        $id = (int)ann_post('id');

        if (ann_post('action') === 'delete') {
            pg_query_params($conn, 'DELETE FROM announcements WHERE id = $1', [$id]);
            $_SESSION['ann_flash'] = 'Announcement deleted.';
            header('Location: announcements.php');
            exit;
        }

        $form = [
            'id' => $id,
            'title' => ann_post('title'),
            'category' => ann_post('category'),
            'event_date' => ann_post('event_date'),
            'expires_on' => ann_post('expires_on'),
            'body' => str_replace("\r\n", "\n", ann_post('body')),
            'is_published' => ann_post('is_published') === '1',
        ];
        if (mb_strlen($form['title']) < 3 || mb_strlen($form['title']) > 120) {
            throw new RuntimeException('The title must be 3 to 120 characters.');
        }
        if ($form['body'] === '' || mb_strlen($form['body']) > 2000) {
            throw new RuntimeException('The message must be 1 to 2000 characters.');
        }
        if (!isset(ANNOUNCEMENT_CATEGORIES[$form['category']])) {
            throw new RuntimeException('Choose a category.');
        }
        $event_date = ann_date($form['event_date'], 'Event date');
        $expires_on = ann_date($form['expires_on'], '"Hide after" date');
        if ($form['category'] === 'event' && $event_date === null) {
            throw new RuntimeException('Events need an event date.');
        }
        if ($form['category'] !== 'event') {
            $event_date = null;
        }

        $params = [$form['title'], $form['body'], $form['category'], $event_date, $expires_on, $form['is_published'] ? 't' : 'f'];
        if ($id > 0) {
            $params[] = $id;
            $ok = pg_query_params($conn,
                'UPDATE announcements SET title = $1, body = $2, category = $3, event_date = $4,
                 expires_on = $5, is_published = $6, updated_at = NOW() WHERE id = $7', $params);
        } else {
            $params[] = (int)$_SESSION['STAFF_ID'];
            $ok = pg_query_params($conn,
                'INSERT INTO announcements (title, body, category, event_date, expires_on, is_published, created_by)
                 VALUES ($1, $2, $3, $4, $5, $6, $7)', $params);
        }
        if (!$ok) {
            throw new RuntimeException('Could not save the announcement. Please try again.');
        }
        $_SESSION['ann_flash'] = $form['is_published'] ? 'Saved and published on the homepage.' : 'Saved as a draft (not on the homepage).';
        header('Location: announcements.php');
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
} elseif (isset($_GET['edit'])) {
    $result = pg_query_params($conn, 'SELECT * FROM announcements WHERE id = $1', [(int)$_GET['edit']]);
    $row = $result ? pg_fetch_assoc($result) : false;
    if ($row) {
        $form = ['id' => (int)$row['id'], 'title' => $row['title'], 'category' => $row['category'],
            'event_date' => (string)$row['event_date'], 'expires_on' => (string)$row['expires_on'],
            'body' => $row['body'], 'is_published' => $row['is_published'] === 't'];
    }
}

$flash = $_SESSION['ann_flash'] ?? '';
unset($_SESSION['ann_flash']);

$items = [];
$result = pg_query($conn, "SELECT id, title, category, event_date, expires_on, is_published, created_at,
    (expires_on IS NOT NULL AND expires_on < CURRENT_DATE) AS expired FROM announcements ORDER BY created_at DESC LIMIT 100");
while ($result && ($row = pg_fetch_assoc($result))) {
    $items[] = $row;
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Announcements | SmartCampus K12</title>
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
<style>
:root { --panel: #0F1D2C; --panel2: #14263A; --border: #1F3247; --muted: #9DB0B4; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--ink-deep); color: var(--sand); font-family: var(--font-body); }
main { max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }
a { color: var(--sun-gold); }
.back { display: inline-flex; min-height: 44px; align-items: center; }
.eyebrow { font-family: var(--font-utility); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--sun-gold); }
h1 { font-family: var(--font-display); font-size: 26px; margin: 6px 0 8px; }
h2 { font-size: 16px; margin: 28px 0 12px; }
.lead { color: var(--muted); margin: 0 0 20px; line-height: 1.6; }
.notice { border: 1px solid var(--border); border-left: 3px solid #7CC9A0; background: var(--panel); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
.notice.error { border-left-color: var(--reef-coral); }
.card { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 18px; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; }
input[type=text], input[type=date], select, textarea { width: 100%; min-height: 44px; background: var(--ink-deep); color: var(--sand); border: 1px solid var(--border); border-radius: 8px; padding: 10px; font: inherit; font-size: 14px; }
textarea { min-height: 140px; resize: vertical; }
.field { margin-bottom: 14px; }
.check { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; font-size: 14px; color: var(--sand); }
.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; }
.btn { display: inline-flex; align-items: center; min-height: 44px; padding: 0 18px; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; border: 1px solid var(--border); background: none; color: var(--sand); text-decoration: none; }
.btn.primary { background: var(--sun-gold); color: var(--ink-deep); border: none; }
.btn.danger { color: var(--reef-coral); border-color: var(--reef-coral); }
.list { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 640px; }
th, td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; font-size: 13px; vertical-align: middle; }
th { background: var(--panel2); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
.pill { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.pill.on { background: rgba(124,201,160,.16); color: #7CC9A0; }
.pill.off { background: rgba(157,176,180,.14); color: var(--muted); }
.pill.exp { background: rgba(232,115,74,.16); color: var(--reef-coral); }
.row-actions { display: flex; gap: 8px; }
.row-actions form { margin: 0; }
.empty { color: var(--muted); padding: 20px; text-align: center; }
input:focus-visible, select:focus-visible, textarea:focus-visible, button:focus-visible, a:focus-visible { outline: 2px solid var(--sun-gold); outline-offset: 2px; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
</style>
</head>
<body>
<main>
  <a class="back" href="dashboard.php">&larr; Dashboard</a>
  <div class="eyebrow">Homepage</div>
  <h1>Announcements</h1>
  <p class="lead">Published posts appear in “News &amp; Events” on the homepage within about 5 minutes. Upcoming events are listed by date; other posts by newest first.</p>

  <?php if ($error !== ''): ?><div class="notice error" role="alert"><?php echo ann_h($error); ?></div><?php endif; ?>
  <?php if ($flash !== ''): ?><div class="notice" role="status"><?php echo ann_h($flash); ?></div><?php endif; ?>

  <form class="card" method="post">
    <input type="hidden" name="token" value="<?php echo ann_h($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$form['id']; ?>">
    <div class="field">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" maxlength="120" required value="<?php echo ann_h($form['title']); ?>">
    </div>
    <div class="grid">
      <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach (ANNOUNCEMENT_CATEGORIES as $value => $label): ?>
            <option value="<?php echo $value; ?>"<?php echo $form['category'] === $value ? ' selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="event_date">Event date (events only)</label>
        <input type="date" id="event_date" name="event_date" value="<?php echo ann_h($form['event_date']); ?>">
      </div>
      <div class="field">
        <label for="expires_on">Hide after (optional)</label>
        <input type="date" id="expires_on" name="expires_on" value="<?php echo ann_h($form['expires_on']); ?>">
      </div>
    </div>
    <div class="field">
      <label for="body">Message</label>
      <textarea id="body" name="body" maxlength="2000" required><?php echo ann_h($form['body']); ?></textarea>
    </div>
    <label class="check"><input type="checkbox" name="is_published" value="1"<?php echo $form['is_published'] ? ' checked' : ''; ?>> Publish on the homepage</label>
    <div class="actions">
      <button class="btn primary" type="submit"><?php echo $form['id'] ? 'Save changes' : 'Add announcement'; ?></button>
      <?php if ($form['id']): ?><a class="btn" href="announcements.php">Cancel</a><?php endif; ?>
    </div>
  </form>

  <h2>All announcements</h2>
  <div class="list">
    <?php if (!$items): ?>
      <p class="empty">No announcements yet. Add the first one above.</p>
    <?php else: ?>
    <table>
      <caption class="sr-only">All announcements</caption>
      <thead><tr><th scope="col">Title</th><th scope="col">Category</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?php echo ann_h($item['title']); ?></td>
          <td><?php echo ann_h(ANNOUNCEMENT_CATEGORIES[$item['category']] ?? $item['category']); ?></td>
          <td><?php echo ann_h($item['event_date'] ?: substr((string)$item['created_at'], 0, 10)); ?></td>
          <td>
            <?php if ($item['expired'] === 't'): ?><span class="pill exp">Expired</span>
            <?php elseif ($item['is_published'] === 't'): ?><span class="pill on">Published</span>
            <?php else: ?><span class="pill off">Draft</span><?php endif; ?>
          </td>
          <td>
            <div class="row-actions">
              <a class="btn" href="announcements.php?edit=<?php echo (int)$item['id']; ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this announcement? This cannot be undone.')">
                <input type="hidden" name="token" value="<?php echo ann_h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                <button class="btn danger" type="submit" name="action" value="delete" aria-label="Delete <?php echo ann_h($item['title']); ?>">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
