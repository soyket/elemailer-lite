# Decisions log

Add a dated entry for any architectural or public-behavior change.

## 2026-11-11: Lite and Pro are independent
Owner statement: Elemailer (Pro) does not require Lite to be installed or active. No file or compatibility requirement between them. Agents must not add cross-dependencies. Note: `elemailer-lite.php` skips initialization if the action `elemailer/after_load` has already fired. Keep that guard unless the owner decides otherwise.

## 2026-11-11: AI-native workflow
AGENTS.md is the single source of truth for agent instructions. State is handed off through docs/tasks/current-task.md and Git, not chat history. The CI workflow starts with blocking syntax lint and advisory PHPCS because the legacy code has coding-standard violations. The matrix uses PHP 7.2 (the minimum in readme.txt) and 8.3.

## 2026-11-11: Known issues are documented, not fixed
docs/known-issues.md lists security and robustness problems found during the review. They were deliberately not changed in the setup branch to keep that change documentation-only.
