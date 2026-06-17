# Nuvio Codex Workflow

This document explains how Codex should work on Nuvio step by step.

Nuvio is a Laravel API backend for an adult learning platform. Codex should implement one phase or one ticket at a time, keep the MVP backend-only, and avoid silently adding out-of-scope features.

In this document, implementation `Phase` headings refer to ticket groups in `docs/10_IMPLEMENTATION_PLAN.md`. Product phases and release gates are defined in `docs/14_RELEASE_ROADMAP.md`.

Repository boundaries:

- Work in `backend/` for the Laravel API MVP.
- Treat `backend/` as its own Git repository.
- Treat `frontend/` as a separate Git repository reserved for the first integrated frontend slice.
- Do not add frontend application code until the V1 API subset exists in backend routes and tests.
- Full B4 API hardening remains required before Private Alpha readiness.

## 1. How To Read The Docs

Read the docs in this order:

1. `CONTEXT.md` for canonical domain language.
2. `docs/00_PRODUCT_VISION.md` for product intent.
3. `docs/02_MVP_SCOPE.md` for strict boundaries.
4. `docs/04_DOMAIN_MODEL.md` for entities and relationships.
5. `docs/05_API_SPEC.md` for canonical endpoint contracts.
6. `docs/10_IMPLEMENTATION_PLAN.md` for the next ticket.
7. `docs/11_TEST_PLAN.md` for required tests.
8. `docs/14_RELEASE_ROADMAP.md` for release gates and release language.

Use the docs as intent, but treat existing code as current reality. If route names or response shapes disagree across docs, prefer `docs/05_API_SPEC.md` and align the other document. If docs and code disagree, prefer a small implementation that preserves working code and update docs only when behavior intentionally changes.

## 2. Implementation Order

Follow `docs/10_IMPLEMENTATION_PLAN.md`.

Implementation ticket-group order:

1. Project setup.
2. Sanctum SPA auth and preferences.
3. Core domain models and migrations.
4. Seed data and content structure.
5. Learning paths and enrollments.
6. Tasks and task versions.
7. Task attempts and answer checking.
8. Review engine.
9. Today action selector.
10. Progress and mastery states.
11. Documentation and cleanup.

Later phases such as custom tasks, simulations, gamification, LearningSessions, and AI teacher scaffolding are not part of the MVP order.

Do not skip ahead to optional phases unless explicitly requested.

## 3. How To Choose The Next Task

Choose the next task by:

1. Checking the user's request.
2. Finding the matching phase or ticket in `docs/10_IMPLEMENTATION_PLAN.md`.
3. Reading the relevant spec docs.
4. Inspecting existing migrations, models, routes, services, and tests.
5. Implementing only the requested ticket or the smallest coherent slice.

If no ticket is specified, choose the earliest incomplete MVP ticket.

## 4. How To Keep Changes Small

Keep each Codex task narrow.

Good task size:

- One migration group and model relationships.
- One service plus tests.
- One endpoint group plus request/resource tests.
- One content seed slice.

Avoid:

- Combining review scheduling, Today selection, and progress UI in one change.
- Refactoring unrelated code.
- Adding optional systems while implementing required MVP behavior.
- Creating abstractions before there are at least two real use cases.

## 5. Coding Conventions

Use Laravel and PHP conventions.

Guidelines:

- Prefer typed properties and return types where practical.
- Use enums or constants for repeated statuses and modes.
- Keep controllers thin.
- Put domain decisions in services.
- Use Form Requests for validation.
- Use API Resources for response shaping.
- Use factories for tests.
- Keep names aligned with docs: `LearningNode`, `NodeRelation`, `TaskVersion`, `TaskAttempt`, `Review`, `MasteryState`, `TodaySelector`.

## 6. Laravel Architecture Conventions

Recommended structure:

- `app/Models` for Eloquent models.
- `app/Http/Controllers` for API controllers.
- `app/Http/Requests` for validation.
- `app/Http/Resources` for response resources.
- `app/Services` for domain services.
- `app/Enums` for stable enums where useful.
- `database/migrations` for schema.
- `database/factories` for test factories.
- `database/seeders` for seed data.
- `tests/Unit` for service tests.
- `tests/Feature` for API and workflow tests.

Controllers should orchestrate. Services should decide.

## 7. Domain Folder Conventions

The MVP can start with standard Laravel folders. Add domain subfolders only when they reduce confusion.

Acceptable service grouping:

- `app/Services/Review/ReviewScheduler.php`
- `app/Services/Today/TodaySelector.php`
- `app/Services/Tasks/TaskGrader.php`
- `app/Services/Mastery/MasteryUpdater.php`
- `app/Services/Content/ContentValidator.php`

Avoid creating a large domain architecture before the code needs it.

## 8. Testing Expectations

Every feature needs tests.

Minimum expectations:

- Migrations can run in tests.
- Model relationships are tested.
- Services have unit tests.
- API endpoints have feature tests.
- Validation failures are tested.
- Authorization boundaries are tested.

Core required tests:

- `ReviewSchedulerTest`
- `TodaySelectorTest`
- `TaskGraderTest`
- `StartTaskAttemptTest`
- `SubmitTaskAttemptTest`
- `LearningPathApiTest`
- `ProgressApiTest`

Run focused tests first, then broader tests:

```bash
php artisan test --filter=ReviewSchedulerTest
php artisan test
```

## 9. Migration Expectations

Migrations must be reversible and explicit.

Rules:

- Add indexes from `docs/04_DOMAIN_MODEL.md`.
- Add unique constraints for pivot and ownership rules.
- Use JSON columns for schemas and payloads.
- Use nullable foreign keys only when the domain allows it.
- Preserve TaskAttempt history through TaskVersion references.
- Do not store only `subject_id` on LearningNode; LearningNode must support multiple Subjects.
- Do not store only one node on Task; Task must support multiple LearningNodes through TaskNode.

After migration work:

- Run migrations.
- Run relationship tests.

## 10. API Response Conventions

Use consistent JSON:

```json
{
  "data": {},
  "meta": {}
}
```

Validation errors should use Laravel's normal `422` format.

Endpoint rules:

- `GET /api/today` returns max 3 actions.
- Task read endpoints do not expose answer schemas.
- Attempt submit endpoints return result and feedback.
- Review endpoints cap due work and do not expose hidden backlog counts in UI-facing responses.
- Progress endpoints use MasteryState and Review data, not only time.
- Progress and Today responses do not expose XP, badges, achievements, streaks, streak freezes, ranks, reward levels, catch-up debt, or lost-progress state.
- V1 responses do not include `completion_state`, `mastery_moment`, `challenge_options`, `mastery_score`, `hidden_due_reviews`, or `returning_after_break`.
- B4 playful response metadata may include `completion_state` and `mastery_moment` only when derived from learning evidence.
- Skill-Map and Challenge Options are Later and need separate contracts.

## 11. Content Import Conventions

Preferred content format is pure YAML under `/content`.

Codex should:

- Keep seed content small.
- Validate content before import.
- Reject invalid tasks before publishing.
- Ensure every task has at least one LearningNode.
- Allow tasks to link to multiple LearningNodes.
- Add tests for invalid content.

Do not build a complex admin CMS for MVP content.

## 12. How To Avoid Over-Engineering

Prefer the simplest implementation that supports the documented MVP.

Do:

- Use relational tables for SkillGraph.
- Compute Today actions on request.
- Use deterministic review scheduling.
- Use simple mastery statuses.
- Keep optional systems optional.

Do not:

- Add a graph database.
- Add event sourcing.
- Add microservices.
- Add complex plugin infrastructure in the MVP.
- Add AI provider integration unless explicitly requested.
- Add custom task endpoints in the MVP.
- Add simulation endpoints or tables in the MVP.
- Add XP, achievement, badge, streak, streak freeze, leaderboard, or reward level tables in V1 or B4.
- Add daily pressure, catch-up, behind, failed, or lost-progress mechanics.
- Add comeback streaks, countdown pressure, artificial scarcity, lootbox-like reveals, or attendance rewards.
- Add AiInteraction tables or AI endpoints in the MVP.
- Build generic workflow engines for simple domain rules.

## 13. What Codex Must Not Implement Unless Explicitly Requested

Do not implement:

- Frontend.
- Payments.
- Subscriptions.
- Community features.
- Native mobile app.
- Complex admin CMS.
- Complex AI teacher.
- General chatbot.
- AI grading as source of truth.
- Career marketplace.
- Full graph visualization.
- Large simulation engine.
- Classroom or team management.

If a requested change seems to require one of these, stop and ask for confirmation.

## 14. Pull Request Checklist

Before finishing a change:

- Tests were added or updated.
- Focused tests pass.
- Relevant broader tests pass when feasible.
- Migrations run.
- API responses match `docs/05_API_SPEC.md` or docs are updated.
- No frontend was added.
- No out-of-scope feature was added.
- Review behavior remains tested.
- Today selector still caps actions at three.
- V1 Today accepts no Energy Mode. B4 Red mode respects max 15 minutes when possible after `POST /api/today/mode` exists.
- Authorization protects user-owned state.
- Task attempts preserve TaskVersion history.

## 15. Definition Of Done For Codex Tasks

A Codex task is done when:

- The requested ticket is implemented.
- Related tests pass.
- New behavior has feature or unit coverage.
- Validation and authorization are handled.
- No unrelated refactor is included.
- Documentation is updated if behavior changed.
- The final response states what changed and what tests were run.

For backend features, "done" usually means database, model, service, endpoint, resource, validation, and tests are aligned.

## 16. Example Codex Task Prompts

### Implementing Migrations And Models

```text
Implement Phase 1 Ticket 1.1 from docs/10_IMPLEMENTATION_PLAN.md.
Add the SkillGraph migrations and Eloquent models for Subject, LearningNode,
NodeRelation, LearningPath and LearningPathNode. Add relationship tests proving
LearningNode can belong to multiple Subjects and LearningPath orders nodes.
Do not add API endpoints yet.
```

### Implementing ReviewScheduler

```text
Implement ReviewScheduler from docs/07_REVIEW_ENGINE.md.
Incorrect, unsure, and skipped attempts should create reviews due in 1 day.
Correct normal attempts should not create retention reviews in the narrow MVP.
Correct review answers should complete the Review and move MasteryState to
`retained`. Add unit tests for scheduling and duplicate review handling.
```

### Implementing TodaySelector

```text
Implement TodaySelector and GET /api/today.
The endpoint must return at most three actions, prioritize due reviews, and
return `meta.limit` without accepting mode, reason, or hidden backlog fields. Add
TodaySelectorTest and TodayApiTest.
```

### Adding Seed Content

```text
Add MVP seed content for Math: Algebra Foundations.
Use LearningNodes, NodeRelations, LearningPaths, Tasks and TaskVersions.
Seed 3 to 5 nodes and 2 to 3 tasks per node. Keep the content small and add
tests that the seeded path contains ordered nodes and every active task has at
least one LearningNode.
```

### Implementing Task Attempts

```text
Implement task attempt start and submit endpoints.
POST /api/task-attempts/start should create a TaskAttempt tied to the active
TaskVersion. POST /api/task-attempts/{id}/submit should grade numeric,
answers in V1. Multiple choice is B4. Self-check is Later. Incorrect, unsure, and skipped attempts should call
ReviewScheduler. Add API and service tests.
```

### Adding API Tests

```text
Add feature tests for the MVP learning loop:
start a LearningPath, call GET /api/today, start a task, submit an incorrect answer,
assert a Review is created, answer the Review correctly, and assert MasteryState
updates. Do not add new product behavior except what is needed to make the test pass.
```

### Later: Scaffolding AI Teacher Without Connecting A Provider

```text
This is not an MVP task. Only use after the MVP is complete and AI work is explicitly requested.
Scaffold the future AI teacher boundary without connecting an AI provider.
Add AiInteraction migration/model only if needed, validate allowed modes from
docs/09_AI_TEACHER_GUARDRAILS.md, and make endpoints return a clear 501 or
disabled response. Do not implement general chat, AI grading or mastery updates.
Add guardrail tests.
```
