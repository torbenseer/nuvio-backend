# Nuvio MVP Scope

## 1. MVP Objective

The Backend MVP is a Laravel API backend that proves Nuvio's core learning loop:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

The MVP must be intentionally small. It should validate one complete backend learning loop before proving subject breadth, simulations, gamification, custom content, or AI.

The MVP should not try to prove every long-term product idea.

Terminology:

- **V1 Integrated Learning Loop**: the first thin product slice through backend and frontend.
- **Backend MVP**: the full API capability and B4 hardening gate.
- **Product MVP**: a broader product-readiness label; do not use it as a synonym for Backend MVP.

V1 exists so integrated product learning can start before the full Backend MVP route set is complete. It does not weaken the full Backend MVP gate.

## 2. MVP User Story

As an adult learner, I can enroll in a learning path, open Nuvio, see at most three recommended actions for today, complete a task, get feedback, and have my progress and reviews updated automatically.

Acceptance expectations:

- The learner does not need to browse a large dashboard.
- The learner always has a clear next action when enrolled content is available.
- Incorrect, unsure, or skipped attempts create review work.
- Progress reflects practice and review outcomes, not only time spent.
- The learner can pause, return, skip, mark unsure, or snooze without losing progress or being framed as behind.
- Motivation comes from competence feedback and clear structure, not XP, badges, streaks, ranks, or daily pressure.

## 3. Must-Have Features

### Laravel API Backend

- API-only Laravel application.
- Authentication for learner-owned state.
- Database-backed content and progress.
- Feature and service tests for core learning behavior.
- V1 may use a pre-provisioned learner before the full auth/preferences surface is hardened.

### Subjects

- Store broad subject areas.
- MVP seed content uses one German Math subject path only.
- Additional subjects are later content expansion.

### LearningNodes

- Store graph nodes used by SkillGraph.
- MVP node type is `skill`.
- Nodes can belong to one or more subjects through the subject-node relationship.

### NodeRelations

- Store directed graph relationships between LearningNodes.
- MVP relation type is `prerequisite`.
- Later relation types may support project, career, simulation, or remediation views.

### LearningPaths

- Store ordered paths through LearningNodes.
- MVP includes one subject-focused LearningPath.

### Enrollments

- A user can enroll in a LearningPath.
- Enrollment state is used by the today action selector.

### Tasks

- Store practice prompts linked to LearningNodes.
- V1 task type: `numeric`.
- B4 task type: `multiple_choice`.
- Later task types: `self_check` and richer interactive Algebra formats.
- Tasks are the unit users attempt.

### TaskVersions

- Store versioned task content and answer schemas.
- TaskAttempts must point to the TaskVersion attempted.
- This preserves attempt history when task content changes.

### TaskAttempts

- Store immutable user attempts.
- A TaskAttempt is created when the learner starts a task.
- Submitting completes the existing TaskAttempt. Self-check is later.
- Supported outcomes: `correct`, `incorrect`, `unsure`, `skipped`.
- Attempts drive feedback, reviews, and mastery updates.

### LearningSessions

- LearningSessions are not required for the narrow MVP.
- A later phase may add lightweight LearningSessions to group attempts.
- A LearningSession is distinct from Laravel's technical `sessions` table.

### Reviews

- Store scheduled review work generated from `incorrect`, `unsure`, or `skipped` attempts.
- Due reviews must be prioritized by the today action selector.

### MasteryStates

- Store per-user mastery state for a LearningNode.
- Mastery should reflect practice and review performance, not time alone.
- MVP states are `unknown`, `practiced`, `review_due`, and `retained`.

### Today Action Selector

- Returns at most three recommended actions for the authenticated user.
- Selects actions deterministically:
  1. Due Reviews ordered by `due_at`.
  2. Start Path when the user has no active enrollment.
  3. The next Task in the active LearningPath.
- Energy Mode and persisted Today mode are B4.

### Basic Progress Summary

- Returns simple progress data for a learner.
- Includes active paths, practiced nodes, review-due nodes, retained nodes, and due review count.
- Does not include XP, badge, achievement, streak, streak freeze, leaderboard, reward level, or loss-state fields.
- Due review counts are compact status in Progress only, not backlog debt.

### Completion States

- B4 may return short `completion_state` metadata after a TaskAttempt or Review answer.
- Completion States must come from real learning actions.
- Allowed examples: "Schritt abgeschlossen", "Lineare Gleichungen: geübt", "Review geplant", "Behalten".
- Completion States must not create XP, badges, streaks, level unlocks, or reward inventory.

### Motivation Without Pressure

- Today provides a clear next action instead of a dashboard.
- Small tasks and reviews create competence feedback. Completion States are B4.
- MasteryStates show competence status: `unknown`, `practiced`, `review_due`, `retained`.
- Review is normal learning work.
- Unsure, Skip, and Snooze are legitimate learner actions.
- Missed days do not create loss, debt, repair flows, or punitive copy.

### Playful Without Pressure

- Nuvio may feel playful through learning interaction, visible competence, SkillGraph state, and B4 Mastery Moments.
- V1 remains numeric-task only and does not add a large interaction engine.
- Skill-Map is Later, secondary to Today, and uses competence state only.
- Challenge Options are Later.
- Interactive Algebra such as equation transformations, term balancing, error marking, and graph manipulation is Later. Equation transformation is the first planned interactive Algebra format.

### Fun Without Pressure Scope

V1 stays small. The smallest meaningful V1 improvement is better opening and feedback copy: concrete Today titles, neutral Review framing, visible Unsure/Skip, and a reliable return to Today.

V1:

- Improve calm Today copy.
- Make Task titles concrete.
- Neutralize and task-scope feedback text.
- Render Unsure and Skip as visible legitimate actions.
- Return to Today after every attempt.
- Show no backlog list.
- Add no gamification fields.

B4:

- Completion States.
- Energy Mode.
- Snooze.
- Better review intervals.
- Small Mastery Moments after real retained evidence.
- Optional "Was jetzt?" choice after feedback, max three options.
- Better Progress Summary without percent pressure.
- Tests against forbidden motivation mechanics.

Later:

- Skill-Map.
- Interactive algebra.
- Simulations.
- Projects.
- Constrained AI teacher.
- Challenge choices with similar or harder tasks.
- Graph manipulation.
- Equation transformations.
- Error-location marking.

Explicitly out of V1:

- Energy Mode, Snooze, Completion States, Mastery Moments, Skill-Map, Challenge Options, broad path browser, AI, simulations, projects, interactive algebra, review history, and any reward or daily-pressure system.

### Seed Content Import Or Seeders

- The MVP must include enough seed content to exercise the core loop.
- Seed content can use Laravel seeders or structured import files.

### Required Tests

- Review scheduling tests.
- Today action selection tests.
- Task attempt and grading tests.
- Mastery state update tests.

## 4. Should-Have Features

No should-have features are part of the narrow MVP.

Anything outside the Today -> TaskAttempt -> Review -> Mastery -> Progress loop must wait until the MVP is implemented and tested.

## 5. Could-Have Features

These are useful but not required for MVP completion:

- Basic admin-only content listing endpoints.
- Lightweight activity log.
- B4 path progress by node state counts, without percent pressure.
- Review history endpoint.
- Task difficulty metadata.
- Support for task hints stored in TaskVersions.

## 6. Explicitly Out Of Scope

The MVP must not include:

- Full AI teacher.
- AI endpoints.
- AiInteraction logging tables.
- User-created tasks.
- Custom task APIs.
- Gamification implementation.
- XP events.
- Achievements or badges.
- Streaks or streak freezes.
- Leaderboards, ranks, leagues, or social comparison.
- Level ladders used as reward progression.
- Daily pressure, catch-up, behind, failed, or lost-progress mechanics.
- Confetti or reward loops after each task.
- Backlog numbers presented as pressure signals.
- Simulation endpoints.
- SimulationDefinition and SimulationRun tables.
- Payment.
- Subscriptions.
- Community features.
- Mobile app.
- Complex admin CMS.
- Large simulation engine.
- Career marketplace.
- Full graph visualization.
- Team or classroom management.
- Certificates.
- Public sharing of user-created tasks.
- Advanced adaptive learning algorithms.

## 7. MVP Data Requirements

The MVP data model must support these core records:

- `users`
- `subjects`
- `learning_nodes`
- `node_relations`
- `learning_paths`
- `learning_path_nodes`
- `enrollments`
- `tasks`
- `task_versions`
- `task_attempts`
- `reviews`
- `mastery_states`

Later records, not part of the MVP:

- `learning_sessions`
- `simulation_definitions`
- `simulation_runs`
- `ai_interactions`

Forbidden records for V1 and B4:

- `xp_events`
- `achievements`
- `streaks`
- `streak_freezes`
- `leaderboard_entries`

Key data rules:

- A LearningNode belongs to one or more Subjects.
- A NodeRelation connects two LearningNodes.
- A LearningPath contains ordered LearningNodes.
- A Task links to one or more LearningNodes.
- A TaskVersion belongs to a Task.
- A TaskAttempt belongs to a user, Task, TaskVersion, and optionally Review.
- A Review belongs to a user and LearningNode, and may point to a Task.
- A MasteryState belongs to a user and LearningNode.

## 8. MVP Content Requirements

Seed content must cover one German subject path only.

Minimum useful seed target:

- 1 Subject.
- 1 LearningPath.
- V1: 1 LearningNode and 1 numeric Task.
- B4: 3 LearningNodes and 2 Tasks per LearningNode.
- B4: at least one prerequisite NodeRelation where appropriate.

Recommended first path:

- Math: Algebra Foundations.
- V1 task: solve `2x + 3 = 11`, answer `4`, tolerance `0`.

Content validation:

- Seed content must be validated before it is accepted as MVP-ready.
- Validation must protect slugs, LearningNode references, NodeRelations, task schemas, and TaskVersion requirements.

Example tasks:

- Math: solve `2x + 3 = 11`.

## 9. MVP Endpoints Overview

This is a capability overview. `docs/05_API_SPEC.md` is canonical for exact route names and response shapes.

Status labels:

- **V1 required**: needed for the first integrated vertical slice.
- **B4 hardening**: required for full Backend MVP API completeness before Private Alpha.

### Auth

- `GET /sanctum/csrf-cookie`
- `POST /login`
- `POST /logout`
- `GET /api/user`
- `PUT /api/user/preferences` (**B4 hardening**, unless implemented in V1)
- Backend registration support for API tests; no first-slice frontend signup or reset UI.

### Content

- `GET /api/learning-paths` (**B4 hardening**)
- `GET /api/learning-paths/{learningPath}` (**B4 hardening**)
- `GET /api/nodes` (**B4 hardening**)
- `GET /api/nodes/{learningNode}` (**B4 hardening**)
- `GET /api/nodes/{learningNode}/tasks` (**B4 hardening**)
- `GET /api/nodes/{learningNode}/prerequisites` (**B4 hardening**)
- `GET /api/tasks/{task}` (**V1 required**)

### Enrollment

- `POST /api/learning-paths/{learningPath}/start` (**V1 required**)

### Today

- `GET /api/today` (**V1 required**)
- `POST /api/today/mode` (**B4 hardening**)

Returns zero to three actions.

### Attempts

- `POST /api/task-attempts/start` (**V1 required**)
- `POST /api/task-attempts/{taskAttempt}/submit` (**V1 required**)
- `POST /api/task-attempts/{taskAttempt}/self-check` (**Later**)

### Reviews

- `GET /api/reviews/due` (**B4 hardening**)
- `GET /api/reviews/{review}` (**V1 required**)
- `POST /api/reviews/{review}/answer` (**V1 required**)
- `POST /api/reviews/{review}/snooze` (**B4 hardening**)

### Progress

- `GET /api/progress/summary` (**V1 required**)
- `GET /api/progress/paths/{learningPath}` (**B4 hardening**)
- `GET /api/progress/paths/{learningPath}/skill-map` (**Later**)

## 10. V1 Definition Of Done

V1 is done when:

- One user can start one Algebra Foundations path.
- Today returns at most three actions and reviews are prioritized before new learning.
- One numeric task can be attempted.
- Feedback is returned.
- Incorrect, unsure, or skipped work creates review work.
- A review can be answered and progress summary changes.
- Core behavior is covered by at least one end-to-end backend test.
- V1 responses do not expose `completion_state`, `mastery_moment`, `challenge_options`, `mastery_score`, `hidden_due_reviews`, `returning_after_break`, or Today Action `reason`.
- Progress summary exposes competence status only, with no XP, badge, streak, achievement, rank, reward level, or lost-progress fields.
- Feedback and Today copy can represent correct, incorrect, unsure, skipped, and review-created states without shame or daily obligation language.
- Hidden review backlog is omitted from V1 UI-facing API responses.

## 11. Backend MVP Acceptance Criteria

- A fresh database can migrate and seed.
- V1 seed content exists for one subject path with 1 LearningNode and 1 numeric Task.
- B4 seed content expands to 3 LearningNodes and 2 Tasks per node.
- Seed content validation protects references and task schemas.
- A user can register through the backend API and authenticate through the Sanctum SPA flow.
- A user can enroll in a LearningPath.
- `GET /api/today` returns at most three actions.
- Due reviews are selected before review-due nodes and new tasks.
- A user can fetch and attempt a Task.
- A TaskAttempt records the TaskVersion attempted.
- Correct attempts update MasteryState positively.
- Incorrect attempts create or update Reviews.
- Unsure attempts create or update Reviews.
- Skipped attempts create or update Reviews.
- Review attempts update Review scheduling.
- Basic progress summary reports practiced, review-due, retained, and due review counts.
- Tests cover review scheduling and today action selection.
- Skill-Map is Later and must show LearningNode competence states only when specified.
- Challenge choices are Later and must be specified separately before implementation.
- No API response exposes XP totals, badges, achievements, streak state, streak freezes, leaderboard rank, reward levels, catch-up debt, hidden backlog counts, missed-day counts, `mastery_score`, or lost-progress state.
- Snooze is supported where implemented as scheduling only; it does not improve mastery and is not a streak freeze.
- Copy fixtures or response examples avoid "catch up", "behind", "failed", "lost progress", and similar pressure language.

## 12. Backend MVP Definition Of Done

The Backend MVP is done when:

- All must-have data models and endpoints exist.
- Seed content can be loaded and validated in a fresh environment.
- The Today -> Task -> Attempt -> Feedback -> Review -> Progress loop works through API calls.
- Numeric and B4 multiple choice task outcomes are supported as documented. Self-check remains Later.
- Review scheduling, Today selection, and progress updates are deterministic and tested.
- Validation, authorization, ownership isolation, and no-answer-leak behavior are covered.
- Motivation without pressure guardrails are preserved across V1 and B4 endpoints.
- Documentation reflects implemented behavior.

## 13. What Comes After Backend MVP

After the Backend MVP proves the core loop, later phases may add:

- Additional German Math content.
- Additional subjects.
- Project-based learning paths.
- Career paths built from LearningNodes.
- Content authoring tools.
- More advanced mastery and review models.

Later features must continue to use SkillGraph instead of creating separate learning models for each product surface.
