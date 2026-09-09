# Complaint Management System

Web-based employee complaint handling system built with Laravel and Livewire.

The application supports role-based complaint intake, validation, HRD handling, root-cause analysis, escalation, SLA monitoring, employee feedback, Action Center work queues, notifications, and management reporting.

## Roles

- **Karyawan** — submits complaints, provides additional input, reviews resolutions, submits resolution feedback.
- **Supervisor** — validates/rejects submitted complaints and assigns active HRD handlers.
- **HRD** — handles assigned complaints, requests input, records root cause/resolution, performs follow-up, and escalates when needed.
- **Pimpinan** — resolves targeted leadership escalations and reviews management-level conditions.
- **Admin** — user/account administration and global system oversight.

## Complaint Workflow

Primary workflow:

```text
submitted
  ├─> rejected
  └─> validated
        └─> assigned
              └─> in_progress
                    ├─> waiting_input
                    │     └─> in_progress
                    ├─> active escalation
                    │     └─> escalation resolved
                    └─> resolved
                          ├─> post-resolution follow-up
                          ├─> employee feedback
                          └─> closed
```

Important workflow rules:
- only Karyawan can create a complaint;
- Karyawan can only view their own complaints;
- only active HRD users can be assigned;
- only the assigned HRD can perform HRD processing actions;
- active escalation blocks resolution/closure;
- required follow-up blocks closure until completed;
- employee `Needs Follow-up` feedback blocks closure until HRD addresses it;
- closing without employee feedback requires a written reason;
- complaint hard-delete is disabled;
- complaint audit history is append-only.

## Implemented Capabilities

### Security and Account Lifecycle
- login/logout;
- public registration disabled;
- role-based authorization;
- employee ownership isolation;
- active/inactive account lifecycle;
- inactive-session termination;
- last-active-admin safeguards;
- login and mutation rate limiting;
- browser security headers;
- Composer and npm security audit gates.

### Complaint Handling
- complaint creation and unique ticket numbers;
- validation/rejection;
- HRD assignment;
- in-progress handling;
- waiting for employee input;
- comments/discussion;
- structured root-cause category;
- root-cause narrative;
- resolution summary/evidence;
- post-resolution monitoring/follow-up.

### SLA and Escalation
Priority-configurable response/resolution targets with complaint-level deadline snapshots.

Initial operational defaults:

| Priority | Response | Resolution |
|---|---:|---:|
| Low | 480 min | 4320 min |
| Medium | 240 min | 2880 min |
| High | 120 min | 1440 min |
| Critical | 30 min | 480 min |

These values are configurable operational defaults, not fixed values derived from interview evidence.

Escalation supports:
- authority limit;
- SLA risk/breach;
- resource constraint;
- policy/system constraint;
- other;
- leadership or higher-authority escalation;
- one active escalation per complaint;
- explicit escalation resolution evidence.

### Employee Feedback
After resolution, the reporter can submit one immutable feedback record:
- Resolution Accepted;
- Needs Follow-up;
- satisfaction rating 1–5;
- optional comment, with explanation required for Needs Follow-up.

### Action Center and Notifications
- role-scoped Action Center derived from current complaint state;
- database in-app notifications;
- unread/read state;
- mark one/all read;
- deep links to complaint detail;
- notification state does not replace source-of-truth Action Center state.

### Management Reporting
- complaint trend;
- category/root-cause distribution;
- response/resolution SLA compliance;
- response/resolution overdue;
- MTTA/MTTR;
- escalation metrics;
- PIC/HRD workload and performance;
- employee feedback coverage/satisfaction;
- recurrence proxy;
- CSV export.

The recurrence value is explicitly a management proxy based on repeated root-cause categories. It does not prove that individual incidents are identical recurrences.

## Data Integrity

- forward-only migrations;
- SQLite and MySQL regression coverage;
- complaint reporter/category/priority retention safeguards;
- account deactivation instead of destructive user deletion;
- append-only complaint history;
- submitted employee feedback immutable;
- old read notifications can be pruned while unread notifications are retained;
- operational indexes support SLA, Action Center, escalation, and timeline queries.

## Tech Stack

- PHP 8.3+
- Laravel 13
- Livewire 3
- Blade
- Tailwind CSS
- Alpine.js
- Vite
- MySQL 8.4 production-parity test target
- SQLite automated test matrix

## Installation

```bash
git clone https://github.com/yogawillyutomo/complaint-management-system.git
cd complaint-management-system

composer install
npm ci

cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then:

```bash
php artisan migrate --seed
npm run build
```

Normal seeding creates reference data only:
- roles;
- categories;
- priorities/SLA targets.

It does **not** create login accounts.

For development:

```bash
npm run dev
php artisan serve
```

## Testing

Full default suite:

```bash
php artisan test
```

Dedicated UAT automation:

```bash
php artisan test tests/Feature/Uat
```

CI gates include:
- SQLite / PHP 8.3;
- SQLite / PHP 8.4;
- SQLite / PHP 8.5;
- MySQL 8.4 / PHP 8.3;
- frontend build;
- Composer security audit;
- npm high-severity security audit;
- release evidence generation on the PHP 8.3 lane.

Generate local release evidence after migrating/seeding reference data:

```bash
php artisan release:evidence --commit=<git-sha>
```

The manifest intentionally excludes secrets and records manual UAT as `NOT_RUN` until a human tester executes and signs the UAT record.

## UAT

UAT documentation:
- [UAT Plan](docs/uat/UAT_PLAN.md)
- [UAT Scenario Matrix](docs/uat/UAT_SCENARIOS.md)
- [Release Candidate Checklist](docs/uat/RELEASE_CANDIDATE_CHECKLIST.md)
- [Thesis Synchronization Matrix](docs/uat/THESIS_SYNC_MATRIX.md)

Controlled UAT fixtures are opt-in and non-production only.

Set `UAT_DEMO_PASSWORD` to at least 12 characters, then run:

```bash
php artisan db:seed --class='Database\Seeders\UatScenarioSeeder'
```

The UAT seeder refuses to run in production.

## Notification Maintenance

Old read notifications can be pruned with:

```bash
php artisan notifications:prune --days=90
```

A daily scheduled prune is registered. Unread notifications are not deleted by this command.

## Development Status

The project has completed the following implementation tranches:

- P0 — Security & Workflow
- P1 — Data Retention & Account Lifecycle
- P2 — Database Integrity & MySQL Parity
- P3 — Root Cause, Resolution & Follow-up
- P4 — SLA, Escalation & Monitoring
- P5 — Reporting & Management Insight
- P6 — Employee Feedback & Resolution Confirmation
- P7 — Action Center & In-App Notifications
- P8 — Operational & Security Hardening
- P9 — UAT & Release Validation
- P10 — Release Evidence & Thesis Traceability (current tranche)

## Thesis / Research Note

Implementation claims should be synchronized with the repository and final UAT evidence.

Interview findings can support requirements and discussion, but should not be used to claim:
- fixed SLA minute values unless separately approved as policy;
- inferential statistical validity for qualitative interview coding;
- system effectiveness before UAT/observational evidence;
- identical-case recurrence from the recurrence proxy.
