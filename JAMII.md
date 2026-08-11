# Jumuishi (central platform)

**Source of truth:** `C:\Users\sweetheart\Desktop\Jamii_Jumuishi`

WDF / WomenLoan is module path **`women-loans`**.

## Local ports

| App | URL |
| --- | --- |
| Jumuishi | `http://127.0.0.1:8000` |
| WDF | `http://127.0.0.1:8001` (`composer dev`) |

In Jumuishi module settings, set **internal URL** to `http://127.0.0.1:8001`.

## Module endpoints (implemented)

| Endpoint | Auth |
| --- | --- |
| `GET /api/jumuishi/health` | none |
| `GET /api/jumuishi/queue-health` | none |
| `POST /api/jumuishi/users/provision` | `X-Jumuishi-Platform-Secret` |
| `POST /api/jumuishi/users/sync` | `X-Jumuishi-Platform-Secret` |
| `GET /jumuishi/sso/consume?ticket=&return_to=` | browser; exchanges ticket server-side |

## Auth redirects

With `JUMUISHI_ENABLED=true`: no local landing/login UI; guests go to Jumuishi SSO.

## Applicant create account (NIDA)

Public **Create account** stays on WDF (`/register`) for **applicants only** (NIDA flow + `applicant` role).

- Landing (Jumuishi) → Women Loans → **Create account** → `http://127.0.0.1:8001/register`
- After register, WDF assigns `applicant`, syncs identity to Jumuishi when enabled, then opens the applicant home.
- Staff still sign in only via Jumuishi (no local WDF login form).

## Seed staff accounts (admin@wdf.go.tz, …)

Local roles/data stay in WDF. **Login is only on Jumuishi**, so seed users must be pushed once:

```bash
# Jumuishi on :8000, WDF on :8001, secrets configured
php artisan jumuishi:push-users
```

That calls `POST /api/internal/users/sync` for each local user (password `password` from seeder), grants module access, and stores `global_user_id`.

Then sign in at Jumuishi with e.g. `admin@wdf.go.tz` / `password` → open Women Loans.
