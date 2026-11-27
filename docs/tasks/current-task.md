# Current task

## Goal
Apply the 2026-11-27 review fixes to branch `ai-native-dev-setup` for owner review: token-saving tiered reading, packaging fixes (C28), CI clean-ups, and doc corrections. No runtime code changes, no changes to `master`.

## Scope / out of scope
In scope: AGENTS.md/CLAUDE.md, .agents/rules/testing.md, docs corrections (C18, C25, C28, C33), .distignore/.gitattributes, Gruntfile copy excludes, composer.json + composer.lock, phpcs.xml.dist, CI workflow, README.md.
Out of scope: C1–C27 and C29–C38 code fixes, version bumps, merges to `master`.

## Acceptance criteria
- [x] `composer lint:syntax` passes
- [x] Workflow YAML parses; PHP 7.2 CI leg pinned to ubuntu-22.04
- [x] `git archive` excludes the AI/dev files including README.md and composer.lock
- [ ] Owner reviews the branch diff

## Manual test steps
No runtime files changed, so no WordPress smoke test is required for this change. Gruntfile.js syntax checked with `node --check`.

## Status / handoff
Done: edits applied and verified (see acceptance criteria).
Remaining: owner review of the branch; merge decision is the owner's.
Blockers: none.
Next step: owner reviews the diff on `ai-native-dev-setup`; after that, start C1 from docs/tasks/backlog.md.
