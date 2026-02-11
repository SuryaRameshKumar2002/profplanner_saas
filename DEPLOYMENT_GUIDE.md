# ProfPlanner - Hostinger Deployment Guide

## Overview
This guide walks you through deploying ProfPlanner from local development to Hostinger shared hosting with live database.

---

## Part 1: Pre-Deployment Checklist

### Local Testing Complete
- [ ] All TESTING_GUIDE.md phases passed
- [ ] Database migration executed locally
- [ ] All CRUD operations working
- [ ] Authentication tested
- [ ] Weekly planner tested
- [ ] Bus management tested
- [ ] File uploads working

### Code Review
- [ ] No debug console.log statements
- [ ] No hardcoded credentials
- [ ] All paths use relative URLs
- [ ] config.php ready for production DB credentials

---

## Part 2: Hostinger Setup

### Step 1: Create Hostinger Account & Get Credentials

Contact or log in to your Hostinger account. You'll need:
- **Host/Server**: Usually `localhost` or `mysql.hostinger.com`
- **Database Name**: From Hostinger panel (e.g., `u123456_profplanner`)
- **Database User**: From Hostinger panel (e.g., `u123456_user`)
- **Database Password**: From Hostinger panel
- **FTP Credentials**: 
  - Server: ftp.yoursite.com (from panel)
  - Username: FTP username
  - Password: FTP password

### Step 2: Create MySQL Database on Hostinger

1. Log in to Hostinger Control Panel
2. Go to **Databases** or **MySQL Databases**
3. Click **Create New Database**
4. Enter:
   - Database Name: `u123456_profplanner` (use your prefix)
   - Username: `u123456_dbuser` (use your prefix)
   - Password: Generate strong password
5. Click **Create**
6. Note the credentials

### Step 3: Create Tables on Hostinger Database

1. In Hostinger panel, click **phpMyAdmin**
2. Select your new database
3. Go to **SQL** tab
4. Paste this complete schema:

```sql
-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  wachtwoord VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  telefoonnummer VARCHAR(20),
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES rollen(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollen table
CREATE TABLE IF NOT EXISTS rollen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roosters table
CREATE TABLE IF NOT EXISTS roosters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  datum DATE,
  tijd TIME,
  starttijd DATETIME,
  eindtijd DATETIME,
  titel VARCHAR(255),
  locatie VARCHAR(255),
  omschrijving TEXT,
  toelichting TEXT,
  extra_werkzaamheden TEXT,
  werknemer_id INT,
  opdrachtgever_id INT,
  werkgever_id INT,
  bus_id INT,
  status VARCHAR(50) DEFAULT 'gepland',
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (werknemer_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (opdrachtgever_id) REFERENCES opdrachtgevers(id) ON DELETE SET NULL,
  FOREIGN KEY (werkgever_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE SET NULL,
  KEY idx_datum (datum),
  KEY idx_werknemer (werknemer_id),
  KEY idx_bus (bus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opdrachtgevers table
CREATE TABLE IF NOT EXISTS opdrachtgevers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  telefoonnummer VARCHAR(20),
  adres TEXT,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Afwezigheden table
CREATE TABLE IF NOT EXISTS afwezigheden (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  datum DATE,
  reden VARCHAR(255),
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uploads table
CREATE TABLE IF NOT EXISTS uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rooster_id INT,
  user_id INT,
  bestandsnaam VARCHAR(255),
  type VARCHAR(50) DEFAULT 'foto',
  pad VARCHAR(500),
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rooster_id) REFERENCES roosters(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Buses table
CREATE TABLE IF NOT EXISTS buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(50) UNIQUE NOT NULL,
  omschrijving TEXT,
  kleur VARCHAR(10) DEFAULT '#16a34a',
  actief BOOLEAN DEFAULT TRUE,
  gemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Werknemers_buses table
CREATE TABLE IF NOT EXISTS werknemers_buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bus_id INT NOT NULL,
  toegewezen_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_bus (user_id, bus_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT IGNORE INTO rollen (naam) VALUES ('werkgever'), ('werknemer');

-- Insert default buses
INSERT IGNORE INTO buses (naam, omschrijving, kleur) VALUES
('HV01', 'Hoog Voltage Team 1', '#16a34a'),
('HV02', 'Hoog Voltage Team 2', '#059669'),
('DVI', 'DVI Specialist Team', '#047857');

-- Insert test users (use proper password hashes in production)
INSERT IGNORE INTO users (naam, email, wachtwoord, rol_id, telefoonnummer) VALUES
('Test Werkgever', 'werkgever@test.nl', '$2y$10$...', 1, '+31612345678'),
('Test Werknemer', 'werknemer@test.nl', '$2y$10$...', 2, '+31687654321');
```

**Important:** Replace password hashes with proper bcrypt hashes. Use this PHP to generate:
```php
<?php
$password = 'your_password';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
```

5. Execute the SQL

---

## Part 3: Update Configuration

### Step 1: Modify config.php for Hostinger

Edit `/config.php`:

```php
<?php
session_start();

/**
 * Hostinger Database Configuration
 */
$db = new PDO(
  "mysql:host=mysql.hostinger.com;dbname=u123456_profplanner;charset=utf8mb4",
  "u123456_dbuser",
  "your_strong_password_here"
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ... rest of config.php remains the same
?>
```

**Credentials to update:**
- `mysql.hostinger.com` → Your Hostinger MySQL server (check panel)
- `u123456_profplanner` → Your database name
- `u123456_dbuser` → Your database user
- `your_strong_password_here` → Your database password

### Step 2: Ensure File Permissions

Files needed with write access:
- `/uploads/` → chmod 755 (or 777 if 755 fails)
- `/logs/` → chmod 755 (if you add logging later)

---

## Part 4: Upload to Hostinger

### Option A: FTP Upload (Recommended for beginners)

1. **Download FTP Client:**
   - FileZilla (free)
   - WinSCP
   - Or use Hostinger's File Manager in panel

2. **Connect with FTP Credentials:**
   - Host: `ftp.yourdomain.com`
   - Username: `ftp_user`
   - Password: `ftp_password`
   - Port: 21 (standard)

3. **Navigate to public_html:**
   - Most hosting requires files in `/public_html/`
   - Create folder: `public_html/profplanner/`

4. **Upload All Files:**
   - Upload entire ProfPlanner folder to `public_html/profplanner/`
   - Ensure directory structure:
     ```
     public_html/
     └── profplanner/
         ├── index.php
         ├── config.php
         ├── login.php
         ├── werkgever.php
         ├── werknemer.php
         ├── assets/
         │   └── style.css
         ├── templates/
         │   ├── header.php
         │   └── footer.php
         ├── uploads/ (create empty folder)
         └── ... (all other files)
     ```

### Option B: SSH/Terminal Upload

```bash
# From your local machine:
sftp user@hostinger.server
cd public_html
mkdir profplanner
put -r /path/to/profplanner/* profplanner/
chmod 755 profplanner
chmod 755 profplanner/uploads
exit
```

---

## Part 5: Create .htaccess (if needed)

If you want: `yourdomain.com/profplanner` instead of `yourdomain.com/profplanner/index.php`

Create `/public_html/profplanner/.htaccess`:

```apache
RewriteEngine On
RewriteBase /profplanner/

# Redirect to index.php for all requests
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Handle PHP files
AddType application/x-httpd-php .php
```

---

## Part 6: Test Production Deployment

### Step 1: Access the Site

Open browser: `https://yourdomain.com/profplanner/`

You should see:
- ProfPlanner home page
- Logo and styling applied
- "Inloggen als Werkgever" / "Inloggen als Werknemer" buttons

### Step 2: Test Login

1. Click "Inloggen als Werkgever"
2. Enter credentials from your database
3. Should redirect to werkgever.php dashboard
4. Verify header, sidebar, and dashboard content load

**If blank page or error:**
- Check Hostinger error logs: `public_html/error_log`
- Verify config.php credentials are correct
- Ensure database tables exist (check in phpMyAdmin)

### Step 3: Test Database Operations

1. **Create a Klus:**
   - Click "Roosters & Klussen" > "+ Klus aanmaken"
   - Fill form and submit
   - Should redirect to rooster_detail.php
   - Data should appear in database

2. **Verify in Database:**
   - Go to Hostinger phpMyAdmin
   - Select database > roosters table
   - New row should exist

### Step 4: Test File Uploads

1. Go to Werknemer dashboard (login as werknemer)
2. Open a klus
3. Click "Foto uploaden"
4. Select an image and upload
5. Should appear in klus details
6. File should exist in `/public_html/profplanner/uploads/`

**If upload fails:**
- Check `/uploads/` folder exists
- Run: `chmod 755 uploads/` via terminal or FTP
- Check Hostinger file size limits

### Step 5: Test Session Persistence

1. Login and navigate to different pages
2. Refresh page (Ctrl+R)
3. Should remain logged in
4. Click "Logout"
5. Should redirect to home page
6. Try accessing `/werkgever.php` directly
7. Should redirect to login

### Step 6: Test Weekly Planner

1. Create multiple jobs assigned to HV01 bus
2. Go to "Weekplanning"
3. Should display week navigation and jobs grouped by bus
4. Click "Volgende week" and "← Vorige week"
5. Should change dates correctly

### Step 7: Test Bus Management

1. Go to "Bus & Team Beheer"
2. Create new bus
3. Assign workers
4. Verify data saves
5. Delete test bus

### Step 8: Test Exports

1. Go to "Export" section
2. Click "Excel" → should download .xlsx
3. Click "PDF" → should download .pdf
4. Open files and verify content

---

## Part 7: Performance Optimization

### Database Optimization

Add indexes for frequently queried columns:

```sql
-- Already included in the schema above, but verify:
CREATE INDEX idx_datum ON roosters(datum);
CREATE INDEX idx_werknemer ON roosters(werknemer_id);
CREATE INDEX idx_bus ON roosters(bus_id);
```

### Image Optimization

For photo uploads, compress before storing:

Edit `upload.php` to add compression (optional for production):

```php
// After file upload, compress:
if (extension_loaded('gd')) {
  $img = imagecreatefromjpeg($file_path);
  imagejpeg($img, $file_path, 80); // 80% quality
  imagedestroy($img);
}
```

### Caching Headers

Add to `.htaccess`:

```apache
# Cache static assets
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpeg "access plus 1 month"
  ExpiresByType image/png "access plus 1 month"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## Part 8: Security Hardening

### 1. Protect config.php

Add to `.htaccess`:

```apache
<Files "config.php">
  Order Allow,Deny
  Deny from all
</Files>
```

Or rename to `.env` (if you add .env handling):

### 2. Update Password Hashes

Generate strong bcrypt hashes for test users:

```php
<?php
// Run this script once, then delete
$password = 'StrongPassword123!';
echo password_hash($password, PASSWORD_DEFAULT);
?>
```

Update in database:

```sql
UPDATE users 
SET wachtwoord = '$2y$10$...' 
WHERE email = 'werkgever@test.nl';
```

### 3. Enable HTTPS

- Usually enabled automatically on Hostinger
- Force in `.htaccess`:

```apache
<IfModule mod_rewrite.c>
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

### 4. Add Security Headers

Edit `.htaccess`:

```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

### 5. Disable Directory Listing

Edit `.htaccess`:

```apache
<IfModule mod_autoindex.c>
  Options -Indexes
</IfModule>
```

---

## Part 9: Backup Strategy

### Automatic Backups

1. Hostinger usually provides automatic backups
2. Go to Hostinger panel > Backups
3. Schedule daily or weekly

### Manual Database Backup

```bash
# Via SSH/Terminal:
mysqldump -h mysql.hostinger.com -u u123456_dbuser -p u123456_profplanner > backup_$(date +%Y%m%d).sql
```

### Backup Files

Via FTP, download `public_html/profplanner/` regularly.

---

## Part 10: Monitoring & Maintenance

### Monitor Error Logs

Check daily for issues:

```bash
# Via FTP or panel file manager
/public_html/error_log
/public_html/php_error_log
```

### Monitor Disk Usage

Hostinger panel shows usage. Key areas:
- Database size
- File storage (especially `/uploads/`)

### Clean Old Uploads

Periodically delete old photos (older than 6 months):

```php
<?php
$uploadDir = '/path/to/uploads';
$sixMonthsAgo = time() - (6 * 30 * 24 * 60 * 60);

foreach (scandir($uploadDir) as $file) {
  if ($file !== '.' && $file !== '..') {
    $filepath = $uploadDir . '/' . $file;
    if (filemtime($filepath) < $sixMonthsAgo) {
      unlink($filepath);
    }
  }
}
?>
```

---

## Troubleshooting Deployment

### Blank Page / 500 Error

**Check:**
1. Error logs: `public_html/error_log`
2. config.php database credentials
3. Database exists and tables created
4. PHP version (7.4+ required for match expressions)

### "Table doesn't exist" Error

**Solution:**
1. Verify all SQL executed in Hostinger phpMyAdmin
2. Check table names match (case-sensitive on Linux)
3. Re-run full schema if needed

### Session Lost / Logout Issues

**Check:**
1. `session_start()` at top of config.php
2. Session path writable: `/tmp/` on Linux
3. Set explicit session path in config.php:

```php
session_save_path('/path/to/session');
```

### Upload Fails / 403 Error

**Solutions:**
1. Set chmod: `chmod 755 uploads/`
2. Check max upload size in Hostinger panel
3. Create empty test file to verify write permissions

### Slow Performance

**Optimize:**
1. Check database indexes exist
2. Enable gzip in `.htaccess`:
```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/plain
  AddOutputFilterByType DEFLATE text/html
  AddOutputFilterByType DEFLATE text/xml
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE application/xml
  AddOutputFilterByType DEFLATE application/xhtml+xml
  AddOutputFilterByType DEFLATE application/rss+xml
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

3. Minimize database queries (use JOIN instead of multiple queries)

---

## Production Checklist

```
Pre-Deployment
- [ ] All local testing complete
- [ ] No debug code in files
- [ ] config.php updated with Hostinger credentials

Hostinger Setup
- [ ] Database created
- [ ] All tables created with full schema
- [ ] Default roles and buses inserted
- [ ] Test users created with strong passwords

Upload
- [ ] Files uploaded to public_html/profplanner/
- [ ] Directory structure correct
- [ ] uploads/ folder created
- [ ] .htaccess configured (if needed)

Testing
- [ ] Site accessible at yourdomain.com/profplanner
- [ ] Login works (werkgever and werknemer)
- [ ] Create klus works
- [ ] Photo upload works
- [ ] Weekly planner displays correctly
- [ ] Bus management works
- [ ] Exports work (Excel/PDF)
- [ ] Session persists
- [ ] Logout works

Security
- [ ] config.php protected from direct access
- [ ] HTTPS enabled
- [ ] Security headers added
- [ ] Directory listing disabled
- [ ] Password hashes bcrypt

Optimization
- [ ] Database indexes created
- [ ] Gzip compression enabled
- [ ] Static file caching configured

Monitoring
- [ ] Error logs monitored
- [ ] Disk usage monitored
- [ ] Backup strategy in place
- [ ] Database backups automated
```

---

## Live Deployment Complete!

Your ProfPlanner system is now live on Hostinger. 

**Next steps:**
1. Invite users and create real accounts
2. Create actual buses/teams for your organization
3. Start assigning jobs and tracking work
4. Monitor performance and collect feedback
5. Plan regular backups

For support, check error logs or contact Hostinger support.

Happy planning! 🚌
