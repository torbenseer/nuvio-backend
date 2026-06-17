# Backend Agent Guide

Work in this repository for the Laravel API MVP.

- Repository: `https://github.com/torbenseer/nuvio-backend`
- Parent coordination repo: `https://github.com/torbenseer/nuvio`
- Frontend sibling repo: `https://github.com/torbenseer/nuvio-frontend`

- Read `CONTEXT.md` first for canonical domain language.
- Treat `docs/05_API_SPEC.md` as the source of truth for MVP routes and response shapes.
- Use `docs/02_MVP_SCOPE.md` for scope boundaries and `docs/10_IMPLEMENTATION_PLAN.md` for ticket order.
- Keep the MVP backend-only. Do not add frontend application code.
- Use grouped service folders: `app/Services/Review`, `Today`, `Tasks`, `Mastery`, and `Content`.
- Do not implement later-phase custom tasks, simulations, gamification, AI, or LearningSession behavior unless explicitly requested.
- Keep API-facing learning and feedback behavior aligned with the frontend docs, especially `../frontend/docs/06_SCREEN_AND_STATE_SPECS.md` and `../frontend/docs/11_UI_GUIDELINE.md`.

## Issue Handling

If an error, failing test, API-contract gap, migration problem, unclear domain rule, or integration blocker appears:

1. Fix it directly if the change is small, local, and safe.
2. If it cannot be solved immediately, create or draft an Issue for `torbenseer/nuvio-backend`.
3. If frontend behavior or UI copy must change as a result, reference the needed frontend Issue in `torbenseer/nuvio-frontend`.
4. Include reproduction steps, expected behavior, actual behavior, affected endpoint/service/model, affected files, logs, and test output.
5. If GitHub issue creation is unavailable, include a ready-to-file issue draft in the final response.

Do not leave unresolved backend problems only as TODOs or private notes.

## Commit Discipline

- Make meaningful backend commits during the work, not only at the end.
- Keep commits small and reviewable: one migration group, model group, endpoint, service behavior, or test slice per commit.
- Do not commit frontend or root files from this repo.
- Do not stage unrelated user changes.
- Before committing, run the relevant backend checks when available, usually targeted PHPUnit tests first and broader tests when the blast radius grows.
- Use clear commit messages, for example `feat: add learning core models`, `test: cover today selector`, or `fix: preserve task attempt version`.
- If backend work changes the frontend contract, update the API docs in the same backend commit or create a linked issue when the contract cannot be settled immediately.
