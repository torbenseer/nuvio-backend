# Nuvio MVP Scope

## 1. MVP Objective

The MVP is a Laravel API backend that proves Nuvio's core learning loop:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

The MVP must be intentionally small. It should validate one complete backend learning loop before proving subject breadth, simulations, gamification, custom content, or AI.

The MVP should not try to prove every long-term product idea.

## 2. MVP User Story

As an adult learner, I can enroll in a learning path, open Nuvio, see at most three recommended actions for today, complete a task, get feedback, and have my progress and reviews updated automatically.

Acceptance expectations:

- The learner does not need to browse a large dashboard.
- The learner always has a clear next action when enrolled content is available.
- Incorrect, unsure, or skipped attempts create review work.
- Progress reflects practice and review outcomes, not only time spent.

## 3. Must-Have Features

### Laravel API Backend

- API-only Laravel application.
- Authentication for learner-owned state.
- Database-backed content and progress.
- Feature and service tests for core learning behavior.

### Subjects

- Store broad subject areas.
- MVP seed content uses one subject only.
- Additional subjects such as physics, electrical engineering, and chemistry are later content expansion.

### LearningNodes

- Store graph nodes used by SkillGraph.
- MVP node type is `skill`.
- Nodes can belong to one or more subjects through the subject-node relationship.

### NodeRelations

- Store directed graph relationships between LearningNodes.
- MVP relation types should include `prerequisite`.
- Later relation types may support project, career, simulation, or remediation views.

### LearningPaths

- Store ordered paths through LearningNodes.
- MVP includes one subject-focused LearningPath.

### Enrollments

- A user can enroll in a LearningPath.
- Enrollment state is used by the today action selector.

### Tasks

- Store practice prompts linked to LearningNodes.
- MVP task types: `numeric`, `multiple_choice`, and `self_check`.
- Tasks are the unit users attempt.

### TaskVersions

- Store versioned task content and answer schemas.
- TaskAttempts must point to the TaskVersion attempted.
- This preserves attempt history when task content changes.

### TaskAttempts

- Store immutable user attempts.
- A TaskAttempt is created when the learner starts a task.
- Submitting or self-checking completes the existing TaskAttempt.
- Supported outcomes: `correct`, `incorrect`, `unsure`, `skipped`.
- Attempts drive feedback, reviews, and mastery updates.

### Sessions

- Sessions are not required for the narrow MVP.
- A later phase may add lightweight sessions to group attempts.

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
  2. Review-due LearningNodes, represented by `review_due` MasteryStates.
  3. The next Task in the active LearningPath.
- Red mode filters for actions with `estimated_minutes <= 15` when possible.

### Basic Progress Summary

- Returns simple progress data for a learner.
- Includes active paths, practiced nodes, review-due nodes, retained nodes, and due review count.

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

- Content validation command for seed files.
- Basic admin-only content listing endpoints.
- Lightweight activity log.
- Path progress percentage.
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

- `sessions`
- `xp_events`
- `achievements`
- `simulation_definitions`
- `simulation_runs`
- `ai_interactions`

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

Seed content must cover one subject path only.

Minimum useful seed target:

- 1 Subject.
- 1 LearningPath.
- 3 to 5 LearningNodes.
- 2 to 3 Tasks per LearningNode.
- At least one prerequisite NodeRelation where appropriate.

Recommended first path:

- Math: Algebra Foundations.

Example tasks:

- Math: solve `2x + 3 = 11`.

## 9. MVP Endpoints Overview

Exact route names can change, but the MVP API must support these capabilities.

### Auth

- `POST /api/register`
- `POST /api/login`
- `POST /api/logout`
- `GET /api/me`

### Content

- `GET /api/subjects`
- `GET /api/subjects/{subject}/learning-paths`
- `GET /api/learning-paths/{learningPath}`
- `GET /api/tasks/{task}`

### Enrollment

- `POST /api/enrollments`
- `GET /api/enrollments`

### Today

- `GET /api/today`
- `POST /api/today/mode`

Returns zero to three actions.

### Attempts

- `POST /api/task-attempts/start`
- `POST /api/task-attempts/{taskAttempt}/submit`
- `POST /api/task-attempts/{taskAttempt}/self-check`

### Reviews

- `GET /api/reviews/due`
- `POST /api/reviews/{review}/answer`
- `POST /api/reviews/{review}/snooze`

### Progress

- `GET /api/progress/summary`
- `GET /api/learning-paths/{learningPath}/progress`
- `GET /api/learning-nodes/{learningNode}/mastery`

## 10. MVP Acceptance Criteria

- A fresh database can migrate and seed.
- Seed content exists for one subject path with 3 to 5 LearningNodes and 2 to 3 Tasks per node.
- A user can register and authenticate.
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

## 11. Definition Of Done

The MVP is done when:

- All must-have data models exist.
- All must-have endpoints are implemented.
- Seed content can be loaded in a fresh environment.
- The Today -> Task -> Attempt -> Feedback -> Review -> Progress loop works through API calls.
- Task grading works for multiple choice and numeric input.
- Self-check attempts can record `correct`, `incorrect`, `unsure`, or `skipped`.
- Review scheduling is deterministic and tested.
- Today action selection is deterministic, capped at three, and tested.
- Basic progress summary is available.
- API validation prevents malformed attempts.
- Authorization prevents users from accessing another user's private state.
- Documentation reflects implemented behavior.

## 12. What Comes After MVP

After the MVP proves the core loop, later phases may add:

- Additional subjects such as physics, electrical engineering, chemistry, biology, and computer science.
- User-created tasks.
- Simple XP events.
- Achievements.
- Simulation definitions and runs.
- Richer simulations.
- Project-based learning paths.
- Career paths built from LearningNodes.
- Content authoring tools.
- Constrained AI teacher for hints and explanations.
- More advanced mastery and review models.

Later features must continue to use SkillGraph instead of creating separate learning models for each product surface.
