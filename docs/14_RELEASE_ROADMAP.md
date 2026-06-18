# Nuvio Release Roadmap

This document is the canonical release roadmap for Nuvio. It defines release language, release order, entry and exit criteria, scope boundaries, readiness gates, and documentation alignment work.

## 1. Release Taxonomy

**Release**:
Any testable milestone. Every release must declare its audience:

- `internal`: useful to backend/frontend maintainers and agents.
- `integration`: useful for proving contracts across backend and frontend.
- `closed user-facing`: usable by invited trusted learners.
- `controlled public`: publicly announced but access-controlled.

**Phase**:
A product horizon such as Backend MVP, Private Alpha, Public Beta, Content Breadth, or a separately approved later product bet. Do not use phase as the canonical term for small implementation groupings.

**Slice**:
An end-to-end vertical increment inside a release. A slice must be testable through real behavior, not only isolated files.

**Ticket**:
The smallest reviewable implementation unit. Tickets may be grouped into implementation stages, but those groups are not product phases.

## 2. Current Reality

The current backend has completed V1 Integrated Learning Loop:

- Laravel application skeleton.
- API route registration through `routes/api.php`.
- `GET /api/status` baseline API endpoint.
- `/up` application health endpoint.
- Boot/status feature tests.
- `GET /api/user` with locale and timezone defaults.
- Minimal German Algebra Foundations seed content.
- V1 Today, Start Path, Task, TaskAttempt, Review, and Progress Summary routes.
- V1 service and feature tests, including `MvpLearningLoopTest`, Today selection, task grading, review scheduling, ownership/guardrail smoke coverage, and review TaskVersion pinning.
- Sanctum SPA session auth, credentialed CORS, stateful frontend domains, CSRF cookie, login/logout, and cross-origin authenticated API flow have focused tests.
- Review due and review snooze routes have focused tests; snooze moves scheduling only and does not improve MasteryState.

The frontend repository is planning-only. Application code, scaffold files, runtime dependencies, build configuration, and package manifests should still wait until frontend implementation is explicitly requested against the real V1 subset.

## 3. Release Sequence

Canonical sequence:

**R0 -> V1 Integrated Learning Loop -> B4 API Hardening -> F2 Integrated Alpha Candidate -> A1 Private Alpha -> PB1 Controlled Public Beta -> P1/P2/P3/P4**

B1, B2, and B3 may still be used as internal ticket clusters. They are not mandatory horizontal release gates that block all frontend learning.

| Release | Audience | Entry | Exit |
|---|---|---|---|
| R0 Current Baseline | internal | Current repo state | `GET /api/status`, `/up`, boot/status tests, docs identify backend/frontend boundaries |
| V1 Integrated Learning Loop | integration/internal | R0 | Completed: one authenticated or pre-provisioned learner completes the narrow loop against real V1 routes and tests |
| B1 Auth And Ownership Foundation | internal ticket cluster | R0 or V1 path | Auth, user defaults, and ownership isolation pieces needed by V1/B4 |
| B2 SkillGraph And Algebra Seed | internal ticket cluster | R0 or V1 path | Subjects, `skill` LearningNodes, `prerequisite` relations, LearningPaths, Enrollments, German Algebra Foundations seed |
| B3 Task Attempt Review Loop | internal ticket cluster | R0 or V1 path | TaskVersions, TaskAttempts, grading, ReviewScheduler, MasteryState updates |
| B4 API Hardening | internal API release | V1 | Full `docs/05_API_SPEC.md` route set implemented in routes and tests, including node APIs, review due/snooze, path progress, validation matrix, ownership matrix, and API completeness |
| F2 Integrated Alpha Candidate | integration | V1 API subset, then B4 before Alpha | Frontend runs against real backend staging API; real-backend smoke covers login/session, Today, task attempt, review, feedback, and progress |
| A1 Private Alpha | closed user-facing | F2 plus B4 complete | Invited trusted learners use pre-provisioned accounts on a closed staging-like environment; support path and data reset policy documented |
| PB1 Controlled Public Beta | controlled public | A1 learnings resolved | Production deployment, backups, monitoring, rollback docs, invite or waitlist signup, password reset, enough German Algebra content for real use |
| P1 Content Breadth And Ops | product phase | PB1 stable | More SkillGraph content and content operations before new product bets |
| P2 Later Product Bet | product phase | PB1 stable plus explicit decision | Separate PRD, API contract, tests, and readiness gate |
| P3 Later Product Bet | product phase | PB1 stable plus explicit decision | Separate PRD, API contract, tests, and readiness gate |
| P4 Later Product Bet | product phase | PB1 stable plus explicit decision | Separate PRD, API contract, tests, and readiness gate |

### V1 Integrated Learning Loop

Status: **completed**

Audience: integration/internal.

Entry: R0.

Exit criteria:

- One authenticated or pre-provisioned learner can complete Today -> Task -> Attempt -> Feedback -> Review -> Progress.
- One German Math path exists: Algebra Foundations.
- One LearningNode, at least one numeric Task, and one active TaskVersion exist.
- `GET /api/today` returns at most three actions, and task attempt start/submit returns deterministic feedback without answer leaks.
- Incorrect, unsure, or skipped work creates a review; the review can be answered; progress summary updates.
- One backend `MvpLearningLoopTest` exists with focused grading, review scheduling, Today selection, ownership, and no-answer-leak coverage.
- One frontend smoke flow may exist against this real subset or MSW aligned to this subset.
- No XP, badges, achievements, streaks, streak freezes, leaderboards, reward levels, catch-up flows, lost-progress fields, or backlog-pressure UI are introduced.
- B4 Completion States are neutral and tied to real task or review actions.

### B4 API Hardening

Status: **completed**

B4 completed:

- Full `docs/05_API_SPEC.md` route hardening in backend routes.
- API tests for the full route set.
- Validation coverage for malformed requests.
- Ownership isolation coverage for learner-owned state.
- No answer leaks from task or review read APIs.
- Deterministic grading, review scheduling, Today selection, and progress updates.
- Motivation Without Pressure guardrails preserved across Today, Review, Progress, and copy fixtures.
- Content validation tooling for the seeded SkillGraph content.
- Sanctum SPA session auth with credentialed CORS and stateful frontend-domain hardening.
- Documentation aligned to implemented behavior.

### Fun Without Pressure Priority

This priority exists across V1, B4, and Later work. Its purpose is to make Nuvio pleasant to open by reducing resistance and making real competence visible, without adding reward pressure.

V1:

- Better copy. Purpose: reduce opening friction. Expected effect: Today feels calmer. Pressure risk: generic softness. Guardrail: copy must stay fachlich concrete.
- Concrete Today titles. Purpose: make the first action obvious. Expected effect: less planning. Pressure risk: long labels. Guardrail: wrap cleanly and keep estimated minutes visible.
- Decoupled feedback. Purpose: create small Aha moments. Expected effect: "Ich lerne wirklich." Pressure risk: content overhead. Guardrail: feedback comes from TaskVersion or FeedbackPolicy.
- Equal Unsure/Skip rendering. Purpose: support honest self-regulation. Expected effect: less shame. Pressure risk: repeated avoidance. Guardrail: schedule Review neutrally.
- Return to Today. Purpose: close the loop. Expected effect: less navigation chaos. Pressure risk: repetitive flow. Guardrail: Feedback explains the next state first.

B4:

- Completion States. Purpose: provide closure. Pressure risk: mini-badges. Guardrail: only after real learning action.
- Energy Mode. Purpose: support low-capacity days. Pressure risk: red/yellow/green as judgment. Guardrail: red means low energy, not danger.
- Snooze. Purpose: support autonomy. Pressure risk: avoidance loop. Guardrail: no Mastery improvement.
- Mastery Moments. Purpose: make retained knowledge visible. Pressure risk: reward loop. Guardrail: only after real retention evidence.
- Better review intervals. Purpose: improve durable learning. Pressure risk: black box. Guardrail: explainable scheduling rules.
- Mini-choice after feedback. Purpose: give autonomy. Pressure risk: too many options. Guardrail: maximum three options.

Later:

- Skill-Map. Purpose: competence overview. Pressure risk: dashboard. Guardrail: secondary to Today.
- Interactive algebra and simulations. Purpose: fun from thinking. Pressure risk: feature creep. Guardrail: only after the core loop is stable.
- Challenge Choices. Purpose: learning choice. Pressure risk: performance pressure. Guardrail: similar, slightly harder, short review, or done.
- Constrained AI teacher. Purpose: explanation. Pressure risk: chat dependency. Guardrail: bound to current Task, Node, and Path.

## 4. Scope Rules

Backend MVP means full API capability and B4 hardening. It remains API-only and is not a production or user-facing release.

Frontend application work may start after explicit confirmation because the V1 API subset exists in backend routes and tests. Full B4 remains required before Private Alpha readiness.

V1 includes:

- Auth or a pre-provisioned learner.
- Today with no more than three actions.
- German learning content and backend feedback strings.
- Algebra Foundations seed content.
- Start path, one numeric task, attempts, reviews, and progress summary.
- German UI defaults if frontend work begins.

B4 adds:

- Full canonical API route set from `docs/05_API_SPEC.md`.
- Preferences and Today mode persistence if deferred from V1.
- Node listing/detail/task/prerequisite APIs.
- Review due/snooze and path progress APIs.
- Multiple choice grading.
- B4 Completion States and Mastery Moments after real learning evidence.
- Full validation, ownership, and route tests.

V1 and B4 exclude:

- Dashboards.
- More than three Today actions.
- `short_text`.
- `self_check`.
- Skill-Map.
- Challenge Options.
- Equation transformation and richer interactive Algebra tasks.
- `concept` as an MVP LearningNode type.
- LearningSessions.
- Custom tasks.
- AI.
- Simulations.
- Gamification, streaks, XP, badges, or achievements.
- Streak freezes, leaderboards, reward levels, catch-up flows, lost-progress states, or backlog pressure.
- Comeback streaks, countdown pressure, artificial scarcity, lootbox-like reveals, or attendance rewards.
- Payments.
- Community features.
- Mobile app.
- CMS.
- Public signup.

## 5. Readiness Requirements

Backend tests required before V1:

- `MvpLearningLoopTest`.
- `TaskGraderTest` for numeric grading.
- `ReviewSchedulerTest` for basic review creation.
- `TodaySelectorTest` for max-three and review priority.
- Ownership/no-answer-leak smoke tests.

Additional backend tests required before B4:

- Auth and preference tests.
- Model relationship and seed/content validation tests.
- Task grading, task attempt, review scheduling, and Today selector tests.
- API tests for every route in `docs/05_API_SPEC.md`.
- Ownership isolation tests.
- Validation error tests.

Frontend tests required for V1 frontend:

- Zod contract parsing.
- MSW fixtures for the V1 subset.
- Component tests for Today and Task.
- Basic API client error handling.
- One Playwright happy path or smoke flow.

Additional frontend hardening before F2:

- Component tests for all B4/F2 screens and states.
- Playwright coverage for Today cap, Start Path, correct/unsure/skip task flows, Review, Feedback, Settings language, German mobile layout, and API unavailable state.
- Real-backend smoke suite.

Public Beta readiness:

- Production deployment.
- Backups.
- Monitoring.
- Rollback path.
- Invite or waitlist signup with password reset.
- Privacy and support basics.
- No unresolved Alpha-blocking learning-loop defects.

## 6. Documentation Alignment

Keep these documents aligned with this roadmap:

- `README.md`.
- `docs/00_START_HERE.md`.
- `docs/01_PRD.md`.
- `docs/02_MVP_SCOPE.md`.
- `docs/06_CONTENT_AUTHORING.md`.
- `docs/10_IMPLEMENTATION_PLAN.md`.
- `docs/11_TEST_PLAN.md`.
- `docs/12_CODEX_WORKFLOW.md`.
- Frontend planning docs in `../frontend/docs`.

Known alignment decisions:

- MVP seed scope is one German Math path: Algebra Foundations.
- Additional subjects are post-MVP content expansion.
- MVP LearningNodes use `skill` only.
- MVP NodeRelations accept `prerequisite` only.
- V1 task type is `numeric`.
- B4 task type is `multiple_choice`.
- `self_check`, `short_text`, Skill-Map, Challenge Options, and equation transformation are later capabilities.
- Content validation is a required Backend MVP gate.
- Existing implementation groupings should be called ticket groups or implementation stages, not product phases.
