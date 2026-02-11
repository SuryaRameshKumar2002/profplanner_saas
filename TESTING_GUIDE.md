# ProfPlanner - Testing & Validation Guide

## Phase 1: Local Database Setup & Testing

### Step 1: Apply Database Migration

1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Select your `profplanner` database
3. Click the **SQL** tab
4. Copy the entire contents of `db_buses_migration.sql`
5. Paste into the SQL editor
6. Click **Go** to execute

**Expected Results:**
- New `buses` table created
- New `werknemers_buses` junction table created
- Foreign key relationships established
- 3 default buses (HV01, HV02, DVI) inserted

### Step 2: Verify Tables Exist

```sql
-- Run in phpMyAdmin SQL tab to verify:
SHOW TABLES;
DESCRIBE buses;
DESCRIBE werknemers_buses;
```

Expected columns in `buses`:
- id (INT, AUTO_INCREMENT)
- naam (VARCHAR 50)
- omschrijving (TEXT)
- kleur (VARCHAR 10)
- actief (BOOLEAN)
- gemaakt_op, gewijzigd_op (TIMESTAMPS)

### Step 3: Test CRUD Operations

#### Create (INSERT) Test
```php
// Test in a temporary test.php file:
<?php
require 'config.php';
try {
  $stmt = $db->prepare("INSERT INTO buses (naam, omschrijving, kleur) VALUES (?, ?, ?)");
  $stmt->execute(['TEST_BUS', 'Test bus for validation', '#FF0000']);
  echo "✓ Bus created successfully";
} catch (Exception $e) {
  echo "✗ Error: " . $e->getMessage();
}
?>
```

#### Read (SELECT) Test
```php
<?php
require 'config.php';
$stmt = $db->query("SELECT * FROM buses");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "✓ Found " . count($buses) . " buses";
?>
```

#### Update Test
```php
<?php
require 'config.php';
$stmt = $db->prepare("UPDATE buses SET omschrijving = ? WHERE naam = ?");
$stmt->execute(['Updated test', 'TEST_BUS']);
echo "✓ Bus updated";
?>
```

#### Delete Test
```php
<?php
require 'config.php';
$stmt = $db->prepare("DELETE FROM buses WHERE naam = ?");
$stmt->execute(['TEST_BUS']);
echo "✓ Bus deleted";
?>
```

---

## Phase 2: Authentication & Session Testing

### Test Case 1: Werkgever Login
1. Go to `http://localhost/profplanner/` (or your local URL)
2. Click "Inloggen als Werkgever"
3. Use credentials from your database (e.g., werkgever@test.nl / password)
4. Verify redirects to `werkgever.php`
5. Check session contains: `$_SESSION['user']['rol']` = 'werkgever'

### Test Case 2: Werknemer Login
1. From home, click "Inloggen als Werknemer"
2. Use werknemer credentials
3. Verify redirects to `werknemer.php`
4. Check session contains: `$_SESSION['user']['rol']` = 'werknemer'

### Test Case 3: Session Persistence
1. After login, refresh the page (Ctrl+R)
2. Verify user remains logged in
3. Navigate between pages (Roosters, Weekplanning, etc.)
4. Verify session maintains across pages

### Test Case 4: Logout
1. Click "Logout" in header
2. Verify redirects to login page
3. Try accessing dashboard directly - should redirect to login
4. Verify session is cleared

---

## Phase 3: Navigation Testing

### Werkgever Dashboard
- [ ] All menu items appear: Dashboard, Weekplanning, Roosters, Logout
- [ ] All cards render: Roosters & Klussen, Weekplanning, Bus & Team Beheer, Afwezigheden, Werknemers, Opdrachtgevers, Export, Instellingen
- [ ] Each button links to correct page

### Werknemer Dashboard
- [ ] Statistics load correctly (jobs this week, completed jobs)
- [ ] Team/Bus badges display if assigned
- [ ] All action buttons present and clickable

---

## Phase 4: Roosters (Jobs) CRUD Testing

### Create New Klus (Job)
1. Werkgever: Go to Roosters & Klussen > + Klus aanmaken
2. Fill form:
   - Datum: Today or future date
   - Starttijd: 09:00
   - Eindtijd: 17:00
   - Klus titel: "Test Klus"
   - Locatie: "Amsterdam"
   - Project info: "Test isolatie project"
   - Werknemer: Select from list
   - Opdrachtgever: Select from list
   - Bus/Team: Select HV01
3. Click "Klus aanmaken"
4. Verify:
   - Redirects to rooster_detail.php
   - All entered data displays
   - Bus badge shows "HV01" with correct color

### View Roosters List
1. Click "Roosters" in menu
2. Verify table shows all jobs with columns: Datum, Tijd, Klus, Locatie, Bus/Team, Werknemer, Status
3. Filter by role (Werkgever sees all, Werknemer sees only own)
4. Click "Open" on a job → should go to rooster_detail.php

### Update Klus
1. In rooster_detail, click "Wijzigen"
2. Edit one field (e.g., change locatie)
3. Click "Opslaan"
4. Verify changes saved

### Werknemer Updates Status
1. Login as Werknemer
2. Go to Roosters > Open a klus
3. Under "Status bijwerken":
   - Select status: "Klus afgerond"
   - Add toelichting: "Completed successfully"
   - Click "Opslaan"
4. Verify:
   - Status badge changes to green
   - Data persists on refresh

---

## Phase 5: Bus Management Testing

### Access Bus Management
1. Werkgever Dashboard > Bus & Team Beheer
2. Should see list of buses (HV01, HV02, DVI)
3. Each bus shows count of assigned workers

### Add New Bus
1. Click "Toevoegen" section
2. Enter:
   - Naam: "TEST_BUS"
   - Omschrijving: "Test team"
   - Kleur: Pick a color
3. Click "Toevoegen"
4. Verify bus appears in list

### Assign Workers to Bus
1. Click "Werknemers" button on a bus card
2. Check/uncheck workers
3. Click "Opslaan"
4. Go back - verify "Werknemers" count updated
5. Query database: `SELECT * FROM werknemers_buses WHERE bus_id = X`
6. Verify relationships created

### Delete Bus
1. Click "Verwijderen" on a test bus
2. Confirm deletion
3. Verify bus removed from list
4. Database: foreign key should set roosters.bus_id to NULL

---

## Phase 6: Weekly Planner Testing

### Access Planner
1. Click "Weekplanning" in menu
2. Should show current week dates
3. Jobs grouped by bus/team sections

### Navigate Weeks
1. Click "Volgende week →"
2. Verify dates change
3. Click "← Vorige week"
4. Should go back
5. Click "Vandaag"
6. Should return to current week

### Verify Grouping
1. Create multiple jobs assigned to same bus
2. On planner, all jobs for that bus should appear in same section
3. Each section should show bus color on left border

### Filter by Role
- **Werkgever**: Sees all jobs, includes "Werknemer" column
- **Werknemer**: Sees only own jobs, no "Werknemer" column

---

## Phase 7: Uploads & Attachments

### Upload Photo
1. Werknemer: Go to Roosters > Open a klus > "Foto uploaden"
2. Select image file (JPG/PNG)
3. Verify upload folder created: `/uploads/`
4. Click upload button
5. Verify file appears in clus details

### Reference Photo (Werkgever)
1. Werkgever: Go to klus detail > "Referentiefoto toevoegen"
2. Upload reference image
3. Verify photo accessible by werknemer

---

## Phase 8: Employee Management & Profiles

### Werknemers Management Page
1. Go to Werkgever Dashboard > Werknemers > Beheren
2. List should show all employees with email
3. Click "Bewerk" on a worker
4. Verify edit page loads (stub for future development)

### User Profile
1. Any logged-in user: Click username in top-right
2. Go to "Mijn Profiel"
3. Update naam or email
4. Change password (provide old password, new, confirm)
5. Verify changes persist across sessions

---

## Phase 9: Settings & System Info

### Access Settings
1. Werkgever: Dashboard > Instellingen
2. Should display:
   - Database table count
   - Bus count
   - User count
   - Rooster count
3. Verify all numbers match database reality

### Database Status
- Run settings.php
- Compare displayed counts with actual:
  ```sql
  SELECT COUNT(*) FROM buses;
  SELECT COUNT(*) FROM users;
  SELECT COUNT(*) FROM roosters;
  ```

---

## Phase 10: Responsive Design Testing

### Desktop (1200px+)
- [ ] Grid layouts use multiple columns
- [ ] Tables display all columns
- [ ] Navigation sidebar visible

### Tablet (768px - 1199px)
- [ ] Grid collapses to 2 columns
- [ ] Tables remain readable
- [ ] Buttons stack nicely

### Mobile (< 768px)
- [ ] Grid single column
- [ ] Tables scroll horizontally
- [ ] Navigation hamburger (if implemented)
- [ ] All buttons full-width
- [ ] Forms stack vertically

**Test with:**
- Browser DevTools (Ctrl+Shift+I > Toggle device toolbar)
- Actual mobile device

---

## Phase 11: Error Handling

### Test Missing Data
1. Try accessing non-existent rooster: `rooster_detail.php?id=99999`
2. Verify error message displays
3. Try accessing other user's klus as werknemer
4. Should show "Geen toegang" error

### Test Validation
1. Try creating klus without required fields
2. Should show error message
3. Form data should persist
4. Try invalid date/time inputs
5. Should reject or correct

### Test Database Errors
1. Temporarily break database connection in config.php
2. Try accessing dashboard
3. Should show helpful error (not raw exception)

---

## Phase 12: Export Testing

### Excel Export
1. Werkgever: Dashboard > Export > Excel
2. Should generate downloadable .xlsx file
3. Verify data includes: roosters, dates, workers, status

### PDF Export
1. Werkgever: Dashboard > Export > PDF
2. Should generate downloadable .pdf file
3. Verify formatting readable
4. Verify all roosters included

---

## Testing Checklist Summary

```
Phase 1: Database Setup
- [ ] Migration executed
- [ ] Tables created
- [ ] Default buses inserted
- [ ] CRUD operations work

Phase 2: Authentication
- [ ] Werkgever login works
- [ ] Werknemer login works
- [ ] Sessions persist
- [ ] Logout clears session

Phase 3: Navigation
- [ ] All menu items present
- [ ] Links navigate correctly
- [ ] Role-based visibility works

Phase 4: Roosters
- [ ] Create job works
- [ ] View jobs works
- [ ] Update job works
- [ ] Status updates work

Phase 5: Bus Management
- [ ] View buses works
- [ ] Create bus works
- [ ] Assign workers works
- [ ] Delete bus works

Phase 6: Weekly Planner
- [ ] Week navigation works
- [ ] Jobs grouped by bus
- [ ] Role-based filtering works

Phase 7: Uploads
- [ ] Photo upload works
- [ ] Files accessible
- [ ] Folder permissions correct

Phase 8: Profiles
- [ ] User profile accessible
- [ ] Profile edit works
- [ ] Password change works

Phase 9: Settings
- [ ] Settings page loads
- [ ] Counts accurate
- [ ] Database info displays

Phase 10: Responsive
- [ ] Desktop layout works
- [ ] Tablet layout works
- [ ] Mobile layout works

Phase 11: Errors
- [ ] Missing data handled
- [ ] Validation works
- [ ] DB errors handled gracefully

Phase 12: Exports
- [ ] Excel export works
- [ ] PDF export works
- [ ] Data complete and formatted
```

---

## Common Issues & Solutions

### Issue: "Table 'buses' doesn't exist"
**Solution:** Run the migration SQL script in phpMyAdmin

### Issue: "CORS error on uploads"
**Solution:** Ensure `/uploads/` folder exists with write permissions (chmod 777)

### Issue: "Session data lost on page refresh"
**Solution:** Verify `session_start()` is at top of config.php before any output

### Issue: "Foreign key constraint fails"
**Solution:** When deleting bus, ensure no roosters reference it. Or manually run:
```sql
UPDATE roosters SET bus_id = NULL WHERE bus_id = X;
DELETE FROM buses WHERE id = X;
```

### Issue: "Werkgever sees wrong user count"
**Solution:** Run: `SELECT * FROM rollen;` - verify rolle.naam matches role check logic

---

## Performance Testing

### Database Query Performance
- Jobs table with 1000+ roosters should load in < 1 second
- Weekly planner with 100 jobs should render in < 2 seconds
- Bus management page with 50 workers should load in < 1 second

Monitor with: `EXPLAIN SELECT ...;` in phpMyAdmin

---

## Next: Deployment Validation

See DEPLOYMENT_GUIDE.md for Hostinger setup and production testing.
