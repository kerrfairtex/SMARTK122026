<?php
/**
 * Public Landing Page — Batu-Batu National Integrated High School
 *
 * A standalone institutional page separate from the RosarioSIS login system.
 * Features real photography and PSA-verified community statistics.
 *
 * @package SmartCampus
 * @since 1.0
 */

// Read RosarioSIS config for school identity (optional — falls back to defaults)
$school_name = 'BATU-BATU NATIONAL INTEGRATED HIGH SCHOOL';
$school_short_name = 'BBNIHS';
$school_id = '305053';
$theme = 'FlatSIS';

// Attempt to read from RosarioSIS config if available (with error handling)
$rosariosis_config = dirname(__FILE__) . '/../config.inc.php';
$rosariosis_warehouse = dirname(__FILE__) . '/../Warehouse.php';
if (file_exists($rosariosis_config) && file_exists($rosariosis_warehouse)) {
    try {
        require_once $rosariosis_config;
        require_once $rosariosis_warehouse;
        $school_name = Config('TITLE') ?: $school_name;
        $school_short_name = Config('NAME') ?: $school_short_name;
        $theme = Config('THEME') ?: $theme;
    } catch (Exception $e) {
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
            background-color: var(--navy); /* Fallback if image fails */
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
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
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
            background-color: var(--navy); /* Fallback color */
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($school_name); ?></h1>
            <p class="tagline">Learning Beyond the Classroom</p>
            <p class="location">Batu-Batu &bull; Panglima Sugala &bull; Tawi-Tawi &bull; BARMM</p>
            <div class="hero-buttons">
                <a href="#about" class="btn btn-outline">Explore Our School</a>
                <a href="../index.php" class="btn btn-primary">Smart Campus Login</a>
            </div>
        </div>
    </section>

    <!-- School at a Glance -->
    <section class="glance">
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
    <section class="community">
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
    <section class="about">
        <div class="container">
            <div class="about-content">
                <h2 class="section-title">About the School</h2>
                <p><?php echo htmlspecialchars($school_name); ?> is a public integrated high school serving learners in the Batu-Batu community of Panglima Sugala, Tawi-Tawi.</p>
                <p>As part of the Bangsamoro Autonomous Region in Muslim Mindanao (BARMM), the school is committed to providing accessible education rooted in the local island community.</p>
                <p>The school is listed in the DepEd infrastructure inventory with ongoing projects to support learning facilities.</p>
            </div>
        </div>
    </section>

    <!-- Academics -->
    <section class="academics">
        <div class="container">
            <h2 class="section-title">Academic Programs</h2>
            <p class="section-subtitle">K-12 basic education</p>
            <div class="academics-grid">
                <div class="academic-card">
                    <h3>Junior High School</h3>
                    <p>Grades 7–10</p>
                </div>
                <div class="academic-card">
                    <h3>Senior High School</h3>
                    <p>Grades 11–12</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Campus -->
    <section class="smart-campus">
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
                <a href="../index.php" class="btn btn-primary">Enter Smart Campus</a>
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

    <!-- Contact -->
    <section class="contact">
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
        <p class="license-note">Community population data: PSA 2024 POPCAN. verify before republishing.</p>
    </footer>

</body>
</html>
