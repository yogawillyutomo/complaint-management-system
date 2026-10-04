# UAT Execution Record

## Build Identification

- **Repository:** `yogawillyutomo/complaint-management-system`
- **Branch / release candidate:** `main` / frozen manual-UAT RC
- **Commit SHA:** `11a9538406eba43b6a620b32aac313881cd0315a`
- **Execution date:** 2026-10-04
- **Environment:** Local non-production UAT environment
- **Database:** Dedicated UAT database; exact database name was not recorded in the evidence chat
- **Tester(s):** Human local operator / repository owner (`yogawillyutomo`)
- **Release evidence artifact:** `release-evidence-11a9538406eba43b6a620b32aac313881cd0315a`
- **Release evidence digest:** `sha256:cb5fd041320d0262efb6017f1e8f02dfb0bdc4521e116f3dfa7e6662639d0533`
- **Manual CI run:** `37189790835`

The CI-generated release-evidence artifact is retained as the automated identity record for the exact application commit under test. The manual screenshots referenced below were observed against the same RC commit after `git rev-parse HEAD` confirmed the SHA and `php artisan uat:readiness` returned 22/22 PASS.

## Automated Gate

| Gate | Result | Evidence |
|---|---|---|
| Dependency Security Audit | PASS | Manual GitHub Actions run `37189790835`; Composer 0 advisories; production npm audit 0 vulnerabilities |
| SQLite / PHP 8.3 | PASS | 117 passed, 1 MySQL-only skipped / 573 assertions |
| SQLite / PHP 8.4 | PASS | Manual GitHub Actions run `37189790835` |
| SQLite / PHP 8.5 | PASS | Manual GitHub Actions run `37189790835` |
| MySQL 8.4 / PHP 8.3 | PASS | 118 tests / 582 assertions |
| Frontend build | PASS | Vite production build passed in runtime lanes |
| End-to-end UAT lifecycle | PASS | `tests/Feature/Uat/EndToEndComplaintUatTest.php` included in the exact-RC test suite |
| UAT readiness | PASS | `php artisan uat:readiness` — 22/22 checks passed |

### Known non-blocking dependency observation

The full npm development dependency graph still reports five high-severity findings through the Tailwind 3 build/dev chain via `braces`. The blocking production-dependency audit is clean, and the full dev audit is retained as an explicit informational signal rather than hidden. A forced Tailwind 4 migration was not introduced into this frozen RC.

## Manual UAT Summary

This record documents the **targeted replacement-RC retest** executed after the login-UI and dependency-security changes. Earlier manual-UAT screenshots remain rehearsal/supporting evidence and are not used as the sole PASS evidence for this RC.

| Scenario group | Result | Tester note / evidence |
|---|---|---|
| Authentication & account boundary | PASS | Branded desktop/mobile login, valid login, invalid login, forgot-password surface |
| Complaint submission & supervisor intake | PASS | Current-RC ticket created, validated, and assigned |
| HRD processing & employee input | PASS | Start progress, request input, reporter response returns ticket to In Progress |
| Root cause / resolution / follow-up | PASS | Structured analysis, required follow-up, follow-up completion |
| Escalation | PASS | Leadership escalation created and exact target Pimpinan resolved it |
| Employee resolution feedback | PASS | Needs Follow-up, HRD address, final closure |
| SLA / Action Center / notifications | PASS | Notification deep-link and read-state/source-action invariant verified |
| Reporting & management insight | PASS | Supervisor allowed; Karyawan receives 403 |
| Administration / retention / security | NOT RUN | Not part of targeted manual replacement-RC retest; automated security/retention regression coverage passed |

## Detailed Manual Results

### Authentication and visual regression

| Scenario ID | Role | Ticket | Expected | Actual | Result | Evidence |
|---|---|---|---|---|---|---|
| VIS-LOGIN-01 | Guest | Login | Desktop login branded for Complaint System / PT Telkom; no Laravel branding | Branded centered login card rendered without clipping | PASS | `VIS-LOGIN-01__GUEST__LOGIN-DESKTOP__PASS.png` |
| VIS-LOGIN-02 | Guest | Login | Mobile login remains usable and branded | Responsive login rendered correctly at mobile viewport | PASS | `VIS-LOGIN-02__GUEST__LOGIN-MOBILE__PASS.png` |
| UAT-A01 | Karyawan | Login | Active Karyawan can sign in | Login succeeded and Karyawan dashboard rendered | PASS | `UAT-A01__KARYAWAN__LOGIN__PASS.png` |
| AUTH-NEG-01 | Karyawan | Login | Invalid credentials rejected with clear error | Error shown; login layout remained intact | PASS | `AUTH-NEG-01__KARYAWAN__INVALID-LOGIN__PASS.png` |
| AUTH-RESET-01 | Guest | Password reset | Forgot-password link opens reset-request surface | Reset-request form rendered with Complaint System branding | PASS | `AUTH-RESET-01__GUEST__FORGOT-PASSWORD__PASS.png` |

### Current-RC lifecycle smoke ticket

Canonical ticket:
`CMP-01M432IYQDW4RS36YY55T3F6R3`

Title:
`UAT RC Final - Kendala Proses Operasional`

| Scenario ID | Role | Expected | Actual | Result | Evidence |
|---|---|---|---|---|---|
| UAT-B01 | Karyawan | Create complaint -> Submitted | New High-priority complaint created with unique ticket and Submitted status | PASS | `UAT-B01__KARYAWAN__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-B03 | Supervisor | Validate -> Validated; first response recorded | Validated; timeline entry added; First Response changed to Responded | PASS | `UAT-B03__SUPERVISOR__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-B05 | Supervisor | Assign active HRD -> Assigned | Assigned to Siti Rahma; status Assigned; timeline recorded assignment | PASS | `UAT-B05__SUPERVISOR__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-C01 | HRD | Assigned HRD starts handling -> In Progress | Siti Rahma started processing; status In Progress | PASS | `UAT-C01__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-C03 | HRD | Request employee input -> Waiting Input | Waiting Input status and success toast observed | PASS | `UAT-C03__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-C04 | Karyawan | Reporter comments -> In Progress | Comment stored; timeline says Employee provided additional information; status In Progress | PASS | `UAT-C04__KARYAWAN__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-E02 | HRD | Create leadership escalation | Active escalation created: Outside Current Authority / Pimpinan-Area Leadership / target Rina Wijaya | PASS | `UAT-E02__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-E06 | Pimpinan | Exact target resolves active escalation | Rina Wijaya resolved escalation; panel None Active; history Resolved | PASS | `UAT-E06__PIMPINAN__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-D04 | HRD | Resolve with structured root cause + required follow-up | Administrative / Approval Process analysis saved; status Resolved; follow-up due 06 Oct 2026 09:00 | PASS | `UAT-D04__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-D05 | HRD | Complete required follow-up with note | Follow-up Complete; completed at 04 Oct 2026 09:13; evaluation note stored | PASS | `UAT-D05__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-F05 | Karyawan | Submit Needs Follow-up feedback | Needs Follow-up, rating 2/5, employee comment stored | PASS | `UAT-F05__KARYAWAN__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-F06 | HRD | Address Needs Follow-up feedback | Follow-up Addressed; HRD note and timestamp stored | PASS | `UAT-F06__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |
| UAT-F07 | HRD | Close after feedback/follow-up complete | Complaint Closed; follow-up remains complete; feedback remains addressed | PASS | `UAT-F07__HRD__CMP-01M432IYQDW4RS36YY55T3F6R3__PASS.png` |

### Notification / Action Center invariant

Controlled fixture:
`UAT-WAITING-INPUT-001`

Before the notification check, the reporter responded to the fixture to return it to `In Progress`; the assigned HRD then requested input again to generate a fresh current-RC notification and restore `Waiting Input`.

| Scenario ID | Role | Expected | Actual | Result | Evidence |
|---|---|---|---|---|---|
| UAT-G04 | Karyawan | Fresh notification deep-links to the exact complaint and becomes read | `Additional input required` opened `/complaints/UAT-WAITING-INPUT-001`; ticket/state matched | PASS | `UAT-G04__KARYAWAN__UAT-WAITING-INPUT-001__PASS.png` |
| UAT-G05 | Karyawan | Reading notification does not remove unresolved source action | Action Center still showed one `Additional input required` item for the same Waiting Input complaint | PASS | `UAT-G05__KARYAWAN__UAT-WAITING-INPUT-001__PASS.png` |

### Reporting authorization boundary

| Scenario ID | Role | Expected | Actual | Result | Evidence |
|---|---|---|---|---|---|
| UAT-H02 | Karyawan | Reports denied | Direct `/reports` access returned 403 Forbidden | PASS | `UAT-H02__KARYAWAN__REPORTS-FORBIDDEN__PASS.png` |
| UAT-H01 | Supervisor | Reports allowed | Complaint Analytics rendered with filters, KPI cards, feedback, SLA/time and escalation analytics | PASS | `UAT-H01__SUPERVISOR__REPORTS-ACCESS__PASS.png` |

Allowed result values:
- PASS
- FAIL
- BLOCKED
- NOT RUN

## Defects Found

No unresolved functional defect was observed in the targeted replacement-RC retest.

| Defect ID | Scenario | Severity | Description | Fix PR/commit | Retest result |
|---|---|---|---|---|---|
| Observation-01 | UAT-E02 | Informational | A transient target-validation message was visible while selecting the Pimpinan target, but the final valid selection submitted successfully and the active escalation was created with the intended target. | None required for this RC | PASS |

## Acceptance

Targeted replacement-RC retest result: **PASS — pending explicit human acceptance/sign-off**.

- **All required targeted retest scenarios executed:** YES
- **Unresolved FAIL:** NONE OBSERVED
- **BLOCKED targeted scenarios:** NONE
- **Accepted for thesis evidence:** PENDING HUMAN SIGN-OFF
- **Accepted as release candidate:** PENDING HUMAN SIGN-OFF
- **Outstanding conditions:** Human tester must explicitly approve the acceptance statements. Administration / retention / security was not manually replayed because it was outside this targeted replacement-RC retest; exact-RC automated security/regression gates passed.

### Tester Sign-off

Name: Human local operator / `yogawillyutomo`

Role: Manual UAT tester / repository owner

Date: 2026-10-04

Signature/approval reference: **PENDING HUMAN SIGN-OFF**
