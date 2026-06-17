# Nuvio Implementation Plan

Nuvio MVP is a Laravel API backend for an adult learning platform. This plan is written for Codex to implement step by step.

This document uses `Phase` headings as implementation ticket groups. Product phases and release gates are defined separately in `docs/14_RELEASE_ROADMAP.md`.

Repository layout:

- Backend code lives in the independent Git repository at `backend/`.
- Future frontend code lives in the independent Git repository at `frontend/`.
- Backend implementation paths in this document are relative to the `backend/` repository root.
- Do not add frontend application code to the backend repository.

Do not include frontend implementation beyond API support. Do not implement custom tasks, simulations, gamification, AI endpoints, payments, community features, mobile apps, or a complex admin CMS in the MVP.

## Status Labels

- **Current**: implemented in the repo now.
- **V1 required**: needed for the first integrated vertical slice.
- **B4 hardening**: required for full Backend MVP API completeness before Private Alpha.
- **Later**: outside V1 and B4.
- **Out of scope**: deliberately excluded from the narrow MVP.

## V1 Integrated Learning Loop Implementation Path

V1 is the smallest backend implementation path that supports a real frontend learning loop before the full B4 API is complete.

### Ticket V1.1: Auth Or Pre-Provisioned Learner

Status: **V1 required**

- Implement `GET /api/user`.
- Provide locale and timezone defaults.
- Ensure protected endpoints reject unauthenticated access.
- Use Sanctum SPA auth or a pre-provisioned learner flow for internal integration.

### Ticket V1.2: Minimal Algebra Seed

Status: **V1 required**

- Seed one Subject: German Math.
- Seed one LearningPath: Algebra Foundations.
- Seed one LearningNode.
- Seed one numeric Task.
- Seed one active TaskVersion.

### Ticket V1.3: Minimal Enrollment And Today

Status: **V1 required**

- Implement `POST /api/learning-paths/{id}/start`.
- Implement `GET /api/today`.
- Return at most three Today actions.
- Show the next task after enrollment.
- Keep review-before-new-learning priority.

### Ticket V1.4: Task Attempt And Grading

Status: **V1 required**

- Implement `GET /api/tasks/{id}`.
- Implement `POST /api/task-attempts/start`.
- Implement `POST /api/task-attempts/{id}/submit`.
- Support numeric grading only for V1.
- Prevent answer leaks in read APIs and responses.

### Ticket V1.5: Review And Mastery/Progress

Status: **V1 required**

- Create review work for incorrect, unsure, or skipped attempts.
- Implement `GET /api/reviews/{id}`.
- Implement `POST /api/reviews/{id}/answer`.
- Implement `GET /api/progress/summary`.
- Update `MasteryState` from task and review outcomes.

### Ticket V1.6: End-To-End Tests

Status: **V1 required**

- Add `MvpLearningLoopTest`.
- Add `TaskGraderTest` for numeric grading.
- Add `ReviewSchedulerTest` for basic review creation.
- Add `TodaySelectorTest` for max-three and review priority.
- Add ownership and no-answer-leak smoke tests.

The broader phases below remain useful as B4 hardening work. Implement only the V1-required subset first unless a B4-hardening item is needed to keep the V1 code simple and correct.

## Phase 0: Project Setup

### Ticket 0.1: Create Laravel API Foundation

Status: **Current** for the existing Laravel skeleton, `routes/api.php`, `GET /api/status`, `/up`, and boot/status tests. Remaining setup work is **V1 required** only when needed by the V1 path.

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

### Ticket 0.2: Add Sanctum SPA Auth And Preferences Contract

Status: **V1 required** for `GET /api/user`, protected endpoint rejection, and locale/timezone defaults. **B4 hardening** for `PUT /api/user/preferences` if preference persistence is deferred from V1.

Goal:

- Provide the MVP authentication and narrow preference contract from `docs/05_API_SPEC.md`.

Files likely affected:

- `composer.json`
- `config/sanctum.php`
- `config/cors.php`
- `routes/api.php`
- `app/Http/Controllers/UserPreferenceController.php`
- `app/Http/Resources/UserResource.php`
- User migration if locale fields are stored on users.

Implementation notes:

- Use Laravel Sanctum SPA cookie authentication.
- Support `GET /sanctum/csrf-cookie`, `POST /login`, `POST /logout`, and `GET /api/user`.
- Implement `PUT /api/user/preferences` for `locale` and `timezone`.
- Backend registration may exist for API tests and local setup, but first-slice frontend signup and password reset UI are out of scope.

Acceptance criteria:

- Authenticated learner endpoints reject unauthenticated requests.
- `GET /api/user` returns `locale` and `timezone`.
- Preferences can be updated and persist for the user.

Tests to add:

- `AuthSessionTest`
- `UserPreferenceTest`

Out of scope:

- Frontend signup UI.
- Password reset UI.

## Phase 1: Core Domain Models And Migrations

### Ticket 1.1: Add SkillGraph Core Tables

Status: **V1 required** for the minimal Subject, LearningPath, LearningNode, and Task linkage needed by Algebra Foundations. **B4 hardening** for the full relationship surface.

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
- NodeRelation accepts only `prerequisite` in the MVP.
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

Status: **V1 required** for Enrollments, Reviews, and MasteryStates used by the integrated loop.

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

Status: **V1 required** for one German Math path, one LearningNode, one numeric Task, and one active TaskVersion. **B4 hardening** for 3 to 5 LearningNodes and 2 to 3 Tasks per node.

Goal:

- Seed one MVP subject path.

Files likely affected:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/SubjectSeeder.php`
- `database/seeders/LearningGraphSeeder.php`
- Optional `content/**/*.yaml`

Implementation notes:

- Seed exactly one German Math LearningPath: Algebra Foundations.
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

### Ticket 2.2: Add Required Content Validation Skeleton

Status: **B4 hardening**. Add earlier only if V1 seed data becomes hard to keep deterministic without it.

Goal:

- Provide an artisan command or service shape for validating MVP seed content.

Files likely affected:

- `app/Console/Commands/ValidateContentCommand.php`
- `app/Console/Commands/ImportContentCommand.php`
- `app/Services/Content/ContentValidator.php`

Implementation notes:

- This is required for B4 release readiness.
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

Status: **B4 hardening**. V1 enters the single Algebra Foundations path through the `start_path` Today action.

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

Status: **V1 required**.

Goal:

- Allow a user to start a LearningPath.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/EnrollmentController.php`
- `app/Http/Resources/EnrollmentResource.php`
- `app/Http/Requests/StartLearningPathRequest.php`

Implementation notes:

- Implement `POST /api/learning-paths/{id}/start`.
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

Status: **V1 required** for numeric Task and active TaskVersion support. **B4 hardening** for broader task type coverage.

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

Status: **V1 required**.

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

Status: **V1 required**.

Goal:

- Starting a task creates a TaskAttempt.

Files likely affected:

- `database/migrations/*_create_task_attempts_table.php`
- `app/Models/TaskAttempt.php`
- `app/Http/Controllers/TaskAttemptController.php`
- `app/Http/Requests/StartTaskAttemptRequest.php`

Implementation notes:

- Implement `POST /api/task-attempts/start`.
- Store user, task, TaskVersion, and status.

Acceptance criteria:

- Starting a task creates a TaskAttempt.
- Attempt references the TaskVersion shown.

Tests to add:

- `StartTaskAttemptTest`

Out of scope:

- Grading.

### Ticket 5.2: Add TaskGrader And Submit Endpoint

Status: **V1 required** for numeric grading and `incorrect|unsure|skipped`. **B4 hardening** for multiple choice. **Later** for self-check.

Goal:

- Grade numeric tasks for V1; add multiple choice for B4 hardening. Self-check is Later.

Files likely affected:

- `app/Services/Tasks/TaskGrader.php`
- `app/Http/Requests/SubmitTaskAttemptRequest.php`
- `app/Http/Controllers/TaskAttemptController.php`

Implementation notes:

- Implement `POST /api/task-attempts/{id}/submit`.
- Do not implement `POST /api/task-attempts/{id}/self-check` in V1 or B4.
- Grade against TaskVersion answer schema.
- `submit` accepts exactly one of `answer` or `result: unsure|skipped`.

Acceptance criteria:

- Numeric tolerance works.
- B4 multiple choice correct choice works.

Tests to add:

- `TaskGraderTest`
- `SubmitTaskAttemptTest`

Out of scope:

- AI grading.

## Phase 6: Review Engine

### Ticket 6.1: Implement ReviewScheduler

Status: **V1 required** for basic review creation and completion. **B4 hardening** for richer scheduling behavior.

Goal:

- Create and update reviews from attempts.

Files likely affected:

- `app/Services/Review/ReviewScheduler.php`
- `app/Services/Mastery/MasteryUpdater.php`
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

Status: **V1 required** for `GET /api/reviews/{id}` and `POST /api/reviews/{id}/answer`. **B4 hardening** for `GET /api/reviews/due` and `POST /api/reviews/{id}/snooze`.

Goal:

- Expose due reviews and review answering.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/ReviewController.php`
- `app/Http/Requests/AnswerReviewRequest.php`
- `app/Http/Requests/SnoozeReviewRequest.php`
- `app/Http/Resources/ReviewResource.php`
- `app/Http/Resources/ReviewDetailResource.php`

Implementation notes:

- Implement `GET /api/reviews/due` for B4.
- Implement `GET /api/reviews/{id}`.
- Implement `POST /api/reviews/{id}/answer`.
- Implement `POST /api/reviews/{id}/snooze` for B4.
- Cap review responses to avoid overwhelming users.

Acceptance criteria:

- Due reviews are returned.
- Review detail returns renderable prompt data without exposing answers.
- Review answer updates schedule and MasteryState.
- Snooze does not improve mastery.

Tests to add:

- `ReviewApiTest`

Out of scope:

- Long review backlog UI.

## Phase 7: Today Action Selector

### Ticket 7.1: Implement TodaySelector

Status: **V1 required**.

Goal:

- Recommend up to three daily actions.

Files likely affected:

- `app/Services/Today/TodaySelector.php`
- `app/DTO/TodayAction.php`
- `app/Http/Controllers/TodayController.php`
- `app/Http/Resources/TodayActionResource.php`

Implementation notes:

- Prioritize due reviews.
- Then Start Path when the user has no active enrollment.
- Then next task in active LearningPath.
- Do not support Energy Mode in V1.

Acceptance criteria:

- `GET /api/today` returns max 3 actions.
- Today actions expose no `reason`, `hidden_due_reviews`, backlog counts, or pressure state.
- Due reviews appear before new learning.

Tests to add:

- `TodaySelectorTest`
- `TodayApiTest`

Out of scope:

- ML recommendation algorithms.

### Ticket 7.2: Add Today Mode Endpoint

Status: **B4 hardening**. V1 does not pass `mode` to `GET /api/today` and does not persist Energy Mode.

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

Status: **V1 required**.

Goal:

- Update MasteryState from TaskAttempts and Reviews.

Files likely affected:

- `app/Services/Mastery/MasteryUpdater.php`
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

Status: **V1 required** for `GET /api/progress/summary`. **B4 hardening** for `GET /api/progress/paths/{id}`.

Goal:

- Provide progress summary and path progress.

Files likely affected:

- `routes/api.php`
- `app/Http/Controllers/ProgressController.php`
- `app/Http/Resources/ProgressSummaryResource.php`
- `app/Http/Resources/PathProgressResource.php`

Implementation notes:

- Implement `GET /api/progress/summary`.
- Implement `GET /api/progress/paths/{id}` for B4.

Acceptance criteria:

- Progress is based on MasteryStates and Reviews, not time spent.
- Path progress uses ordered LearningNodes.
- Progress responses expose competence and review status only.
- No XP, badge, achievement, streak, rank, reward level, catch-up, or lost-progress fields are added.
- Path progress does not expose `percent_complete` or `mastery_score`.
- Skill-Map is Later.

Tests to add:

- `ProgressApiTest`

Out of scope:

- Complex dashboards.
- Stars, level numbers, badge slots, reward locks, and backlog lists.

### Ticket 8.3: Add Playful Without Pressure Response Metadata

Status: **B4 hardening** for neutral `completion_state` and `mastery_moment`. **Later** for `challenge_options` and Skill-Map response metadata.

Goal:

- Make learning progress feel visible through state, not reward systems.

Files likely affected:

- `app/Http/Resources/*`
- `app/Services/Progress/*`
- `tests/Feature/*`

Implementation notes:

- Do not add `completion_state`, `mastery_moment`, or `challenge_options` to V1 responses.
- Add optional B4 `completion_state` to task attempt and review answer responses.
- Add optional B4 `mastery_moment` only when a Review or MasteryState transition provides real evidence.
- Keep deterministic next learning choices as Later.
- Do not persist a separate reward table.

Acceptance criteria:

- `completion_state` labels are short and neutral.
- `mastery_moment` appears only for correct Review, retained transition, formerly unsure task later solved, or return-after-break completion.
- `challenge_options` are not implemented in B4.
- Responses do not include XP, badges, achievements, streaks, reward levels, countdown pressure, scarcity, or catch-up state.

Tests to add:

- Response resource tests for allowed metadata.
- Forbidden-field assertions for pressure mechanics.

Out of scope:

- New grading engines.
- Interactive Algebra implementation.
- Reward inventory.

## Later Phases: Not MVP

Status: **Later** or **Out of scope** for V1/B4.

These features must not be implemented until the MVP learning loop is complete and explicitly expanded:

- Custom task creation.
- SimulationDefinition and SimulationRun APIs.
- AI teacher endpoints.
- AiInteraction logging tables.
- LearningSessions and richer analytics.

These motivation features must not be implemented for V1 or B4:

- XP events or points.
- Achievements, badges, trophies, or collections.
- Streaks, streak freezes, or streak repair.
- Leaderboards, ranks, leagues, or social comparison.
- Reward levels.
- Daily pressure, catch-up, behind, failed, or lost-progress flows.
- Comeback streaks.
- Countdown pressure, artificial scarcity, lootbox or slot-machine reward reveals, or attendance rewards.

## Playful Without Pressure Ticket Set

Use these as the first small implementation sequence when the API and frontend are ready for playful-but-non-controlling polish:

1. Add neutral B4 `completion_state` values to task and review mutation responses.
2. Add forbidden-field tests for XP, badges, achievements, streaks, ranks, reward levels, catch-up debt, countdowns, scarcity, and lost progress.
3. Add optional `mastery_moment` metadata for correct Review and `review_due` to `retained` transitions.
4. Draft Later Skill-Map endpoint contract from existing SkillGraph and MasteryState data.
5. Draft Later interactive Algebra task schemas for equation transformation, error marking, term balancing, and graph manipulation.

## Phase 9: Documentation And Cleanup

### Ticket 9.1: Align Docs With Implementation

Status: **V1 required** for V1 behavior; **B4 hardening** for full API completeness docs.

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
- Product docs preserve Motivation Without Pressure guardrails.
- V1 and B4 acceptance criteria explicitly reject XP, badges, achievements, streaks, leaderboards, reward levels, and loss-state copy.

Tests to add:

- None unless docs include executable examples.

Out of scope:

- Marketing copy.

### Ticket 9.2: Final MVP Verification

Status: **V1 required** for the first `MvpLearningLoopTest`; **B4 hardening** for the full Backend MVP verification pass.

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
- Progress summary uses MasteryState and Review data without XP, badge, streak, rank, reward level, or loss-state fields.
- Fixtures and response examples avoid pressure copy such as "catch up", "behind", "failed", and "lost progress".

Tests to add:

- `MvpLearningLoopTest`

Out of scope:

- Frontend acceptance testing.
