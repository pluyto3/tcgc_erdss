# TCGC Employee Retention Decision Support System

A PHP application built on Laravel 13, Blade HTML views, shared CSS, and vanilla JavaScript. The included locked dependencies require PHP 8.4.1 or newer. It preserves the original employee, assessment, feedback, intervention approval, monitoring, reporting, account, and administration workflows.

## Run locally

The current workspace already has dependencies, an application key, migrations, and synthetic demonstration records installed.

```powershell
php artisan serve
```

Open http://127.0.0.1:8000. If you use XAMPP Apache instead, open `http://localhost/tcgc_erdss/public/`. The repository-level `.htaccess` prevents access to private source files; the public directory contains the front controller and browser assets. A virtual host should set its DocumentRoot to this project's `public` directory with `AllowOverride All` and `mod_rewrite` enabled.

For a fresh local checkout:

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType Directory -Force storage/framework/cache/data,storage/framework/sessions,storage/framework/views,storage/logs,bootstrap/cache
New-Item -ItemType File database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Frontend files are served locally. No Node, npm, CDN, or JavaScript compilation is required.

## Demo access

For local development only. All operational records created by the seeder are synthetic. The default password is `DemoPass123!`; it can be changed before seeding with `DEMO_PASSWORD` in `.env`.

| Role | Email |
| --- | --- |
| HR Administrator | hr.admin@tcgc-demo.local |
| Executive | executive@tcgc-demo.local |
| Department Head | dept.head@tcgc-demo.local |
| Employee | employee@tcgc-demo.local |

The demo seeder refuses to run in production and does not reseed a database with existing roles. Production needs an authorized administrator and institution-owned records, rather than shared demo credentials.

## Data sources and dashboard features

The research source is the owner's **FEATURES IN DASHBOARD.docx**. Reference values are maintained in `config/research.php`: retention 2.82/4, workforce 285 across five units, eight HRM means, all eight beta coefficients and p-values, R² .399, seven qualitative themes, and three action tiers. These values are explicitly labeled research baselines. They are never used to fabricate current employees, response sets, or trend history.

The dashboard's **Data source** filter switches between the research baseline and current assessment records. The local seeded dataset has 30 sample employees and four sample departments; its figures intentionally differ from the institutional baseline. Live mode provides period and unit filters, participation, workforce status rates, assessment history, and comparisons across units and employee categories. Unit heads always receive only their assigned scope, including in exports and policy reports.

The baseline has no dated turnover series. Trend panels are populated from recorded assessments. Regression coefficients are contextual research associations and are not recalculated or used for individual resignation scores. Theme counts represent manually tagged feedback; no automated qualitative classification is implied.

## Structure

```text
app/Http/Controllers/    Authentication and module request handling
app/Http/Middleware/     Role checks, active sessions, security headers
app/Http/Requests/       Validated login input
app/Models/              Eloquent authentication and employee relationships
app/Services/            Assessments, imports, dashboard data, priorities, reports, settings
app/Support/             Shared formatting and aggregate analytics helpers
bootstrap/               Laravel initialization and provider registration
config/                  Environment configuration, module permissions, research references
database/migrations/     One-time schema adoption and reporting indexes
database/schema/         SQLite and MySQL domain schema definitions
database/seeders/        Explicit, local-only synthetic demonstration seed
resources/views/         Shared Blade layout, components, dashboards and module views
public/assets/           Shared CSS and progressive JavaScript enhancements
routes/                  Named web routes and maintenance console command
tests/                   Workflow, privacy and permission regression tests
docs/                    Review findings and feature mapping
```

Existing operational views have been retained under `resources/views/modules`; their mutation handling now runs in Laravel controllers. The dashboard, insights, priorities and report views use reusable Blade components. Some administrative view queries remain in the retained views to preserve workflow behavior; they are a clear next boundary for further incremental service extraction.

## Existing databases and MySQL

Back up the database before upgrading an installation. For an original SQLite database, set `DB_DATABASE` to its **absolute** existing path, for example `C:/xampp/htdocs/tcgc_erdss/storage/tcgc_erdss.sqlite`. This workspace had no existing database when reviewed.

For MySQL, create an empty database or select the existing original-schema database, then configure:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tcgc_erdss
DB_USERNAME=root
DB_PASSWORD=
```

Run `php artisan config:clear` and `php artisan migrate`. Do **not** use `migrate:fresh` on institutional records. The adoption migration uses `CREATE TABLE IF NOT EXISTS`, applies legacy compatibility upgrades once, and does not automatically seed. Rolling back the initial adoption is deliberately disabled because it could destroy pre-existing domain data. SQLite is the verified runtime for this refactor; MySQL configuration and schema are provided but have not been exercised against a running MySQL server.

## Maintenance and checks

```powershell
composer test
composer format
php artisan view:cache
php artisan retention:refresh-priorities
```

Priority views and reports compute suggestions without writing on GET requests. HR confirmation saves the selected review status. The explicit maintenance command refreshes stored suggestions when needed; it does not override review statuses.

Quarterly updates are managed by creating dated assessment periods, importing workforce records, collecting responses, updating interventions, and reviewing the monitoring page. The application does not schedule or email reports automatically. Reports provides six CSV/print report types and a formatted policy summary that supports browser print/save as PDF.

Security includes Laravel sessions and CSRF checks, shared login throttling, POST-only sign-out, refreshed active-account checks, server validation, atomic anonymous assessment submission, per-factor privacy thresholds, role-scoped report data, CSV formula escaping, validated logo uploads, and request-local settings reuse. Uploaded logos and response records remain local.

## Original source backup

The original project is recoverable from `storage/backups/original-project.zip`. Its procedural sources are also retained in `storage/backups/original-source/`, outside the public directory. No original source files were discarded.
