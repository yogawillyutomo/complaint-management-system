# Manual UAT Operator Runbook

## Purpose

This runbook is the operational procedure for executing manual UAT against one exact release-candidate commit.

Manual UAT is a human acceptance activity. Automated tests and CI evidence are prerequisites, not substitutes for tester observation.

## 1. Select and Freeze the Release Candidate

Use the exact commit on `main` that will be tested.

Before manual UAT begins, activate the repository release-candidate freeze as described in `RELEASE_CANDIDATE_FREEZE.md`. The freeze activation itself must pass full CI; the resulting `main` commit becomes the candidate identity.

Record:
- repository;
- commit SHA;
- GitHub Actions run;
- release-evidence artifact name/digest;
- test date;
- tester names.

Do not begin manual UAT while the candidate branch is changing.

## 2. Use a Dedicated Non-Production Database

The UAT database must not contain operational/production records.

Never run `UatScenarioSeeder` in production.

Recommended clean setup:

```bash
php artisan migrate:fresh --seed
```

Normal seeding creates reference data only and no login accounts.

## 3. Configure Controlled UAT Credentials

Set a local-only password of at least 12 characters.

PowerShell:

```powershell
$env:UAT_DEMO_PASSWORD="replace-with-a-local-uat-password"
```

Bash:

```bash
export UAT_DEMO_PASSWORD="replace-with-a-local-uat-password"
```

Do not put the real UAT password in:
- screenshots;
- Git commits;
- UAT result tables;
- thesis appendices;
- release evidence artifacts.

## 4. Seed Controlled Scenarios

```bash
php artisan db:seed --class="Database\Seeders\UatScenarioSeeder"
```

This creates only controlled `example.test` UAT identities and predefined complaint scenarios.

## 5. Verify Readiness Before Opening the Browser

Run:

```bash
php artisan uat:readiness
```

All checks must show `PASS`.

For machine-readable output:

```bash
php artisan uat:readiness --json
```

The command checks:
- non-production environment;
- UAT password configuration;
- required database schema;
- canonical roles;
- priority/SLA reference values;
- active UAT accounts with correct roles;
- submitted scenario;
- assigned scenario;
- waiting-input scenario;
- resolved + Needs Follow-up scenario;
- active leadership escalation scenario.

It does not:
- reveal the UAT password;
- create accounts;
- migrate;
- reset data;
- fix drift automatically.

A failed readiness check means manual UAT must not start.

## 6. Start the Application

Use the normal local application start procedure.

Example:

```bash
php artisan serve
npm run dev
```

Use one browser profile/incognito window per role when practical to avoid session confusion.

## 7. Execute Scenarios

Use:
- `docs/uat/UAT_SCENARIOS.md`;
- `docs/uat/UAT_EXECUTION_RECORD.md`.

Execute scenario IDs deliberately.

For each scenario record:
- tester;
- role;
- ticket;
- expected result;
- actual result;
- PASS / FAIL / BLOCKED;
- evidence filename;
- notes.

Do not infer PASS from prior automated tests.

## 8. Evidence Naming Convention

Recommended screenshot format:

```text
<SCENARIO-ID>__<ROLE>__<TICKET-OR-SURFACE>__<RESULT>.png
```

Examples:

```text
UAT-B03__SUPERVISOR__UAT-SUBMITTED-001__PASS.png
UAT-C03__HRD__UAT-ASSIGNED-001__PASS.png
UAT-E06__PIMPINAN__UAT-ESCALATED-001__PASS.png
UAT-I02__ADMIN__SELF-DEACTIVATION__PASS.png
```

If a scenario fails:

```text
UAT-F05__HRD__UAT-RESOLVED-FEEDBACK-001__FAIL.png
```

Do not overwrite the original failure evidence after a fix. Capture a separate retest artifact.

## 9. Defect Handling

For every FAIL:
1. stop claiming the scenario as accepted;
2. record actual behavior;
3. record screenshot/log evidence;
4. create a defect issue/PR reference;
5. fix on a dedicated branch;
6. run full automated CI;
7. merge only when authorized;
8. create/select a new release-candidate commit;
9. regenerate release evidence;
10. retest the failed scenario and any affected regression scenarios.

A code change after UAT starts creates a new candidate identity. Do not reuse evidence from the old commit as if it belonged to the new commit.

## 10. Acceptance

Manual UAT is accepted only after:
- selected scenarios are executed;
- all required scenarios are PASS;
- any BLOCKED items are explicitly resolved or accepted as conditions;
- FAIL items have successful retest evidence;
- tester names and dates are recorded;
- the exact commit SHA is recorded;
- the release evidence artifact corresponds to that commit.

Only then may `Accepted for thesis evidence` or `Accepted as release candidate` be marked YES.
