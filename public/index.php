<!doctype html>
<html lang="en">
<head>
  <!-- build: 220019fc2 2026-08-29T08:46:36.167090Z -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batu-Batu National High School — SmartCampus K-12</title>
    <link rel="canonical" href="https://smartcampk12.onrender.com/">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "School",
      "name": "Batu-Batu National High School",
      "alternateName": "BBNIHS",
      "description": "A public K-12 national high school in Panglima Sugala, Tawi-Tawi, BARMM, serving the Batu-Batu community since 1982.",
      "url": "https://smartcampk12.onrender.com/",
      "telephone": "(062) 992-4151",
      "email": "smartcampus@bbnihs.edu.ph",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Barangay Batu-Batu, Poblacion",
        "addressLocality": "Panglima Sugala",
        "addressRegion": "BARMM",
        "addressCountry": "PH"
      },
      "department": {
        "@type": "Organization",
        "name": "Department of Education (DepEd) - Tawi-Tawi Schools Division"
      },
      "foundingDate": "1982-11-14",
      "legalName": "Batu-Batu National High School",
      "sameAs": "https://www.deped.gov.ph/"
    }
    </script>
    <meta property="og:title" content="Batu-Batu National High School — SmartCampus K-12">
    <meta property="og:description" content="Batu-Batu National High School, a public K-12 school in Panglima Sugala, Tawi-Tawi, BARMM. Serving Batu-Batu since its conversion to a national high school in 1982.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://smartcampk12.onrender.com/">
    <meta property="og:image" content="https://smartcampk12.onrender.com/apple-touch-icon.png">
    <meta property="og:image:width" content="180">
    <meta property="og:image:height" content="180">
    <meta property="og:site_name" content="SmartCampus K-12">
    <meta property="og:locale" content="en_PH">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Batu-Batu National High School — SmartCampus K-12">
    <meta name="twitter:description" content="Batu-Batu National High School, a public K-12 school in Panglima Sugala, Tawi-Tawi, BARMM.">
    <link rel="preload" as="image" href="assets/images/Batu-batu1_full.jpeg" imagesizes="(max-width: 1100px) 100vw, 1100px" fetchpriority="high">
    <meta name="description" content="Batu-Batu National High School, a public K-12 school in Panglima Sugala, Tawi-Tawi, BARMM. Serving Batu-Batu since its conversion to a national high school in 1982.">
    <!-- PWA -->
    <link rel="manifest" href="/public/manifest.json">
    <meta name="theme-color" content="#ffffff">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SMARTCAMP-K12">
    <style>
/* ============================================================
 * CRITICAL CSS — inlined for first paint
 * tokens + base + above-the-fold components
 * Non-critical components loaded async below
 * ============================================================ */
/* =====================================================================
 * SmartCampus K-12 / Batu-Batu NHS — Design Tokens
 * Spec Part 1: 6 color tokens (maritime, not generic SaaS navy-and-yellow)
 * 3 type roles: Display (serif) / Body (humanist sans) / Utility (mono)
 * 5 spacing steps, 4 radius steps, 3 elevation steps
 * ===================================================================== */

:root {
    /* Color tokens (6) */
    --ink-deep: #0A1420;       /* base background, near-black navy */
    --tide-teal: #0E4F4F;      /* secondary surfaces, section alternation */
    --sun-gold: #F4B400;       /* institutional gold (Philippine-flag warm yellow) */
    --reef-coral: #E8734A;     /* live / active state accent */
    --sand: #EDE6D6;           /* warm off-white for light surfaces */
    --foam: #CFE8E4;           /* hairlines, dividers, low-emphasis text */

    /* Translucent overlays (derived) */
    --ink-overlay-90: rgba(10, 20, 32, 0.90);
    --ink-overlay-70: rgba(10, 20, 32, 0.70);
    --ink-overlay-40: rgba(10, 20, 32, 0.40);
    --foam-overlay-08: rgba(207, 232, 228, 0.08);
    --foam-overlay-16: rgba(207, 232, 228, 0.16);
    --sun-gold-overlay-12: rgba(244, 180, 0, 0.12);

    /* Type roles (3) */
    --font-display: 'Fraunces', 'Source Serif 4', 'Georgia', 'Times New Roman', serif;
    --font-body: 'Inter', 'Public Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-utility: 'IBM Plex Mono', 'SFMono-Regular', Menlo, Consolas, monospace;

    /* Spacing scale (5 steps) */
    --space-1: 0.25rem;   /* 4px */
    --space-2: 0.5rem;    /* 8px */
    --space-3: 1rem;      /* 16px */
    --space-4: 1.5rem;    /* 24px */
    --space-5: 3rem;      /* 48px */

    /* Radius scale (4 steps) */
    --radius-1: 2px;
    --radius-2: 6px;
    --radius-3: 12px;
    --radius-4: 24px;

    /* Elevation scale (3 steps) */
    --elev-1: 0 2px 4px rgba(0, 0, 0, 0.20);
    --elev-2: 0 4px 12px rgba(0, 0, 0, 0.30);
    --elev-3: 0 12px 32px rgba(0, 0, 0, 0.40);

    /* Motion tokens (5 durations, 3 easings) */
    --dur-micro: 120ms;
    --dur-hover: 180ms;
    --dur-base: 300ms;
    --dur-reveal: 500ms;
    --dur-hero: 900ms;
    --ease-standard: cubic-bezier(0.4, 0, 0.2, 1);
    --ease-out: cubic-bezier(0.2, 0, 0, 1);
    --ease-in: cubic-bezier(0.4, 0, 1, 1);
}

/* High-contrast a11y toggle: palette override (DepEd yellow → pure black/white) */
html.a11y-hc {
    --ink-deep: #000000;
    --tide-teal: #0a2f2f;
    --sun-gold: #ffd400;
    --reef-coral: #ff5a1f;
    --sand: #ffffff;
    --foam: #f5f5f5;
    --ink-overlay-90: rgba(0, 0, 0, 0.95);
    --ink-overlay-70: rgba(0, 0, 0, 0.85);
    --ink-overlay-40: rgba(0, 0, 0, 0.60);
    --foam-overlay-08: rgba(255, 255, 255, 0.10);
    --foam-overlay-16: rgba(255, 255, 255, 0.20);
}


/* ------------------------------------------------------------------ */
/* =====================================================================
 * SmartCampus K-12 — Base styles
 * Resets, document-level typography, section rhythm, container widths
 * ===================================================================== */

/* Google Fonts loaded via <link> in <head> */

/* Reset */
*, *::before, *::after { box-sizing: border-box; }
* { margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
body {
    font-family: var(--font-body);
    font-size: 16px;
    line-height: 1.6;
    color: var(--foam);
    background: var(--ink-deep);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
img, svg, video, canvas { display: block; max-width: 100%; }
button { font: inherit; cursor: pointer; border: 0; background: none; color: inherit; }
a { color: var(--sun-gold); text-decoration: none; transition: color var(--dur-hover) var(--ease-standard); }
a:hover { color: var(--reef-coral); }
h1, h2, h3, h4, h5, h6 { font-family: var(--font-display); font-weight: 600; line-height: 1.2; }

/* Document-level typography */
h1 { font-size: clamp(2.2rem, 5vw, 4rem); }
h2 { font-size: clamp(1.6rem, 3.5vw, 2.6rem); }
h3 { font-size: clamp(1.3rem, 2.5vw, 1.7rem); }
h4 { font-size: clamp(1.05rem, 2vw, 1.2rem); }

/* Section rhythm */
section { padding: var(--space-5) var(--space-3); }
section + section { border-top: 1px solid var(--foam-overlay-08); }

/* Alternating surface treatment: every other section gets a tide-teal tint */
section.surface-tide { background: var(--tide-teal); }

/* Container */
.container {
    max-width: 1100px;
    margin: 0 auto;
    width: 100%;
    padding: 0 var(--space-3);
}
.container-narrow { max-width: 760px; margin: 0 auto; padding: 0 var(--space-3); }

/* Section heading rhythm */
.section-title { margin-bottom: var(--space-2); color: var(--sand); }
.section-subtitle { color: var(--foam); opacity: 0.8; margin-bottom: var(--space-4); max-width: 60ch; }

/* A11y: skip-link, focus visibility */
.skip-link {
    position: absolute;
    top: -40px;
    left: var(--space-3);
    background: var(--sun-gold);
    color: var(--ink-deep);
    padding: var(--space-2) var(--space-3);
    font-weight: 600;
    border-radius: var(--radius-1);
    z-index: 1000;
    transition: top var(--dur-hover) var(--ease-standard);
}
.skip-link:focus { top: var(--space-2); }
:focus-visible { outline: 2px solid var(--sun-gold); outline-offset: 2px; border-radius: var(--radius-1); }

/* A11y motion gates */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
    /* Fully disable decorative animations, don't just speed them up */
    .horizon-line { animation: none; }
}
html.save-data *, html.save-data *::before, html.save-data *::after {
    animation: none !important;
    transition: none !important;
}

/* A11y type roles */
html.a11y-dyslexic body, html.a11y-dyslexic h1, html.a11y-dyslexic h2, html.a11y-dyslexic h3, html.a11y-dyslexic p {
    font-family: 'OpenDyslexic', 'Comic Sans MS', var(--font-body);
    letter-spacing: 0.02em;
    line-height: 1.75;
}
html.a11y-large { font-size: 115%; }


/* ------------------------------------------------------------------ */
/* Critical components (above-the-fold) */

/* =====================================================================
 * SmartCampus K-12 — Component styles
 * Buttons · cards · stat tiles · stepper · a11y bar · modals · timeline
 * BEM-ish naming. CSS custom properties come from tokens.css.
 * ===================================================================== */

@import url('base.css');

/* ---------- Buttons ---------- */
.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 0.7rem 1.25rem;
    border-radius: var(--radius-2);
    font-weight: 600;
    font-family: var(--font-body);
    cursor: pointer;
    transition: transform var(--dur-hover) var(--ease-standard),
                background var(--dur-hover) var(--ease-standard),
                box-shadow var(--dur-hover) var(--ease-standard);
    border: 1px solid transparent;
    text-decoration: none;
}
.btn--primary {
    background: var(--sun-gold);
    color: var(--ink-deep);
    box-shadow: var(--elev-1);
}
.btn--primary:hover, .btn--primary:focus-visible {
    background: #ffce3f;
    transform: translateY(-2px);
    box-shadow: var(--elev-2);
}
.btn--primary:active { transform: translateY(0); box-shadow: var(--elev-1); }
.btn--ghost {
    background: transparent;
    color: var(--foam);
    border-color: var(--foam-overlay-16);
}
.btn--ghost:hover, .btn--ghost:focus-visible {
    background: var(--foam-overlay-08);
    color: var(--sand);
    border-color: var(--foam);
}

/* ---------- Stat tiles (At a Glance) ---------- */
.tile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-3);
}
.tile {
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-3);
    padding: var(--space-4);
    transition: transform 220ms var(--ease-out), border-color 220ms var(--ease-standard);
    transform-style: preserve-3d;
    will-change: transform;
}
.tile:hover { border-color: var(--sun-gold); transform: translateY(-3px); }
.tile__label { color: var(--foam); opacity: 0.7; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; }
.tile__value { color: var(--sand); font-family: var(--font-utility); font-size: 1.6rem; margin-top: var(--space-2); word-break: break-word; }
.tile__source { color: var(--foam); opacity: 0.6; font-size: 0.75rem; margin-top: var(--space-1); }

/* ---------- Stat counters (Community population) ---------- */
.counter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-3);
}
.counter {
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-3);
    padding: var(--space-4);
    text-align: center;
}
.counter__number { font-family: var(--font-utility); font-size: 2.5rem; font-weight: 500; color: var(--sun-gold); }
.counter__label { color: var(--sand); margin-top: var(--space-2); }
.counter__context { color: var(--foam); opacity: 0.6; font-size: 0.8rem; margin-top: var(--space-1); }

/* ---------- Grade cards (Academics) ---------- */
.grade-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--space-3);
}
.grade {
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-2);
    padding: var(--space-3);
    text-align: center;
    transition: border-color var(--dur-hover) var(--ease-standard),
                transform var(--dur-hover) var(--ease-standard);
    cursor: pointer;
    user-select: none;
}
.grade:hover { border-color: var(--sun-gold); transform: translateY(-2px); }
.grade__level { font-family: var(--font-utility); font-size: 1.4rem; color: var(--sand); }

/* ---------- Status timeline (Admissions) ---------- */
.status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: var(--space-4) 0;
    gap: var(--space-2);
}
.status-step {
    flex: 1;
    text-align: center;
    position: relative;
    padding: var(--space-2) var(--space-1);
    font-size: 0.75rem;
    color: var(--foam);
    opacity: 0.5;
    transition: opacity var(--dur-base) var(--ease-standard);
}
.status-step--active { opacity: 1; color: var(--sand); }
.status-step--done { opacity: 0.7; color: var(--reef-coral); }
.status-step__dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: currentColor;
    margin: 0 auto var(--space-1);
}
.status-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 50%;
    right: -50%;
    width: 100%;
    height: 2px;
    background: var(--foam-overlay-16);
    z-index: -1;
}
.status-step--done::after { background: var(--reef-coral); }

/* ---------- Wizard stepper (Start Your Enrollment) ---------- */
.wizard {
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-3);
    padding: var(--space-4);
    margin: var(--space-4) 0;
}
.wizard__progress {
    height: 4px;
    background: var(--foam-overlay-16);
    border-radius: 2px;
    margin: var(--space-3) 0;
    position: relative;
    overflow: hidden;
}
.wizard__progress::after {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--sun-gold);
    width: var(--progress, 0%);
    transition: width var(--dur-base) var(--ease-standard);
}
.wizard__steps { display: flex; justify-content: space-between; }
.wizard__step {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--foam-overlay-08);
    color: var(--foam);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-utility);
    font-size: 0.85rem;
    transition: background var(--dur-base) var(--ease-standard),
                color var(--dur-base) var(--ease-standard),
                transform var(--dur-base) var(--ease-standard);
}
.wizard__step--done { background: var(--reef-coral); color: var(--ink-deep); }
.wizard__step--active { background: var(--sun-gold); color: var(--ink-deep); transform: scale(1.1); }

/* ---------- About / mission / vision / history ---------- */
.about-card {
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    border-left: 4px solid var(--sun-gold);
    border-radius: var(--radius-2);
    padding: var(--space-4);
    margin: var(--space-3) 0;
}
.about-card__heading { color: var(--sun-gold); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: var(--space-2); }
.about-card__body { color: var(--sand); }
.about-card__cite { color: var(--foam); opacity: 0.6; font-size: 0.8rem; margin-top: var(--space-2); font-style: italic; }
.about-card__cite a { color: var(--foam); }

.history-timeline { list-style: none; padding: 0; margin: var(--space-4) 0; }
.history-timeline__item {
    position: relative;
    padding: var(--space-3) 0 var(--space-3) var(--space-5);
    border-left: 2px solid var(--foam-overlay-16);
    margin-left: var(--space-2);
}
.history-timeline__year { font-family: var(--font-utility); color: var(--sun-gold); font-weight: 500; }
.history-timeline__text { color: var(--sand); margin-top: var(--space-1); }
.history-timeline__cite { color: var(--foam); opacity: 0.6; font-size: 0.8rem; margin-top: var(--space-1); font-style: italic; }
.history-timeline__item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 1.5rem;
    width: 10px;
    height: 10px;
    background: var(--reef-coral);
    border-radius: 50%;
}

/* ---------- Empty state (Part 3, About) ---------- */
.empty-state {
    background: var(--ink-overlay-40);
    border: 1px dashed var(--foam-overlay-16);
    border-radius: var(--radius-2);
    padding: var(--space-4);
    text-align: center;
    color: var(--foam);
    font-style: italic;
    opacity: 0.8;
}

/* ---------- Forms ---------- */
.form-field { margin-bottom: var(--space-3); position: relative; }
.form-field label {
    display: block;
    color: var(--foam);
    margin-bottom: var(--space-1);
    font-size: 0.85rem;
    font-weight: 500;
}
.form-field input, .form-field select, .form-field textarea {
    width: 100%;
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    color: var(--sand);
    padding: 0.6rem 0.8rem;
    border-radius: var(--radius-2);
    font: inherit;
    transition: border-color var(--dur-hover) var(--ease-standard);
}
.form-field input:focus, .form-field select:focus, .form-field textarea:focus {
    outline: 0;
    border-color: var(--sun-gold);
    box-shadow: 0 0 0 2px var(--sun-gold-overlay-12);
}
.form-note { color: var(--foam); opacity: 0.6; font-size: 0.8rem; margin-top: var(--space-2); }

/* ---------- Contact / routing table ---------- */
.routing-table {
    width: 100%;
    border-collapse: collapse;
    margin: var(--space-3) 0;
    background: var(--ink-overlay-40);
    border-radius: var(--radius-2);
    overflow: hidden;
}
.routing-table th, .routing-table td {
    padding: var(--space-2) var(--space-3);
    text-align: left;
    border-bottom: 1px solid var(--foam-overlay-08);
}
.routing-table th { color: var(--sun-gold); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.06em; }
.routing-table tr:last-child td { border-bottom: 0; }

/* ---------- Accessibility toolbar ---------- */
.a11y-bar {
    position: sticky;
    top: 0;
    z-index: 50;
    background: var(--ink-overlay-90);
    border-bottom: 1px solid var(--foam-overlay-16);
    padding: var(--space-2) var(--space-3);
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: 0.85rem;
}
.a11y-bar__label { color: var(--foam); opacity: 0.7; }
.a11y-bar__btn {
    padding: 0.4rem 0.8rem;
    background: var(--foam-overlay-08);
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-1);
    color: var(--foam);
    transition: background var(--dur-hover) var(--ease-standard),
                border-color var(--dur-hover) var(--ease-standard);
}
.a11y-bar__btn[aria-pressed="true"] {
    background: var(--sun-gold);
    color: var(--ink-deep);
    border-color: var(--sun-gold);
}
.a11y-bar__btn:hover { background: var(--foam-overlay-16); }
.a11y-bar__conn { margin-left: auto; display: inline-flex; align-items: center; gap: 0.4rem; color: var(--foam); opacity: 0.7; }
.a11y-bar__conn .dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; }
.a11y-bar__conn[data-state="offline"] .dot { background: #6b7280; }

/* ---------- Top navigation ---------- */
.top-nav {
    position: sticky;
    top: 42px; /* below a11y-bar */
    z-index: 40;
    background: var(--ink-overlay-90);
    border-bottom: 1px solid var(--foam-overlay-08);
    padding: var(--space-2) var(--space-3);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}
.top-nav__brand {
    font-family: var(--font-display);
    font-weight: 700;
    color: var(--sun-gold);
    font-size: 1rem;
    text-decoration: none;
}
.top-nav__links { display: flex; gap: var(--space-3); flex: 1; }
.top-nav__link {
    color: var(--foam);
    font-size: 0.85rem;
    padding: 0.4rem 0.6rem;
    border-radius: var(--radius-1);
    transition: color var(--dur-hover) var(--ease-standard);
}
.top-nav__link:hover, .top-nav__link.is-active { color: var(--sun-gold); }

/* ---------- Hero ---------- */
.hero {
    position: relative;
    min-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: var(--ink-deep);
}
.hero__canvas-wrap {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.hero__canvas-wrap canvas { width: 100%; height: 100%; }
.hero__fallback {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, var(--ink-deep) 0%, var(--tide-teal) 60%, var(--ink-deep) 100%);
    z-index: -1;
}
.hero__fallback::after {
    content: '';
    position: absolute;
    bottom: 20%;
    left: 0;
    right: 0;
    height: 30%;
    background:
        radial-gradient(ellipse 60% 40% at 30% 80%, rgba(14,79,79,0.6), transparent),
        radial-gradient(ellipse 50% 30% at 70% 90%, rgba(14,79,79,0.5), transparent);
}
.hero__content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 900px;
    padding: var(--space-5) var(--space-3);
}
.hero__eyebrow {
    color: var(--sun-gold);
    font-family: var(--font-utility);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin-bottom: var(--space-2);
}
.hero__title { color: var(--sand); margin-bottom: var(--space-3); text-shadow: 0 2px 12px rgba(0,0,0,0.4); }
.hero__sub { color: var(--foam); font-size: 1.2rem; margin-bottom: var(--space-3); }
.hero__location { color: var(--foam); opacity: 0.7; font-size: 0.9rem; margin-bottom: var(--space-4); font-family: var(--font-utility); }
.hero__credibility { color: var(--foam); opacity: 0.6; font-size: 0.8rem; margin-bottom: var(--space-4); font-style: italic; }
.hero__actions { display: flex; gap: var(--space-2); justify-content: center; flex-wrap: wrap; }
.hero__clock { position: absolute; top: 1rem; right: 1rem; color: var(--foam); opacity: 0.7; font-family: var(--font-utility); font-size: 0.85rem; z-index: 3; }

/* ---------- Signature horizon line ---------- */
.horizon-line {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--sun-gold), transparent);
    margin: 0;
    opacity: 0.5;
    position: relative;
}
.horizon-line::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, var(--reef-coral), transparent);
    animation: horizon-shimmer 6s ease-in-out infinite;
}
@keyframes horizon-shimmer {
    0%, 100% { opacity: 0.3; transform: translateX(-30%); }
    50% { opacity: 0.8; transform: translateX(30%); }
}

/* ---------- Module grid (Features) ---------- */
.module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--space-3);
}
.module {
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-2);
    padding: var(--space-3);
    text-align: center;
    transition: border-color var(--dur-hover) var(--ease-standard);
}
.module:hover { border-color: var(--reef-coral); }
.module__icon { font-size: 2rem; margin-bottom: var(--space-1); }
.module__name { color: var(--sand); font-size: 0.9rem; }
.module__dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; margin: 0 auto var(--space-1); }

/* ---------- Footer ---------- */
.site-footer {
    background: var(--tide-teal);
    padding: var(--space-5) var(--space-3);
    color: var(--foam);
}
.site-footer a { color: var(--sun-gold); }
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}
.footer-col h4 { color: var(--sand); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: var(--space-2); }
.footer-col p { color: var(--foam); font-size: 0.9rem; line-height: 1.5; }
.footer-col ul { list-style: none; }
.footer-col li { margin-bottom: 0.4rem; font-size: 0.9rem; }
.footer-credit { text-align: center; color: var(--sand); font-size: 0.8rem; border-top: 1px solid var(--foam-overlay-08); padding-top: var(--space-3); }

/* ---------- Search dialog (preserved from PR-2) ---------- */
dialog#searchDialog {
    padding: 0;
    border: 1px solid var(--foam-overlay-16);
    border-radius: var(--radius-2);
    background: var(--ink-deep);
    color: var(--foam);
    max-width: 600px;
    width: calc(100% - 2rem);
    box-shadow: var(--elev-3);
}
dialog#searchDialog::backdrop { background: rgba(0, 0, 0, 0.65); }
.search-input {
    width: 100%;
    background: transparent;
    border: 0;
    border-bottom: 1px solid var(--foam-overlay-16);
    color: var(--sand);
    font: inherit;
    font-size: 1.1rem;
    padding: var(--space-3);
    outline: none;
}
.search-results { list-style: none; max-height: 60vh; overflow-y: auto; }
.search-results li {
    padding: 0.7rem var(--space-3);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.search-results li[aria-selected="true"], .search-results li:hover { background: var(--tide-teal); }
.search-results .type {
    font-size: 0.65rem;
    text-transform: uppercase;
    padding: 0.1rem 0.4rem;
    border-radius: var(--radius-1);
    background: var(--foam-overlay-16);
    color: var(--foam);
}
.search-results .empty { padding: var(--space-3); text-align: center; color: var(--foam); opacity: 0.6; }
.search-hint { padding: 0.5rem var(--space-3); border-top: 1px solid var(--foam-overlay-08); color: var(--foam); opacity: 0.6; font-size: 0.75rem; }

/* ---------- Floating contact button (preserved from PR-3) ---------- */
.float-contact { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 50; display: flex; flex-direction: column-reverse; align-items: flex-end; gap: 0.6rem; }
.float-contact__launch {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: var(--sun-gold);
    color: var(--ink-deep);
    display: flex; align-items: center; justify-content: center;
    box-shadow: var(--elev-2);
    font-size: 1.5rem;
    transition: transform var(--dur-hover) var(--ease-standard);
}
.float-contact__launch:hover { transform: scale(1.05); }
.float-contact__pills { display: none; flex-direction: column; gap: 0.4rem; align-items: flex-end; }
.float-contact.open .float-contact__pills { display: flex; }
.float-contact__pill {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.5rem 0.85rem;
    border-radius: 999px;
    color: var(--ink-deep);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: var(--elev-1);
    white-space: nowrap;
}
.float-contact__pill--viber { background: #7360F2; color: white; }
.float-contact__pill--whatsapp { background: #25D366; color: white; }

/* ---------- Photo strip (About / Community sections) ---------- */
.photo-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-3);
    margin: var(--space-3) 0 var(--space-5);
}
.photo-strip figure {
    margin: 0;
    border-radius: var(--radius-2);
    overflow: hidden;
    background: var(--ink-overlay-40);
    border: 1px solid var(--foam-overlay-16);
    aspect-ratio: 4 / 3;
    position: relative;
}
.photo-strip figure img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform var(--dur-base) var(--ease-standard);
}
.photo-strip figure:hover img { transform: scale(1.04); }
.photo-strip figcaption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: var(--space-2) var(--space-3);
    background: linear-gradient(180deg, transparent, rgba(10, 20, 32, 0.85));
    color: var(--sand);
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity var(--dur-hover) var(--ease-standard);
}
.photo-strip figure:hover figcaption,
.photo-strip figure:focus-within figcaption { opacity: 1; }

/* Unverified figures get a coral border and always-visible tag */
.photo-strip figure.unverified {
    border: 1px solid var(--reef-coral);
    position: relative;
}
.photo-strip figure.unverified::before {
    content: '?';
    position: absolute;
    top: 0.4rem;
    right: 0.4rem;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--reef-coral);
    color: var(--ink-deep);
    font-weight: 700;
    font-size: 0.85rem;
    line-height: 22px;
    text-align: center;
    z-index: 2;
    box-shadow: var(--elev-1);
}
.photo-strip figcaption.unverified-tag {
    opacity: 1;
    position: absolute;
    top: 0.4rem;
    left: 0.4rem;
    background: var(--reef-coral);
    color: var(--ink-deep);
    padding: 0.15rem 0.5rem;
    border-radius: 2px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    z-index: 2;
}
.photo-strip figure p {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.6rem var(--space-3);
    margin: 0;
    background: linear-gradient(180deg, transparent, rgba(10, 20, 32, 0.92));
    color: var(--sand);
    font-size: 0.78rem;
    line-height: 1.4;
    opacity: 0;
    transition: opacity var(--dur-hover) var(--ease-standard);
}
.photo-strip figure:hover p,
.photo-strip figure:focus-within p { opacity: 1; }
.photo-strip figure p em { color: var(--sun-gold); font-style: italic; }

/* ---------- Reveal on scroll (Tier 1) ---------- */
.reveal { opacity: 0; transform: translateY(12px); transition: opacity 600ms var(--ease-out), transform 600ms var(--ease-out); }
.reveal.is-in { opacity: 1; transform: translateY(0); }

/* Tier 0/1 visibility gate */
html.tier-static .hero__canvas-wrap { display: none; }
html.tier-static .hero__fallback { z-index: 0; }
html.tier-static .reveal { opacity: 1; transform: none; transition: none; }
/* =====================================================================
 * SmartCampus K-12 — Mobile breakpoint patch
 * Appended at end of components.css. It does not modify any existing
 * selector — it only adds mobile overrides, so it's safe to drop in.
 *
 * Requires ONE small HTML change in the nav markup — see comment at the
 * top of the file. The checkbox + label were already added in the
 * previous turn.
 * ===================================================================== */

/* ---------- Hero fallback: brighter, visible before Three.js loads ---------- */
.hero__fallback {
    background: linear-gradient(
        180deg,
        var(--ink-deep) 0%,
        var(--tide-teal) 45%,
        #1a6b6b 65%,
        var(--ink-deep) 100%
    );
}

@media (max-width: 720px) {
    /* Shrink the hero on small screens so the fallback gradient doesn't
       read as a giant block of near-empty color before content appears */
    .hero { min-height: 60vh; }
    .hero__content { padding: var(--space-4) var(--space-3); }
}

/* ---------- Accessibility bar: wrap instead of overflow ---------- */
@media (max-width: 720px) {
    .a11y-bar {
        flex-wrap: wrap;
        row-gap: var(--space-2);
    }
    .a11y-bar__conn {
        margin-left: 0;
        order: -1;
        width: 100%;
    }
}

/* ---------- Top nav: collapse to hamburger + slide-down drawer ---------- */
@media (max-width: 860px) {
    .top-nav {
        position: sticky;
        top: 0;               /* let the a11y bar scroll away instead of
                                  pinning nav at a hardcoded offset */
        flex-wrap: wrap;
    }

    /* Hide the inline link row by default on mobile */
    .top-nav__links {
        display: none;
        flex-direction: column;
        width: 100%;
        gap: 0;
        order: 3;
        border-top: 1px solid var(--foam-overlay-08);
        margin-top: var(--space-2);
        padding-top: var(--space-2);
    }
    .top-nav__link {
        padding: var(--space-2) var(--space-1);
        width: 100%;
    }

    /* Checkbox-driven toggle (no JS required) */
    .top-nav__toggle-input { display: none; }
    .top-nav__toggle-input:checked ~ .top-nav__links {
        display: flex;
    }
    .top-nav__toggle-input:checked ~ .top-nav__toggle-label .top-nav__burger-icon {
        transform: rotate(90deg);
    }

    .top-nav__toggle-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: var(--radius-1);
        border: 1px solid var(--foam-overlay-16);
        color: var(--foam);
        cursor: pointer;
        order: 2;
        margin-left: auto;
        transition: background var(--dur-hover) var(--ease-standard);
    }
    .top-nav__toggle-label:hover { background: var(--foam-overlay-08); }
    .top-nav__burger-icon {
        font-family: var(--font-utility);
        font-size: 1.1rem;
        line-height: 1;
        transition: transform var(--dur-base) var(--ease-standard);
    }

    /* Search button stays visible, brand stays visible, links + toggle
       reflow beneath */
    .top-nav__brand { order: 1; }
    #searchLaunch { order: 4; margin-left: var(--space-2); }
}

@media (min-width: 861px) {
    /* Make sure the toggle never shows on desktop even if markup is present */
    .top-nav__toggle-label { display: none; }
}


/* =====================================================================
 * SmartCampus K-12 — Unified mobile header patch
 * Replaces the "two stacked bars + inconsistent overflow" header with
 * ONE sticky bar on mobile: [Brand] ... [Hamburger] [Search]
 * The hamburger drawer contains BOTH nav links AND accessibility
 * toggles, in that order, so nothing competes for space above the fold.
 *
 * Desktop (>860px) is untouched — a11y bar + nav still render as your
 * original two rows there, since there's room for them.
 * ===================================================================== */

/* ---------- 1. Harden the checkbox hiding (was: display:none only,
   which can render briefly as a raw browser checkbox before CSS
   applies). Visually hidden but still focusable/toggleable, and can
   never flash visible even mid-paint. ---------- */
.top-nav__toggle-input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 860px) {

    /* ---------- 2. Collapse the a11y bar into the drawer instead of
       showing as its own sticky row ---------- */
    .a11y-bar {
        display: none;
    }

    /* Re-show the a11y toggles, but now INSIDE the nav drawer */
    .top-nav__links .a11y-bar__btn,
    .top-nav__links .a11y-bar__conn {
        display: inline-flex;
    }

    /* ---------- 3. Single unified sticky bar ---------- */
    .top-nav {
        position: sticky;
        top: 0;
        z-index: 50;
        flex-wrap: wrap;
        background: var(--ink-overlay-90);
    }
    .top-nav__brand { order: 1; }
    .top-nav__toggle-label {
        order: 2;
        margin-left: auto;
    }
    #searchLaunch { order: 3; margin-left: var(--space-2); }

    /* Drawer: nav links + a11y controls together, in document order */
    .top-nav__links {
        display: none;
        flex-direction: column;
        width: 100%;
        order: 4;
        gap: 0;
        border-top: 1px solid var(--foam-overlay-08);
        margin-top: var(--space-2);
        padding-top: var(--space-2);
    }
    .top-nav__toggle-input:checked ~ .top-nav__links {
        display: flex;
    }
    .top-nav__link {
        width: 100%;
        padding: var(--space-2) var(--space-1);
    }

    /* A11y controls, once moved into the drawer, get their own
       labeled section with a divider so they read as a distinct
       group rather than more nav links */
    .top-nav__links .a11y-bar__label {
        display: block;
        width: 100%;
        padding: var(--space-2) var(--space-1) var(--space-1);
        color: var(--foam);
        opacity: 0.6;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border-top: 1px solid var(--foam-overlay-08);
        margin-top: var(--space-1);
    }
    .top-nav__links .a11y-bar__btn {
        width: 100%;
        justify-content: flex-start;
        margin: 0.15rem 0;
    }
    .top-nav__links .a11y-bar__conn {
        margin: var(--space-2) 0 0;
        width: 100%;
    }
}

@media (min-width: 861px) {
    /* Desktop: keep original two-bar layout, ignore all of the above */
    .top-nav__toggle-label { display: none; }
    .a11y-bar { display: flex; }
}

/* =====================================================================
 * Universal responsive hardening patch
 * Fixes: horizontal overflow, hardcoded image dimensions, table overflow,
 *        flex wrapping on small screens
 * ===================================================================== */

/* Foundation: prevent any horizontal overflow at the root */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

/* Images with hardcoded width/height attributes: force responsive */
img[width], img[height] {
    max-width: 100%;
    height: auto;
}

/* Routing table: wrap in scrollable container on small screens */
.routing-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: var(--space-3) 0;
}

/* Wizard button row: allow wrapping on small screens */
.wizard button[type="button"] {
    flex-shrink: 0;
}

/* Status bar: allow wrapping on very small screens */
.status-bar {
    flex-wrap: wrap;
}
.status-step {
    flex: 1 1 min-content;
    min-width: 100px;
    font-size: 0.7rem;
}
.status-step__dot {
    margin: 0 auto var(--space-1);
    width: 10px;
    height: 10px;
}

/* Grade grid: min width for very small screens */
.grade-grid {
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
}

/* Module grid: min width for very small screens */
.module-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

/* Footer grid: stack on very small screens */
.footer-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

/* Container: ensure it doesn't exceed viewport width */
.container, .container-narrow {
    max-width: min(1100px, 100% - 1rem);
}

/* Hero content: ensure text doesn't overflow on tiny screens */
.hero__content {
    padding: var(--space-4) var(--space-2);
}

/* Photo strip: ensure figures don't force overflow */
.photo-strip figure {
    width: 100%;
    max-width: 100%;
}

/* Form fields: prevent label/input overflow */
.form-field label,
.form-field input,
.form-field select,
.form-field textarea {
    max-width: 100%;
}
</style>
    <link rel="stylesheet" href="/css/components.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="/css/components.css"></noscript>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%23F4B400'/%3E%3Ctext x='16' y='22' font-size='20' text-anchor='middle' font-family='serif' font-weight='700' fill='%230A1420'%3EB%3C/text%3E%3C/svg%3E">
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>

    <!-- ============================================================
         ACCESSIBILITY TOOLBAR (sticky top)
         Per Part 2: toggles gate the animated tier (motion/hover effects).
         ============================================================ -->
    <!-- Unified header: single <nav> with brand, hamburger, search, and
         (inside the drawer) nav links + accessibility toggles + conn badge.
         Replaces the previous two-row header (a11y-bar + top-nav). On
         desktop the drawer is just always-open; on mobile it's
         checkbox-toggled. -->
    <nav class="top-nav" aria-label="Primary">
        <a href="#home" class="top-nav__brand">
            <img src="assets/images/logo.jpg" alt="BBNIHS Logo" style="height:32px;width:32px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;">
            BBNIHS
        </a>

        <input type="checkbox" id="navToggle" class="top-nav__toggle-input" aria-label="Toggle navigation menu">
        <label for="navToggle" class="top-nav__toggle-label" aria-label="Toggle navigation menu">
            <span class="top-nav__burger-icon">☰</span>
        </label>

        <button class="a11y-bar__btn" id="searchLaunch" type="button" aria-label="Open search (/)">Search /</button>

        <div class="top-nav__links">
            <a class="top-nav__link" href="#glance">At a Glance</a>
            <a class="top-nav__link" href="#community">Community</a>
            <a class="top-nav__link" href="#about">About</a>
            <a class="top-nav__link" href="#academics">Academics</a>
            <a class="top-nav__link" href="#admissions">Admissions</a>
            <a class="top-nav__link" href="#features">Features</a>
            <a class="top-nav__link" href="#contact">Contact</a>

            <span class="a11y-bar__label">Accessibility</span>
            <button class="a11y-bar__btn" id="a11yHc" type="button" aria-pressed="false">High contrast</button>
            <button class="a11y-bar__btn" id="a11yDys" type="button" aria-pressed="false">Dyslexia-friendly font</button>
            <button class="a11y-bar__btn" id="a11yLarge" type="button" aria-pressed="false">Larger text</button>
            <span class="a11y-bar__conn" id="connBadge" data-state="online"><span class="dot"></span>Online</span>
        </div>
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
                <div style="margin-bottom: 1.5rem; position: relative; display: flex; justify-content: center; align-items: center; width: min(92vw, 480px); height: min(92vw, 480px); margin-left: auto; margin-right: auto;">
                    <object data="assets/images/orbital-system.svg" type="image/svg+xml" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 3; mix-blend-mode: screen;"></object>
                    <img src="assets/images/logo.jpg" alt="Batu-Batu National High School Official Seal" style="position: relative; width: clamp(140px, 28vw, 210px); height: clamp(140px, 28vw, 210px); border-radius: 50%; object-fit: cover; border: 3px solid var(--sun-gold); box-shadow: var(--elev-3); z-index: 2;">
                </div>
                <p class="hero__eyebrow">Batu-Batu · Panglima Sugala · Tawi-Tawi · BARMM</p>
                <h1 class="hero__title">BATU-BATU</h1>
                <p class="hero__sub">Learning, growing, and building the future of Tawi-Tawi</p>
                <p class="hero__location">Barangay Batu-Batu, Poblacion &middot; Panglima Sugala &middot; Tawi-Tawi</p>
                <p class="hero__credibility reveal">A public K-12 school serving the Batu-Batu community since its conversion to a national high school in 1982 (Batas Pambansa Blg. 290).</p>
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
             Stat tiles with CSS perspective tilt + count-up animation.
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
                <p class="section-lead">
                    Tawi-Tawi is the Philippines&rsquo; southernmost province &mdash; a region of islands, maritime culture, and diverse communities including the Sama, Jama Mapun, Badjao, and Tausug peoples.
                </p>
                <p class="form-note" style="margin-top: var(--space-4); margin-bottom: var(--space-3);">Real photos from the Tawi-Tawi archipelago. The first is vision-verified; the others are region-tagged but pending verification.</p>
                <div class="photo-strip">
                    <figure><img src="assets/images/tawi-bongao.jpg" alt="Bongao, Tawi-Tawi &mdash; stilt houses over water with mountains in the distance (vision-verified)" loading="lazy" width="1600" height="1201" ></figure>
                    <figure><img src="assets/images/Batu-batu2_full.jpeg" alt="Aerial view of a Tawi-Tawi stilt-house coastal village (vision-verified)" loading="lazy" width="479" height="640" ></figure>
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
                <p class="section-spacer-bottom">
                    Batu-Batu National High School traces its roots to a barangay high school serving the Batu-Batu community, and was formally converted into a national high school on <strong>14 November 1982</strong> by Batas Pambansa Blg. 290. The full history is captured in the timeline below. The photos below are the visual record of the school and its community. Where a photo is shown without a caption, it is presented as provided to the SmartCampus team; the school registrar will confirm each subject so it can be captioned properly.
                </p>

                <!-- ============================================================
                     PHOTO BANK
                     All photos live together as one gallery, organized by
                     scene (campus, community). All photos are captioned with the likely subject based on the
                     filename; the school registrar will confirm each so the
                     captions can be tightened.
                     ============================================================ -->
                <h4 style="margin-top: var(--space-4); color: var(--sand);">Campus &amp; community photos</h4>
                <div class="photo-strip">
                    <figure>
                        <img src="assets/images/Batu-batu1_full.jpeg" alt="Batu-Batu National High School campus building, two-story concrete with cream and blue trim, trellised grounds in front." loading="lazy" width="738" height="415" >
                        <figcaption>Batu-Batu National High School &mdash; the campus building as documented this academic year.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/Batu-batu2_full.jpeg" alt="Aerial view of a Tawi-Tawi stilt-house coastal village with mountains in the background." loading="lazy" width="479" height="640" >
                        <figcaption>An aerial view of the surrounding coastal community, showing the stilt-house architecture typical of Tawi-Tawi.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/Batu-batu3_full.jpeg" alt="BBNIHS classroom or activity room with learners during a school event, tropical ceiling fans visible." loading="lazy" width="720" height="405" >
                        <figcaption>A BBNIHS classroom or activity room during a school event.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/Batu-batu4_full.jpeg" alt="Group photo of BBNIHS learners in a school setting." loading="lazy" width="640" height="480" >
                        <figcaption>A BBNIHS learner group photo, taken on campus.</figcaption>
                    </figure>
                </div>

                <h4 style="margin-top: var(--space-4); color: var(--sand);">More photos &mdash; subject to school confirmation</h4>
                <p class="form-note" style="margin-bottom: var(--space-3);">Each photo is captioned with the likely subject based on its filename. The school registrar will confirm the actual subject and date of each; until then, captions are provisional.</p>
                <div class="photo-strip">
                    <figure>
                        <img src="assets/images/bbnihs-baccalaureate.jpeg" alt="School event photo," loading="lazy" width="640" height="480" >
                                                <p>Filename suggests a Baccalaureate Mass / moving-up ceremony. <em>To confirm: which graduating batch and academic year?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/bbnihs-graduation.jpeg" alt="School event photo," loading="lazy" width="640" height="480" >
                                                <p>Filename suggests a graduation ceremony. <em>To confirm: which batch and date, and is the venue the BBNIHS gym or another location?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/bbnihs-legacy.jpg" alt="School event photo," loading="lazy" width="554" height="554" >
                                                <p>Filename suggests a legacy / alumni event. <em>To confirm: which alumni batch, and is the photo BBNIHS-specific?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/bbnihs-scholarship.jpeg" alt="School event photo," loading="lazy" width="738" height="415" >
                                                <p>Filename suggests a scholarship / awards event. <em>To confirm: which scholarship program and school year?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/bbnihs-staff.jpeg" alt="BBNIHS faculty and staff group photo." loading="lazy" width="720" height="405" >
                        <figcaption>BBNIHS faculty and staff group photo (vision-verified). Individual names are listed in the faculty directory, pending confirmation from the school registrar.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/classroom1.jpeg" alt="Classroom photo," loading="lazy" width="800" height="533" >
                                                <p>Classroom interior. <em>To confirm: which grade level and subject, and is this BBNIHS or another school?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/classroom2.jpeg" alt="Classroom photo," loading="lazy" width="480" height="640" >
                                                <p>Classroom interior. <em>To confirm: which grade level and subject, and is this BBNIHS or another school?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-01.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="554" height="554" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-02.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="601" height="510" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-03.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="678" height="452" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-04.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="678" height="452" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-05.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="678" height="452" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-06.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="554" height="554" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-08.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="465" height="659" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-09.jpeg" alt="School photo, awaiting confirmation." loading="lazy" width="554" height="554" >
                                                <p>Generic filename. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-campus.jpg" alt="Campus photo, awaiting confirmation." loading="lazy" width="1600" height="1200" >
                                                <p>Filename suggests a campus shot. <em>To confirm: is this BBNIHS or another school, and is the date recent?</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-education.jpg" alt="Education photo, awaiting confirmation." loading="lazy" width="1600" height="1066" >
                                                <p>Filename suggests an education-themed photo. <em>To confirm: subject, date, and source of this image.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/tawi-bongao.jpg" alt="Bongao, Tawi-Tawi &mdash; stilt houses over water with mountains in the distance." loading="lazy" width="1600" height="1201" >
                        <figcaption>Bongao, Tawi-Tawi &mdash; the provincial capital (vision-verified). Tawi-Tawi is the southernmost province of the Philippines, an archipelago of over 100 islands.</figcaption>
                    </figure>
                    <figure>
                        <img src="assets/images/tawi-bajau-children.jpeg" alt="Bajau / Sama children, Tawi-Tawi &mdash; awaiting confirmation." loading="lazy" width="601" height="510" >
                                                <p>Filename suggests Bajau or Sama children. <em>To confirm: location, year, and source. (Important: visual depictions of indigenous children in Tawi-Tawi must be handled with care and with community consent for public use.)</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/tawi-boatrace.jpeg" alt="Boat race, Tawi-Tawi &mdash; awaiting confirmation." loading="lazy" width="678" height="452" >
                                                <p>Filename suggests a boat race. <em>To confirm: event name, date, and whether it took place in Tawi-Tawi or another province.</em></p>
                    </figure>
                    <figure>
                        <img src="assets/images/img-campus-small.jpg" alt="Campus photo, awaiting confirmation." loading="lazy" width="1600" height="1200" >
                                                <p>Filename suggests a campus shot. <em>To confirm: is this BBNIHS or another school, and is the date recent? (Awaiting vision verification before final caption.)</em></p>
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
                            <div class="form-field"><label><input type="checkbox" id="doc_bc" name="doc_bc"> Birth Certificate (PSA / NSO)</label></div>
                            <div class="form-field"><label><input type="checkbox" id="doc_rc" name="doc_rc"> Report Card</label></div>
                            <div class="form-field"><label><input type="checkbox" id="doc_tc" name="doc_tc"> Transfer Credentials</label></div>
                            <div class="form-field"><label><input type="checkbox" id="doc_other" name="doc_other"> Other Required Documents</label></div>
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
                <div class="routing-table-wrap">
                <table class="routing-table">
                    <thead><tr><th>Your concern</th><th>Contact</th></tr></thead>
                    <tbody>
                        <tr><td>Enrollment, status, learner records</td><td>Batu-Batu NHS &middot; (062) 992-4151 (DepEd Tawi-Tawi Schools Division Office)</td></tr>
                        <tr><td>Application status, online form issues</td><td>SmartCampus project team &middot; <a href="mailto:smartcampus@bbnihs.edu.ph">smartcampus@bbnihs.edu.ph</a></td></tr>
                        <tr><td>Website, technical support</td><td>SmartCampus project team &middot; <a href="mailto:smartcampus@bbnihs.edu.ph">smartcampus@bbnihs.edu.ph</a></td></tr>
                        <tr><td>School policies, complaints, learner protection</td><td>Batu-Batu NHS &middot; (062) 992-4151 (DepEd Tawi-Tawi Schools Division Office)</td></tr>
                        <tr><td>Other / general</td><td>Batu-Batu NHS &middot; (062) 992-4151 (DepEd Tawi-Tawi Schools Division Office)</td></tr>
                    </tbody>
                </table>
                </div>

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
                    <h4>SmartCampus Project Team</h4>
                    <ul>
                        <li>KADIL, AL-KHALID I.</li>
                        <li>FATIMA JAHARA MENDOZA</li>
                        <li>JAMES KENNETH CAGANG</li>
                        <li>AVON MADALI</li>
                        <li>SAFRY MANALO</li>
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

    <!-- Floating quick contact — removed in v1.7 -->


    <!-- Scripts: tier detection and main behaviors first, then reveal/stepper -->
    <!-- deploy-trigger: 2daef6038 -->
    <script src="/js/main.js" defer></script>
    <script src="/js/reveal.js" defer></script>
    <script src="/js/stepper.js" defer></script>
    <script src="/js/enhancements.js" defer></script>
</body>
</html>
