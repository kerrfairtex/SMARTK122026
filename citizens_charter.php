<?php
/**
 * Citizen's Charter — frontline services of Batu-Batu National High School.
 *
 * Service content lives in $services below so the school can update steps,
 * requirements and times without touching the layout. Processing times are
 * proposed targets until the school's approved Citizen's Charter replaces them.
 *
 * @package SmartCampus
 */

declare(strict_types=1);

header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'none'");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$school = [
    'name' => 'Batu-Batu National High School',
    'id' => '305053',
    'address' => 'Barangay Batu-Batu, Poblacion, Panglima Sugala, Tawi-Tawi, BARMM',
    'phone' => '(062) 992-4151',
    'email' => 'batubatucampus@gmail.com',
    'hours' => 'Monday to Friday, 8:00 AM – 5:00 PM (except holidays)',
];

$services = [
    [
        'title' => 'Enrollment of learners (new, transferee, returning)',
        'who' => 'Learners and their parents or guardians',
        'requirements' => [
            'PSA birth certificate (for new learners)',
            'Report card (SF9) from the last school attended, if any',
            'Learner Reference Number (LRN), if the learner already has one',
        ],
        'steps' => [
            ['Fill out the online enrollment form on this website, or the enrollment form at the school office.', 'Records the application and gives a reference number.', '10 minutes'],
            ['Present the requirements at the school office (or upload them online).', 'Checks the documents and the LRN.', '15 minutes'],
            ['Check the application status online with the reference number.', 'Approves the application, encodes the learner and assigns a section.', '1 working day'],
        ],
        'fee' => 'None',
        'total' => '1 working day after complete requirements',
        'office' => 'Registrar / enrollment committee',
    ],
    [
        'title' => 'Transfer of the learner\'s permanent record (SF10)',
        'who' => 'Receiving schools (requests are school-to-school)',
        'requirements' => [
            'Written request from the receiving school',
            'Name and LRN of the learner',
        ],
        'steps' => [
            ['The receiving school sends a request for the SF10.', 'Logs the request and retrieves the record.', '1 working day'],
            ['—', 'Sends the SF10 directly to the receiving school.', '2 working days'],
        ],
        'fee' => 'None',
        'total' => '3 working days',
        'office' => 'Registrar',
    ],
    [
        'title' => 'Certificate of Enrollment / Good Moral Character',
        'who' => 'Currently or previously enrolled learners, or their parents or guardians',
        'requirements' => [
            'Request form (available at the school office)',
            'Valid ID of the learner or of the parent or guardian',
        ],
        'steps' => [
            ['Fill out the request form at the school office.', 'Verifies the learner\'s records.', '15 minutes'],
            ['Claim the certificate.', 'Prepares, signs and releases the certificate.', '1 working day'],
        ],
        'fee' => 'None',
        'total' => '1 working day',
        'office' => 'Registrar / Guidance office',
    ],
    [
        'title' => 'Release of the report card (SF9)',
        'who' => 'Parents or guardians of enrolled learners',
        'requirements' => [
            'Attendance at the scheduled parent-teacher conference, or a valid ID if claiming at the office',
        ],
        'steps' => [
            ['Attend the parent-teacher conference at the end of the quarter.', 'The adviser discusses and releases the report card.', '30 minutes'],
        ],
        'fee' => 'None',
        'total' => 'On the scheduled conference day',
        'office' => 'Class adviser',
    ],
];

function cc_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Citizen's Charter — <?php echo cc_h($school['name']); ?></title>
<meta name="description" content="Frontline services of <?php echo cc_h($school['name']); ?>: requirements, steps, fees and processing times.">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
<style>
* { box-sizing: border-box; }
body { margin: 0; background: var(--ink-deep); color: var(--sand); font-family: var(--font-body); line-height: 1.6; }
.wrap { max-width: 960px; margin: 0 auto; padding: var(--space-4) var(--space-3) var(--space-5); }
a { color: var(--sun-gold); }
.home { display: inline-flex; align-items: center; min-height: 44px; }
.eyebrow { font-family: var(--font-utility); font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--sun-gold); }
h1 { font-family: var(--font-display); font-size: clamp(1.6rem, 4vw, 2.2rem); margin: var(--space-1) 0 var(--space-2); }
h2 { font-family: var(--font-display); font-size: 1.25rem; margin: 0 0 var(--space-3); }
.lead { color: var(--foam); margin: 0 0 var(--space-4); }
.facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-2) var(--space-4); background: var(--ink-overlay-40); border: 1px solid var(--foam-overlay-16); border-radius: var(--radius-2); padding: var(--space-3) var(--space-4); margin-bottom: var(--space-4); font-size: 0.9rem; }
.facts b { display: block; color: var(--foam); font-weight: 500; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; }
.service { background: var(--ink-overlay-40); border: 1px solid var(--foam-overlay-16); border-left: 4px solid var(--sun-gold); border-radius: var(--radius-2); padding: var(--space-4); margin-bottom: var(--space-4); }
.meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-2) var(--space-4); margin-bottom: var(--space-3); font-size: 0.9rem; }
.meta b { display: block; color: var(--foam); font-weight: 500; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; }
h3 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--sun-gold); margin: var(--space-3) 0 var(--space-2); }
ul { margin: 0; padding-left: 1.2rem; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 560px; font-size: 0.9rem; }
th, td { text-align: left; vertical-align: top; padding: var(--space-2) var(--space-3); border-bottom: 1px solid var(--foam-overlay-16); }
th { color: var(--foam); font-weight: 500; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; }
td.time { white-space: nowrap; font-family: var(--font-utility); font-size: 0.85rem; }
.note { color: var(--foam); font-size: 0.85rem; border-top: 1px solid var(--foam-overlay-16); padding-top: var(--space-3); }
a:focus-visible { outline: 2px solid var(--sun-gold); outline-offset: 2px; }
@media print { body { background: #fff; color: #111; } .service, .facts { background: none; border-color: #999; } a { color: #111; } .home { display: none; } }
</style>
</head>
<body>
<div class="wrap">
  <a class="home" href="/">&larr; Back to homepage</a>
  <div class="eyebrow">Citizen's Charter</div>
  <h1><?php echo cc_h($school['name']); ?></h1>
  <p class="lead">The frontline services of the school: who may avail, what to bring, the steps, fees and processing times. Basic education services in public schools are free of charge.</p>

  <div class="facts">
    <span><b>School ID</b><?php echo cc_h($school['id']); ?></span>
    <span><b>Office hours</b><?php echo cc_h($school['hours']); ?></span>
    <span><b>Address</b><?php echo cc_h($school['address']); ?></span>
    <span><b>Contact</b><?php echo cc_h($school['phone']); ?> &middot; <a href="mailto:<?php echo cc_h($school['email']); ?>"><?php echo cc_h($school['email']); ?></a></span>
  </div>

  <?php foreach ($services as $i => $service): ?>
    <section class="service" aria-labelledby="service-<?php echo $i; ?>">
      <h2 id="service-<?php echo $i; ?>"><?php echo ($i + 1) . '. ' . cc_h($service['title']); ?></h2>
      <div class="meta">
        <span><b>Who may avail</b><?php echo cc_h($service['who']); ?></span>
        <span><b>Office</b><?php echo cc_h($service['office']); ?></span>
        <span><b>Fees</b><?php echo cc_h($service['fee']); ?></span>
        <span><b>Total processing time</b><?php echo cc_h($service['total']); ?></span>
      </div>
      <h3>Requirements</h3>
      <ul>
        <?php foreach ($service['requirements'] as $requirement): ?><li><?php echo cc_h($requirement); ?></li><?php endforeach; ?>
      </ul>
      <h3>Steps</h3>
      <div class="table-wrap">
        <table>
          <caption class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Steps for <?php echo cc_h($service['title']); ?></caption>
          <thead><tr><th scope="col">Client steps</th><th scope="col">School action</th><th scope="col">Time</th></tr></thead>
          <tbody>
            <?php foreach ($service['steps'] as [$client, $school_action, $time]): ?>
              <tr><td><?php echo cc_h($client); ?></td><td><?php echo cc_h($school_action); ?></td><td class="time"><?php echo cc_h($time); ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endforeach; ?>

  <section class="service" aria-labelledby="feedback">
    <h2 id="feedback">Feedback and complaints</h2>
    <ul>
      <li>Talk to the school office, write to <a href="mailto:<?php echo cc_h($school['email']); ?>"><?php echo cc_h($school['email']); ?></a>, or call <?php echo cc_h($school['phone']); ?>.</li>
      <li>Concerns not resolved by the school may be raised with the Schools Division Office of Tawi-Tawi.</li>
      <li>Government service complaints may also be reported to the 8888 Citizens' Complaint Hotline.</li>
    </ul>
  </section>

  <p class="note">Processing times shown are proposed targets for the SmartCampus K-12 pilot. Where they differ, the school's approved Citizen's Charter prevails. See also <a href="/privacy.php">Privacy &amp; Accessibility</a>.</p>
</div>
</body>
</html>
