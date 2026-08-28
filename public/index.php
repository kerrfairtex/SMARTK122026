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
        .feature-item .module-dot {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 6px rgba(74,222,128,0.5);
        }
        .feature-item.off .module-dot {
            background: #6b7280;
            box-shadow: none;
        }
        .feature-item { position: relative; }

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
        .values-intro {
            color: var(--gray-300);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.25rem;
            max-width: 60ch;
        }
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

        /* ===== Tier 1 PR-2: Cmd/K search (non-destructive) ===== */

        /* Search dialog */
        dialog#searchDialog {
            padding: 0;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            background: var(--navy);
            color: var(--gray-100);
            max-width: 600px;
            width: calc(100% - 2rem);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        dialog#searchDialog::backdrop {
            background: rgba(0,0,0,0.65);
        }
        .search-wrap { padding: 0; }
        .search-input {
            width: 100%;
            background: transparent;
            border: 0;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            color: var(--white);
            font: inherit;
            font-size: 1.1rem;
            padding: 1rem 1.25rem;
            outline: none;
        }
        .search-input::placeholder { color: var(--gray-500); }
        .search-results {
            list-style: none;
            margin: 0;
            padding: 0.5rem 0;
            max-height: 60vh;
            overflow-y: auto;
        }
        .search-results li {
            padding: 0.65rem 1.25rem;
            cursor: pointer;
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
        }
        .search-results li[aria-selected="true"],
        .search-results li:hover { background: var(--navy-light); }
        .search-results .type {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.15rem 0.45rem;
            border-radius: 3px;
            background: rgba(255,255,255,0.08);
            color: var(--gray-300);
            flex: none;
        }
        .search-results .type.faq     { background: rgba(74,222,128,0.15); color: #4ade80; }
        .search-results .type.section { background: rgba(42,155,208,0.15); color: var(--accent); }
        .search-results .type.action  { background: rgba(234,179,8,0.15); color: #fbbf24; }
        .search-results .title { font-weight: 600; }
        .search-results .empty {
            padding: 1.25rem;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        .search-results .hint {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            color: var(--gray-500);
            font-size: 0.75rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .search-results .hint kbd {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 3px;
            padding: 0.1rem 0.4rem;
            font-family: ui-monospace, monospace;
            font-size: 0.7rem;
            color: var(--gray-300);
        }
        .a11y-bar .search-launch {
            margin-left: auto;
            padding-left: 0.75rem;
            border-left: 1px solid rgba(255,255,255,0.15);
            color: var(--gray-500);
        }
        .a11y-bar .search-launch kbd {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 3px;
            padding: 0.1rem 0.4rem;
            font-family: ui-monospace, monospace;
            font-size: 0.7rem;
            color: var(--gray-300);
            margin-left: 0.4rem;
        }

        /* ===== PR-3: Floating Viber/WhatsApp button ===== */
        .float-contact {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 50;
            display: flex;
            flex-direction: column-reverse;
            align-items: flex-end;
            gap: 0.6rem;
        }
        .float-contact .fc-launch {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--accent);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(0,0,0,0.35);
            cursor: pointer;
            border: 0;
            font-size: 1.5rem;
            transition: transform 0.15s ease, background 0.15s ease;
        }
        .float-contact .fc-launch:hover { background: var(--accent-hover); transform: scale(1.05); }
        .float-contact .fc-launch:focus-visible { outline: 2px solid #fbbf24; outline-offset: 3px; }
        .float-contact .fc-pills {
            display: none;
            flex-direction: column;
            gap: 0.4rem;
            align-items: flex-end;
        }
        .float-contact.open .fc-pills { display: flex; }
        .float-contact .fc-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            color: var(--white);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            white-space: nowrap;
        }
        .float-contact .fc-pill.viber   { background: #7360F2; }
        .float-contact .fc-pill.viber:hover   { background: #5a48d1; }
        .float-contact .fc-pill.whatsapp { background: #25D366; }
        .float-contact .fc-pill.whatsapp:hover { background: #1ebd57; }
        .float-contact .fc-pill svg { width: 18px; height: 18px; flex: none; }
        @media (prefers-reduced-motion: reduce) {
            .float-contact .fc-launch { transition: none; }
        }
        @media print {
            .float-contact { display: none !important; }
        }
        @media (max-width: 480px) {
            .float-contact .fc-pill { font-size: 0.8rem; padding: 0.5rem 0.7rem; }
        }

        /* ===== PR-3: OSM iframe ===== */
        .osm-wrap {
            margin: 1.25rem 0 0;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            background: var(--navy);
        }
        .osm-wrap iframe {
            display: block;
            width: 100%;
            height: 280px;
            border: 0;
        }
        .osm-caption {
            font-size: 0.8rem;
            color: var(--gray-500);
            text-align: right;
            padding: 0.4rem 0.6rem;
            background: var(--navy-light);
        }
        .osm-caption a { color: var(--gray-300); }

        /* ===== Spec §0: Motion tokens (shared across all sections) ===== */
        :root {
            --dur-micro: 120ms;
            --dur-hover: 180ms;
            --dur-base: 300ms;
            --dur-reveal: 500ms;
            --dur-hero: 900ms;
            --ease-standard: cubic-bezier(.4, 0, .2, 1);
            --ease-out: cubic-bezier(.2, 0, 0, 1);
            --ease-in: cubic-bezier(.4, 0, 1, 1);
            --stagger-step: 70ms;
        }
        @media (prefers-reduced-motion: reduce) {
            :root {
                --dur-micro: 0ms; --dur-hover: 0ms; --dur-base: 0ms;
                --dur-reveal: 0ms; --dur-hero: 0ms;
            }
        }
        html.motion-reduced, html.motion-reduced * {
            animation-duration: 0ms !important;
            transition-duration: 0ms !important;
        }
        /* Save-data gate: kill all custom-property transitions on low data. */
        html.save-data, html.save-data * {
            transition: none !important;
            animation: none !important;
        }

        /* ===== Spec §2a: Sticky nav backdrop + sliding active-link underline ===== */
        .nav-bar {
            transition: background-color var(--dur-base) var(--ease-standard),
                        backdrop-filter var(--dur-base) var(--ease-standard);
        }
        .nav-bar.scrolled {
            background: rgba(8, 24, 43, 0.92);
            backdrop-filter: blur(8px);
        }
        .nav-bar { position: relative; }
        .nav-bar a { position: relative; transition: color var(--dur-hover) var(--ease-standard); }
        .nav-bar a::after {
            content: '';
            position: absolute;
            left: 12px; right: 12px; bottom: 6px;
            height: 2px;
            background: #fbbf24;
            transform: scaleX(0);
            transform-origin: center;
            transition: transform var(--dur-base) var(--ease-standard);
        }
        .nav-bar a.active::after, .nav-bar a:hover::after, .nav-bar a:focus-visible::after {
            transform: scaleX(1);
        }

        /* ===== Spec §2c: Tawi-Tawi clock tick pulse on the seconds digit ===== */
        .school-clock .clock-tick { display: inline-block; transition: opacity var(--dur-micro) linear; }
        .school-clock.tick .clock-tick { opacity: 0.55; }
        @media (prefers-reduced-motion: reduce) {
            .school-clock .clock-tick { transition: none; }
        }

        /* ===== Spec §2d: FAB contextual pulse ===== */
        .float-contact .fc-launch {
            transition: transform var(--dur-hover) var(--ease-standard),
                        background var(--dur-hover) var(--ease-standard);
        }
        .float-contact.context-pulse .fc-launch {
            animation: fab-attention var(--dur-hover) var(--ease-standard) 1;
        }
        @keyframes fab-attention {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.08); }
            100% { transform: scale(1); }
        }

        /* ===== Spec §2b: Progress bar uses native scroll-timeline where possible ===== */
        .scroll-ribbon {
            transform-origin: left;
            transform: scaleX(var(--scroll-progress, 0));
            transition: width 80ms linear; /* legacy fallback */
        }
        @supports (animation-timeline: scroll()) {
            .scroll-ribbon {
                animation: progress-grow linear;
                animation-timeline: scroll(root);
                transition: none;
            }
            @keyframes progress-grow {
                from { transform: scaleX(0); }
                to   { transform: scaleX(1); }
            }
        }

        /* ===== Spec §3: Hero staggered load (one-per-session, fades+rises) ===== */
        @keyframes hero-rise {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .hero h1, .hero .tagline, .hero .location, .hero .hero-buttons, .hero .supporting {
            opacity: 0;
        }
        html.hero-armed .hero h1 {
            animation: hero-rise var(--dur-hero) var(--ease-out) 0ms forwards;
        }
        html.hero-armed .hero .tagline {
            animation: hero-rise var(--dur-hero) var(--ease-out) 100ms forwards;
        }
        html.hero-armed .hero .location {
            animation: hero-rise var(--dur-hero) var(--ease-out) 180ms forwards;
        }
        html.hero-armed .hero .hero-buttons {
            animation: hero-rise var(--dur-base) var(--ease-out) 260ms forwards;
        }
        html.hero-armed .hero .supporting {
            animation: hero-rise var(--dur-base) var(--ease-out) 360ms forwards;
        }
        @media (prefers-reduced-motion: reduce) {
            .hero h1, .hero .tagline, .hero .location, .hero .hero-buttons, .hero .supporting { opacity: 1; }
            html.hero-armed .hero h1, html.hero-armed .hero .tagline,
            html.hero-armed .hero .location, html.hero-armed .hero .hero-buttons,
            html.hero-armed .hero .supporting { animation: none; }
        }

        /* ===== Spec §4/§7c/§12: Shared card-stagger entrance ===== */
        .stagger-card {
            opacity: 0;
            transform: translateY(8px);
        }
        html.stagger-armed .stagger-card {
            animation: card-rise var(--dur-reveal) var(--ease-out) forwards;
        }
        html.stagger-armed .stagger-card:nth-child(1) { animation-delay: 0ms; }
        html.stagger-armed .stagger-card:nth-child(2) { animation-delay: calc(var(--stagger-step) * 1); }
        html.stagger-armed .stagger-card:nth-child(3) { animation-delay: calc(var(--stagger-step) * 2); }
        html.stagger-armed .stagger-card:nth-child(4) { animation-delay: calc(var(--stagger-step) * 3); }
        html.stagger-armed .stagger-card:nth-child(5) { animation-delay: calc(var(--stagger-step) * 4); }
        html.stagger-armed .stagger-card:nth-child(6) { animation-delay: calc(var(--stagger-step) * 5); }
        @keyframes card-rise {
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .stagger-card { opacity: 1; transform: none; }
            html.stagger-armed .stagger-card { animation: none; }
        }

        /* ===== Spec §5: Count-up target gets hidden until armed (rAF in JS) ===== */
        .countup { font-variant-numeric: tabular-nums; }
        .countup:not(.armed) { /* nothing special, but keeps a hook for SSR */ }

        /* ===== Spec §6, §18: Parallax via animation-timeline: view() ===== */
        .parallax-bg {
            background-attachment: fixed;
        }
        @supports (animation-timeline: view()) {
            .parallax-bg {
                animation: parallax-slide linear both;
                animation-timeline: view();
                animation-range: cover 0% cover 100%;
            }
            @keyframes parallax-slide {
                from { transform: translateY(0); }
                to   { transform: translateY(40%); }
            }
        }

        /* ===== Spec §7a: Carousel crossfade + active-dot scale ===== */
        .about-slider { position: relative; }
        .about-slider .slide {
            opacity: 0;
            transition: opacity var(--dur-base) var(--ease-standard);
            position: absolute; inset: 0;
            pointer-events: none;
        }
        .about-slider .slide.active {
            opacity: 1;
            position: relative;
            pointer-events: auto;
        }
        .about-dots .dot {
            transition: transform var(--dur-hover) var(--ease-standard),
                        opacity var(--dur-hover) var(--ease-standard);
        }
        .about-dots .dot.active {
            transform: scale(1.3);
        }
        @media (prefers-reduced-motion: reduce) {
            .about-slider .slide { transition: none; }
            .about-dots .dot { transition: none; }
        }

        /* ===== Spec §8: Timeline line draw via stroke-dashoffset ===== */
        .timeline-svg path.line {
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            transition: stroke-dashoffset 1ms linear;
        }
        .timeline-svg.armed path.line {
            transition: stroke-dashoffset 600ms linear;
            stroke-dashoffset: 0;
        }
        .timeline-svg .dot-fill { opacity: 0; transition: opacity var(--dur-base) var(--ease-standard); }
        .timeline-svg .dot-fill.armed { opacity: 1; }
        @media (prefers-reduced-motion: reduce) {
            .timeline-svg path.line, .timeline-svg .dot-fill { transition: none; }
            .timeline-svg.armed path.line { stroke-dashoffset: 0; }
        }

        /* ===== Spec §9: Grade buttons: tap/hover feedback only ===== */
        .grade-item, .grade-list .grade-item {
            transition: border-color var(--dur-hover) var(--ease-standard);
        }
        .grade-item:active { transform: scale(0.98); transition: transform var(--dur-micro) var(--ease-standard); }

        /* ===== Spec §10: OPEN badge breathing opacity (one allowed ambient loop) ===== */
        .status-badge.live {
            animation: badge-breathe 2.5s ease-in-out infinite;
        }
        @keyframes badge-breathe {
            0%, 100% { opacity: 1; }
            50%      { opacity: 0.85; }
        }
        @media (prefers-reduced-motion: reduce) {
            .status-badge.live { animation: none; }
        }

        /* ===== Spec §11: 5-step process stepper line fill + number→checkmark morph ===== */
        .ms-step { transition: background var(--dur-base) var(--ease-standard),
                              border-color var(--dur-base) var(--ease-standard),
                              color var(--dur-base) var(--ease-standard); }
        .ms-step .ms-num, .ms-step .ms-check { transition: opacity var(--dur-base) var(--ease-standard); }
        .ms-step.done .ms-num { opacity: 0; }
        .ms-step.done .ms-check { opacity: 1; }
        .ms-step.done { background: var(--accent); color: var(--navy); border-color: var(--accent); }
        .ms-progress { background: rgba(255,255,255,0.12); }
        .ms-progress .ms-bar {
            transition: width var(--dur-base) var(--ease-standard);
            background: var(--accent);
        }
        @media (prefers-reduced-motion: reduce) {
            .ms-step, .ms-step .ms-num, .ms-step .ms-check,
            .ms-progress .ms-bar { transition: none; }
            .status-badge.live { animation: none; }
        }

        /* ===== Spec §13: Accordions via grid-template-rows 0fr→1fr ===== */
        details.accordion {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows var(--dur-base) var(--ease-standard);
        }
        details.accordion[open] {
            grid-template-rows: 1fr;
        }
        details.accordion > .accordion-inner {
            overflow: hidden;
            min-height: 0;
            opacity: 0;
            transition: opacity 60% var(--ease-standard);
        }
        details.accordion[open] > .accordion-inner {
            opacity: 1;
        }
        details.accordion > summary {
            list-style: none;
            cursor: pointer;
            transition: color var(--dur-hover) var(--ease-standard);
        }
        details.accordion > summary::-webkit-details-marker { display: none; }
        details.accordion > summary::after {
            content: '+';
            display: inline-block;
            margin-left: 0.5rem;
            transition: transform var(--dur-base) var(--ease-standard);
            color: var(--accent);
            font-weight: 700;
        }
        details.accordion[open] > summary::after {
            transform: rotate(45deg);
        }
        @media (prefers-reduced-motion: reduce) {
            details.accordion, details.accordion > .accordion-inner,
            details.accordion > summary::after { transition: none; }
        }

        /* ===== Spec §14: Enrollment step slide + save-toast ===== */
        .ms-step-panel {
            transition: transform var(--dur-base) var(--ease-standard),
                        opacity var(--dur-base) var(--ease-standard);
        }
        .ms-step-panel.leaving-forward { transform: translateX(-100%); opacity: 0; }
        .ms-step-panel.leaving-back    { transform: translateX(100%);  opacity: 0; }
        .ms-step-panel.entering-forward { transform: translateX(100%);  opacity: 0; }
        .ms-step-panel.entering-back    { transform: translateX(-100%); opacity: 0; }
        .toast {
            position: fixed;
            left: 50%;
            bottom: 1.5rem;
            transform: translateX(-50%) translateY(120%);
            background: var(--accent);
            color: var(--navy);
            padding: 0.7rem 1.1rem;
            border-radius: 6px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.35);
            font-weight: 600;
            z-index: 60;
            transition: transform var(--dur-base) var(--ease-out);
            pointer-events: none;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }
        @media (prefers-reduced-motion: reduce) {
            .ms-step-panel, .toast { transition: none; }
        }

        /* ===== Spec §15: 3-dot pulse on Check Status + result slide-down ===== */
        .pulse-dots span {
            display: inline-block;
            animation: dot-pulse 1.2s ease-in-out infinite;
        }
        .pulse-dots span:nth-child(2) { animation-delay: 0.15s; }
        .pulse-dots span:nth-child(3) { animation-delay: 0.3s; }
        @keyframes dot-pulse {
            0%, 80%, 100% { opacity: 0.3; transform: scale(0.9); }
            40%           { opacity: 1;   transform: scale(1.1); }
        }
        .result-slide {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height var(--dur-base) var(--ease-standard),
                        opacity var(--dur-base) var(--ease-standard);
        }
        .result-slide.show {
            max-height: 600px;
            opacity: 1;
        }
        @media (prefers-reduced-motion: reduce) {
            .pulse-dots span { animation: none; }
            .result-slide, .result-slide.show { transition: none; max-height: none; }
        }

        /* ===== Spec §16: Offline-flow nodes + live online/offline badge ===== */
        .offline-flow-node {
            opacity: 0;
            transform: translateY(8px);
        }
        .offline-flow.armed .offline-flow-node {
            animation: card-rise var(--dur-reveal) var(--ease-out) forwards;
        }
        .offline-flow.armed .offline-flow-node:nth-child(1) { animation-delay: 0ms; }
        .offline-flow.armed .offline-flow-node:nth-child(2) { animation-delay: 100ms; }
        .offline-flow.armed .offline-flow-node:nth-child(3) { animation-delay: 200ms; }
        .offline-flow.armed .offline-flow-node:nth-child(4) { animation-delay: 300ms; }
        .offline-flow.armed .offline-flow-node:nth-child(5) { animation-delay: 400ms; }
        .offline-flow svg.connector { stroke-dasharray: 1000; stroke-dashoffset: 1000; }
        .offline-flow.armed svg.connector { animation: connector-draw 800ms linear forwards; }
        @keyframes connector-draw { to { stroke-dashoffset: 0; } }
        .conn-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.78rem;
            background: rgba(255,255,255,0.06);
            color: var(--gray-300);
            transition: background var(--dur-hover) var(--ease-standard),
                        color var(--dur-hover) var(--ease-standard);
        }
        .conn-badge .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #4ade80;
            transition: background var(--dur-hover) var(--ease-standard);
        }
        .conn-badge.offline { color: var(--gray-500); }
        .conn-badge.offline .dot { background: #6b7280; }
        @media (prefers-reduced-motion: reduce) {
            .offline-flow.armed .offline-flow-node,
            .offline-flow.armed svg.connector { animation: none; transform: none; opacity: 1; }
            .offline-flow svg.connector { stroke-dashoffset: 0; }
        }

        /* ===== Spec §19: Filter tab sliding underline (reuse §2a pattern) ===== */
        .filter-chips { position: relative; }
        .filter-chips .chip { position: relative; overflow: hidden; }
        .filter-chips .chip::after {
            content: '';
            position: absolute;
            left: 12px; right: 12px; bottom: 6px;
            height: 2px;
            background: var(--white);
            transform: scaleX(0);
            transform-origin: center;
            transition: transform var(--dur-base) var(--ease-standard);
        }
        .filter-chips .chip.active::after { transform: scaleX(1); }

        /* ===== Spec §20: Floating-label form + map gate + submit morph ===== */
        .form-field { position: relative; }
        .form-field label {
            position: absolute;
            left: 0.6rem;
            top: 0.7rem;
            color: var(--gray-500);
            pointer-events: none;
            transition: transform var(--dur-hover) var(--ease-standard),
                        color var(--dur-hover) var(--ease-standard);
            background: var(--navy);
            padding: 0 0.3rem;
        }
        .form-field input:focus + label,
        .form-field input:not(:placeholder-shown) + label,
        .form-field textarea:focus + label,
        .form-field textarea:not(:placeholder-shown) + label,
        .form-field select:focus + label {
            transform: translateY(-1.4rem);
            color: var(--accent);
            font-size: 0.78rem;
        }
        .form-field input, .form-field textarea, .form-field select {
            padding: 0.6rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.18);
            color: var(--white);
            border-radius: 4px;
            transition: border-color var(--dur-hover) var(--ease-standard),
                        box-shadow var(--dur-hover) var(--ease-standard);
            width: 100%;
        }
        .form-field input:focus, .form-field textarea:focus, .form-field select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent);
        }
        .form-field input::placeholder, .form-field textarea::placeholder { color: transparent; }
        .map-gate {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            background: var(--navy-light);
            border-radius: 6px;
            border: 1px dashed rgba(255,255,255,0.18);
            color: var(--gray-300);
            text-align: center;
            padding: 1.5rem;
            cursor: pointer;
            transition: border-color var(--dur-hover) var(--ease-standard),
                        background var(--dur-hover) var(--ease-standard);
        }
        .map-gate:hover, .map-gate:focus-visible {
            border-color: var(--accent);
            background: rgba(42,155,208,0.08);
        }
        .osm-wrap.armed { display: block; }
        .btn.submit-morph { transition: background var(--dur-base) var(--ease-standard), color var(--dur-base) var(--ease-standard); }
        .btn.submit-success { background: #22c55e; color: #fff; }
        @media (prefers-reduced-motion: reduce) {
            .form-field label, .form-field input, .form-field textarea, .form-field select,
            .filter-chips .chip::after, .map-gate, .btn.submit-morph { transition: none; }
        }

        /* ===== Spec §23: a11y toggle cross-fade via custom-property transitions ===== */
        html {
            transition: background-color var(--dur-base) var(--ease-standard),
                        color var(--dur-base) var(--ease-standard);
        }
        .a11y-bar button { transition: background var(--dur-hover) var(--ease-standard), border-color var(--dur-hover) var(--ease-standard), color var(--dur-hover) var(--ease-standard); }

        /* ===== Tier 1 PR-1: Interactive additions (non-destructive) ===== */

        /* Sub-scroll ribbon (top progress bar) */
        .scroll-ribbon {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, var(--accent), #4ade80);
            z-index: 1001;
            transition: width 80ms linear;
        }
        @media (prefers-reduced-motion: reduce) {
            .scroll-ribbon { transition: none; }
        }

        /* School clock (hero) */
        .school-clock {
            position: absolute;
            bottom: 1rem;
            right: 1.25rem;
            color: var(--gray-300);
            font-size: 0.85rem;
            font-variant-numeric: tabular-nums;
            background: rgba(8,24,43,0.55);
            padding: 0.35rem 0.7rem;
            border-radius: 4px;
            backdrop-filter: blur(4px);
            z-index: 2;
        }
        .school-clock .clock-label {
            color: var(--gray-500);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-right: 0.4rem;
        }

        /* Office hours badge */
        .office-hours {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: var(--gray-300);
        }
        .office-hours .oh-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 6px rgba(74,222,128,0.5);
        }
        .office-hours.closed .oh-dot { background: #6b7280; box-shadow: none; }

        /* Gallery filter chips */
        .filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0 1.5rem;
        }
        .filter-chips .chip {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--gray-300);
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-chips .chip:hover { border-color: var(--accent); color: var(--white); }
        .filter-chips .chip.active {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--white);
        }
        .cg-stage img.hidden { display: none !important; }

        /* Accessibility toggles bar */
        .a11y-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: var(--navy-light);
            border-top: 1px solid rgba(255,255,255,0.08);
            color: var(--gray-300);
            font-size: 0.85rem;
        }
        .a11y-bar .a11y-label {
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.7rem;
            margin-right: 0.5rem;
        }
        .a11y-bar button {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--gray-300);
            padding: 0.35rem 0.7rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.15s ease;
        }
        .a11y-bar button:hover { border-color: var(--accent); color: var(--white); }
        .a11y-bar button[aria-pressed="true"] {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--white);
        }
        /* High-contrast mode (AA → AAA palette) */
        html.a11y-hc {
            --navy-deep: #000000;
            --navy: #0a0a0a;
            --navy-light: #141414;
            --gray-100: #ffffff;
            --gray-300: #f5f5f5;
            --gray-500: #d4d4d4;
            --accent: #ffd400;
            --accent-hover: #ffe34d;
        }
        /* OpenDyslexic (lazy-loaded only when toggled) */
        @font-face {
            font-family: 'OpenDyslexicLocal';
            src: local('OpenDyslexic'), local('OpenDyslexic-Regular');
            font-display: swap;
        }
        html.a11y-dyslexic body,
        html.a11y-dyslexic button,
        html.a11y-dyslexic input,
        html.a11y-dyslexic select,
        html.a11y-dyslexic textarea {
            font-family: 'OpenDyslexicLocal', 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: 0.02em;
            line-height: 1.75;
        }
        /* Large text */
        html.a11y-large { font-size: 115%; }

        /* Print stylesheet */
        @media print {
            .scroll-ribbon, .nav-bar, .a11y-bar, .hero-bg, .filter-chips,
            .cg-arrow, .cg-controls, .cg-dots, .about-dots, .about-slider .nav-btn,
            footer .footer-grid { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            .hero { min-height: auto !important; padding: 1.5rem !important; }
            .hero::after { display: none !important; }
            .hero h1, .hero .tagline, .hero .location, .hero .supporting { color: #000 !important; text-shadow: none !important; }
            .hero-buttons { display: none !important; }
            .school-clock { display: none !important; }
            section { padding: 1rem !important; background: #fff !important; }
            .glance-card, .stat-card, .academic-card, .ecard, .contact-card,
            .assist-office, .form-field, .enroll-status, .value-item,
            .grade-item, .feature-item, .req, .enroll-form-wrap, .contact-form-wrap {
                background: #fff !important; color: #000 !important;
                border: 1px solid #ccc !important; page-break-inside: avoid;
            }
            h1, h2, h3, h4 { color: #000 !important; page-break-after: avoid; }
            a { color: #000 !important; text-decoration: underline; }
            .photo-section::before, .photo-section::after { display: none !important; }
            .photo-section { min-height: auto !important; color: #000 !important; background: #f5f5f5 !important; }
            .photo-content h2, .photo-content p { color: #000 !important; text-shadow: none !important; }
            .enroll-result, .ms-progress, .ms-step, .pipeline, .enroll-form-wrap,
            .contact-form-wrap, .enroll-cta, .enroll-cards, .enroll-sub, .status-legend,
            .faq, .dates-grid, .who-table, .offline-enroll, .timeline-track,
            .values-grid, .grade-list, .steps, .glance-grid, .stats-grid,
            .academics-grid, .contact-grid, .filter-chips, .about-slider,
            .photo-content, .steps, .about-content, .island-content,
            .glance-card .label, .glance-card .value, .stat-card .context,
            .stat-card .label, .stat-card .number, .academic-card h3,
            .academic-card p, .ecard h4, .ecard p, .contact-card h4,
            .contact-card p, .contact-source, .form-field label, .form-note {
                color: #000 !important;
            }
            .footer-copy { color: #000 !important; border-top: 1px solid #000 !important; }
            .btn { display: none !important; }
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

    <!-- Sub-scroll progress ribbon (PR-1, non-destructive) -->
    <div class="scroll-ribbon" id="scrollRibbon" aria-hidden="true"></div>

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
            <?php
                // PR-4: smart CTA copy rotation. Default behavior unchanged.
                // Supported ?for= values: parent, teacher, deped. Anything else falls through to default.
                $for_raw   = isset($_GET['for']) ? (string)$_GET['for'] : '';
                $for_clean = strtolower(preg_replace('/[^a-z]/', '', $for_raw));
                $cta_label  = 'Discover Our School';
                $cta_href   = '#about';
                $cta_aria   = '';
                if ($for_clean === 'teacher') {
                    $cta_label = 'Teacher Login';
                    $cta_href  = 'login.php';
                } elseif ($for_clean === 'parent') {
                    $cta_label = 'Apply for SY 2026&ndash;27';
                    $cta_href  = '#enroll';
                } elseif ($for_clean === 'deped') {
                    $cta_label = 'Contact DepEd Tawi-Tawi';
                    $cta_href  = 'mailto:' . (isset($deped_division_phone) ? htmlspecialchars($deped_division_phone) : 'kerrfairtex@gmail.com');
                    $cta_aria  = ' (opens email)';
                }
            ?>
            <div class="hero-buttons">
                <a href="<?php echo htmlspecialchars($cta_href); ?>" class="btn btn-primary"<?php if ($cta_aria !== '') echo ' aria-label="' . htmlspecialchars($cta_label . $cta_aria) . '"'; ?>><?php echo htmlspecialchars($cta_label); ?></a>
                <a href="login.php" class="btn btn-outline">SmartCampus Portal</a>
                <a href="#enroll" class="btn btn-outline">Admissions / Enrollment</a>
                <a href="#contact" class="btn btn-outline">Contact the School</a>
            </div>
        </div>

        <!-- School clock (PR-1, non-destructive) -->
        <time id="schoolClock" class="school-clock" datetime="" aria-label="Current time in Tawi-Tawi">
            <span class="clock-label">Tawi-Tawi</span>
            <span id="clockTime">--:--:--</span>
        </time>
    </section>

    <!-- School at a Glance -->
    <section id="glance" class="glance">
        <div class="container">
            <h2 class="section-title">School at a Glance</h2>
            <p class="section-subtitle">Verified institutional information</p>
            <div class="glance-grid stagger-group">
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
            <div class="stats-grid stagger-group">
                <?php foreach ($community_stats as $key => $stat): ?>
                <div class="stat-card">
                    <div class="number countup" data-target="<?php echo (int)$stat['population']; ?>"><?php echo number_format($stat['population']); ?></div>
                    <div class="context"><?php echo $stat['context']; ?></div>
                    <div class="label"><?php echo $stat['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Island Community Photo Section -->
    <section class="photo-section island-section parallax-bg">
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

                <p><strong>Mission:</strong> <em id="missionBody" data-untouched="1">[To be configured from verified school records &mdash; insert the official DepEd/BARMM mission statement.]</em></p>

                <p><strong>Vision:</strong> <em id="visionBody" data-untouched="1">[To be configured from verified school records &mdash; insert the official school vision.]</em></p>

                <p id="valuesIntroBody" data-untouched="1" class="values-intro">The values below describe how Batu-Batu National Integrated High School approaches its work every day.</p>

                <div class="values-grid stagger-group">
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
                    <div class="v open status-badge live">OPEN</div>
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
                <div class="enroll-cards stagger-group">
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
                <div class="enroll-cards stagger-group">
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
                <div id="requirementsAccordion">
                <details class="req accordion">
                    <summary>New Learner</summary>
                    <div class="accordion-inner">
                    <ul>
                        <li>Learner information</li>
                        <li>PSA / NSO birth certificate, where required</li>
                        <li>Previous school records, where applicable</li>
                        <li>Enrollment-related forms</li>
                        <li>Parent / guardian information</li>
                        <li>Supporting documents</li>
                    </ul>
                    </div>
                </details>
                <details class="req accordion">
                    <summary>Transferee</summary>
                    <div class="accordion-inner">
                    <ul>
                        <li>Birth certificate</li>
                        <li>Previous school records</li>
                        <li>Form 137 / learner records, as applicable</li>
                        <li>Form 138 / report card, as applicable</li>
                        <li>Transfer credentials</li>
                        <li>Parent / guardian information</li>
                    </ul>
                    </div>
                </details>
                <details class="req accordion">
                    <summary>Returning Learner</summary>
                    <div class="accordion-inner">
                    <ul>
                        <li>Existing learner information</li>
                        <li>Previous school records</li>
                        <li>Updated parent / guardian information</li>
                        <li>Required enrollment forms</li>
                    </ul>
                    </div>
                </details>
                </div>
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
                <details class="accordion"><summary>Who can enroll at Batu-Batu NIHS?</summary><div class="accordion-inner"><p>Kindergarten, elementary, junior high (Grades 7&ndash;10), and senior high (Grades 11&ndash;12) learners, subject to the school's grade-level coverage for the school year.</p></div></details>
                <details class="accordion"><summary>What documents are required?</summary><div class="accordion-inner"><p>Requirements vary by enrollment category (New Learner, Transferee, Returning Learner). See the Requirements section above for the typical documents. The school configures the exact list per school year.</p></div></details>
                <details class="accordion"><summary>Can I enroll without an internet connection?</summary><div class="accordion-inner"><p>Yes. You can begin the application online and submit required documents physically at the school if connectivity is limited. Use "Save &amp; Continue Later" to keep your progress on this device.</p></div></details>
                <details class="accordion"><summary>Can I submit documents physically?</summary><div class="accordion-inner"><p>Yes. If online upload isn't possible, bring the required documents to the school. The application records which documents you will submit.</p></div></details>
                <details class="accordion"><summary>How do I check my application?</summary><div class="accordion-inner"><p>Use "Check Application Status" with the reference number you received (e.g. BATU-2026-001284). No full account is required.</p></div></details>
                <details class="accordion"><summary>What happens after I submit my application?</summary><div class="accordion-inner"><p>The school reviews your information and documents, then verifies and approves. You can track each stage via your reference number.</p></div></details>
                <details class="accordion"><summary>Can I edit my application after submission?</summary><div class="accordion-inner"><p>Contact the school with your reference number to request changes; the school can update records on your behalf.</p></div></details>
                <details class="accordion"><summary>How do transferees enroll?</summary><div class="accordion-inner"><p>Choose "Transferee" as the enrollment type and provide previous-school records (Form 137/138, transfer credentials) in the Requirements section.</p></div></details>
                <details class="accordion"><summary>Where can I get assistance?</summary><div class="accordion-inner"><p>Visit <?php echo htmlspecialchars($school_name); ?> in Batu-Batu, Panglima Sugala, Tawi-Tawi, or use the Contact section below.</p></div></details>
            </div>

            <div class="offline-enroll">
                <h3>Enrollment for Intermittent Connectivity</h3>
                <p style="text-align:center;color:var(--gray-300);">Designed for Turtle Islands / Tawi-Tawi, where connections can be slow or unreliable.</p>
                <div class="offline-flow">
                    <span class="step offline-flow-node">Start Application</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step offline-flow-node">Save &amp; Continue</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step offline-flow-node">Submit When Connected</span>
                    <span class="arrow">&rarr;</span>
                    <span class="step offline-flow-node">Need Offline Assistance?</span>
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
                <div class="feature-item" data-module="Students">
                    <div class="icon">📋</div>
                    <div class="name">Student Information</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Students">
                    <div class="icon">📝</div>
                    <div class="name">Enrollment</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Attendance">
                    <div class="icon">✅</div>
                    <div class="name">Attendance</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Grades">
                    <div class="icon">📊</div>
                    <div class="name">Grades</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Scheduling">
                    <div class="icon">📅</div>
                    <div class="name">Class Schedules</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Custom">
                    <div class="icon">📢</div>
                    <div class="name">Announcements</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Resources">
                    <div class="icon">📚</div>
                    <div class="name">Library</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
                <div class="feature-item" data-module="Custom">
                    <div class="icon">👨‍👩‍👧</div>
                    <div class="name">Parent Communication</div>
                    <div class="module-dot" aria-label="Module status"></div>
                </div>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="login.php" class="btn btn-primary">Enter Smart Campus</a>
            </div>
        </div>
    </section>

    <!-- Community Photo Section -->
    <section class="photo-section community-section parallax-bg">
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

            <!-- Gallery filter chips (PR-1, non-destructive) -->
            <div class="filter-chips" role="toolbar" aria-label="Gallery filter">
                <button class="chip active" type="button" data-filter="all" aria-pressed="true">All</button>
                <button class="chip" type="button" data-filter="campus" aria-pressed="false">Campus</button>
                <button class="chip" type="button" data-filter="community" aria-pressed="false">Community</button>
                <button class="chip" type="button" data-filter="events" aria-pressed="false">Events</button>
            </div>

            <div class="cg-stage" id="cgStage">
                <img class="active" src="assets/images/bbnihs-staff.jpeg" alt="Batu-Batu National High School teachers and staff group photo"
                     data-title="Our Faculty &amp; Staff"
                     data-desc="Teachers and school personnel of Batu-Batu National High School in Panglima Sugala, Tawi-Tawi, posed in front of the school building."
                     data-cat="campus"
                     loading="lazy" decoding="async">
                <img src="assets/images/bbnihs-baccalaureate.jpeg" alt="Batu-Batu Integrated High School Joint Baccalaureate Service Recognition Day"
                     data-title="Baccalaureate Recognition Day"
                     data-desc="Students and faculty of Batu-Batu Integrated High School during their Joint Baccalaureate Service Recognition Day, celebrating learner achievement."
                     data-cat="events"
                     loading="lazy" decoding="async">
                <img src="assets/images/bbnihs-graduation.jpeg" alt="Batu Integrated High School 52nd Graduation Exercises"
                     data-title="52nd Graduation Exercises"
                     data-desc="The graduating class, faculty, and staff of Batu Integrated High School on stage for the 52nd Graduation Exercises."
                     data-cat="events"
                     loading="lazy" decoding="async">
                <img src="assets/images/bbnihs-legacy.jpg" alt="Batu-Batu National High School named NMYL Legacy Program Site"
                     data-title="Legacy Program Site"
                     data-desc="Batu-Batu National High School in Panglima Sugala, Tawi-Tawi, recognized as a Legacy Program Site by the National Movement of Young Legislators (February 4, 2025)."
                     data-cat="campus"
                     loading="lazy" decoding="async">
                <img src="assets/images/bbnihs-scholarship.jpeg" alt="Bangsamoro Scholarship Program induction in Tawi-Tawi"
                     data-title="Scholarship &amp; Training Induction"
                     data-desc="Participants in a Training Induction Program for the Bangsamoro Scholarship Program for Technical-Vocational Education, Tawi-Tawi &mdash; expanding access to learning beyond the classroom."
                     data-cat="events"
                     loading="lazy" decoding="async">
                <img src="assets/images/classroom1.jpeg" alt="A teacher leading a classroom lesson"
                     data-title="In the Classroom"
                     data-desc="A teacher guides young learners through a lesson at their desks &mdash; the daily heart of basic education."
                     data-cat="campus"
                     loading="lazy" decoding="async">
                <img src="assets/images/tawi-bajau-children.jpeg" alt="Children on a coastal stilt-village walkway"
                     data-title="Island Community Life"
                     data-desc="Children cross a bamboo walkway over the sea in a Tawi-Tawi coastal community &mdash; the everyday reality of island living."
                     data-cat="community"
                     loading="lazy" decoding="async">
                <img src="assets/images/tawi-bongao.jpg" alt="Bongao coastal stilt village with mountain backdrop"
                     data-title="Our Region: Tawi-Tawi"
                     data-desc="Stilt homes and fish farms along the coastline beneath Tawi-Tawi's rugged mountains &mdash; the province Batu-Batu calls home."
                     data-cat="community"
                     loading="lazy" decoding="async">

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
                    <!-- Office hours badge (PR-1, non-destructive) -->
                    <p class="office-hours" id="officeHours" aria-live="polite">
                        <span class="oh-dot" aria-hidden="true"></span>
                        <span id="officeHoursLabel">Office hours: Mon&ndash;Fri 7:30 AM &ndash; 4:30 PM (Tawi-Tawi)</span>
                    </p>
                    <p class="contact-source">Source: DepEd School Masterlist (National Inventory Dashboard).</p>
                </div>

                <!-- Where We Are -->
                <div class="contact-card">
                    <h4>Where We Are</h4>
                    <p>Batu-Batu, Poblacion<br>Panglima Sugala<br>Tawi-Tawi<br>BARMM, Philippines</p>
                    <!-- PR-3: OSM embed. Wrapped in a tap-to-load gate per spec §20 to save data on the
                         low-bandwidth audience. The .map-gate placeholder is visible by default;
                         clicking it (or pressing Enter) swaps in the iframe via .armed. -->
                    <div class="map-gate" data-target="#osmMap" role="button" tabindex="0" aria-label="Tap to load the OpenStreetMap embed">
                        <div>
                            <strong style="display:block;font-size:1.05rem;margin-bottom:0.3rem;color:var(--white);">📍 Tap to load map — uses data</strong>
                            <span style="color:var(--gray-500);font-size:0.85rem;">The interactive map will only load if you tap here. Lightweight, no tracking.</span>
                        </div>
                    </div>
                    <div class="osm-wrap" id="osmMap" hidden>
                        <iframe
                            title="Map of Batu-Batu, Poblacion, Panglima Sugala, Tawi-Tawi"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            sandbox=""
                            src="https://www.openstreetmap.org/export/embed.html?bbox=119.85%2C4.65%2C119.95%2C4.75&amp;layer=mapnik&amp;marker=4.7%2C119.9"></iframe>
                        <div class="osm-caption">
                            Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors &middot;
                            <a href="https://www.openstreetmap.org/?mlat=4.7&amp;mlon=119.9#map=14/4.7/119.9" target="_blank" rel="noopener">Open in OpenStreetMap &rarr;</a>
                        </div>
                    </div>
                    <p style="margin-top:0.75rem;"><a class="btn btn-outline" href="<?php echo htmlspecialchars($school_maps); ?>" target="_blank" rel="noopener">Open in Google Maps</a></p>
                </div>

                <!-- SmartCampus Support (project / developer) -->
                <div class="contact-card">
                    <h4>SmartCampus Support</h4>
                    <p>Need help with the SmartCampus portal?<br><br>
                    &nbsp;</p>
                    <p>
                        <a href="tel:<?php echo htmlspecialchars($project_phone); ?>"><?php echo htmlspecialchars($project_phone); ?></a><br>
                        <a href="mailto:<?php echo htmlspecialchars($project_email); ?>"><?php echo htmlspecialchars($project_email); ?></a><br>
                        <a href="<?php echo htmlspecialchars($project_facebook); ?>" target="_blank" rel="noopener">Facebook</a>
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
                    <!-- PR-3: honeypot field. Hidden from real users; bots fill it. -->
                    <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                        <label for="hp_url">Leave this field empty</label>
                        <input type="text" id="hp_url" name="website_url" tabindex="-1" autocomplete="off" value="">
                    </div>
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
            <div class="contact-who" id="who">
                <h3>Who Should I Contact?</h3>
                <table class="who-table">
                    <thead><tr><th>Concern</th><th>Contact</th></tr></thead>
                    <tbody>
                        <tr><td>Enrollment</td><td>School / designated enrollment personnel</td></tr>
                        <tr><td>Student records</td><td>School registrar</td></tr>
                        <tr><td>Academic concerns</td><td>School / teacher</td></tr>
                        <tr><td>Parent concerns</td><td>School administration</td></tr>
                        <tr><td>SmartCampus technical issue</td><td>SmartCampus team</td></tr>
                        <tr><td>Website issue</td><td>SmartCampus team</td></tr>
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
                <p class="assist-note">For school administrative matters, contact the school or DepEd.</p>
            </div>

            <p class="assist-note">Don't rely exclusively on email. A phone / SMS / in-person visit is more practical for many island residents.</p>
        </div>
    </section>

    <!-- Accessibility toggle bar (PR-1, non-destructive) -->
    <div class="a11y-bar" role="region" aria-label="Accessibility options">
        <span class="a11y-label">Accessibility</span>
        <button type="button" id="a11yHc" aria-pressed="false">High contrast</button>
        <button type="button" id="a11yDys" aria-pressed="false">Dyslexia-friendly font</button>
        <button type="button" id="a11yLarge" aria-pressed="false">Larger text</button>
        <!-- Search launcher (PR-2, non-destructive) -->
        <button type="button" class="search-launch" id="searchLaunch" aria-label="Open search">
            Search<kbd>/</kbd>
        </button>
        <!-- Spec §16: live online/offline badge -->
        <span id="connBadge" class="conn-badge" role="status" aria-live="polite">
            <span class="dot"></span>Online
        </span>
    </div>

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
                <h4>ADVISER</h4>
                <p>ALAWADDIN JR I. BUDDIN</p>
            </div>
            <div class="footer-col">
                <h4>CONTACT</h4>
            </div>
            <div class="footer-col">
                <h4>DEVELOPERS</h4>
                <p>KADIL, AL-KHALID I.</p>
                <p>FATIMA JAHARA MENDOZA</p>
                <p>JAMES KENNETH CAGANG</p>
                <p>AVON MADALI</p>
                <p>SAFRY MANALO</p>
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
                if (!inp.value.trim()) { ok = false; inp.style.borderColor = '#dc2626'; }
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
            var payload = gather();
            // Local fallback (offline) — preserved from before Tier 3
            try { localStorage.setItem('bbnihs_draft', JSON.stringify(payload)); } catch (e) {}
            // Server-side draft (cross-device) — new in Tier 3
            res.textContent = 'Saving your progress\u2026';
            res.classList.add('show');
            fetch('enroll_api.php?action=draft_save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.url) {
                    res.innerHTML = 'Saved on the server. To continue on another device, copy this link: <br><input type="text" value="' + data.url + '" readonly style="width:100%;font-family:ui-monospace,monospace;margin-top:0.4rem;padding:0.4rem;">';
                } else {
                    res.textContent = 'Saved on this device only (server unreachable).';
                }
            })
            .catch(function () {
                res.textContent = 'Saved on this device only (server unreachable).';
            });
        });
        // restore draft from server-side token (Tier 3, ?action=draft_resume&token=...)
        (function () {
            var url = new URL(window.location.href);
            var action = url.searchParams.get('action');
            var token = url.searchParams.get('token');
            if (action !== 'draft_resume' || !token || !/^[a-f0-9]{64}$/.test(token)) return;
            fetch('enroll_api.php?action=draft_resume&token=' + encodeURIComponent(token))
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.payload) return;
                    var d = data.payload;
                    Object.keys(d).forEach(function (k) {
                        if (k === 'documents') return;
                        if (form[k]) { if (form[k].type === 'checkbox') form[k].checked = true; else form[k].value = d[k]; }
                    });
                    if (d.documents) {
                        form.doc_bc.checked = !!d.documents.bc; form.doc_rc.checked = !!d.documents.rc;
                        form.doc_tc.checked = !!d.documents.tc; form.doc_other.checked = !!d.documents.other;
                    }
                    res.textContent = 'Draft restored from server. Review and submit, or save again to keep going.';
                    res.classList.add('show');
                    showStep(0);
                })
                .catch(function () { /* fall through to localStorage */ });
        })();

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

    <!-- ===== Tier 1 PR-2: Cmd/K search dialog (non-destructive) ===== -->
    <dialog id="searchDialog" aria-label="Search this page">
        <div class="search-wrap">
            <input type="search" class="search-input" id="searchInput"
                   placeholder="Search this page&hellip; (e.g. enrollment, contact, Grade 11)"
                   aria-label="Search query" autocomplete="off" spellcheck="false">
            <ul class="search-results" id="searchResults" role="listbox" aria-label="Search results"></ul>
            <div class="hint">
                <span><kbd>&uarr;</kbd><kbd>&darr;</kbd> navigate</span>
                <span><kbd>Enter</kbd> open</span>
                <span><kbd>Esc</kbd> close</span>
            </div>
        </div>
    </dialog>

    <!-- ===== PR-2: inline search index (non-destructive) ===== -->
    <script type="application/json" id="searchIndex">
{
  "sections": [
    {
      "id": "glance",
      "title": "School at a Glance",
      "type": "section",
      "keywords": ["school id", "BBNIHS", "Tawi-Tawi", "Batu-Batu", "Poblacion", "Panglima Sugala", "305053", "DepEd", "public", "integrated", "high school"]
    },
    {
      "id": "community",
      "title": "Our Community",
      "type": "section",
      "keywords": ["PSA", "POPCEN", "2024", "population", "3936", "52657", "482645", "barangay", "municipality", "province", "Tawi-Tawi", "census"]
    },
    {
      "id": "about",
      "title": "About Batu-Batu NHS",
      "type": "section",
      "keywords": ["K-12", "barangay", "island", "Panglima Sugala", "mission", "vision", "learner", "teacher", "inclusion", "inclusive", "resilience", "community", "K to 12"]
    },
    {
      "id": "admissions",
      "title": "Academic Programs",
      "type": "section",
      "keywords": ["junior high", "senior high", "Grade 7", "Grade 8", "Grade 9", "Grade 10", "Grade 11", "Grade 12", "JHS", "SHS", "K-12", "track", "strand", "academic", "DepEd"]
    },
    {
      "id": "enroll",
      "title": "Admissions &amp; Enrollment",
      "type": "section",
      "keywords": ["enroll", "apply", "application", "transferee", "balik-aral", "returning learner", "requirements", "birth certificate", "form 137", "form 138", "LRN", "reference number", "status", "school year", "2026", "2027"]
    },
    {
      "id": "features",
      "title": "Smart Campus K12",
      "type": "section",
      "keywords": ["student information", "attendance", "grades", "schedule", "announcements", "library", "parent communication", "SmartCampus", "portal", "module"]
    },
    {
      "id": "contact",
      "title": "Contact",
      "type": "section",
      "keywords": ["phone", "email", "facebook", "viber", "whatsapp", "DepEd", "Tawi-Tawi", "Panglima Sugala", "Batu-Batu", "092-4151", "Schools Division", "Tawi-Tawi Schools Division", "office hours", "Mon-Fri", "7:30", "4:30"]
    },
    {
      "id": "who",
      "title": "Who Should I Contact?",
      "type": "section",
      "keywords": ["enrollment", "registrar", "academic", "parent", "SmartCampus team", "SmartCampus", "DepEd", "Tawi-Tawi Schools Division", "learner protection", "concerns", "routing"]
    },
    {
      "id": "faq-1",
      "title": "Who can enroll at Batu-Batu NIHS?",
      "type": "faq",
      "text": "Kindergarten, elementary, junior high (Grades 7-10), and senior high (Grades 11-12) learners, subject to the school's grade-level coverage for the school year."
    },
    {
      "id": "faq-2",
      "title": "What documents are required?",
      "type": "faq",
      "text": "Requirements vary by enrollment category (New Learner, Transferee, Returning Learner). New: birth certificate, previous school records, enrollment forms. Transferee: form 137, form 138, transfer credentials."
    },
    {
      "id": "faq-3",
      "title": "Can I enroll without an internet connection?",
      "type": "faq",
      "text": "Yes. You can begin the application online and submit required documents physically at the school if connectivity is limited. Use 'Save &amp; Continue Later' to keep your progress on this device."
    },
    {
      "id": "faq-4",
      "title": "Can I submit documents physically?",
      "type": "faq",
      "text": "Yes. If online upload isn't possible, bring the required documents to the school. The application records which documents you will submit."
    },
    {
      "id": "faq-5",
      "title": "How do I check my application status?",
      "type": "faq",
      "text": "Use 'Check Application Status' with the reference number you received (for example BATU-2026-001284). No full account is required."
    },
    {
      "id": "faq-6",
      "title": "What happens after I submit my application?",
      "type": "faq",
      "text": "The school reviews your information and documents, then verifies and approves. You can track each stage via your reference number."
    },
    {
      "id": "faq-7",
      "title": "Can I edit my application after submission?",
      "type": "faq",
      "text": "Contact the school with your reference number to request changes; the school can update records on your behalf."
    },
    {
      "id": "faq-8",
      "title": "How do transferees enroll?",
      "type": "faq",
      "text": "Choose 'Transferee' as the enrollment type and provide previous-school records (Form 137/138, transfer credentials) in the Requirements section."
    },
    {
      "id": "faq-9",
      "title": "Where can I get assistance?",
      "type": "faq",
      "text": "Visit Batu-Batu National Integrated High School in Batu-Batu, Panglima Sugala, Tawi-Tawi, or use the Contact section."
    },
    {
      "id": "contact-form",
      "title": "Contact the SmartCampus Team",
      "type": "action",
      "text": "Send a message to the project team. For enrollment decisions or school records, contact the school directly."
    },
    {
      "id": "enroll-form",
      "title": "Start Your Enrollment",
      "type": "action",
      "text": "Begin the 5-step enrollment form. New, Returning, Transferee, or Balik-Aral categories. Save and continue later is supported on this device."
    },
    {
      "id": "status-form",
      "title": "Check Application Status",
      "type": "action",
      "text": "Look up your application by reference number (for example BATU-2026-001284). See which stage your application has reached."
    }
  ]
}
    </script>

    <!-- ===== Tier 1 PR-2: Cmd/K search behavior (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';

        var dialog = document.getElementById('searchDialog');
        var input = document.getElementById('searchInput');
        var results = document.getElementById('searchResults');
        var launch = document.getElementById('searchLaunch');
        var indexNode = document.getElementById('searchIndex');
        if (!dialog || !input || !results || !launch || !indexNode) return;

        var indexData;
        try { indexData = JSON.parse(indexNode.textContent); }
        catch (e) { indexData = { sections: [] }; }
        var sections = indexData.sections || [];

        var selectedIdx = 0;
        var lastQuery = '';

        function score(entry, q) {
            var t = (entry.title || '').toLowerCase();
            var k = (entry.keywords || []).join(' ').toLowerCase();
            var x = (entry.text || '').toLowerCase();
            if (t.indexOf(q) >= 0) return 3;          // title hit
            if (k.indexOf(q) >= 0) return 2;          // keyword hit
            if (x.indexOf(q) >= 0) return 1;          // text hit
            return 0;
        }

        function render(query) {
            results.innerHTML = '';
            selectedIdx = 0;
            if (!query) { showEmpty('Start typing to search\u2026'); return; }
            var q = query.toLowerCase().trim();
            if (!q) { showEmpty('Start typing to search\u2026'); return; }
            var hits = [];
            for (var i = 0; i < sections.length; i++) {
                var s = sections[i];
                var sc = score(s, q);
                if (sc > 0) hits.push({ e: s, sc: sc, i: i });
            }
            hits.sort(function (a, b) {
                if (b.sc !== a.sc) return b.sc - a.sc;
                return a.i - b.i;
            });
            if (hits.length === 0) { showEmpty('No matches for \u201c' + escapeHtml(query) + '\u201d'); return; }
            hits = hits.slice(0, 8);
            for (var j = 0; j < hits.length; j++) {
                (function (h, idx) {
                    var li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.setAttribute('data-id', h.e.id);
                    li.setAttribute('data-type', h.e.type);
                    if (idx === 0) li.setAttribute('aria-selected', 'true');
                    var typeSpan = document.createElement('span');
                    typeSpan.className = 'type ' + h.e.type;
                    typeSpan.textContent = h.e.type;
                    var titleSpan = document.createElement('span');
                    titleSpan.className = 'title';
                    titleSpan.textContent = h.e.title;
                    li.appendChild(typeSpan);
                    li.appendChild(titleSpan);
                    li.addEventListener('click', function () { activate(h.e); });
                    li.addEventListener('mouseenter', function () { setSelected(idx); });
                    results.appendChild(li);
                })(hits[j], j);
            }
        }

        function showEmpty(msg) {
            var li = document.createElement('li');
            li.className = 'empty';
            li.setAttribute('role', 'presentation');
            li.textContent = msg;
            results.appendChild(li);
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function setSelected(i) {
            var items = results.querySelectorAll('li[role="option"]');
            if (!items.length) return;
            if (i < 0) i = items.length - 1;
            if (i >= items.length) i = 0;
            selectedIdx = i;
            for (var k = 0; k < items.length; k++) {
                if (k === i) items[k].setAttribute('aria-selected', 'true');
                else items[k].removeAttribute('aria-selected');
            }
            items[i].scrollIntoView({ block: 'nearest' });
        }

        function activate(entry) {
            close();
            if (entry.type === 'action') {
                if (entry.id === 'enroll-form') {
                    var el = document.getElementById('enroll-form');
                    if (el) el.scrollIntoView({ behavior: 'smooth' }); else location.hash = '#enroll';
                } else if (entry.id === 'status-form') {
                    var el2 = document.getElementById('enroll-status');
                    if (el2) el2.scrollIntoView({ behavior: 'smooth' }); else location.hash = '#enroll';
                } else if (entry.id === 'contact-form') {
                    var el3 = document.getElementById('contact');
                    if (el3) el3.scrollIntoView({ behavior: 'smooth' }); else location.hash = '#contact';
                }
            } else {
                var target = document.getElementById(entry.id);
                if (target) target.scrollIntoView({ behavior: 'smooth' });
                else location.hash = '#' + entry.id;
            }
        }

        function open() {
            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', '');
            input.value = '';
            render('');
            setTimeout(function () { input.focus(); }, 30);
        }
        function close() {
            if (typeof dialog.close === 'function') dialog.close();
            else dialog.removeAttribute('open');
        }

        launch.addEventListener('click', open);
        input.addEventListener('input', function () {
            lastQuery = input.value;
            render(lastQuery);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setSelected(selectedIdx + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setSelected(selectedIdx - 1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                var items = results.querySelectorAll('li[role="option"]');
                if (items[selectedIdx]) items[selectedIdx].click();
            }
            else if (e.key === 'Escape') { e.preventDefault(); close(); }
        });

        // Global hotkeys: '/' or Cmd/Ctrl-K
        document.addEventListener('keydown', function (e) {
            if (e.defaultPrevented) return;
            var tag = (e.target && e.target.tagName) || '';
            var inField = tag === 'INPUT' || tag === 'TEXTAREA' || (e.target && e.target.isContentEditable);
            if (e.key === '/' && !inField) {
                e.preventDefault();
                if (dialog.open) close(); else open();
            } else if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                if (dialog.open) close(); else open();
            }
        });

        // Click outside the dialog content to close
        dialog.addEventListener('click', function (e) {
            var r = dialog.getBoundingClientRect();
            var inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
            if (!inside) close();
        });
        // Native dialog also fires 'cancel' on Esc; nothing extra needed.
    })();
    </script>

    <!-- ===== Tier 1 PR-1: Interactive behaviors (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';

        // --- Sub-scroll ribbon ---
        var ribbon = document.getElementById('scrollRibbon');
        var docEl = document.documentElement;
        function updateRibbon() {
            var h = docEl.scrollHeight - docEl.clientHeight;
            var pct = h > 0 ? (window.scrollY / h) * 100 : 0;
            if (ribbon) ribbon.style.width = Math.min(100, Math.max(0, pct)) + '%';
        }
        window.addEventListener('scroll', updateRibbon, { passive: true });
        window.addEventListener('resize', updateRibbon);
        updateRibbon();

        // --- School clock (Asia/Manila) ---
        var clockEl = document.getElementById('clockTime');
        var clockTime = document.getElementById('schoolClock');
        function updateClock() {
            try {
                var now = new Date();
                var fmt = new Intl.DateTimeFormat('en-GB', {
                    timeZone: 'Asia/Manila',
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
                });
                var parts = fmt.formatToParts(now);
                var hh = '', mm = '', ss = '';
                for (var i = 0; i < parts.length; i++) {
                    if (parts[i].type === 'hour') hh = parts[i].value;
                    else if (parts[i].type === 'minute') mm = parts[i].value;
                    else if (parts[i].type === 'second') ss = parts[i].value;
                }
                var txt = hh + ':' + mm + ':' + ss;
                if (clockEl) clockEl.textContent = txt;
                if (clockTime) clockTime.setAttribute('datetime', now.toISOString());
            } catch (e) {
                if (clockEl) clockEl.textContent = '--:--:--';
            }
        }
        updateClock();
        setInterval(updateClock, 1000);

        // --- Office hours (Mon-Fri 07:30-16:30 Asia/Manila) ---
        var ohBadge = document.getElementById('officeHours');
        var ohLabel = document.getElementById('officeHoursLabel');
        function updateOfficeHours() {
            if (!ohBadge || !ohLabel) return;
            var now = new Date();
            // Asia/Manila day-of-week: 0=Sun..6=Sat; get via Intl
            var dowFmt = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', weekday: 'short' });
            var hourFmt = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', hour12: false });
            var dowStr = dowFmt.format(now);
            var hrStr = hourFmt.format(now);
            var dowMap = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
            var dow = dowMap[dowStr];
            var hh = parseInt(hrStr.split(':')[0], 10);
            var mm = parseInt(hrStr.split(':')[1], 10);
            var mins = hh * 60 + mm;
            var open = (dow >= 1 && dow <= 5 && mins >= 7 * 60 + 30 && mins <= 16 * 60 + 30);
            if (open) {
                ohBadge.classList.remove('closed');
                ohLabel.textContent = 'Office open now · Mon\u2013Fri 7:30 AM \u2013 4:30 PM (Tawi-Tawi)';
            } else {
                ohBadge.classList.add('closed');
                ohLabel.textContent = 'Office closed · Mon\u2013Fri 7:30 AM \u2013 4:30 PM (Tawi-Tawi)';
            }
        }
        updateOfficeHours();
        setInterval(updateOfficeHours, 60000);

        // --- Gallery filter chips ---
        var chips = document.querySelectorAll('.filter-chips .chip');
        var galleryImgs = document.querySelectorAll('#cgStage img');
        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var f = chip.getAttribute('data-filter');
                chips.forEach(function (c) {
                    c.classList.remove('active');
                    c.setAttribute('aria-pressed', 'false');
                });
                chip.classList.add('active');
                chip.setAttribute('aria-pressed', 'true');
                galleryImgs.forEach(function (img) {
                    var cat = img.getAttribute('data-cat') || '';
                    if (f === 'all' || cat === f) {
                        img.classList.remove('hidden');
                    } else {
                        img.classList.add('hidden');
                    }
                });
                // If active image is now hidden, advance to first visible
                var active = document.querySelector('#cgStage img.active');
                if (active && active.classList.contains('hidden')) {
                    active.classList.remove('active');
                    var firstVisible = document.querySelector('#cgStage img:not(.hidden)');
                    if (firstVisible) firstVisible.classList.add('active');
                }
            });
        });

        // --- Accessibility toggles (state persisted to localStorage) ---
        var A11Y_KEYS = { hc: 'a11y_hc', dys: 'a11y_dys', large: 'a11y_large' };
        function readA11y() {
            try {
                return {
                    hc: localStorage.getItem(A11Y_KEYS.hc) === '1',
                    dys: localStorage.getItem(A11Y_KEYS.dys) === '1',
                    large: localStorage.getItem(A11Y_KEYS.large) === '1'
                };
            } catch (e) { return { hc: false, dys: false, large: false }; }
        }
        function writeA11y(state) {
            try {
                localStorage.setItem(A11Y_KEYS.hc, state.hc ? '1' : '0');
                localStorage.setItem(A11Y_KEYS.dys, state.dys ? '1' : '0');
                localStorage.setItem(A11Y_KEYS.large, state.large ? '1' : '0');
            } catch (e) {}
        }
        function applyA11y(state) {
            var html = document.documentElement;
            html.classList.toggle('a11y-hc', state.hc);
            html.classList.toggle('a11y-dyslexic', state.dys);
            html.classList.toggle('a11y-large', state.large);
            var btnHc = document.getElementById('a11yHc');
            var btnDys = document.getElementById('a11yDys');
            var btnLarge = document.getElementById('a11yLarge');
            if (btnHc) btnHc.setAttribute('aria-pressed', state.hc ? 'true' : 'false');
            if (btnDys) btnDys.setAttribute('aria-pressed', state.dys ? 'true' : 'false');
            if (btnLarge) btnLarge.setAttribute('aria-pressed', state.large ? 'true' : 'false');
        }
        function toggleA11y(key) {
            var s = readA11y();
            s[key] = !s[key];
            writeA11y(s);
            applyA11y(s);
        }
        var b1 = document.getElementById('a11yHc');
        var b2 = document.getElementById('a11yDys');
        var b3 = document.getElementById('a11yLarge');
        if (b1) b1.addEventListener('click', function () { toggleA11y('hc'); });
        if (b2) b2.addEventListener('click', function () { toggleA11y('dys'); });
        if (b3) b3.addEventListener('click', function () { toggleA11y('large'); });
        applyA11y(readA11y());
    })();
    </script>

    <!-- ===== Tier 3: module status dots (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';
        var items = document.querySelectorAll('.features-grid .feature-item[data-module]');
        if (!items.length) return;
        // Default to "on" (green dot) so a network failure still looks fine.
        items.forEach(function (it) { it.classList.remove('off'); });
        fetch('modules_api.php', { cache: 'no-store' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.modules) return;
                items.forEach(function (it) {
                    var m = it.getAttribute('data-module');
                    if (m && data.modules[m] === false) it.classList.add('off');
                });
            })
            .catch(function () { /* keep all-on */ });
    })();
    </script>

    <!-- ===== PR-3: Floating Viber/WhatsApp contact button (non-destructive) ===== -->
    <div class="float-contact" id="floatContact" role="region" aria-label="Quick contact">
        <div class="fc-pills" id="fcPills">
            <a class="fc-pill viber" href="viber://chat?number=%2B639637130812" target="_blank" rel="noopener" aria-label="Chat on Viber">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.4 0C5.1 0 0 5.1 0 11.4c0 2.4.8 4.7 2.2 6.6L.7 24l6.2-1.6c1.8 1 3.9 1.5 6 1.5 6.3 0 11.4-5.1 11.4-11.4S17.7 0 11.4 0zm0 20.9c-1.9 0-3.8-.5-5.4-1.5l-.4-.2-3.7 1 1-3.6-.2-.4c-1.2-1.7-1.8-3.7-1.8-5.7 0-5.6 4.6-10.1 10.1-10.1 5.6 0 10.1 4.6 10.1 10.1.1 5.5-4.5 10.4-9.7 10.4zm5.6-7.7c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.2.2-.4.1-.1 0-.5-.1-1-.8-.5-1.3-1.6-1.4-1.7-.2-.3-.4-.3-.6-.1-.3.1-1.1 1.1-1.1 1.1s-1 1.1-.2 2.2c.8 1.1 1.5 1.8 3.4 3.3 2.2 1.7 2.2 1.1 2.6 1 .4-.1 1.3-.5 1.5-1 .2-.5.2-.9.1-1-.1-.1-.3-.2-.6-.4z"/></svg>
                <span>Viber</span>
            </a>
            <a class="fc-pill whatsapp" href="https://wa.me/639637130812?text=Hi%20Batu-Batu%20NHS%20(BBNIHS)" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.8-.7-1.4-1.6-1.6-1.9-.2-.3 0-.4.1-.6.1-.1.2-.3.3-.5.4-.2.2-.3.3-.5.5s-.4.2-.6.1c-.3-.1-1.1-.4-2.1-1.3-.8-.7-1.3-1.5-1.5-1.8-.2-.3 0-.4.1-.5.1-.1.3-.7 1-1.4.2-.3.3-.6.5-.8.1-.1.2-.2.2-.3.1-.1 0-.2 0-.3-.1-.2-.7-1.7-.9-2.3-.2-.6-.5-.5-.6-.5h-.5c-.2 0-.5.1-.7.3-.3.3-1 1-1 2.4s1 2.7 1.2 2.9c.1.2 2.1 3.2 5 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.2.2-1.4-.2-.1-.3-.2-.6-.3zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.6 1.4 5.1L2 22l5.1-1.4c1.5.8 3.2 1.2 4.9 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.1c-1.6 0-3.1-.4-4.5-1.3l-.3-.2-3 .8.8-2.9-.2-.3c-.9-1.4-1.4-3-1.4-4.6 0-4.7 3.8-8.5 8.5-8.5s8.5 3.8 8.5 8.5-3.7 8.5-8.4 8.5z"/></svg>
                <span>WhatsApp</span>
            </a>
        </div>
        <button type="button" class="fc-launch" id="fcLaunch" aria-label="Open quick contact" aria-expanded="false" aria-controls="fcPills">
            <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
        </button>
    </div>

    <!-- ===== PR-3: Floating button + contact-click beacon (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';
        var root = document.getElementById('floatContact');
        var launch = document.getElementById('fcLaunch');
        if (root && launch) {
            launch.addEventListener('click', function () {
                var open = root.classList.toggle('open');
                launch.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function (e) {
                if (!root.contains(e.target)) {
                    root.classList.remove('open');
                    launch.setAttribute('aria-expanded', 'false');
                }
            });
        }
        // Fire a 1x1 beacon per channel click. The endpoint is engagement.php
        // (extended in PR-3) which writes status='contact_click:<channel>'
        // to access_log, surfaced in the admin snapshot.
        var contactBtns = document.querySelectorAll('.float-contact .fc-pill');
        contactBtns.forEach(function (a) {
            a.addEventListener('click', function () {
                var channel = a.classList.contains('viber') ? 'viber' : (a.classList.contains('whatsapp') ? 'whatsapp' : 'unknown');
                var img = new Image(1, 1);
                img.alt = '';
                img.style.cssText = 'position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;';
                img.src = 'engagement.php?action=contact_click&channel=' + channel;
                document.body.appendChild(img);
                setTimeout(function () { if (img.parentNode) img.parentNode.removeChild(img); }, 5000);
            });
        });
    })();
    </script>

    <!-- ===== Spec §0–§23: Motion behaviors (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';

        // ---------- §0: Reduced-motion + save-data gates ----------
        var root = document.documentElement;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            root.classList.add('motion-reduced');
        }
        if (navigator.connection && (navigator.connection.saveData ||
            ['slow-2g', '2g'].indexOf(navigator.connection.effectiveType) !== -1)) {
            root.classList.add('save-data');
        }
        // If user already armed the hero this session, skip the load sequence
        var sessionArmed = false;
        try { sessionArmed = sessionStorage.getItem('bbnihs-hero-armed') === '1'; } catch (e) {}
        if (sessionArmed) root.classList.add('hero-armed');
        else requestAnimationFrame(function () { root.classList.add('hero-armed'); try { sessionStorage.setItem('bbnihs-hero-armed', '1'); } catch (e) {} });

        // ---------- §2a: Sticky nav backdrop on scroll ----------
        var nav = document.querySelector('.nav-bar');
        if (nav) {
            var lastScrolled = false;
            function onScroll() {
                var scrolled = window.scrollY > 40;
                if (scrolled !== lastScrolled) {
                    nav.classList.toggle('scrolled', scrolled);
                    lastScrolled = scrolled;
                }
                // §2b: legacy scroll-progress fallback
                var ribbon = document.getElementById('scrollRibbon');
                if (ribbon && !CSS.supports('(animation-timeline: scroll())')) {
                    var h = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    var pct = h > 0 ? (window.scrollY / h) * 100 : 0;
                    ribbon.style.width = Math.min(100, Math.max(0, pct)) + '%';
                }
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            // Active-link class via IO on each section
            try {
                var links = nav.querySelectorAll('a[href^="#"]');
                var map = {};
                links.forEach(function (a) {
                    var id = a.getAttribute('href').slice(1);
                    var t = document.getElementById(id);
                    if (t) map[id] = a;
                });
                if (Object.keys(map).length) {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (e) {
                            var a = map[e.target.id];
                            if (!a) return;
                            if (e.isIntersecting) {
                                links.forEach(function (l) { l.classList.remove('active'); });
                                a.classList.add('active');
                            }
                        });
                    }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
                    Object.keys(map).forEach(function (id) {
                        var t = document.getElementById(id);
                        if (t) io.observe(t);
                    });
                }
            } catch (e) {}
        }

        // ---------- §2c: Clock tick pulse (CSS does the work, this just adds the .tick class once per second) ----------
        var clock = document.getElementById('schoolClock');
        var lastSec = -1;
        setInterval(function () {
            if (!clock) return;
            var s = new Date().getSeconds();
            if (s !== lastSec) {
                lastSec = s;
                clock.classList.add('tick');
                setTimeout(function () { clock.classList.remove('tick'); }, 100);
            }
        }, 250);

        // ---------- §2d: FAB contextual pulse on first scroll-into-view of the offline-flow section ----------
        var offlineFlow = document.querySelector('.offline-flow');
        if (offlineFlow && 'IntersectionObserver' in window) {
            var fabPulsed = false;
            new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting && !fabPulsed) {
                        fabPulsed = true;
                        var fc = document.getElementById('floatContact');
                        if (fc) {
                            fc.classList.add('context-pulse');
                            setTimeout(function () { fc.classList.remove('context-pulse'); }, 220);
                        }
                    }
                });
            }, { threshold: 0.4 }).observe(offlineFlow);
        }

        // ---------- §3: Hero arm is handled above (in §0 init). Nothing more to do.

        // ---------- §4/§7c/§12: Shared card-stagger arm via IO ----------
        if ('IntersectionObserver' in window) {
            var staggerGroups = document.querySelectorAll('.stagger-group');
            staggerGroups.forEach(function (g) {
                new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            g.classList.add('stagger-armed');
                            // Add stagger-card class to direct children
                            Array.prototype.forEach.call(g.children, function (c) { c.classList.add('stagger-card'); });
                        }
                    });
                }, { threshold: 0.15 }).observe(g);
            });
        } else {
            // No IO support: just show everything
            document.querySelectorAll('.stagger-group').forEach(function (g) {
                g.classList.add('stagger-armed');
                Array.prototype.forEach.call(g.children, function (c) { c.classList.add('stagger-card'); });
            });
        }

        // ---------- §5: Count-up animation on .countup elements ----------
        function formatNum(n) {
            return n.toLocaleString('en-PH');
        }
        function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }
        function countUp(el) {
            var target = parseInt(el.getAttribute('data-target') || '0', 10);
            if (target <= 0) return;
            el.classList.add('armed');
            var dur = Math.min(1200, 700 + Math.log10(Math.max(10, target)) * 200);
            var start = performance.now();
            function step(now) {
                var t = Math.min(1, (now - start) / dur);
                var v = Math.floor(easeOutCubic(t) * target);
                el.textContent = formatNum(v);
                if (t < 1) requestAnimationFrame(step);
                else el.textContent = formatNum(target);
            }
            requestAnimationFrame(step);
        }
        if ('IntersectionObserver' in window) {
            document.querySelectorAll('.countup').forEach(function (el) {
                new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            countUp(e.target);
                            e.target._io && e.target._io.disconnect();
                        }
                    });
                }, { threshold: 0.4 }).observe(el);
            });
        } else {
            document.querySelectorAll('.countup').forEach(countUp);
        }

        // ---------- §7a: Carousel crossfade + auto-advance + touch-pause + swipe ----------
        var slider = document.querySelector('.about-slider');
        if (slider) {
            var slides = slider.querySelectorAll('.slide');
            var dots = slider.querySelectorAll('.about-dots .dot');
            var idx = 0, timer = null;
            function show(n) {
                idx = (n + slides.length) % slides.length;
                slides.forEach(function (s, i) { s.classList.toggle('active', i === idx); });
                dots.forEach(function (d, i) { d.classList.toggle('active', i === idx); });
            }
            function tick() { show(idx + 1); }
            function startTimer() { stopTimer(); timer = setInterval(tick, 6000); }
            function stopTimer() { if (timer) { clearInterval(timer); timer = null; } }
            dots.forEach(function (d, i) {
                d.addEventListener('click', function () { show(i); startTimer(); });
            });
            var prev = slider.querySelector('.nav-btn.prev');
            var next = slider.querySelector('.nav-btn.next');
            if (prev) prev.addEventListener('click', function () { show(idx - 1); startTimer(); });
            if (next) next.addEventListener('click', function () { show(idx + 1); startTimer(); });
            // Touch-pause + swipe
            var touchStartX = 0, touchStartY = 0;
            slider.addEventListener('touchstart', function (e) { stopTimer(); touchStartX = e.touches[0].clientX; touchStartY = e.touches[0].clientY; }, { passive: true });
            slider.addEventListener('touchend', function (e) {
                var dx = (e.changedTouches[0].clientX - touchStartX);
                var dy = Math.abs(e.changedTouches[0].clientY - touchStartY);
                if (dy < 30) {
                    if (dx > 40) show(idx - 1);
                    else if (dx < -40) show(idx + 1);
                }
                startTimer();
            });
            // Hover/focus pause
            slider.addEventListener('mouseenter', stopTimer);
            slider.addEventListener('mouseleave', startTimer);
            slider.addEventListener('focusin', stopTimer);
            slider.addEventListener('focusout', startTimer);
            // Initial state
            if (slides.length > 0 && !slides[0].classList.contains('active')) {
                slides.forEach(function (s, i) { s.classList.toggle('active', i === 0); });
            }
            startTimer();
        }

        // ---------- §8: Timeline arm via IO ----------
        if ('IntersectionObserver' in window) {
            document.querySelectorAll('.timeline-svg').forEach(function (svg) {
                new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            svg.classList.add('armed');
                            svg.querySelectorAll('.dot-fill').forEach(function (d, i) {
                                setTimeout(function () { d.classList.add('armed'); }, 400 + i * 200);
                            });
                        }
                    });
                }, { threshold: 0.2 }).observe(svg);
            });
        } else {
            document.querySelectorAll('.timeline-svg').forEach(function (svg) {
                svg.classList.add('armed');
                svg.querySelectorAll('.dot-fill').forEach(function (d) { d.classList.add('armed'); });
            });
        }

        // ---------- §11: 5-step process stepper: mark .done on completed dots ----------
        // The form already manages step state; we just add the visual done class
        // when a step's data-done attr is set or when the step number < current.
        document.querySelectorAll('.ms-step').forEach(function (s, i) {
            // No-op default; the enrollment form's JS will toggle .done on prev steps
            // and the ms-progress .ms-bar width will be set inline.
        });

        // ---------- §14: Save-toast on Save & Continue Later ----------
        function showToast(msg, ms) {
            var t = document.getElementById('bbnihsToast');
            if (!t) {
                t = document.createElement('div');
                t.id = 'bbnihsToast';
                t.className = 'toast';
                t.setAttribute('role', 'status');
                t.setAttribute('aria-live', 'polite');
                document.body.appendChild(t);
            }
            t.textContent = msg;
            requestAnimationFrame(function () { t.classList.add('show'); });
            setTimeout(function () { t.classList.remove('show'); }, ms || 3000);
        }
        // Hook into the existing save button by listening for click on #msSave
        var msSave = document.getElementById('msSave');
        if (msSave) {
            msSave.addEventListener('click', function () {
                // Defer slightly to let the existing localStorage + draft_save fire
                setTimeout(function () {
                    showToast('Saved on this device. Resume anytime by reopening this link.', 3000);
                }, 200);
            });
        }

        // ---------- §15: 3-dot pulse + result slide-down on Check Status ----------
        // The existing code shows a result in #statusResult. We add the dot-pulse
        // markup to the button while waiting, then trigger the result-slide class.
        var statusForm = document.getElementById('statusForm');
        var statusResult = document.getElementById('statusResult');
        if (statusForm && statusResult) {
            // Wrap the result with result-slide class on first show
            statusResult.classList.add('result-slide');
            statusForm.addEventListener('submit', function () {
                var btn = statusForm.querySelector('button[type="submit"]');
                if (btn) {
                    var orig = btn.innerHTML;
                    btn.innerHTML = '<span class="pulse-dots"><span></span><span></span><span></span></span>';
                    btn.disabled = true;
                    setTimeout(function () {
                        btn.innerHTML = orig;
                        btn.disabled = false;
                        statusResult.classList.add('show');
                    }, 900);
                }
            });
        }

        // ---------- §16: Online/offline badge ----------
        var connBadge = document.getElementById('connBadge');
        function updateConn() {
            if (!connBadge) return;
            if (navigator.onLine) {
                connBadge.classList.remove('offline');
                connBadge.innerHTML = '<span class="dot"></span>Online';
            } else {
                connBadge.classList.add('offline');
                connBadge.innerHTML = '<span class="dot"></span>Offline &mdash; saved locally';
            }
        }
        if (connBadge) {
            updateConn();
            window.addEventListener('online', updateConn);
            window.addEventListener('offline', updateConn);
        }
        // Offline-flow reveal
        if ('IntersectionObserver' in window) {
            document.querySelectorAll('.offline-flow').forEach(function (f) {
                new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            f.classList.add('armed');
                            Array.prototype.forEach.call(f.querySelectorAll('.offline-flow-node'), function (n) { n.classList.add('offline-flow-node'); });
                        }
                    });
                }, { threshold: 0.2 }).observe(f);
            });
        }

        // ---------- §20: Map tap-to-load gate ----------
        document.querySelectorAll('.map-gate').forEach(function (gate) {
            function openMap() {
                var sel = gate.getAttribute('data-target') || '.osm-wrap';
                var target = document.querySelector(sel);
                if (!target) return;
                target.classList.add('armed');
                target.removeAttribute('hidden');
                gate.style.display = 'none';
            }
            gate.addEventListener('click', openMap);
            gate.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openMap(); }
            });
        });

        // ---------- §13: Single-open accordion for the Requirements group (FAQ stays multi-open) ----------
        var reqGroup = document.getElementById('requirementsAccordion');
        if (reqGroup) {
            reqGroup.querySelectorAll('details.accordion').forEach(function (d) {
                d.addEventListener('toggle', function () {
                    if (!d.open) return;
                    reqGroup.querySelectorAll('details.accordion').forEach(function (other) {
                        if (other !== d && other.open) other.removeAttribute('open');
                    });
                });
            });
        }

        // ---------- §20: Submit-button morph on success ----------
        var contactForm = document.getElementById('contactForm');
        if (contactForm) {
            var origText = null;
            var submitBtn = contactForm.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) origText = submitBtn.value || submitBtn.textContent;
            var observer = new MutationObserver(function () {
                var result = document.getElementById('contactResult');
                if (!result) return;
                if (result.classList.contains('show') && /received/i.test(result.textContent) && submitBtn) {
                    submitBtn.classList.add('btn', 'submit-morph', 'submit-success');
                    if (submitBtn.tagName === 'INPUT') submitBtn.value = '✓ Sent';
                    else submitBtn.textContent = '✓ Sent';
                    setTimeout(function () {
                        submitBtn.classList.remove('submit-success');
                        if (submitBtn.tagName === 'INPUT') submitBtn.value = origText;
                        else submitBtn.textContent = origText;
                    }, 2200);
                }
            });
            observer.observe(document.getElementById('contactResult') || document.body, { childList: true, subtree: true, attributes: true });
        }

        // ---------- §23: a11y toggle cross-fade is CSS-only via custom-property transitions
        // (see html { transition: ... } above). The PR-1 toggle handlers already
        // mutate body classes which the CSS picks up automatically.

        // ---------- §13 / §10 / §16: Expose helper for inline page JS to call ----------
        window.__bbnihs = window.__bbnihs || {};
        window.__bbnihs.toast = showToast;
    })();
    </script>

    <!-- ===== Tier 3: about_content fetch (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';
        // Fetch the current mission / vision / values_intro from the
        // about_content API. Placeholder text inside <em> tags stays
        // visible until the API responds, so users with JS disabled or
        // a slow connection still see something.
        var PLACES = [
            { section: 'mission',     placeholderId: 'missionBody' },
            { section: 'vision',      placeholderId: 'visionBody' },
            { section: 'values_intro',placeholderId: 'valuesIntroBody' }
        ];
        PLACES.forEach(function (p) {
            var el = document.getElementById(p.placeholderId);
            if (!el) return;
            fetch('about_content_api.php?section=' + encodeURIComponent(p.section), { cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.body && data.body.length > 0) {
                        el.textContent = data.body;
                        el.removeAttribute('data-untouched');
                    }
                })
                .catch(function () { /* keep placeholder */ });
        });
    })();
    </script>

    <!-- ===== Tier 1 PR-4: Engagement signals (non-destructive) ===== -->
    <script>
    (function () {
        'use strict';
        // Section IDs that count as engagement events. Same set PR-2 uses for search.
        // Mirrors nav anchors + FAQ section + action targets.
        var SECTIONS = ['glance', 'community', 'about', 'admissions', 'enroll', 'features', 'contact', 'who'];
        var sent = {};   // sent[sectionId] = true
        var entered = {}; // entered[sectionId] = timestamp (ms)

        function fire(section, duration) {
            if (sent[section]) return;
            sent[section] = true;
            try {
                var body = JSON.stringify({ section: section, duration: duration || 0 });
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('engagement.php', new Blob([body], { type: 'application/json' }));
                } else {
                    fetch('engagement.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true });
                }
            } catch (e) { /* silent: engagement is best-effort, never block UX */ }
        }

        function setup() {
            if (!('IntersectionObserver' in window)) return;
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    var id = entry.target && entry.target.id;
                    if (SECTIONS.indexOf(id) === -1) return;
                    if (entry.isIntersecting) {
                        entered[id] = Date.now();
                    } else if (entered[id]) {
                        // Fired only after at least 1s of dwell to filter out glance-bys
                        var dwell = Date.now() - entered[id];
                        if (dwell >= 1000) fire(id, dwell);
                        delete entered[id];
                    }
                });
            }, { threshold: 0.25 });
            SECTIONS.forEach(function (id) {
                var el = document.getElementById(id);
                if (el) io.observe(el);
            });
        }

        // Page-hide: fire any sections the user is still dwelling on
        function flushOnHide() {
            var now = Date.now();
            for (var id in entered) {
                if (Object.prototype.hasOwnProperty.call(entered, id)) {
                    var dwell = now - entered[id];
                    if (dwell >= 1000) fire(id, dwell);
                    delete entered[id];
                }
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setup);
        } else {
            setup();
        }
        window.addEventListener('pagehide', flushOnHide);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') flushOnHide();
        });
    })();
    </script>

</body>
</html>
