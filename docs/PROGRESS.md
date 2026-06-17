# Nuvio Backend Progress

## 2026-06-17 - V1 Auth/User Foundation

Status: completed.

Changed:

- Added focused coverage for `GET /api/user` returning the authenticated learner profile with German locale and Europe/Berlin timezone defaults.
- Covered unauthenticated rejection for the V1 user endpoint.
- Prepared the first V1 ticket boundary: auth or pre-provisioned learner support before Today and task loop work.

Commit:

- `169e88e feat: add v1 user endpoint`

Checks:

- `php artisan test --filter=UserEndpointTest` passed: 2 tests, 3 assertions.

Open risks:

- The workspace already contains broader uncommitted V1 learning-loop implementation work. It must be split into coherent follow-up commits without staging unrelated local changes.
- Full Sanctum/browser login hardening remains B4 unless V1 chooses real login instead of pre-provisioned learner support.

Next:

- Split the existing German Algebra seed and minimal enrollment/Today work into coherent follow-up commits from `docs/00_START_HERE.md`.

## 2026-06-17 - V1 Learning Core Schema

Status: completed.

Changed:

- Added the relational learning core tables for Subjects, LearningNodes, LearningPaths, Path Nodes, Tasks, TaskVersions, Enrollments, Reviews, TaskAttempts, and MasteryStates.
- Added Eloquent models and relationships needed by the V1 loop.
- Added learner relationships for enrollments, attempts, reviews, and mastery states.

Commit:

- `4d16433 feat: add learning core schema`

Checks:

- `php artisan test --filter=UserEndpointTest` passed: 2 tests, 3 assertions.

Open risks:

- NodeRelation is still not modeled because the V1 seed has one node and no prerequisites. B4 still needs prerequisite relations before node API hardening.
- The core migration is broad because all V1 loop state shares foreign keys. Future schema changes should stay smaller.

Next:

- Commit the German Algebra seed content against the new core schema.

## 2026-06-17 - V1 German Algebra Seed

Status: completed.

Changed:

- Seeded one Subject: German Math.
- Seeded one LearningPath: Algebra Foundations.
- Seeded one LearningNode for solving linear equations.
- Seeded one numeric Task and one active TaskVersion with German prompt, input schema, answer schema, and explanation.

Commit:

- `1cbe859 feat: seed algebra foundations path`

Checks:

- `php artisan migrate:fresh --seed --env=testing` passed.

Open risks:

- Seed content is intentionally tiny for V1 and not enough for real learner breadth.
- Content validation tooling remains B4 hardening.

Next:

- Add minimal enrollment and Today selection over the seeded path.

## 2026-06-17 - V1 Enrollment And Today Selector

Status: completed.

Changed:

- Added `GET /api/today` with a cap of three Today Actions.
- Added `POST /api/learning-paths/{learningPath}/start` with idempotent active enrollment behavior.
- Added deterministic Today ordering: due reviews first, then Start Path when no enrollment exists, then the next path task.
- Added focused Today selector coverage for cap, review priority, and concrete task titles.

Commit:

- `f2f0e32 feat: add enrollment today selector`

Checks:

- `php artisan test --filter=TodaySelectorTest` passed: 3 tests, 19 assertions.

Open risks:

- Today currently chooses the first active path and first active enrollment only, which is acceptable for the single V1 seed but needs B4 expansion.
- Energy Mode remains deferred.

Next:

- Add numeric task fetch/start/submit and deterministic grading.

## 2026-06-17 - V1 Numeric Task Attempts

Status: completed.

Changed:

- Added `GET /api/tasks/{task}` without answer leaks.
- Added `POST /api/task-attempts/start` with TaskVersion ownership to the Task.
- Added `POST /api/task-attempts/{taskAttempt}/submit` for numeric answers, unsure, and skipped results.
- Added deterministic numeric grading and neutral feedback copy.
- Added review scheduling and mastery update side effects for task outcomes inside a transaction.

Commit:

- `25cec7c feat: add numeric task attempts`

Checks:

- `php artisan test --filter='TaskAttemptFlowTest|TaskGraderTest|ReviewSchedulerTest'` passed: 9 tests, 41 assertions.

Open risks:

- The submit route is closure-based in `routes/api.php`; controller/resource extraction is a B4 maintainability task once the V1 contract settles.
- Validation coverage is still narrow and should expand for B4.

Next:

- Add review answering and progress summary to close the V1 learning loop.

## 2026-06-17 - V1 Review And Progress Loop

Status: completed.

Changed:

- Added `GET /api/reviews/{review}` without answer leaks.
- Added `POST /api/reviews/{review}/answer` for numeric, unsure, and skipped review outcomes.
- Added `GET /api/progress/summary` with compact competence-status counts.
- Added end-to-end V1 loop coverage and ownership/pressure-guardrail smoke tests.
- Added conflict handling for already completed reviews.

Commit:

- `df86b59 feat: add review progress loop`

Checks:

- `php artisan test --filter='MvpLearningLoopTest|OwnershipAndGuardrailTest|TodaySelectorTest|TaskAttemptFlowTest|TaskGraderTest|ReviewSchedulerTest'` passed: 18 tests, 153 assertions.

Open risks:

- The V1 review attempt uses the currently active TaskVersion for the review read/answer path; B4 should persist and reuse a review-specific TaskVersion if content versioning changes before review completion.
- Full ownership, validation, route matrix, node APIs, review due/snooze, and path progress remain B4 hardening.

Next:

- Run the broader backend test suite and then update the root backend submodule pointer if the backend remains green.
