# Notifix

Notifix is a full-stack platform for UVA Engineering Foundations office-hour coordination, announcements, appointments, and TA workflows.

Current architecture:

- Laravel API for core app data (office hours, announcements, appointments, TA bios)
- Vue 3 SPA frontend (Vite + Tailwind)
- AWS Cognito auth for account/password/email verification

## Auth + Roles (AWS Cognito)

The app now uses Cognito for:

- email + password signup/login
- required email verification code flow
- JWT-based API authentication (bearer token)
- role resolution from Cognito groups with email allowlist fallback

### Required environment variables

Set these in `.env`:

```env
AWS_DEFAULT_REGION=us-east-1
COGNITO_USER_POOL_ID=us-east-1_yourPoolId
COGNITO_APP_CLIENT_ID=yourAppClientId
COGNITO_ISSUER=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_yourPoolId
COGNITO_JWKS_URL=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_yourPoolId/.well-known/jwks.json
VITE_AWS_REGION=${AWS_DEFAULT_REGION}
VITE_COGNITO_USER_POOL_ID=${COGNITO_USER_POOL_ID}
VITE_COGNITO_APP_CLIENT_ID=${COGNITO_APP_CLIENT_ID}
```

## Setup steps

### 0) Prerequisites (install first)

Your friend should install these before running the project:

- Git
- PHP 8.4+ (with `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`)
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

### If you already have the repo (after pulling updates)

Yes — because of the Cognito integration, your friends need to install/update dependencies after pulling.

After `git pull`, run:

```bash
composer install
npm install
```

Make sure `.env` has these Cognito values:

```env
AWS_DEFAULT_REGION=us-east-1
COGNITO_USER_POOL_ID=us-east-1_y5iIepcok
COGNITO_APP_CLIENT_ID=7pr91atefpbav8deqfvgtifllb
COGNITO_ISSUER=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_y5iIepcok
COGNITO_JWKS_URL=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_y5iIepcok/.well-known/jwks.json
VITE_AWS_REGION=us-east-1
VITE_COGNITO_USER_POOL_ID=us-east-1_y5iIepcok
VITE_COGNITO_APP_CLIENT_ID=7pr91atefpbav8deqfvgtifllb
```

Then run:

```bash
php artisan config:clear
php artisan migrate
npm run dev:full
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

### 3) Role mapping

Defaults in `resources/js/composables/useAuthProfile.js`:

**Professor** (Instructor Dashboard + staff tools):

- `cdd9sb@virginia.edu`
- `amm8km@virginia.edu`

**TA** (staff tools, no Instructor Dashboard):

- `xfw9vp@virginia.edu`
- `uhu5nr@virginia.edu`
- `amq6tv@virginia.edu`

Primary role source is Cognito groups (`student`, `ta`, `professor`). Allowlists are fallback when group assignment has not yet been applied.

### 4) Start app

```bash
npm run dev:full
```

Open:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

### 5) First-run validation checklist

- `npm run build` completes without errors
- Signup sends a real Cognito verification code and requires confirm before login
- Professor account can access `/instructor-dashboard`; TA account cannot
- Student account cannot access `/instructor-dashboard`

## Frontend auth code locations

### Signup / login

- `resources/js/pages/SignupPage.vue`
  - Sign up flow with Cognito verification code
  - Login flow against Cognito credentials
  - Stores Cognito JWTs in session storage and sends bearer token to API

### Session + role bootstrap

- `resources/js/composables/useAuthProfile.js`
  - `initializeAuth()`
  - `refreshAuthProfile()`
  - Cognito JWT claim parsing + fallback email role mapping
  - computed role flags: `isStudent`, `isTa`, `isProfessor`, `isStaff`

### Route protection

- `resources/js/app.js`
  - blocks unauthenticated users from protected routes
  - blocks non-TA/professor users from `/instructor-dashboard`
  - blocks authenticated users from `/signup`

## Role-based behavior summary

- `professor`:
  - can access Instructor Dashboard (and email management)
  - same staff tools as TA on other pages
- `ta`:
  - no Instructor Dashboard
  - staff actions (office hours, announcements, check-in, etc.)
- `student`:
  - no Instructor Dashboard
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

## Deployment

- EC2 deployment assets were intentionally removed from this project.
- AWS is currently used only for Cognito authentication (verification + password management).
- Hosting/runtime setup is intentionally left for later.

## Notes

- Current auth/profile tokens are stored in `sessionStorage` per tab.
- If you hit build errors, run:
  - `npm run build`
  - check for merge markers (`<<<<<<<`, `=======`, `>>>>>>>`).
