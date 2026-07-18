# AetherLife: Personal Productivity & Life Planner Web Application

AetherLife is a production-ready, premium SaaS-like **Personal Productivity & Life Planner Web Application** built using PHP 8+ and MySQL. It features visual glassmorphism, responsive mobile layouts, touch-friendly checklists, and computed behavior analytics (such as active streaks and GitHub-style contribution heatmaps).

The application runs directly on standard shared hosting environments (like Hostinger) with no Node.js, composer, or Java requirements.

---

## 🚀 Key Features

*   **Premium Glassmorphism Dashboard**: Ticking header clock, current streaks, weather stubs, daily motivational quotes, active goal progress circles, and Pomodoro focus summaries.
*   **Modular Task Board**: Dynamic check-ins, nested checklist items, estimated/actual focus times, task difficulty categories, and attachment uploading.
*   **Streaks Habits Tracker**: Week-view checklists with dynamic current/longest streaks calculators and success rate percentages.
*   **Goals & Milestones Planner**: Category targets (short/yearly/quarterly/vision) with milestones checkoff triggers.
*   **Markdown Notes Canvas**: Notion-inspired folders manager with favorite star, thumbtack pinning, and dynamic HTML previews.
*   **Structured Reflections Journal**: Morning check-ins and evening summaries with mood buttons, energy sliders, and win trackers.
*   **Pomodoro Focus Timer**: Audio synth buzzer chimes (utilizing browser Web Audio API) with active task binding selectors.
*   **Calculated Analytics Dashboard**: Composition productivity indexes, consistency metrics, category charts, and contribution contribution graphs.
*   **Data Portability & Backups**: Dynamic JSON bundles exports, CSV spreadsheets, and downloadable SQL backups.
*   **Asynchronous Notification Engine**: Email queue database schema processed via short interval cron runners.

---

## 📂 Folder Structure

```
plan/
├── index.php                 # Core Front Controller / Router
├── .htaccess                 # Rewrite routing rules & security headers
├── config/                   # Configuration files
│   ├── config.php            # Environment constants & SMTP details
│   ├── db.php                # PDO connection & transactions wrapper
│   ├── security.php          # CSRF, XSS, rate-limiters, and inputs cleaning
│   └── helpers.php           # Flash alerts, streak calculations, quote providers
├── database/                 # Database migrations
│   ├── migrations/           # Chronological SQL schema migrations
│   ├── migrate.php           # Sequential migrations runner
│   ├── seed.php              # Demo sandbox seeder script
│   └── schema.sql            # Master database backup fallback
├── controllers/              # MVC Controllers
│   ├── BaseController.php    # Pages layouts renderer & auth guards
│   ├── AuthController.php    # Registrations, password recovery, verification
│   └── ...                   # Task, Habit, Goal, Journal, Analytics controllers
├── models/                   # MVC database queries mapper models
├── api/                      # Modular AJAX JSON endpoints (auth, calendar, tasks, etc.)
├── views/                    # Views layouts & panels
│   ├── layouts/              # Main app shell and guest backdrops
│   ├── partials/             # Sidebar, navbar, mobile sticky bottom navigation
│   └── ...                   # Page-specific views (tasks, habits, analytics)
├── vendor/
│   └── PHPMailer/            # Standalone PHPMailer stubs (ready for SMTP upload)
└── uploads/                  # Safe folder for user attachments and avatars
```

---

## 🛠️ Local Installation Guide (XAMPP / MAMP)

1.  **Clone / Copy files**: Move the project folder `plan/` into your local server root (e.g. `C:/xampp/htdocs/plan` or `/Applications/MAMP/htdocs/plan`).
2.  **Configure Database Credentials**: Open `config/config.php` and configure your database parameters:
    ```php
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'planner_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    ```
3.  **Run Database Migrations**: 
    *   Via CLI: Navigate to the `plan/database` directory and run: `php migrate.php`
    *   Via Web: Visit `http://localhost/plan/database/migrate.php` in your browser.
4.  **Seed Demo Sandbox**:
    *   Via CLI: Run `php seed.php` from `database/` directory.
    *   Via Web: Visit `http://localhost/plan/database/seed.php`.
5.  **Log In**: Open `http://localhost/plan/` and log in with the seeded sandbox profile:
    *   **Email**: `demo@aetherlife.com`
    *   **Password**: `password123`

---

## 🌐 Hostinger Shared Hosting Deployment Guide

1.  **Archive Project**: Zip your local project directory `plan/`.
2.  **Upload Files**: Use Hostinger File Manager to upload and extract the archive directly inside `public_html/` (or a subfolder).
3.  **Create Database**: Log in to Hostinger hPanel, go to **Databases > MySQL Databases**, and create a new database.
4.  **Configure Production Settings**: Open `config/config.php` inside Hostinger File Manager, and update:
    *   `APP_URL`: Set to your production URL (e.g. `https://yourdomain.com`).
    *   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: Set to Hostinger database credentials.
5.  **Run Migrations**: Visit `https://yourdomain.com/database/migrate.php` in your browser.
6.  **Secure Upload Directory**: Ensure `uploads/` folder is writeable (permission `755`). The system includes a default `uploads/.htaccess` file that denies executing any remote PHP files uploaded as attachments.

---

## 📧 PHPMailer & SMTP Configurations

The system is configured to use secure SMTP. To activate SMTP email delivery:
1.  Download the latest source files of **PHPMailer** (core files: `PHPMailer.php`, `SMTP.php`, `Exception.php`).
2.  Place these three files inside `vendor/PHPMailer/` (replacing the stubs).
3.  Configure SMTP credentials in `config/config.php`:
    ```php
    define('SMTP_HOST', 'smtp.hostinger.com');
    define('SMTP_PORT', 587); // or 465 for SSL
    define('SMTP_USER', 'your-account@yourdomain.com');
    define('SMTP_PASS', 'secure_smtp_password');
    define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
    ```
*Note: If no PHPMailer files are found under `vendor/`, the helper automatically falls back to native PHP `mail()`, allowing registrations to work out-of-the-box in local MAMP setups.*

---

## ⏰ Cron Jobs Setup (Hostinger hPanel)

To automate morning reports (sent daily at 7:30 AM) and process queued emails (sent every 5 minutes), set up the following cron jobs in your Hostinger hPanel under **Advanced > Cron Jobs**:

### 1. Email Queue Sender (Recommended: Run every 5 minutes)
Processes the SQL queue and dispatches emails.
*   **Command**: `php /home/uXXXX/public_html/cron/notification_sender.php`
*   **Interval**: `*/5 * * * *` (Every 5 minutes)

### 2. Daily Morning Digest (Recommended: Run daily at 7:30 AM)
Queues today's tasks and habits summaries for users.
*   **Command**: `php /home/uXXXX/public_html/cron/daily_report.php`
*   **Interval**: `30 7 * * *` (Daily at 7:30 AM)

*(Replace `/home/uXXXX/public_html/` with your Hostinger account absolute path, visible in hPanel information details).*

---

## 🔒 Security Practices Built-in

*   **Prepared Queries (SQLi Protection)**: All SQL statements execute utilizing PDO parameters binding.
*   **Strict Sanitization (XSS Protection)**: Clean output rendering wrapper `e($value)` escapes inputs.
*   **CSRF Tokens Verification**: Hidden input checks guard all post submissions.
*   **Sessions Security**: Timeouts session validations. Remember cookies are protected with `HTTPOnly` flags.
*   **Rate-Limiter**: Simple session-based throttling guards logins.
