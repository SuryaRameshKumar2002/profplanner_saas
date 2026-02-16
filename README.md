# ProfPlanner

ProfPlanner is a PHP + MySQL portal for sales-confirmed job planning and execution.

## Core modules

- Super Admin dashboard and werkgever management
- Employer dashboard (`werkgever`)
- Employee dashboard (`werknemer`)
- Sales dashboard (`sales_manager`, `sales_agent`)
- CRM/Sales leads + agenda + planning + confirm-to-job flow
- Jobs/roosters CRUD
- Weekly planning grouped by bus/team (`HV01`, `HV02`, ...)
- Client and employee management
- Photo uploads per job
- Absence reporting
- Export (Excel/PDF)

## Stack

- PHP (PDO)
- MySQL/MariaDB
- Apache (XAMPP local, Hostinger production)

## Project setup files

- Schema: `database/schema.sql`
- Demo data: `database/seed_demo.sql`
- Local setup script: `scripts/setup-local.ps1`
- Existing DB migration: `scripts/migrate-super-admin.php`
- Local smoke test: `scripts/smoke-test.php`

## Local run

Follow `QUICK_START.md`.

## Default seeded accounts

- `superadmin@profplanner.app` / `Pp!Sup3rAdm1n#2026`
- `werkgever@profplanner.app` / `Pp!Werkg3ver#2026`
- `werknemer@profplanner.app` / `Pp!Werkn3mer#2026`
- `salesmanager@profplanner.app` / `Pp!SalesMng#2026`
- `salesagent@profplanner.app` / `Pp!SalesAg3nt#2026`

Rotate seeded credentials after first deployment.

## Environment variables

`config.php` supports:

- `PP_DB_HOST`
- `PP_DB_NAME`
- `PP_DB_USER`
- `PP_DB_PASS`

If not set, local defaults are used (`127.0.0.1`, `profplanner`, `root`, empty password).
