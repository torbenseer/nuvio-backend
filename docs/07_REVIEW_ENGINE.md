# Nuvio Review Engine Specification

Reviews are a core learning mechanism in Nuvio. The review engine should be simple, motivating, deterministic, and ADHD-friendly.

## 1. Purpose Of Reviews

Reviews help learners retain knowledge after initial practice.

The review engine exists to:

- Turn weak attempts into future practice.
- Keep learned material active over time.
- Prioritize retention before new learning.
- Reduce the need for users to decide what to study.
- Support progress based on practice and retention, not time spent.

## 2. Review Principles

- Reviews are normal learning work, not punishment.
- Incorrect, unsure, and skipped attempts create review work.
- Reviews should be short.
- Today should show max 3 due reviews.
- The UI should never display a huge backlog.
- Missed reviews should remain due and be surfaced gradually through the Today cap.
- Messaging must never shame the user.
- The schedule should be easy to test and explain.

## 3. Review Triggers

Reviews can be created or updated when:

- A TaskAttempt is incorrect.
- A TaskAttempt is unsure.
- A TaskAttempt is skipped.
- A review attempt is incorrect, unsure, or skipped.

Correct normal task attempts do not create retention reviews in the narrow MVP. This is a narrow MVP limitation, not a learning claim: V1 can mark first-attempt success as practiced, but it may not yet prove retention over time.

B4 must add retention-review scheduling for correct first-time task solutions so that important skills are revisited after initial success.

## 4. Review Schedule

Default first schedule:

- Incorrect: review in 1 day.
- Unsure: review in 1 day.
- Skipped: review in 1 day.

Successful review:

- Mark the Review as `completed`.
- Move related MasteryState to `retained`, meaning successful review evidence has been observed.

Failed, skipped, or unsure review:

- Reschedule in 1 day.
- Increment lapse count.
- Mark related MasteryState as `review_due`.

## 5. How Attempts Create Reviews

When a TaskAttempt is completed:

1. Find LearningNodes linked to the Task.
2. For each primary LearningNode, determine whether the result is weak evidence.
3. Find existing active Review for the user, node, and task.
4. Create or update the Review.
5. Update MasteryState.

Rules:

- Incorrect attempts must create or update a Review.
- Unsure attempts must create or update a Review.
- Skipped attempts must create or update a Review.
- Correct task attempts do not create Reviews in the narrow MVP.
- Duplicate Reviews for the same user, LearningNode, and Task should be avoided.

## 6. How Reviews Update Mastery

MasteryState statuses:

- `unknown`: no evidence yet.
- `practiced`: user has successful practice evidence.
- `review_due`: review is due or recent evidence is weak.
- `retained`: user has successful review evidence. It is not a permanent mastery guarantee and must not be presented as a strong claim that the learner will always know the skill.

Update rules:

- Correct normal attempt can move `unknown` to `practiced`.
- Incorrect or unsure attempt can move status to `review_due`.
- Skipped attempt can move status to `review_due`.
- Correct review can move `review_due` to `retained` in V1.
- Repeated incorrect reviews can move `retained` back to `review_due`.
- B4 should define `retained` more strictly as successful time-delayed retrieval, either after a minimum spacing interval or after multiple successful retrievals. This keeps Mastery Moments tied to real retention evidence rather than one immediate success.

Mastery score, if used, must remain bounded, such as 0 to 100.

## 7. Confidence Handling

Confidence and help-used evidence are not part of the narrow MVP.

B4 must add:

- Retention reviews for correct first-time answers.

Later phases may add:

- `confidence`: `low`, `medium`, `high`, or null.
- `help_used`: boolean.
- Longer spaced repetition intervals such as 7, 21, and 60 days.
- Transfer tasks that check whether a skill works in a changed context.
- Variation mechanisms that avoid repeating only the same prompt shape.
- Reflection prompts where the learner can name the strategy used.
- Error-analysis mechanisms that classify recurring mistake patterns without shame.

Until that phase begins, review scheduling depends only on `correct`, `incorrect`, `unsure`, and `skipped`.

## 8. Incorrect Answer Handling

Incorrect answers should produce useful review work without shame.

Backend behavior:

- Store TaskAttempt as `incorrect`.
- Create or update Review due in 1 day.
- Increment incorrect count on MasteryState.
- Move MasteryState to `review_due` when appropriate.
- Return feedback that is factual and supportive.

Do not:

- Mark the learner as a failure.
- Display punitive streak loss.
- Create duplicate Reviews for repeated incorrect attempts on the same task.

## 9. Unsure Answer Handling

Unsure is a first-class attempt outcome.

Backend behavior:

- Store TaskAttempt as `unsure`.
- Create or update Review due in 1 day.
- Increment unsure count on MasteryState.
- Move MasteryState to `review_due`.

Rationale:

- Unsure means the learner has identified weak knowledge.
- It should be treated as useful evidence, not as cheating or failure.

## 10. Review Caps

ADHD-friendly cap:

- Today shows max 3 due reviews per day.

API behavior:

- `GET /api/today` returns max 3 actions total.
- `GET /api/reviews/due` is B4. It should accept a limit and default to a small number when implemented.
- V1/B4 UI-facing responses must not include `hidden_due_reviews`; internal observability may track backlog counts outside learner-facing API.

Selection priority when many reviews are due:

1. Overdue reviews.
2. Reviews from active enrollments.
3. Lower mastery states.
4. Shorter estimated duration.
5. Older due date.

## 11. Snooze Behavior

Status: **B4 hardening**. V1 review screens must not depend on Snooze.

Snooze allows the user to delay a review without improving mastery.

Rules:

- Snooze does not count as practice.
- Snooze does not improve MasteryState.
- Snooze should move `due_at` by a short period, such as 1 hour or 1 day.
- Repeated snoozes can keep the review visible later but should not create shame messaging.

Suggested limits:

- Minimum snooze: 15 minutes.
- Maximum snooze: 1 day.

## 12. Missed Reviews

Missed reviews should remain due. The system manages pressure by capping what Today shows, not by silently moving old due dates.

Rules:

- Do not show all missed reviews at once.
- Do not punish missed days.
- Keep Review due dates intact for prioritization.
- Today selector should expose only the top capped set.
- Very old missed reviews can be reintroduced gradually through capped Today selection.

Optional later behavior:

- If a review is overdue by more than 14 days, mark as `review_due` and schedule a short reactivation task.

## 13. Today Selector Integration

Today selection should ask the review engine for due reviews first.

Priority:

1. Due reviews.
2. Start Path when the user has no active enrollment.
3. Next task in active LearningPath.

Constraints:

- Return at most three actions.
- V1 accepts no `mode` input and does not apply Energy Mode.
- B4 Energy Mode may prefer short actions for low-energy mode when `POST /api/today/mode` is implemented.
- Review backlog must not be displayed as a long list or debt count.

Tie-breakers:

- Due reviews are ordered by oldest `due_at` first.
- Path tasks are selected by LearningPathNode position, then Task difficulty, then Task ID.

## 14. Edge Cases

- Task belongs to multiple LearningNodes: create reviews for primary nodes first; secondary nodes can update MasteryState lightly.
- TaskVersion changed after attempt started: grade against the TaskVersion on the attempt.
- User submits same attempt twice: reject or return existing result.
- Submit and Review Answer must be atomic or covered by tests that prove partial state cannot remain after a failure.
- Duplicate submit must consistently return `409` or consistently return the stored result idempotently.
- Parallel weak attempts must not create duplicate active Reviews.
- Completed or suspended Reviews cannot be answered through the normal answer path.
- Attempts and Reviews are always scoped to the authenticated user.
- Review points to archived task: select another task from the same LearningNode.
- User has no enrollments: Today can recommend starting a path.
- User has many overdue reviews: show only the capped set.
- User marks unsure repeatedly: keep one active review and update due date or lapse count.

## 15. Data Model

### Review

Suggested fields:

- `id`
- `user_id`
- `learning_node_id`
- `task_id` nullable
- `status`
- `due_at` nullable when completed
- `interval_days`
- `lapses`
- `last_attempted_at`
- `snoozed_until` nullable
- `created_at`
- `updated_at`

Statuses:

- `scheduled`
- `completed`
- `suspended`

Due status is computed from `status = scheduled` and `due_at <= now()`.

### TaskAttempt

Review-related fields:

- `id`
- `user_id`
- `task_id`
- `task_version_id`
- `review_id` nullable
- `status`
- `result`
- `submitted_answer` JSON nullable
- `started_at`
- `completed_at` nullable

### MasteryState

Review-related fields:

- `id`
- `user_id`
- `learning_node_id`
- `status`
- `mastery_score`
- `correct_attempts`
- `incorrect_attempts`
- `unsure_attempts`
- `review_successes`
- `last_practiced_at`
- `last_reviewed_at`

## 16. Pseudocode

### ReviewScheduler

```php
final class ReviewScheduler
{
    public function scheduleAfterAttempt(TaskAttempt $attempt): void
    {
        $nodes = $attempt->task->learningNodes()
            ->wherePivot('role', 'primary')
            ->get();

        foreach ($nodes as $node) {
            $days = $this->initialDelayDays($attempt);

            if ($days === null) {
                continue;
            }

            Review::query()->updateOrCreate(
                [
                    'user_id' => $attempt->user_id,
                    'learning_node_id' => $node->id,
                    'task_id' => $attempt->task_id,
                    'status' => 'scheduled',
                ],
                [
                    'due_at' => now()->addDays($days),
                    'interval_days' => $days,
                    'last_attempted_at' => $attempt->completed_at,
                ]
            );
        }
    }

    private function initialDelayDays(TaskAttempt $attempt): ?int
    {
        if (in_array($attempt->result, ['incorrect', 'unsure', 'skipped'], true)) {
            return 1;
        }

        return null;
    }

    public function applyReviewAnswer(Review $review, TaskAttempt $attempt): Review
    {
        if ($attempt->result === 'correct') {
            $review->fill([
                'interval_days' => null,
                'due_at' => null,
                'last_attempted_at' => now(),
                'status' => 'completed',
            ])->save();

            return $review;
        }

        $review->fill([
            'lapses' => $review->lapses + 1,
            'interval_days' => 1,
            'due_at' => now()->addDay(),
            'last_attempted_at' => now(),
            'status' => 'scheduled',
        ])->save();

        return $review;
    }
}
```

### TodaySelector

```php
final class TodaySelector
{
    public function actionsFor(User $user): array
    {
        $limit = 3;

        $reviews = Review::query()
            ->where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit($limit)
            ->get()
            ->map(fn (Review $review) => TodayAction::review($review));

        if ($reviews->count() >= $limit) {
            return $reviews->take($limit)->values()->all();
        }

        $remaining = $limit - $reviews->count();

        $pathActions = $this->nextPathActions($user, $remaining);

        return $reviews
            ->concat($pathActions)
            ->take($limit)
            ->values()
            ->all();
    }
}
```

## 17. Test Cases

### Scheduling

- Correct normal attempt does not create a Review.
- Incorrect attempt schedules review in 1 day.
- Unsure attempt schedules review in 1 day.
- Skipped attempt schedules review in 1 day.

### Review Updates

- Successful review marks Review completed.
- Successful review moves MasteryState to `retained`.
- Failed review resets interval to 1 day.
- Unsure review resets interval to 1 day.
- Skipped review resets interval to 1 day.

### Caps And Today

- Today shows max 3 due reviews.
- Today does not return a huge backlog.
- V1 Today accepts no Energy Mode input.
- B4 Energy Mode filters to reviews or tasks of max 15 minutes when available.
- Due reviews are selected before new path work.

### Mastery

- Incorrect attempt moves MasteryState to `review_due`.
- Unsure attempt moves MasteryState to `review_due`.
- Skipped attempt moves MasteryState to `review_due`.
- Correct review can move MasteryState to `retained`.

### Edge Cases

- Duplicate incorrect attempts update existing Review.
- Parallel weak attempts do not create duplicate active Reviews.
- Completed or suspended Reviews cannot be answered again.
- Review with archived task selects replacement task from same LearningNode.
- Attempt is graded against stored TaskVersion.
- Snooze does not improve mastery when B4 implements it.

## 18. Acceptance Criteria

- Reviews are created from incorrect, unsure, and skipped attempts.
- Correct normal task attempts do not create retention reviews in the narrow MVP.
- This lack of retention scheduling for correct first attempts is explicitly documented as a narrow MVP limitation.
- B4 schedules retention reviews for correct first attempts.
- Successful reviews are completed and update MasteryState to `retained`.
- Today shows max 3 due reviews.
- Review backlog is summarized, not displayed as a long list.
- Missed reviews remain due and are reintroduced through capped Today selection.
- Snoozing does not improve mastery when B4 implements it.
- Review updates are deterministic and covered by tests.
- Failure messaging is never shame-based.
- `retained` means successful review evidence, not permanent mastery.
- Transfer, variation, reflection, and error-analysis mechanisms are tracked as B4/Later learning gaps until specified.
