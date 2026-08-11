# MCC Scheduler

MCC Scheduler is a Laravel 13 academic scheduling system with dedicated administrator, dean, instructor, and student portals. It manages accounts, Microsoft 365 student validation and OTP registration, departments, sections, subjects, instructor workloads, rooms, conflict-safe class generation, QR room tools, analytics, archives, notifications, and printable reports.

## Requirements

- PHP 8.3+
- Composer 2
- MySQL 8 or compatible MariaDB
- Node.js and npm for local frontend builds
- PHP extensions required by Laravel, plus cURL and PDO MySQL

## Local setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Run the automated checks with:

```bash
composer test
```

## Production

See [`deploy/HOSTINGER.md`](deploy/HOSTINGER.md) for the complete Hostinger deployment procedure and [`deploy/hostinger.env.example`](deploy/hostinger.env.example) for safe production environment defaults.

Never commit `.env`, database exports, Microsoft Graph secrets, SMTP passwords, or user-uploaded files.
