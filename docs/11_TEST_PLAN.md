# Nuvio MVP Test Plan

Nuvio MVP is a Laravel API backend. The test suite must prove the core loop works:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

Backend MVP release readiness is defined in `docs/14_RELEASE_ROADMAP.md`. Content validation tests are required for B2/B4 readiness.

## 1. Testing Goals

The MVP tests should prove:

- SkillGraph relationships work.
- Content can be seeded or imported safely.
- Users can start learning paths.
- Tasks can be started and attempted.
- Reviews are created from weak evidence.
- MasteryStates update from attempts and reviews.
- Today selection is capped and ADHD-friendly.
- Later features are tested separately when they are implemented.

## 2. Unit Tests

Use unit tests for deterministic services.

Primary unit test targets:

- `TaskGrader`
- `ReviewScheduler`
- `TodaySelector`
- `MasteryUpdater`
- `ContentValidator`

Expected style:

```php
public function test_correct_numeric_answer_within_tolerance_is_correct(): void
{
    $result = app(TaskGrader::class)->grade($taskVersion, ['value' => 3.01]);

    $this->assertTrue($result->isCorrect());
}
```

## 3. Feature Tests

Use feature tests for full backend flows.

Core feature tests:

- Register or authenticate user.
- Start a LearningPath.
- Open Today.
- Start task attempt.
- Submit answer.
- Review is created when needed.
- Answer review.
- Progress summary updates.

Recommended full-flow test:

- `MvpLearningLoopTest`

Flow:

1. User starts a path.
2. `GET /api/today` returns a task action.
3. User starts task.
4. User submits incorrect answer.
5. Review is created.
6. Review is answered correctly.
7. MasteryState updates.

## 4. API Tests

API tests should verify routes, status codes, response shape, authorization, and side effects.

V1 required endpoint coverage:

- `GET /api/today`
- `POST /api/learning-paths/{id}/start`
- `GET /api/tasks/{id}`
- `POST /api/task-attempts/start`
- `POST /api/task-attempts/{id}/submit`
- `GET /api/reviews/{id}`
- `POST /api/reviews/{id}/answer`
- `GET /api/progress/summary`

B4 endpoint coverage:

- `POST /api/today/mode`
- `GET /api/learning-paths`
- `GET /api/learning-paths/{id}`
- `GET /api/nodes`
- `GET /api/nodes/{id}`
- `GET /api/nodes/{id}/tasks`
- `GET /api/nodes/{id}/prerequisites`
- `GET /api/reviews/due`
- `POST /api/reviews/{id}/snooze`
- `GET /api/progress/paths/{id}`

Later endpoint coverage:

- `POST /api/task-attempts/{id}/self-check`

Assertions:

- Authenticated routes reject unauthenticated users.
- User-owned state is not visible to other users.
- Response JSON does not expose answer schemas before attempts.
- Validation errors return `422`.

Current B4 matrix anchors:

- `AuthSessionTest` covers the Sanctum CSRF cookie route, credentialed CORS for the configured frontend origin, unconfigured-origin CORS hardening, SPA login, logout, and authenticated cross-origin API requests.
- `AuthenticatedApiMatrixTest` covers `401` rejection across all learner-owned API routes except public `GET /api/status`.
- `UserEndpointTest` covers user preference auth, valid updates, and locale/timezone validation.
- `TodayModeTest` and `TodaySelectorTest` cover Energy Mode validation, Today caps, pressure-field guardrails, and unsupported Today query filters.
- `LearningPathApiTest` and `LearningNodeApiTest` cover active-only reads, invalid filters, `404` behavior, authentication, no-answer-leak task lists, prerequisites, and Enrollment idempotency/reactivation.
- `TaskAttemptFlowTest` covers Task read `404` behavior, start validation, submit validation, ownership, duplicate submit conflict, and no answer leaks.
- `ReviewDueApiTest`, `ReviewVersioningTest`, and `OwnershipAndGuardrailTest` cover due Review caps, Review read/answer/snooze auth and ownership, validation, conflict behavior, TaskVersion pinning, and pressure guardrails.
- `PathProgressApiTest` covers Progress Summary and Path Progress authentication, user-state isolation, inactive path/node behavior, and pressure-field guardrails.

## 5. Content Validation Tests

Content validation tests should protect import quality.

Current anchors:

- `ContentValidatorTest` covers duplicate and invalid slugs, missing Subject and LearningNode references, invalid NodeRelations, unsupported relation types, LearningPath node references/order, Tasks without LearningNode links, invalid numeric answer schemas, missing prompt/explanation fields, and active TaskVersion constraints.
- `SeedContentTest` runs `php artisan nuvio:content:validate` against the seeded database content.

Required tests:

- Subject slugs are unique.
- LearningNode slugs are unique.
- LearningNode references existing Subjects.
- NodeRelation references existing nodes.
- NodeRelation cannot point from a node to itself.
- LearningPath orders nodes correctly.
- Task has at least one LearningNode.
- Task can belong to multiple LearningNodes.
- TaskVersion has prompt, answer schema, and explanation.
- Numeric task has `correctValue` and `tolerance`.
- Multiple choice task has exactly one correct choice.
- Content import rejects invalid tasks.

Example:

```php
public function test_content_import_rejects_task_without_learning_node(): void
{
    $result = app(ContentValidator::class)->validateTask($payload);

    $this->assertTrue($result->hasError('learningNodes'));
}
```

## 6. Review Scheduler Tests

ReviewScheduler is a core MVP service and must have strong coverage.

Required tests:

- Incorrect answer creates Review due in 1 day.
- Unsure attempt creates Review due in 1 day.
- Skipped attempt creates Review due in 1 day.
- Correct normal answer does not create a Review in the narrow MVP, and the test name documents this as a narrow MVP limitation.
- B4 coverage must add retention-review scheduling for correct first-time answers.
- Successful review marks Review completed.
- Successful review moves MasteryState to `retained`.
- Failed review resets interval to 1 day.
- Duplicate weak attempts update existing Review instead of creating many duplicates.
- Parallel weak attempts for the same user, LearningNode, and Task create or keep only one active scheduled Review.
- Completed or suspended Reviews cannot be answered through the normal answer endpoint.

Use Laravel time helpers:

```php
Carbon::setTestNow('2026-06-13 09:00:00');
```

## 7. Today Selector Tests

TodaySelector must stay simple, capped, and explainable.

Required tests:

- `GET /api/today` returns no more than 3 actions.
- Due reviews appear before new tasks.
- Due reviews appear before Start Path, and Start Path appears before a new Task when no enrollment exists.
- Due reviews are ordered by oldest `due_at` first.
- User with no enrollment gets a start-path action.
- Today does not expose `reason`, `mode`, `hidden_due_reviews`, backlog counts, or pressure state.
- `GET /api/today?mode=red` is not part of V1 behavior.
- Missed reviews do not create an overwhelming backlog.
- User with completed path and due reviews gets review actions.

Example:

```php
$this->actingAs($user)
    ->getJson('/api/today')
    ->assertOk()
    ->assertJsonCount(3, 'data');
```

## 8. Mastery State Tests

MasteryState tests should prove progress is based on evidence, not only time.

Required tests:

- New user starts with no MasteryState or status `unknown`.
- Correct task moves state to `practiced`.
- Incorrect attempt moves state to `review_due`.
- Unsure attempt moves state to `review_due`.
- Skipped attempt moves state to `review_due`.
- Passing a review updates MasteryState.
- Successful review can move `review_due` to `retained`.
- Mastery score remains within bounds.

## 9. Task Attempt Tests

Required tests:

- Starting a task creates a TaskAttempt.
- Started attempt references TaskVersion.
- Submitting a correct numeric answer marks attempt as correct.
- Numeric tolerance is respected.
- Incorrect numeric answer marks attempt incorrect.
- Multiple choice correct answer marks attempt correct in B4 tests only.
- Self-check `unsure` result is stored in Later tests only.
- Self-check `skipped` result is stored in Later tests only.
- Auto-graded submit accepts `result: unsure` without an answer.
- Auto-graded submit accepts `result: skipped` without an answer.
- Auto-graded submit rejects requests containing both `answer` and `result`.
- Submitted attempt cannot be submitted twice.
- Duplicate submit consistently returns `409` or consistently returns the stored result idempotently; the implementation must choose one behavior and test it.
- Submit is atomic or tests prove no partial TaskAttempt, Review, or MasteryState remains after injected scheduler/mastery failure.
- Attempt belonging to another user cannot be submitted.
- TaskAttempt is graded against stored TaskVersion even if a newer version exists.
- Task read and review read responses do not expose correct answers, accepted values, grading tolerances, canonical solutions, or hidden correctness metadata.

## 10. Learning Path Tests

Required tests:

- LearningPath orders nodes correctly.
- User can start a LearningPath.
- Starting same path twice is idempotent.
- Enrollment belongs to user.
- Path progress excludes another user's state.
- Prerequisite NodeRelations can be queried.

Relationship tests:

- LearningNode can belong to multiple Subjects.
- Task can belong to multiple LearningNodes.
- LearningPath can contain the same LearningNode only once unless explicitly allowed.

## 11. Later Simulation Tests

Not part of the MVP. Add only after simulation support is explicitly requested.

Required tests:

- SimulationDefinition links to LearningNodes.
- SimulationDefinition exposes frontend component key.
- Starting simulation creates SimulationRun.
- Completing simulation stores output payload.
- SimulationRun belongs to user.
- Completed simulation does not update MasteryState unless evaluator rule allows it.

## 12. Motivation Without Pressure Guardrail Tests

Required for V1/B4 where responses, fixtures, or copy are added.

Required tests:

- Today and Progress responses do not include XP, badge, achievement, streak, rank, reward level, catch-up, or lost-progress fields.
- Today, Task, Review, Feedback, and Progress responses do not include XP, Badges, Achievements, Streaks, Streak Freezes, Leaderboards, Levels as rewards, Reward Inventory, Catch-up Goals, Backlog Debt, Missed-Day Counts, Lost Progress, Daily Pressure, Countdown Pressure, Artificial Scarcity, or Lootbox/Slot-Machine metadata.
- Progress summary uses MasteryStates, Reviews, TaskAttempts, and LearningPath order.
- Hidden backlog counts are not returned from V1 UI-facing responses; any later internal count must not render as learner-facing debt.
- `reviews_due` is tested as neutral orientation, not as a debt, remainder, or missed-work count.
- Unsure, Skip, and Snooze paths use neutral result states.
- Missed days do not create loss, repair, or penalty events.
- Copy fixtures avoid "catch up", "behind", "failed", "lost progress", and streak-protection language.
- V1 responses do not include `completion_state`, `mastery_moment`, `challenge_options`, `mastery_score`, `returning_after_break`, `percent_complete`, `hidden_due_reviews`, `reason`, or `mode`.
- B4 Completion States are returned only after real task or review actions.
- B4 Mastery Moments are returned only after correct Review or retained transition.
- Later Skill-Map responses show `unknown`, `practiced`, `review_due`, or `retained` only.
- Later challenge options contain only deterministic learning choices and no reward choices.
- No response fixture includes countdown pressure, artificial scarcity, lootbox-like reveal, comeback streak, or attendance reward.

## 13. API Contract Drift Tests

Required before frontend integration:

- Backend API feature tests assert the exact V1 endpoint set from `05_API_SPEC.md`.
- JSON response tests cover required fields and forbidden fields for Today, Task read, Task submit, Review read, Review answer, and Progress Summary.
- A contract fixture or generated schema comparison checks that frontend MSW/Zod fixtures do not expect fields missing from the Laravel API.
- Real backend smoke tests cover the V1 loop separately from frontend MSW contract tests.
- MSW fixtures are treated as frontend contract tests only; they do not replace Laravel feature tests.
- Any addition of Energy Mode, Reviews Due, Snooze, Skill-Map, Challenge Options, or Self-check must fail V1 contract tests unless the endpoint is explicitly moved out of B4/Later in the API spec.

## 14. Later AI Teacher Guardrail Tests

AI teacher is not MVP. Add these only after AI endpoints or scaffolding are explicitly requested.

Required tests:

- User cannot request hint for another user's TaskAttempt.
- First hint does not reveal full solution.
- Check-answer mode does not update TaskAttempt result.
- Check-answer mode does not update MasteryState.
- Generate-review-card mode returns draft requiring confirmation.
- Unsafe request is refused.
- AI interaction is logged.

## 15. Edge Cases

Required edge-case coverage:

- Task linked to multiple LearningNodes creates Review for primary node.
- TaskVersion changes after attempt starts.
- Review points to archived task.
- User has many overdue reviews.
- User marks many tasks unsure in one session.
- Content import references missing LearningNode.
- Snoozed review does not improve mastery when B4 implements Snooze.
- Duplicate submit/race conditions do not create partial state.
- Completed Review cannot be answered again.
- Attempts and Reviews remain user-isolated.
- Answer leak prevention remains covered for Task and Review reads.

## 16. Regression Test Priorities

Highest priority regressions:

1. Today returns more than 3 actions.
2. Incorrect, unsure, or skipped attempt does not create Review.
3. Correct normal attempts do not create Reviews in the narrow MVP.
4. Review answer does not update MasteryState.
5. TaskAttempt loses TaskVersion history.
6. LearningNode loses multi-subject support.
7. Task loses multi-node support.
8. Content import accepts invalid task.
9. Private user state leaks.
10. API responses drift from the V1 frontend contract or expose forbidden pressure/reward fields.
11. Task or Review read leaks the correct answer.
12. Review Answer accepts a completed or suspended Review.

## 17. Definition Of Done

The MVP test suite is done when:

- Migrations run in test environment.
- Factories exist for core models.
- Model relationships are tested.
- Content validation has negative tests.
- Task grading has unit tests.
- Task attempt API has feature tests.
- ReviewScheduler has schedule and duplicate tests.
- Contract drift tests distinguish frontend MSW/Zod fixtures from real Laravel smoke tests.
- Forbidden reward/pressure fields and forbidden copy are covered by regression tests.
- TodaySelector has cap, priority, start-path, review-before-task, and forbidden-field tests.
- MasteryState updates are tested from attempts and reviews.
- LearningPath enrollment and progress are tested.
- Later-feature tests are absent from the MVP suite unless those features are explicitly implemented.
- All tests run from a fresh checkout with `php artisan test`.
