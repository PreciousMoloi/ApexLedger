# APEXLedger — Client Portal for Ntuli Accountants and Associates

APEXLedger is a secure, web-based client portal that replaces scattered email,
WhatsApp, and paper files with one central system for document sharing,
messaging, and service tracking between **Ntuli Accountants and Associates**
and their clients.

This document explains what the website does, how each part works, how the
different types of users register and log in, and what each role is
responsible for.

---

## 1. What the Website Does

- Clients register themselves online, upload documents, message their
  accountant, and track the progress of work being done for them (tax
  returns, payroll, bookkeeping, advisory, audits).
- Accounting staff (accountants, managers, admins) log in to the same portal
  to manage their assigned clients, respond to messages, review documents,
  and track service tickets.
- Every accountant is responsible for one **duty** (Taxation, Bookkeeping,
  Payroll, Advisory, or Audit). Client queries are routed automatically to
  the accountant who owns that duty — **no admin approval or hand-off is
  required** for a client's message to reach the right person.

---

## 2. Technology & Setup

- **Stack:** PHP + MySQL (built and tested for WAMP on Windows).
- **Database:** `apexledger_db` is created automatically the first time the
  site is loaded — there is nothing to set up manually in phpMyAdmin.

**Setup steps:**
1. Extract the zip file.
2. Place the `ApexLedger` folder inside `C:\wamp64\www\`.
3. Give the `uploads/` folder write permissions (right‑click → Properties →
   Security → Edit → Add → Everyone → Full Control).
4. Start WAMP and wait for the tray icon to turn green.
5. Open `http://localhost/phpmyadmin` just to confirm MySQL is running.
6. Open `http://localhost/ApexLedger/` in your browser.

If your MySQL root user has a password, edit the three variables at the top
of `config/database.php`.

---

## 3. User Types & Roles

There are two categories of accounts: **Clients** and **Staff**. Staff are
further split into three roles: **Accountant**, **Manager**, and **Admin**.

### 3.1 Client
The customer of the accounting firm.

**What they can do:**
- Register themselves (see Section 4).
- Upload and view their own documents.
- Send secure messages that route directly to the right specialist
  accountant.
- Request a service (creates a pre-filled message rather than a ticket —
  ticket creation itself is staff-only).
- View the status of their own service tickets.
- Update their profile, notification preferences, and password.

**What they cannot do:** see other clients' data, create tickets directly,
review/approve documents, or access reports/admin tools.

### 3.2 Accountant
A staff member with a specific **duty** — Taxation, Bookkeeping, Payroll,
Advisory, or Audit. Each accountant is only responsible for the clients and
queries tied to their duty.

**What they can do:**
- View and manage the clients assigned to them.
- Receive client queries automatically for their duty — no one filters or
  approves these first.
- Create and update service tickets for their clients.
- Review, approve, or reject uploaded documents and leave review notes.
- Reply to secure messages and see urgent-flagged messages highlighted.

### 3.3 Manager
Oversight role, one level above accountants.

**What they can do:**
- Everything a client-facing dashboard shows, but scoped to firm-wide data.
- View the **Reports** page (ticket completion, workload per accountant,
  average response time, overdue tickets, CSV export).
- Update ticket status and review documents (per the Permissions Matrix).
- Has two-factor authentication enabled by default.

**What they cannot do:** manage staff accounts or access the Admin Panel.

### 3.4 Admin
Full system administrator.

**What they can do:**
- Create and deactivate staff accounts (accountants, managers, other
  admins), each assigned a duty/department.
- **Reassign which accountant a client is paired with** — this is a
  staffing decision, not a message-approval step. Once assigned, that
  client's queries continue to flow straight to their accountant with no
  further admin involvement.
- Activate/archive client accounts.
- View the Permissions Matrix, full Audit Log, and System Health dashboard
  (storage used, active clients/staff, pending document reviews, failed
  logins, open ticket load).
- Has two-factor authentication enabled by default.

**Important:** Admin does **not** sit in the path of an individual client
query or message. Its role is account/staffing management, not gatekeeping
day-to-day communication.

---

## 4. How Registration & Login Work

### Client registration (self-service)
1. A prospective client fills in the **Register** form: full name, email,
   phone, 10-digit tax number, physical address, an optional **service
   preference** (Taxation / Bookkeeping / Payroll / Advisory / Audit), and a
   password (min. 8 characters, 1 uppercase letter, 1 number).
2. On submission, the system automatically assigns the client to the
   accountant who owns the matching duty (e.g. selecting "Payroll" assigns
   the client to the Payroll accountant). If several accountants share a
   duty, the one with the lightest current caseload is chosen. If no
   preference is given, the client is assigned to whichever active
   accountant currently has the fewest clients.
3. The client can now log in immediately with their email and password.

### Staff accounts (created by Admin only)
Staff cannot self-register. An Admin creates each account from the **Admin
Panel → User Management** tab, setting: full name, email, role
(Accountant/Manager/Admin), and duty/department. New accounts are created
with the default password `Password123!`.

### Logging in
1. Enter email and password on the **Login** page.
2. Accounts with **two-factor authentication enabled** (Admin and Manager,
   by default) are shown a 6-digit demo verification code on screen (this
   demo build has no live SMS/SendGrid gateway wired up) which must be
   entered within 5 minutes to complete login.
3. **Account lockout:** 5 failed login attempts within 15 minutes locks the
   account for 15 minutes.
4. **Auto-logout:** sessions end automatically after 30 minutes of
   inactivity, with a warning shown beforehand.

---

## 5. How a Client Query Reaches the Right Accountant

This is the core workflow of the portal:

1. A client opens **Messages** and starts a new conversation.
2. They choose what the query is about from a dropdown: General (their
   assigned accountant), Taxation, Bookkeeping, Payroll, Advisory, or Audit.
   The dropdown shows the name of the specialist who will receive it.
3. The message is inserted straight into a thread with that accountant.
   There is no queue, no approval step, and no admin routing involved.
4. If the chosen duty has no active accountant (edge case), the message
   falls back to the client's normally assigned accountant.
5. Messages containing urgent keywords ("urgent", "deadline", "penalty",
   "asap", "overdue") are automatically flagged with a red "Urgent" pill so
   the accountant sees them immediately.

Service tickets follow the same principle: only accountants can create
tickets (for their own clients), and a client's "Request a Service" button
opens a pre-filled message to their specialist rather than going through a
staff queue.

---

## 6. Page-by-Page Overview

| Page | Purpose |
|---|---|
| `index.php` | Public landing page — overview of the platform and its features. |
| `about.php` | Company profile, mission, team, and POPIA compliance statement. |
| `contact.php` | Office address, hours, phone/WhatsApp, and map. |
| `register.php` | New client onboarding form. |
| `login.php` | Login with optional 2FA and account lockout. |
| `dashboard.php` | Role-based summary (documents, messages, tickets, overdue items) — the content shown differs for Client / Accountant / Manager / Admin. |
| `documents.php` | Upload documents; staff can set review status (pending/under review/approved/rejected) and leave notes. |
| `messages.php` | Threaded, encrypted messaging with duty-based routing and urgent flagging. |
| `tickets.php` | Service ticket tracking with priority/status badges, filters, and internal notes (staff-only). |
| `profile.php` | Edit personal details, notification preferences, and password. |
| `reports.php` | Manager/Admin only — charts, average response time, overdue list, CSV export. |
| `admin.php` | Admin only — staff/client management, accountant reassignment, Permissions Matrix, Audit Log, System Health. |
| `logout.php` | Ends the session and logs the event. |

---

## 7. Security Features

- Passwords hashed with PHP's `password_hash()`.
- Optional two-factor authentication (enabled by default for Admin/Manager).
- Account lockout after 5 failed logins within 15 minutes.
- Automatic session logout after 30 minutes of inactivity.
- Full audit logging: logins, logouts, registrations, uploads, document
  reviews, messages, ticket changes, profile/password changes, and admin
  actions.
- AES‑256 / secure-upload badges shown on documents.
- Designed to align with South Africa's POPIA (Protection of Personal
  Information Act).

---

## 8. Default Login Credentials (Demo Data)

| Type | Email | Password | 2FA |
|---|---|---|---|
| Client | thabo@example.com | Password123! | Off |
| Client | zanele@example.com | Password123! | Off |
| Accountant — Taxation | acc1@ntuli.co.za | Password123! | Off |
| Accountant — Payroll | acc2@ntuli.co.za | Password123! | Off |
| Accountant — Bookkeeping | acc3@ntuli.co.za | Password123! | Off |
| Accountant — Advisory | acc4@ntuli.co.za | Password123! | Off |
| Accountant — Audit | acc5@ntuli.co.za | Password123! | Off |
| Manager | mgr@ntuli.co.za | Password123! | **On** |
| Admin | admin@ntuli.co.za | Password123! | **On** |

---

## 9. Database at a Glance

Auto-created tables: `users` (staff), `clients`, `documents`,
`message_threads` / `messages`, `service_tickets` / `ticket_history`,
`notifications`, `login_attempts`, and `audit_log`. Each accountant's
`department` column holds their duty (Taxation / Bookkeeping / Payroll /
Advisory / Audit), and each client's `assigned_accountant_id` and
`service_preference` columns drive the automatic routing described in
Section 5.

---

## 10. Business Details

**Ntuli Accountants and Associates**
- Address: C/O Mandela & Financial Square, Woltemade St, eMalahleni, 1034, Mpumalanga
- Phone: 079 400 5315
- Email: accounts@ntuli.co.za
- Hours: Mon–Fri 8am–5pm
