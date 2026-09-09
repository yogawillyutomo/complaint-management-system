# Release Candidate Freeze Policy

## Objective

Once a commit is selected for manual UAT, that commit is treated as a frozen release candidate.

The freeze protects traceability between:
- code;
- CI result;
- release-evidence artifact;
- manual UAT screenshots;
- tester results;
- thesis claims.

## During Freeze

Allowed changes:
- confirmed defect fixes;
- test corrections that do not weaken application invariants;
- documentation corrections that describe the tested behavior accurately;
- security fixes required to keep the candidate safe.

Not allowed without starting a new candidate:
- new business features;
- workflow redesign;
- role/authorization weakening;
- status-machine changes unrelated to a confirmed defect;
- new analytics presented as already accepted;
- deployment/platform changes bundled into UAT fixes;
- historical migration rewrites.

## Candidate Identity Rule

If application code, migration, dependency lock, workflow behavior, or security-relevant configuration changes, the previous candidate is no longer the current candidate.

The replacement candidate must:
1. have a new commit SHA;
2. pass the full CI matrix;
3. produce a new release-evidence artifact;
4. be checked with `php artisan uat:readiness`;
5. retest affected manual UAT scenarios.

## Evidence Reuse Rule

Evidence from candidate A must not be represented as evidence for candidate B.

Unchanged screenshots may be useful for comparison, but acceptance evidence must identify the exact candidate under test.

## Manual UAT Status

Before human execution:
- `manual_uat_status = NOT_RUN`.

During execution:
- individual scenarios may be PASS / FAIL / BLOCKED / NOT RUN.

Only a human tester may complete the UAT acceptance/sign-off section.

## Exit Criteria

The freeze can end when:
- required manual scenarios pass;
- all blocking defects are closed/retested;
- release candidate is accepted;
- thesis synchronization uses evidence from that same candidate.

After acceptance, future feature work should begin in a new post-UAT tranche rather than modifying the accepted candidate evidence.


## Repository Enforcement

The repository contains `.github/rc-freeze.json` and a CI **Release Candidate Freeze Guard**.

The guard always reads the freeze configuration from the pull request's **base commit**, not from the pull request branch. This prevents a PR from bypassing an active freeze by changing its own copy of the policy.

### Before manual UAT

The committed policy remains:

```json
{
  "active": false
}
```

This means normal development remains possible while the UAT tooling itself is still being prepared.

### Activating the freeze

Immediately before manual UAT starts:

1. select the exact `main` candidate;
2. open a dedicated PR that only activates the freeze policy;
3. use the title prefix `[rc-admin]` or the `rc-freeze-admin` label;
4. set `active=true`;
5. pass the full CI matrix;
6. merge that activation PR;
7. treat the resulting `main` commit as the candidate to test;
8. retain its release-evidence artifact;
9. prepare/seed the UAT environment;
10. run `php artisan uat:readiness`;
11. begin manual UAT only after readiness is fully PASS.

### Changes while freeze is active

Documentation-only changes under `docs/` or `README.md` are allowed without an RC reason label/prefix.

Any other change requires one approved reason:

| Reason | Label | PR title prefix |
|---|---|---|
| Confirmed defect fix | `rc-defect-fix` | `[rc-defect]` |
| Security fix | `rc-security-fix` | `[rc-security]` |
| Test correction without weakening invariants | `rc-test-fix` | `[rc-test]` |
| Deliberate candidate replacement | `rc-candidate-replace` | `[rc-replace]` |
| Freeze-policy administration | `rc-freeze-admin` | `[rc-admin]` |

Every allowed non-documentation change while frozen creates a **replacement candidate**. The new candidate must run full CI, produce new release evidence, pass UAT readiness, and retest affected scenarios.

### Historical migration invariant

While freeze is active, modifying, deleting, or renaming an existing historical migration is blocked even when an RC reason label/prefix is present.

A confirmed defect that requires a database change must use a **new forward migration**.

### Deactivating the freeze

Freeze configuration changes require `rc-freeze-admin` or `[rc-admin]`.

Do not deactivate the freeze merely to make an unrelated feature PR pass. Deactivation should happen only after UAT acceptance/release-candidate exit or as an explicit candidate-governance decision.
