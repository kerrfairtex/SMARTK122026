<?php
/**
 * Public Landing Page — Batu-Batu National Integrated High School
 *
 * A standalone institutional page separate from the RosarioSIS login system.
 * Features real photography and PSA-verified community statistics.
 *
 * @package SmartCampus
 * @since   1.0
 */

// Read RosarioSIS config for school identity (optional — falls back to defaults)
$school_name = 'Batu-Batu National High School';
$school_short_name = 'BBNIHS';
$school_id = '305053';
$theme = 'FlatSIS';
// Big hero display name (upper-center lettering). Kept short/branded per request.
$hero_name = 'BATU-BATU';

// Contact details. IMPORTANT: Kerr Fairtex is the SmartCampus K-12 PROJECT /
// DEVELOPER contact (technology & website support) — NOT the school's official
// representative. Official school matters stay attributable to the school / DepEd.
$project_phone = '09637130812';                              // SmartCampus project mobile (Kerr Fairtex)
$project_email = 'kerrfairtex@gmail.com';
$project_facebook = 'https://www.facebook.com/KerrFairtex';
$school_maps = 'https://maps.app.goo.gl/iqsoLNLLXWFH9FjQ6';
$deped_division_phone = '(062) 992-4151';                    // Tawi-Tawi Schools Division Office
$school_address = 'Batu-Batu, Poblacion, Panglima Sugala, Tawi-Tawi';
$school_classification = 'Public &bull; DepEd Managed &bull; JHS with SHS';

// Attempt to read from RosarioSIS config if available (with error handling)
$rosariosis_config = __DIR__ . '/config.inc.php';
$rosariosis_warehouse = __DIR__ . '/Warehouse.php';
if (file_exists($rosariosis_config) && file_exists($rosariosis_warehouse)) {
    try {
        require_once $rosariosis_config;
        require_once $rosariosis_warehouse;
        // NOTE: Config() -> DBGet -> db_start, which calls die() on connection
        // failure (db_show_error). To avoid a white-screen when the DB is down,
        // probe connectivity first; only read identity if the DB answers.
        $db_ok = false;
        try {
            $db_ok = (db_start(false) !== false);
        } catch (Throwable $t) {
            $db_ok = false;
        }
        if ($db_ok) {
            // Only read identity if the RosarioSIS schema is actually present.
            // A missing 'config' table must NOT white-screen the public landing.
            try {
                $cfg_conn = db_start(false);
                $cfg_check = @pg_query($cfg_conn, 'SELECT 1 FROM config LIMIT 1');
                if ($cfg_check !== false) {
                    // NOTE: intentionally do NOT overwrite $school_name with
                    // Config('TITLE') — the DB TITLE is the SIS brand label
                    // ('SMARTCAMP-K12'), not the school's real name. The landing
                    // uses the hardcoded official name and $hero_name='BATU-BATU'.
                    $school_short_name = Config('NAME') ?: $school_short_name;
                    $theme = Config('THEME') ?: $theme;
                }
            } catch (Throwable $t) {
                // DB reachable but schema missing/incomplete -> keep hardcoded defaults
            }
        }
    } catch (Throwable $e) {
        // Fall back to defaults if config loading fails
    }
}

// PSA-verified community statistics (2024 POPCEN)
$community_stats = [
    'batu_batu' => ['population' => 3936, 'label' => 'Batu-Batu', 'context' => 'Barangay Population'],
    'panglima_sugala' => ['population' => 52657, 'label' => 'Panglima Sugala', 'context' => 'Municipality Population'],
    'tawi_tawi' => ['population' => 482645, 'label' => 'Tawi-Tawi', 'context' => 'Province Population'],
];

// Image base path
$img_base = 'assets/images/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($school_name); ?> — Smart Campus K12</title>
    <meta name="description" content="<?php echo htmlspecialchars($school_name); ?> — A public integrated high school in Batu-Batu, Panglima Sugala, Tawi-Tawi, BARMM, Philippines. School ID: <?php echo $school_id; ?>">
    <style>
        :root {
            --navy-deep: #08182B;
            --navy: #101F35;
            --navy-light: #142640;
            --sea-blue: #1a4a6b;
            --sea-light: #2a6a9b;
            --sand: #f5f0e8;
            --white: #F8FAFC;
            --gray-100: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-500: #94A3B8;
            --accent: #2a9bd0;
            --accent-hover: #005f8a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--navy-deep);
            color: var(--gray-100);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            background-color: var(--navy);
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('<?php echo $img_base; ?>img-09.jpeg');
            background-size: cover;
            background-position: center;
            filter: brightness(0.5);
            z-index: 0;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(8,24,43,0.6) 0%, rgba(8,24,43,0.8) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }

        .hero .tagline {
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            color: var(--gray-300);
            margin-bottom: 1.5rem;
            font-weight: 300;
            text-shadow: 0 1px 10px rgba(0,0,0,0.5);
        }

        .hero .location {
            font-size: 0.95rem;
            color: var(--gray-500);
            margin-bottom: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .hero .supporting {
            font-size: 1.05rem;
            color: var(--gray-300);
            max-width: 620px;
            margin: 0 auto 2rem;
            font-weight: 300;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 0.875rem 2rem;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--white);
            border-color: var(--accent);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,115,170,0.3);
        }

        .btn-outline {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border-color: var(--white);
            backdrop-filter: blur(5px);
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--navy-deep);
        }

        /* Section Styles */
        section {
            padding: 5rem 2rem;
            scroll-margin-top: 80px;
        }

        /* Navigation Bar */
        .nav-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--navy-deep);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            z-index: 1000;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 2rem;
            overflow-x: auto;
        }
        .nav-bar a {
            color: var(--gray-300);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .nav-bar a:hover {
            color: var(--white);
            background: rgba(255,255,255,0.08);
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1rem;
            color: var(--gray-500);
            margin-bottom: 2rem;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* School at a Glance */
        .glance {
            background: var(--navy);
        }

        .glance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .glance-card {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }

        .glance-card .label {
            font-size: 0.8rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .glance-card .value {
            font-size: 1.1rem;
            color: var(--white);
            font-weight: 600;
        }

        /* Community Stats */
        .community {
            background: var(--navy-deep);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.25rem;
        }

        .stat-card .context {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            font-size: 1rem;
            color: var(--gray-300);
            font-weight: 600;
        }

        /* About */
        .about {
            background: var(--navy);
        }

        .about-content {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
        }

        .about-content p {
            color: var(--gray-300);
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }

        /* Photo Sections */
        .photo-section {
            position: relative;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: var(--navy);
        }

        .photo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            background-position: center;
            filter: brightness(0.5);
        }

        .photo-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(8,24,43,0.7) 0%, rgba(20,32,64,0.7) 100%);
        }

        .photo-section > * {
            position: relative;
            z-index: 2;
        }

        .photo-section.island-section::before {
            background-image: url('<?php echo $img_base; ?>img-09.jpeg');
        }

        .photo-section.community-section::before {
            background-image: url('<?php echo $img_base; ?>img-02.jpeg');
        }

        .photo-content {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
        }

        .photo-content h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .photo-content p {
            color: var(--gray-300);
            font-size: 1.1rem;
            text-shadow: 0 1px 5px rgba(0,0,0,0.5);
        }

        /* Academics */
        .academics {
            background: var(--navy-deep);
        }

        .academics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .academic-card {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 2rem;
        }

        .academic-card h3 {
            color: var(--white);
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .academic-card p {
            color: var(--gray-500);
            font-size: 0.95rem;
        }

        /* Smart Campus */
        .smart-campus {
            background: linear-gradient(135deg, var(--navy) 0%, var(--sea-blue) 100%);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .feature-item {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
        }

        .feature-item .icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .feature-item .name {
            font-size: 0.85rem;
            color: var(--gray-300);
        }

        /* Island Community */
        .island {
            background: var(--navy);
        }

        .island-content {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
        }

        .island-content p {
            color: var(--gray-300);
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }

        .identity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .identity-tag {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            color: var(--gray-300);
        }

        /* Campus Gallery */
        .gallery {
            background: var(--navy-deep);
        }

        /* Campus Life & Community Gallery (single-photo editorial) */
        .gallery { background: var(--navy); }
        .cg-stage {
            position: relative;
            max-width: 900px;
            margin: 1.5rem auto 0;
            aspect-ratio: 16 / 9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.35);
            background: var(--navy-deep);
        }
        .cg-stage img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }
        .cg-stage img.active { opacity: 1; }
        .cg-stage .cg-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(8,24,43,0.55);
            color: var(--white);
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            font-size: 1.3rem;
            cursor: pointer;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }
        .cg-stage .cg-arrow:hover { background: var(--accent); }
        .cg-stage .cg-prev { left: 12px; }
        .cg-stage .cg-next { right: 12px; }
        .cg-caption {
            max-width: 900px;
            margin: 1rem auto 0;
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.1rem 1.5rem;
        }
        .cg-caption h4 { color: var(--white); margin: 0 0 0.4rem; font-size: 1.05rem; }
        .cg-caption p { color: var(--gray-300); margin: 0; font-size: 0.92rem; line-height: 1.5; }
        .cg-controls {
            max-width: 900px;
            margin: 0.75rem auto 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .cg-controls .cg-text {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--gray-300);
            padding: 0.45rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .cg-controls .cg-text:hover { border-color: var(--accent); color: var(--white); }
        .cg-dots { display: flex; gap: 8px; }
        .cg-dots .dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: rgba(255,255,255,0.35); cursor: pointer; transition: background 0.2s ease;
        }
        .cg-dots .dot.active { background: var(--white); }

        .gallery-card .caption h4 {
            color: var(--white);
            font-size: 1.05rem;
            margin-bottom: 0.35rem;
        }

        .gallery-card .caption p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* Timeline */
        .timeline {
            background: var(--navy-deep);
        }

        .timeline-track {
            max-width: 760px;
            margin: 2rem auto 0;
            border-left: 2px solid rgba(255,255,255,0.15);
            padding-left: 2rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.45rem;
            top: 0.35rem;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--accent);
            border: 2px solid var(--navy-deep);
        }

        .timeline-item h4 {
            color: var(--white);
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .timeline-item p {
            color: var(--gray-500);
            font-size: 0.95rem;
        }

        /* Academic levels */
        .level-block {
            margin-bottom: 2rem;
        }

        .level-block h3 {
            color: var(--white);
            font-size: 1.35rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent);
            padding-left: 0.75rem;
        }

        .grade-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
        }

        .grade-item {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            padding: 0.9rem;
            text-align: center;
            color: var(--gray-300);
            font-weight: 600;
        }

        .track-note {
            margin-top: 1rem;
            color: var(--gray-500);
            font-size: 0.9rem;
            font-style: italic;
        }

        /* Values */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .value-item {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            padding: 1.25rem;
        }

        .value-item h4 {
            color: var(--white);
            font-size: 1rem;
            margin-bottom: 0.35rem;
        }

        .value-item p {
            color: var(--gray-500);
            font-size: 0.88rem;
        }

        /* About photo slider */
        .about-slider {
            position: relative;
            max-width: 700px;
            margin: 0 auto 2rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .about-slides {
            position: relative;
            width: 100%;
            height: 320px;
        }

        .about-slides img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 320px;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.6s ease-in-out;
        }

        .about-slides img.active {
            opacity: 1;
        }

        .about-slider .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(8,24,43,0.6);
            color: var(--white);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.25rem;
            cursor: pointer;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .about-slider .nav-btn:hover {
            background: var(--accent);
        }

        .about-slider .prev { left: 10px; }
        .about-slider .next { right: 10px; }

        .about-dots {
            position: absolute;
            bottom: 12px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            z-index: 3;
        }

        .about-dots .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .about-dots .dot.active {
            background: var(--white);
        }

        /* Enrollment Portal */
        .enroll {
            background: linear-gradient(135deg, var(--navy) 0%, var(--sea-blue) 100%);
        }

        .enroll-status {
            max-width: 760px;
            margin: 1.5rem auto 2rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }

        .enroll-status .stat {
            text-align: center;
        }

        .enroll-status .stat .k {
            font-size: 0.8rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }

        .enroll-status .stat .v {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--white);
        }

        .enroll-status .stat .v.open { color: #4ade80; }

        .enroll-cta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .steps {
            max-width: 760px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .step-card {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.5rem;
        }

        .step-card .num {
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--accent);
            color: var(--white);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .step-card h4 { color: var(--white); font-size: 1rem; margin-bottom: 0.35rem; }
        .step-card p { color: var(--gray-500); font-size: 0.9rem; }

        .enroll-form-wrap {
            max-width: 560px;
            margin: 2.5rem auto 0;
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 2rem;
        }

        .enroll-form-wrap h3 { color: var(--white); margin-bottom: 1rem; text-align: center; }

        .form-field { margin-bottom: 1rem; }
        .form-field label { display: block; color: var(--gray-300); font-size: 0.9rem; margin-bottom: 0.35rem; }
        .form-field input, .form-field select {
            width: 100%;
            padding: 0.7rem;
            border-radius: 4px;
            border: 1px solid rgba(255,255,255,0.15);
            background: var(--navy-deep);
            color: var(--white);
            font-size: 0.95rem;
        }

        .form-note { color: var(--gray-500); font-size: 0.82rem; margin-top: 0.75rem; text-align: center; }

        .enroll-result {
            margin-top: 1.25rem;
            padding: 1rem;
            border-radius: 6px;
            background: rgba(74,222,128,0.12);
            border: 1px solid rgba(74,222,128,0.4);
            color: var(--white);
            font-size: 0.95rem;
            display: none;
        }
        .enroll-result.show { display: block; }

        .ms-progress {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .ms-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--navy-deep);
            color: var(--gray-500);
            border: 1px solid rgba(255,255,255,0.15);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .ms-dot.active { background: var(--accent); color: var(--white); border-color: var(--accent); }

        .ms-step h4 { color: var(--white); margin-bottom: 1rem; }
        .ms-step[hidden] { display: none; }

        .ms-nav {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-top: 1.25rem;
        }

        .review-box {
            background: var(--navy-deep);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 1rem 1.25rem;
            color: var(--gray-300);
            font-size: 0.92rem;
        }
        .review-box div { margin-bottom: 0.35rem; }
        .review-box strong { color: var(--white); }

        /* Status pipeline */
        .pipeline {
            text-align: left;
            max-width: 320px;
            margin: 1rem auto 0;
        }
        .pipeline .pstep {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.4rem 0;
            color: var(--gray-500);
        }
        .pipeline .pstep .bullet {
            width: 14px; height: 14px; border-radius: 50%;
            border: 2px solid var(--gray-500);
            flex: none;
        }
        .pipeline .pstep.done { color: var(--gray-300); }
        .pipeline .pstep.done .bullet { background: var(--gray-500); }
        .pipeline .pstep.current { color: #4ade80; font-weight: 700; }
        .pipeline .pstep.current .bullet { border-color: #4ade80; background: #4ade80; }

        /* Who can enroll / categories */
        .enroll-cards {
            max-width: 900px;
            margin: 2rem auto 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }

        .ecard {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.25rem;
        }

        .ecard h4 { color: var(--white); font-size: 1rem; margin-bottom: 0.35rem; }
        .ecard p { color: var(--gray-500); font-size: 0.85rem; }

        .enroll-sub {
            max-width: 900px;
            margin: 2.5rem auto 0;
        }

        .enroll-sub h3 {
            color: var(--white);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent);
            padding-left: 0.75rem;
        }

        .req {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            margin-bottom: 0.6rem;
            overflow: hidden;
        }

        .req > summary {
            cursor: pointer;
            padding: 0.9rem 1.1rem;
            color: var(--white);
            font-weight: 600;
            list-style: none;
        }

        .req > summary::-webkit-details-marker { display: none; }
        .req > summary::before { content: '+ '; color: var(--accent); }
        .req[open] > summary::before { content: '\2212 '; }

        .req ul { padding: 0 1.1rem 1rem 2.2rem; color: var(--gray-300); font-size: 0.9rem; }
        .req ul li { margin-bottom: 0.3rem; }

        .config-note {
            max-width: 900px;
            margin: 1.5rem auto 0;
            color: var(--gray-500);
            font-size: 0.82rem;
            font-style: italic;
            text-align: center;
        }

        /* Important dates */
        .dates-grid {
            max-width: 760px;
            margin: 1.5rem auto 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .date-card {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.25rem;
            text-align: center;
        }
        .date-card .k { color: var(--gray-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
        .date-card .v { color: var(--white); font-size: 1.05rem; font-weight: 700; }
        .dates-cta { text-align: center; margin-top: 1.5rem; }

        /* Status timeline (static legend from spec) */
        .status-legend {
            max-width: 760px;
            margin: 1.5rem auto 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }
        .status-legend .sl {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            padding: 0.8rem 1rem;
        }
        .status-legend .sl b { color: var(--white); display: block; margin-bottom: 0.2rem; }
        .status-legend .sl span { color: var(--gray-500); font-size: 0.82rem; }

        /* FAQ */
        .faq { max-width: 820px; margin: 1.5rem auto 0; }
        .faq details {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            margin-bottom: 0.6rem;
            overflow: hidden;
        }
        .faq details > summary {
            cursor: pointer;
            padding: 0.9rem 1.1rem;
            color: var(--white);
            font-weight: 600;
            list-style: none;
        }
        .faq details > summary::-webkit-details-marker { display: none; }
        .faq details > summary::before { content: '+ '; color: var(--accent); }
        .faq details[open] > summary::before { content: '\2212 '; }
        .faq details p { padding: 0 1.1rem 1rem 1.1rem; color: var(--gray-300); font-size: 0.92rem; }

        /* Contact */
        .contact {
            background: var(--navy-deep);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .contact-card {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.25rem;
        }

        .contact-card h4 {
            color: var(--white);
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }

        .contact-card p {
            color: var(--gray-300);
            font-size: 0.92rem;
            margin-bottom: 0.6rem;
        }

        .contact-card a { color: var(--accent); text-decoration: none; }
        .contact-card a:hover { text-decoration: underline; }

        .contact-source {
            color: var(--gray-500) !important;
            font-size: 0.75rem !important;
            font-style: italic;
            margin-top: 0.5rem;
        }

        .contact-form-wrap {
            max-width: 560px;
            margin: 2.5rem auto 0;
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.5rem;
        }
        .contact-form-wrap h3 { color: var(--white); margin-bottom: 1.25rem; text-align: center; }
        .contact-form-wrap .btn-primary { width: 100%; }
        .contact-form-wrap .enroll-result { margin-top: 1rem; }

        .contact-who {
            max-width: 760px;
            margin: 2.5rem auto 0;
        }
        .contact-who h3 { color: var(--white); margin-bottom: 1rem; text-align: center; }
        .who-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--navy-light);
            border-radius: 8px;
            overflow: hidden;
        }
        .who-table th, .who-table td {
            text-align: left;
            padding: 0.7rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: var(--gray-300);
            font-size: 0.9rem;
        }
        .who-table th { background: var(--navy-deep); color: var(--white); }
        .who-table tr:last-child td { border-bottom: none; }

        /* Offline enrollment (connectivity-resilient) */
        .offline-enroll {
            max-width: 760px;
            margin: 2rem auto 0;
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 1.5rem;
        }
        .offline-enroll h3 { color: var(--white); text-align: center; margin-bottom: 0.5rem; }
        .offline-flow {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            justify-content: center;
            align-items: center;
            margin: 1rem 0;
        }
        .offline-flow .step {
            background: var(--navy-deep);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 0.6rem 0.9rem;
            color: var(--gray-300);
            font-size: 0.88rem;
        }
        .offline-flow .arrow { color: var(--accent); font-weight: 700; }
        .offline-enroll .need {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* Need Assistance (high-visibility, Turtle Islands community) */
        .assistance {
            background: linear-gradient(135deg, var(--accent) 0%, #0e7490 100%);
            color: var(--white);
            text-align: center;
        }
        .assistance .container { max-width: 760px; }
        .assistance h2 { color: var(--white); }
        .assistance .sub { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .assistance .desc { color: rgba(255,255,255,0.9); margin-bottom: 1.5rem; }
        .assistance .actions { margin-bottom: 1.75rem; }
        .assistance .btn-primary { background: var(--white); color: var(--navy); border-color: var(--white); }
        .assistance .btn-primary:hover { background: var(--navy-light); color: var(--white); }
        .assist-office {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .assist-office h4 { color: var(--white); margin-bottom: 0.4rem; }
        .assist-office p { color: rgba(255,255,255,0.9); margin: 0; }
        .assist-buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .assist-buttons a {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            color: var(--white);
            padding: 0.6rem 1.1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
        .assist-buttons a:hover { background: rgba(255,255,255,0.22); }
        .assist-note { font-size: 0.85rem; color: rgba(255,255,255,0.85); font-style: italic; }

        /* Footer */
        footer {
            background: var(--navy-deep);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 2.5rem 2rem 1.5rem;
        }

        .footer-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
        }

        .footer-col h4 {
            color: var(--white);
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
        }

        .footer-col p {
            color: var(--gray-300);
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }

        .footer-col a { color: var(--accent); text-decoration: none; }
        .footer-col a:hover { text-decoration: underline; }

        .footer-muted { color: var(--gray-500) !important; }

        .footer-copy {
            text-align: center;
            color: var(--gray-500);
            font-size: 0.8rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.75rem;
            }

            .hero .tagline {
                font-size: 1rem;
            }

            section {
                padding: 2.5rem 1.5rem;
            }

            .stat-card .number {
                font-size: 2rem;
            }

            .photo-section {
                min-height: 300px;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="nav-bar">
        <a href="#glance">At a Glance</a>
        <a href="#community">Community</a>
        <a href="#about">About</a>
        <a href="#admissions">Academics</a>
        <a href="#enroll">Admissions</a>
        <a href="#features">Features</a>
        <a href="#contact">Contact</a>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($hero_name); ?></h1>
            <p class="tagline">Learning, growing, and building the future of Tawi-Tawi</p>
            <p class="location">Batu-Batu, Panglima Sugala, Tawi-Tawi &bull; BARMM</p>
            <p class="supporting">A public high school serving learners in Batu-Batu, Panglima Sugala, supported by SmartCampus K&ndash;12 digital services.</p>
            <div class="hero-buttons">
                <a href="#about" class="btn btn-primary">Discover Our School</a>
                <a href="login.php" class="btn btn-outline">SmartCampus Portal</a>
                <a href="#enroll" class="btn btn-outline">Admissions / Enrollment</a>
                <a href="#contact" class="btn btn-outline">Contact the School</a>
            </div>
        </div>
    </section>

    <!-- School at a Glance -->
    <section id="glance" class="glance">
        <div class="container">
            <h2 class="section-title">School at a Glance</h2>
            <p class="section-subtitle">Verified institutional information</p>
            <div class="glance-grid">
                <div class="glance-card">
                    <div class="label">School ID</div>
                    <div class="value"><?php echo $school_id; ?></div>
                </div>
                <div class="glance-card">
                    <div class="label">School Name</div>
                    <div class="value"><?php echo htmlspecialchars($school_name); ?></div>
                </div>
                <div class="glance-card">
                    <div class="label">Location</div>
                    <div class="value">Batu-Batu, Panglima Sugala</div>
                </div>
                <div class="glance-card">
                    <div class="label">Province</div>
                    <div class="value">Tawi-Tawi, BARMM</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Community -->
    <section id="community" class="community">
        <div class="container">
            <h2 class="section-title">Our Community</h2>
            <p class="section-subtitle">Philippine Statistics Authority, 2024 Population Census</p>
            <div class="stats-grid">
                <?php foreach ($community_stats as $key => $stat): ?>
                <div class="stat-card">
                    <div class="number"><?php echo number_format($stat['population']); ?></div>
                    <div class="context"><?php echo $stat['context']; ?></div>
                    <div class="label"><?php echo $stat['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Island Community Photo Section -->
    <section class="photo-section island-section">
        <div class="photo-content">
            <h2>Our Island Home</h2>
            <p>Tawi-Tawi is the Philippines' southernmost province — a region of islands, maritime culture, and diverse communities including the Sama, Jama Mapun, Badjao, and Tausug peoples.</p>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <h2 class="section-title">About Batu-Batu NHS</h2>
                <div class="about-slider" id="aboutSlider">
                    <div class="about-slides">
                        <img class="active" src="assets/images/bbnihs-legacy.jpg" alt="Batu-Batu National High School campus">
                        <img src="assets/images/Batu-batu1.jpeg" alt="Batu-Batu National High School">
                        <img src="assets/images/bbnihs-graduation.jpeg" alt="Batu-Batu National High School graduation">
                        <img src="assets/images/Batu-batu3.jpeg" alt="Batu-Batu National High School">
                        <img src="assets/images/Batu-batu4.jpeg" alt="Batu-Batu National High School">
                    </div>
                    <button class="nav-btn prev" aria-label="Previous photo">&#10094;</button>
                    <button class="nav-btn next" aria-label="Next photo">&#10095;</button>
                    <div class="about-dots">
                        <span class="dot active" data-i="0"></span>
                        <span class="dot" data-i="1"></span>
                        <span class="dot" data-i="2"></span>
                        <span class="dot" data-i="3"></span>
                        <span class="dot" data-i="4"></span>
                    </div>
                </div>

                <p><strong><?php echo htmlspecialchars($school_name); ?></strong> (School ID <?php echo $school_id; ?>) is a public National High School in Batu-Batu, Panglima Sugala, Tawi-Tawi, within the Bangsamoro Autonomous Region in Muslim Mindanao (BARMM).</p>

                <p><strong>Location:</strong> Batu-Batu is a barangay in the municipality of Panglima Sugala (Balimbing), Tawi-Tawi, near the Philippines&ndash;Malaysia border. The school serves learners from the local island community and surrounding barangays.</p>

                <p><strong>Educational mandate:</strong> As a National Integrated High School, the institution delivers the K&ndash;12 basic education program &mdash; Junior High School (Grades 7&ndash;10) and Senior High School (Grades 11&ndash;12) &mdash; under the Department of Education (DepEd), Republic of the Philippines.</p>

                <p><strong>Mission:</strong> <em>[To be configured from verified school records &mdash; insert the official DepEd/BARMM mission statement.]</em></p>

                <p><strong>Vision:</strong> <em>[To be configured from verified school records &mdash; insert the official school vision.]</em></p>

                <div class="values-grid">
                    <div class="value-item">
                        <h4>Learner-Centered</h4>
                        <p>Education rooted in the needs of island-community learners.</p>
                    </div>
                    <div class="value-item">
                        <h4>Inclusive</h4>
                        <p>Accessible basic education for all children of the community.</p>
                    </div>
                    <div class="value-item">
                        <h4>Community</h4>
                        <p>Partnership with families and the Turtle Islands community.</p>
                    </div>
                    <div class="value-item">
                        <h4>Resilience</h4>
                        <p>Adapting to the opportunities and challenges of island life.</p>
                    </div>
                </div>

                <p style="margin-top:2rem;"><strong>School leadership &amp; faculty:</strong> <em>[Names and positions of the School Head, department heads, and teaching/non-teaching personnel to be populated from the official plantilla / verified school directory.]</em></p>

                <p><strong>Community role:</strong> Beyond instruction, the school is a hub for community learning, DepEd programs, and local development in Batu-Batu, Panglima Sugala, contributing to the social and economic life of the municipality and the wider Tawi-Tawi island communities.</p>
            </div>
        </div>
    </section>

    <!-- School History Timeline -->
    <section class="timeline">
        <div class="container">
            <h2 class="section-title">School History</h2>
            <p class="section-subtitle">A visual timeline of Batu-Batu NIHS</p>
            <div class="timeline-track">
                <div class="timeline-item">
                    <h4>Foundation</h4>
                    <p>The school was established to provide public secondary education to the Batu-Batu community. <em>[Exact founding year and original name &mdash; verify from DepEd records.]</em></p>
                </div>
                <div class="timeline-item">
                    <h4>Development</h4>
                    <p>Growth of academic offerings and school facilities to serve a growing island population.</p>
                </div>
                <div class="timeline-item">
                    <h4>Expansion</h4>
                    <p>Integration into the National Integrated High School system and expansion of the K&ndash;12 program.</p>
                </div>
                <div class="timeline-item">
                    <h4>SmartCampus Digital Transformation</h4>
                    <p>Adoption of SmartCampus K&ndash;12 digital services &mdash; student information, enrollment, attendance, and grades &mdash; to modernize school administration.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Academics -->
    <section id="admissions" class="academics">
        <div class="container">
            <h2 class="section-title">Academic Programs</h2>
            <p class="section-subtitle">K-12 basic education under DepEd</p>

            <div class="level-block">
                <h3>Junior High School</h3>
                <div class="grade-list">
                    <div class="grade-item">Grade 7</div>
                    <div class="grade-item">Grade 8</div>
                    <div class="grade-item">Grade 9</div>
                    <div class="grade-item">Grade 10</div>
                </div>
            </div>

            <div class="level-block">
                <h3>Senior High School</h3>
                <div class="grade-list">
                    <div class="grade-item">Grade 11</div>
                    <div class="grade-item">Grade 12</div>
                </div>
                <p class="track-note">Available tracks / strands are configured by the school in accordance with the DepEd Senior High School program offerings.</p>
            </div>

            <div style="text-align:center; margin-top:2rem;">
                <a href="#contact" class="btn btn-primary">Inquire About Admissions</a>
            </div>
        </div>
    </section>

    <!-- Admissions / Enrollment Portal -->
    <section id="enroll" class="enroll">
        <div class="container">
            <h2 class="section-title">Admissions &amp; Enrollment</h2>
            <p class="section-subtitle">Enroll at Batu-Batu National Integrated High School</p>

            <div class="enroll-status">
                <div class="stat">
                    <div class="k">Enrollment Status</div>
                    <div class="v open">OPEN</div>
                </div>
                <div class="stat">
                    <div class="k">School Year</div>
                    <div class="v">2026&ndash;2027</div>
                </div>
                <div class="stat">
                    <div class="k">Period</div>
                    <div class="v">Now &ndash; Aug 2026</div>
                </div>
                <div class="stat">
                    <div class="k">Accepting</div>
                    <div class="v">Grade 7&ndash;12</div>
                </div>
            </div>

            <div class="enroll-cta">
                <a href="#enroll-form" class="btn btn-primary">Start Enrollment</a>
                <a href="#enroll-status" class="btn btn-outline">Check Application Status</a>
            </div>

            <div class="enroll-sub">
                <h3>Important Enrollment Dates</h3>
                <div class="dates-grid" id="datesGrid">
                    <div class="date-card"><div class="k">Enrollment Opens</div><div class="v" id="dOpens">—</div></div>
                    <div class="date-card"><div class="k">Enrollment Period</div><div class="v" id="dPeriod">—</div></div>
                    <div class="date-card"><div class="k">Classes Begin</div><div class="v" id="dClasses">—</div></div>
                </div>
                <div class="dates-cta">
                    <a href="#enroll-form" class="btn btn-outline">View Enrollment Calendar</a>
                </div>

                <h3>Enrollment Status Timeline</h3>
                <div class="status-legend">
                    <div class="sl"><b>Submitted</b><span>Application received</span></div>
                    <div class="sl"><b>Under Review</b><span>School is reviewing information</span></div>
                    <div class="sl"><b>Documents Needed</b><span>Additional requirements required</span></div>
                    <div class="sl"><b>Verified</b><span>Documents / information verified</span></div>
                    <div class="sl"><b>Approved</b><span>Application approved</span></div>
                    <div class="sl"><b>Enrolled</b><span>Enrollment completed</span></div>
                    <div class="sl"><b>Rejected</b><span>Application cannot proceed</span></div>
                </div>
            </div>

            <div class="steps">
                <div class="step-card">
                    <span class="num">01</span>
                    <h4>Choose Enrollment Type</h4>
                    <p>Select New, Returning, Transferee, or Balik-Aral.</p>
                </div>
                <div class="step-card">
                    <span class="num">02</span>
                    <h4>Complete Application</h4>
                    <p>Fill learner, guardian, and previous-school details. Save &amp; continue later.</p>
                </div>
                <div class="step-card">
                    <span class="num">03</span>
                    <h4>Submit Documents</h4>
                    <p>List requirements online or submit physically at the school if connectivity is limited.</p>
                </div>
                <div class="step-card">
                    <span class="num">04</span>
                    <h4>School Verification</h4>
                    <p>The school reviews requirements and learner records.</p>
                </div>
                <div class="step-card">
                    <span class="num">05</span>
                    <h4>Enrollment Confirmed</h4>
                    <p>Upon approval, the learner is officially enrolled.</p>
                </div>
            </div>

            <div class="enroll-sub">
                <h3>Who Can Enroll?</h3>
                <div class="enroll-cards">
                    <div class="ecard">
                        <h4>Kindergarten</h4>
                        <p>For eligible incoming Kindergarten learners.</p>
                    </div>
                    <div class="ecard">
                        <h4>Elementary</h4>
                        <p>Incoming/returning elementary learners, where the school's grade coverage supports this.</p>
                    </div>
                    <div class="ecard">
                        <h4>Junior High School</h4>
                        <p>Grades 7&ndash;10.</p>
                    </div>
                    <div class="ecard">
                        <h4>Senior High School</h4>
                        <p>Grades 11&ndash;12.</p>
                    </div>
                </div>
                <p class="config-note">Actual grade-level availability is configured from the SmartCampus dashboard rather than hardcoded here.</p>

                <h3>Enrollment Categories</h3>
                <div class="enroll-cards">
                    <div class="ecard">
                        <h4>New Student</h4>
                        <p>Enrolling at Batu-Batu for the first time.</p>
                    </div>
                    <div class="ecard">
                        <h4>Returning Student</h4>
                        <p>Existing Batu-Batu learners continuing to the next grade.</p>
                    </div>
                    <div class="ecard">
                        <h4>Transferee</h4>
                        <p>Transferring from another school.</p>
                    </div>
                    <div class="ecard">
                        <h4>Balik-Aral / Returning Learner</h4>
                        <p>Returning to formal schooling after an interruption, where applicable.</p>
                    </div>
                </div>
                <p class="config-note">Categories are configurable according to the school's actual policies.</p>

                <h3>Requirements</h3>
                <details class="req">
                    <summary>New Learner</summary>
                    <ul>
                        <li>Learner information</li>
                        <li>PSA / NSO birth certificate, where required</li>
                        <li>Previous school records, where applicable</li>
                        <li>Enrollment-related forms</li>
                        <li>Parent / guardian information</li>
                        <li>Supporting documents</li>
                    </ul>
                </details>
                <details class="req">
                    <summary>Transferee</summary>
                    <ul>
                        <li>Birth certificate</li>
                        <li>Previous school records</li>
                        <li>Form 137 / learner records, as applicable</li>
                        <li>Form 138 / report card, as applicable</li>
                        <li>Transfer credentials</li>
                        <li>Parent / guardian information</li>
                    </ul>
                </details>
                <details class="req">
                    <summary>Returning Learner</summary>
                    <ul>
                        <li>Existing learner information</li>
                        <li>Previous school records</li>
                        <li>Updated parent / guardian information</li>
                        <li>Required enrollment forms</li>
                    </ul>
                </details>
                <p class="config-note">Requirements are configurable per school year, grade level, and enrollment category from the dashboard.</p>
            </div>

            <div class="enroll-form-wrap" id="enroll-form">
                <h3>Start Your Enrollment</h3>
                <div class="ms-progress" id="msProgress">
                    <span class="ms-dot active" data-step="1">1</span>
                    <span class="ms-dot" data-step="2">2</span>
                    <span class="ms-dot" data-step="3">3</span>
                    <span class="ms-dot" data-step="4">4</span>
                    <span class="ms-dot" data-step="5">5</span>
                </div>

                <form id="enrollForm" novalidate>
                    <!-- Step 1: Learner -->
                    <div class="ms-step" data-step="1">
                        <h4>Learner Information</h4>
                        <div class="form-field"><label>Learner's Name</label><input name="lname" required></div>
                        <div class="form-field"><label>Birth Date</label><input type="date" name="bdate" required></div>
                        <div class="form-field"><label>Sex</label>
                            <select name="sex" required><option value=""></option><option>Male</option><option>Female</option></select></div>
                        <div class="form-field"><label>Birthplace</label><input name="bplace"></div>
                        <div class="form-field"><label>Address</label><input name="laddress" required></div>
                        <div class="form-field"><label>Grade Level</label>
                            <select name="grade" required>
                                <option value=""></option><option>Kindergarten</option><option>Grade 1</option><option>Grade 2</option><option>Grade 3</option><option>Grade 4</option><option>Grade 5</option><option>Grade 6</option><option>Grade 7</option><option>Grade 8</option><option>Grade 9</option><option>Grade 10</option><option>Grade 11</option><option>Grade 12</option>
                            </select></div>
                        <div class="form-field"><label>School Year</label><input name="sy" value="2026-2027" required></div>
                        <div class="form-field"><label>Enrollment Type</label>
                            <select name="etype" required>
                                <option value=""></option><option>New Student</option><option>Returning Student</option><option>Transferee</option><option>Balik-Aral / Returning Learner</option>
                            </select></div>
                    </div>

                    <!-- Step 2: Parent/Guardian -->
                    <div class="ms-step" data-step="2" hidden>
                        <h4>Parent / Guardian</h4>
                        <div class="form-field"><label>Parent/Guardian Name</label><input name="pname" required></div>
                        <div class="form-field"><label>Relationship</label><input name="prel"></div>
                        <div class="form-field"><label>Contact Number</label><input name="pcontact" required></div>
                        <div class="form-field"><label>Address</label><input name="paddress"></div>
                        <div class="form-field"><label>Email (optional)</label><input type="email" name="pemail"></div>
                    </div>

                    <!-- Step 3: Previous School -->
                    <div class="ms-step" data-step="3" hidden>
                        <h4>Previous School</h4>
                        <div class="form-field"><label>Previous School</label><input name="pschool"></div>
                        <div class="form-field"><label>School Address</label><input name="psaddress"></div>
                        <div class="form-field"><label>Last Grade Completed</label><input name="plastgrade"></div>
                        <div class="form-field"><label>School Year</label><input name="psy"></div>
                        <div class="form-field"><label>Learner Reference Number</label><input name="lref"></div>
                    </div>

                    <!-- Step 4: Documents -->
                    <div class="ms-step" data-step="4" hidden>
                        <h4>Documents</h4>
                        <p class="form-note" style="text-align:left;margin-top:0;">List the requirements you will submit. If online upload isn't possible, you may submit documents physically at the school.</p>
                        <div class="form-field"><label><input type="checkbox" name="doc_bc"> Birth Certificate</label></div>
                        <div class="form-field"><label><input type="checkbox" name="doc_rc"> Report Card</label></div>
                        <div class="form-field"><label><input type="checkbox" name="doc_tc"> Transfer Credentials</label></div>
                        <div class="form-field"><label><input type="checkbox" name="doc_other"> Other Required Documents</label></div>
                    </div>

                    <!-- Step 5: Review -->
                    <div class="ms-step" data-step="5" hidden>
                        <h4>Review Application</h4>
                        <div id="reviewBox" class="review-box"></div>
                    </div>

                    <div class="ms-nav">
                        <button type="button" class="btn btn-outline" id="msPrev" style="display:none;">Back</button>
                        <button type="button" class="btn btn-primary" id="msNext">Next</button>
                        <button type="submit" class="btn btn-primary" id="msSubmit" style="display:none;">Submit Application</button>
                        <button type="button" class="btn btn-outline" id="msSave" style="margin-left:auto;">Save &amp; Continue Later</button>
                    </div>
                    <p class="form-note">No payment is collected here. The school will contact you to complete enrollment.</p>
                </form>
                <div class="enroll-result" id="enrollResult"></div>
            </div>

            <div class="status-check" id="enroll-status">
                <h3 style="color:var(--white);margin-bottom:1rem;">Check Application Status</h3>
                <form id="statusForm" novalidate>
                    <div class="form-field">
                        <label for="sf_ref">Application Reference Number</label>
                        <input type="text" id="sf_ref" name="ref" placeholder="e.g. BATU-2026-001284" required>
                    </div>
                    <button type="submit" class="btn btn-outline">Check Status</button>
                </form>
                <p class="form-note" style="max-width:560px;margin:1rem auto 0;">Status lookup shows only your application stage. Student, parent, and document details remain inside the authenticated SmartCampus system and are not exposed here.</p>
                <div class="enroll-result" id="statusResult"></div>
            </div>

            <div class="faq">
                <h3 style="color:var(--white);border-left:4px solid var(--accent);padding-left:0.75rem;margin-bottom:1rem;">Frequently Asked Questions</h3>
                <details><summary>Who can enroll at Batu-Batu NIHS?</summary><p>Kindergarten, elementary, junior high (Grades 7&ndash;10), and senior high (Grades 11&ndash;12) learners, subject to the school's grade-level coverage for the school year.</p></details>
                <details><summary>What documents are required?</summary><p>Requirements vary by enrollment category (New Learner, Transferee, Returning Learner). See the Requirements section above for the typical documents. The school configures the exact list per school year.</p></details>
                <details><summary>Can I enroll without an internet connection?</summary><p>Yes. You can begin the application online and submit required documents physically at the school if connectivity is limited. Use "Save &amp; Continue Later" to keep your progress on this device.</p></details>
                <details><summary>Can I submit documents physically?</summary><p>Yes. If online upload isn't possible, bring the required documents to the school. The application records which documents you will submit.</p></details>
                <details><summary>How do I check my application?</summary><p>Use "Check Application Status" with the reference number you received (e.g. BATU-2026-001284). No full account is required.</p></details>
                <details><summary>What happens after I submit my application?</summary><p>The school reviews your information and documents, then verifies and approves. You can track each stage via your reference number.</p></details>
                <details><summary>Can I edit my application after submission?</summary><p>Contact the school with your reference number to request changes; the school can update records on your behalf.</p></details>
                <details><summary>How do transferees enroll?</summary><p>Choose "Transferee" as the enrollment type and provide previous-school records (Form 137/138, transfer credentials) in the Requirements section.</p></details>
                <details><summary>Where can I get assistance?</summary><p>Visit <?php echo htmlspecialchars($school_name); ?> in Batu-Batu, Panglima Sugala, Tawi-Tawi, or use the Contact section below.</p></details>
            </div>

            <div class="offline-enroll">
                <h3>Enrollment for Intermittent Connectivity</h3>
                <p style="text-align:center;color:var(--gray-300);">Designed for Turtle Islands / Tawi-Tawi, where connections can be slow or unreliable.</p>
                <div class="offline-flow">
                    <span class="step">Start Application</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step">Save &amp; Continue</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step">Submit When Connected</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step">Need Offline Assistance?</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step">School Enrollment Office</span>
                </div>
                <div class="need">
                    <p style="color:var(--gray-300);margin-bottom:1rem;">You can begin the form online and finish later &mdash; your progress is saved on this device. If you cannot get online, visit the school enrollment office and bring your documents; personnel can assist you in person.</p>
                    <a href="#contact" class="btn btn-outline">Visit the School Office</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Campus -->
    <section id="features" class="smart-campus">
        <div class="container">
            <h2 class="section-title">Smart Campus K12</h2>
            <p class="section-subtitle">Digital school services for students, teachers, parents, and administrators</p>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="icon">📋</div>
                    <div class="name">Student Information</div>
                </div>
                <div class="feature-item">
                    <div class="icon">📝</div>
                    <div class="name">Enrollment</div>
                </div>
                <div class="feature-item">
                    <div class="icon">✅</div>
                    <div class="name">Attendance</div>
                </div>
                <div class="feature-item">
                    <div class="icon">📊</div>
                    <div class="name">Grades</div>
                </div>
                <div class="feature-item">
                    <div class="icon">📅</div>
                    <div class="name">Class Schedules</div>
                </div>
                <div class="feature-item">
                    <div class="icon">📢</div>
                    <div class="name">Announcements</div>
                </div>
                <div class="feature-item">
                    <div class="icon">📚</div>
                    <div class="name">Library</div>
                </div>
                <div class="feature-item">
                    <div class="icon">👨‍👩‍👧</div>
                    <div class="name">Parent Communication</div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="login.php" class="btn btn-primary">Enter Smart Campus</a>
            </div>
        </div>
    </section>

    <!-- Community Photo Section -->
    <section class="photo-section community-section">
        <div class="photo-content">
            <h2>In Our Community</h2>
            <p>From the sea to the classroom, our community is shaped by knowledge, work, and cooperation.</p>
        </div>
    </section>

    <!-- Island Identity -->
    <section class="island">
        <div class="container">
            <div class="island-content">
                <h2 class="section-title">In Tawi-Tawi</h2>
                <p>Our island communities carry generations of maritime knowledge, tradition, and cultural heritage. The sea shapes how we see the world.</p>
                <div class="identity-tags">
                    <span class="identity-tag">Sea</span>
                    <span class="identity-tag">Islands</span>
                    <span class="identity-tag">Community</span>
                    <span class="identity-tag">Culture</span>
                    <span class="identity-tag">Education</span>
                    <span class="identity-tag">Resilience</span>
                    <span class="identity-tag">Future</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Campus Life & Community Gallery -->
    <section id="gallery" class="gallery">
        <div class="container">
            <h2 class="section-title">Campus Life &amp; Community Gallery</h2>
            <p class="section-subtitle">Real moments from Batu-Batu National High School and the island communities of Tawi-Tawi.</p>

            <div class="cg-stage" id="cgStage">
                <img class="active" src="assets/images/bbnihs-staff.jpeg" alt="Batu-Batu National High School teachers and staff group photo"
                     data-title="Our Faculty &amp; Staff"
                     data-desc="Teachers and school personnel of Batu-Batu National High School in Panglima Sugala, Tawi-Tawi, posed in front of the school building.">
                <img src="assets/images/bbnihs-baccalaureate.jpeg" alt="Batu-Batu Integrated High School Joint Baccalaureate Service Recognition Day"
                     data-title="Baccalaureate Recognition Day"
                     data-desc="Students and faculty of Batu-Batu Integrated High School during their Joint Baccalaureate Service Recognition Day, celebrating learner achievement.">
                <img src="assets/images/bbnihs-graduation.jpeg" alt="Batu Integrated High School 52nd Graduation Exercises"
                     data-title="52nd Graduation Exercises"
                     data-desc="The graduating class, faculty, and staff of Batu Integrated High School on stage for the 52nd Graduation Exercises.">
                <img src="assets/images/bbnihs-legacy.jpg" alt="Batu-Batu National High School named NMYL Legacy Program Site"
                     data-title="Legacy Program Site"
                     data-desc="Batu-Batu National High School in Panglima Sugala, Tawi-Tawi, recognized as a Legacy Program Site by the National Movement of Young Legislators (February 4, 2025).">
                <img src="assets/images/bbnihs-scholarship.jpeg" alt="Bangsamoro Scholarship Program induction in Tawi-Tawi"
                     data-title="Scholarship &amp; Training Induction"
                     data-desc="Participants in a Training Induction Program for the Bangsamoro Scholarship Program for Technical-Vocational Education, Tawi-Tawi &mdash; expanding access to learning beyond the classroom.">
                <img src="assets/images/classroom1.jpeg" alt="A teacher leading a classroom lesson"
                     data-title="In the Classroom"
                     data-desc="A teacher guides young learners through a lesson at their desks &mdash; the daily heart of basic education.">
                <img src="assets/images/tawi-bajau-children.jpeg" alt="Children on a coastal stilt-village walkway"
                     data-title="Island Community Life"
                     data-desc="Children cross a bamboo walkway over the sea in a Tawi-Tawi coastal community &mdash; the everyday reality of island living.">
                <img src="assets/images/tawi-bongao.jpg" alt="Bongao coastal stilt village with mountain backdrop"
                     data-title="Our Region: Tawi-Tawi"
                     data-desc="Stilt homes and fish farms along the coastline beneath Tawi-Tawi's rugged mountains &mdash; the province Batu-Batu calls home.">

                <button class="cg-arrow cg-prev" aria-label="Previous photo">&#10094;</button>
                <button class="cg-arrow cg-next" aria-label="Next photo">&#10095;</button>
            </div>

            <div class="cg-caption" id="cgCaption">
                <h4 id="cgTitle"></h4>
                <p id="cgDesc"></p>
            </div>

            <div class="cg-controls">
                <button class="cg-text" id="cgPrev">&#8249; Previous</button>
                <div class="cg-dots" id="cgDots"></div>
                <button class="cg-text" id="cgNext">Next &#8250;</button>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Contact</h2>
            <p class="section-subtitle">Connect with the Batu-Batu school community</p>

            <div class="contact-grid">
                <!-- School Information (official DepEd reference) -->
                <div class="contact-card">
                    <h4>School Information</h4>
                    <p><strong><?php echo htmlspecialchars($school_name); ?></strong><br>School ID: <?php echo htmlspecialchars($school_id); ?></p>
                    <p><?php echo htmlspecialchars($school_address); ?><br><?php echo $school_classification; ?></p>
                    <p class="contact-source">Source: DepEd School Masterlist (National Inventory Dashboard).</p>
                </div>

                <!-- Where We Are -->
                <div class="contact-card">
                    <h4>Where We Are</h4>
                    <p>Batu-Batu, Poblacion<br>Panglima Sugala<br>Tawi-Tawi<br>BARMM, Philippines</p>
                    <a class="btn btn-outline" href="<?php echo htmlspecialchars($school_maps); ?>" target="_blank" rel="noopener">Open in Google Maps</a>
                </div>

                <!-- SmartCampus Support (project / developer) -->
                <div class="contact-card">
                    <h4>SmartCampus Support</h4>
                    <p>Need help with the SmartCampus portal?<br><br>
                    <strong>Kerr Fairtex</strong></p>
                    <p>
                        <a href="tel:<?php echo htmlspecialchars($project_phone); ?>"><?php echo htmlspecialchars($project_phone); ?></a><br>
                        <a href="mailto:<?php echo htmlspecialchars($project_email); ?>"><?php echo htmlspecialchars($project_email); ?></a><br>
                        <a href="<?php echo htmlspecialchars($project_facebook); ?>" target="_blank" rel="noopener">Facebook &mdash; Kerr Fairtex</a>
                    </p>
                    <p class="contact-source">Support topics: login, enrollment application, application status, website/portal errors, account assistance, technical feedback.</p>
                </div>

                <!-- DepEd Division -->
                <div class="contact-card">
                    <h4>DepEd &mdash; Tawi-Tawi Division</h4>
                    <p>Schools Division Office &ndash; Tawi-Tawi<br>Department of Education<br>Bongao, Tawi-Tawi</p>
                    <p>Telephone: <a href="tel:<?php echo htmlspecialchars($deped_division_phone); ?>"><?php echo htmlspecialchars($deped_division_phone); ?></a></p>
                    <p class="contact-source">Source: DepEd Regional &amp; Division Offices Directory.</p>
                </div>
            </div>

            <!-- Contact form (SmartCampus Team) -->
            <div class="contact-form-wrap">
                <h3>Contact the SmartCampus Team</h3>
                <form id="contactForm" novalidate>
                    <div class="form-field"><label>Full Name *</label><input name="name" required></div>
                    <div class="form-field"><label>Email Address *</label><input type="email" name="email" required></div>
                    <div class="form-field"><label>Mobile Number</label><input name="mobile"></div>
                    <div class="form-field">
                        <label>Concern *</label>
                        <select name="concern" required>
                            <option value=""></option>
                            <option>Enrollment</option>
                            <option>SmartCampus</option>
                            <option>Website</option>
                            <option>Technical Support</option>
                            <option>School Information</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-field"><label>Message *</label><textarea name="message" rows="4" required></textarea></div>
                    <button type="submit" class="btn btn-primary">Submit Message</button>
                </form>
                <div class="enroll-result" id="contactResult"></div>
                <p class="contact-source">For official school records, enrollment decisions, learner records, and other school administrative matters, please contact the school or appropriate DepEd office. This form reaches the SmartCampus project team (technology/website support), not the school administration.</p>
            </div>

            <!-- Who should I contact? -->
            <div class="contact-who">
                <h3>Who Should I Contact?</h3>
                <table class="who-table">
                    <thead><tr><th>Concern</th><th>Contact</th></tr></thead>
                    <tbody>
                        <tr><td>Enrollment</td><td>School / designated enrollment personnel</td></tr>
                        <tr><td>Student records</td><td>School registrar</td></tr>
                        <tr><td>Academic concerns</td><td>School / teacher</td></tr>
                        <tr><td>Parent concerns</td><td>School administration</td></tr>
                        <tr><td>SmartCampus technical issue</td><td>Kerr Fairtex (SmartCampus team)</td></tr>
                        <tr><td>Website issue</td><td>Kerr Fairtex (SmartCampus team)</td></tr>
                        <tr><td>Application system issue</td><td>SmartCampus support</td></tr>
                        <tr><td>DepEd concern</td><td>Tawi-Tawi Schools Division Office</td></tr>
                        <tr><td>Learner protection concern</td><td>School / DepEd appropriate channel</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Need Assistance -->
    <section class="assistance">
        <div class="container">
            <h2>Need Help With Enrollment?</h2>
            <p class="desc">Our school personnel can assist you with the enrollment process.</p>
            <div class="actions">
                <a href="#contact" class="btn btn-primary">Contact the School</a>
            </div>

            <div class="assist-office">
                <h4>Enrollment Assistance</h4>
                <p>School Office<br><?php echo htmlspecialchars($school_address); ?></p>
            </div>

            <p class="assist-note" style="margin-bottom:1rem;">For enrollment help, visit the school office or use the contact channels below. Official enrollment decisions and learner records are handled by the school and DepEd.</p>

            <div class="assist-office" style="background:rgba(255,255,255,0.06);">
                <h4>SmartCampus Project Support</h4>
                <p style="margin-bottom:0.75rem;">Technology &amp; website help (not the school's official line)</p>
                <div class="assist-buttons">
                    <a href="tel:<?php echo htmlspecialchars($project_phone); ?>">Call</a>
                    <a href="sms:<?php echo htmlspecialchars($project_phone); ?>">Message</a>
                    <a href="<?php echo htmlspecialchars($project_facebook); ?>" target="_blank" rel="noopener">Facebook</a>
                    <a href="<?php echo htmlspecialchars($school_maps); ?>" target="_blank" rel="noopener">Get Directions</a>
                </div>
                <p class="assist-note">SmartCampus K&ndash;12 developer contact: Kerr Fairtex. For school administrative matters, contact the school or DepEd.</p>
            </div>

            <p class="assist-note">Don't rely exclusively on email. A phone / SMS / in-person visit is more practical for many island residents.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h4>BATU-BATU</h4>
                <p>National High School</p>
                <p class="footer-muted">School ID: 305053<br>Batu-Batu, Panglima Sugala, Tawi-Tawi<br>BARMM, Philippines</p>
            </div>
            <div class="footer-col">
                <h4>SMARTCAMPUS K&ndash;12</h4>
                <p class="footer-muted">Digital school services for learning, administration, communication, and community engagement.</p>
            </div>
            <div class="footer-col">
                <h4>CONTACT</h4>
                <p>Kerr Fairtex<br><span class="footer-muted">SmartCampus K&ndash;12</span></p>
                <p><a href="tel:<?php echo htmlspecialchars($project_phone); ?>"><?php echo htmlspecialchars($project_phone); ?></a><br>
                   <a href="mailto:<?php echo htmlspecialchars($project_email); ?>"><?php echo htmlspecialchars($project_email); ?></a><br>
                   <a href="<?php echo htmlspecialchars($project_facebook); ?>" target="_blank" rel="noopener">Facebook</a></p>
            </div>
            <div class="footer-col">
                <h4>QUICK LINKS</h4>
                <p><a href="#about">About</a></p>
                <p><a href="#admissions">Academics</a></p>
                <p><a href="#enroll">Admissions</a></p>
                <p><a href="login.php">SmartCampus Portal</a></p>
                <p><a href="#contact">Contact</a></p>
                <p><a href="privacy.php">Privacy</a></p>
                <p><a href="privacy.php#accessibility">Accessibility</a></p>
            </div>
            <div class="footer-col">
                <h4>INSTITUTIONAL REFERENCE</h4>
                <p class="footer-muted">Department of Education<br>Schools Division of Tawi-Tawi<br>BARMM</p>
            </div>
        </div>
        <p class="footer-copy">&copy; 2026 SmartCampus K&ndash;12</p>
    </footer>

    <script>
    (function () {
        var slider = document.getElementById('aboutSlider');
        if (!slider) return;
        var slides = slider.querySelectorAll('.about-slides img');
        var dots = slider.querySelectorAll('.about-dots .dot');
        var idx = 0, timer = null;
        var DELAY = 3000; // change every 3 seconds

        function show(n) {
            idx = (n + slides.length) % slides.length;
            slides.forEach(function (s, i) { s.classList.toggle('active', i === idx); });
            dots.forEach(function (d, i) { d.classList.toggle('active', i === idx); });
        }
        function next() { show(idx + 1); }
        function prev() { show(idx - 1); }
        function start() { stop(); timer = setInterval(next, DELAY); }
        function stop() { if (timer) { clearInterval(timer); timer = null; } }

        slider.querySelector('.next').addEventListener('click', function () { next(); start(); });
        slider.querySelector('.prev').addEventListener('click', function () { prev(); start(); });
        dots.forEach(function (d) {
            d.addEventListener('click', function () { show(parseInt(d.getAttribute('data-i'), 10)); start(); });
        });
        // Pause auto-play when hovering the slider
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);

        start();
    })();

    // Enrollment Portal — backed by enroll_api.php (Supabase via app DB connection)
    (function () {
        var form = document.getElementById('enrollForm');
        if (!form) return;
        var step = 1, TOTAL = 5;
        var dots = document.querySelectorAll('#msProgress .ms-dot');
        var steps = form.querySelectorAll('.ms-step');
        var prevBtn = document.getElementById('msPrev');
        var nextBtn = document.getElementById('msNext');
        var submitBtn = document.getElementById('msSubmit');
        var saveBtn = document.getElementById('msSave');
        var res = document.getElementById('enrollResult');

        var STAGES = ['Submitted', 'Under Review', 'Documents Needed', 'Verified', 'Approved', 'Enrolled', 'Rejected'];

        function showStep(n) {
            step = n;
            steps.forEach(function (s) {
                s.hidden = (parseInt(s.getAttribute('data-step'), 10) !== n);
            });
            dots.forEach(function (d) {
                d.classList.toggle('active', parseInt(d.getAttribute('data-step'), 10) <= n);
            });
            prevBtn.style.display = n > 1 ? '' : 'none';
            nextBtn.style.display = n < TOTAL ? '' : 'none';
            submitBtn.style.display = n === TOTAL ? '' : 'none';
            if (n === TOTAL) buildReview();
        }

        function buildReview() {
            var f = form;
            var rows = [
                ['Learner', val(f.lname) + ' (' + val(f.sex) + ')'],
                ['Birth Date', val(f.bdate)],
                ['Birthplace', val(f.bplace)],
                ['Address', val(f.laddress)],
                ['Grade Level', val(f.grade)],
                ['School Year', val(f.sy)],
                ['Enrollment Type', val(f.etype)],
                ['Parent/Guardian', val(f.pname) + ' (' + val(f.prel) + ')'],
                ['Contact', val(f.pcontact)],
                ['Previous School', val(f.pschool) + ' / ' + val(f.plastgrade)]
            ];
            var docs = [];
            if (f.doc_bc.checked) docs.push('Birth Certificate');
            if (f.doc_rc.checked) docs.push('Report Card');
            if (f.doc_tc.checked) docs.push('Transfer Credentials');
            if (f.doc_other.checked) docs.push('Other');
            var html = rows.map(function (r) {
                return '<div><strong>' + r[0] + ':</strong> ' + (r[1] || '<em>—</em>') + '</div>';
            }).join('');
            html += '<div><strong>Documents:</strong> ' + (docs.length ? docs.join(', ') : '<em>none selected</em>') + '</div>';
            document.getElementById('reviewBox').innerHTML = html;
        }

        function val(el) { return el ? (el.value || '') : ''; }

        function validateStep(n) {
            var s = form.querySelector('.ms-step[data-step="' + n + '"]');
            var ok = true;
            s.querySelectorAll('[required]').forEach(function (inp) {
                if (!inp.value.trim()) { ok = false; inp.style.borderColor = '#ef4444'; }
                else { inp.style.borderColor = ''; }
            });
            return ok;
        }

        function gather() {
            var data = {};
            new FormData(form).forEach(function (v, k) { data[k] = v; });
            data.documents = {
                bc: form.doc_bc.checked, rc: form.doc_rc.checked,
                tc: form.doc_tc.checked, other: form.doc_other.checked
            };
            return data;
        }

        nextBtn.addEventListener('click', function () {
            if (!validateStep(step)) { res.textContent = 'Please complete the required fields.'; res.classList.add('show'); return; }
            res.classList.remove('show');
            if (step < TOTAL) showStep(step + 1);
        });
        prevBtn.addEventListener('click', function () { showStep(step - 1); });

        saveBtn.addEventListener('click', function () {
            localStorage.setItem('bbnihs_draft', JSON.stringify(gather()));
            res.textContent = 'Progress saved on this device. Return and continue later.';
            res.classList.add('show');
        });
        // restore draft
        try {
            var d = JSON.parse(localStorage.getItem('bbnihs_draft') || 'null');
            if (d) {
                Object.keys(d).forEach(function (k) {
                    if (k === 'documents') return;
                    if (form[k]) { if (form[k].type === 'checkbox') form[k].checked = true; else form[k].value = d[k]; }
                });
                if (d.documents) {
                    form.doc_bc.checked = !!d.documents.bc; form.doc_rc.checked = !!d.documents.rc;
                    form.doc_tc.checked = !!d.documents.tc; form.doc_other.checked = !!d.documents.other;
                }
            }
        } catch (e) {}

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateStep(step)) return;
            res.textContent = 'Submitting…';
            res.classList.add('show');
            fetch('enroll_api.php?action=submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(gather())
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (j.error) { res.textContent = 'Error: ' + j.error; return; }
                localStorage.removeItem('bbnihs_draft');
                res.innerHTML = 'APPLICATION SUBMITTED<br>Reference: <strong>' + j.ref + '</strong><br>Status: ' + j.status +
                    '. Keep this reference to check your status. The school will review and contact you.';
                form.reset();
                showStep(1);
            }).catch(function () { res.textContent = 'Network error. Please try again or submit documents physically at the school.'; });
        });

        // --- Load enrollment config (dates/status from DB) ---
        fetch('enroll_api.php?action=config').then(function (r) { return r.json(); }).then(function (j) {
            if (!j.period) return;
            var p = j.period;
            var fmt = function (d) { return d ? new Date(d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'; };
            var opens = fmt(p.enrollment_opens), closes = fmt(p.enrollment_closes), begins = fmt(p.classes_begin);
            document.getElementById('dOpens').textContent = opens;
            document.getElementById('dPeriod').textContent = (opens && closes) ? (opens + ' – ' + closes) : '—';
            document.getElementById('dClasses').textContent = begins;
        }).catch(function () {});

        // --- Status check (uses API + full 7-stage pipeline) ---
        var sform = document.getElementById('statusForm');
        sform.addEventListener('submit', function (e) {
            e.preventDefault();
            var ref = document.getElementById('sf_ref').value.trim().toUpperCase();
            var sres = document.getElementById('statusResult');
            sres.textContent = 'Checking…';
            sres.classList.add('show');
            fetch('enroll_api.php?action=status&ref=' + encodeURIComponent(ref)).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.found) {
                    sres.innerHTML = 'No application found for reference <strong>' + ref + '</strong>. Ensure it is entered exactly (e.g. BATU-2026-001284).';
                    return;
                }
                var status = j.application.status || 'Submitted';
                var idx = STAGES.indexOf(status);
                if (idx < 0) idx = 0;
                var pipe = STAGES.map(function (st, i) {
                    var cls = i < idx ? 'done' : (i === idx ? 'current' : '');
                    return '<div class="pstep ' + cls + '"><span class="bullet"></span>' + st + '</div>';
                }).join('');
                sres.innerHTML = 'Reference <strong>' + j.application.ref + '</strong><br>Applicant: ' + (j.application.learner_name || '—') +
                    '<div class="pipeline">' + pipe + '</div>';
            }).catch(function () { sres.textContent = 'Network error. Please try again.'; });
        });

        showStep(1);
    })();

    // Contact form -> contact_api.php (SmartCampus project team)
    (function () {
        var form = document.getElementById('contactForm');
        if (!form) return;
        var res = document.getElementById('contactResult');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var data = {};
            new FormData(form).forEach(function (v, k) { data[k] = v; });
            res.textContent = 'Sending…';
            res.classList.add('show');
            fetch('contact_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (j.error) { res.textContent = 'Error: ' + j.error; return; }
                res.textContent = j.message || 'Message received.';
                form.reset();
            }).catch(function () { res.textContent = 'Network error. Please try again or use the contact details below.'; });
        });
    })();

    // Editorial Campus Life & Community Gallery (single photo + caption panel)
    (function () {
        var stage = document.getElementById('cgStage');
        if (!stage) return;
        var imgs = Array.prototype.slice.call(stage.querySelectorAll('img'));
        var titleEl = document.getElementById('cgTitle');
        var descEl = document.getElementById('cgDesc');
        var dotsWrap = document.getElementById('cgDots');
        if (!imgs.length || !titleEl) return;

        var idx = 0, timer = null, DELAY = 3000;
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Build dots
        imgs.forEach(function (_, i) {
            var d = document.createElement('span');
            d.className = 'dot' + (i === 0 ? ' active' : '');
            d.addEventListener('click', function () { show(i); start(); });
            dotsWrap.appendChild(d);
        });
        var dots = Array.prototype.slice.call(dotsWrap.children);

        function preload(i) {
            var n = (i + 1) % imgs.length;
            if (imgs[n] && imgs[n].getAttribute('src')) { var p = new Image(); p.src = imgs[n].getAttribute('src'); }
        }

        function show(n) {
            idx = (n + imgs.length) % imgs.length;
            imgs.forEach(function (im, i) { im.classList.toggle('active', i === idx); });
            dots.forEach(function (d, i) { d.classList.toggle('active', i === idx); });
            titleEl.textContent = imgs[idx].getAttribute('data-title') || '';
            descEl.innerHTML = imgs[idx].getAttribute('data-desc') || '';
            preload(idx);
        }
        function next() { show(idx + 1); }
        function prev() { show(idx - 1); }
        function start() { stop(); if (!reduceMotion) timer = setInterval(next, DELAY); }
        function stop() { if (timer) { clearInterval(timer); timer = null; } }

        stage.querySelector('.cg-next').addEventListener('click', function () { next(); start(); });
        stage.querySelector('.cg-prev').addEventListener('click', function () { prev(); start(); });
        var pBtn = document.getElementById('cgPrev'), nBtn = document.getElementById('cgNext');
        if (pBtn) pBtn.addEventListener('click', function () { prev(); start(); });
        if (nBtn) nBtn.addEventListener('click', function () { next(); start(); });
        stage.addEventListener('mouseenter', stop);
        stage.addEventListener('mouseleave', start);

        // Touch swipe
        var sx = null;
        stage.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; stop(); }, { passive: true });
        stage.addEventListener('touchend', function (e) {
            if (sx === null) return;
            var dx = e.changedTouches[0].clientX - sx;
            if (Math.abs(dx) > 40) { if (dx < 0) next(); else prev(); }
            sx = null; start();
        }, { passive: true });

        show(0);
        start();
    })();

    // Generic image crossfade cycler (e.g. Classroom gallery card), every data-cycle ms
    (function () {
        var cycles = document.querySelectorAll('.img-cycle');
        cycles.forEach(function (box) {
            var imgs = box.querySelectorAll('img');
            if (imgs.length < 2) return;
            var delay = parseInt(box.getAttribute('data-cycle'), 10) || 3000;
            var i = 0;
            setInterval(function () {
                imgs[i].classList.remove('active');
                i = (i + 1) % imgs.length;
                imgs[i].classList.add('active');
            }, delay);
        });
    })();
    </script>

</body>
</html>
