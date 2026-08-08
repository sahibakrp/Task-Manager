# Task Manager

This repository contains a PHP backend and React frontend for a task management application.

## Project Structure

- `backend/` - PHP REST API
- `frontend/` - React application
- `database/schema.sql` - MySQL database schema
- `.gitignore` - Ignored files and folders
- `README.md` - Project overview

## Next Steps

1. Initialize Git
   - `git init`
2. Set up the MySQL database using `database/schema.sql`
3. Build the PHP REST API in `backend/`
4. Add authentication and authorization
5. Create the React frontend

## Backend Quick Start

Run the PHP API locally with:

```bash
cd backend
php -S 127.0.0.1:8000
```

Then test:

```bash
curl http://127.0.0.1:8000/tasks
```

The starter backend now works with a local file-based fallback store when no MySQL server is available, while still using MySQL once your database is configured.

# Authentication

Endpoints implemented in backend:

- `POST /register` — register a new user. Body: `{name, email, password}`
- `POST /login` — login and receive a JWT. Body: `{email, password}`
- `POST /logout` — stateless logout (client discards token)
- `GET /tasks` — list tasks for the current user
- `GET /tasks/:id` — fetch a single task by id
- `POST /tasks` — create a task for the current user
- `PUT /tasks/:id` — update a task you own
- `DELETE /tasks/:id` — delete a task you own

JWT handling:
- Tokens are signed using HMAC SHA-256. Configuration in `backend/config/jwt.php`.
- Token payload includes `sub` (user id), `name`, `email`, and `role_id`.

Usage (example):

Request:

```
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "secret"
}
```

Response:

```
{
  "token": "<JWT>",
  "user": {"id":1,"name":"...","email":"..."}
}
```

Protecting routes:
- Include header `Authorization: Bearer <token>` on requests.
- Use `backend/middleware/Auth.php` helper functions `jwt_decode()` and `get_bearer_token()` in controllers/middleware to validate tokens.



# Task Manager Frontend

This React frontend connects to the PHP backend at `http://localhost/Task-Manager/backend`.

## Setup

1. Install dependencies:

```bash
cd frontend
npm install
```

2. Start the development server:

```bash
npm run dev
```

3. Open the frontend in your browser:

```text
http://localhost:5173/
```

## Default endpoints

- React frontend: `http://localhost:5173/`
- PHP backend API: `http://localhost/Task-Manager/backend`

Do not use the automatically-displayed network addresses unless you specifically need another device on your network. Use `localhost` for the simplest setup.

## Tailwind CSS

This project is configured to use Tailwind CSS.

- Installed packages: `tailwindcss`, `postcss`, `autoprefixer`
- Tailwind config: `tailwind.config.js`
- PostCSS config: `postcss.config.js`
- CSS entry: `src/index.css`

The `src` files are scanned for Tailwind classes from:

```text
./index.html
./src/**/*.{js,jsx,ts,tsx}
```

You can now use utility classes in any React component, for example:

```jsx
<div className="min-h-screen bg-slate-50 text-slate-900">
  ...
</div>
```

## Notes

- Login and register pages are available at `/login` and `/register`.
- After login, the dashboard loads tasks from the backend.
- The app stores JWT in `localStorage` and sends it in `Authorization: Bearer <token>` headers.
- If your backend runs from a different host/port, set `VITE_API_BASE` in a `.env` file:

```
VITE_API_BASE=http://localhost/Task-Manager/backend
```