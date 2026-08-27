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
$school_name = 'BATU-BATU NATIONAL INTEGRATED HIGH SCHOOL';
$school_short_name = 'BBNIHS';
$school_id = '305053';
$theme = 'FlatSIS';

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
                    $school_name = Config('TITLE') ?: $school_name;
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
            --accent: #0073aa;
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .gallery-card {
            background: var(--navy-light);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .gallery-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }

        .gallery-card .caption {
            padding: 1rem 1.25rem 1.25rem;
        }

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

        /* Contact */
        .contact {
            background: var(--navy-deep);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .contact-item h4 {
            color: var(--white);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .contact-item p {
            color: var(--gray-500);
            font-size: 0.95rem;
        }

        /* Footer */
        footer {
            background: var(--navy-deep);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 2rem;
            text-align: center;
        }

        footer p {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        footer a {
            color: var(--gray-300);
            text-decoration: none;
        }

        footer a:hover {
            color: var(--white);
        }

        footer .license-note {
            margin-top: 1rem;
            font-size: 0.7rem;
            color: var(--gray-500);
            font-style: italic;
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
        <a href="#features">Features</a>
        <a href="#contact">Contact</a>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($school_name); ?></h1>
            <p class="tagline">Learning, growing, and building the future of the Turtle Islands</p>
            <p class="location">Batu-Batu &bull; Turtle Islands &bull; Tawi-Tawi &bull; BARMM</p>
            <p class="supporting">A modern school community serving learners in Batu-Batu, Turtle Islands, Tawi-Tawi, supported by SmartCampus K&ndash;12 digital services.</p>
            <div class="hero-buttons">
                <a href="#about" class="btn btn-primary">Discover Our School</a>
                <a href="login.php" class="btn btn-outline">SmartCampus Portal</a>
                <a href="#admissions" class="btn btn-outline">Admissions / Enrollment</a>
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
                <h2 class="section-title">About Batu-Batu NIHS</h2>
                <img src="assets/images/img-campus.jpg" alt="Batu-Batu National Integrated High School campus" style="width:100%;max-width:700px;margin:1.5rem auto 1rem;border-radius:8px;display:block;box-shadow:0 4px 20px rgba(0,0,0,0.3)">
                <div class="about-slider" id="aboutSlider">
                    <div class="about-slides">
                        <img class="active" src="assets/images/Batu-batu1.jpeg" alt="Batu-Batu National Integrated High School">
                        <img src="assets/images/Batu-batu2.jpeg" alt="Batu-Batu National Integrated High School">
                        <img src="assets/images/Batu-batu3.jpeg" alt="Batu-Batu National Integrated High School">
                        <img src="assets/images/Batu-batu4.jpeg" alt="Batu-Batu National Integrated High School">
                    </div>
                    <button class="nav-btn prev" aria-label="Previous photo">&#10094;</button>
                    <button class="nav-btn next" aria-label="Next photo">&#10095;</button>
                    <div class="about-dots">
                        <span class="dot active" data-i="0"></span>
                        <span class="dot" data-i="1"></span>
                        <span class="dot" data-i="2"></span>
                        <span class="dot" data-i="3"></span>
                    </div>
                </div>

                <p><strong><?php echo htmlspecialchars($school_name); ?></strong> (School ID <?php echo $school_id; ?>) is a public National Integrated High School in Batu-Batu, Turtle Islands, Tawi-Tawi, within the Bangsamoro Autonomous Region in Muslim Mindanao (BARMM).</p>

                <p><strong>Location:</strong> Batu-Batu is part of the Turtle Islands, a group of islands in the southernmost municipality of Tawi-Tawi, near the Philippines&ndash;Malaysia border. The school serves learners from the island community and surrounding barangays.</p>

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

                <p><strong>Community role:</strong> Beyond instruction, the school is a hub for community learning, DepEd programs, and local development in the Turtle Islands, contributing to the social and economic life of the municipality.</p>
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
            <h2>Rooted in Our Community</h2>
            <p>From the sea to the classroom, our community is shaped by knowledge, work, and cooperation.</p>
        </div>
    </section>

    <!-- Island Identity -->
    <section class="island">
        <div class="container">
            <div class="island-content">
                <h2 class="section-title">Rooted in Tawi-Tawi</h2>
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

    <!-- Campus Life Gallery -->
    <section class="gallery">
        <div class="container">
            <h2 class="section-title">Campus Life</h2>
            <p class="section-subtitle">Glimpses of learning and community at Batu-Batu NIHS</p>
            <div class="gallery-grid">
                <div class="gallery-card">
                    <img src="assets/images/img-education.jpg" alt="Students engaged in learning">
                    <div class="caption">
                        <h4>In the Classroom</h4>
                        <p>Day-to-day teaching and learning across the K-12 basic education program.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-01.jpeg" alt="School grounds and surroundings">
                    <div class="caption">
                        <h4>School Grounds</h4>
                        <p>The campus setting in the island community of Batu-Batu, Panglima Sugala.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-03.jpeg" alt="Students on campus">
                    <div class="caption">
                        <h4>Students</h4>
                        <p>Learners of Batu-Batu National Integrated High School.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-04.jpeg" alt="Campus activity">
                    <div class="caption">
                        <h4>Campus Activity</h4>
                        <p>Moments from school life and student activities.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-05.jpeg" alt="School facility">
                    <div class="caption">
                        <h4>School Facility</h4>
                        <p>Learning spaces that support teaching and administration.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-06.jpeg" alt="Community and school">
                    <div class="caption">
                        <h4>Community &amp; School</h4>
                        <p>The school and its island community, shaped by the sea and cooperation.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-08.jpeg" alt="Campus view">
                    <div class="caption">
                        <h4>Campus View</h4>
                        <p>A view of the school environment in Tawi-Tawi.</p>
                    </div>
                </div>
                <div class="gallery-card">
                    <img src="assets/images/img-campus.jpg" alt="Batu-Batu National Integrated High School campus">
                    <div class="caption">
                        <h4>Our Campus</h4>
                        <p>Batu-Batu National Integrated High School.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Contact</h2>
            <div class="contact-grid">
                <div class="contact-item">
                    <h4>Address</h4>
                    <p>Batu-Batu, Panglima Sugala<br>Tawi-Tawi, BARMM, Philippines</p>
                </div>
                <div class="contact-item">
                    <h4>School ID</h4>
                    <p>305053</p>
                </div>
                <div class="contact-item">
                    <h4>Region</h4>
                    <p>BARMM (Bangsamoro Autonomous Region in Muslim Mindanao)</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 <a href="https://www.facebook.com/share/1DYQyL1mhS/" target="_blank" rel="noopener">Kerr Fairtex</a> and Company</p>
        <p>Powered by <a href="https://www.rosariosis.org" target="_blank" rel="noopener">RosarioSIS</a> — Open Source Student Information System</p>
        <p style="margin-top: 1rem; font-size: 0.75rem;">School ID 305053 &bull; DepEd Philippines &bull; Batu-Batu, Panglima Sugala, Tawi-Tawi</p>
        <p class="license-note">Community population data: PSA 2024 POPCEN. Verify image licenses before republishing.</p>
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
    </script>

</body>
</html>
