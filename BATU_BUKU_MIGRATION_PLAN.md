# BATU-BATU NIHS Migration Plan (RosarioSIS rebrand)

## Goal
Rebrand existing RosarioSIS into BATU-BATU NATIONAL INTEGRATED HIGH SCHOOL SIS
(Philippine K-12 public school in Turtle Islands, Tawi-Tawi) while preserving core
functionality. All DB changes are ADDITIVE. Git history preserved (no force-push).

## Architecture facts (Phase 1 discovery)
- Entry: `index.php` (login), `Modules.php` (shell), `Warehouse.php` (head/foot).
- Branding source: DB `config` table — `TITLE` ('Rosario Student Information System'),
  `NAME` ('RosarioSIS'). Driven by `Config()` helper. NOT hard-coded in templates.
- School profile source: DB `schools` table (title, address, city, state, phone, www,
  principal, short_name). Editable via School_Setup/Schools.php UI.
- Logo: `assets/themes/{FlatSIS,WPadmin}/logo.png`. favicon: `favicon.ico`,
  `apple-touch-icon.png` in repo root.
- Grade levels: `school_gradelevels` table. Seed KG-KG..08. Add 09-12 (Philippine
  K-12: Kindergarten + Grades 1-12). Configure per-school availability via UI.
- Marking periods: `school_marking_periods` table. Seed FY + 2 Sem + 4 Quarters.
  Quarters already exist; rename labels to "Quarter 1..4" if needed. Keep configurable.
- LRN: does NOT exist. `students` table has `custom_200000000..011` custom columns.
  Add LRN via ADDITIVE migration + register as custom field (no column destruction).
- Demo seed users: admin/Admin, teacher/Teacher, parent/Parent. Passwords are bcrypt
  hashes; will be reset via First Login flow (already supported).
- Demo school: "Default School, 500 S. Street St., Springfield, IL" + www.rosariosis.org.
  Replace via config/seed, NOT hardcoded strings.
- CSP plugin: report-only mode. "CSP Violations - Reports" page is its admin UI — a
  legitimate feature, investigated in Phase 6.

## Phases (implementation order)
1. Discovery — DONE
2. Git checkpoint commit (clean tree -> commit 0)
3. Branding (titles, logo, favicon, metadata, footer copyright)
4. School config (BATU-BATU profile, school year, quarters, grade levels 09-12,
   subjects, demo-user reset guidance)
5. Philippine adaptation (terminology: School Year, Learner/Student, LRN, Adviser,
   Report Card; presentation-layer labels — no DB column renames)
6. UI modernization (responsive nav drawer, mobile tables, touch targets; low-bandwidth)
7. Security (investigate CSP, audit auth)
8. Testing (existing suite + smoke tests)
9. Final audit (repo-wide "Rosario"/"rosariosis" sweep; keep license/attribution)

## Non-destructive rules
- NEVER drop/overwrite tables or production data.
- Additive migrations only; preserve backward compatibility.
- Do not force-push or rewrite history.
- Do NOT remove RosarioSIS attribution in LICENSE/COPYRIGHT/composer.json/CHANGELOG.
- Replace demo data only where clearly identified as demo.

## Commit plan (small logical commits)
1. chore: inspect and prepare BATU-BATU NIHS migration
2. feat: rebrand system for BATU-BATU NIHS
3. feat: configure Philippine school structure
4. ui: modernize responsive administration interface
5. security: resolve CSP and security configuration
6. test: verify BATU-BATU SIS workflows
