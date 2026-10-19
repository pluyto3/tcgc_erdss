# Test the downloadable project locally

This package includes the refactored Laravel application and its Composer dependencies. PHP itself is not included. Use PHP 8.4.1 or newer with PDO SQLite, mbstring, OpenSSL, and fileinfo enabled. The bundled dependency versions require PHP 8.4.1 even though Laravel 13 itself supports PHP 8.3. XAMPP installations with older PHP versions need an upgrade before using this package.

1. Extract the ZIP to a new folder. Keep it separate from any existing project you want to retain.
2. Open a terminal inside the extracted `tcgc_erdss` folder, where `artisan` is located.
3. Run:

```powershell
php scripts/setup-local.php
php artisan serve
```

4. Open http://127.0.0.1:8000 in your browser.

If port 8000 is already in use, run `php artisan serve --port=8001` and open http://127.0.0.1:8001 instead.

The setup helper creates a new local `.env`, generates a unique application key, prepares SQLite, and loads synthetic demonstration records. No existing database, credentials, session files, logs, or compiled caches from the development workspace are included. A previously configured local environment and existing database records are preserved if the helper is run again.

## Demo sign-in

Password for all four demo accounts: `DemoPass123!`

| Role | Email |
| --- | --- |
| HR Administrator | hr.admin@tcgc-demo.local |
| Executive | executive@tcgc-demo.local |
| Department Head | dept.head@tcgc-demo.local |
| Employee | employee@tcgc-demo.local |

The research baseline and current demo records are separate data sources in the dashboard. This package is for local evaluation. See `README.md` for the project structure, MySQL configuration, and integration limits, and `docs/REVIEW.md` for the refactor review. The original procedural project is retained in `storage/backups/original-project.zip`.
