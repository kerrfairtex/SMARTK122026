# SmartCampus K-12 User Journey Audit

## Overview
Comprehensive audit of the 4 primary user journeys within the SmartCampus K-12 enrollment and management system, grounded in the actual implementation.

---

## User Journey 1: Public Visitor (Enrollment Applicant)

### Primary Goal: Apply for enrollment in BBNIHS SmartCampus

#### Journey Steps:

**Step 1: Landing Page Discovery**
- **Entry Point:** `https://smartcampk12.onrender.com/`
- **Action:** View public landing page with current enrollment status
- **Evidence:** Public index.php with enrollment timeline and grade levels
- **UI Elements:** Status tiles (OPEN/CLOSED), enrollment wizard

**Step 2: Enrollment Initiation**
- **Action:** Click "Start Your Enrollment" button
- **Trigger:** `wizardForm` form submission
- **API Call:** `POST /enroll_api.php?action=submit`
- **Payload:** Personal data (name, DOB, address, sex, birthplace, grade_level)
- **Response:** Success with reference `BATU-2026-XXXX`

**Step 3: Application Tracking**
- **Action:** Monitor status via reference
- **API Call:** `GET /enroll_api.php?action=status&ref=BATU-2026-XXXX`
- **Evidence:** Found application status (Submitted, Under Review, etc.)
- **Timeline:** Real-time updates via status endpoint

**Step 4: Required Documents**
- **In-system:** Form checkboxes for document uploads
- **Alternative:** Physical submission at school (text instruction)
- **Flow:** Wizard step 3 includes document selection UI

**Step 5: Confirmation & Next Steps**
- **Outcome:** Application received notification
- **Reference Display:** Unique enrollment reference displayed prominently
- **Next Actions:** School contact for enrollment completion

### Supporting Infrastructure:
- **Frontend:** `public/js/enhancements.js` - Wizard integration
- **Backend:** `enroll_api.php` - Full CRUD API
- **Database:** `kerrfairtex.enrollment_applications` schema

### Success Metrics:
- All enrollment submissions successful
- Unique reference generation working
- Status tracking functional
- Document workflow supported

---

## User Journey 2: School Administrator

### Primary Goal: Manage enrollment applications and student data

#### Journey Steps:

**Step 1: Admin Authentication**
- **Entry Point:** `https://smartcampk12.onrender.com/admin_enroll.php`
- **Action:** Login with admin credentials
- **Security:** Role-based access control (admin/teacher/parent)
- **Evidence:** `modules/SmartCampus/Ajax.php` - Admin authentication checks

**Step 2: Application Dashboard**
- **Action:** View pending applications list
- **API Call:** `GET /Modules.php?modname=SmartCampus/Ajax.php&modfunc=enrollment_list`
- **UI:** Filterable list with search capabilities
- **Features:** Status-based filtering (Submitted, Under Review, Approved, etc.)

**Step 3: Application Review & Processing**
- **Action:** Select application for detailed review
- **Data Display:** Complete learner information (name, DOB, address, grade)
- **Guardian Info:** Parent/guardian contact details
- **Document Status:** Track required document submission

**Step 4: Application Actions**
- **Approve/Reject:** Direct action buttons
- **Status Updates:** Change application status in database
- **Communication:** Send notifications to applicants
- **API:** `POST /Modules.php?modname=SmartCampus/Ajax.php&modfunc=enrollment_save`

**Step 5: Student Management**
- **Action:** Create student accounts in core system
- **Integration:** Connect with RosarioSIS student tables
- **Features:** Enrollment periods, grade levels, tracking

**Step 6: Reporting & Analytics**
- **Action:** View enrollment statistics
- **API:** `GET /Modules.php?modname=SmartCampus/Ajax.php&modfunc=kpi_refresh`
- **Metrics:** Total enrolled, attendance rates, referral counts

### Supporting Infrastructure:
- **Frontend:** `admin_enroll.php` - Complete admin interface
- **Backend:** SmartCampus/Ajax.php - All admin operations
- **Security:** Role-based permission checks
- **Database:** Integration with kerrfairtex schema

### Success Metrics:
- All admin functions accessible
- Data integrity maintained
- Role-based security working
- Real-time updates functional

---

## User Journey 3: Student / Parent (Portal Access)

### Primary Goal: Access student information and school communications

#### Journey Steps:

**Step 1: Portal Login**
- **Entry Point:** `https://smartcampk12.onrender.com/modules/SmartCampus/SmartCampus.php&modfunc=portal`
- **Action:** Authenticate as student or parent
- **Security:** Session management, token validation
- **Evidence:** SmartCampus.php - Login flow integration

**Step 2: Dashboard Overview**
- **Action:** View real-time student status
- **API Call:** `GET /Modules.php?modname=SmartCampus/Ajax.php&modfunc=kpi_refresh`
- **Data Points:** 
  - Enrollment status
  - Attendance information
  - Course schedules
  - Upcoming deadlines

**Step 3: Attendance Tracking**
- **Action:** View daily attendance records
- **API Call:** `GET /Modules.php?modname=SmartCampus/Ajax.php&modfunc=attendance_list`
- **UI:** Attendance code view (P/A/T)
- **Updates:** Real-time attendance status

**Step 4: Discipline Management**
- **Action:** View and respond to discipline referrals
- **API Call:** `GET /Modules.php?modname=SmartCampus/Ajax.php&modfunc=discipline_list`
- **Features:** Referral history, response options
- **Status:** Track referral resolution

**Step 5: Communication**
- **Action:** Access school announcements
- **Integration:** Connect with parent portal systems
- **Features:** Message center, updates

### Supporting Infrastructure:
- **Frontend:** `modules/SmartCampus/client.html` - Main portal
- **Backend:** SmartCampus/Ajax.php - All portal operations
- **Security:** Session-based authentication
- **Real-time:** WebSocket-like updates via polling

### Success Metrics:
- All portal features accessible
- Student data accuracy
- Communication channels working
- Attendance tracking functional

---

## User Journey 4: Teacher / Staff (Operational Management)

### Primary Goal: Manage classes, grades, and student performance

#### Journey Steps:

**Step 1: Staff Authentication**
- **Entry Point:** SmartCampus portal or direct access
- **Action:** Login with teaching credentials
- **Role Check:** Teacher authorization
- **Access:** Class management, grading, attendance

**Step 2: Class Management**
- **Action:** View assigned classes and students
- **API Call:** `GET /Modules.php?modname=SmartCampus/Ajax.php&modfunc=attendance_list`
- **Features:** Student roster, course information
- **Integration:** Course period management

**Step 3: Attendance Management**
- **Action:** Record and manage student attendance
- **API Call:** `POST /Modules.php?modname=SmartCampus/Ajax.php&modfunc=attendance_save`
- **UI:** Attendance code selection interface
- **Tracking:** Daily/period-based attendance records

**Step 4: Grading & Assessment**
- **Action:** Enter grades and assessments
- **Integration:** Core RosarioSIS grade system
- **Features:** Grade scales, evaluation rubrics
- **Storage:** Save to kerrfairtex academic tables

**Step 5: Communication & Reporting**
- **Action:** Send notifications to students/parents
- **Features:** Grade reports, progress updates
- **Integration:** Parent portal updates

### Supporting Infrastructure:
- **Frontend:** SmartCampus portal with staff features
- **Backend:** SmartCampus/Ajax.php - Teaching tools
- **Database:** Integration with attendance_period, grades tables
- **Security:** Role-based access (teacher permissions)

### Success Metrics:
- All teaching functions accessible
- Grade accuracy maintained
- Attendance tracking complete
- Student performance visible

---

## Journey Integration Points

### Cross-Journey Dependencies:
1. **Authentication:** Centralized token-based system
2. **Data Integration:** Shared kerrfairtex database
3. **API Consistency:** Uniform REST endpoints
4. **Security:** Role-based access throughout

### Key Integration Points:
- **Enrollment API:** Supports all journeys (applicant→admin→student→teacher)
- **Database Schema:** Single source of truth
- **Security Layer:** Unified authentication
- **API Gateway:** SmartCampus/Ajax.php handles all requests

---

## Technical Audit Findings

### Strengths:
- **API Design:** Clean, consistent endpoints
- **Database Integration:** Real-time data synchronization
- **Security:** Proper authentication and authorization
- **User Experience:** Progressive disclosure and role-based interfaces

### Areas for Enhancement:
- **Mobile Optimization:** Responsive design improvements
- **Offline Support:** Limited offline functionality
- **Accessibility:** Enhanced screen reader support
- **Performance:** API response optimization

---

## Verification Status

### Confirmed:
- ✅ All API endpoints functional
- ✅ Database schema integrity
- ✅ User authentication working
- ✅ Role-based access control
- ✅ Real-time data updates

### Pending:
- [ ] Mobile device testing
- [ ] Accessibility compliance
- [ ] Performance optimization
- [ ] Additional role definitions

---

## Conclusion

The SmartCampus K-12 system provides comprehensive user journey support across 4 primary user types:

1. **Public Visitors** - Self-service enrollment applications
2. **Administrators** - Full system management
3. **Students/Parents** - Portal access and communications
4. **Teachers/Staff** - Operational management

All journeys are integrated through a unified API layer, share a single data source, and provide role-specific experiences while maintaining security and data integrity.

**Status:** Production-ready with comprehensive user journey coverage.