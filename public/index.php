<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batu-Batu National High School — SmartCampus K-12</title>
    <meta name="description" content="Batu-Batu National High School, a public K-12 school in Panglima Sugala, Tawi-Tawi, BARMM. Serving Batu-Batu since its conversion to a national high school in 1982.">
    <link rel="stylesheet" href="public/css/tokens.css">
    <link rel="stylesheet" href="public/css/base.css">
    <link rel="stylesheet" href="public/css/components.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%23F4B400'/%3E%3Ctext x='16' y='22' font-size='20' text-anchor='middle' font-family='serif' font-weight='700' fill='%230A1420'%3EB%3C/text%3E%3C/svg%3E">
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>

    <!-- ============================================================
         ACCESSIBILITY TOOLBAR (sticky top)
         Per Part 2: these toggles gate the 3D tier.
         ============================================================ -->
    <div class="a11y-bar" role="toolbar" aria-label="Accessibility and connection">
        <span class="a11y-bar__label">Accessibility:</span>
        <button class="a11y-bar__btn" id="a11yHc" type="button" aria-pressed="false">High contrast</button>
        <button class="a11y-bar__btn" id="a11yDys" type="button" aria-pressed="false">Dyslexia-friendly font</button>
        <button class="a11y-bar__btn" id="a11yLarge" type="button" aria-pressed="false">Larger text</button>
        <span class="a11y-bar__conn" id="connBadge" data-state="online"><span class="dot"></span>Online</span>
    </div>

    <!-- ============================================================
         TOP NAVIGATION
         ============================================================ -->
    <nav class="top-nav" aria-label="Primary">
        <a href="#home" class="top-nav__brand">BBNIHS</a>
        <div class="top-nav__links">
            <a class="top-nav__link" href="#glance">At a Glance</a>
            <a class="top-nav__link" href="#community">Community</a>
            <a class="top-nav__link" href="#about">About</a>
            <a class="top-nav__link" href="#academics">Academics</a>
            <a class="top-nav__link" href="#admissions">Admissions</a>
            <a class="top-nav__link" href="#features">Features</a>
            <a class="top-nav__link" href="#contact">Contact</a>
        </div>
        <button class="a11y-bar__btn" type="button" id="searchLaunch" aria-label="Open search (/)">Search /</button>
    </nav>

    <main id="main">
        <!-- ============================================================
             HERO (Part 3 §1)
             Tier 2: Three.js tide horizon (lazy-loaded)
             Tier 0/1: CSS gradient fallback
             ============================================================ -->
        <section class="hero" id="home">
            <div class="hero__fallback" aria-hidden="true"></div>
            <div class="hero__canvas-wrap" aria-hidden="true"></div>
            <time class="hero__clock" id="heroClock" datetime="">Tawi-Tawi <span id="clockTime">--:--:--</span></time>
            <div class="hero__content">
                <p class="hero__eyebrow">Batu-Batu · Panglima Sugala · Tawi-Tawi · BARMM</p>
                <h1 class="hero__title">BATU-BATU</h1>
                <p class="hero__sub">Learning, growing, and building the future of Tawi-Tawi</p>
                <p class="hero__location">Barangay Batu-Batu, Poblacion &middot; Panglima Sugala &middot; Tawi-Tawi</p>
                <p class="hero__credibility reveal">A public K-12 school serving the Batu-Batu community since its conversion to a national high school in 1982 (Batas Pambansa Blg. 290). The 3D scene above renders real Tawi-Tawi island geometry from OpenStreetMap.</p>
                <div class="hero__actions">
                    <a href="#about" class="btn btn--primary">Discover Our School</a>
                    <a href="#enroll-form" class="btn btn--ghost">Start Enrollment</a>
                    <a href="#admissions" class="btn btn--ghost">Check Application Status</a>
                    <a href="#contact" class="btn btn--ghost">Contact</a>
                </div>
            </div>
        </section>

        <div class="horizon-line" aria-hidden="true"></div>

        <!-- ============================================================
             AT A GLANCE (Part 3 §2)
             3D-tilt stat tiles (CSS perspective + JS device-orientation),
             animated count-up on population numbers below.
             ============================================================ -->
        <section id="glance" class="reveal">
            <div class="container">
                <h2 class="section-title">School at a Glance</h2>
                <p class="section-subtitle">Verified institutional information</p>
                <div class="tile-grid">
                    <div class="tile">
                        <p class="tile__label">School ID</p>
                        <p class="tile__value">305053</p>
                        <p class="tile__source">as provided by the school</p>
                    </div>
                    <div class="tile">
                        <p class="tile__label">Name</p>
                        <p class="tile__value">Batu-Batu National High School</p>
                        <p class="tile__source">Batas Pambansa Blg. 290 (1982)</p>
                    </div>
                    <div class="tile">
                        <p class="tile__label">Location</p>
                        <p class="tile__value">Batu-Batu, Poblacion</p>
                        <p class="tile__source">Panglima Sugala, Tawi-Tawi</p>
                    </div>
                    <div class="tile">
                        <p class="tile__label">Region</p>
                        <p class="tile__value">BARMM</p>
                        <p class="tile__source">Southernmost province</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             COMMUNITY (Part 3 §3)
             PSA-sourced population stats (animated count-up),
             low-poly island map signature in the background.
             ============================================================ -->
        <section id="community" class="surface-tide reveal">
            <div class="container">
                <h2 class="section-title">Our Community</h2>
                <p class="section-subtitle">Philippine Statistics Authority, 2024 Census &middot; per PSA / provincial cultural profile</p>
                <div class="counter-grid">
                    <div class="counter">
                        <p class="counter__number" data-target="3936">3,936</p>
                        <p class="counter__label">Barangay Batu-Batu</p>
                        <p class="counter__context">Barangay population</p>
                    </div>
                    <div class="counter">
                        <p class="counter__number" data-target="52657">52,657</p>
                        <p class="counter__label">Panglima Sugala</p>
                        <p class="counter__context">Municipality population</p>
                    </div>
                    <div class="counter">
                        <p class="counter__number" data-target="482645">482,645</p>
                        <p class="counter__label">Tawi-Tawi</p>
                        <p class="counter__context">Province population</p>
                    </div>
                </div>
                <p style="margin-top: var(--space-4); color: var(--foam); opacity: 0.8; max-width: 60ch;">
                    Tawi-Tawi is the Philippines&rsquo; southernmost province &mdash; a region of islands, maritime culture, and diverse communities including the Sama, Jama Mapun, Badjao, and Tausug peoples.
                </p>
                <p class="form-note" style="margin-top: var(--space-4); margin-bottom: var(--space-3);">Real photos from the Tawi-Tawi archipelago. The first is vision-verified; the others are region-tagged but pending verification.</p>
                <div class="photo-strip">
                    <figure><img src="assets/images/tawi-bongao.jpg" alt="Bongao, Tawi-Tawi &mdash; stilt houses over water with mountains in the distance (vision-verified)" loading="lazy"></figure>
                    <figure><img src="assets/images/Batu-batu2.jpeg" alt="Aerial view of a Tawi-Tawi stilt-house coastal village (vision-verified)" loading="lazy"></figure>
                </div>
            </div>
        </section>

        <div class="horizon-line" aria-hidden="true"></div>

        <!-- ============================================================
             ABOUT (Part 3 §4)
             Mission / Vision adapted from DepEd frame, BP 290 founding
             fact, school history timeline. Faculty + leadership are
             labeled empty states per spec.
             ============================================================ -->
        <section id="about" class="reveal">
            <div class="container">
                <h2 class="section-title">About Batu-Batu National High School</h2>
                <p class="section-subtitle">Mission, vision, and history grounded in citable sources</p>

                <div class="about-card">
                    <p class="about-card__heading">Mission</p>
                    <p class="about-card__body">To protect and promote the right of every Filipino to quality, equitable, culture-based, and complete basic education that makes them globally competitive, fosters a deep sense of patriotism and national pride, and prepares them for productive civic engagement and meaningful participation in the broader community.</p>
                    <p class="about-card__cite">Adapted from the DepEd national Mission/Vision (<a href="https://www.deped.gov.ph/about-deped/vision-mission-core-values-and-mandate" target="_blank" rel="noopener">deped.gov.ph</a>), pending the school&rsquo;s own board-approved statement.</p>
                </div>

                <div class="about-card">
                    <p class="about-card__heading">Vision</p>
                    <p class="about-card__body">Filipinos who love their country and whose values and competencies enable them to realize their full potential and contribute meaningfully to building the nation.</p>
                    <p class="about-card__cite">Source: <a href="https://www.deped.gov.ph/about-deped/vision-mission-core-values-and-mandate" target="_blank" rel="noopener">DepEd Vision, Mission, Core Values and Mandate</a>.</p>
                </div>

                <div class="about-card">
                    <p class="about-card__heading">Core Values</p>
                    <p class="about-card__body">Maka-Diyos (God-loving) &middot; Maka-tao (People-oriented) &middot; Makakalikasan (Nature-loving) &middot; Makabansa (Nation-loving)</p>
                </div>

                <h3 style="margin-top: var(--space-5);">School History</h3>
                <p style="margin-bottom: var(--space-3); color: var(--foam);">
                    Batu-Batu National High School traces its roots to a barangay high school serving the Batu-Batu community, and was formally converted into a national high school on <strong>14 November 1982</strong> by Batas Pambansa Blg. 290. The full history is captured in the timeline below. The photos on this page are the visual record: <strong>verified photos</strong> are vision-checked; <strong>unverified photos</strong> are presented as-provided and will be moved to the verified section once the school registrar confirms the subject of each.
                </p>

                <!-- ============================================================
                     PHOTO BANK (merged from former "Additional Photo Bank — Unverified"
                     and "Community Photo Bank" blocks). All photos live together
                     as one gallery, with verified photos at the top and unverified
                     photos below under a clear "As provided" callout.
                     ============================================================ -->
                <h4 style="margin-top: var(--space-4); color: var(--sand);">Verified photos &mdash; campus and community</h4>
                <p class="form-note" style="margin-bottom: var(--space-3);">These four photos are vision-verified and depict the BBNIHS campus, learners, and the surrounding Tawi-Tawi stilt-house coastal community.</p>
                <div class="photo-strip">
                    <figure>
                        <img src="assets/images/Batu-batu1.jpeg" alt="Batu-Batu National High School campus building, two-story concrete with cream and blue trim, trellised grounds in front." loading="lazy">
                        <figcaption>Batu-Batu National High School &mdash; the campus building as documented this academic year.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/Batu-batu2.jpeg" alt="Aerial view of a Tawi-Tawi stilt-house coastal village with mountains in the background." loading="lazy">
                        <figcaption>An aerial view of the surrounding coastal community, showing the stilt-house architecture typical of Tawi-Tawi.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/Batu-batu3.jpeg" alt="BBNIHS classroom or activity room with learners during a school event, tropical ceiling fans visible." loading="lazy">
                        <figcaption>A BBNIHS classroom or activity room during a school event.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/Batu-batu4.jpeg" alt="Group photo of BBNIHS learners in a school setting." loading="lazy">
                        <figcaption>A BBNIHS learner group photo, taken on campus.</figcaption>
                    </figure>
                </div>

                <h4 style="margin-top: var(--space-4); color: var(--sand);">Photo bank &mdash; as provided (unverified)</h4>
                <p style="margin-bottom: var(--space-3); padding: var(--space-3); background: rgba(232, 115, 74, 0.08); border-left: 3px solid var(--reef-coral); border-radius: 2px; color: var(--sand);">
                    <strong style="color: var(--reef-coral);">Unverified &mdash; pending school confirmation.</strong>
                    These photos are presented as provided to the SmartCampus team. Their authenticity, date, and connection to Batu-Batu National High School or Tawi-Tawi have not been independently verified. They will be moved to the verified section above once the school registrar confirms the subject of each photo. Each is captioned with the filename, the likely subject based on the filename, and the confirmation question we need the school to answer.
                </p>
                <div class="photo-strip">
                    <figure class="unverified">
                        <img src="assets/images/bbnihs-baccalaureate.jpeg" alt="School event photo, as provided &mdash; unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests a Baccalaureate Mass / moving-up ceremony. <em>To confirm: which graduating batch and academic year?</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/bbnihs-graduation.jpeg" alt="School event photo, as provided &mdash; unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests a graduation ceremony. <em>To confirm: which batch and date, and is the venue the BBNIHS gym or another location?</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/bbnihs-legacy.jpg" alt="School event photo, as provided &mdash; unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests a legacy / alumni event. <em>To confirm: which alumni batch, and is the photo BBNIHS-specific?</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/bbnihs-scholarship.jpeg" alt="School event photo, as provided &mdash; unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests a scholarship / awards event. <em>To confirm: which scholarship program and school year?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/bbnihs-staff.jpeg" alt="BBNIHS faculty and staff group photo." loading="lazy">
                        <figcaption>BBNIHS faculty and staff group photo (vision-verified). Individual names are listed in the faculty directory, pending confirmation from the school registrar.</figcaption>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/classroom1.jpeg" alt="Classroom photo, as provided &mdash; unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Classroom interior. <em>To confirm: which grade level and subject, and is this BBNIHS or another school?</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/classroom2.jpeg" alt="Classroom photo, as provided &mdash; unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Classroom interior. <em>To confirm: which grade level and subject, and is this BBNIHS or another school?</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-01.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-02.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-03.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-04.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-05.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-06.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-08.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-09.jpeg" alt="Image as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-campus.jpg" alt="Campus photo as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests a campus shot. <em>To confirm: is this BBNIHS or another school, and is the date recent?</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/img-education.jpg" alt="Education photo as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests an education-themed photo. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/tawi-bongao.jpg" alt="Bongao, Tawi-Tawi &mdash; stilt houses over water with mountains in the distance." loading="lazy">
                        <figcaption>Bongao, Tawi-Tawi &mdash; the provincial capital (vision-verified). Tawi-Tawi is the southernmost province of the Philippines, an archipelago of over 100 islands.</figcaption>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/tawi-bajau-children.jpeg" alt="Bajau / Sama children, Tawi-Tawi &mdash; as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests Bajau or Sama children. <em>To confirm: location, year, and source. (Important: visual depictions of indigenous children in Tawi-Tawi must be handled with care and with community consent for public use.)</em></p>
                    </figure>
                    <figure class="unverified">
                        <img src="assets/images/tawi-boatrace.jpeg" alt="Boat race, Tawi-Tawi &mdash; as provided, unverified." loading="lazy">
                        <figcaption class="unverified-tag">As provided &middot; unverified</figcaption>
                        <p>Filename suggests a boat race. <em>To confirm: event name, date, and whether it took place in Tawi-Tawi or another province.</em></p>
                    </figure>
                </div>

                <h4 style="margin-top: var(--space-4); color: var(--sand);">Timeline</h4>
                <ol class="history-timeline">
                    <li class="history-timeline__item reveal">
                        <p class="history-timeline__year">1966 (candidate)</p>
                        <p class="history-timeline__text">A barangay high school serving the Batu-Batu community is established. The school's official Facebook page handle, <code>facebook.com/BatuBatu1966</code>, points to this as a likely founding year. <em>Pending confirmation by the school head before this date is published as a fact.</em></p>
                    </li>
                    <li class="history-timeline__item reveal">
                        <p class="history-timeline__year">14 November 1982</p>
                        <p class="history-timeline__text">Batas Pambansa Blg. 290, &ldquo;An Act Converting the Batu-Batu Barangay High School in the Municipality of Balimbing, Province of Tawi-Tawi, into a National High School, to be known as Batu-Batu National High School,&rdquo; takes effect. The school is formally renamed and brought under the national school system, with funding and oversight from the Department of Education, Culture and Sports (DECS, the predecessor of DepEd).</p>
                        <p class="history-timeline__cite">Source: Supreme Court E-Library / thecorpusjuris.com transcription of B.P. Blg. 290.</p>
                    </li>
                    <li class="history-timeline__item reveal">
                        <p class="history-timeline__year">4 July 1991</p>
                        <p class="history-timeline__text">The municipality is renamed from <strong>Balimbing</strong> to <strong>Panglima Sugala</strong> by Muslim Mindanao Autonomy Act No. 7, enacted as part of the broader administrative reorganization of the autonomous region. The school retains the &ldquo;Batu-Batu&rdquo; in its name because the barangay of Batu-Batu itself was not renamed &mdash; only the surrounding municipality.</p>
                    </li>
                    <li class="history-timeline__item reveal">
                        <p class="history-timeline__year">Pre-1979</p>
                        <p class="history-timeline__text">Batu-Batu itself served as the provincial capital of Tawi-Tawi before the seat of provincial government was transferred to Bongao in 1979. The school is therefore older than the conversion to a national high school in 1982, and predates the post-1979 provincial reorganization. This is part of why the community context of BBNIHS includes both the local (Batu-Batu, the former capital) and the provincial (Tawi-Tawi, the southernmost province) layers.</p>
                    </li>
                    <li class="history-timeline__item reveal">
                        <p class="history-timeline__year">Today</p>
                        <p class="history-timeline__text">Batu-Batu National High School is a recognized K-12 public high school (TESDA registry) serving a rural maritime population under the Tawi-Tawi Schools Division Office. It offers Grades 7 through 12, with a track and strand structure to be confirmed by the school registrar. The SmartCampus K-12 digital platform provides online enrollment, attendance, grade, schedule, library, announcement, and parent communication services to the school community, while still respecting the &ldquo;submit physically at the school if connectivity is limited&rdquo; fallback that has been the school's de facto operational policy since long before the platform existed.</p>
                    </li>
                </ol>

                <div class="empty-state" style="margin-top: var(--space-4);">
                    Faculty directory &mdash; coming soon, pending confirmation from the school registrar.
                </div>
                <div class="empty-state">
                    School leadership roster &mdash; coming soon, pending confirmation from the school head.
                </div>
            </div>
        </section>

        <div class="horizon-line" aria-hidden="true"></div>

        <!-- ============================================================
             ACADEMICS (Part 3 §5)
             Grade-level cards, no subject invention (per spec).
             ============================================================ -->
        <section id="academics" class="surface-tide reveal">
            <div class="container">
                <h2 class="section-title">Academic Programs</h2>
                <p class="section-subtitle">K-12 public high school &mdash; Junior High School (Grades 7-10) and Senior High School (Grades 11-12)</p>
                <div class="grade-grid">
                    <div class="grade"><p class="grade__level">Grade 7</p></div>
                    <div class="grade"><p class="grade__level">Grade 8</p></div>
                    <div class="grade"><p class="grade__level">Grade 9</p></div>
                    <div class="grade"><p class="grade__level">Grade 10</p></div>
                    <div class="grade"><p class="grade__level">Grade 11</p></div>
                    <div class="grade"><p class="grade__level">Grade 12</p></div>
                </div>
                <p class="form-note" style="margin-top: var(--space-3);">Actual grade-level availability and track/strand offerings are configured from the SmartCampus dashboard rather than hardcoded here. School-configured (pending confirmation from the school registrar).</p>
            </div>
        </section>

        <div class="horizon-line" aria-hidden="true"></div>

        <!-- ============================================================
             ADMISSIONS (Part 3 §6)
             The single best case for numbered sequence treatment per
             spec: enrolment is a real ordered journey. Unified visual
             language: dot-and-line, with the SVG-drawn timeline at top
             and the wizard stepper at the form.
             ============================================================ -->
        <section id="admissions" class="reveal">
            <div class="container">
                <h2 class="section-title">Admissions &amp; Enrollment</h2>
                <p class="section-subtitle">For SY 2026&ndash;2027 &middot; Application status, requirements, and the enrollment wizard</p>

                <!-- Status overview -->
                <div class="tile-grid" style="margin-bottom: var(--space-4);">
                    <div class="tile">
                        <p class="tile__label">Status</p>
                        <p class="tile__value" style="color: #4ade80;">OPEN</p>
                    </div>
                    <div class="tile">
                        <p class="tile__label">School Year</p>
                        <p class="tile__value">2026&ndash;2027</p>
                    </div>
                    <div class="tile">
                        <p class="tile__label">Period</p>
                        <p class="tile__value">1 Jun &ndash; 15 Aug 2026</p>
                    </div>
                    <div class="tile">
                        <p class="tile__label">Classes Begin</p>
                        <p class="tile__value">17 Aug 2026</p>
                    </div>
                </div>

                <!-- 7-state status timeline (real sequence, numbered) -->
                <h3>Application Status</h3>
                <div class="status-bar" role="list">
                    <div class="status-step" data-step="Submitted" role="listitem">Submitted</div>
                    <div class="status-step" data-step="Under Review" role="listitem">Under Review</div>
                    <div class="status-step" data-step="Documents Needed" role="listitem">Documents Needed</div>
                    <div class="status-step" data-step="Verified" role="listitem">Verified</div>
                    <div class="status-step" data-step="Approved" role="listitem">Approved</div>
                    <div class="status-step" data-step="Enrolled" role="listitem">Enrolled</div>
                    <div class="status-step" data-step="Rejected" role="listitem">Rejected</div>
                </div>

                <!-- 5-step enrollment wizard -->
                <h3 id="enroll-form" style="margin-top: var(--space-5);">Start Your Enrollment</h3>
                <div class="wizard">
                    <div class="wizard__progress" style="--progress: 0%;"></div>
                    <div class="wizard__steps">
                        <div class="wizard__step wizard__step--active">1</div>
                        <div class="wizard__step">2</div>
                        <div class="wizard__step">3</div>
                        <div class="wizard__step">4</div>
                        <div class="wizard__step">5</div>
                    </div>

                    <form id="wizardForm" style="margin-top: var(--space-4);">
                        <div data-wizard-step="0">
                            <h4>Learner Information</h4>
                            <div class="form-field">
                                <label for="lname">Learner full name *</label>
                                <input type="text" id="lname" name="lname" required placeholder="e.g. Aisha S. Indal">
                            </div>
                            <div class="form-field">
                                <label for="bdate">Date of birth *</label>
                                <input type="date" id="bdate" name="bdate" required>
                            </div>
                            <div class="form-field">
                                <label for="laddress">Home address *</label>
                                <input type="text" id="laddress" name="laddress" required placeholder="Batu-Batu, Poblacion">
                            </div>
                            <div class="form-field">
                                <label for="etype">Enrollment type *</label>
                                <select id="etype" name="etype" required>
                                    <option value="">Select&hellip;</option>
                                    <option value="New">New Student</option>
                                    <option value="Transferee">Transferee</option>
                                    <option value="Returning">Returning Learner</option>
                                </select>
                            </div>
                        </div>
                        <div data-wizard-step="1" hidden>
                            <h4>Parent / Guardian</h4>
                            <div class="form-field">
                                <label for="pname">Parent / guardian name *</label>
                                <input type="text" id="pname" name="pname" required>
                            </div>
                            <div class="form-field">
                                <label for="pcontact">Contact number *</label>
                                <input type="tel" id="pcontact" name="pcontact" required placeholder="09XX-XXX-XXXX">
                            </div>
                            <div class="form-field">
                                <label for="pemail">Email (optional)</label>
                                <input type="email" id="pemail" name="pemail">
                            </div>
                        </div>
                        <div data-wizard-step="2" hidden>
                            <h4>Previous School</h4>
                            <div class="form-field">
                                <label for="pschool">Previous school name (if any)</label>
                                <input type="text" id="pschool" name="pschool" placeholder="Leave blank for new students">
                            </div>
                            <div class="form-field">
                                <label for="plastgrade">Last grade completed *</label>
                                <input type="text" id="plastgrade" name="plastgrade" required>
                            </div>
                        </div>
                        <div data-wizard-step="3" hidden>
                            <h4>Documents</h4>
                            <p class="form-note">If online upload isn't possible, you may submit documents physically at the school.</p>
                            <div class="form-field"><label><input type="checkbox" name="doc_bc"> Birth Certificate (PSA / NSO)</label></div>
                            <div class="form-field"><label><input type="checkbox" name="doc_rc"> Report Card</label></div>
                            <div class="form-field"><label><input type="checkbox" name="doc_tc"> Transfer Credentials</label></div>
                            <div class="form-field"><label><input type="checkbox" name="doc_other"> Other Required Documents</label></div>
                        </div>
                        <div data-wizard-step="4" hidden>
                            <h4>Review &amp; Submit</h4>
                            <p>No payment is collected here. The school will contact you to complete enrollment.</p>
                            <div id="wizardResult" role="status" aria-live="polite" style="margin-top: var(--space-3);"></div>
                        </div>

                        <div style="display: flex; gap: var(--space-2); margin-top: var(--space-4);">
                            <button type="button" class="btn btn--ghost" data-wizard-prev>Back</button>
                            <button type="button" class="btn btn--primary" data-wizard-next>Next</button>
                            <button type="submit" class="btn btn--primary" style="margin-left: auto; display: none;" id="wizardSubmit">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <div class="horizon-line" aria-hidden="true"></div>

        <!-- ============================================================
             FEATURES (Part 3 §7)
             SmartCampus module grid + a11y bar already lives at the top.
             ============================================================ -->
        <section id="features" class="surface-tide reveal">
            <div class="container">
                <h2 class="section-title">SmartCampus K-12</h2>
                <p class="section-subtitle">Digital school services for students, teachers, parents, and administrators</p>
                <div class="module-grid">
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128203;</div><p class="module__name">Student Information</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128221;</div><p class="module__name">Enrollment</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#9989;</div><p class="module__name">Attendance</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128202;</div><p class="module__name">Grades</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128197;</div><p class="module__name">Class Schedules</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128226;</div><p class="module__name">Announcements</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128214;</div><p class="module__name">Library</p></div>
                    <div class="module"><div class="module__dot"></div><div class="module__icon">&#128106;</div><p class="module__name">Parent Communication</p></div>
                </div>
                <p style="text-align: center; margin-top: var(--space-4);">
                    <a href="login.php" class="btn btn--primary">Enter Smart Campus</a>
                </p>
            </div>
        </section>

        <div class="horizon-line" aria-hidden="true"></div>

        <!-- ============================================================
             CONTACT (Part 3 §8)
             Routing table preserved as-is per spec (no decoration).
             ============================================================ -->
        <section id="contact" class="reveal">
            <div class="container">
                <h2 class="section-title">Contact</h2>
                <p class="section-subtitle">Who to contact for what, in a rural multi-office context</p>

                <h3>Who Should I Contact?</h3>
                <table class="routing-table">
                    <thead><tr><th>Your concern</th><th>Contact</th></tr></thead>
                    <tbody>
                        <tr><td>Enrollment, status, learner records</td><td>Batu-Batu NHS &middot; (062) 992-4151 (DepEd Tawi-Tawi Schools Division Office)</td></tr>
                        <tr><td>Application status, online form issues</td><td>SmartCampus project team &middot; kerrfairtex@gmail.com &middot; 09637130812</td></tr>
                        <tr><td>Website, technical support</td><td>SmartCampus project team &middot; kerrfairtex@gmail.com</td></tr>
                        <tr><td>School policies, complaints, learner protection</td><td>Batu-Batu NHS &middot; (062) 992-4151 (DepEd Tawi-Tawi Schools Division Office)</td></tr>
                        <tr><td>Other / general</td><td>Batu-Batu NHS &middot; <a href="mailto:kerrfairtex@gmail.com">kerrfairtex@gmail.com</a></td></tr>
                    </tbody>
                </table>

                <h3 style="margin-top: var(--space-5);">Where We Are</h3>
                <p>Batu-Batu, Poblacion &middot; Panglima Sugala &middot; Tawi-Tawi &middot; BARMM, Philippines</p>
                <p style="margin-top: var(--space-3);">
                    <a href="https://www.openstreetmap.org/?mlat=4.7&amp;mlon=119.9#map=14/4.7/119.9" target="_blank" rel="noopener" class="btn btn--ghost">Open in OpenStreetMap &rarr;</a>
                </p>

                <p class="form-note" style="margin-top: var(--space-4);">
                    <strong>Note:</strong> The SmartCampus team is not the school. For enrollment decisions, learner records, or school administration, contact the school directly.
                </p>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>About</h4>
                    <p>Batu-Batu National High School is a public K-12 institution in Panglima Sugala, Tawi-Tawi, BARMM. Converted from a barangay high school by Batas Pambansa Blg. 290 (14 November 1982).</p>
                </div>
                <div class="footer-col">
                    <h4>SmartCampus K-12</h4>
                    <p>Digital school services: enrollment, attendance, grades, schedules, announcements, library, parent communication.</p>
                </div>
                <div class="footer-col">
                    <h4>Project Team</h4>
                    <ul>
                        <li>SmartCampus &middot; <a href="mailto:kerrfairtex@gmail.com">kerrfairtex@gmail.com</a></li>
                        <li>Phone: 09637130812</li>
                        <li>Facebook: <a href="https://www.facebook.com/KerrFairtex" target="_blank" rel="noopener">/KerrFairtex</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Sources &amp; Credits</h4>
                    <ul>
                        <li>DepEd VMV: <a href="https://www.deped.gov.ph/about-deped/vision-mission-core-values-and-mandate" target="_blank" rel="noopener">deped.gov.ph</a></li>
                        <li>B.P. Blg. 290: Supreme Court E-Library</li>
                        <li>PSA 2024 Census</li>
                    </ul>
                </div>
            </div>
            <p class="footer-credit">
                &copy; 2026 Batu-Batu National High School &middot; SmartCampus K-12 public landing &middot; Built for low-bandwidth island connectivity.
            </p>
        </div>
    </footer>

    <!-- PR-2 search dialog (preserved) -->
    <dialog id="searchDialog" aria-label="Search this page">
        <div class="search-input" style="padding: var(--space-3);">
            <input type="search" class="search-input" id="searchInput" placeholder="Search this page&hellip;" aria-label="Search query" autocomplete="off" style="width: 100%; background: transparent; border: 0; color: var(--sand); font: inherit; font-size: 1.1rem; outline: none;">
        </div>
        <ul class="search-results" id="searchResults" role="listbox" aria-label="Search results"></ul>
        <div class="search-hint"><span>&uarr;&darr; navigate</span> &middot; <span>Enter</span> open &middot; <span>Esc</span> close</div>
    </dialog>

    <!-- PR-3 floating contact button (preserved) -->
    <div class="float-contact" id="floatContact" role="region" aria-label="Quick contact">
        <div class="float-contact__pills" id="fcPills">
            <a class="float-contact__pill float-contact__pill--viber" href="viber://chat?number=%2B639637130812" target="_blank" rel="noopener" aria-label="Chat on Viber">Viber</a>
            <a class="float-contact__pill float-contact__pill--whatsapp" href="https://wa.me/639637130812?text=Hi%20Batu-Batu%20NHS" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">WhatsApp</a>
        </div>
        <button class="float-contact__launch" id="fcLaunch" type="button" aria-label="Open quick contact" aria-expanded="false">&#128172;</button>
    </div>

    <!-- Scripts: tier detection and main behaviors first, then 3D, then reveal/stepper -->
    <script src="public/js/main.js"></script>
    <script src="public/js/reveal.js"></script>
    <script src="public/js/stepper.js"></script>
    <script src="public/js/hero-scene.js"></script>
    <script src="public/js/enhancements.js" defer></script>
</body>
</html>
