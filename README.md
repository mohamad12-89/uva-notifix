# Notifix

Notifix is a full-stack platform for UVA Engineering Foundations office-hour coordination, announcements, appointments, and TA workflows.

Current architecture:

- Laravel API for core app data (office hours, announcements, appointments, TA bios)
- Vue 3 SPA frontend (Vite + Tailwind)
- Local/session-based auth mock for user accounts and roles (Supabase paused)

## Auth + Roles (current)

The app currently uses a local mock auth flow for:

- email + password signup/login UI
- lightweight verification modal flow (demo mode)
- per-tab session-based auth state
- role lookup from hardcoded TA/professor emails (`student` or `ta_professor`)

### Required environment variables

No Supabase environment variables are required in the paused mode.

## Setup steps

### 0) Prerequisites (install first)

Your friend should install these before running the project:

- Git
- PHP 8.2+ (with `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`)
- Composer 2.x
- Node.js 20+ and npm
- SQLite (or at least SQLite support enabled in PHP)

Quick version checks:

```bash
php -v
composer -V
node -v
npm -v
```

### 1) Clone and install

```bash
git clone https://github.com/mohamad12-89/uva-notifix.git
cd uva-notifix
composer install
npm install
```

### 1.1) Key JavaScript packages used (installed by `npm install`)

- `vue`, `vue-router` (frontend app + routing)
- `vite`, `@vitejs/plugin-vue` (dev/build tooling)
- `tailwindcss`, `@tailwindcss/vite` (styling)
- `axios` (API requests)
- `concurrently` (runs Laravel PHP server + Vite together)

### 2) App env and Laravel DB

```bash
cp .env.example .env
php artisan key:generate
New-Item -Path database/database.sqlite -ItemType File -Force  # PowerShell
php artisan migrate
```

On Windows PowerShell, use:

```powershell
copy .env.example .env
```

Then set these values in `.env`:

```env
APP_URL=http://127.0.0.1:8080
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### 3) Role mapping (hardcoded)

TA/Professor emails are currently hardcoded in `resources/js/composables/useAuthProfile.js`:

- `khg5bj@virginia.edu`
- `cdd9sb@virginia.edu`
- `xfw9vp@virginia.edu`
- `uhu5nr@virginia.edu`

Any other `@virginia.edu` email is treated as `student`.

### 4) Start app

```bash
npm run dev:full
```

Open:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

### 5) First-run validation checklist

- `npm run build` completes without errors
- Signup opens verification modal and allows continue flow
- TA account can access `/instructor-dashboard`
- Student account cannot access `/instructor-dashboard`

## Frontend auth code locations

### Signup / login

- `resources/js/pages/SignupPage.vue`
  - Sign up flow with local verification modal (demo mode)
  - Login flow accepts any password for `@virginia.edu` emails
  - Verification:
    - user confirms from the modal and is logged into a per-tab local session

### Session + role bootstrap

- `resources/js/composables/useAuthProfile.js`
  - `initializeAuth()`
  - `refreshAuthProfile()`
  - hardcoded role mapping by email
  - computed role flags: `isStudent`, `isTaProfessor`

### Route protection

- `resources/js/app.js`
  - blocks unauthenticated users from protected routes
  - blocks non-TA/professor users from `/instructor-dashboard`
  - blocks authenticated users from `/signup`

## Role-based behavior summary

- `ta_professor`:
  - can access Instructor Dashboard
  - can access TA-only actions in relevant pages
- `student`:
  - no Instructor Dashboard access
  - student-only actions remain available

## API endpoints (Laravel)

### Office hours

- `GET /api/office-hours`
- `POST /api/office-hours`
- `PUT /api/office-hours/{officeHour}`
- `DELETE /api/office-hours/{officeHour}`
- `POST /api/office-hours/{officeHour}/join`
- `DELETE /api/office-hours/{officeHour}/join`
- `GET /api/office-hours/{officeHour}/signups`
- `POST /api/office-hours/{officeHour}/signups/{signup}/check-in`
- `GET /api/analytics/office-hours`

### Announcements

- `GET /api/announcements`
- `POST /api/announcements`
- `DELETE /api/announcements/{announcement}`

### Appointments

- `GET /api/appointments`
- `POST /api/appointments`
- `PUT /api/appointments/{appointment}`
- `DELETE /api/appointments/{appointment}`

### TA bios

- `GET /api/ta-bios`
- `POST /api/ta-bios`
- `PUT /api/ta-bios/{taBio}`
- `DELETE /api/ta-bios/{taBio}`

### Legacy Laravel auth endpoints (optional/legacy)

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/verify`

## Notes

- Supabase integration is currently paused.
- Current auth/profile state is stored in `sessionStorage` per tab.
- If you hit build errors, run:
  - `npm run build`
  - check for merge markers (`<<<<<<<`, `=======`, `>>>>>>>`).
