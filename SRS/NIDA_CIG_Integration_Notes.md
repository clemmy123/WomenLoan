# NIDA CIG Integration — Conversation Handoff

**Date:** 2026-07-14  
**Status:** Demo (fake) UI ready; live CIG pending Friday workshop + network/VPN/certs  
**Method:** `RQVerification` (demographic security questions)

---

## Goal (WDF)

During **create account**:

1. Applicant enters **NIN** (20 digits)
2. Answers NIDA demographic questions (one-by-one)
3. After **2 correct** answers, pull and show:
   - Photo, first / middle / last name
   - Sex (**Female** only — WDF policy; Male → reject)
   - NIN, DOB, age (computed from DOB), nationality
4. Then collect email / phone / password and register

---

## CIG facts (from TZ_NID_CIG_TECH_API_DOCUMENT)

### Flow — `RQVerification`
- **Step 1 payload:** `<Payload><NIN>…</NIN></Payload>` → first question + `RQCode`
- **Step 2+ payload:** `NIN` + `RQCode` + `QNANSW`
- Check `PREV_ANSW_CODE`: **123** = correct, **124** = incorrect
- When correct answers = **2** → demographic data in response payload
- Status **122** = attempts limit reached

### Response person fields (map to Applicant)
| CIG tag | WDF |
|---------|-----|
| `NIN` / `NATIONALIDNUMBER` | `nin` |
| `FIRSTNAME` | `first_name` |
| `MIDDLENAME` | `middle_name` |
| `LASTNAME` / sample `SURNAME` | `last_name` |
| `SEX` (MALE/FEMALE) | `sex` → `Female` |
| `DATEOFBIRTH` (YYYY-MM-DD) | `dob` → age |
| `NATIONALITY` | `nationality` |
| `PHOTO` (JPEG Base64) | `photo_path` |
| `SIGNATURE` (optional) | `signature_path` |

Note: table vs sample XML tag names can differ — use flexible mapping.

### Security (live)
- Access via **VPN** (granted by NIDA)
- Certificates (4):
  1. `NIDACA` (Root CA)
  2. `NIDASubCA` (Sub CA)
  3. **Stakeholder** cert (CSR → NIDA signs → `.p12` / Personal)
  4. `NIDACIGSecurity` (Message Security)
- Request: AES-256 payload (random Key/IV each time) → RSA-encrypt Key/IV with Message Security public key → sign with Stakeholder private key (RSASSA-PKCS1-v1_5 + **SHA1**)
- Response: verify signature → decrypt Key/IV with Stakeholder private key → AES decrypt payload
- Hosts file: map CIG IP → hostname (example in doc: `41.59.254.115 nacer01`)
- Header includes **UserID** (stakeholder account)

### What to request from NIDA (Friday / after network)
1. VPN access (test)
2. CIG hostname + IP / base URL
3. All 4 certificates (+ how to submit CSR)
4. Stakeholder **UserID**
5. Permission for method **`RQVerification`**
6. Test NINs
7. Later: production URL/certs

---

## What is already in this codebase

### Demo UI
- Register wizard: NIN → questions → identity preview → account fields
- Fake answers: **Asha**, then **Dodoma**
- Any valid 20-digit NIN works in fake mode
- Routes: `POST /api/nida/start`, `POST /api/nida/answer`

### Key files
| Path | Role |
|------|------|
| `app/Services/Nida/FakeNidaClient.php` | Local RQVerification stub |
| `app/Services/Nida/HttpNidaClient.php` | **Live CIG — implement here** |
| `app/Services/Nida/NidaService.php` | App orchestration (keep) |
| `app/Http/Controllers/NidaController.php` | API (keep) |
| `resources/views/auth/register.blade.php` | UI wizard (keep) |
| `resources/js/pages/nida-register.js` | Frontend flow (keep) |
| `config/services.php` → `nida` | Config |
| `.env` | `NIDA_ENABLED`, `NIDA_DRIVER`, URL, UserID |

### Switch to live
```env
NIDA_ENABLED=true
NIDA_DRIVER=http
NIDA_BASE_URL=...
NIDA_USER_ID=...
# + cert paths when wired
```

`AppServiceProvider` binds `fake` → `FakeNidaClient`, `http` → `HttpNidaClient`.

**UI does not need redesign** — only implement real crypto/SOAP inside `HttpNidaClient`.

---

## Applicant preliminary profile (`/applicants/create`)

NIDA verification happens **only at registration**.

On complete preliminary info:
- **NIN + identity** (names, sex, DOB, age, nationality, photo) are shown as **constants** (read-only) from `users` (saved at register)
- User only fills loan preference, marital status, disability, address
- Email/phone remain locked from registration

Key: `users.nin` / `dob` / `sex` / `nationality` / `nida_photo_path` / `nida_verified_at`


| | Fake (now) | Live CIG |
|--|------------|----------|
| Flow / UI | Same idea | Same |
| Data | Demo person (Neema Juma Mwangi) | Real NIDA record |
| Questions | Fixed (Asha / Dodoma) | Real RQ codes from CIG |
| Transport | Local JSON API | VPN + certs + AES/RSA/sign |

---

## Programming tasks after NIDA + network ready

1. Confirm VPN + hosts + certs on server
2. Implement `HttpNidaClient` (encrypt / sign / call / decrypt / parse)
3. Set `.env` to `NIDA_DRIVER=http`
4. Test with NIDA test NINs
5. Later swap production endpoint/certs

---

## Useful TOC pages (from CIG doc)

- **4.4** RQVerification — pp. 50–55  
- **4.1.1.4–4.1.1.5** Response fields + sample — pp. 33–41  
- **3.2–3.4** VPN + certificates — pp. 15–16  
- **5** Getting Started (crypto steps) — pp. 70–73  
