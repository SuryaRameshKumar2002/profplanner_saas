# ProfPlanner - Quick Start (5 Minutes)

Get ProfPlanner running locally in under 5 minutes!

---

## Step 1: Database Setup (1 minute)

### Open phpMyAdmin
- **XAMPP:** http://localhost/phpmyadmin
- **WAMP:** http://localhost/phpmyadmin  
- **Built-in PHP:** http://localhost/phpmyadmin

### Create Database
1. Click **Databases** tab
2. Create name: `profplanner`
3. Collation: `utf8mb4_unicode_ci`
4. Click **Create**

---

## Step 2: Run Migration (1 minute)

1. Select **profplanner** database
2. Click **SQL** tab
3. **Copy & Paste** entire content of `db_buses_migration.sql`
4. Click **Go**

Done! All tables + default data created automatically.

---

## Step 3: Update config.php (1 minute)

Edit `config.php`:

**Local (XAMPP/WAMP):**
```php
$db = new PDO(
  "mysql:host=127.0.0.1;dbname=profplanner;charset=utf8mb4",
  "root",
  ""
);
```

**Mac (uses 127.0.0.1):**
```php
$db = new PDO(
  "mysql:host=127.0.0.1;dbname=profplanner;charset=utf8mb4",
  "root",
  ""
);
```

Save file.

---

## Step 4: Create Test Users (1 minute)

In phpMyAdmin **SQL** tab, paste & execute:

```sql
-- Get role IDs
SELECT * FROM rollen;

-- Create Werkgever (use role_id 1)
INSERT INTO users (naam, email, wachtwoord, rol_id) VALUES 
('Werkgever Test', 'werkgever@test.nl', 
'$2y$10$DH0D6GIGlLeWO6d9TjPqRehPEYfq3PypJzcRRvPvBdvjKWLEDAFva', 1);

-- Create Werknemer (use role_id 2)
INSERT INTO users (naam, email, wachtwoord, rol_id) VALUES 
('Werknemer Test', 'werknemer@test.nl', 
'$2y$10$DH0D6GIGlLeWO6d9TjPqRehPEYfq3PypJzcRRvPvBdvjKWLEDAFva', 2);
```

**Password hash above = "password123"**

---

## Step 5: Create Uploads Folder (< 1 minute)

In project root, create folder:
```
/uploads/
```

Set permissions (Windows: ignore, Linux/Mac):
```bash
chmod 755 uploads
```

---

## Step 6: Access Application (1 minute)

### Open Browser
- **XAMPP:** http://localhost/profplanner/
- **WAMP:** http://localhost/profplanner/
- **Built-in:** http://localhost:8000/profplanner/

You should see: **ProfPlanner - Planning & klusbeheer** with two login buttons.

---

## Step 7: Login Test (1 minute)

### Login as Werkgever
1. Click "Inloggen als Werkgever"
2. Email: `werkgever@test.nl`
3. Password: `password123`
4. Click "Inloggen"

**Expected:** Redirects to Werkgever Dashboard with 8 cards

### Login as Werknemer
1. Go home (click ProfPlanner logo)
2. Click "Inloggen als Werknemer"
3. Email: `werknemer@test.nl`
4. Password: `password123`
5. Click "Inloggen"

**Expected:** Redirects to Werknemer Dashboard with stats and team info

---

## Done! You're Ready to Test

### Quick Actions to Try

1. **Create a Job:**
   - Werkgever Dashboard > Roosters & Klussen > + Klus aanmaken
   - Fill form, select HV01 bus
   - Click "Klus aanmaken"

2. **View Weekly Plan:**
   - Click "Weekplanning" in header
   - See job grouped by bus
   - Click "Volgende week" to navigate

3. **Upload Photo:**
   - Werknemer Dashboard > Open a job
   - Click "Foto uploaden"
   - Select image
   - Verify it appears

4. **Manage Buses:**
   - Werkgever > Bus & Team Beheer
   - Create new bus or assign workers
   - See changes reflected

---

## Troubleshooting

### Blank page / Error
1. Check error log: Look for errors in browser console (F12)
2. Verify database: Open phpMyAdmin > select profplanner > verify tables exist
3. Check config.php: Ensure database name is "profplanner"

### "Table doesn't exist"
1. Go to phpMyAdmin
2. Select profplanner database
3. Run `db_buses_migration.sql` again

### Login fails
1. Verify users inserted: `SELECT * FROM users;` in phpMyAdmin
2. Check password hash correct (should start with $2y$10$)
3. Try credentials: werkgever@test.nl / password123

### Upload folder error
1. Create `/uploads/` folder in project root
2. On Linux/Mac: `chmod 755 uploads`
3. Try uploading again

### Slow to load
1. First load is normal (slower)
2. Refresh page (Ctrl+R)
3. Should be faster

---

## Next Steps

✅ **Local testing working?** Proceed to:
1. **Full testing:** Read `TESTING_GUIDE.md` (12 test phases)
2. **Deployment:** Read `DEPLOYMENT_GUIDE.md` (Hostinger setup)
3. **Features:** Explore all pages and functionality

---

## File Locations (if needed)

```
profplanner/
├── config.php          ← Update database credentials here
├── assets/style.css    ← Styling (green + grey theme)
├── templates/          ← Header/footer templates
├── uploads/            ← Created folder for photos
├── db_buses_migration.sql  ← Run this in phpMyAdmin
├── README.md           ← Full documentation
├── TESTING_GUIDE.md    ← 12 test phases
└── DEPLOYMENT_GUIDE.md ← Hostinger deployment
```

---

## Test Credentials

```
Werkgever (Employer):
  Email: werkgever@test.nl
  Password: password123

Werknemer (Employee):
  Email: werknemer@test.nl
  Password: password123
```

---

## 5-Minute Checklist

- [ ] Database "profplanner" created
- [ ] Migration SQL executed
- [ ] config.php updated
- [ ] Test users created in phpMyAdmin
- [ ] /uploads/ folder created
- [ ] Site accessible in browser
- [ ] Werkgever login works
- [ ] Werknemer login works
- [ ] Created sample job
- [ ] Viewed weekly planner

**All ✓? Congrats! ProfPlanner is ready for testing.**

---

## What You Just Built

A production-ready planning system with:
- **Authentication:** Werkgever & Werknemer roles
- **Job Management:** Full CRUD operations
- **Team Planning:** Weekly planner grouped by bus
- **File Uploads:** Store job photos
- **Responsive Design:** Works on mobile, tablet, desktop
- **Company Branding:** Green + grey professional theme

---

**Ready to dive deeper? Start with TESTING_GUIDE.md for comprehensive validation.**
