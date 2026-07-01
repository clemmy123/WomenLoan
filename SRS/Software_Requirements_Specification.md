# Software Requirements Specification (SRS)

## Women Development Fund — Loan Management Platform

| Field | Value |
|-------|-------|
| **Document title** | Software Requirements Specification |
| **System name** | WDF Loan Management Platform |
| **Codebase** | WomenLoan |
| **Version** | 1.0 |
| **Date** | June 2026 |
| **Organization** | Women Development Fund (WDF) |
| **Ministry** | Ministry of Community Development, Gender, Women and Special Groups (MoCGWSG), Tanzania |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [System Architecture](#3-system-architecture)
4. [Project Folder Structure](#4-project-folder-structure)
5. [User Roles and Permissions](#5-user-roles-and-permissions)
6. [Functional Requirements](#6-functional-requirements)
7. [Loan Approval Workflow](#7-loan-approval-workflow)
8. [Repayment and Interest Rules](#8-repayment-and-interest-rules)
9. [Data Model](#9-data-model)
10. [Routes and Interfaces](#10-routes-and-interfaces)
11. [Non-Functional Requirements](#11-non-functional-requirements)
12. [Configuration and Deployment](#12-configuration-and-deployment)
13. [Appendices](#13-appendices)

---

## 1. Introduction

### 1.1 Purpose

This document specifies the functional and non-functional requirements for the WDF Loan Management Platform. It is intended for developers, testers, ministry stakeholders, and system administrators who need to understand what the system does, how it is structured, and what business rules it enforces.

### 1.2 Scope

The system is a **web-based monolithic application** that supports:

- Applicant registration and profile management (NIDA-style data)
- Individual and group loan applications via a multi-step wizard
- A **9-step approval workflow** from ward CDO to accountant disbursement
- One-time applicant group registration for group lending
- Repayment scheduling with **16% flat interest** and payment recording
- Role-based dashboards, filtered reports, and Excel/PDF export
- User and role administration with geographic scoping for CDO staff
- Bilingual user interface (English and Swahili)

The system does **not** include external payment gateway integration, mobile apps, or SMS notifications in the current release.

### 1.3 Definitions and Acronyms

| Term | Definition |
|------|------------|
| WDF | Women Development Fund |
| MoCGWSG | Ministry of Community Development, Gender, Women and Special Groups |
| CDO | Community Development Officer |
| NIN / NIDA | National Identification Number (Tanzania) |
| KM | Knowledge Management (approval role) |
| TZS | Tanzanian Shilling |
| RBAC | Role-Based Access Control |
| SRS | Software Requirements Specification |

### 1.4 References

- Laravel 12 documentation: https://laravel.com/docs/12.x
- Spatie Laravel Permission: https://github.com/spatie/laravel-permission
- Project README: `README.md` (developer setup guide)

---

## 2. Overall Description

### 2.1 Product Perspective

The platform is a self-contained Laravel application served over HTTP. All business logic runs server-side. The frontend uses server-rendered Blade templates enhanced with Alpine.js for interactive forms (loan wizard, workflow modals, report filters, group member management).

### 2.2 Product Functions (Summary)

| Module | Primary users | Key functions |
|--------|---------------|---------------|
| Authentication | All | Login, register, logout, session management |
| Applicants | Applicants, admins | Profile CRUD, geographic location |
| Loan applications | Applicants | 7-step wizard, drafts, edit pending, submit |
| Workflow | CDOs, ministry, executives, accountant | Receive, review, propose, approve, disburse, rollback |
| My Group | Applicants (group loans) | One-time group setup, member view/edit/remove |
| Loan groups (admin) | Admins | CRUD groups, assign applicants |
| Repayments | Applicants, staff | Schedule view, pay-here with collection account |
| Reports | Ministry, CDOs, executives | Filters, charts, Excel/PDF export |
| Dashboard | All authorized roles | KPIs, trends, pipeline (role-scoped) |
| Administration | Admins | Users, roles, permissions, geographic zones |

### 2.3 User Classes

| User class | Description |
|------------|-------------|
| **Applicant** | Woman entrepreneur applying for WDF loan; manages own profile and applications |
| **Ward CDO** | Receives applications at ward level; forwards to ministry |
| **Council / Region CDO** | Geographic visibility for monitoring (council/region scope) |
| **Ministry CDO** | Reviews applications, proposes loan amounts, forwards up the chain |
| **Assistant Director** | Step 5 reviewer |
| **Director** | Step 6 reviewer |
| **KM** | Step 7 final approver before chief |
| **Chief** | Assigns accountant for disbursement |
| **Accountant** | Disburses assigned loans; records repayments |
| **Admin / Super Admin** | System configuration, users, roles, applicants, groups |

### 2.4 Operating Environment

| Component | Requirement |
|-----------|-------------|
| Server | PHP 8.2+, web server (Apache/Nginx) or `php artisan serve` |
| Database | MySQL 8+ (production) or SQLite (development) |
| Browser | Modern evergreen browsers (Chrome, Firefox, Edge) |
| Node.js | 18+ (build-time only, for Vite assets) |

### 2.5 Design Constraints

- Session-based authentication (no public REST API for third parties)
- Document uploads stored on local `public` disk
- Currency displayed as **TZS** with thousand separators
- Resource URLs use **Hashids** instead of raw numeric IDs
- All authorization enforced server-side (middleware, policies, global scopes)

---

## 3. System Architecture

### 3.1 Architectural Pattern

```
Browser (Blade + Alpine.js + Tailwind)
        │
        ▼
Routes (web.php) ──► Middleware (auth, can:*, SetLocale)
        │
        ▼
Controllers (thin) ──► Form Requests (validation)
        │
        ▼
Services (business logic, transactions)
        │
        ▼
Models (Eloquent + Global Scopes)
        │
        ▼
MySQL / SQLite
```

### 3.2 Key Services

| Service | Responsibility |
|---------|----------------|
| `LoanApplicationService` | Create, update, draft, finalize loan applications |
| `LoanWorkflowService` | Workflow actions, step transitions, rollback, disbursement |
| `WorkflowAuthorizationService` | Who may perform which workflow action |
| `LoanQueryService` | Paginated lists, show-page eager loading, active-loan check |
| `ApplicantGroupService` | One-time group setup, member CRUD |
| `RepaymentScheduleService` | 16% interest schedule, payment recording |
| `ReportService` | Filtered reports, charts, export data |
| `DashboardStatsService` | Cached dashboard KPIs and charts |
| `GeoHierarchyService` | Region → street cascade API |

### 3.3 Global Scopes (Data Access)

| Scope | Model | Behavior |
|-------|-------|----------|
| `ApprovalLevelScope` | `Loan` | Filters loans by role, workflow step, geographic zone, or `view all loans` |
| `ApplicantAccess` | `Applicant`, `LoanPayment`, `LoanGroup` | Filters by applicant ownership or staff geographic zone |

### 3.4 Technology Stack

| Layer | Technology | Version |
|-------|------------|---------|
| Language | PHP | ^8.2 |
| Framework | Laravel | ^12.0 |
| RBAC | spatie/laravel-permission | ^6.25 |
| ID encoding | hashids/hashids | ^5.0 |
| Excel export | maatwebsite/excel | ^3.1 |
| PDF export | barryvdh/laravel-dompdf | ^3.1 |
| CSS | Tailwind CSS | ^4.0 |
| JS | Alpine.js | ^3.14 |
| Charts | Chart.js | ^4.4 |
| Build | Vite | ^7.0 |

---

## 4. Project Folder Structure

```
WomenLoan/
├── SRS/                              # System documentation (this folder)
│   ├── README.md
│   └── Software_Requirements_Specification.md
├── app/
│   ├── Exports/                      # Excel export classes (ReportsExport)
│   ├── Helpers/                      # Global helpers (currency, hashid, workflow)
│   ├── Http/
│   │   ├── Controllers/              # HTTP controllers
│   │   ├── Middleware/               # SetLocale
│   │   └── Requests/                 # Form validation
│   ├── Models/                       # Eloquent models
│   │   ├── Concerns/                 # HasHashid, Searchable, etc.
│   │   └── Scopes/                   # ApprovalLevelScope, ApplicantAccess
│   ├── Providers/                    # AppServiceProvider
│   ├── Rules/                        # Custom validation rules
│   ├── Services/                     # Business logic layer
│   └── Support/                      # WorkflowSteps, PermissionCatalog
├── bootstrap/                        # Laravel bootstrap
├── config/                           # App, database, wdf, hashids, permission
├── database/
│   ├── migrations/                   # Schema migrations
│   ├── seeders/                      # Roles, geography, staff, sample data
│   └── factories/
├── lang/
│   ├── en/                           # English translations
│   └── sw/                           # Swahili translations
├── public/                           # Web root, images, compiled assets
├── resources/
│   ├── css/app.css                   # Tailwind + design system
│   ├── js/
│   │   ├── app.js                    # Alpine bootstrap
│   │   └── pages/                    # dashboard, reports, loan-wizard, geo-cascade
│   └── views/
│       ├── layouts/                  # app, guest
│       ├── partials/                 # sidebar, modals, badges
│       ├── auth/                     # login, register
│       ├── applicants/
│       ├── loan_applications/        # wizard, show, workflow forms
│       ├── loan_groups/              # admin group CRUD
│       ├── my_group/                 # applicant group setup & show
│       ├── repayments/
│       ├── reports/
│       └── admin/                    # users, roles
├── routes/
│   └── web.php                       # All web routes
├── storage/                          # Logs, cache, uploads
├── tests/
│   ├── Feature/                      # Integration tests
│   └── Unit/
├── README.md                         # Developer documentation
├── composer.json
├── package.json
└── vite.config.js
```

---

## 5. User Roles and Permissions

### 5.1 Built-in Roles

| Role | Workflow step(s) | Geographic scope |
|------|------------------|------------------|
| `super_admin` | All (bypass) | None |
| `admin` | None | None |
| `applicant` | 3 (accept/decline) | Own data only |
| `cdo_ward` | 1 | Ward |
| `cdo_council` | — | Council |
| `cdo_region` | — | Region |
| `cdo_ministry` | 2, 4 | National (`view all loans`) |
| `assistant_director` | 5 | National |
| `director` | 6 | National |
| `km` | 7 | National |
| `chief` | 8 | National |
| `accountant` | 9 | Assigned loans only |

### 5.2 Permission List

| Category | Permissions |
|----------|-------------|
| Dashboard | `view dashboard` |
| Applicants | `manage applicants`, `register applicant`, `view own profile` |
| Loans | `create loan application`, `view own loans`, `edit pending loan`, `accept loan amount`, `view loan by track id`, `view ward/council/region/all loans` |
| Workflow | `receive application`, `review ward application`, `forward to ministry`, `review ministry application`, `propose loan amount`, `send to applicant confirmation`, `forward to assistant director`, `comment as assistant director`, `forward to director`, `comment as director`, `forward to km`, `approve as km`, `assign accountant`, `disburse loan`, `rollback workflow step` |
| Finance | `view repayments`, `record repayment` |
| Reports | `view reports` |
| Admin | `manage users`, `manage roles`, `manage loan groups` |

### 5.3 Authorization Layers

1. **Route middleware** — `can:permission` on protected routes
2. **Controller** — `$this->authorize()` calls
3. **WorkflowAuthorizationService** — step + action matrix
4. **Eloquent global scopes** — automatic query filtering
5. **Gate::before** — `super_admin` bypasses all checks

---

## 6. Functional Requirements

### 6.1 Authentication (FR-AUTH)

| ID | Requirement |
|----|-------------|
| FR-AUTH-01 | Users shall register with name, email, and password; default role is `applicant`. |
| FR-AUTH-02 | Users shall log in with email and password via session. |
| FR-AUTH-03 | Inactive users (`is_active = false`) shall be denied login. |
| FR-AUTH-04 | Users shall log out and destroy session. |
| FR-AUTH-05 | Unauthenticated users shall be redirected to `/login`. |

### 6.2 Applicant Profile (FR-APP)

| ID | Requirement |
|----|-------------|
| FR-APP-01 | Applicants shall complete a profile with NIN, names, DOB, sex, marital status, phone, email, and street-level location. |
| FR-APP-02 | NIN shall be unique across applicants. |
| FR-APP-03 | Phone numbers shall be validated (Tanzania format) and normalized. |
| FR-APP-04 | Staff with `manage applicants` shall CRUD applicant records subject to `ApplicantAccess` scope. |
| FR-APP-05 | Applicants shall attach/detach admin-managed loan groups (one group per applicant). |

### 6.3 Loan Application (FR-LOAN)

| ID | Requirement |
|----|-------------|
| FR-LOAN-01 | Applicants shall apply via a **7-step wizard**: loan type → business → applicant info → guarantor → amount → bank → declaration. |
| FR-LOAN-02 | Loan types shall be `individual` or `group`. |
| FR-LOAN-03 | Applicants shall save and resume drafts (`draft_loans` table). |
| FR-LOAN-04 | System shall assign track ID format `WL` + 6-digit number (e.g. `WL000001`). |
| FR-LOAN-05 | Applicants shall not start a new application while an active (non-terminal) loan exists. |
| FR-LOAN-06 | Applicants may edit applications only at workflow step 1 with status `pending` or `received`. |
| FR-LOAN-07 | Individual loans require application letter and bank statement on new submission. |
| FR-LOAN-08 | Group loans require group constitution, muhtasari, certificate, application letter, and bank statement. |
| FR-LOAN-09 | Business proposal document required on new submission (PDF/DOC/DOCX, max 5 MB). |
| FR-LOAN-10 | `has_disability` and `is_widowed` flags required on application. |

### 6.4 Group Lending (FR-GRP)

| ID | Requirement |
|----|-------------|
| FR-GRP-01 | Applicants shall register their group **once** via `/my-group/setup`. |
| FR-GRP-02 | Group setup captures group details, leader (applicant), and additional members. |
| FR-GRP-03 | After setup, group creator may **view, add, edit, and remove** members (leader cannot be removed). |
| FR-GRP-04 | Group member NIN shall be unique within the group. |
| FR-GRP-05 | Group loans shall use the applicant's registered group only. |
| FR-GRP-06 | Admins may manage loan groups and assign applicants via `/loan-groups`. |

### 6.5 Workflow (FR-WF)

| ID | Requirement |
|----|-------------|
| FR-WF-01 | Loans shall progress through a **9-step approval pipeline** (see Section 7). |
| FR-WF-02 | Each workflow action shall be logged in `approval_levels` and `loans.approval_history`. |
| FR-WF-03 | Ministry shall propose loan amount and send to applicant for acceptance (step 3). |
| FR-WF-04 | Applicant may accept (→ step 4) or decline (→ step 2) proposed amount. |
| FR-WF-05 | Chief shall assign a specific accountant (`officer_id`) before disbursement. |
| FR-WF-06 | Only the assigned accountant may disburse at step 9. |
| FR-WF-07 | Authorized reviewers may **rollback** to the previous step with mandatory reason (not after disbursement). |
| FR-WF-08 | Staff with `view all loans` shall retain visibility of loans after forwarding (no false "data loss"). |
| FR-WF-09 | Users may track loans by track ID via `/track`. |

### 6.6 Repayments (FR-REP)

| ID | Requirement |
|----|-------------|
| FR-REP-01 | Disbursement shall auto-create a repayment schedule with **16% interest** (see Section 8). |
| FR-REP-02 | Users with `view repayments` shall list and view repayment schedules. |
| FR-REP-03 | Repayment detail page shall display WDF **collection bank account** (configurable). |
| FR-REP-04 | Applicants and accountants may record payments (`record repayment`); outstanding balance updates immediately. |
| FR-REP-05 | Payment amount shall not exceed outstanding debt. |

### 6.7 Reports (FR-RPT)

| ID | Requirement |
|----|-------------|
| FR-RPT-01 | Users with `view reports` shall filter disbursed loans by period, date range, geography, loan type, age, disability, and widowed status. |
| FR-RPT-02 | Reports shall show KPIs, charts (trend, region, type, disability, widowed, age), and detail table. |
| FR-RPT-03 | Reports shall export to **Excel** and **PDF** preserving active filters. |
| FR-RPT-04 | Report data shall respect role-based scoping (`ApprovalLevelScope`). |

### 6.8 Dashboard (FR-DASH)

| ID | Requirement |
|----|-------------|
| FR-DASH-01 | Authorized users shall view role-scoped KPIs and charts on `/dashboard`. |
| FR-DASH-02 | Dashboard statistics shall be cached (~45 seconds per user). |

### 6.9 Administration (FR-ADM)

| ID | Requirement |
|----|-------------|
| FR-ADM-01 | Admins shall CRUD users with role assignment and geographic zone (`zoneable`). |
| FR-ADM-02 | Admins shall CRUD roles and assign grouped permissions. |
| FR-ADM-03 | System roles `super_admin` and `applicant` shall be protected from deletion. |
| FR-ADM-04 | Only `super_admin` may assign `super_admin` role to others. |

### 6.10 Internationalization (FR-I18N)

| ID | Requirement |
|----|-------------|
| FR-I18N-01 | UI shall support **English** and **Swahili** via `/locale/{locale}`. |
| FR-I18N-02 | Locale preference shall persist for the session. |

---

## 7. Loan Approval Workflow

### 7.1 Pipeline Overview

```
Step 1  Ward CDO        → receive, forward to ministry
Step 2  Ministry        → propose amount → applicant
Step 3  Applicant       → accept or decline amount
Step 4  Ministry        → forward to Assistant Director
Step 5  Ass. Director   → forward to Director
Step 6  Director        → forward to KM
Step 7  KM              → approve
Step 8  Chief            → assign accountant
Step 9  Accountant      → disburse
```

### 7.2 Workflow Actions

| Action | From step | To step | Status change |
|--------|-----------|---------|---------------|
| `receive` | 1 | 1 | `pending` → `received` |
| `forward_ministry` | 1 | 2 | → `in_review` |
| `propose_amount` | 2 | 3 | → `awaiting_applicant` |
| `accept_amount` | 3 | 4 | `applicant_acceptance` = accepted |
| `decline_amount` | 3 | 2 | → `declined_by_applicant` |
| `forward_ass_dir` | 4 | 5 | `in_review` |
| `forward_director` | 5 | 6 | `in_review` |
| `forward_km` | 6 | 7 | `in_review` |
| `approve_km` | 7 | 8 | → `approved` |
| `assign_accountant` | 8 | 9 | → `ready_for_disbursement` |
| `disburse` | 9 | 9 | → `disbursed` |
| `rollback_step` | 2–9 | previous | per rollback map |

### 7.3 Loan Statuses

`pending`, `received`, `in_review`, `awaiting_applicant`, `declined_by_applicant`, `approved`, `ready_for_disbursement`, `disbursed`, `rejected`

**Terminal statuses** (allow new application): `disbursed`, `declined_by_applicant`, `rejected`

---

## 8. Repayment and Interest Rules

### 8.1 Interest Calculation

| Parameter | Value | Config key |
|-----------|-------|------------|
| Interest rate | **16%** flat on principal | `config('wdf.interest_rate')` = 0.16 |
| Repayment term | **12 months** (default) | `WDF_REPAYMENT_TERM_MONTHS` |
| Payment interval | Monthly | `loan_payments.payment_interval` |

**Formulas:**

```
interest_amount     = amount_disbursed × 0.16
total_payable       = amount_disbursed + interest_amount
monthly_installment = total_payable ÷ term_months
outstanding_debt    = total_payable − amount_paid
```

### 8.2 Collection Account (Pay Here)

Displayed on repayment detail page. Configurable via `.env`:

| Variable | Default |
|----------|---------|
| `WDF_REPAYMENT_BANK` | CRDB Bank |
| `WDF_REPAYMENT_ACCOUNT` | 0150001234567 |
| `WDF_REPAYMENT_ACCOUNT_NAME` | Women Development Fund |

### 8.3 Payment Recording

- User transfers funds to collection account externally
- User records payment in system with amount and optional reference
- System updates `amount_paid`, `outstanding_debt`, installment schedule, and payment transaction log

---

## 9. Data Model

### 9.1 Entity Relationship (Core)

```
users ────────────── applicants ────── applicant_loan_group ────── loan_groups
  │                      │                                         │
  │                      │                                         ├── loan_group_members
  │                      └────────── loans ─────────────────────────┘
  │                                    │
  │                    ┌───────────────┼───────────────┐
  │                    │               │               │
  │            business_details   gurantors    approval_levels
  │                    │                               │
  │              regions → districts → councils        │
  │                         → wards → streets         │
  │                                                    │
  └──────────────────────────────────────── loan_payments
```

### 9.2 Primary Tables

| Table | Description |
|-------|-------------|
| `users` | Authentication, roles, geographic zone (`zoneable`) |
| `applicants` | Applicant profiles (NIN, names, DOB, location) |
| `loans` | Loan applications (amounts, status, step, flags, history) |
| `business_details` | Per-loan business info, geo FKs, document paths |
| `gurantors` | Loan guarantors (note: table name spelling in schema) |
| `approval_levels` | Workflow audit trail |
| `loan_groups` | Group entities |
| `loan_group_members` | Members registered under applicant groups |
| `applicant_loan_group` | Pivot: applicants ↔ groups |
| `loan_payments` | Repayment schedules and payment history (JSON) |
| `draft_loans` | Saved wizard drafts (JSON `form_data`) |
| `regions`, `districts`, `councils`, `wards`, `streets` | Geographic hierarchy |
| `roles`, `permissions`, `model_has_*`, `role_has_*` | Spatie RBAC |

### 9.3 Key Loan Fields

| Field | Description |
|-------|-------------|
| `loan_track_id` | Public tracking ID (WL000001) |
| `loan_type` | `individual` or `group` |
| `has_disability` | Applicant disability flag |
| `is_widowed` | Widow/widower (wajane) flag |
| `requested_amount` | Amount requested by applicant |
| `proposed_amount` | Amount proposed by ministry |
| `disbursed_amount` | Final disbursed amount |
| `current_step` | Workflow step (1–9) |
| `status` | Loan status |
| `officer_id` | Assigned accountant user ID |
| `approval_history` | JSON log of actions |

---

## 10. Routes and Interfaces

### 10.1 Public Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/login` | `login` |
| POST | `/login` | — |
| GET | `/register` | `register` |
| POST | `/register` | — |
| GET | `/locale/{locale}` | `locale.switch` |

### 10.2 Authenticated Routes (Summary)

| Module | Base path | Permission |
|--------|-----------|------------|
| Dashboard | `/dashboard` | `view dashboard` |
| Track loan | `/track` | `view loan by track id` |
| Workflow | `POST /loans/{loan}/workflow` | per action |
| Applicants | `/applicants` | various |
| Loan groups (admin) | `/loan-groups` | `manage loan groups` |
| My group | `/my-group` | `create loan application` |
| Loan applications | `/loan-applications` | applicant / scoped |
| Repayments | `/repayments` | `view repayments` |
| Reports | `/reports` | `view reports` |
| Admin users | `/admin/users` | `manage users` |
| Admin roles | `/admin/roles` | `manage roles` |

### 10.3 Internal JSON API (Authenticated)

Base: `/api/loans/`

| Endpoint | Purpose |
|----------|---------|
| `GET /districts/{regionId}` | Geo cascade |
| `GET /councils/{districtId}` | Geo cascade |
| `GET /wards/{councilId}` | Geo cascade |
| `GET /streets/{wardId}` | Geo cascade |
| `GET /applicant/{nin}` | Applicant lookup |
| `GET /group/{groupId}/members` | Group members for wizard |

### 10.4 User Interface

- Server-rendered Blade templates
- Alpine.js for interactivity (modals, wizard, filters)
- Tailwind CSS 4 design system with dark mode toggle
- Chart.js for dashboard and reports (lazy-loaded)
- No CDN dependencies; assets built via Vite

---

## 11. Non-Functional Requirements

### 11.1 Security

| ID | Requirement |
|----|-------------|
| NFR-SEC-01 | All mutating routes protected by CSRF tokens. |
| NFR-SEC-02 | Passwords hashed with bcrypt. |
| NFR-SEC-03 | Resource IDs obfuscated via Hashids in URLs. |
| NFR-SEC-04 | Row-level security via Eloquent global scopes. |
| NFR-SEC-05 | `super_admin` bypass logged via standard permission system. |
| NFR-SEC-06 | Uploaded files restricted by MIME type and size (5 MB). |

### 11.2 Performance

| ID | Requirement |
|----|-------------|
| NFR-PERF-01 | Dashboard stats cached per user (~45 s TTL). |
| NFR-PERF-02 | Database indexes on frequently queried columns. |
| NFR-PERF-03 | Eager loading on show pages to avoid N+1 queries. |
| NFR-PERF-04 | Chart.js loaded only on dashboard/reports pages. |

### 11.3 Usability

| ID | Requirement |
|----|-------------|
| NFR-UX-01 | Bilingual UI (EN/SW). |
| NFR-UX-02 | Dark mode support with persisted preference. |
| NFR-UX-03 | Responsive layout (mobile sidebar, card layouts on small screens). |
| NFR-UX-04 | Flash messages for success/error feedback. |
| NFR-UX-05 | TZS currency formatting throughout. |

### 11.4 Reliability

| ID | Requirement |
|----|-------------|
| NFR-REL-01 | Workflow actions wrapped in database transactions. |
| NFR-REL-02 | Approval history dual-written (relational + JSON). |
| NFR-REL-03 | Health check endpoint at `/up` (Laravel 12). |

### 11.5 Maintainability

| ID | Requirement |
|----|-------------|
| NFR-MNT-01 | Business logic in service classes, not controllers. |
| NFR-MNT-02 | Validation in dedicated Form Request classes. |
| NFR-MNT-03 | Feature tests for workflow, loans, groups, repayments, reports. |
| NFR-MNT-04 | Translation files organized by domain (`lang/en/*.php`). |

---

## 12. Configuration and Deployment

### 12.1 Environment Variables

| Variable | Purpose |
|----------|---------|
| `APP_NAME`, `APP_URL`, `APP_DEBUG` | Application identity |
| `APP_LOCALE` | Default locale (`en`) |
| `DB_*` | Database connection |
| `HASHIDS_SALT`, `HASHIDS_LENGTH` | URL ID encoding |
| `WDF_REPAYMENT_BANK` | Collection bank name |
| `WDF_REPAYMENT_ACCOUNT` | Collection account number |
| `WDF_REPAYMENT_ACCOUNT_NAME` | Account holder name |
| `WDF_REPAYMENT_TERM_MONTHS` | Loan term in months (default 12) |

### 12.2 Installation (Summary)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
php artisan serve
```

### 12.3 Seeded Test Accounts

All seeded users use password: **`password`**

| Email | Role |
|-------|------|
| `test@example.com` | Applicant |
| `ward.cdo@wdf.go.tz` | Ward CDO |
| `ministry@wdf.go.tz` | Ministry CDO |
| `assdir@wdf.go.tz` | Assistant Director |
| `director@wdf.go.tz` | Director |
| `km@wdf.go.tz` | KM |
| `chief@wdf.go.tz` | Chief |
| `accountant1@wdf.go.tz` | Accountant |
| `admin@wdf.go.tz` | Admin |

Sample loans `WL000001`–`WL000011` cover each workflow step.

### 12.4 Production Recommendations

```env
APP_DEBUG=false
LOG_LEVEL=warning
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Run `php artisan config:cache`, `route:cache`, `view:cache` after deployment.

---

## 13. Appendices

### Appendix A — 7-Step Application Wizard vs 9-Step Approval

| Concept | Steps | Purpose |
|---------|-------|---------|
| **Application wizard** | 7 UI steps | Data collection by applicant |
| **Approval workflow** | 9 backend steps | Review and disbursement by staff |

These are independent: the wizard completes at submission (step 1 of workflow); approval steps 1–9 follow.

### Appendix B — Document Upload Paths

| Document | Storage path (public disk) |
|----------|---------------------------|
| Business proposal | `proposals/` |
| Application letter | `application-letters/` |
| Bank statement | `bank-statements/` |
| Group constitution | `group-documents/` |
| Group muhtasari | `group-documents/` |
| Group certificate | `group-documents/` |
| Workflow attachments | per `approval_levels.attachment_path` |

### Appendix C — Test Coverage (Feature Tests)

| Test file | Coverage |
|-----------|----------|
| `WorkflowTest` | Full 9-step pipeline, rollback, authorization |
| `LoanSubmissionTest` | Submit individual/group with documents |
| `LoanEditTest` | Edit pending/received applications |
| `LoanDraftTest` | Save and resume drafts |
| `LoanApplicationAccessTest` | Active loan blocking |
| `GroupSetupTest` | Group setup, member CRUD |
| `RepaymentTest` | Schedule creation, payment recording |
| `ReportFilterTest` | Filters, Excel/PDF export |
| `DashboardAndAccessTest` | Dashboard, access control |

Run: `php artisan test`

### Appendix D — Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | June 2026 | WDF IT | Initial SRS — full system as implemented |

---

*End of Software Requirements Specification*
