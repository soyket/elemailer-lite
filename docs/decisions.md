# Decisions log

Add a dated entry for any architectural or public-behavior change.

## 2026-11-11: Lite and Pro are independent
Pro does not require Lite to be installed or active. No file or compatibility requirement between them. Agents must not introduce cross-dependencies.

## 2026-11-11: AI-native workflow
AGENTS.md is the single source of truth for agent instructions. State is handed off through docs/tasks/current-task.md and Git, not chat history. CI starts with blocking syntax lint and advisory PHPCS because of legacy code.
