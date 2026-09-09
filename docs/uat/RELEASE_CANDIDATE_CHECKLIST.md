# Release Candidate Checklist

This checklist is for a UAT/release-candidate build. It is not a deployment instruction.

## 1. Source Control

- [ ] Target commit SHA recorded.
- [ ] No uncommitted local changes are being treated as release evidence.
- [ ] All intended PRs are merged to `main`.
- [ ] Post-merge GitHub Actions run is green.
- [ ] No temporary CI write permission remains.
- [ ] No Railway/deployment change is bundled unintentionally.

## 2. Automated Gates

- [ ] SQLite / PHP 8.3 passes.
- [ ] SQLite / PHP 8.4 passes.
- [ ] SQLite / PHP 8.5 passes.
- [ ] MySQL 8.4 / PHP 8.3 parity passes.
- [ ] Frontend build passes.
- [ ] Composer security audit passes.
- [ ] npm security audit reports no high/critical vulnerability.
- [ ] End-to-end UAT feature tests pass.

## 3. Database and Migration Readiness

- [ ] Fresh migration succeeds.
- [ ] Existing forward migration chain is retained; historical migrations are not rewritten.
- [ ] MySQL migration path is tested.
- [ ] Complaint retention FKs behave as intended.
- [ ] Operational indexes exist.
- [ ] Notification table exists.
- [ ] Backup/restore procedure is documented before any real production rollout.

## 4. Access and Security

- [ ] Public registration remains disabled.
- [ ] Inactive account login is blocked.
- [ ] Existing inactive session is terminated.
- [ ] Roleless user is denied protected complaint surfaces.
- [ ] Karyawan can only view own complaints.
- [ ] Only active HRD can be assigned.
- [ ] Last-active-admin safeguards pass.
- [ ] Complaint hard delete remains disabled.
- [ ] Audit history remains append-only.
- [ ] Browser security headers are present.
- [ ] Mutation throttles operate on complaint create/comment/feedback/notification read actions.

## 5. Complaint Workflow

- [ ] submitted → validated.
- [ ] submitted → rejected.
- [ ] validated → assigned.
- [ ] assigned → in_progress.
- [ ] in_progress → waiting_input.
- [ ] waiting_input + reporter response → in_progress.
- [ ] structured root cause/resolution required before resolve.
- [ ] active escalation blocks resolve/close.
- [ ] follow-up requirement blocks close until completed.
- [ ] Needs Follow-up feedback blocks close until addressed.
- [ ] no-feedback closure requires written reason.
- [ ] final closed complaint preserves full history.

## 6. SLA and Escalation

- [ ] Priority SLA targets are present.
- [ ] Complaint deadlines snapshot priority targets.
- [ ] First official response is recorded.
- [ ] Response breach logic verified.
- [ ] Resolution breach logic verified.
- [ ] One active escalation per complaint.
- [ ] Pimpinan target validation verified.
- [ ] Escalation resolution audit evidence exists.

## 7. Notifications and Action Center

- [ ] New complaint notifies supervisor.
- [ ] Assignment notifies HRD.
- [ ] Waiting input notifies employee.
- [ ] Resolved complaint requests employee feedback.
- [ ] Needs Follow-up feedback notifies HRD.
- [ ] Escalation notifies leadership target.
- [ ] Escalation resolution notifies HRD.
- [ ] Notification read state is independent from Action Center state.
- [ ] Read notification pruning retains unread items.

## 8. Reporting

- [ ] Management access is role-scoped.
- [ ] Date filter works.
- [ ] Maximum 366-day range guard works.
- [ ] SLA compliance metrics match controlled fixture calculations.
- [ ] MTTA/MTTR match controlled fixture calculations.
- [ ] Root-cause distribution works.
- [ ] Recurrence is explicitly labeled a proxy.
- [ ] Feedback coverage/satisfaction metrics work.
- [ ] CSV export downloads expected columns.

## 9. Manual UAT Evidence

- [ ] Tester names recorded.
- [ ] Test date recorded.
- [ ] Environment and commit SHA recorded.
- [ ] PASS/FAIL/BLOCKED recorded for each selected scenario.
- [ ] Screenshots reference exact scenario IDs.
- [ ] All FAIL results have remediation references.
- [ ] Re-test results are recorded after fixes.

## 10. Thesis Synchronization Gate

Do not copy implementation claims into the thesis until the matching feature is verified in the release candidate.

- [ ] Use actual role names from the application.
- [ ] Use actual complaint statuses.
- [ ] Use actual screenshots from current `main`.
- [ ] Describe root-cause categories as implemented.
- [ ] Describe SLA targets as configurable initial operational defaults, not interview-derived fixed policy.
- [ ] Describe recurrence as a proxy.
- [ ] Distinguish qualitative interview coding from application metrics.
- [ ] Use actual automated test counts from the final CI run.
- [ ] Use manual UAT results only after execution; do not pre-label them PASS.
