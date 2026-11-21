<?php
/**
 * Privacy & Accessibility — public page policy.
 * This is a static policy page for the Batu-Batu public website.
 */
$school_name = 'Batu-Batu National High School';
$school_id = '305053';
$project_phone = '09637130812';
$project_email = 'info@batu-batunhs.edu.ph';
$project_facebook = 'https://www.facebook.com/SMARTCAMPK12';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy &amp; Accessibility — <?php echo htmlspecialchars($school_name); ?></title>
    <style>
        :root{
            --navy-deep:#0c1e3a; --navy-light:#16294a; --accent:#0ea5b7; --gray-300:#cbd5e1; --gray-500:#94a3b8; --white:#f8fafc;
        }
        *{box-sizing:border-box;}
        body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:var(--navy-deep);color:var(--gray-300);line-height:1.6;}
        .wrap{max-width:820px;margin:0 auto;padding:3rem 1.5rem;}
        h1{color:var(--white);font-size:1.8rem;}
        h2{color:var(--white);font-size:1.25rem;margin-top:2rem;border-left:4px solid var(--accent);padding-left:0.75rem;}
        p,li{font-size:0.95rem;}
        a{color:var(--accent);}
        .home{display:inline-block;margin-bottom:1.5rem;color:var(--accent);text-decoration:none;}
        .card{background:var(--navy-light);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:1.25rem 1.5rem;margin:1rem 0;}
        .foot{margin-top:2.5rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.06);font-size:0.8rem;color:var(--gray-500);}
    </style>
</head>
<body>
<div class="wrap">
    <a class="home" href="index.php">&larr; Back to Batu-Batu</a>
    <h1>Privacy &amp; Accessibility</h1>
    <p>This page explains how the Batu-Batu public website and the SmartCampus K&ndash;12 enrollment portal handle information. It applies to the public website only.</p>

    <h2>What we do not publish</h2>
    <div class="card">
        <p>To protect learners and families, the public website does <strong>not</strong> expose:</p>
        <ul>
            <li>Student names, student phone numbers, or student IDs</li>
            <li>Parent or guardian phone numbers</li>
            <li>Learner records, grades, or academic history</li>
            <li>Application documents or uploaded files</li>
            <li>Private staff or faculty personal numbers (without authorization)</li>
            <li>Private Facebook profiles</li>
        </ul>
        <p>Student, parent, and staff information stays inside the authenticated SmartCampus system, which requires a valid account and role-based access.</p>
    </div>

    <h2>Enrollment applications</h2>
    <div class="card">
        <p>When you submit an enrollment application through this site, the information you provide is stored in the SmartCampus database: the learner's name, birth date, sex, birthplace, address, grade level, school year and enrollment type; the parent or guardian's name, phone number and email; and the learner's previous school and last grade completed. Application <strong>status</strong> is the only detail shown when you look up your own reference number. Documents, parent contact numbers, and full records are not returned by the public status check.</p>
        <p>You can check an application only with the exact reference number you received. Authorized school administrators review applications through the authenticated system. When an application is accepted and the learner is enrolled, these details become part of the learner's school record, including the guardian as an emergency contact.</p>
    </div>

    <h2>Contact messages</h2>
    <div class="card">
        <p>Messages sent through the "Contact the SmartCampus Team" form reach the SmartCampus project team (technology and website support). They are <strong>not</strong> a substitute for official school or DepEd channels. For enrollment decisions, learner records, and school administrative matters, contact the school or the appropriate DepEd office.</p>
    </div>

    <h2 id="data-privacy">Your rights under the Data Privacy Act</h2>
    <div class="card">
        <p>Personal information collected through this site is processed in line with the Data Privacy Act of 2012 (Republic Act No. 10173). As a data subject, you have the right to:</p>
        <ul>
            <li>be informed about how your personal information is collected and used;</li>
            <li>access the personal information held about you or your child;</li>
            <li>correct information that is inaccurate or incomplete;</li>
            <li>object to processing, or ask for information to be blocked or erased when it is no longer needed or was processed unlawfully;</li>
            <li>obtain a copy of your information in a commonly used electronic format (data portability);</li>
            <li>file a complaint with the National Privacy Commission, and claim damages where applicable.</li>
        </ul>
        <p>To exercise any of these rights, contact the school using the details below. A parent or guardian may make the request on behalf of a learner who is a minor.</p>
        <p><strong>Where information is stored.</strong> The SmartCampus database is hosted with Supabase on Amazon Web Services in the Tokyo, Japan region, and the website runs on Render. Access to stored records requires a school staff account.</p>
    </div>

    <h2 id="accessibility">Accessibility</h2>
    <div class="card">
        <p>We aim to make this site usable for everyone, including users on low-bandwidth connections common in island communities:</p>
        <ul>
            <li>Responsive layout that works on phones and small screens</li>
            <li>High-contrast text and clear headings</li>
            <li>Enrollment designed for intermittent connectivity — you can save progress and submit later, or visit the school office for assistance</li>
            <li>Plain-language content and a "Who should I contact?" guide</li>
        </ul>
        <p>If you encounter a barrier, contact the SmartCampus project team and we will work to address it.</p>
    </div>

    <h2>Contact</h2>
    <div class="card">
        <p><strong>SmartCampus K&ndash;12 Project Contact</strong><br>
        Batu-Batu National High School — Project Contact<br>
        <a href="tel:<?php echo htmlspecialchars($project_phone); ?>"><?php echo htmlspecialchars($project_phone); ?></a><br>
        <a href="mailto:<?php echo htmlspecialchars($project_email); ?>"><?php echo htmlspecialchars($project_email); ?></a><br>
        <a href="<?php echo htmlspecialchars($project_facebook); ?>" target="_blank" rel="noopener">Facebook</a></p>
        <p>For official school matters: <?php echo htmlspecialchars($school_name); ?> (School ID <?php echo htmlspecialchars($school_id); ?>), Batu-Batu, Panglima Sugala, Tawi-Tawi, or the DepEd Schools Division Office &ndash; Tawi-Tawi.</p>
    </div>

    <div class="foot">
        &copy; 2026 SmartCampus K&ndash;12 &bull; <?php echo htmlspecialchars($school_name); ?> (School ID <?php echo htmlspecialchars($school_id); ?>) &bull; Batu-Batu, Panglima Sugala, Tawi-Tawi, BARMM
    </div>
</div>
</body>
</html>
