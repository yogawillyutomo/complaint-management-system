# UAT Plan — Complaint Management System

## 1. Purpose

This User Acceptance Testing (UAT) tranche validates that the application behaves as an end-to-end employee complaint handling system, not only as a collection of isolated features.

The acceptance basis is the workflow currently implemented in the repository:

`submitted → validated → assigned → in_progress ↔ waiting_input → resolved → closed`

with optional escalation, post-resolution follow-up, and employee resolution feedback.

UAT must verify both:
- successful role-based business flows; and
- fail-closed behavior when a user attempts an action outside their authority or outside the valid complaint state.

## 2. UAT Roles

| Role | Main acceptance responsibility |
|---|---|
| Karyawan | Create complaint, provide additional input, review resolution, submit feedback |
| Supervisor | Validate/reject submitted complaints and assign an active HRD PIC |
| HRD | Process assigned complaints, request input, record root cause/resolution, follow up, escalate when necessary |
| Pimpinan | Resolve targeted leadership escalations and review management-level conditions |
| Admin | Global oversight, user lifecycle, reporting, and cross-system administration |

## 3. UAT Environment Rules

UAT must use a dedicated non-production database.

Never run `UatScenarioSeeder` against:
- production;
- a shared operational database;
- a database containing evidence that must be retained.

The seeder fails closed when:
- `APP_ENV=production`; or
- `UAT_DEMO_PASSWORD` is missing or shorter than 12 characters.

Normal `php artisan db:seed` creates reference data only and does not create login accounts.

## 4. Preparing a Clean UAT Environment

### Standard reference data

```bash
php artisan migrate:fresh --seed
```

This creates:
- roles;
- complaint categories;
- priority/SLA reference data.

It creates no demo users.

### Controlled UAT scenarios

Set an explicit local-only UAT password first.

PowerShell:

```powershell
$env:UAT_DEMO_PASSWORD="replace-with-a-local-uat-password"
php artisan db:seed --class="Database\Seeders\UatScenarioSeeder"
```

Bash:

```bash
export UAT_DEMO_PASSWORD="replace-with-a-local-uat-password"
php artisan db:seed --class='Database\Seeders\UatScenarioSeeder'
```

UAT accounts:

| Role | Email |
|---|---|
| Admin | `uat.admin@example.test` |
| Karyawan | `uat.employee@example.test` |
| Supervisor | `uat.supervisor@example.test` |
| HRD | `uat.hrd@example.test` |
| Pimpinan | `uat.pimpinan@example.test` |

The password is the value supplied through `UAT_DEMO_PASSWORD`.

## 5. Seeded State Scenarios

| Ticket | State | Intended validation |
|---|---|---|
| `UAT-SUBMITTED-001` | submitted | Supervisor validation/rejection |
| `UAT-ASSIGNED-001` | assigned | Assigned HRD starts handling |
| `UAT-WAITING-INPUT-001` | waiting_input | Employee provides additional information |
| `UAT-RESOLVED-FEEDBACK-001` | resolved | Needs Follow-up feedback handling |
| `UAT-ESCALATED-001` | in_progress + active escalation | Pimpinan resolves escalation |

## 6. UAT Result Definitions

- **PASS** — observed behavior matches the expected result.
- **FAIL** — expected behavior is not met.
- **BLOCKED** — test cannot be completed because of an environment/data prerequisite.
- **NOT RUN** — scenario has not yet been executed.

A FAIL must capture:
- role used;
- ticket number;
- actual result;
- expected result;
- screenshot/log reference;
- remediation issue/PR if created.

## 7. Automated Acceptance Gate

The repository contains an automated UAT lifecycle suite:

`tests/Feature/Uat/EndToEndComplaintUatTest.php`

It verifies:
- full role-to-role complaint lifecycle;
- ownership isolation;
- assigned-HRD boundary;
- escalation authority;
- active-escalation resolution block;
- waiting-input employee response;
- structured resolution;
- employee Needs Follow-up feedback;
- post-resolution follow-up;
- feedback addressing;
- final closure and audit history;
- UAT seeder fail-closed behavior.

Automated UAT is required but does not replace manual UI acceptance.

## 8. Manual Acceptance Evidence

For each manual scenario record:
- tester;
- execution date;
- environment/build commit;
- role;
- ticket;
- expected result;
- actual result;
- PASS/FAIL/BLOCKED;
- screenshot reference;
- tester note.

The commit SHA used for UAT must be recorded before execution.
