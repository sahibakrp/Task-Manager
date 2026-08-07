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
