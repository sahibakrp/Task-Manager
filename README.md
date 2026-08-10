# Task Manager

This repository contains a PHP backend and a React frontend for a task management application.

## Project Structure

- `backend/` � PHP REST API and data models
- `frontend/` � React application built with Vite
- `backend/database/schema.sql` � MySQL database schema
- `backend/config/` � database and JWT configuration
- `backend/storage/` � JSON fallback storage for tasks and users
- `README.md` � setup and usage instructions

## Backend Overview

The backend uses PHP and supports both MySQL persistence and JSON fallback storage.

- `backend/index.php` handles routing and JSON responses.
- `backend/config/db.php` defines the `App\Config\Database` connection class.
- `backend/config/jwt.php` contains JWT settings.
- `backend/routes/` contains route handlers for auth, users, and tasks.
- `backend/models/` contains the task and user models.

## Backend Requirements

- PHP 8+
- Composer
- Optional MySQL server for full persistence

## Backend Setup

```bash
cd backend
composer install
```

Create a `.env` file in `backend/` or set these environment variables:

```ini
APP_ENV=development
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=task_manager
DB_USER=root
DB_PASSWORD=
JWT_SECRET=change_this_secret
ALLOWED_ORIGINS=http://localhost:5173
```

### Run the backend locally

```bash
php -S 127.0.0.1:8000 index.php
```

### Test the backend

```bash
curl http://127.0.0.1:8000/
```

## Backend API Endpoints

- `GET /` � API root
- `GET /health` � service health check
- `GET /health/db` � database connectivity check
- `POST /register` � create a new user
- `POST /login` � authenticate user and receive a JWT
- `POST /logout` � logout endpoint
- `GET /tasks` � list tasks
- `GET /tasks/:id` � get a task by id
- `POST /tasks` � create a task
- `PUT /tasks/:id` � update a task
- `DELETE /tasks/:id` � delete a task
- `GET /users` � list users (admin only)
- `GET /users/:id` � retrieve user by id (admin only)

## Authentication

The API uses JWT tokens.

- JWT configuration is stored in `backend/config/jwt.php`.
- Send `Authorization: Bearer <token>` on protected requests.

Example login request:

```http
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "secret"
}
```

Example login response:

```json
{
  "token": "<JWT>",
  "user": {"id": 1, "name": "...", "email": "..."}
}
```

## Frontend Overview

The frontend is a React application built with Vite and Tailwind CSS.

### Frontend Setup

```bash
cd frontend
npm install
```

### Run the frontend

```bash
npm run dev
```

Open the app at:

```text
http://localhost:5173/
```

### Frontend Configuration

If the backend is hosted elsewhere, create `frontend/.env` with:

```ini
VITE_API_BASE=http://localhost:8000
```

## Notes

- Registration and login pages are available at `/register` and `/login`.
- The frontend stores JWT in `localStorage` and sends it in `Authorization` headers.
- The backend can fallback to JSON storage in `backend/storage/` if MySQL is unavailable.
- Use `backend/database/schema.sql` for MySQL schema setup.

## Troubleshooting

- Verify `JWT_SECRET` is set.
- Confirm `ALLOWED_ORIGINS` includes `http://localhost:5173`.
- Run `composer install` in `backend/` and `npm install` in `frontend/`.
- Start the PHP backend from `backend/` using `index.php` as the router.



## Assessment Completion

### Mandatory Tasks

- [x] Task 1 — Full-Stack CRUD App: Task Manager
- [x] Task 2 — Authentication & Authorization
- [x] Task 3 — Database Design & Query Optimization

### Optional Tasks

- [ ] Task 4 — Containerization with Docker
- [ ] Task 5 — Node.js / PostgreSQL Variant
- [ ] Task 6 — Cloud Deployment: AWS / GCP
- [ ] Task 7 — AI Agent Feature

### Time Spent

The core mandatory Tasks 1–3 were initially implemented in approximately 5–6 hours on Saturday 08/08/2026.

Additional time was spent on testing, debugging, database/query optimization, and final verification before submission.

The optional Tasks 4–7 were not attempted within the available assessment time.