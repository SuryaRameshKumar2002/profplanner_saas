# ProfPlanner - Implementation Checklist

Complete this checklist to set up and deploy ProfPlanner.

---

## Phase 1: Local Setup (5 minutes)

### Prerequisites
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ or MariaDB installed
- [ ] Web server running (XAMPP, WAMP, or built-in PHP)
- [ ] phpMyAdmin accessible

### Database Creation
- [ ] Open phpMyAdmin
- [ ] Create database: `profplanner`
- [ ] Collation: `utf8mb4_unicode_ci`
- [ ] Open `db_buses_migration.sql`
- [ ] Copy entire file content
- [ ] Paste into phpMyAdmin SQL tab
- [ ] Execute (click "Go")
- [ ] Verify all 8 tables created:
  - [ ] users
  - [ ] rollen
  - [ ] roosters
  - [ ] buses
  - [ ] werknemers_buses
  - [ ] opdrachtgevers
  - [ ] afwezigheden
  - [ ] uploads

### Configuration
- [ ] Edit `config.php`
- [ ] Update database host (localhost or 127.0.0.1)
- [ ] Update database name (profplanner)
- [ ] Update database user (root)
- [ ] Update database password (if needed)
- [ ] Save file

### Test Users
- [ ] In phpMyAdmin, run SQL:
  ```sql
  INSERT INTO rollen (naam) VALUES ('werkgever'), ('werknemer');
  INSERT INTO users (naam, email, wachtwoord, rol_id) VALUES 
  ('Werkgever Test', 'werkgever@test.nl', 
  '$2y$10$DH0D6GIGlLeWO6d9TjPqRehPEYfq3PypJzcRRvPvBdvjKWLEDAFva', 1),
  ('Werknemer Test', 'werknemer@test.nl', 
  '$2y$10$DH0D6GIGlLeWO6d9TjPqRehPEYfq3PypJzcRRvPvBdvjKWLEDAFva', 2);
  ```
- [ ] Password is: `password123`
- [ ] Verify users exist: `SELECT * FROM users;`

### Folders
- [ ] Create `/uploads/` folder in project root
- [ ] Linux/Mac: Run `chmod 755 uploads`

### Access
- [ ] Open browser: `http://localhost/profplanner/`
- [ ] See ProfPlanner home page
- [ ] Two login buttons appear

---

## Phase 2: Local Testing (30 minutes)

### Authentication
- [ ] Login as Werkgever:
  - [ ] Click "Inloggen als Werkgever"
  - [ ] Email: `werkgever@test.nl`
  - [ ] Password: `password123`
  - [ ] Redirects to `/werkgever.php`
  - [ ] See 8 dashboard cards

- [ ] Login as Werknemer:
  - [ ] Go home (click logo)
  - [ ] Click "Inloggen als Werknemer"
  - [ ] Email: `werknemer@test.nl`
  - [ ] Password: `password123`
  - [ ] Redirects to `/werknemer.php`
  - [ ] See stats and team info

- [ ] Test Logout:
  - [ ] Click "Logout" in header
  - [ ] Redirects to login page
  - [ ] Session cleared

### Navigation
- [ ] Header navigation shows:
  - [ ] Dashboard (role-specific)
  - [ ] Weekplanning
  - [ ] Roosters
  - [ ] Logout button with username

### Roosters (Jobs)
- [ ] Create Job:
  - [ ] Werkgever > Roosters & Klussen > + Klus aanmaken
  - [ ] Fill all fields:
    - [ ] Datum: Today or future
    - [ ] Starttijd: 09:00
    - [ ] Eindtijd: 17:00
    - [ ] Titel: "Test Klus"
    - [ ] Locatie: "Amsterdam"
    - [ ] Omschrijving: "Test details"
    - [ ] Werknemer: Select from list
    - [ ] Opdrachtgever: Select from list
    - [ ] Bus: Select HV01
  - [ ] Click "Klus aanmaken"
  - [ ] Redirects to job detail
  - [ ] Bus badge shows "HV01"

- [ ] View Roosters:
  - [ ] Click "Roosters" in menu
  - [ ] See all jobs in table
  - [ ] Bus column shows color badge
  - [ ] Click "Open" → detail page

- [ ] Update Status (Werknemer):
  - [ ] Login as Werknemer
  - [ ] Open a job from roosters
  - [ ] Scroll to "Status bijwerken"
  - [ ] Select: "Klus afgerond"
  - [ ] Add toelichting
  - [ ] Click "Opslaan"
  - [ ] Status badge turns green

### Bus Management
- [ ] Access Bus Management:
  - [ ] Werkgever > Bus & Team Beheer
  - [ ] See HV01, HV02, DVI listed

- [ ] Create Bus:
  - [ ] Click "Toevoegen" section
  - [ ] Naam: "TEST_BUS"
  - [ ] Omschrijving: "Test team"
  - [ ] Pick color
  - [ ] Click "Toevoegen"
  - [ ] Bus appears in list

- [ ] Assign Workers:
  - [ ] Click "Werknemers" on a bus
  - [ ] Check/uncheck workers
  - [ ] Click "Opslaan"
  - [ ] Worker count updates

- [ ] Delete Bus:
  - [ ] Click "Verwijderen" on TEST_BUS
  - [ ] Confirm deletion
  - [ ] Bus removed from list

### Weekly Planner
- [ ] View Planner:
  - [ ] Click "Weekplanning" in header
  - [ ] See current week dates
  - [ ] Jobs grouped by bus section

- [ ] Navigate Weeks:
  - [ ] Click "Volgende week →" 
  - [ ] Dates advance 7 days
  - [ ] Click "← Vorige week"
  - [ ] Dates go back
  - [ ] Click "Vandaag"
  - [ ] Returns to current week

- [ ] Verify Grouping:
  - [ ] Each bus shows as separate section
  - [ ] Bus color on left border
  - [ ] All jobs for that bus listed below
  - [ ] Werknemer column visible (for employer only)

### File Uploads
- [ ] Werknemer uploads photo:
  - [ ] Open a job in roosters
  - [ ] Click "Foto uploaden"
  - [ ] Select image file
  - [ ] Click upload
  - [ ] Photo appears in job detail

- [ ] Verify file stored:
  - [ ] Check `/uploads/` folder
  - [ ] File exists with unique name
  - [ ] File accessible in browser

### Employee Management
- [ ] Werknemers page:
  - [ ] Werkgever > Werknemers > Beheren
  - [ ] See all employees listed
  - [ ] Show name, email, status

### Client Management
- [ ] Klanten page:
  - [ ] Werkgever > Klanten > Beheren
  - [ ] Add new client:
    - [ ] Bedrijfsnaam: "Test BV"
    - [ ] Email: test@test.nl
    - [ ] Save
  - [ ] Client appears in list
  - [ ] Delete test client

### User Profile
- [ ] Access profile (any user):
  - [ ] Click username in top-right
  - [ ] Go to "Mijn Profiel"
  - [ ] See profile info
  - [ ] Update naam → Save
  - [ ] Change password:
    - [ ] Old password: password123
    - [ ] New password: newpassword123
    - [ ] Confirm
    - [ ] Save
  - [ ] Logout and login with new password

### Settings
- [ ] Werkgever > Settings:
  - [ ] See table count
  - [ ] See bus count
  - [ ] See user count
  - [ ] See rooster count

### Responsive Design
- [ ] Desktop (1200px+):
  - [ ] Multi-column grid layout
  - [ ] All table columns visible
  - [ ] Side-by-side cards

- [ ] Tablet (768px - 1199px):
  - [ ] Press F12 → Device toolbar
  - [ ] Set to tablet size (iPad)
  - [ ] 2-column grid
  - [ ] Buttons stack properly

- [ ] Mobile (< 768px):
  - [ ] Press F12 → Device toolbar
  - [ ] Set to mobile size (iPhone 12)
  - [ ] Single column layout
  - [ ] Buttons full-width
  - [ ] Forms stack vertically
  - [ ] Tables scroll horizontally

---

## Phase 3: Database Verification

### Verify Data Integrity
- [ ] In phpMyAdmin SQL tab, run:
  ```sql
  SELECT COUNT(*) FROM users;
  SELECT COUNT(*) FROM roosters;
  SELECT COUNT(*) FROM buses;
  SELECT COUNT(*) FROM werknemers_buses;
  ```
- [ ] Counts match expected (e.g., 2 users, 1+ roosters, 3+ buses)

### Verify Foreign Keys
- [ ] Create a rooster with bus_id = 1
- [ ] Delete buses table entry with id = 1
- [ ] Rooster should have bus_id = NULL (cascading)

### Verify Indexes
- [ ] In phpMyAdmin, check tables
- [ ] Tables should have indexes on:
  - [ ] roosters.datum
  - [ ] roosters.werknemer_id
  - [ ] roosters.bus_id

---

## Phase 4: Code Quality Review

### PHP Code
- [ ] No `var_dump()` or `print_r()` calls
- [ ] No `console.log()` statements
- [ ] No hardcoded credentials
- [ ] All database queries use prepared statements
- [ ] All user input escaped with h()
- [ ] Proper error handling in place

### Database
- [ ] All tables have proper charset (utf8mb4)
- [ ] Foreign keys defined
- [ ] Indexes on performance columns
- [ ] Cascading deletes configured

### Security
- [ ] Passwords hashed with bcrypt
- [ ] Session validation on all protected pages
- [ ] No directory listing (.htaccess)
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS prevention (HTML escaping)

---

## Phase 5: Before Production Deployment

### Configuration Changes
- [ ] Update `config.php` for Hostinger credentials
- [ ] Change default test users (create real users)
- [ ] Update email/contact info
- [ ] Configure session timeout
- [ ] Set up error logging

### Security Hardening
- [ ] Enable HTTPS (usually automatic on Hostinger)
- [ ] Protect config.php (add to .htaccess)
- [ ] Set proper file permissions (644 for .php, 755 for folders)
- [ ] Update all passwords to strong ones
- [ ] Disable directory listing

### File Organization
- [ ] Remove any test files
- [ ] Create .htaccess for clean URLs (optional)
- [ ] Ensure uploads folder created and writable
- [ ] Back up local database

### Documentation
- [ ] Prepare Hostinger account
- [ ] Have FTP credentials ready
- [ ] Have MySQL credentials ready
- [ ] Document any custom configuration
- [ ] Create backup of local database

---

## Phase 6: Hostinger Deployment

### Pre-Deployment (from DEPLOYMENT_GUIDE.md)
- [ ] Create Hostinger MySQL database
- [ ] Run full schema SQL in Hostinger phpMyAdmin
- [ ] Note database credentials
- [ ] Update config.php with Hostinger credentials

### FTP Upload
- [ ] Download FTP client (FileZilla recommended)
- [ ] Connect with Hostinger FTP credentials
- [ ] Navigate to `public_html/`
- [ ] Create folder: `profplanner`
- [ ] Upload all files to `public_html/profplanner/`
- [ ] Create empty `/uploads/` folder on server
- [ ] Set permissions: `chmod 755 uploads`

### Configuration for Production
- [ ] Update config.php database host (e.g., mysql.hostinger.com)
- [ ] Update config.php database name (e.g., u123456_profplanner)
- [ ] Update config.php database user (e.g., u123456_dbuser)
- [ ] Update config.php database password
- [ ] Upload updated config.php

### Security Setup
- [ ] Create .htaccess to protect config.php
- [ ] Add HTTPS redirect (if needed)
- [ ] Disable directory listing
- [ ] Set file permissions properly

---

## Phase 7: Production Testing

### Site Access
- [ ] Open `https://yourdomain.com/profplanner/`
- [ ] See home page load correctly
- [ ] Styling appears (green + grey theme)
- [ ] No errors in console (F12)

### Database Connection
- [ ] Try login
- [ ] Should connect to production database
- [ ] If error, check credentials in config.php

### Functionality Testing
- [ ] Login works (create production user first)
- [ ] Dashboard loads
- [ ] Create test job
- [ ] View in roosters list
- [ ] Check database entry created
- [ ] Upload photo
- [ ] View weekly planner
- [ ] Test bus management

### Performance
- [ ] Pages load within 2-3 seconds
- [ ] No 500 errors
- [ ] Database queries efficient
- [ ] Check Hostinger error logs: `/error_log`

### Monitoring
- [ ] Check error logs weekly
- [ ] Monitor disk usage
- [ ] Check database size
- [ ] Verify backups running

---

## Phase 8: Post-Deployment

### User Setup
- [ ] Create real user accounts:
  - [ ] Werkgevers (employers)
  - [ ] Werknemers (employees)
- [ ] Assign to buses/teams
- [ ] Provide login credentials securely
- [ ] Document passwords in secure location

### Data Migration (if applicable)
- [ ] Migrate existing jobs from old system
- [ ] Verify data integrity
- [ ] Test all reports/exports
- [ ] Confirm employees see correct jobs

### Training
- [ ] Train employers on dashboard
- [ ] Train employees on job updates
- [ ] Document workflow
- [ ] Create quick reference guide

### Monitoring & Maintenance
- [ ] Set up weekly error log review
- [ ] Monthly database maintenance
- [ ] Quarterly security audits
- [ ] Annual performance review

---

## Phase 9: Ongoing Operations

### Weekly Tasks
- [ ] Check error logs: `/error_log`
- [ ] Verify users can login
- [ ] Test job creation/assignment
- [ ] Monitor upload folder size

### Monthly Tasks
- [ ] Review user activity
- [ ] Check database size
- [ ] Verify backups completed
- [ ] Update any dependencies

### Quarterly Tasks
- [ ] Security audit
- [ ] Performance review
- [ ] Database optimization
- [ ] User feedback collection

### Annual Tasks
- [ ] Full system audit
- [ ] Plan upgrades/improvements
- [ ] Review and update security
- [ ] Capacity planning

---

## Documentation Checklist

### For You (Local Development)
- [ ] Read README.md - understand system
- [ ] Read QUICK_START.md - 5-minute setup
- [ ] Read TESTING_GUIDE.md - 12 test phases
- [ ] Read PROJECT_SUMMARY.md - what was built

### For Deployment
- [ ] Read DEPLOYMENT_GUIDE.md completely
- [ ] Complete each step in order
- [ ] Follow production checklist
- [ ] Test each phase before moving on

### For Operations
- [ ] Bookmark README.md for reference
- [ ] Keep TESTING_GUIDE.md for regression testing
- [ ] Keep DEPLOYMENT_GUIDE.md for troubleshooting
- [ ] Document any custom modifications

---

## Troubleshooting Quick Reference

| Issue | Solution | Doc |
|-------|----------|-----|
| Blank page | Check error_log, verify DB connection | TESTING_GUIDE.md |
| Login fails | Verify users in DB, check password hash | TESTING_GUIDE.md |
| Tables don't exist | Run db_buses_migration.sql again | QUICK_START.md |
| Uploads fail | Create /uploads folder, chmod 755 | TESTING_GUIDE.md |
| Slow loading | Check database indexes, query count | PROJECT_SUMMARY.md |
| CORS errors | Check file permissions, upload settings | TESTING_GUIDE.md |

---

## Checkpoints

### Checkpoint 1: Local Setup ✅
```
All 5-minute setup items complete
Database tables verified
Test users created
Site accessible at localhost
```

### Checkpoint 2: Local Testing ✅
```
All 12 test phases completed
No errors in logs
All features working
Responsive design verified
```

### Checkpoint 3: Production Ready ✅
```
Security hardening complete
Backups configured
Monitoring set up
Documentation complete
```

### Checkpoint 4: Live & Operational ✅
```
Users can login
Jobs being created
Photos uploading
Weekly planner in use
All exports working
```

---

## Final Verification

Before marking complete, verify:

- [ ] All tasks in Phase 1-2 checked
- [ ] Database verified in Phase 3
- [ ] Code quality reviewed in Phase 4
- [ ] Hostinger deployment complete (Phase 5-6)
- [ ] Production testing passed (Phase 7)
- [ ] Users trained and using system (Phase 8)
- [ ] Documentation read and understood (Doc checklist)
- [ ] Monitoring and maintenance planned (Phase 9)

---

## Sign-Off

**Local Development Verified:** _______________  Date: _______

**Production Deployment Complete:** _______________  Date: _______

**System Operational:** _______________  Date: _______

---

## Contact & Support

**For setup help:** Follow QUICK_START.md or TESTING_GUIDE.md

**For deployment:** Follow DEPLOYMENT_GUIDE.md or contact Hostinger support

**For bugs:** Check error logs and troubleshooting section above

**For features:** See PROJECT_SUMMARY.md - Future Enhancements section

---

**ProfPlanner v1.0.0 - Ready for Production** ✅
