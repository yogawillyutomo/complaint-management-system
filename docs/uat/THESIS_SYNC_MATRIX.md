# Thesis Synchronization Matrix

This matrix prevents the thesis narrative from drifting away from the current application.

## Source-of-truth Rule

For implementation claims:
1. repository code and migrations define what is implemented;
2. automated tests define what has been programmatically verified;
3. manual UAT evidence defines what users actually accepted;
4. interview findings support requirements and discussion, but do not automatically prove system effectiveness.

Do not convert planned features into completed features without repository evidence.

## Feature-to-Thesis Mapping

| System area | Current implementation evidence | Thesis section to synchronize |
|---|---|---|
| Authentication/account lifecycle | Active account guard, login throttling, admin deactivation | System implementation, security testing |
| RBAC | ComplaintPolicy + role-scoped Livewire actions | Design, implementation, black-box/UAT |
| Complaint lifecycle | submitted/validated/assigned/in_progress/waiting_input/resolved/closed/rejected | Activity/workflow diagrams, implementation |
| Root-cause analysis | structured category + root cause + resolution summary/evidence | Requirement rationale, implementation, evaluation |
| Follow-up | required flag, due date, completion note/time | Workflow, monitoring/evaluation discussion |
| Escalation | structured reason/level/target/resolution | Workflow, exception handling |
| SLA | response/resolution targets, snapshotted deadlines, breach detection | Operational monitoring; state defaults are configurable |
| Employee feedback | Accepted/Needs Follow-up, satisfaction 1–5, immutable content | Effectiveness/feedback discussion |
| Action Center | role/state-derived unresolved work | Dashboard/UI implementation |
| Notifications | database read/unread event notifications | Supporting workflow feature |
| Analytics | trend, SLA, MTTA/MTTR, root cause, PIC, feedback metrics | Reporting/management dashboard |
| Recurrence | repeated root-cause-category proxy | Discussion only as proxy; not identical-case recurrence proof |
| Audit/retention | append-only history, complaint non-delete, account deactivation | Data integrity/security discussion |
| Security hardening | headers, throttling, dependency audit | Security testing/release readiness |

## Interview-to-System Alignment

The interview supports the need for:
- open communication and clarification;
- root-cause identification;
- explicit PIC;
- escalation when authority/resources/policy require higher action;
- monitoring and follow-up;
- response/feedback;
- recurring-problem evaluation;
- process simplification/digitalization.

The interview does **not** establish:
- fixed SLA minute values;
- a statistically validated satisfaction threshold;
- a causal relationship between the system and reduced complaints;
- inferential statistical validity of qualitative thematic coding.

Those distinctions must remain explicit in the thesis.

## Screenshot Evidence to Refresh

Capture screenshots from the final release candidate, not an older build.

Recommended evidence set:
1. Login.
2. Karyawan complaint list/create.
3. Complaint detail/timeline.
4. Supervisor validation/assignment.
5. HRD root-cause/resolution panel.
6. Waiting-input employee response.
7. SLA monitoring.
8. Escalation panel.
9. Employee resolution feedback.
10. Action Center.
11. Notification bell.
12. Management reports.
13. User management/account activation.
14. Relevant access-denied behavior if required by testing chapter.

Each screenshot should record:
- role used;
- ticket/scenario ID;
- release commit SHA;
- caption describing only what is visible/verified.

## Testing Evidence

Use automated test counts only from the final `main` workflow run after the UAT tranche is merged.

Manual UAT results must remain:
- NOT RUN before execution;
- PASS only after observed expected behavior;
- FAIL when behavior differs;
- BLOCKED when environment/data prevent execution.

Do not turn the existence of an automated test into a claim that a human tester accepted the UI.
