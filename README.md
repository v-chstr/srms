# SRMS - Student Research Management System

A web-based research management system for the SPUP School of Information Technology and Engineering (SITE). Built with Laravel, Blade, and TailwindCSS.

## Features

- Student research paper submission and tracking
- Adviser review and feedback workflow
- Admin panel for user, course, and paper management
- Defense scheduling with calendar view
- Announcements system
- PDF report export
- Research archive with search and filtering

## Requirements

- PHP 8.3+
- MySQL
- Node.js 18+
- Composer

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

## Accounts

After seeding, log in with the credentials created by the seeders. Check the seeder files in `database/seeders/` for the default accounts.
