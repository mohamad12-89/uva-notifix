# Notifix

Notifix is a full-stack platform for UVA Engineering Foundations office-hour coordination, announcements, appointments, and TA workflows.

Current architecture:

- Laravel API for core app data (office hours, announcements, appointments, TA bios)
- Vue 3 SPA frontend (Vite + Tailwind)
- Supabase Auth for user accounts, sessions, and role-driven access control

## Supabase Auth + Roles (current)

The app now uses Supabase for:

- email + password signup/login
- email verification before protected access
- session-based auth state
- role lookup from a Supabase `roles` table (`student` or `ta_professor`)

### Required environment variables

Set these in `.env`:

```env
VITE_SUPABASE_URL=your_supabase_project_url
VITE_SUPABASE_ANON_KEY=your_supabase_anon_key
```

`VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY` are also defined in `.env.example`.

## Setup steps

### 1) Clone and install

```bash
git clone https://github.com/mohamad12-89/uva-notifix.git
cd uva-notifix
composer install
npm install
```

### 2) App env and Laravel DB

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

On Windows PowerShell, use:

```powershell
copy .env.example .env
```

### 3) Configure Supabase

In Supabase SQL editor, run:

```sql
-- see full script:
-- docs/supabase_roles_setup.sql
```

The script creates:

- `public.roles`
- RLS policies for role-safe access
- seed rows for TA/professor emails:
  - `cdd9sb@virginia.edu`
  - `xfw9vp@virginia.edu`
  - `uhu5nr@virginia.edu`
  - (for testing student role) `khg5bj@virginia.edu` as `student`

### 3.1) Configure email link verification

The signup page uses Supabase email-link verification:

1. Go to Supabase Dashboard -> Authentication -> Providers -> Email.
2. Enable Email provider and keep Confirm email enabled.
3. In Supabase Auth URL configuration, include your local and deployed app URLs as allowed redirect URLs.
4. The app sets `emailRedirectTo` to `/signup`, so after clicking the email link the user returns to Notifix and is verified automatically.

### 4) Start app

```bash
npm run dev:full
```

Open:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

## Frontend auth code locations

### Signup / login

- `resources/js/pages/SignupPage.vue`
  - Sign up flow with Supabase email verification link:
    - `supabase.auth.signUp({ email, password, options: { emailRedirectTo, data } })`
  - Login with Supabase:
    - `supabase.auth.signInWithPassword({ email, password })`
  - Verification:
    - user clicks verification link from Supabase email
    - app reads updated Supabase session and continues automatically

### Session + role bootstrap

- `resources/js/composables/useAuthProfile.js`
  - `initializeAuth()`
  - `refreshAuthProfile()`
  - `fetchRoleByEmail(email)` from Supabase `roles` table
  - computed role flags: `isStudent`, `isTaProfessor`

### Route protection

- `resources/js/app.js`
  - blocks unauthenticated users from protected routes
  - blocks non-TA/professor users from `/instructor-dashboard`
  - blocks authenticated users from `/signup`

## Role-based behavior summary

- `ta_professor`:
  - can access Instructor Dashboard
  - can manage TA/professor role emails in dashboard
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

- Supabase is now the primary auth/session source.
- If you change role rows in Supabase, users may need to refresh or re-authenticate to pick up role changes immediately.
- If you hit build errors, run:
  - `npm run build`
  - check for merge markers (`<<<<<<<`, `=======`, `>>>>>>>`).

## Troubleshooting

### Missing Supabase Dependency Error

- **Symptom:** `Failed to resolve import "@supabase/supabase-js" from "resources/js/lib/supabase.js"`
- **Cause:** The Supabase package is not installed in your local `node_modules` directory.
- **Solution:** Stop the dev server (`Ctrl + C`), run `npm install @supabase/supabase-js`, and restart the server with `npm run dev:full`.
