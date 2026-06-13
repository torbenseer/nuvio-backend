# Nuvio Implementation Plan

Nuvio MVP is a Laravel API backend for a Duolingo-like adult learning platform. This plan is written for Codex to implement step by step.

Repository layout:

- Backend code lives in the independent Git repository at `backend/`.
- Future frontend code lives in the independent Git repository at `frontend/`.
- Backend implementation paths in this document are relative to the `backend/` repository root.
- Do not add frontend application code to the backend repository.

Do not include frontend implementation beyond API support. Do not implement custom tasks, simulations, gamification, AI endpoints, payments, community features, mobile apps, or a complex admin CMS in the MVP.

## Phase 0: Project Setup

### Ticket 0.1: Create Laravel API Foundation

Goal:

- Set up the Laravel API project, test environment, and baseline configuration.

Files likely affected:

- `composer.json`
- `.env.example`
- `config/*.php`
- `routes/api.php`
- `tests/TestCase.php`

Implementation notes:

- Use Laravel API conventions.
- Add Sanctum if authentication is included in the first slice.
- Configure test database.

Acceptance criteria:

- Test suite runs.
- Migrations run successfully.
- API routes file exists.

Tests to add:

- Basic application boot test.

Out of scope:

- Frontend.
- Docker complexity unless already used.

## Phase 1: Core Domain Models And Migrations

### Ticket 1.1: Add SkillGraph Core Tables

Goal:

- Add migrations and models for Subjects, LearningNodes, NodeRelations, LearningPaths, and LearningPathNodes.

Files likely affected:

- `database/migrations/*_create_subjects_table.php`
- `database/migrations/*_create_learning_nodes_table.php`
- `database/migrations/*_create_learning_node_subject_table.php`
- `database/migrations/*_create_node_relations_table.php`
- `database/migrations/*_create_learning_paths_table.php`
- `database/migrations/*_create_learning_path_nodes_table.php`
- `app/Models/Subject.php`
- `app/Models/LearningNode.php`
- `app/Models/NodeRelation.php`
- `app/Models/LearningPath.php`
- `app/Models/LearningPathNode.php`

Implementation notes:

- A LearningNode can belong to multiple Subjects through a pivot table.
- NodeRelation supports `prerequisite`, `supports`, `similar`, `application`, and `transfer`.
- LearningPath consists of ordered LearningNodes.

Acceptance criteria:

- Migrations run successfully.
- Models and relationships are tested.
- A LearningNode can belong to multiple Subjects.
- LearningPath nodes preserve order.

Tests to add:

- `LearningNodeRelationshipTest`
- `LearningPathRelationshipTest`
- `NodeRelationTest`

Out of scope:

- Graph visualization.
- Graph database.

### Ticket 1.2: Add Learner State Tables

Goal:

- Add Enrollments, Reviews, and MasteryStates.

Files likely affected:

- `database/migrations/*_create_enrollments_table.php`
- `database/migrations/*_create_reviews_table.php`
- `database/migrations/*_create_mastery_states_table.php`
- `app/Models/Enrollment.php`
- `app/Models/Review.php`
- `app/Models/MasteryState.php`

Implementation notes:

- MasteryState is unique per user and LearningNode.
- Review belongs to user and LearningNode, optionally Task.
- Mastery statuses are `unknown`, `practiced`, `review_due`, and `retained`.

Acceptance criteria:

- Migrations run successfully.
- User relationships to enrollments, reviews, and mastery states work.

Tests to add:

- `LearnerStateRelationshipTest`

Out of scope:

- Review scheduling logic.
- Today selector.

## Phase 2: Seed Data And Content Structure

### Ticket 2.1: Add MVP Seed Content

Goal:

- Seed one MVP subject path.

Files likely affected:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/SubjectSeeder.php`
- `database/seeders/LearningGraphSeeder.php`
- Optional `content/**/*.yaml`

Implementation notes:

- Seed at least one LearningPath per subject.
- Seed 3 to 5 LearningNodes and prerequisite NodeRelations where useful.
- Seed 2 to 3 Tasks per LearningNode.
- Keep content deliberately small.

Acceptance criteria:

- Fresh database can be seeded.
- Seeded path contains ordered LearningNodes.
- One initial subject exists.

Tests to add:

- `SeedContentTest`

Out of scope:

- Large curriculum.
- Admin CMS.

### Ticket 2.2: Add Optional Content Validation Skeleton

Goal:

- Provide an artisan command or service shape for validating MVP seed content.

Files likely affected:

- `app/Console/Commands/ValidateContentCommand.php`
- `app/Console/Commands/ImportContentCommand.php`
- `app/Services/ContentImport/*`

Implementation notes:

- This should be validation-only unless import is explicitly requested.
- Validate slugs, references, and task schemas once tasks exist.

Acceptance criteria:

- Command can report invalid content without publishing or mutating content.

Tests to add:

- `ContentValidationTest`

Out of scope:

- Full CMS.

## Phase 3: Learning Paths And Enrollments

### Ticket 3.1: Add Learning Path API

Goal:

- Implement read endpoints for LearningPaths.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/LearningPathController.php`
- `app/Http/Resources/LearningPathResource.php`
- `app/Http/Resources/LearningNodeResource.php`

Implementation notes:

- Implement `GET /api/learning-paths`.
- Implement `GET /api/learning-paths/{id}`.

Acceptance criteria:

- Paths list successfully.
- Path detail includes ordered LearningNodes.

Tests to add:

- `LearningPathApiTest`

Out of scope:

- Creating paths via API.

### Ticket 3.2: Add Enrollment Start API

Goal:

- Allow a user to start a LearningPath.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/EnrollmentController.php`
- `app/Http/Resources/EnrollmentResource.php`
- `app/Http/Requests/StartLearningPathRequest.php`

Implementation notes:

- Implement `POST /api/learning-paths/{id}/start`.
- Implement `GET /api/enrollments/{id}/progress` later or stub progress until MasteryStates are complete.
- Starting the same path twice should be idempotent.

Acceptance criteria:

- A user can start a LearningPath.
- Duplicate start returns existing Enrollment.

Tests to add:

- `StartLearningPathTest`

Out of scope:

- Paid paths.
- Team enrollments.

## Phase 4: Tasks And Task Versions

### Ticket 4.1: Add Task Tables And Models

Goal:

- Add Tasks, TaskVersions, and TaskNodes.

Files likely affected:

- `database/migrations/*_create_tasks_table.php`
- `database/migrations/*_create_task_versions_table.php`
- `database/migrations/*_create_task_nodes_table.php`
- `app/Models/Task.php`
- `app/Models/TaskVersion.php`
- `app/Models/TaskNode.php`

Implementation notes:

- A Task can belong to multiple LearningNodes.
- TaskVersion freezes prompt, answer schema, explanation, and choices.

Acceptance criteria:

- Migrations run successfully.
- A Task can belong to multiple LearningNodes.
- TaskVersion relationship works.

Tests to add:

- `TaskRelationshipTest`
- `TaskVersionTest`

Out of scope:

- Rich media task types.

### Ticket 4.2: Add Task Read API

Goal:

- Implement task retrieval without exposing answers.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/TaskController.php`
- `app/Http/Resources/TaskResource.php`

Implementation notes:

- Implement `GET /api/tasks/{id}`.
- Include active TaskVersion ID.
- Do not expose answer schema.

Acceptance criteria:

- Task can be fetched.
- Correct answer is not exposed.

Tests to add:

- `TaskApiTest`

Out of scope:

- Task creation.

## Phase 5: Task Attempts And Answer Checking

### Ticket 5.1: Add TaskAttempt Model And Start Endpoint

Goal:

- Starting a task creates a TaskAttempt.

Files likely affected:

- `database/migrations/*_create_task_attempts_table.php`
- `app/Models/TaskAttempt.php`
- `app/Http/Controllers/TaskAttemptController.php`
- `app/Http/Requests/StartTaskAttemptRequest.php`

Implementation notes:

- Implement `POST /api/task-attempts/start`.
- Store user, task, TaskVersion, session, and status.

Acceptance criteria:

- Starting a task creates a TaskAttempt.
- Attempt references the TaskVersion shown.

Tests to add:

- `StartTaskAttemptTest`

Out of scope:

- Grading.

### Ticket 5.2: Add TaskGrader And Submit Endpoint

Goal:

- Grade numeric, multiple choice, and self-check tasks.

Files likely affected:

- `app/Services/TaskGrader.php`
- `app/Http/Requests/SubmitTaskAttemptRequest.php`
- `app/Http/Controllers/TaskAttemptController.php`

Implementation notes:

- Implement `POST /api/task-attempts/{id}/submit`.
- Implement `POST /api/task-attempts/{id}/self-check`.
- Grade against TaskVersion answer schema.

Acceptance criteria:

- Numeric tolerance works.
- Multiple choice correct choice works.
- Self-check stores user-selected result.

Tests to add:

- `TaskGraderTest`
- `SubmitTaskAttemptTest`
- `SelfCheckTaskAttemptTest`

Out of scope:

- AI grading.

## Phase 6: Review Engine

### Ticket 6.1: Implement ReviewScheduler

Goal:

- Create and update reviews from attempts.

Files likely affected:

- `app/Services/ReviewScheduler.php`
- `app/Services/MasteryUpdater.php`
- `app/Models/Review.php`
- `app/Models/MasteryState.php`

Implementation notes:

- Incorrect attempts create Reviews.
- Unsure attempts create Reviews.
- Skipped attempts create Reviews.
- Correct normal attempts do not create Reviews in the narrow MVP.
- Successful review answers complete the Review and move MasteryState to `retained`.

Acceptance criteria:

- Submitting an incorrect, unsure, or skipped attempt creates a Review.
- Duplicate reviews are avoided.
- Passing a Review updates MasteryState.

Tests to add:

- `ReviewSchedulerTest`
- `ReviewAttemptTest`

Out of scope:

- Complex spaced repetition algorithms.

### Ticket 6.2: Add Review API

Goal:

- Expose due reviews and review answering.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/ReviewController.php`
- `app/Http/Requests/AnswerReviewRequest.php`
- `app/Http/Requests/SnoozeReviewRequest.php`
- `app/Http/Resources/ReviewResource.php`

Implementation notes:

- Implement `GET /api/reviews/due`.
- Implement `POST /api/reviews/{id}/answer`.
- Implement `POST /api/reviews/{id}/snooze`.
- Cap review responses to avoid overwhelming users.

Acceptance criteria:

- Due reviews are returned.
- Review answer updates schedule and MasteryState.
- Snooze does not improve mastery.

Tests to add:

- `ReviewApiTest`

Out of scope:

- Long review backlog UI.

## Phase 7: Today Action Selector

### Ticket 7.1: Implement TodaySelector

Goal:

- Recommend up to three daily actions.

Files likely affected:

- `app/Services/TodaySelector.php`
- `app/DTO/TodayAction.php`
- `app/Http/Controllers/TodayController.php`
- `app/Http/Resources/TodayActionResource.php`

Implementation notes:

- Prioritize due reviews.
- Then review_due nodes.
- Then next task in active LearningPath.
- Support `red`, `yellow`, and `green` energy modes.

Acceptance criteria:

- `GET /api/today` returns max 3 actions.
- Red mode actions are max 15 minutes when possible.
- Due reviews appear before new learning.

Tests to add:

- `TodaySelectorTest`
- `TodayApiTest`

Out of scope:

- ML recommendation algorithms.

### Ticket 7.2: Add Today Mode Endpoint

Goal:

- Store user energy mode.

Files likely affected:

- `app/Http/Controllers/TodayModeController.php`
- `app/Http/Requests/SetTodayModeRequest.php`
- User profile migration if needed.

Implementation notes:

- Implement `POST /api/today/mode`.
- Allowed modes: `red`, `yellow`, `green`.

Acceptance criteria:

- Invalid mode is rejected.
- Stored mode affects Today selection.

Tests to add:

- `TodayModeTest`

Out of scope:

- Complex personalization.

## Phase 8: Progress And Mastery States

### Ticket 8.1: Implement MasteryUpdater

Goal:

- Update MasteryState from TaskAttempts and Reviews.

Files likely affected:

- `app/Services/MasteryUpdater.php`
- `app/Enums/MasteryStatus.php`
- `app/Models/MasteryState.php`

Implementation notes:

- Statuses: `unknown`, `practiced`, `review_due`, `retained`.
- Reviews should influence `retained`.

Acceptance criteria:

- Passing a Review updates MasteryState.
- Incorrect, unsure, or skipped attempts move state toward `review_due`.

Tests to add:

- `MasteryUpdaterTest`

Out of scope:

- Advanced psychometrics.

### Ticket 8.2: Add Progress API

Goal:

- Provide progress summary and path progress.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/ProgressController.php`
- `app/Http/Resources/ProgressSummaryResource.php`
- `app/Http/Resources/PathProgressResource.php`

Implementation notes:

- Implement `GET /api/progress/summary`.
- Implement `GET /api/progress/paths/{id}`.
- Implement or complete `GET /api/enrollments/{id}/progress`.

Acceptance criteria:

- Progress is based on MasteryStates and Reviews, not time spent.
- Path progress uses ordered LearningNodes.

Tests to add:

- `ProgressApiTest`

Out of scope:

- Complex dashboards.

## Later Phases: Not MVP

These features must not be implemented until the MVP learning loop is complete and explicitly expanded:

- Custom task creation.
- SimulationDefinition and SimulationRun APIs.
- XP events, achievements, badges, or streaks.
- AI teacher endpoints.
- AiInteraction logging tables.
- Sessions and richer analytics.

## Phase 9: Documentation And Cleanup

### Ticket 9.1: Align Docs With Implementation

Goal:

- Keep docs accurate after implementation.

Files likely affected:

- `docs/*.md`
- `README.md`
- `.env.example`

Implementation notes:

- Update route names, model names, and setup commands if implementation differs.
- Add developer setup instructions.

Acceptance criteria:

- Documentation reflects implemented behavior.
- MVP scope remains clear.

Tests to add:

- None unless docs include executable examples.

Out of scope:

- Marketing copy.

### Ticket 9.2: Final MVP Verification

Goal:

- Verify end-to-end MVP behavior.

Files likely affected:

- `tests/Feature/*`
- `tests/Unit/*`

Implementation notes:

- Add one full flow feature test:
  Today -> start task -> submit incorrect -> review created -> answer review -> progress updated.

Acceptance criteria:

- Migrations run successfully.
- Models and relationships are tested.
- `GET /api/today` returns max 3 actions.
- Red mode actions are max 15 minutes when possible.
- Starting a task creates a TaskAttempt.
- Submitting an incorrect, unsure, or skipped attempt creates a Review.
- Passing a Review updates MasteryState.
- Tests cover ReviewScheduler and TodaySelector.

Tests to add:

- `MvpLearningLoopTest`

Out of scope:

- Frontend acceptance testing.
