# ProfPlanner - Planning & Sales System

A comprehensive PHP + MySQL planning and sales system for managing jobs, teams, employees, and operations. Built with green + grey company branding and responsive design.

## Features

### Werkgever (Employer) Dashboard
- 📋 **Job Management (Roosters)** - Create, edit, and manage job assignments
- 📅 **Weekly Planning** - View weekly schedule grouped by bus/team
- 🚌 **Bus/Team Management** - Create teams (HV01, HV02, DVI, etc.) and assign workers
- 👥 **Employee Management** - Manage workers and their team assignments
- 💼 **Client Management** - Maintain list of opdrachtgevers (clients)
- ❌ **Absence Tracking** - Monitor employee absence reports
- 📤 **Uploads** - Store reference photos for jobs
- 📊 **Exports** - Export to Excel and PDF
- ⚙️ **Settings** - System configuration and database management

### Werknemer (Employee) Dashboard
- 📋 **My Jobs** - View assigned roosters and job details
- 📅 **Weekly Schedule** - Personal weekly planning view
- ✅ **Status Updates** - Mark jobs as completed, paused, or cancelled
- 📸 **Photo Uploads** - Upload job photos and progress images
- ❌ **Absence Reporting** - Report sick days or unavailability
- 👤 **Profile Management** - Update personal information and password

### Key Components
- **Authentication System** - Role-based login (Werkgever/Werknemer)
- **Session Management** - Secure session handling
- **Responsive Design** - Works on desktop, tablet, mobile
- **Company Branding** - Green (#16a34a) + Grey color scheme
- **Database** - MySQL with proper indexing and relationships
- **File Management** - Upload system for images and documents

---

## Installation & Setup

### Requirements
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache with .htaccess support or similar)

### Local Development Setup

#### 1. Clone or Download Project
```bash
# Download the project files
# Place in your web root (htdocs for XAMPP, www for WAMP, etc.)
```

#### 2. Create Database
```bash
# Open phpMyAdmin (http://localhost/phpmyadmin)
# Create new database: "profplanner"
```

#### 3. Run Database Migration
```bash
# In phpMyAdmin:
# 1. Select your "profplanner" database
# 2. Click "SQL" tab
# 3. Copy entire contents of: db_buses_migration.sql
# 4. Paste and click "Go"
# 5. This creates all necessary tables
```

#### 4. Configure Database Connection
Edit `config.php`:
```php
$db = new PDO(
  "mysql:host=127.0.0.1;dbname=profplanner;charset=utf8mb4",
  "root",
  ""  // Add password if needed
);
```

#### 5. Create Test Users
In phpMyAdmin SQL tab:
```sql
-- Insert roles if not present
INSERT INTO rollen (naam) VALUES ('werkgever'), ('werknemer');

-- Create test Werkgever
INSERT INTO users (naam, email, wachtwoord, rol_id) VALUES 
('Test Werkgever', 'werkgever@test.nl', '$2y$10$...HASH...', 1);

-- Create test Werknemer
INSERT INTO users (naam, email, wachtwoord, rol_id) VALUES 
('Test Werknemer', 'werknemer@test.nl', '$2y$10$...HASH...', 2);
```

To generate password hash:
```php
<?php echo password_hash('password123', PASSWORD_DEFAULT); ?>
```

#### 6. Create Uploads Folder
```bash
# Create folder at: /uploads
# Set permissions: chmod 755 uploads
```

#### 7. Access the Application
```
http://localhost/profplanner/
```

---

## Project Structure

```
profplanner/
├── index.php                      # Landing page
├── login.php                      # Login form (role-based)
├── logout.php                     # Logout handler
├── config.php                     # Database configuration
│
├── werkgever.php                  # Employer dashboard
├── werknemer.php                  # Employee dashboard
│
├── roosters.php                   # Jobs list/table
├── rooster_detail.php             # Job detail view
├── rooster_status.php             # Update job status
├── klus_toevoegen.php             # Create/edit job
├── klus_verwijderen.php           # Delete job
│
├── planner_weekly.php             # Weekly bus-based planner
│
├── buses_management.php           # Manage buses/teams
├── werknemers_management.php      # Manage employees
├── klanten_management.php         # Manage clients
│
├── afwezigheid_melden.php         # Report absence
├── afwezigheid_overzicht.php      # View absences
│
├── upload.php                     # File upload handler
├── export_excel.php               # Export to Excel
├── export_pdf.php                 # Export to PDF
│
├── profile.php                    # User profile management
├── settings.php                   # System settings
│
├── db_update.sql                  # Legacy schema updates
├── db_buses_migration.sql         # Buses table migration
│
├── reset_demo.php                 # Demo reset (caution!)
│
├── assets/
│   └── style.css                  # Company branding styles
│
├── templates/
│   ├── header.php                 # Page header (nav, styling)
│   └── footer.php                 # Page footer
│
├── uploads/                       # User-uploaded files
│   ├── [job_photos]/
│   └── [reference_images]/
│
├── README.md                      # This file
├── TESTING_GUIDE.md              # Complete testing procedures
└── DEPLOYMENT_GUIDE.md           # Hostinger deployment guide
```

---

## Database Schema

### Main Tables

**users**
- id, naam, email, wachtwoord, rol_id, telefoonnummer, timestamps

**rollen**
- id, naam (werkgever, werknemer)

**roosters** (jobs)
- id, datum, tijd, starttijd, eindtijd, titel, locatie
- omschrijving, toelichting, extra_werkzaamheden
- werknemer_id, opdrachtgever_id, werkgever_id, bus_id
- status, timestamps

**buses** (teams)
- id, naam, omschrijving, kleur, actief, timestamps

**werknemers_buses** (many-to-many)
- id, user_id, bus_id, toegewezen_op

**opdrachtgevers** (clients)
- id, naam, email, telefoonnummer, adres, timestamps

**afwezigheden** (absences)
- id, user_id, datum, reden, timestamps

**uploads** (files)
- id, rooster_id, user_id, bestandsnaam, type, pad, timestamps

---

## Workflow

### Employer (Werkgever) Workflow
1. Log in with werkgever account
2. Create buses/teams in "Bus & Team Beheer"
3. Assign employees to buses
4. Create jobs (klussen) with:
   - Date, time, location
   - Job description and details
   - Assigned employee
   - Assigned bus/team
5. View jobs in "Roosters" or "Weekplanning"
6. Monitor employee status updates
7. Track absences
8. Export completed jobs

### Employee (Werknemer) Workflow
1. Log in with werknemer account
2. View assigned jobs in dashboard and roosters list
3. For each job:
   - Review details and location
   - Update status (in progress, completed, paused)
   - Upload photo evidence
4. Report absence if needed
5. View team schedule in weekplanning

### Bus/Team Setup
1. Werkgever creates buses (HV01, HV02, DVI)
2. Assign color and description
3. Add employees to bus
4. When creating jobs, assign to bus
5. Weekly planner shows jobs grouped by bus

---

## User Roles & Permissions

### Werkgever (Employer)
- ✅ Full CRUD on jobs (roosters)
- ✅ Create and manage buses/teams
- ✅ Manage employee assignments
- ✅ View all jobs and weekly schedule
- ✅ Track absence reports
- ✅ Export data (Excel, PDF)
- ✅ System settings access

### Werknemer (Employee)
- ✅ View own assigned jobs
- ✅ Update job status
- ✅ Upload photos
- ✅ Report absence
- ✅ View personal weekly schedule
- ✅ Update profile
- ❌ Cannot create/delete jobs
- ❌ Cannot manage other employees

---

## Configuration

### Database Connection (config.php)

**Local Development:**
```php
$db = new PDO(
  "mysql:host=127.0.0.1;dbname=profplanner;charset=utf8mb4",
  "root",
  ""
);
```

**Hostinger Production:**
```php
$db = new PDO(
  "mysql:host=mysql.hostinger.com;dbname=u123456_profplanner;charset=utf8mb4",
  "u123456_dbuser",
  "your_password"
);
```

### File Upload Settings

Edit `upload.php`:
```php
$uploadDir = __DIR__ . "/uploads";
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
```

### Email Notifications (Future)

Configuration for absence alerts and job notifications coming in v2.

---

## Styling & Branding

### Color Scheme
- **Primary Green:** #16a34a (buttons, active states)
- **Dark Grey:** #1f2937 (headers, backgrounds)
- **Light Grey:** #f3f4f6 (backgrounds)
- **Neutral Grey:** #9ca3af (text, borders)
- **White:** #ffffff (cards, primary background)

### Customization

Edit `assets/style.css`:
```css
:root {
  --primary-green: #16a34a;
  --primary-green-dark: #15803d;
  --grey-dark: #1f2937;
  --grey-light: #f3f4f6;
  /* ... */
}
```

### Responsive Breakpoints
- **Desktop:** 1200px+
- **Tablet:** 768px - 1199px
- **Mobile:** < 768px

---

## Testing

See **TESTING_GUIDE.md** for comprehensive testing procedures including:
- Database validation
- Authentication testing
- CRUD operations
- Weekly planner testing
- Bus management
- Upload testing
- Error handling
- Responsive design testing

Quick test checklist:
- [ ] Login as werkgever
- [ ] Create a job
- [ ] Login as werknemer
- [ ] View and update job status
- [ ] Upload a photo
- [ ] Check weekplanning
- [ ] Manage buses

---

## Deployment

See **DEPLOYMENT_GUIDE.md** for complete Hostinger deployment including:
- Database setup on shared hosting
- FTP upload process
- Configuration for production
- Security hardening
- Performance optimization
- Backup strategy

Quick deployment steps:
1. Create MySQL database on Hostinger
2. Run schema SQL in phpMyAdmin
3. Update config.php with credentials
4. Upload files via FTP to public_html
5. Create uploads folder
6. Test login and basic operations

---

## Common Tasks

### Create a New Job
1. Werkgever → Roosters & Klussen → + Klus aanmaken
2. Fill form with date, time, location, details
3. Assign employee and client
4. Select bus/team
5. Save

### Assign Employee to Bus
1. Werkgever → Bus & Team Beheer
2. Click "Werknemers" on a bus card
3. Check/uncheck employees
4. Save

### View Weekly Schedule
1. Anyone → Click "Weekplanning" in menu
2. See all jobs grouped by bus
3. Navigate weeks with buttons
4. Click job for details

### Upload Job Photo
1. Employee → Roosters → Open a job
2. Click "Foto uploaden"
3. Select image file
4. Submit
5. Photo appears in job details

### Report Absence
1. Employee → Afwezigheid melden
2. Select date and reason
3. Submit
4. Employer sees in Afwezigheden overzicht

---

## Troubleshooting

### Can't connect to database
- Check config.php credentials
- Verify MySQL running
- Check database exists: `SHOW DATABASES;`

### Blank page after login
- Check error_log file
- Verify tables created
- Check PHP errors: `error_reporting(E_ALL);`

### Uploads not working
- Create uploads folder: `mkdir uploads`
- Set permissions: `chmod 755 uploads`
- Check file size limits

### Session lost
- Verify `session_start()` at top of config.php
- Check session folder writable
- Clear browser cookies

### Slow performance
- Check database indexes exist
- Monitor query count
- Enable query caching

---

## Security Notes

⚠️ **Important:**
- Always use HTTPS in production
- Change default passwords
- Keep database credentials in config.php (not in version control)
- Regularly backup database and uploads
- Monitor error logs for suspicious activity
- Update PHP version regularly
- Use strong passwords (12+ characters, mixed case)
- Enable prepared statements (already done)

---

## Future Enhancements

### v2.0 Planned Features
- [ ] Email notifications for job assignments
- [ ] SMS alerts for urgent jobs
- [ ] Mobile app (React Native)
- [ ] Real-time GPS tracking
- [ ] Advanced reporting and analytics
- [ ] Invoice generation
- [ ] Timesheet tracking
- [ ] API for integrations

### Performance Optimizations
- [ ] Add query caching
- [ ] Implement Redis sessions
- [ ] Optimize image compression
- [ ] Lazy loading for large lists
- [ ] Database read replicas

---

## Support & Maintenance

### Regular Maintenance Tasks
- **Weekly:** Check error logs
- **Monthly:** Backup database
- **Quarterly:** Update PHP/MySQL
- **Annually:** Security audit

### Getting Help

**For bugs or issues:**
1. Check TESTING_GUIDE.md for known issues
2. Review error logs
3. Check database integrity
4. Test with sample data
5. Contact hosting provider

**For deployment:**
1. See DEPLOYMENT_GUIDE.md
2. Contact Hostinger support
3. Check Hostinger documentation

---

## License & Credits

ProfPlanner is built for professional planning and sales operations.

- **Built with:** PHP 7.4+, MySQL, HTML5, CSS3
- **Deployment:** Hostinger compatible
- **Responsive:** Mobile-first design
- **Accessible:** WCAG guidelines

---

## Quick Links

- **Local Testing:** TESTING_GUIDE.md
- **Deployment:** DEPLOYMENT_GUIDE.md
- **Database Schema:** db_buses_migration.sql
- **Styling:** assets/style.css
- **Config:** config.php

---

## Version History

- **v1.0.0** (Initial Release)
  - Core CRUD operations
  - Weekly bus-based planner
  - Bus/team management
  - File uploads
  - Excel/PDF exports
  - Employee management
  - Company branding

---

**Last Updated:** 2024
**Status:** Production Ready ✅

Enjoy planning with ProfPlanner! 🚌
