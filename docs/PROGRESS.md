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

- Full ownership, validation, route matrix, node APIs, review due/snooze, and path progress remain B4 hardening.

Next:

- Run the broader backend test suite and then update the root backend submodule pointer if the backend remains green.

## 2026-06-17 - V1 Review TaskVersion Pinning

Status: completed.

Changed:

- Added `reviews.task_version_id` so review work can keep using the TaskVersion that created the review.
- Updated task outcome scheduling to persist the attempted TaskVersion on created or updated Reviews.
- Updated review read and answer routes to use the stored TaskVersion instead of a later active version.
- Added regression coverage for review read/answer after a newer TaskVersion becomes active.

Commit:

- `a399154 fix: pin review task versions`

Checks:

- `php artisan test --filter='ReviewVersioningTest|MvpLearningLoopTest|OwnershipAndGuardrailTest|ReviewSchedulerTest|TaskAttemptFlowTest'` passed: 11 tests, 134 assertions.

Open risks:

- Existing reviews from pre-migration environments may have `task_version_id = null`; the route falls back to the active TaskVersion for those legacy rows.
- B4 still needs a broader validation/ownership matrix for Review endpoints.

Next:

- Run the full backend test suite again and update the root backend submodule pointer.

## 2026-06-17 - B4 Web Session Route Coverage

Status: completed.

Changed:

- Added focused coverage for the existing browser-session auth routes in `routes/web.php`.
- Covered `GET /sanctum/csrf-cookie`, successful `POST /login`, invalid credentials, and `POST /logout`.
- Kept the route set classified as B4 hardening because V1 can use a pre-provisioned learner and full Sanctum package/config/CORS hardening is not complete.

Commit:

- `f0f9fb6 test: cover web auth session routes`

Checks:

- `php artisan test --filter=AuthSessionTest` passed: 4 tests, 13 assertions.

Open risks:

- The routes provide the local web session flow, but the backend still needs full B4 Sanctum package/configuration, stateful-domain, CORS credential, and browser-origin hardening before F2/A1.
- `PUT /api/user/preferences` remains B4 and is not implemented.

Next:

- Align backend planning docs with the completed V1 state, then run the full backend test suite and update the root backend submodule pointer.

## 2026-06-17 - Backend Roadmap Alignment

Status: completed.

Changed:

- Added backend agent and domain-language entry points.
- Added `docs/00_START_HERE.md` and `docs/14_RELEASE_ROADMAP.md`.
- Aligned backend planning docs around V1 completed, B4 hardening next, and frontend planning allowed only after explicit start.
- Preserved Motivation Without Pressure guardrails across API, scope, roadmap, workflow, and test planning docs.

Commit:

- `a3b7375 docs: align backend roadmap with v1 loop`

Checks:

- `php artisan test` passed: 27 tests, 184 assertions.

Open risks:

- Full B4 API hardening remains open: preferences, node APIs, review due/snooze, path progress, validation matrix, ownership matrix, and full Sanctum/CORS/browser-origin hardening.
- Frontend docs still need their own planning-only commit and root submodule pointers still need updating.

Next:

- Commit frontend planning docs, then update root submodule pointers.

## 2026-06-18 - B4 User Preferences Endpoint

Status: completed.

Changed:

- Checked the current session hardening surface: local web session login/logout routes exist and are tested, `laravel/sanctum` is not installed, `config/cors.php` is not present, and session cookie settings are still env-driven defaults.
- Added `PUT /api/user/preferences` behind the existing `web` plus `auth` API route group.
- Persisted the authenticated user's narrow `locale` and `timezone` preferences.
- Added validation for supported Slice 1 UI locales (`de`, `en`) and IANA timezones.
- Added feature coverage for authenticated updates, unauthenticated rejection, required fields, unsupported locales, invalid timezones, and `GET /api/user` reflecting persisted preferences.

Commit:

- `aa23044 feat: add user preferences endpoint`

Checks:

- `php artisan test --filter=UserEndpointTest` passed: 5 tests, 19 assertions.
- `php artisan test --filter='UserEndpointTest|AuthSessionTest'` passed: 9 tests, 32 assertions.
- `php artisan test` passed: 30 tests, 200 assertions.

Open risks:

- Full Sanctum package/configuration, stateful domains, CORS credential handling, and browser-origin hardening remain open B4 work before F2/A1.
- The Preferences endpoint still lives in `routes/api.php` closures with the rest of the V1 loop; controller/request/resource extraction remains a B4 maintainability task.
- Supported locale normalization for regional variants such as `de-DE` is still expected on the frontend side unless the backend contract is expanded.

Next:

- Start the next B4 API slice: `POST /api/today/mode` persistence or the first read-only Learning Path/Node API, with focused validation and ownership tests.

## 2026-06-18 - B4 Today Mode Endpoint

Status: completed.

Changed:

- Added `users.energy_mode` with a `yellow` default for the authenticated learner's current Energy Mode.
- Added `POST /api/today/mode` behind the existing `web` plus `auth` API route group.
- Persisted allowed Energy Mode values: `red`, `yellow`, and `green`.
- Returned the canonical response shape: `{ "data": { "mode": "yellow" } }`.
- Added focused feature coverage for unauthenticated rejection, valid updates, required `mode`, invalid `mode`, and persistence preservation after invalid requests.

Commit:

- `64609de feat: add today mode endpoint`

Checks:

- `php artisan test --filter=TodayModeTest` passed: 3 tests, 11 assertions.
- `php artisan test --filter='TodayModeTest|UserEndpointTest|TodaySelectorTest'` passed: 11 tests, 49 assertions.
- `php artisan test` passed: 33 tests, 211 assertions.

Open risks:

- `GET /api/today` still does not rank or filter recommendations by stored Energy Mode. The endpoint persistence slice is complete, but the B4 Today selection behavior still needs a focused follow-up before the API fully satisfies the red/yellow/green selection acceptance criteria.
- The Today mode endpoint still lives in `routes/api.php` closures with the rest of the V1/B4 learning routes; controller/request/resource extraction remains covered by the existing B4 maintainability issue.

Next:

- Add the first small Energy Mode-aware Today selection rule, starting with red mode preferring actions of 15 minutes or less when possible.

## 2026-06-18 - B4 Maintainability Planning

Status: completed.

Changed:

- Added a B4 maintainability ticket to `docs/10_IMPLEMENTATION_PLAN.md` for extracting route closures into Laravel controllers, Form Requests, API Resources, and focused services.
- Linked the plan to existing backend issue `torbenseer/nuvio-backend#6`.
- Added the maintainability slice to `docs/00_START_HERE.md` as a B4 hardening task that can run in small endpoint-group commits.

Commit:

- Recorded in this documentation-only planning commit.

Checks:

- No PHPUnit run needed for documentation-only planning changes.
- Diff reviewed before commit.

Open risks:

- The refactor is not implemented yet; current API endpoints still live in route closures until the planned endpoint-group extraction begins.
- Each future extraction must preserve response shapes, validation behavior, ownership behavior, and no-answer-leak guarantees.

Next:

- Implement the first maintainability extraction slice for User, Preferences, Today, and Today Mode after the next behavior-critical B4 slice is chosen.

## 2026-06-18 - B4 Today Red Mode Selection

Status: completed.

Changed:

- Made `GET /api/today` read the authenticated user's persisted `energy_mode`.
- Added Red Mode selection behavior that prefers available Today Actions with `estimated_minutes <= 15` while preserving the existing `data` and `meta.limit` response shape.
- Kept Today capped at three Actions and continued to omit `mode`, `reason`, backlog counts, and pressure/gamification fields from the response.
- Added direct test data in `TodaySelectorTest` instead of expanding seed breadth for this narrow selector slice.

Commit:

- `7eb2108 feat: make today selector mode aware`

Checks:

- `php artisan test --filter=TodaySelectorTest` passed: 6 tests, 205 assertions.
- `php artisan test` passed: 36 tests, 397 assertions.

Open risks:

- Red Mode now has the first short-action preference rule, but Yellow and Green still behave like the existing default selector until later Energy Mode rules are explicitly defined.
- `GET /api/today` and adjacent learning routes still live in `routes/api.php` closures or thin services; controller/resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- Fetching all due reviews is acceptable for the current MVP scale but may need a bounded candidate strategy when due review volume grows.

Next:

- Start the next B4 API slice: implement the read-only Learning Path/Node APIs from backend issue `torbenseer/nuvio-backend#3`, or take the focused controller/resource extraction slice for User, Preferences, Today, and Today Mode from backend issue `torbenseer/nuvio-backend#6`.

## 2026-06-18 - B4 Learning Path Read APIs

Status: completed.

Changed:

- Added `GET /api/learning-paths` for authenticated learners with the canonical `{ "data": [...] }` response shape.
- Listed active LearningPaths only and included `id`, `slug`, `title`, `subject`, `estimated_minutes`, and active `node_count`.
- Added optional `subject` filtering against active Subject slugs, with inactive or unknown subject filters returning `422`.
- Added `GET /api/learning-paths/{learningPath}` for active path detail with ordered active LearningNodes.
- Kept this first issue `#3` slice narrow: no Node APIs, no seed breadth expansion, and no controller/resource extraction.
- Added guardrail coverage that Learning Path responses do not expose Progress, pressure, or gamification fields.

Commit:

- `28884e6 feat: add learning path read APIs`

Checks:

- `php artisan test --filter=LearningPathApiTest` passed: 5 tests, 65 assertions.
- `php artisan test` passed: 41 tests, 462 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#3` remains partially open for `GET /api/nodes`, `GET /api/nodes/{id}`, `GET /api/nodes/{id}/tasks`, and `GET /api/nodes/{id}/prerequisites`.
- Learning Path routes still live in `routes/api.php`; controller, FormRequest, and Resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- Active Path detail silently omits inactive nodes; B4 content validation should prevent active paths from containing inactive or inconsistent node references.

Next:

- Continue backend issue `torbenseer/nuvio-backend#3` with the next small read-only slice: `GET /api/nodes` and `GET /api/nodes/{id}` before adding node task/prerequisite endpoints.
