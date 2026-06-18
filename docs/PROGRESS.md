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

## 2026-06-18 - B4 Learning Node Read APIs

Status: completed.

Changed:

- Added `GET /api/nodes` for authenticated learners with the canonical `{ "data": [...] }` response shape.
- Listed active LearningNodes only and included `id`, `slug`, `type`, and `title`.
- Added optional `type=skill` filtering and validation that rejects unsupported node types such as `concept`.
- Added optional `subject` filtering against active Subject slugs, matching the existing read-only path filter behavior.
- Added `GET /api/nodes/{learningNode}` for active node detail with `id`, `slug`, `type`, `title`, `description`, and active Subject memberships.
- Kept the issue `#3` slice narrow: no Node Task APIs, no prerequisite APIs, no seed breadth expansion, and no controller/resource extraction.
- Added guardrail coverage that Node responses do not expose task answers, Progress, pressure, or gamification fields.

Commit:

- `7b38cd0 feat: add learning node read APIs`

Checks:

- `php artisan test --filter=LearningNodeApiTest` passed: 7 tests, 106 assertions.
- `php artisan test` passed: 48 tests, 568 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#3` remains partially open for `GET /api/nodes/{id}/tasks` and `GET /api/nodes/{id}/prerequisites`.
- Learning Node routes still live in `routes/api.php`; controller, FormRequest, and Resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- The optional Node `subject` filter now validates active Subject slugs; this matches the existing Learning Path read API but should stay documented if the API spec later wants explicit Node subject-filter validation text.

Next:

- Continue backend issue `torbenseer/nuvio-backend#3` with the next small read-only slice: `GET /api/nodes/{id}/tasks`, keeping answer schemas and hidden correctness metadata out of responses.

## 2026-06-18 - B4 Learning Node Task Read API

Status: completed.

Changed:

- Added `GET /api/nodes/{id}/tasks` for authenticated learners with the canonical `{ "data": [...] }` response shape.
- Required the LearningNode to exist and be active; inactive or missing nodes return `404`.
- Listed only active Tasks linked to the active LearningNode.
- Kept the Task response limited to `id`, `type`, `difficulty`, and `estimated_minutes`.
- Kept the issue `#3` slice narrow: no prerequisite APIs, no seed breadth expansion, no frontend runtime, and no controller/FormRequest/Resource extraction.
- Added guardrail coverage that Node Task responses do not expose answers, answer schemas, accepted values, tolerances, explanations, hidden correctness data, Progress, pressure, or gamification fields.

Commit:

- `0daae99 feat: add learning node task api`

Checks:

- `php artisan test --filter=LearningNodeApiTest` passed: 11 tests, 187 assertions.
- `php artisan test` passed: 52 tests, 649 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#3` remains partially open for `GET /api/nodes/{id}/prerequisites`.
- Learning Node routes still live in `routes/api.php`; controller, FormRequest, and Resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- Node Task ordering is currently by Task ID, which is sufficient for the current linked-task read API but may need an explicit content ordering field if B4 content breadth requires authored ordering.

Next:

- Continue backend issue `torbenseer/nuvio-backend#3` with the final read-only slice: `GET /api/nodes/{id}/prerequisites`.

## 2026-06-18 - B4 Learning Node Prerequisites API

Status: completed.

Changed:

- Added a `node_relations` table and `NodeRelation` model for SkillGraph prerequisite relationships.
- Added LearningNode prerequisite/dependent relationship helpers for MVP `prerequisite` relations.
- Added `GET /api/nodes/{id}/prerequisites` for authenticated learners with the canonical `{ "data": [...] }` response shape.
- Required the LearningNode to exist and be active; inactive or missing nodes return `404`.
- Listed only active prerequisite LearningNodes and kept each item limited to `id`, `title`, and `relation`.
- Added guardrail coverage that prerequisite responses do not expose answers, Progress, pressure, or gamification fields.
- Completed the remaining route work for backend issue `torbenseer/nuvio-backend#3`.

Commit:

- `9225276 feat: add learning node prerequisites api`

Checks:

- `php artisan test --filter=LearningNodeApiTest` passed: 14 tests, 248 assertions.
- `php artisan test` passed: 55 tests, 710 assertions.

Open risks:

- Learning Path and Node routes still live in `routes/api.php`; controller, FormRequest, and Resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- The V1 seed still has one LearningNode and no authored prerequisite relation. B4 content validation and seed breadth remain covered by backend issue `torbenseer/nuvio-backend#7`.
- NodeRelation content rules such as no self-relations are not yet enforced by content validation; that remains part of the content validation issue.

Next:

- Close backend issue `torbenseer/nuvio-backend#3`, then choose the next B4 slice: due reviews/snooze (`#5`) or path progress (`#2`).

## 2026-06-18 - B4 Path Progress API

Status: completed.

Changed:

- Added `GET /api/progress/paths/{learningPath}` for authenticated learners with the canonical `{ "data": ... }` response shape.
- Added a focused `PathProgress` service that derives path status from ordered active LearningNodes, the authenticated user's scheduled Reviews, and the authenticated user's MasteryStates.
- Returned `learning_path_id`, `title`, neutral `node_counts`, and ordered node statuses of `unknown`, `practiced`, `review_due`, or `retained`.
- Required the LearningPath to exist and be active; inactive or missing paths return `404`.
- Added ownership coverage so other learners' MasteryStates and scheduled Reviews do not affect the response.
- Added guardrail coverage that Path Progress does not expose `percent_complete`, `mastery_score`, XP, badges, achievements, streaks, reward levels, catch-up, debt, lost-progress, or collection-completion fields.
- Completed backend issue `torbenseer/nuvio-backend#2`.

Commit:

- `8e9dc65 feat: add path progress api`

Checks:

- `php artisan test --filter=PathProgressApiTest` passed: 5 tests, 36 assertions.
- `php artisan test` passed: 60 tests, 746 assertions.

Open risks:

- Path Progress still lives in `routes/api.php`; controller/resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- The endpoint treats active LearningPaths as public readable paths because there is no separate path visibility field yet; if private paths are added, ownership/visibility rules need a focused follow-up.
- Review status currently treats any scheduled Review on a path node as `review_due`, matching existing MasteryState language but not exposing due counts or backlog pressure.

Next:

- Close backend issue `torbenseer/nuvio-backend#2`, then continue B4 with due reviews/snooze (`#5`) or maintainability extraction (`#6`).

## 2026-06-18 - B4 Review Due And Snooze APIs

Status: completed.

Changed:

- Added `GET /api/reviews/due` for authenticated learners with the canonical `{ "data": [...], "meta": { "returned": ..., "cap": ... } }` response shape.
- Defaulted due review listing to a small cap of three and validated optional `limit` with a maximum of 10.
- Listed only the authenticated user's `scheduled` Reviews with `due_at <= now()`, ordered by oldest due date, and omitted hidden backlog counts.
- Added `POST /api/reviews/{id}/snooze` with 15 to 1440 minute validation.
- Made snooze move `due_at` from the current time while preserving `status = scheduled` and without changing MasteryState or creating attempts.
- Added guardrail coverage that due/snooze responses do not expose answers, hidden backlog counts, pressure fields, mastery scores, or gamification fields.
- Completed backend issue `torbenseer/nuvio-backend#5`.

Commit:

- `8bdcdaf feat: add review due snooze apis`

Checks:

- `php artisan test --filter=ReviewDueApiTest` passed: 5 tests, 64 assertions.
- `php artisan test --filter='ReviewDueApiTest|MvpLearningLoopTest|TodaySelectorTest|PathProgressApiTest|OwnershipAndGuardrailTest|ReviewVersioningTest|ReviewSchedulerTest'` passed: 25 tests, 422 assertions.
- `php artisan test` passed: 65 tests, 810 assertions.

Open risks:

- Due Review prioritization is currently ordered by due date. The richer Review Engine priority list for active enrollments, lower mastery states, and shorter duration remains a possible follow-up if due volume grows.
- Review routes still live in `routes/api.php`; controller/FormRequest/Resource extraction remains covered by backend issue `torbenseer/nuvio-backend#6`.
- Full validation and ownership matrix hardening remains covered by backend issue `torbenseer/nuvio-backend#4`.

Next:

- Close backend issue `torbenseer/nuvio-backend#5`, then continue B4 with either the focused Review route extraction slice from #6 or content validation/seed breadth from #7.

## 2026-06-18 - B4 User And Today Route Extraction

Status: completed.

Changed:

- Started backend issue `torbenseer/nuvio-backend#6` with the first focused route-closure extraction batch.
- Moved `GET /api/user` into `UserController` and `UserResource`.
- Moved `PUT /api/user/preferences` into `UserPreferenceController`, `UpdateUserPreferenceRequest`, and `UserPreferenceResource`.
- Moved `GET /api/today` into `TodayController` and `TodayActionResource`, preserving the existing `meta.limit` response.
- Moved `POST /api/today/mode` into `TodayModeController`, `SetTodayModeRequest`, and `TodayModeResource`.
- Left Learning Path, Node, Task Attempt, Review, and Progress route closures for later smaller extraction batches.

Commit:

- Recorded in this route extraction commit.

Checks:

- `php artisan test --filter='UserEndpointTest|TodayModeTest|TodaySelectorTest'` passed before the refactor: 14 tests, 235 assertions.
- `php artisan test --filter='UserEndpointTest|TodayModeTest|TodaySelectorTest'` passed after the refactor: 14 tests, 235 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#6` remains partially open until the remaining Learning Path/Node, Enrollment/Task Attempt, Review, and Progress closures are extracted.
- Full validation and ownership matrix hardening remains covered by backend issue `torbenseer/nuvio-backend#4`.

Next:

- Continue backend issue `torbenseer/nuvio-backend#6` with the next coherent extraction batch, likely Learning Path and Learning Node read APIs.

## 2026-06-18 - B4 Learning Path And Node Route Extraction

Status: completed.

Changed:

- Continued backend issue `torbenseer/nuvio-backend#6` with the read-only Learning Path and Learning Node API extraction batch.
- Moved `GET /api/learning-paths` and `GET /api/learning-paths/{learningPath}` into `LearningPathController`, `ListLearningPathsRequest`, `LearningPathSummaryResource`, and `LearningPathDetailResource`.
- Moved `GET /api/nodes`, `GET /api/nodes/{learningNode}`, `GET /api/nodes/{learningNode}/tasks`, and `GET /api/nodes/{learningNode}/prerequisites` into `LearningNodeController`, `ListLearningNodesRequest`, and focused API Resources.
- Preserved active-only filtering, subject/type validation, ordered path nodes, no-answer-leak node task responses, and prerequisite response shape.
- Left Start Path enrollment, Task, Task Attempt, Review, and Progress route closures for later smaller extraction batches.

Commit:

- Recorded in this route extraction commit.

Checks:

- `php artisan test --filter='LearningPathApiTest|LearningNodeApiTest'` passed before the refactor: 19 tests, 313 assertions.
- `php artisan test --filter='LearningPathApiTest|LearningNodeApiTest'` passed after the refactor: 19 tests, 313 assertions.
- `php artisan test --filter='LearningPathApiTest|LearningNodeApiTest|TodaySelectorTest|PathProgressApiTest|OwnershipAndGuardrailTest'` passed: 35 tests, 598 assertions.
- `php artisan route:list --path=api --except-vendor` showed the User, Today, Learning Path, and Learning Node APIs routed through controllers.

Open risks:

- Backend issue `torbenseer/nuvio-backend#6` remains partially open until Enrollment/Task Attempt, Review, and Progress closures are extracted.
- Content validation and expanded seed breadth remain covered by backend issue `torbenseer/nuvio-backend#7`.

Next:

- Continue backend issue `torbenseer/nuvio-backend#6` with the Enrollment and Task/TaskAttempt extraction batch, or switch to validation matrix hardening if behavior coverage becomes more urgent.

## 2026-06-18 - B4 Enrollment And Task Attempt Route Extraction

Status: completed.

Changed:

- Continued backend issue `torbenseer/nuvio-backend#6` with the Enrollment, Task, and TaskAttempt extraction batch.
- Moved `POST /api/learning-paths/{learningPath}/start` into `EnrollmentController` and `EnrollmentResource`, preserving the existing `200` response status for created and existing Enrollments.
- Moved `GET /api/tasks/{task}` into `TaskController` and `TaskResource`, preserving the no-answer-leak task payload.
- Moved `POST /api/task-attempts/start` into `TaskAttemptController`, `StartTaskAttemptRequest`, and `StartedTaskAttemptResource`, preserving the existing `200` response status for started attempts.
- Moved `POST /api/task-attempts/{taskAttempt}/submit` into `TaskAttemptController`, `SubmitTaskAttemptRequest`, and `TaskAttemptResultResource`.
- Preserved deterministic numeric grading, unsure/skipped handling, review scheduling, mastery state updates, and the existing feedback response fields.
- Left Review due/read/snooze/answer and Progress route closures for later smaller extraction batches.

Commit:

- Recorded in this route extraction commit.

Checks:

- `php artisan test --filter='TaskAttemptFlowTest|MvpLearningLoopTest|ReviewSchedulerTest|TaskGraderTest|TodaySelectorTest'` passed before the refactor: 16 tests, 295 assertions.
- After initial extraction, focused tests caught Laravel Resource `201` statuses for newly created Enrollment and TaskAttempt models; controllers now explicitly preserve the existing `200` status.
- `php artisan test --filter='TaskAttemptFlowTest|MvpLearningLoopTest|ReviewSchedulerTest|TaskGraderTest|TodaySelectorTest'` passed after the fix: 16 tests, 295 assertions.
- `php artisan test --filter='OwnershipAndGuardrailTest|ReviewVersioningTest|ReviewDueApiTest'` passed: 11 tests, 118 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#6` remains partially open until Review and Progress route closures are extracted.
- `SubmitTaskAttemptRequest` now owns attempt authorization; broader validation and ownership matrix hardening remains covered by backend issue `torbenseer/nuvio-backend#4`.

Next:

- Continue backend issue `torbenseer/nuvio-backend#6` with Review due/read/snooze/answer extraction, then Progress extraction.

## 2026-06-18 - B4 Review Route Extraction

Status: completed.

Changed:

- Continued backend issue `torbenseer/nuvio-backend#6` with the Review API extraction batch.
- Moved `GET /api/reviews/due` into `ReviewController`, `ListDueReviewsRequest`, and `DueReviewResource`, preserving cap metadata and no hidden backlog counts.
- Moved `GET /api/reviews/{review}` into `ReviewController` and `ReviewDetailResource`, preserving TaskVersion pinning and no-answer-leak review payloads.
- Moved `POST /api/reviews/{review}/snooze` into `ReviewController`, `SnoozeReviewRequest`, and `SnoozedReviewResource`, preserving snooze-only scheduling behavior.
- Moved `POST /api/reviews/{review}/answer` into `ReviewController`, `AnswerReviewRequest`, and `ReviewAnswerResource`, preserving grading, review scheduling, mastery transitions, and feedback fields.
- Left only Progress route closures for the final backend issue `#6` extraction batch.

Commit:

- Recorded in this route extraction commit.

Checks:

- `php artisan test --filter='ReviewDueApiTest|ReviewVersioningTest|MvpLearningLoopTest|OwnershipAndGuardrailTest|ReviewSchedulerTest'` passed before the refactor: 14 tests, 181 assertions.
- `php artisan test --filter='ReviewDueApiTest|ReviewVersioningTest|MvpLearningLoopTest|OwnershipAndGuardrailTest|ReviewSchedulerTest'` passed after the refactor: 14 tests, 181 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#6` remains open only for Progress route extraction.
- `AnswerReviewRequest` and `SnoozeReviewRequest` now own review authorization; broader validation and ownership matrix hardening remains covered by backend issue `torbenseer/nuvio-backend#4`.

Next:

- Finish backend issue `torbenseer/nuvio-backend#6` with Progress Summary and Path Progress route extraction.

## 2026-06-18 - B4 Progress Route Extraction

Status: completed.

Changed:

- Finished backend issue `torbenseer/nuvio-backend#6` with the final Progress API extraction batch.
- Moved `GET /api/progress/summary` into `ProgressController` and `ProgressSummaryResource`.
- Moved `GET /api/progress/paths/{learningPath}` into `ProgressController` and `PathProgressResource`.
- Preserved path active checks, authenticated-user state isolation, competence-status counts, ordered node statuses, and pressure/gamification guardrails.
- Reduced `routes/api.php` to route declarations plus the small public `GET /api/status` closure.

Commit:

- Recorded in this route extraction commit.

Checks:

- `php artisan test --filter='PathProgressApiTest|MvpLearningLoopTest|OwnershipAndGuardrailTest'` passed before the refactor: 11 tests, 129 assertions.
- `php artisan test --filter='PathProgressApiTest|MvpLearningLoopTest|OwnershipAndGuardrailTest'` passed after the refactor: 11 tests, 129 assertions.
- `php artisan route:list --path=api --except-vendor` showed all learning-loop APIs except `GET /api/status` routed through controllers.

Open risks:

- Backend issue `torbenseer/nuvio-backend#6` is complete after this batch, but backend issue `#4` still needs the broader validation and ownership matrix over the extracted endpoints.
- Backend issue `#7` still needs content validation and expanded Algebra Foundations seed breadth.

Next:

- Close backend issue `torbenseer/nuvio-backend#6`, then continue B4 with backend issue `#4` validation and ownership matrix hardening.

## 2026-06-18 - B4 Task And Review Validation Matrix Slice

Status: completed.

Changed:

- Continued backend issue `torbenseer/nuvio-backend#4` with a focused TaskAttempt and Review interaction matrix slice.
- Added unauthenticated coverage for task read, task attempt start, task attempt submit, review answer, and review snooze routes.
- Added TaskAttempt submit validation coverage for missing payload, answer/result exclusivity, non-numeric answers, unsupported recovery results, foreign-user attempts, and already submitted attempts.
- Added Review answer validation coverage for missing payload, answer/result exclusivity, non-numeric answers, and unsupported recovery results.
- Added Review snooze conflict coverage for non-scheduled Reviews.
- Hardened `StartTaskAttemptRequest` so a TaskVersion must be active and belong to the submitted Task, returning Laravel JSON validation errors instead of a route-level not-found response.

Commit:

- Recorded in this validation matrix commit.

Checks:

- `php artisan test --filter=TaskAttemptFlowTest` passed: 6 tests, 40 assertions.
- `php artisan test --filter=ReviewDueApiTest` passed: 8 tests, 79 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#4` remains open for the remaining route groups, especially Learning Path/Node unauthenticated matrix gaps, Progress summary/path edge cases, and any remaining ownership isolation cases.
- Backend issue `torbenseer/nuvio-backend#7` still needs content validation and expanded Algebra Foundations seed breadth.
- Backend issue `torbenseer/nuvio-backend#1` still needs full Sanctum/CORS hardening.

Next:

- Continue backend issue `torbenseer/nuvio-backend#4` with the next small validation/ownership batch, likely Learning Path and Learning Node unauthenticated/not-found/filter coverage.

## 2026-06-18 - B4 Read API And Progress Matrix Slice

Status: completed.

Changed:

- Continued backend issue `torbenseer/nuvio-backend#4` with a broader Learning Path, Learning Node, Enrollment, and Progress matrix slice.
- Added a route-wide authenticated API matrix proving every learner-owned API route rejects unauthenticated requests with `401`, excluding only public `GET /api/status`.
- Added Learning Path route auth coverage for list, detail, and start routes.
- Added Learning Path start coverage for inactive and missing paths, existing active Enrollment reuse, and paused Enrollment reactivation without duplicate Enrollments.
- Added Learning Node route auth coverage for list, detail, tasks, and prerequisites routes.
- Added Progress Summary auth and authenticated-user isolation coverage across active paths, MasteryStates, and due Reviews.
- Preserved active-only read behavior, existing validation response shapes, path progress guardrails, and no pressure/gamification fields.

Commit:

- Recorded in this validation matrix commit.

Checks:

- `php artisan test --filter=LearningPathApiTest` passed: 8 tests, 78 assertions.
- `php artisan test --filter=LearningNodeApiTest` passed: 15 tests, 252 assertions.
- `php artisan test --filter=PathProgressApiTest` passed: 7 tests, 56 assertions.
- `php artisan test --filter=AuthenticatedApiMatrixTest` passed: 1 test, 20 assertions.
- `php artisan test --filter='AuthenticatedApiMatrixTest|LearningPathApiTest|LearningNodeApiTest|PathProgressApiTest'` passed: 31 tests, 406 assertions.

Open risks:

- Backend issue `torbenseer/nuvio-backend#4` remains open for final matrix review across remaining route-specific edge cases, especially Task read inactive/no-active-version behavior, Review read not-found/ownership gaps beyond existing smoke coverage, and any API spec/test-plan documentation alignment.
- Backend issue `torbenseer/nuvio-backend#7` still needs content validation and expanded Algebra Foundations seed breadth.
- Backend issue `torbenseer/nuvio-backend#1` still needs full Sanctum/CORS hardening.

Next:

- Finish backend issue `torbenseer/nuvio-backend#4` with a final route-specific edge-case pass, or move to backend issue `#7` if the remaining matrix risks are acceptable for now.
