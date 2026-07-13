# Women Development Fund — Loan Management Platform

A web-based loan application and approval system for the **Women Development Fund (WDF)** under the Ministry of Community Development, Gender, Women and Special Groups (MoCGWSG), Tanzania. The platform supports multi-step approval workflows, role-based access, geographic scoping for Community Development Officers (CDOs), bilingual UI (English / Swahili), and TZS currency formatting.

---

## Table of Contents

1. [Features](#features)
2. [Technology Stack](#technology-stack)
3. [Requirements](#requirements)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [Default Login Accounts](#default-login-accounts)
7. [Application Modules](#application-modules)
8. [Loan Approval Workflow](#loan-approval-workflow)
9. [Roles & Permissions](#roles--permissions)
10. [Data Scoping & Security](#data-scoping--security)
11. [URL Hash IDs](#url-hash-ids)
12. [Architecture](#architecture)
13. [Project Structure](#project-structure)
14. [Database Schema](#database-schema)
15. [Routes & API Endpoints](#routes--api-endpoints)
16. [Global Helpers](#global-helpers)
17. [Internationalization](#internationalization)
18. [Frontend & UI](#frontend--ui)
19. [Performance Optimizations](#performance-optimizations)
20. [Development Commands](#development-commands)
21. [Seeding & Sample Data](#seeding--sample-data)
22. [Deployment](#deployment)
23. [License](#license)

---

## Features

| Area | Capabilities |
|------|--------------|
| **Applicants** | NIDA-style profile registration, geographic location (Region → Street), loan history |
| **Loan applications** | Multi-step wizard, draft save/resume, business details, guarantors, document uploads |
| **Workflow** | 9-step approval pipeline from Ward CDO through disbursement |
| **Loan groups** | Group lending with member assignment and uniqueness validation |
| **Dashboard & reports** | KPI cards, Chart.js trends, pipeline breakdown, regional stats (scoped per role) |
| **Repayments** | Loan payment tracking with role-scoped visibility |
| **Administration** | User management with geographic zones; full role CRUD with grouped permission UI |
| **RBAC** | Spatie permissions, permission-gated sidebar/routes, custom roles, `super_admin` bypass |
| **UX** | Dark mode, Bootstrap-style status badges, unified tables/cards, outline icon actions, EN/SW locale switcher, local assets (no CDN) |

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Database | MySQL (production) / SQLite (local dev) |
| Auth & RBAC | Laravel session auth + [Spatie Laravel Permission](https://github.com/spatie/laravel-permission) v6 |
| ID obfuscation | [hashids/hashids](https://github.com/vinkla/hashids) v5 — encoded IDs in resource URLs |
| Frontend | Blade templates, Tailwind CSS 4, Alpine.js 3, Vite 7 |
| Charts | Chart.js 4 (lazy-loaded on dashboard/reports only) |
| Fonts | Inter (Latin subset via `@fontsource/inter`, self-hosted) |
| Build | Vite + `@tailwindcss/vite` |

---

## Requirements

- PHP **8.2+** with extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`
- Composer 2.x
- Node.js **18+** and npm
- MySQL 8+ (recommended for production) or SQLite (local)

---

## Installation

### Quick setup (Composer script)

```bash
git clone <repository-url> WomenLoan
cd WomenLoan
composer setup
```

This runs: `composer install`, copies `.env`, generates app key, migrates, `npm install`, and `npm run build`.

### Manual setup

```bash
# 1. Dependencies
composer install
cp .env.example .env
php artisan key:generate

# 2. Database — edit .env first (see Configuration)
php artisan migrate

# 3. Seed roles, geography, staff users, and sample data
php artisan db:seed
# Or fresh install with sample data:
php artisan migrate:fresh --seed

# 4. Frontend
npm install
npm run build

# 5. Storage link (for uploaded documents)
php artisan storage:link

# 6. Run
php artisan serve
```

Visit `http://localhost:8000` and log in with any seeded account (password: `password`).

### Development with hot reload

```bash
composer dev
```

Starts concurrently: `php artisan serve`, queue listener, log tail (`pail`), and `npm run dev` (Vite HMR).

---

## Configuration

Key environment variables in `.env`:

| Variable | Description | Default (example) |
|----------|-------------|-------------------|
| `APP_NAME` | Application name | `Laravel` |
| `APP_URL` | Base URL | `http://localhost` |
| `APP_DEBUG` | Debug mode | `true` (set `false` in production) |
| `APP_LOCALE` | Default language | `en` |
| `DB_CONNECTION` | Database driver | `sqlite` / `mysql` |
| `DB_DATABASE` | Database name/path | `database/database.sqlite` |
| `SESSION_DRIVER` | Session storage | `database` |
| `CACHE_STORE` | Cache driver | `database` (use `redis` in production) |
| `QUEUE_CONNECTION` | Queue driver | `database` |
| `HASHIDS_SALT` | Salt for URL hash IDs | falls back to `APP_KEY` |
| `HASHIDS_LENGTH` | Minimum encoded ID length | `8` |

### Production recommendations

```env
APP_DEBUG=false
LOG_LEVEL=warning
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## Default Login Accounts

All seeded staff accounts use password: **`password`**

| Email | Role | Purpose |
|-------|------|---------|
| `admin@wdf.go.tz` | `super_admin` | Full system access |
| `ward.cdo@wdf.go.tz` | `cdo_ward` | Ward-level review (Step 1) |
| `council.cdo@wdf.go.tz` | `cdo_council` | Council-scoped visibility |
| `region.cdo@wdf.go.tz` | `cdo_region` | Region-scoped visibility |
| `ministry@wdf.go.tz` | `cdo_ministry` | Ministry review & amount proposal |
| `assdir@wdf.go.tz` | `assistant_director` | Step 5 |
| `director@wdf.go.tz` | `director` | Step 6 |
| `km@wdf.go.tz` | `km` | Knowledge Management approval (Step 7) |
| `chief@wdf.go.tz` | `chief` | Assign accountant (Step 8) |
| `accountant1@wdf.go.tz` | `accountant` | Disbursement (Step 9) |
| `accountant2@wdf.go.tz` | `accountant` | Second accountant |
| `test@example.com` | `applicant` | Sample applicant with profile |
| `applicant2@wdf.go.tz` … `applicant5@wdf.go.tz` | `applicant` | Additional sample applicants |

New users can self-register at `/register`; they receive the `applicant` role and are prompted to complete their profile.

---

## Application Modules

### Dashboard (`/dashboard`)

- Requires `view dashboard` permission
- Role-aware KPI cards (totals, pending, approved, disbursed amounts)
- Applicants see only their own loan stats
- Applications trend chart (lazy-loaded Chart.js)
- Pipeline bar chart (staff roles only)
- Recent applications table with Bootstrap-style status badges
- Stats cached ~45 seconds per user

### Applicants (`/applicants`)

- CRUD for applicant profiles (NIN, DOB, contact, location)
- Cascading geographic selectors (Region → District → Council → Ward → Street)
- Attach/detach loan group membership
- Search by name, NIN, phone, email
- Phone numbers normalized to Tanzania format (`255…` / `0…`)

### Loan Applications (`/loan-applications`)

- **Index** — submitted loans and saved drafts
- **Apply** — 7-step wizard with draft save/resume
- **Show** — full loan detail with workflow actions, approval history, guarantors
- Track ID format: `WL000001`, `WL000002`, …
- One active application per applicant (blocks duplicate pending loans)

### Loan Groups (`/loan-groups`)

- Create groups with registration number, contact info
- Assign applicants (one group per applicant enforced)
- View group members and linked loans

### Workflow & Tracking

- **Track** (`/track?track_id=WL000001`) — lookup loan by track ID (requires `view loan by track id`)
- **Workflow actions** (`POST /loans/{loan}/workflow`) — step-specific buttons on loan show page

### Repayments (`/repayments`)

- List loan payments scoped by role and geography

### Reports (`/reports`)

- Full KPI row, disbursement summary, multiple charts
- Paginated loan table with region column
- Requires `view reports` permission

### Administration (`/admin`)

| Path | Permission | Function |
|------|------------|----------|
| `/admin/users` | `manage users` | Create/edit users, assign roles, set geographic zones |
| `/admin/roles` | `manage roles` | List, create, edit permissions, delete custom roles |

**Role management rules:**

- **Protected roles** (`super_admin`, `applicant`) cannot be deleted
- **`super_admin` permissions** are locked and cannot be edited
- **Custom roles** can be created with any combination of permissions from `PermissionCatalog`
- Only a `super_admin` may assign the `super_admin` role to another user
- Permission UI is grouped (dashboard, applicants, loans, workflow, groups, finance, reports, administration) with sidebar menu hints

---

## Loan Approval Workflow

The system implements a **9-step pipeline**. Each loan has `current_step` (1–9) and a `status` field updated as actions are taken.

```
Step  Actor              Action (examples)
────  ─────────────────  ─────────────────────────────────────
 1    Ward CDO           receive → forward_ministry
 2    Ministry           propose_amount → send_to_applicant
 3    Applicant          accept_amount / decline_amount
 4    Ministry           forward_ass_dir
 5    Assistant Director forward_director
 6    Director           forward_km
 7    KM                 approve_km
 8    Chief              assign_accountant
 9    Accountant         disburse
```

### Workflow actions reference

| Action | Step | Role | Effect |
|--------|------|------|--------|
| `receive` | 1 | Ward CDO | Status → `received` |
| `forward_ministry` | 1 | Ward CDO | Step → 2 |
| `propose_amount` | 2 | Ministry | Sets `proposed_amount`, step → 3, status → `awaiting_applicant` |
| `send_to_applicant` | 2/4 | Ministry | Forwards to applicant confirmation |
| `accept_amount` | 3 | Applicant | Step → 4, continues review |
| `decline_amount` | 3 | Applicant | Step → 2, status → `declined_by_applicant` |
| `forward_ass_dir` | 4 | Ministry | Step → 5 |
| `forward_director` | 5 | Ass. Director | Step → 6 |
| `forward_km` | 6 | Director | Step → 7 |
| `approve_km` | 7 | KM | Step → 8, status → `approved` |
| `assign_accountant` | 8 | Chief | Sets `officer_id`, step → 9, status → `ready_for_disbursement` |
| `disburse` | 9 | Assigned accountant | Sets `disbursed_amount`, status → `disbursed` |

Every action creates an `ApprovalLevel` audit record and appends to the loan's `approval_history` JSON.

### Loan statuses

| Status | Meaning |
|--------|---------|
| `pending` | Newly submitted |
| `received` | Received by Ward CDO |
| `in_review` | Under review at a workflow step |
| `awaiting_applicant` | Waiting for applicant to accept proposed amount |
| `declined_by_applicant` | Applicant declined proposed amount |
| `approved` | Approved by KM |
| `ready_for_disbursement` | Accountant assigned |
| `disbursed` | Funds disbursed (terminal) |
| `rejected` | Rejected (terminal) |

---

## Roles & Permissions

Roles are seeded in `database/seeders/RolePermissionSeeder.php` using Spatie Permission. Permissions are catalogued in `app/Support/PermissionCatalog.php` (single source of truth for admin UI and validation).

### Built-in roles

| Role | Description |
|------|-------------|
| `super_admin` | All permissions; bypasses all gates via `Gate::before` |
| `admin` | User/role management, reports, applicants, groups (no workflow steps) |
| `applicant` | Own profile, create/view own loans, accept amounts |
| `cdo_ward` | Ward loans, receive & forward applications |
| `cdo_council` | Council-scoped loan visibility |
| `cdo_region` | Region-scoped loan visibility |
| `cdo_ministry` | Ministry review, propose amounts, forward |
| `assistant_director` | Step 5 review |
| `director` | Step 6 review |
| `km` | Step 7 approval |
| `chief` | Step 8 — assign accountant |
| `accountant` | Step 9 — disburse assigned loans |

### Permission groups

| Group | Example permissions |
|-------|---------------------|
| Dashboard | `view dashboard` |
| Applicants | `manage applicants`, `register applicant`, `view own profile` |
| Loans | `create loan application`, `view own loans`, `view ward/council/region/all loans` |
| Workflow | `receive application`, `propose loan amount`, `disburse loan`, … |
| Groups | `manage loan groups` |
| Finance | `view repayments` |
| Reports | `view reports` |
| Administration | `manage users`, `manage roles` |

### Authorization layers

1. **Route middleware** — e.g. `can:view dashboard`, `can:manage roles`, `can:view reports`
2. **Spatie permissions** — `$user->can('permission name')` in controllers and views
3. **`Gate::before`** — `super_admin` always passes
4. **`NavPermissions`** — sidebar menu items shown only when the user has the matching permission
5. **Global scopes** — query-level row filtering (`ApprovalLevelScope`, `ApplicantAccess`)
6. **`WorkflowAuthorizationService`** — step + action matrix for loan workflow buttons

Custom roles created in the admin UI inherit the same permission checks; assign them to users via **Admin → Users**.

---

## Data Scoping & Security

### Global scopes

| Scope | Model(s) | Behavior |
|-------|----------|----------|
| `ApprovalLevelScope` | `Loan` | Filters loans by role, workflow step, and geographic zone; applicants see only `user_id` matches |
| `ApplicantAccess` | `Applicant`, `LoanPayment` | Filters by user role and CDO zone; applicants scoped by `user_id` on their own profile |

> **Note:** `ApplicantAccess` handles the `Applicant` model first (direct `user_id` filter) before resolving related IDs, avoiding recursive queries when applicants load their profile or dashboard.

### Geographic zones

Staff CDO users have a **morphTo** `zoneable` relation on `User`:

- `cdo_ward` → `Ward`
- `cdo_council` → `Council`
- `cdo_region` → `Region`

Loans are scoped via `business_details` (region, council, ward IDs).

Authentication, permissions, gates, and workflow authorization are described in [Roles & Permissions](#roles--permissions).

---

## URL Hash IDs

Resource URLs use **Hashids** instead of raw numeric database IDs for `User`, `Applicant`, `Loan`, `LoanGroup`, and `Role` models (via `App\Models\Concerns\HasHashid`).

| Concern | Detail |
|---------|--------|
| Config | `config/hashids.php` — salt, length, alphabet |
| Service | `App\Services\HashidService` (singleton) |
| Helpers | `hashid_encode($id)`, `hashid_decode($hash)` |
| Route binding | `HasHashid::resolveRouteBinding()` decodes hashes automatically |
| Invalid hash | Returns 404 (numeric IDs in URLs are not accepted) |

Example: `/loan-applications/aB3xK9mQ` instead of `/loan-applications/12`.

Set a dedicated `HASHIDS_SALT` in production (do not rely on `APP_KEY` rotation).

---

## Architecture

The application follows a **thin controller, fat service** pattern:

```
HTTP Request
    ↓
Controller (authorize, validate via FormRequest, delegate)
    ↓
Service (business logic, transactions)
    ↓
Model (relationships, scopes, accessors)
    ↓
Database
```

### Services (`app/Services/`)

| Service | Responsibility |
|---------|----------------|
| `LoanApplicationService` | Submit applications, drafts, track ID, lookups |
| `LoanWorkflowService` | Workflow state transitions, audit logging |
| `WorkflowAuthorizationService` | Who can perform which workflow action |
| `LoanQueryService` | Paginated loan lists, show-page eager loads |
| `LoanTrackIdGenerator` | Generate `WL######` track IDs |
| `DraftLoanService` | Save/delete application drafts |
| `ApplicantService` | Applicant CRUD, search, group attach/detach |
| `LoanGroupService` | Group CRUD, eligible members |
| `UserProvisioningService` | Admin user create/update with roles & zones |
| `GeoHierarchyService` | Region→Street cascade (cached 1 hour) |
| `DashboardStatsService` | Cached dashboard/report statistics (applicant-scoped queries) |
| `RoleService` | Create/update/delete custom roles, sync permissions |
| `HashidService` | Encode/decode Hashids for URL route binding |

### Support classes (`app/Support/`)

| Class | Purpose |
|-------|---------|
| `WorkflowSteps` | Single source of truth for step numbers, labels, role maps |
| `NavPermissions` | Pre-computed sidebar visibility flags |
| `PermissionCatalog` | Grouped permission definitions for role admin UI |

### Model concerns (`app/Models/Concerns/`)

| Trait | Purpose |
|-------|---------|
| `HasDisplayName` | Build/display full names from NIDA name parts |
| `HasHashid` | Hashid encoding, route binding, `$model->hashid` accessor |
| `Searchable` | `scopeSearch()` for paginated listings |

### Validation rules (`app/Rules/`)

| Rule | Purpose |
|------|---------|
| `TanzaniaPhone` | Validate `255` / `0` mobile format |
| `ApplicantNotInAnyGroup` | Prevent duplicate group membership |

---

## Project Structure

```
WomenLoan/
├── app/
│   ├── Helpers/
│   │   ├── BadgeHelper.php             # loan_status_badge_variant(), active_status_badge_variant()
│   │   ├── CurrencyHelper.php          # format_tzs()
│   │   ├── HashidHelper.php            # hashid_encode(), hashid_decode()
│   │   └── LocaleHelper.php            # loan_status_label(), role_label(), permission_label(), …
│   ├── Http/
│   │   ├── Controllers/                # Thin HTTP layer
│   │   ├── Middleware/
│   │   │   └── SetLocale.php
│   │   └── Requests/                   # Form validation (incl. Admin/StoreRoleRequest)
│   ├── Models/
│   │   ├── Concerns/                   # HasDisplayName, HasHashid, Searchable
│   │   ├── Role.php                    # Extended Spatie role (protected/locked helpers)
│   │   └── Scopes/                     # ApprovalLevelScope, ApplicantAccess
│   ├── Providers/
│   │   └── AppServiceProvider.php      # Gate::before, view composers, HashidService
│   ├── Rules/
│   ├── Services/                       # Business logic
│   └── Support/                        # WorkflowSteps, NavPermissions, PermissionCatalog
├── config/
│   └── hashids.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── RolePermissionSeeder.php
│       ├── LocationSeeder.php          # 5 regions + geo hierarchy
│       ├── StaffUserSeeder.php
│       └── DummyDataSeeder.php         # Sample loans at each step
├── lang/
│   ├── en/                             # English (nav, dashboard, permissions, statuses, …)
│   └── sw/                             # Swahili translations
├── public/
│   └── build/                          # Vite production assets
├── resources/
│   ├── css/app.css                     # Tailwind 4, dark mode, app-card/app-table, Bootstrap badges
│   ├── js/
│   │   ├── app.js                      # Alpine.js bootstrap
│   │   ├── geo-cascade.js              # Shared location dropdown logic
│   │   └── pages/                      # Page-specific bundles (dashboard, reports)
│   └── views/
│       ├── layouts/                    # app, guest
│       ├── partials/                   # sidebar, badge, loan-status-badge, table-icon, flash-messages
│       ├── applicants/
│       ├── loan_applications/
│       ├── loan_groups/
│       ├── admin/
│       └── ...
├── routes/web.php
└── vite.config.js
```

---

## Database Schema

### Core entities

| Table | Description |
|-------|-------------|
| `users` | Auth users with `zoneable` morph for CDO geography |
| `applicants` | Applicant profiles linked to `users` and `streets` |
| `loans` | Loan applications with workflow state |
| `business_details` | Per-loan business info and geo FKs |
| `gurantors` | Loan guarantors |
| `approval_levels` | Workflow audit trail |
| `loan_groups` | Group lending entities |
| `applicant_loan_group` | Pivot: applicants ↔ groups |
| `loan_payments` | Repayment records |
| `draft_loans` | In-progress application drafts (`form_data` JSON) |

### Geographic hierarchy

```
regions
  └── districts
        └── councils
              └── wards
                    └── streets  ← applicant location_id
```

Sample regions seeded: **Dodoma**, **Dar es Salaam**, **Arusha**, **Mwanza**, **Mbeya**.

### Key loan columns

| Column | Type | Notes |
|--------|------|-------|
| `loan_track_id` | string | Unique, e.g. `WL000001` |
| `current_step` | int | 1–9 |
| `status` | string | Workflow status |
| `requested_amount` | decimal | Applicant request |
| `proposed_amount` | decimal | Ministry proposal |
| `disbursed_amount` | decimal | Final disbursement |
| `approval_history` | JSON | Action log |
| `officer_id` | FK users | Assigned accountant |

### Spatie permission tables

Created by `2026_06_29_203706_create_permission_tables.php`:

- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

---

## Routes & API Endpoints

### Public / guest

| Method | URI | Name |
|--------|-----|------|
| GET | `/login` | `login` |
| POST | `/login` | — |
| GET | `/register` | `register` |
| POST | `/register` | — |

### Authenticated

| Method | URI | Name | Middleware / notes |
|--------|-----|------|-------------------|
| GET | `/dashboard` | `dashboard` | `can:view dashboard` |
| GET | `/track` | `loans.track` | `can:view loan by track id` |
| POST | `/loans/{loan}/workflow` | `loans.workflow` | `{loan}` resolved via Hashid |
| Resource | `/applicants` | `applicants.*` | Hashid route keys |
| Resource | `/loan-groups` | `loan-groups.*` | `can:manage loan groups` |
| GET | `/loan-applications` | `loan-applications.index` | |
| GET | `/loan-applications/apply` | `loan-applications.create` | |
| POST | `/loan-applications/store` | `loan-applications.store` | |
| GET | `/loan-applications/{loan}` | `loan-applications.show` | Hashid |
| GET | `/repayments` | `repayments.index` | `can:view repayments` |
| GET | `/reports` | `reports.index` | `can:view reports` |
| Resource | `/admin/users` | `admin.users.*` | `can:manage users` (no show) |
| Resource | `/admin/roles` | `admin.roles.*` | `can:manage roles` (no show) |
| GET | `/locale/{locale}` | `locale.switch` | |

### Internal JSON API (auth required)

Base path: `/api/loans/`

| Method | URI | Name | Returns |
|--------|-----|------|---------|
| GET | `/districts/{regionId}` | `loans.api.districts` | Districts in region |
| GET | `/councils/{districtId}` | `loans.api.councils` | Councils in district |
| GET | `/wards/{councilId}` | `loans.api.wards` | Wards in council |
| GET | `/streets/{wardId}` | `loans.api.streets` | Streets in ward |
| GET | `/applicant/{nin}` | `loans.api.applicant` | Applicant by NIN |
| GET | `/group/{groupId}/members` | `loans.api.group-members` | Group members |

---

## Global Helpers

Autoloaded from `app/Helpers/` (see `composer.json` → `autoload.files`).

| Helper | File | Purpose |
|--------|------|---------|
| `format_tzs($amount)` | `CurrencyHelper.php` | Format as `TZS 1,234,567` |
| `hashid_encode($id)` / `hashid_decode($hash)` | `HashidHelper.php` | Encode/decode Hashids |
| `loan_status_label($status)` | `LocaleHelper.php` | Translated loan status label |
| `role_label($role)` | `LocaleHelper.php` | Translated role name |
| `permission_label($permission)` | `LocaleHelper.php` | Translated permission name |
| `workflow_action_label($action)` | `LocaleHelper.php` | Translated workflow action |
| `loan_type_label($type)` | `LocaleHelper.php` | Translated loan type |
| `loan_status_badge_variant($status)` | `BadgeHelper.php` | Bootstrap badge class for loan status |
| `active_status_badge_variant($bool)` | `BadgeHelper.php` | `success` / `danger` for user active state |

### Blade partials for badges

```blade
@include('partials.loan-status-badge', ['status' => $loan->status])

@include('partials.badge', ['variant' => 'primary', 'text' => 'Label'])
```

Variants: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `light`, `dark`.

**Loan status → badge variant mapping:**

| Status | Badge |
|--------|-------|
| `pending` | secondary |
| `received` | info |
| `in_review` | primary |
| `awaiting_applicant` | warning |
| `declined_by_applicant`, `rejected` | danger |
| `approved`, `ready_for_disbursement`, `disbursed` | success |

---

## Internationalization

- Locales: **English (`en`)** and **Swahili (`sw`)**
- Switch via header links or `/locale/en` / `/locale/sw`
- Translation files: `lang/en/` and `lang/sw/`
  - `nav.php` — navigation labels
  - `dashboard.php` — dashboard strings
  - `permissions.php` — permission names and group labels (role admin UI)
  - `roles.php` — role display names
  - `statuses.php` — loan status labels
  - `workflow.php` — workflow action labels
  - `messages.php` — flash messages
  - `auth.php`, `common.php`, `applicants.php`, `loans.php`, `groups.php`, `repayments.php`, `admin.php`, `geo.php`
- Middleware: `App\Http\Middleware\SetLocale`
- Currency displayed as **TZS** with comma separators via `format_tzs()` helper

---

## Frontend & UI

Built with **Vite**, **Tailwind CSS 4**, and **Alpine.js**. No external CDN dependencies — all JS, CSS, and fonts are self-hosted.

### Design system (`resources/css/app.css`)

| Class / component | Purpose |
|-------------------|---------|
| `.app-card`, `.app-card-header`, `.app-card-footer` | Unified card surfaces (light + dark) |
| `.app-table` | Dashboard-style tables with dark-mode hovers/borders |
| `.app-input`, `.app-label`, `.app-select`, `.app-textarea` | Form controls with dark-mode support |
| `.table-icon-btn` | Outline icon action buttons (view/edit/delete) |
| `.badge`, `.badge-*` | Bootstrap-style status/count badges |
| `.dark` variant | Dark theme (black base, charcoal elevation) |

Dark mode is toggled via the `admin_dark_mode` cookie and the `.dark` class on `<html>`.

### Entry points (`vite.config.js`)

| Entry | Loaded on |
|-------|-----------|
| `resources/css/app.css` | All pages (in `<head>`) |
| `resources/js/app.js` | All pages (before `</body>`) — Alpine.js |
| `resources/js/pages/dashboard.js` | Dashboard only — lazy Chart.js |
| `resources/js/pages/reports.js` | Reports only — lazy Chart.js |
| `resources/js/pages/geo-cascade.js` | Applicant create/edit, loan apply — location dropdowns |

### Bundle strategy

- **Alpine.js** and **Chart.js** are separate manual chunks
- Chart.js loads only when dashboard/reports pages request it (`import('chart.js/auto')`)
- CSS and JS are split: CSS in `<head>`, JS at end of `<body>` for faster first paint
- Production assets output to `public/build/` with 1-year cache headers (`.htaccess`)

### Build commands

```bash
npm run dev      # Development with HMR
npm run build    # Production build (run after CSS/JS changes)
```

After Blade or config changes in production, also run `php artisan view:clear` or `php artisan optimize:clear`.

---

## Performance Optimizations

| Area | Optimization |
|------|--------------|
| **Stats** | Dashboard metrics cached 45s per user; applicant queries scoped to own loans; flushed on workflow/submit |
| **Permissions** | Spatie permission cache; flushed when roles are created/updated/deleted |
| **Geography** | Region→street data cached 1 hour in `GeoHierarchyService` |
| **Queries** | Lean column selects on list pages; aggregate stats in single SQL |
| **Indexes** | `loans.status`, `current_step`, `created_at`, `officer_id`, `draft_loans.user_id`, etc. |
| **Sidebar** | Permission flags computed once via `NavPermissions` view composer |
| **Fonts** | Latin-only Inter (3 weights: 400, 600, 700) |
| **HTTP** | `AddLinkHeadersForPreloadedAssets` middleware for Vite preload hints |

---

## Development Commands

```bash
# Run tests
composer test
# or
php artisan test

# Clear caches (after role/permission or config changes)
php artisan optimize:clear
php artisan cache:clear

# Code style (Laravel Pint)
./vendor/bin/pint

# Fresh database with sample data
php artisan migrate:fresh --seed

# List routes
php artisan route:list

# Tinker
php artisan tinker
```

---

## Seeding & Sample Data

`php artisan db:seed` runs in order:

1. **RolePermissionSeeder** — roles, permissions, assignments
2. **LocationSeeder** — 5 regions with full geo hierarchy
3. **StaffUserSeeder** — all workflow actor accounts
4. **DummyDataSeeder** — 5 applicants, 2 loan groups, 11 loans (`WL000001`–`WL000011`, one per workflow step), guarantors, approval history, 1 repayment, 1 draft

Sample loan track IDs after seeding:

| Track ID | Step | Status |
|----------|------|--------|
| `WL000001` | 1 | Pending (Ward) |
| `WL000002` | 2 | In review (Ministry) |
| `WL000003` | 3 | Awaiting applicant |
| … | … | … |
| `WL000009` | 9 | Ready for disbursement |
| `WL000010` | 9 | Disbursed |
| `WL000011` | — | Draft only |

---

## Deployment

### Pre-deploy checklist

1. Set `APP_DEBUG=false` and `APP_URL` to production domain
2. Configure MySQL connection in `.env`
3. Use Redis for `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`
4. Run migrations: `php artisan migrate --force`
5. Build assets: `npm run build`
6. Link storage: `php artisan storage:link`
7. Cache config/routes/views:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
8. Set correct file permissions on `storage/` and `bootstrap/cache/`
9. Configure web server document root to `public/`

### Apache

The included `public/.htaccess` handles URL rewriting and sets long-cache headers for built assets (`*.js`, `*.css`, `*.woff2`).

### Queue worker

If using database/redis queues in production:

```bash
php artisan queue:work --tries=3
```

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
