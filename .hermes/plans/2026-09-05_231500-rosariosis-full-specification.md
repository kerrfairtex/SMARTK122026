# RosarioSIS Full Implementation Plan for BBNIHS SmartCampus K-12

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Build a complete, production-ready RosarioSIS fork for Batu-Batu National Integrated High School (BBNIHS) with full user journeys, menus, submenus, layouts, database schema, Docker configuration, and authentication — based on grounded audit evidence — while strictly preserving the existing public landing page design and layout.

**Architecture:** RosarioSIS core application with BBNIHS project-owned customizations layered on top. The public landing page (`public/index.php`) and its assets (`public/css/*`, `public/js/*`, `public/assets/images/*`) are PROTECTED and must never be modified. All customization occurs in BBNIHS-owned paths: `modules/SmartCampus/`, BBNIHS API files, `docker-entrypoint.sh`, `render.yaml`, `Dockerfile`, and new application-layer files.

**Tech Stack:** PHP 8.2-apache, PostgreSQL via Supabase pooler (`aws-0-ap-northeast-1.pooler.supabase.com:6543`), schema `kerrfairtex`, RosarioSIS core, FlatSIS theme, custom BBNIHS CSS/JS assets, PWA support via `pwabuilder-sw.js`.

## Current Context / Assumptions

**Grounded Facts (from AUDIT_HISTORY.md):**
- Repository: `kerrfairtex/SMARTK122026`, branch `mobile`, HEAD `1f3b80b258ccc8813efd80cc5b0f8257748c8dfe`
- Live URL: `https://smartcampk12.onrender.com/`
- Render service: `smartcampus-k12`
- Database: Supabase PostgreSQL, schema `kerrfairtex`, port `6543`, user `postgres.ebyepweqwihdvjecrufk`, SSL mode `require`
- Public landing page (`public/index.php`) is BBNIHS-owned and PROTECTED
- RosarioSIS core files are owned by upstream and must not be modified without evidence
- All 15 module menus extracted: School_Setup, Students, Users, Scheduling, Grades, Attendance, Eligibility, Discipline, Accounting, Student_Billing, Food_Service, Resources, Custom, SmartCampus, misc
- 4 user profiles: `admin`, `teacher`, `parent`, `student`
- Session variables: `$_SESSION['STAFF_ID']`, `$_SESSION['STUDENT_ID']`, `$_SESSION['UserSchool']`, `$_SESSION['UserSyear']`, `$_SESSION['UserMP']`, `$_SESSION['token']`, `$_SESSION['locale']`
- CSRF: `$_SESSION['token']` + `X-CSRF-Token` header
- Authentication fully traced in `index.php` lines 80-335
- `docker-entrypoint.sh` mutates runtime filesystem (idempotent)
- `healthz.php` proves DB connection + `SELECT 1`
- `search_path=kerrfairtex,public` required for all queries

**Blockers/Unknowns:**
- Database state unverified (no live DB access)
- Live browser rendering unverified
- RosarioSIS upstream version unpinned
- Some CSS class dependencies not fully mapped

## Proposed Approach

1. **Preserve the landing page** — `public/index.php`, `public/css/*`, `public/js/*`, `public/assets/images/*` are frozen. No further modification.
2. **Own the application layer** — All new code for menus, dashboards, workflows, and BBNIHS features goes in `modules/SmartCampus/` and new BBNIHS-owned files outside RosarioSIS core.
3. **Database-first** — Define the exact schema, tables, columns, and constraints in SQL before writing PHP. Apply via `rosariosis.sql` or migration scripts.
4. **Strangler migration** — Gradually replace RosarioSIS core ownership with BBNIHS-owned implementations, following the 12-phase strangler plan from the audit.
5. **Docker/Render** — Keep `Dockerfile` and `docker-entrypoint.sh` as-is; they are already working. Only modify if a defect is found.
6. **Testing** — Add PHPUnit tests in `tests/` for all BBNIHS code. RosarioSIS core tests are out of scope unless we need to verify behavior.

## Database Schema Specification

**Connection:** `pg_connect("host=aws-0-ap-northeast-1.pooler.supabase.com port=6543 dbname=postgres user=postgres.ebyepweqwihdvjecrufk password=... options='--search_path=kerrfairtex,public' sslmode=require")`

**Schema:** `kerrfairtex`

**Core Tables (from RosarioSIS):**
- `staff` — USERNAME, PROFILE, STAFF_ID, LAST_LOGIN, FAILED_LOGIN, PASSWORD, SYEAR
- `students` — STUDENT_ID, USERNAME, LAST_LOGIN, FAILED_LOGIN, PASSWORD, SYEAR
- `student_enrollment` — STUDENT_ID, SYEAR, START_DATE, END_DATE
- `access_log` — CREATED_AT, USER_AGENT, IP_ADDRESS, STATUS, SYEAR, USERNAME, PROFILE
- `schools` — ID, SYEAR, TITLE
- `config` — config_name, config_title, config_value
- `school_marking_periods` — marking_period_id, syear, school_id, title, start_date, end_date, mp_type
- `school_periods` — period_id, syear, school_id, title, sort_order, length_minutes
- `school_gradelevels` — gradelevel_id, syear, school_id, title, short_name, sort_order

**BBNIHS-Specific Tables:**
- `enrollment_periods` — enrollment_period_id, syear, school_id, title, start_date, end_date, max_applicants
- `enrollment_applications` — application_id, student_id, enrollment_period_id, status, submitted_at, reviewed_by, reviewed_at
- `enrollment_drafts` — draft_id, student_id, data_json, updated_at
- `about_content` — content_id, section, content_html, updated_at
- `access_log` (BBNIHS extension) — mirrors RosarioSIS access_log with additional fields

## Menu & Submenu Specification

**Source:** All 15 `modules/*/Menu.php` files extracted from repository.

### Admin Menu
- **School Setup**
  - Calendar
  - Portal Notes
  - Portal Polls
  - Marking Periods
  - Periods
  - Grade Levels
  - Schools
  - Copy School
  - School Fields
  - Configuration
  - Rollover
  - Access Log
- **Students**
  - Student Search
  - Add Student
  - Student Fields
  - Enrollment Codes
  - Student Breakdown
  - Letters
  - Labels
  - Print Student Info
  - Advanced Report
  - Add Drop
- **Users**
  - User Search
  - Add User
  - Preferences
  - Profiles
  - Exceptions
  - User Fields
- **Scheduling**
  - Scheduler
  - Mass Schedule
  - Mass Requests
  - Mass Drops
  - Print Schedules
  - Print Class Lists
  - Print Class Pictures
  - Print Requests
  - Schedule Report
  - Requests Report
  - Incomplete Schedules
  - Add Drop
  - Courses
  - Requests
  - Schedule
- **Grades**
  - Grades
  - Assignments
  - Anomalous Grades
  - Progress Reports
  - Gradebook Breakdown
  - Student Grades
  - Final Grades
  - GPA Rank List
  - Input Final Grades
  - Report Cards
  - Configuration
  - Report Card Grades
  - Report Card Comments
  - Report Card Comment Codes
  - Edit History Marking Periods
  - Edit Report Card Grades
  - Mass Create Assignments
- **Attendance**
  - Take Attendance
  - Administration
  - Add Absences
  - Teacher Completion
  - Percent
  - Daily Summary
  - Fix Daily Attendance
  - Duplicate Attendance
  - Attendance Codes
- **Eligibility**
  - Student
  - Add Activity
  - Student List
  - Teacher Completion
  - Activities
  - Entry Times
  - Enter Eligibility
- **Discipline**
  - Referrals
  - Make Referral
  - Category Breakdown
  - Category Breakdown Time
  - Student Field Breakdown
  - Referral Log
  - Discipline Form
- **Accounting**
  - Incomes
  - Expenses
  - Salaries
  - Staff Payments
  - Daily Transactions
  - Staff Balances
  - Statements
  - Categories
- **Student Billing**
  - Student Fees
  - Student Payments
  - Mass Assign Fees
  - Mass Assign Payments
  - Student Balances
  - Daily Transactions
  - Statements
- **Food Service**
  - Accounts
  - Statements
  - Transactions
  - Serve Menus
  - Activity Report
  - Transactions Report
  - Menu Reports
  - Reminders
  - Daily Menus
  - Menu Items
  - Menus
  - Kiosk
- **Resources**
  - Resources
- **Custom**
  - [Custom module entries]
- **SmartCampus**
  - SmartCampus Dashboard
  - Enrollment
  - Take Attendance
  - Discipline Log
- **misc**
  - Portal (dashboard)
  - Choose Course
  - Choose Request
  - Export
  - View Contact

### Teacher Menu
- Students (limited)
- Scheduling (own classes)
- Grades (own classes)
- Attendance (own classes)
- Eligibility (own classes)
- Discipline (referrals)
- SmartCampus (limited)

### Parent Menu
- Students (own children)
- Grades (own children)
- Attendance (own children)
- Eligibility (own children)
- Discipline (own children)
- Food Service (own children)
- SmartCampus (limited)

### Student Menu
- Forced to parent view per `modules/SmartCampus/Menu.php` line 43
- Same as parent menu

## User Journeys

### Journey 1: Public Visitor
1. Visits `https://smartcampk12.onrender.com/`
2. Views landing page with school info, photos, orbital animation
3. Clicks "Sign In" → redirects to `/login.php`
4. No registration path from landing page

### Journey 2: Admin Login
1. Visits `/login.php`
2. Enters username/password
3. System validates against `staff` table with `UPPER(USERNAME)` + `SYEAR`
4. `session_regenerate_id(true)` called
5. CSRF token generated via `openssl_random_pseudo_bytes(16)`
6. Redirect to `Modules.php`
7. Sees full admin menu with all 15 modules
8. Can access School Setup, Users, Scheduling, Grades, etc.

### Journey 3: Teacher Login
1. Visits `/login.php`
2. Enters username/password
3. System validates against `staff` table
4. Redirect to `Modules.php`
5. Sees limited teacher menu
6. Can access own classes, grades, attendance, eligibility

### Journey 4: Parent Login
1. Visits `/login.php`
2. Enters username/password
3. System validates against `students` table via `student_enrollment` date checks
4. Redirect to `Modules.php`
5. Sees parent menu with own children's data
6. Can view grades, attendance, eligibility, discipline for children

### Journey 5: Student Login
1. Visits `/login.php`
2. Enters username/password
3. System validates against `students` table
4. Redirect to `Modules.php`
5. Forced to parent view (per `modules/SmartCampus/Menu.php` line 43)
6. Sees same menu as parent

### Journey 6: BBNIHS Enrollment (SmartCampus)
1. Public visitor or logged-in user accesses enrollment
2. Fills out enrollment application form
3. System saves to `enrollment_applications` table
4. Admin reviews application
5. Admin approves/rejects
6. Student record created in `students` table
7. Student can login with provided credentials

### Journey 7: SmartCampus Dashboard
1. Any logged-in user accesses dashboard
2. System shows role-specific widgets
3. Admin sees system-wide stats
4. Teacher sees own class stats
5. Parent sees own children's stats
- Student sees own stats (via parent view)

## Layout Specification

### Landing Page (`public/index.php`) — PROTECTED
- Single-file HTML document (~84.7 KB)
- Inline CSS tokens + critical CSS in `<head>`
- External CSS: `public/css/tokens.css`, `public/css/base.css`, `public/css/components.css`
- External JS: `public/js/main.js`, `public/js/reveal.js`, `public/js/enhancements.js`, `public/js/stepper.js`
- Hero section with school logo (`logo.jpg`) and orbital animation SVG
- Photo gallery with school images
- PWA manifest at `/public/manifest.json`
- Service worker at `/pwabuilder-sw.js`
- Build marker injected by `docker-entrypoint.sh`: `<!-- build: SHORT_SHA TIMESTAMP -->`
- CACHE_NAME: `smartcamp-k12-SHORT_SHA`
- Responsive breakpoints: 720px, 860px, 861px
- A11y features: skip-link, focus-visible, prefers-reduced-motion, save-data gates

### RosarioSIS Application Layout
- **Login:** `/login.php` (RosarioSIS core)
- **Dashboard:** `/Modules.php` after login
- **Sidebar:** `Side.php` — left navigation with module icons
- **Top bar:** School name, user info, logout
- **Content area:** Module-specific PHP pages
- **Footer:** `Bottom.php` — copyright, version info
- **Theme:** FlatSIS (default) or WPadmin

### SmartCampus Module Layout
- Dashboard: `modules/SmartCampus/SmartCampus.php`
- Enrollment: `modules/SmartCampus/Enrollment.php`
- Attendance: `modules/SmartCampus/TakeAttendance.php`
- Discipline: `modules/SmartCampus/DisciplineLog.php`

## Docker & Deployment Configuration

### Dockerfile (existing, PROTECTED)
```dockerfile
FROM php:8.2-apache
# ... existing configuration ...
EXPOSE 10000
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
```

### docker-entrypoint.sh (existing, PROTECTED)
- Generates `config.inc.php` from environment variables
- Configures Apache port from `$PORT` (Render provides this, default 10000)
- Swaps landing page: `public/index.php` → `/`, `index.php` → `login.php`
- Symlinks landing assets: `/assets/images`, `/css`, `/js`
- Injects build marker and CACHE_NAME from `$RENDER_GIT_COMMIT`
- Sets writable directories for uploads

### render.yaml (existing, PROTECTED)
```yaml
services:
  - type: web
    name: smartcampus-k12
    runtime: docker
    repo: https://github.com/kerrfairtex/SMARTK122026
    branch: mobile
    healthCheckPath: /healthz.php
    envVars:
      - key: DB_SERVER
        value: aws-0-ap-northeast-1.pooler.supabase.com
      - key: DB_PORT
        value: "6543"
      - key: DB_NAME
        value: postgres
      - key: DB_USER
        value: postgres.ebyepweqwihdvjecrufk
      - key: DB_PASSWORD
        sync: true
      - key: DEFAULT_SYYEAR
        value: "2026"
      - key: THEME
        value: FlatSIS
      - key: SUPABASE_SSL_MODE
        value: require
      - key: CORS_ORIGIN
        value: https://smartk-122026.vercel.app
    disk:
      name: smartcampus-data
      mountPath: /var/www/html/assets/FileUploads
      sizeGB: 1
```

## Step-by-Step Implementation Plan

### Phase 1: Database Schema & Migrations
**Objective:** Create all required tables in `kerrfairtex` schema

### Task 1.1: Create BBNIHS schema migration SQL
**Files:**
- Create: `rosariosis-spec/004_bbnihs_schema.sql`
- Test: `tests/Integration/DatabaseSchemaTest.php`

**Step 1:** Write SQL migration creating BBNIHS tables
```sql
-- rosariosis-spec/004_bbnihs_schema.sql
SET search_path = kerrfairtex,public;

CREATE TABLE IF NOT EXISTS enrollment_periods (
    enrollment_period_id SERIAL PRIMARY KEY,
    syear INTEGER NOT NULL,
    school_id INTEGER NOT NULL,
    title VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    max_applicants INTEGER,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS enrollment_applications (
    application_id SERIAL PRIMARY KEY,
    student_id INTEGER,
    enrollment_period_id INTEGER REFERENCES enrollment_periods(enrollment_period_id),
    status VARCHAR(20) DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT NOW(),
    reviewed_by INTEGER REFERENCES staff(STAFF_ID),
    reviewed_at TIMESTAMP,
    data_json JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS enrollment_drafts (
    draft_id SERIAL PRIMARY KEY,
    student_id INTEGER,
    data_json JSONB,
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS about_content (
    content_id SERIAL PRIMARY KEY,
    section VARCHAR(50) UNIQUE NOT NULL,
    content_html TEXT,
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS bbnihs_access_log (
    log_id SERIAL PRIMARY KEY,
    user_id INTEGER,
    user_type VARCHAR(10),
    action VARCHAR(50),
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

**Step 2:** Run migration against test database
```bash
# Requires DB_PASSWORD from user
export PGPASSWORD="<user-provided-password>"
psql -h aws-0-ap-northeast-1.pooler.supabase.com -p 6543 -U postgres.ebyepweqwihdvjecrufk -d postgres -f rosariosis-spec/004_bbnihs_schema.sql
```
Expected: Tables created successfully

**Step 3:** Write PHPUnit test verifying tables exist
```php
// tests/Integration/DatabaseSchemaTest.php
public function test_bbnihs_tables_exist(): void
{
    $tables = ['enrollment_periods', 'enrollment_applications', 'enrollment_drafts', 'about_content', 'bbnihs_access_log'];
    foreach ($tables as $table) {
        $result = pg_query($this->conn, "SELECT to_regclass('kerrfairtex.$table')");
        $this->assertNotFalse(pg_fetch_result($result, 0, 0));
    }
}
```

**Step 4:** Run test
```bash
vendor/bin/phpunit tests/Integration/DatabaseSchemaTest.php
```
Expected: PASS

**Step 5:** Commit
```bash
git add rosariosis-spec/004_bbnihs_schema.sql tests/Integration/DatabaseSchemaTest.php
git commit -m "feat: add BBNIHS schema migration and test"
```

---