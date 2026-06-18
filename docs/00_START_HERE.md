# Nuvio Backend Start Here

This is the current planning entry point for backend work.

## Current Reality

Status: **Current**

- Backend V1 Integrated Learning Loop is implemented and tested.
- Laravel skeleton exists.
- API routes are registered through `routes/api.php`.
- `GET /api/status` exists.
- `/up` health endpoint exists.
- Boot/status, auth user, Today, task attempt, review, progress, review versioning, and guardrail tests exist.
- Real V1 learning API routes exist for a pre-provisioned or authenticated learner.
- B4 preference, Today mode, Learning Path, Learning Node, Node Task, Node Prerequisite, and Path Progress routes exist with focused tests.
- B4 review due and review snooze routes exist with focused tests.
- Web session login/logout routes exist with focused tests, but full Sanctum package/config/CORS hardening remains B4.
- Frontend is planning-only until explicitly started against the real V1 subset.
- B4 Algebra Foundations seed breadth now includes three LearningNodes, six numeric Tasks, prerequisite NodeRelations, and validation-only content tooling.

## Active Goal

Status: **B4 hardening / frontend readiness**

V1 is complete. The next backend goal is **B4 API Hardening** while the next cross-repo goal can be the first frontend slice against the real V1 subset when explicitly requested.

B4 must complete the full canonical API route set and hardening gates before Private Alpha readiness.

The implemented V1 loop is:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

## Decision

Use **small real API contract before small real frontend**.

Do not use **complete backend before any frontend**.

Frontend application work may start because the V1 API subset exists in backend routes and tests, but only when explicitly requested. Full B4 API completeness remains required before Private Alpha readiness.

## Completed V1 Tickets

Status: **completed**

1. Add auth or pre-provisioned learner support with `GET /api/user`, locale/timezone defaults, and protected endpoint rejection.
2. Seed one German Math path: Algebra Foundations, one LearningNode, one numeric Task, and one active TaskVersion.
3. Implement minimal enrollment and Today: `POST /api/learning-paths/{id}/start`, `GET /api/today`, max three actions, and next task after enrollment.
4. Implement numeric task attempt flow: `GET /api/tasks/{id}`, `POST /api/task-attempts/start`, `POST /api/task-attempts/{id}/submit`, deterministic grading, and no answer leaks.
5. Implement review/progress loop and tests: review creation for incorrect/unsure/skipped, review answer, progress summary update, `MvpLearningLoopTest`.

Product guardrail for the completed V1 tickets:

- Motivation must come from clear next action, small completed tasks, competence status, Review as normal work, and recovery paths.
- V1 playfulness comes from clear learning interaction and competence status. B4 may add Completion States and Mastery Moments after real learning evidence. Skill-Map and interactive learning tasks are later.
- Do not add XP, badges, achievements, streaks, streak freezes, leaderboards, reward levels, catch-up flows, lost-progress fields, or backlog pressure.
- Do not add comeback streaks, countdown pressure, artificial scarcity, lootbox-like reveals, or rewards for attendance.

## Next Backend Tickets

Status: **B4 hardening**

1. Complete full Sanctum package/configuration, stateful domains, CORS credentials, and browser-origin hardening.
2. Keep the first frontend slice aligned to the real V1/B4 API contracts once frontend implementation starts.
3. Continue any remaining B4 hardening gaps found by integration, without adding pressure mechanics.

## Active API Subset

| Endpoint | Status |
|---|---|
| `GET /api/status` | implemented |
| `/up` | implemented |
| `GET /api/user` | implemented |
| `PUT /api/user/preferences` | implemented |
| `GET /api/today` | implemented |
| `POST /api/today/mode` | implemented |
| `GET /api/learning-paths` | implemented |
| `GET /api/learning-paths/{id}` | implemented |
| `POST /api/learning-paths/{id}/start` | implemented |
| `GET /api/tasks/{id}` | implemented |
| `POST /api/task-attempts/start` | implemented |
| `POST /api/task-attempts/{id}/submit` | implemented |
| `POST /api/task-attempts/{id}/self-check` | Later |
| `GET /api/reviews/{id}` | implemented |
| `POST /api/reviews/{id}/answer` | implemented |
| `GET /api/progress/summary` | implemented |
| `GET /api/nodes` | implemented |
| `GET /api/nodes/{id}` | implemented |
| `GET /api/nodes/{id}/tasks` | implemented |
| `GET /api/nodes/{id}/prerequisites` | implemented |
| `GET /api/reviews/due` | implemented |
| `POST /api/reviews/{id}/snooze` | implemented |
| `GET /api/progress/paths/{learningPath}` | implemented |
| AI, simulations, custom tasks, gamification, CMS, payments, community, public signup, mobile app | later |

## Definition Of Done For V1

Status: **completed**

- One learner can start one Algebra Foundations path.
- `GET /api/today` returns at most three actions and prioritizes reviews before new learning.
- One numeric task can be fetched, started, submitted, and graded without answer leaks.
- Feedback is returned from the submit flow.
- Incorrect, unsure, or skipped work creates review work.
- A review can be answered and progress summary changes.
- Core behavior is covered by `MvpLearningLoopTest` plus focused service tests.
- Progress and feedback use competence/review status only, without XP, badges, achievements, streaks, ranks, reward levels, loss copy, or daily pressure.
- B4 Completion States are neutral and derived from real task or review actions.

## Planning Map

- Full roadmap: `docs/14_RELEASE_ROADMAP.md`.
- Canonical endpoint contract: `docs/05_API_SPEC.md`.
- Implementation path: `docs/10_IMPLEMENTATION_PLAN.md`.
- Scope boundaries: `docs/02_MVP_SCOPE.md`.
- Frontend slice plan: `../../frontend/docs/01_POST_MVP_FRONTEND_PLAN.md`.
