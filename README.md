# Notifix

Notifix is a full-stack web app for UVA Engineering Foundations students to browse and join office hours, request appointments, and view TA bios.

## Tech Stack

- Backend: Laravel (PHP)
- Frontend: Vue 3 (Composition API) + Vue Router
- Styling: Tailwind CSS
- Database: SQLite (default dev setup, easy to switch to MySQL)

## Features

- Homepage with weekly office hours, join action, and collapsible About section
- Office hours calendar page with add/edit/delete/join support
- Appointment request form with persistent storage and confirmation
- TA bios page with add/edit/delete support
- Account page with simple register/login API flow
- Shared navigation across all pages

## API Endpoints

- `GET/POST/PUT/DELETE /api/office-hours`
- `POST /api/office-hours/{id}/join`
- `GET/POST/PUT/DELETE /api/appointments`
- `GET/POST/PUT/DELETE /api/ta-bios`
- `POST /api/auth/register`
- `POST /api/auth/login`

## Local Setup

1. Install PHP, Composer, and Node.js.
2. Install backend dependencies:
   - `composer install`
3. Install frontend dependencies:
   - `npm install`
4. Copy env file (if missing):
   - `copy .env.example .env`
5. Generate app key:
   - `php artisan key:generate`
6. Run migrations:
   - `php artisan migrate`
7. Start Laravel server:
   - `php artisan serve`
8. In another terminal, start Vite:
   - `npm run dev`
9. Open:
   - `http://127.0.0.1:8000`

## Notes

- SQLite is configured by default for development.
- To switch to MySQL, update `.env` DB settings and run `php artisan migrate:fresh`.
