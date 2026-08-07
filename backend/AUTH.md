# Authentication

Endpoints implemented in backend:

- `POST /register` — register a new user. Body: `{name, email, password}`
- `POST /login` — login and receive a JWT. Body: `{email, password}`
- `POST /logout` — stateless logout (client discards token)

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
