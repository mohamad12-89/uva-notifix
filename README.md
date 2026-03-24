# Notifix

Notifix is a full-stack web app for UVA Engineering Foundations students to:

- view weekly and calendar-based office hours
- join office hours (attendance count updates)
- submit appointment requests
- view and manage TA bios

This project is built with a Laravel API backend and a Vue 3 frontend with a UVA-inspired design.

## Tech Stack

- Backend: Laravel (PHP)
- Frontend: Vue 3 (Composition API) + Vue Router
- Styling: Tailwind CSS
- Database: SQLite (default dev setup, easy to switch to MySQL)

## Core Features

- Homepage with weekly office hours, join action, and collapsible About section
- Office Hours page with weekly calendar layout and add/edit/delete/join support
- Appointments page with persistent request submission and edit/delete support
- TA Bios page with add/edit/delete support
- Account page with login UI (register section removed from UI)
- Shared navigation across all pages

## API Endpoints

- `GET/POST/PUT/DELETE /api/office-hours`
- `POST /api/office-hours/{id}/join`
- `GET/POST/PUT/DELETE /api/appointments`
- `GET/POST/PUT/DELETE /api/ta-bios`
- `POST /api/auth/register`
- `POST /api/auth/login`

## Repository

- GitHub: [https://github.com/mohamad12-89/uva-notifix](https://github.com/mohamad12-89/uva-notifix)

## Clone and Run (New Environment)

### 1) Clone the project

```bash
git clone https://github.com/mohamad12-89/uva-notifix.git
cd uva-notifix
```

### 2) Install dependencies

```bash
composer install
npm install
```

### 3) Configure environment

Create `.env` if needed:

- Windows PowerShell:
  - `copy .env.example .env`
- macOS/Linux:
  - `cp .env.example .env`

Generate app key and run migrations:

```bash
php artisan key:generate
php artisan migrate
```

### 4) Start the app (single command)

```bash
npm run dev:full
```

This command starts:

- PHP server: `http://127.0.0.1:8080`
- Vite frontend dev server (auto-selects open port)

Open the app in your browser:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

## Available Scripts

- `npm run dev` - start Vite only
- `npm run serve:php` - start PHP server only
- `npm run dev:full` - start PHP + Vite together (recommended)
- `npm run build` - production frontend build

## Notes

- SQLite is configured by default for development.
- To switch to MySQL, update `.env` DB settings and run `php artisan migrate:fresh`.
- If ports are busy, Vite automatically uses the next available port.
