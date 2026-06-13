# Nuvio MVP Test Plan

Nuvio MVP is a Laravel API backend. The test suite must prove the core loop works:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

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

Required endpoint coverage:

- `GET /api/today`
- `POST /api/today/mode`
- `GET /api/learning-paths`
- `GET /api/learning-paths/{id}`
- `POST /api/learning-paths/{id}/start`
- `GET /api/enrollments/{id}/progress`
- `GET /api/nodes`
- `GET /api/nodes/{id}`
- `GET /api/nodes/{id}/tasks`
- `GET /api/nodes/{id}/prerequisites`
- `GET /api/tasks/{id}`
- `POST /api/task-attempts/start`
- `POST /api/task-attempts/{id}/submit`
- `POST /api/task-attempts/{id}/self-check`
- `GET /api/reviews/due`
- `POST /api/reviews/{id}/answer`
- `POST /api/reviews/{id}/snooze`
- `GET /api/progress/summary`
- `GET /api/progress/paths/{id}`

Assertions:

- Authenticated routes reject unauthenticated users.
- User-owned state is not visible to other users.
- Response JSON does not expose answer schemas before attempts.
- Validation errors return `422`.

## 5. Content Validation Tests

Content validation tests should protect import quality.

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
- Correct normal answer does not create a Review in the narrow MVP.
- Successful review marks Review completed.
- Successful review moves MasteryState to `retained`.
- Failed review resets interval to 1 day.
- Duplicate weak attempts update existing Review instead of creating many duplicates.

Use Laravel time helpers:

```php
Carbon::setTestNow('2026-06-13 09:00:00');
```

## 7. Today Selector Tests

TodaySelector must stay simple, capped, and explainable.

Required tests:

- `GET /api/today` returns no more than 3 actions.
- Due reviews appear before review-due nodes and new tasks.
- Due reviews are ordered by oldest `due_at` first.
- Review-due nodes appear before new path work.
- Red mode returns tasks <= 15 minutes when such tasks exist.
- Yellow mode can return normal 25 to 60 minute learning actions.
- Missed reviews do not create an overwhelming backlog.
- Hidden backlog count can be returned without listing every review.
- User with no enrollment gets a start-path action.
- User with completed path and due reviews gets review actions.

Example:

```php
$this->actingAs($user)
    ->getJson('/api/today?mode=red')
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
- Multiple choice correct answer marks attempt correct.
- Short text normalization works.
- Self-check `unsure` result is stored.
- Self-check `skipped` result is stored.
- Submitted attempt cannot be submitted twice.
- Attempt belonging to another user cannot be submitted.
- TaskAttempt is graded against stored TaskVersion even if a newer version exists.

## 10. Learning Path Tests

Required tests:

- LearningPath orders nodes correctly.
- User can start a LearningPath.
- Starting same path twice is idempotent.
- Enrollment belongs to user.
- Enrollment progress uses MasteryStates.
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

## 12. Later Gamification Tests

Not part of the MVP. Add only after XP events or achievements are explicitly requested.

Required tests:

- Correct task can create XpEvent.
- Completed review can create XpEvent.
- XP event references source action.
- Achievement is awarded once.
- Gamification does not determine MasteryState.
- Missed days do not create shame-based penalty events.

## 13. Later AI Teacher Guardrail Tests

AI teacher is not MVP. Add these only after AI endpoints or scaffolding are explicitly requested.

Required tests:

- Unknown mode is rejected.
- User cannot request hint for another user's TaskAttempt.
- First hint does not reveal full solution.
- Check-answer mode does not update TaskAttempt result.
- Check-answer mode does not update MasteryState.
- Generate-review-card mode returns draft requiring confirmation.
- Unsafe request is refused.
- AI interaction is logged.

## 14. Edge Cases

Required edge-case coverage:

- Task linked to multiple LearningNodes creates Review for primary node.
- TaskVersion changes after attempt starts.
- Review points to archived task.
- User has many overdue reviews.
- User marks many tasks unsure in one session.
- Red mode has no short actions available.
- Content import references missing LearningNode.
- Snoozed review does not improve mastery.

## 15. Regression Test Priorities

Highest priority regressions:

1. Today returns more than 3 actions.
2. Incorrect, unsure, or skipped attempt does not create Review.
3. Correct normal attempts create Reviews in the narrow MVP.
4. Review answer does not update MasteryState.
5. TaskAttempt loses TaskVersion history.
6. LearningNode loses multi-subject support.
7. Task loses multi-node support.
8. Content import accepts invalid task.
9. Private user state leaks.

## 16. Definition Of Done

The MVP test suite is done when:

- Migrations run in test environment.
- Factories exist for core models.
- Model relationships are tested.
- Content validation has negative tests.
- Task grading has unit tests.
- Task attempt API has feature tests.
- ReviewScheduler has schedule and duplicate tests.
- TodaySelector has cap, priority, red mode, and backlog tests.
- MasteryState updates are tested from attempts and reviews.
- LearningPath enrollment and progress are tested.
- Later-feature tests are absent from the MVP suite unless those features are explicitly implemented.
- All tests run from a fresh checkout with `php artisan test`.
