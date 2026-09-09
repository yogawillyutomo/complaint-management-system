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
