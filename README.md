# Notifix

Notifix is a full-stack web app for **UVA Engineering Foundations** students. It brings office hours, announcements, appointments, and TA information into one place with a UVA-themed UI.

The app uses a **Laravel** JSON API and a **Vue 3** single-page frontend (Vue Router + Tailwind CSS).

---

## Project overview

| Layer | Role |
|--------|------|
| **Laravel (`routes/api.php`)** | REST-style JSON API for office hours, announcements, appointments, TA bios, and optional server auth. |
| **Vue (`resources/js/`)** | Client UI: pages, shared composables (e.g. office hours state, local auth profile), and routing. |
| **SQLite (default)** | Persists Laravel models in development; configurable via `.env`. |

**Data flow:** The browser loads the Laravel app from `public/` (via the PHP dev server). The Vue app calls `/api/...` through Axios (see `resources/js/lib/api.js`), typically with `APP_URL` / same origin when using `npm run dev:full`.

---

## Tech stack

- **Backend:** Laravel (PHP)
- **Frontend:** Vue 3 (Composition API), Vue Router
- **Styling:** Tailwind CSS v4
- **Build:** Vite + Laravel Vite plugin
- **Database:** SQLite by default (MySQL supported via `.env`)

---

## Core features (current)

- **First-visit auth (demo / design)**  
  New visitors are directed to `/signup`. They can **sign up** (UVA `@virginia.edu` email, password, mocked email verification with a 2-minute window and any 6-digit code) or **log in** with any fake `@virginia.edu` email and password. Profile is stored in **localStorage** (not production-grade auth).

- **Profile**  
  After login, initials appear in the navbar; clicking opens **Profile** (`/profile`) to edit name and password and to **sign out** (returns to `/signup`). The **Account** nav link is hidden while logged in via the demo profile.

- **Home**  
  This week’s office hours in **list** or **calendar** view; join / unjoin updates attendance counts via the API.

- **Office hours**  
  Monthly calendar, search with suggestions, add/edit/delete slots (start/end time), join/unjoin.

- **Announcements**  
  View and manage announcements (API-backed).

- **Appointments**  
  Create and manage appointment requests.

- **TA bios**  
  View and manage TA biography entries.

- **Instructor dashboard**  
  Instructor-oriented UI (aligned with recent backend/dashboard work).

- **Account**  
  Server login form (`POST /api/auth/login`) for API-backed auth when not using only the demo flow.

---

## Frontend routes

| Path | Page |
|------|------|
| `/signup` | Sign up / log in (gate for unauthenticated demo users) |
| `/` | Home |
| `/office-hours` | Office hours calendar & management |
| `/announcements` | Announcements |
| `/appointments` | Appointments |
| `/ta-bios` | TA bios |
| `/account` | Account (API login) |
| `/profile` | Profile settings & sign out (demo profile) |
| `/instructor-dashboard` | Instructor dashboard |

Router guard: unverified demo users (no stored verified profile) are redirected to `/signup`; verified users hitting `/signup` are sent to `/`.

---

## API endpoints (prefix `/api`)

**Office hours**

- `GET /api/office-hours` — list
- `POST /api/office-hours` — create
- `PUT /api/office-hours/{id}` — update
- `DELETE /api/office-hours/{id}` — delete
- `POST /api/office-hours/{id}/join` — increment attendance
- `DELETE /api/office-hours/{id}/join` — decrement attendance (non-negative)
- `GET /api/analytics/office-hours` — office-hours analytics

**Announcements**

- `GET /api/announcements`
- `POST /api/announcements`

**Appointments**

- `GET/POST/PUT/DELETE /api/appointments` (standard REST shapes)

**TA bios**

- `GET/POST/PUT/DELETE /api/ta-bios`

**Auth (server)**

- `POST /api/auth/register`
- `POST /api/auth/login`

---

## Repository

- **GitHub:** [https://github.com/mohamad12-89/uva-notifix](https://github.com/mohamad12-89/uva-notifix)

---

## How to access the project (clone and run)

These steps match the scripts in `package.json` and the PHP dev server binding used in this repo.

### 1) Clone

```bash
git clone https://github.com/mohamad12-89/uva-notifix.git
cd uva-notifix
```

If you cloned into another folder name, `cd` into that folder instead.

### 2) Install dependencies

```bash
composer install
npm install
```

### 3) Environment and database

Create `.env` from the example, generate an app key, and migrate:

**Windows (PowerShell)**

```powershell
copy .env.example .env
```

**macOS / Linux**

```bash
cp .env.example .env
```

Then:

```bash
php artisan key:generate
php artisan migrate
```

### 4) Start the app (recommended)

```bash
npm run dev:full
```

This runs **both**:

- **PHP** — `php -S 127.0.0.1:8080 -t public` (serves Laravel from `public/`)
- **Vite** — dev server (default port **5173**; if busy, Vite picks the next free port, e.g. **5174**)

**Open the app in the browser at:**

**[http://127.0.0.1:8080](http://127.0.0.1:8080)**

Use this URL (not only the Vite port) so Laravel serves HTML and wires the Vite dev assets correctly.

### 5) Production-style frontend build (optional)

```bash
npm run build
```

Laravel will use compiled assets from `public/build/` when not in dev mode.

---

## npm scripts

| Script | Purpose |
|--------|---------|
| `npm run dev` | Vite only |
| `npm run serve:php` | PHP built-in server on `127.0.0.1:8080` with document root `public` |
| `npm run dev:full` | PHP + Vite together (recommended for local development) |
| `npm run build` | Production Vite build |

---

## Notes

- **SQLite** is the default; switch DB in `.env` and run `php artisan migrate` (or `migrate:fresh` when appropriate).
- **Port 8080** must be free for `serve:php`. If it is in use, change the port in `package.json` (`serve:php`) or stop the other process.
- **Merge conflicts:** If you see Vite errors like `Unexpected token '<<'`, search the repo for Git conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`) and resolve them.
- **Demo auth** is client-side only; do not rely on it for real security. Use Laravel auth and policies when moving to production.

---

## Recent direction (changelog summary)

- Shared **office hours** state (`useOfficeHours`) so deletes and search stay consistent across pages; **join/unjoin** API and UI.
- **Home** shows **this week’s** hours; office hours form uses labeled fields and layout improvements.
- **Announcements** API and UI; **instructor dashboard** and related backend updates.
- **First-visit** sign-up / log-in flow (`@virginia.edu`, mocked verification), **profile** page, navbar initials, **sign out**.
- **README** updated to reflect routes, API, and accurate local access via `http://127.0.0.1:8080` with `npm run dev:full`.
