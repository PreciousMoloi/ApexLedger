# APEXLedger - Ntuli Accountants and Associates

## Complete Client Portal System

### Setup Instructions (WAMP)

1. Extract this zip file
2. Place the `ApexLedger` folder inside `C:\wamp64\www\`
3. Ensure the `uploads/` folder has write permissions (right‑click → Properties → Security → Edit → Add → Everyone → Full Control)
4. Start WAMP — wait for the tray icon to turn green
5. Open phpMyAdmin (`http://localhost/phpmyadmin`) just to confirm MySQL is running — you don't need to create anything manually
6. Open your browser → `http://localhost/ApexLedger/`

The database `apexledger_db` and all tables are **auto-created on first page load** by `config/database.php` (default WAMP credentials: user `root`, no password). If your MySQL root user has a password, edit the three variables at the top of `config/database.php`.

### Default Login Credentials

| Type | Email | Password | 2FA |
|------|-------|----------|-----|
| Client | thabo@example.com | Password123! | Off |
| Client | zanele@example.com | Password123! | Off |
| Staff Accountant | acc1@ntuli.co.za | Password123! | Off |
| Staff Accountant | acc2@ntuli.co.za | Password123! | Off |
| Admin | admin@ntuli.co.za | Password123! | **On** |
| Manager | mgr@ntuli.co.za | Password123! | **On** |

Accounts with 2FA enabled will be shown a 6‑digit demo code directly on screen after entering the correct password (there is no live SMS gateway wired up in this build — in production this would be dispatched via the SendGrid/SMS provider referenced in the System Design document).

### Business Details

**Ntuli Accountants and Associates**
- Address: C/O Mandela & Financial Square, Woltemade St, eMalahleni, 1034, Mpumalanga
- Phone: 079 400 5315
- Email: accounts@ntuli.co.za
- Hours: Mon-Fri 8am-5pm

### File Structure

```
ApexLedger/
├── assets/
│   ├── css/main.css      (Shared design system — colours, badges, cards, sidebar)
│   └── js/app.js         (Auto-logout, dark mode, mobile sidebar)
├── includes/
│   └── sidebar.php       (Shared app navigation, role-aware)
├── config/
│   └── database.php      (Auto-creates DB & tables, helper functions)
├── uploads/               (File upload folder - needs write permissions)
├── index.php              (Landing page)
├── about.php              (About company with team & compliance)
├── contact.php            (Contact info, hours, map, WhatsApp)
├── register.php           (New Client Onboarding form)
├── login.php               (Secure Login + optional 2FA + lockout)
├── dashboard.php           (Role-based dashboard - client/accountant/admin/manager)
├── documents.php           (Upload, review workflow, AES-256 badge)
├── messages.php            (Secure threaded messaging, urgent flagging, attachments)
├── tickets.php             (Service tickets, priority/status badges, filters, notes)
├── profile.php             (Profile, notification preferences, password change)
├── reports.php             (Manager/Admin reporting dashboard with charts)
├── admin.php               (User mgmt, Permissions Matrix, Audit Log, System Health)
├── logout.php              (Session logout + audit entry)
└── README.md
```

### What changed in this revision

The interface was rebuilt to match the System Design mockups and the functional/non‑functional requirements in the project documentation:

- **New design system** (`assets/css/main.css`): blue primary actions, green create/save actions, navy headings, and colour‑coded status/priority/review badges, applied consistently across every page. New icon‑based "APEXLedger" brand mark replaces the old gradient wordmark.
- **Two‑factor authentication** on login for accounts with `two_factor_enabled` set, with a 5‑minute expiring code (demo mode shows the code on screen).
- **Account lockout** after 5 failed login attempts within 15 minutes (NFR‑Security).
- **Auto‑logout after 30 minutes of inactivity**, both client‑side (JS) and server‑side (session check), with a warning toast.
- **Urgent keyword auto‑flagging** on messages ("urgent", "deadline", "penalty", etc.) with a red "Urgent" pill, plus an "Encrypted" pill and optional document attachments on messages.
- **Document review workflow**: accountants/admins can set pending/under review/approved/rejected status and leave an internal note per document; AES‑256 / secure‑upload badges shown per the mockups.
- **Service ticket priority & status badges**, status/priority filters, and an internal‑notes field per ticket. Ticket creation is restricted to accountants per UC‑12; clients use "Request a Service" which opens a pre‑filled message instead.
- **Notification preferences** (email/SMS/push) on the profile page, persisted to the database.
- **Admin Panel**: added a Permissions Matrix tab and a System Health tab (storage usage, active clients/staff, pending reviews, failed logins, audit events — all derived from real data).
- **New Reports page** (Manager/Admin only) with a ticket‑completion donut chart, workload‑by‑accountant bar chart, average response time, an overdue‑tickets list, and a working CSV export.
- **Audit logging** wired into login, logout, registration, uploads, document review, messages, ticket creation/updates, profile/password changes, and admin actions.
- **Service preference** field added to client registration (per FR‑01) and shown on profile.

### Features

- ✅ Complete User Authentication (Clients + Staff, role-based access, optional 2FA)
- ✅ Auto-create Database & tables on first run
- ✅ Client Registration with password & service preference
- ✅ Secure Dashboard with role-based views (Client / Accountant / Admin / Manager)
- ✅ Document Management with review workflow, categories, AES-256 badge
- ✅ Secure Messaging with threaded conversations, urgent flagging, attachments
- ✅ Service Tickets with priority/status badges, filters, internal notes
- ✅ User Profile with notification preferences & password change
- ✅ Manager/Admin Reports dashboard with charts and CSV export
- ✅ Admin Panel: staff/client management, Permissions Matrix, Audit Log, System Health
- ✅ About Us & Contact pages with business details
- ✅ Fully responsive design with mobile sidebar
- ✅ Dark mode toggle
- ✅ Audit logging for key actions
- ✅ Account lockout & 30-minute auto-logout (POPIA / NFR-Security aligned)
