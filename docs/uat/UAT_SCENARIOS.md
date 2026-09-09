# UAT Scenario Matrix

Use this matrix as the manual acceptance checklist. The expected results describe the current repository behavior.

## A. Authentication and Account Boundary

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-A01 | Karyawan | Login with active account | Login succeeds and dashboard is accessible |
| UAT-A02 | Any | Login with inactive account | Authentication is denied |
| UAT-A03 | Any authenticated | Session becomes inactive after admin deactivation | Existing protected session is terminated |
| UAT-A04 | Roleless user | Open dashboard/complaints | Access is denied |
| UAT-A05 | Karyawan | Open another employee's complaint URL | Access is denied |

## B. Complaint Submission and Supervisor Intake

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-B01 | Karyawan | Create complaint with valid category/priority/title/description | Unique ticket is created with status `submitted` |
| UAT-B02 | Admin/Supervisor/HRD/Pimpinan | Attempt to create complaint | Access is denied |
| UAT-B03 | Supervisor | Validate submitted complaint | Status becomes `validated`; first response is recorded |
| UAT-B04 | Supervisor | Reject submitted complaint | Status becomes `rejected`; reporter receives notification |
| UAT-B05 | Supervisor | Assign validated complaint to active HRD | Status becomes `assigned`; HRD receives notification |
| UAT-B06 | Supervisor | Assign non-HRD or inactive HRD | Validation blocks assignment |

## C. HRD Processing and Employee Input

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-C01 | Assigned HRD | Start assigned complaint | Status becomes `in_progress` |
| UAT-C02 | Other HRD | Start complaint assigned to different HRD | Access is denied |
| UAT-C03 | Assigned HRD | Request employee input | Status becomes `waiting_input`; reporter receives notification/action |
| UAT-C04 | Reporter | Send comment while waiting_input | Comment is stored; complaint returns to `in_progress` |
| UAT-C05 | Reporter | Read notification only | Action Center item remains until complaint state changes |

## D. Root Cause, Resolution, and Follow-up

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-D01 | Assigned HRD | Resolve without structured root cause/resolution | Validation blocks resolution |
| UAT-D02 | Assigned HRD | Resolve with valid root cause and summary | Status becomes `resolved`; `resolved_at` is recorded |
| UAT-D03 | Assigned HRD | Enable follow-up without due date | Validation blocks resolution |
| UAT-D04 | Assigned HRD | Resolve with follow-up enabled | Follow-up due date is stored; closure remains blocked until completion |
| UAT-D05 | Assigned HRD | Complete required follow-up with note | Completion timestamp/note are stored |
| UAT-D06 | Assigned HRD | Close resolved complaint without feedback | Written closure reason is required |

## E. Escalation

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-E01 | Karyawan | Attempt to escalate complaint | Access is denied |
| UAT-E02 | Assigned HRD/Supervisor/Admin | Create eligible leadership escalation | One active escalation is created |
| UAT-E03 | HRD | Create second active escalation on same complaint | Operation is blocked |
| UAT-E04 | Leadership escalation | Target inactive/non-pimpinan user | Validation rejects target |
| UAT-E05 | Assigned HRD | Resolve complaint while escalation active | Resolution is blocked |
| UAT-E06 | Target Pimpinan | Resolve active escalation with note | Escalation becomes resolved; HRD can continue workflow |

## F. Employee Resolution Feedback

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-F01 | Reporter | Submit Accepted feedback with rating 1–5 | One immutable feedback record is stored |
| UAT-F02 | Reporter | Submit Needs Follow-up without explanation | Validation blocks submission |
| UAT-F03 | Reporter | Submit second feedback | Access is denied |
| UAT-F04 | Other user | Submit reporter feedback | Access is denied |
| UAT-F05 | Reporter | Submit Needs Follow-up | Closure is blocked until HRD addresses feedback |
| UAT-F06 | Assigned HRD | Address Needs Follow-up with note | Address actor/time/note are stored |
| UAT-F07 | Assigned HRD | Close after feedback and required follow-up are complete | Complaint becomes `closed` |

## G. SLA, Action Center, and Notifications

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-G01 | Supervisor | Submitted complaint passes response deadline | Action Center marks response SLA breach as critical |
| UAT-G02 | HRD | Open complaint passes resolution deadline | Resolution-overdue action appears |
| UAT-G03 | Pimpinan | Critical complaint breaches SLA | Leadership Action Center item appears |
| UAT-G04 | Any target user | Workflow event notification arrives | Bell unread count increases and deep link opens complaint |
| UAT-G05 | Any target user | Mark notification read | Read state changes but unresolved Action Center work remains |
| UAT-G06 | Any user | Mark all notifications read | Unread count becomes zero; complaint workflow is unchanged |

## H. Reporting and Management Insight

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-H01 | Admin/Supervisor/HRD/Pimpinan | Open reports | Access succeeds |
| UAT-H02 | Karyawan | Open reports | Access is denied |
| UAT-H03 | Management | Filter valid date range | Metrics use complaints created in selected period |
| UAT-H04 | Management | Select date range over 366 days | Validation blocks request |
| UAT-H05 | Management | Export CSV | Filtered complaint-level evidence downloads |
| UAT-H06 | Management | Review recurrence proxy | UI labels it as a proxy, not proof of identical recurrence |
| UAT-H07 | Management | Review feedback metrics | Coverage, satisfaction, Accepted/Needs Follow-up and closure-without-feedback are visible |

## I. Administration, Retention, and Security

| ID | Role | Scenario | Expected result |
|---|---|---|---|
| UAT-I01 | Admin | Deactivate employee with complaint history | User access is blocked; complaint/history/comment remain |
| UAT-I02 | Admin | Deactivate own account | Operation is blocked |
| UAT-I03 | Admin | Remove role from last active admin | Operation is blocked |
| UAT-I04 | Any | Attempt application complaint delete | Policy denies deletion |
| UAT-I05 | Application model | Rewrite/delete complaint history | Append-only guard rejects mutation |
| UAT-I06 | HTTP response | Inspect browser security headers | nosniff, DENY frame, same-origin referrer, permissions policy are present |
| UAT-I07 | Notifications | Prune old read notifications | Old read rows are removed; unread rows remain |
