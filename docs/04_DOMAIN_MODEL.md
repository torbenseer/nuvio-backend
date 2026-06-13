# Nuvio Domain Model

Nuvio is a Laravel API backend for a Duolingo-like adult learning platform. Its core MVP domain is SkillGraph: a learning graph where subjects, learning paths, tasks, attempts, reviews, and mastery states are connected through reusable learning nodes.

Career paths, projects, simulations, gamification, custom tasks, and AI are later phases. They must not add MVP tables, endpoints, or migrations unless explicitly requested.

The MVP should use relational tables and Eloquent models. A graph database is not required.

## 1. Entity Relationship Overview

Core relationships:

- A User has many Enrollments, TaskAttempts, Reviews, and MasteryStates.
- A Subject has many LearningNodes through a pivot table.
- A LearningNode can belong to multiple Subjects.
- A LearningNode can relate to other LearningNodes through NodeRelations.
- A LearningPath consists of ordered LearningNodes through LearningPathNode.
- A User enrolls in a LearningPath through Enrollment.
- A Task can belong to multiple LearningNodes through TaskNode.
- A Task has many TaskVersions.
- A TaskAttempt points to the TaskVersion attempted.
- Incorrect, unsure, or skipped TaskAttempts create or update Reviews.
- A MasteryState stores one user's progress for one LearningNode.

## 2. Core Entities

### User

Purpose:

- Represents a learner account.

Important fields:

- `id`
- `name`
- `email`
- `password`
- `created_at`
- `updated_at`

Relationships:

- Has many Enrollments.
- Has many TaskAttempts.
- Has many Reviews.
- Has many MasteryStates.

Notes:

- Use Laravel's default User model as the base.
- Keep learning state in separate tables.
- Later phases may add Sessions, SimulationRuns, Achievements, XpEvents, AiInteractions, and owned Tasks.

### Subject

Purpose:

- Represents a broad area of learning, such as math or chemistry.

Important fields:

- `id`
- `slug`
- `name`
- `description`
- `sort_order`
- `is_active`

Relationships:

- Belongs to many LearningNodes.
- Has many LearningPaths.

Notes:

- A LearningNode can belong to multiple Subjects, so do not store `subject_id` directly on `learning_nodes` as the only subject relationship.

Example record:

```json
{
  "slug": "electrical-engineering",
  "name": "Electrical Engineering",
  "description": "Circuit foundations and applied electronics.",
  "is_active": true
}
```

### LearningNode

Purpose:

- Represents a reusable unit in SkillGraph.
- Usually a skill in the MVP, but flexible enough for concepts, project steps, or career competencies later.

Important fields:

- `id`
- `slug`
- `type`
- `title`
- `description`
- `level`
- `estimated_minutes`
- `is_active`

Relationships:

- Belongs to many Subjects.
- Has many outgoing NodeRelations.
- Has many incoming NodeRelations.
- Belongs to many LearningPaths through LearningPathNode.
- Belongs to many Tasks through TaskNode.
- Has many MasteryStates.
- Has many Reviews.

Notes:

- MVP node type should include `skill`.
- Future node types may include `concept`, `project_step`, `career_competency`, or `simulation_goal`.
- Do not add future node types to the MVP enum until a feature needs them.

Example record:

```json
{
  "slug": "apply-ohms-law",
  "type": "skill",
  "title": "Apply Ohm's law",
  "description": "Use V = I R to calculate voltage, current, or resistance.",
  "level": 1
}
```

### NodeRelation

Purpose:

- Represents a directed relationship between two LearningNodes.

Important fields:

- `id`
- `source_node_id`
- `target_node_id`
- `type`
- `strength`
- `metadata` JSON

Relationships:

- Belongs to source LearningNode.
- Belongs to target LearningNode.

Supported relation types:

- `prerequisite`
- `supports`
- `similar`
- `application`
- `transfer`

Notes:

- `prerequisite` means the source node should usually be learned before the target node.
- `supports` means the source helps with the target but is not mandatory.
- `similar` links related nodes.
- `application` links theory to use.
- `transfer` links knowledge across contexts or subjects.

Example record:

```json
{
  "source_node_id": 10,
  "target_node_id": 11,
  "type": "prerequisite",
  "strength": 1.0
}
```

### LearningPath

Purpose:

- Represents a guided sequence through LearningNodes.

Important fields:

- `id`
- `subject_id` nullable
- `slug`
- `type`
- `title`
- `description`
- `estimated_minutes`
- `is_active`

Relationships:

- Belongs to optional Subject.
- Has many LearningPathNodes.
- Belongs to many LearningNodes through LearningPathNode.
- Has many Enrollments.

Notes:

- MVP path type should include `subject_path`.
- Future types may include `career_path` and `project_path`.

Example record:

```json
{
  "slug": "circuit-fundamentals",
  "type": "subject_path",
  "title": "Circuit Fundamentals",
  "estimated_minutes": 240
}
```

### LearningPathNode

Purpose:

- Orders LearningNodes inside a LearningPath.

Important fields:

- `id`
- `learning_path_id`
- `learning_node_id`
- `position`
- `is_required`
- `metadata` JSON

Relationships:

- Belongs to LearningPath.
- Belongs to LearningNode.

Notes:

- The path order should guide Today selection.
- Do not duplicate node content in this table.

### Enrollment

Purpose:

- Represents a user's participation in a LearningPath.

Important fields:

- `id`
- `user_id`
- `learning_path_id`
- `status`
- `started_at`
- `completed_at`

Relationships:

- Belongs to User.
- Belongs to LearningPath.

Statuses:

- `active`
- `paused`
- `completed`

Notes:

- Enrollment should be idempotent for the same user and path.

### Task

Purpose:

- Represents a practice item that can be attempted.

Important fields:

- `id`
- `owner_user_id` nullable
- `slug` nullable
- `type`
- `status`
- `source`
- `difficulty`
- `estimated_minutes`

Relationships:

- Belongs to owner User when user-created.
- Belongs to many LearningNodes through TaskNode.
- Has many TaskVersions.
- Has many TaskAttempts.

Notes:

- Official tasks have `owner_user_id = null`.
- User-created tasks can be added later using the same table.
- The MVP does not expose custom task creation endpoints.
- Task content belongs in TaskVersion, not Task.

### TaskVersion

Purpose:

- Freezes task content and answer schema for attempts.

Important fields:

- `id`
- `task_id`
- `version`
- `prompt`
- `body` JSON nullable
- `answer_schema` JSON
- `explanation`
- `hint` nullable
- `is_active`
- `published_at`

Relationships:

- Belongs to Task.
- Has many TaskAttempts.

Notes:

- TaskAttempt must reference the TaskVersion used.
- If prompt or answer changes, create a new TaskVersion instead of mutating old attempt context.

Example record:

```json
{
  "task_id": 100,
  "version": 1,
  "prompt": "A 6 V battery is connected to a 3 ohm resistor. What current flows?",
  "answer_schema": {
    "type": "numeric",
    "correct_value": 2,
    "tolerance": 0.01,
    "unit": "A"
  },
  "explanation": "Current equals voltage divided by resistance, so 6 / 3 = 2 A."
}
```

### TaskNode

Purpose:

- Links a Task to one or more LearningNodes.

Important fields:

- `id`
- `task_id`
- `learning_node_id`
- `role`
- `weight`

Relationships:

- Belongs to Task.
- Belongs to LearningNode.

Notes:

- A Task can belong to multiple LearningNodes.
- Roles may include `primary`, `secondary`, or `prerequisite`.

### TaskAttempt

Purpose:

- Stores one user interaction with one TaskVersion.

Important fields:

- `id`
- `user_id`
- `task_id`
- `task_version_id`
- `review_id` nullable
- `status`
- `submitted_answer` JSON nullable
- `result` nullable
- `grading_method`
- `feedback` nullable
- `graded_payload` JSON nullable
- `started_at`
- `completed_at` nullable

Relationships:

- Belongs to User.
- Belongs to Task.
- Belongs to TaskVersion.
- Belongs to Review optionally.

Statuses:

- `started`
- `completed`

Results:

- `correct`
- `incorrect`
- `unsure`
- `skipped`

Notes:

- TaskAttempts are immutable evidence.
- A TaskAttempt is created when a learner starts a task.
- Submit or self-check completes the existing TaskAttempt.
- Incorrect, unsure, or skipped attempts must create or update Reviews.

### Session

Purpose:

- Later phase: groups user activity for a learning session.

Important fields:

- `id`
- `user_id`
- `energy_mode`
- `started_at`
- `ended_at`
- `metadata` JSON

Relationships:

- Belongs to User.
- Has many TaskAttempts.
- Has many SimulationRuns.

Energy modes:

- `red`
- `yellow`
- `green`

Notes:

- Sessions are not required for the narrow MVP.
- Red mode should keep work to 15 minutes or less.
- Sessions should remain lightweight when implemented later.

### Review

Purpose:

- Stores scheduled review work for a user and LearningNode, optionally tied to a Task.

Important fields:

- `id`
- `user_id`
- `learning_node_id`
- `task_id` nullable
- `status`
- `due_at` nullable when completed
- `interval_days`
- `ease`
- `lapses`
- `last_attempted_at`

Relationships:

- Belongs to User.
- Belongs to LearningNode.
- Belongs to Task optionally.
- Has many TaskAttempts.

Statuses:

- `scheduled`
- `completed`
- `suspended`

Notes:

- Due reviews are computed as `status = scheduled` and `due_at <= now()`.
- Do not store a separate `due` status in the MVP.
- Avoid unlimited duplicate reviews for the same user, node, and task.

### MasteryState

Purpose:

- Stores progress for one User and one LearningNode.

Important fields:

- `id`
- `user_id`
- `learning_node_id`
- `status`
- `mastery_score`
- `correct_attempts`
- `incorrect_attempts`
- `unsure_attempts`
- `last_practiced_at`
- `retained_at`

Relationships:

- Belongs to User.
- Belongs to LearningNode.

Statuses:

- `unknown`
- `practiced`
- `review_due`
- `retained`

Notes:

- Progress is based on practice, retention, and review outcomes, not time alone.
- Use one MasteryState per user and LearningNode.
- Richer states such as `mastered` and `transfer_ready` are later-phase states.

### SimulationDefinition

Purpose:

- Later phase: defines a reusable simulation linked to SkillGraph.

Important fields:

- `id`
- `slug`
- `title`
- `description`
- `provider`
- `launch_config` JSON
- `is_active`

Relationships:

- Belongs to many LearningNodes through a pivot table such as `simulation_definition_nodes`.
- Has many SimulationRuns.

Notes:

- Not part of the narrow MVP.
- SimulationDefinition is reusable across users.

### SimulationRun

Purpose:

- Later phase: stores one user interaction with a simulation.

Important fields:

- `id`
- `user_id`
- `simulation_definition_id`
- `session_id` nullable
- `status`
- `started_at`
- `completed_at`
- `input_payload` JSON nullable
- `result_payload` JSON nullable

Relationships:

- Belongs to User.
- Belongs to SimulationDefinition.
- Belongs to Session optionally.

Notes:

- Not part of the narrow MVP.
- SimulationRun does not replace TaskAttempt.
- Simulation progress should update MasteryState only through explicit rules.

### Achievement

Purpose:

- Later phase: represents a durable user achievement or badge.

Important fields:

- `id`
- `user_id`
- `type`
- `key`
- `title`
- `description`
- `awarded_at`
- `metadata` JSON

Relationships:

- Belongs to User.

Notes:

- Not part of the narrow MVP.
- Avoid shame-based streak achievements.
- Achievements should recognize meaningful learning evidence.

### XpEvent

Purpose:

- Later phase: stores event-based gamification points.

Important fields:

- `id`
- `user_id`
- `source_type`
- `source_id`
- `points`
- `reason`
- `created_at`

Relationships:

- Belongs to User.
- Morphs to source model if polymorphic sources are used.

Notes:

- Not part of the narrow MVP.
- Gamification should be event-based.
- XP should not be the source of truth for mastery.

### AiInteraction Later Phase

Purpose:

- Logs constrained AI teacher interactions.

Important fields:

- `id`
- `user_id`
- `task_id` nullable
- `task_version_id` nullable
- `learning_node_id` nullable
- `intent`
- `user_message`
- `response`
- `safety_status`
- `context_payload` JSON
- `created_at`

Relationships:

- Belongs to User.
- Belongs to Task optionally.
- Belongs to TaskVersion optionally.
- Belongs to LearningNode optionally.

Notes:

- Not part of the core MVP.
- AI interactions must be constrained to current task, node, path, and safety rules.
- AI must not be the deterministic grader.

## 3. Suggested Laravel Model Names

- `User`
- `Subject`
- `LearningNode`
- `NodeRelation`
- `LearningPath`
- `LearningPathNode`
- `Enrollment`
- `Task`
- `TaskVersion`
- `TaskNode`
- `TaskAttempt`
- `Session`
- `Review`
- `MasteryState`

Later-phase model names:

- `SimulationDefinition`
- `SimulationRun`
- `Achievement`
- `XpEvent`
- `AiInteraction`

## 4. Suggested Database Tables

Required MVP tables:

- `users`
- `subjects`
- `learning_nodes`
- `learning_node_subject`
- `node_relations`
- `learning_paths`
- `learning_path_nodes`
- `enrollments`
- `tasks`
- `task_versions`
- `task_nodes`
- `task_attempts`
- `reviews`
- `mastery_states`

Later tables, not part of the MVP:

- `sessions`
- `simulation_definitions`
- `simulation_definition_nodes`
- `simulation_runs`
- `achievements`
- `xp_events`
- `ai_interactions`

## 5. Important Indexes

Recommended indexes:

- `subjects.slug` unique.
- `learning_nodes.slug` unique or unique per node type.
- `learning_node_subject(subject_id, learning_node_id)` unique.
- `node_relations(source_node_id, target_node_id, type)` unique.
- `learning_paths.slug` unique.
- `learning_path_nodes(learning_path_id, position)` unique.
- `learning_path_nodes(learning_path_id, learning_node_id)` unique.
- `enrollments(user_id, learning_path_id)` unique.
- `tasks(owner_user_id)`.
- `task_versions(task_id, version)` unique.
- `task_nodes(task_id, learning_node_id, role)` unique where practical.
- `task_attempts(user_id, task_id, started_at)`.
- `task_attempts(user_id, task_version_id)`.
- `task_attempts(review_id)`.
- `reviews(user_id, due_at)`.
- `reviews(user_id, learning_node_id, task_id, status)`.
- `mastery_states(user_id, learning_node_id)` unique.

Later indexes:

- `sessions(user_id, started_at)`.
- `simulation_runs(user_id, simulation_definition_id, started_at)`.
- `xp_events(user_id, created_at)`.
- `ai_interactions(user_id, created_at)` later.

## 6. Validation Rules

### Learning Graph

- A LearningNode must have a valid `type`, `title`, and `slug`.
- A LearningNode can belong to one or more Subjects.
- A NodeRelation cannot connect a node to itself.
- NodeRelation type must be one of `prerequisite`, `supports`, `similar`, `application`, or `transfer`.
- Duplicate NodeRelations for the same source, target, and type are not allowed.
- A LearningPathNode must reference an active LearningPath and LearningNode.
- Path positions must be unique within a LearningPath.

### Tasks

- A Task must have at least one TaskVersion before it is active.
- A Task should have at least one TaskNode.
- A TaskVersion must include a prompt and answer schema.
- Answer schema must match task type.
- TaskAttempt must reference the TaskVersion actually shown to the user.
- TaskAttempt result must be `correct`, `incorrect`, `unsure`, or `skipped`.

### Reviews And Mastery

- Incorrect, unsure, or skipped TaskAttempts must create or update a Review.
- A Review must reference a User and LearningNode.
- A MasteryState must be unique per User and LearningNode.
- Mastery score must stay within defined bounds, such as 0 to 100.

### Access Control

- Users can access official tasks.
- Users can access their own user-created tasks only after custom tasks are implemented.
- Users cannot access another user's private tasks, sessions, reviews, attempts, or mastery states.

## 7. MVP Simplifications

- Use a relational database for SkillGraph.
- Treat most LearningNodes as `skill` nodes.
- Use a simple integer mastery score.
- Use deterministic review intervals.
- Compute Today actions on request instead of persisting recommendations.
- Do not implement Sessions in the narrow MVP.
- Do not implement simulations or simulation metadata in the narrow MVP.
- Do not implement achievements or XP in the narrow MVP.
- Do not implement AiInteraction or AI endpoints in the narrow MVP.

## 8. Future Extensions

- Add biology and computer science Subjects.
- Add career paths as LearningPaths of type `career_path`.
- Add project-based paths as LearningPaths of type `project_path`.
- Add richer NodeRelation types if needed.
- Add multiple task grading strategies.
- Add user-created tasks and sharing workflow.
- Add reusable simulations and simulation goals.
- Add richer achievement rules.
- Add constrained AI teacher interactions with logging and safety checks.
- Add graph visualization as a separate read model, not as a replacement for core tables.
