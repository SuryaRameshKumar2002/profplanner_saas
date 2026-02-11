# ProfPlanner Quick Start

## 1. Local setup (XAMPP, Windows)

1. Copy project to `C:\xampp\htdocs\profplanner_CLIENT_JOBS`.
2. Start `Apache` and `MySQL` in XAMPP Control Panel.
3. Run setup script in PowerShell (inside project root):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\setup-local.ps1
```

If you already had an older database, run:

```powershell
php .\scripts\migrate-super-admin.php
```

4. Open app:

`http://localhost/profplanner_CLIENT_JOBS/`

Important: do not use `http://localhost/htdocs/...`.
`htdocs` is the Apache document root, not part of the URL path.
If your XAMPP Apache uses port 8080, use:
`http://localhost:8080/profplanner_CLIENT_JOBS/`

## 2. Test login

- Super Admin: `admin@profplanner.local` / `password123`
- Werkgever: `werkgever@test.nl` / `password123`
- Werknemer: `werknemer@test.nl` / `password123`

## 3. Smoke test (optional)

```powershell
php .\scripts\smoke-test.php
```

Expected: `Schema OK` and table counts.

## 3b. If Apache URL still fails (fallback run)

Run built-in PHP server:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run-local.ps1
```

Then open:
`http://127.0.0.1:8090/`

## 4. Manual DB import (fallback)

If script is blocked, import in phpMyAdmin:

1. Create DB `profplanner`.
2. Import `database/schema.sql`.
3. Import `database/seed_demo.sql`.

## 5. Full flow test (5 minutes)

1. Login as werkgever.
2. Open `Werknemers` and verify add/edit works.
3. Open `Bus & Team Beheer` and assign werknemer to `HV01`.
4. Create a job via `+ Klus aanmaken`.
5. Open `CRM/Sales`, add a lead, and confirm lead to job.
6. Login as werknemer and open `Mijn Roosters`.
7. Open job, update status, upload photo.
8. Login as werkgever and verify status/upload in detail page.

## 6. Hostinger single-domain deploy

1. Create one domain/subdomain and point it to the app folder.
2. In Hostinger hPanel, create MySQL DB + user.
3. Upload files via File Manager/FTP.
4. In phpMyAdmin (Hostinger), import:
   - `database/schema.sql`
   - `database/seed_demo.sql` (optional)
5. Set DB env vars in Hostinger PHP settings or `.htaccess`:
   - `PP_DB_HOST`
   - `PP_DB_NAME`
   - `PP_DB_USER`
   - `PP_DB_PASS`
6. Ensure `uploads/` exists and is writable.
7. Open your domain and test both logins.

## 7. Production checklist

- Keep `PP_ALLOW_DIAGNOSTICS` unset in production (blocks diagnostic/reset endpoints).
- Replace demo users with real users.
- Enforce HTTPS on domain.
- Keep database backups enabled in Hostinger.
