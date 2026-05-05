# MY Penguin-LAB

MY Penguin-LAB is a web-based Linux learning simulator built with Laravel. The system supports role-based learning, randomized and assignable command-line question sets, class enrollment, scoring, course feedback, notifications, terminal access preparation, and administrative monitoring.

The platform is designed for students, lecturers, and administrators who need a structured environment for Linux command practice, assessment, progress tracking, and research-driven course improvement.

## System Overview

MY Penguin-LAB provides a guided Linux lab experience where students answer command-line scenarios, receive marks, use hints with score penalties, view progress, and access course feedback. Lecturers manage classes, enroll students, assign question sets, view class scoreboards, and review feedback summaries. Administrators manage users, semesters, question banks, feedback, notifications, terminal access settings, and system health.

The system uses a cyberpunk/game-style interface to make command-line learning more engaging while keeping the backend structure simple and maintainable.

## Key Features

- Role-based access for admin, lecturer, and student users
- Admin user management with search, pagination, password reset, status control, and delete safeguards
- Lecturer class management with CSV student import and enrollment summaries
- Semester management with current semester assignment
- Flexible question bank and assignable question sets
- Student scenario flow based on assigned class or individual question sets
- Hint system with score penalty
- Session-based and user-based scoring through student answers
- Student dashboard ranking, progress, badges, and leaderboard views
- Lecturer/admin scoreboard with class and semester filtering
- Course feedback activation, submission, analytics, raw data, and CSV export
- Notification center with AJAX polling and dismiss/read actions
- Profile update with photo upload and default avatar selection
- Terminal access control and Guacamole URL integration structure
- Admin system health dashboard for DB, storage, Docker, containers, Guacamole, queue, and notifications
- Responsive Bootstrap-based cyberpunk UI

## Tech Stack

- Backend: PHP 8.3+, Laravel 13
- Frontend: Blade, Bootstrap 5, custom CSS, JavaScript
- Database: MySQL/MariaDB or SQLite for local development
- Build tooling: Vite, Node.js, npm
- Authentication: Laravel session authentication
- Storage: Laravel filesystem storage
- Optional lab integration: Docker, Apache Guacamole, SSH
- Optional background processing: Laravel queue

## System Modules

### Admin

- Dashboard and system overview
- Manage users, roles, status, passwords, and terminal access
- Manage semesters
- Manage classes and enrolled students
- Manage question bank and question sets
- Assign question sets to classes or individual students
- View scoreboards, leaderboards, and exam results
- Manage course feedback questions and feedback activation
- View feedback analytics and raw feedback data
- Manage notifications
- View login security logs
- Monitor system health

### Lecturer

- Create, edit, and manage own classes
- Upload CSV student lists
- View students in own classes
- Reset or disable students under own classes
- Create own questions and question sets
- Assign active valid question sets to own classes or students
- View class scoreboard and student progress
- Enable course feedback for own classes
- View feedback summaries for own classes
- View terminal status for students in own classes

### Student

- View dashboard, score, rank, progress, and badge
- Choose assigned question set when multiple sets are available
- Answer Linux command scenarios
- Use hint with mark penalty
- View results and progress
- Submit course feedback when activated
- View notifications
- Update own profile and avatar
- Launch terminal access when enabled

## Installation

### Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL/MariaDB or SQLite
- Web server or Laravel Herd/local PHP server
- Optional: Docker and Apache Guacamole for terminal integration

### Setup Steps

1. Clone the repository.

```bash
git clone <repository-url>
cd shellfix
```

2. Install PHP dependencies.

```bash
composer install
```

3. Install frontend dependencies.

```bash
npm install
```

4. Create the environment file.

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

5. Generate the application key.

```bash
php artisan key:generate
```

6. Configure the database in `.env`.

Example for MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Do not commit real credentials to version control.

7. Run migrations and seeders.

```bash
php artisan migrate --seed
```

8. Create the storage link for uploaded profile photos.

```bash
php artisan storage:link
```

9. Build frontend assets.

```bash
npm run build
```

## How To Run

For local development:

```bash
php artisan serve
```

In a second terminal, run Vite if working on frontend assets:

```bash
npm run dev
```

If queue processing is enabled:

```bash
php artisan queue:work
```

Open the application in a browser:

```text
http://127.0.0.1:8000
```

If using Laravel Herd, open the configured local site URL instead.

## Optional Terminal Integration

MY Penguin-LAB includes a safe terminal integration structure for Docker and Apache Guacamole. By default, automation should remain disabled until the server environment is ready.

Relevant environment placeholders:

```env
GUACAMOLE_BASE_URL=http://YOUR-SERVER-IP:8080/guacamole
GUACAMOLE_MODE=placeholder
TERMINAL_AUTOMATION_ENABLED=false
TERMINAL_DOCKER_IMAGE=ubuntu:22.04
TERMINAL_CONTAINER_PREFIX=penguinlab_
```

Do not store or publish real Guacamole database passwords, SSH keys, or server credentials.

## Course Feedback

Course feedback is controlled by admin or lecturer activation. Students can submit feedback when access is enabled for their user, class, or target group. Feedback analytics include category averages, question averages, rating distribution, class trends, top and lowest rated questions, and a local rule-based research summary.

## System Health Dashboard

The admin System Health Dashboard monitors:

- Laravel app availability
- Database connection
- Storage writability
- Docker engine status
- Running student containers
- Guacamole HTTP availability
- Queue status
- Notification polling status

The dashboard refreshes automatically using AJAX and does not execute Docker lifecycle actions.

## Version Information

- System name: MY Penguin-LAB
- Project codename: ShellFix
- Current phase: Phase 8
- Version: 1.0.0
- Framework: Laravel 13
- PHP requirement: 8.3+

## Security Notes

- Never commit `.env` files with real credentials.
- Keep terminal automation disabled until Docker, SSH, and Guacamole are properly secured.
- Use HTTPS in production.
- Restrict admin and lecturer accounts carefully.
- Rotate default passwords before production use.
- Review file permissions for storage and uploaded assets.

## Author

Developed by Faizal Yahaya 

MY Penguin-LAB is intended for Linux learning, classroom assessment, and academic research improvement workflows.
