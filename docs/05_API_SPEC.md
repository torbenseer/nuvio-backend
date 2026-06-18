# Nuvio MVP API Specification

Nuvio is a Laravel API backend for an adult learning platform. The MVP API supports the loop:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

## Conventions

Base path: `/api`

Authentication: Laravel Sanctum for authenticated learner endpoints.

This document is canonical for MVP route names, request shapes, response shapes, and endpoint behavior. Other planning documents should be aligned to it when they disagree.

Endpoint status labels:

- **V1 required**: needed for the first integrated learning loop.
- **B4 hardening**: required for full Backend MVP API completeness before Private Alpha, but not required before first frontend learning.
- **Later**: outside the narrow MVP route set.

Response shape:

```json
{
  "data": {},
  "meta": {}
}
```

Validation error shape:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Specific validation message."]
  }
}
```

Common errors:

- `401` unauthenticated.
- `403` forbidden.
- `404` not found.
- `422` validation failed.

## 1. Auth And Preferences

Nuvio uses Laravel Sanctum cookie-based SPA authentication.

### GET /sanctum/csrf-cookie

Status: **V1 required** if V1 uses Sanctum login; otherwise **B4 hardening** after a pre-provisioned learner is used internally.

Purpose: Issue the CSRF cookie required before login and state-changing SPA requests.

### POST /login

Status: **V1 required** if V1 uses Sanctum login; otherwise **B4 hardening** after a pre-provisioned learner is used internally.

Purpose: Authenticate a learner through Laravel's cookie session flow.

### POST /logout

Status: **V1 required** if V1 uses Sanctum login; otherwise **B4 hardening** after a pre-provisioned learner is used internally.

Purpose: End the current authenticated browser session.

### GET /api/user

Status: **V1 required**

Purpose: Return the authenticated learner and persisted locale preferences.

Response example:

```json
{
  "data": {
    "id": 1,
    "name": "Ada Learner",
    "email": "ada@example.com",
    "locale": "de",
    "timezone": "Europe/Berlin"
  }
}
```

Error cases:

- `401` if unauthenticated.

### PUT /api/user/preferences

Status: **B4 hardening**. May move into **V1 required** only if persisted locale/timezone preferences are implemented in the first slice.

Purpose: Persist narrow learner preferences needed by the first frontend slice.

Request example:

```json
{
  "locale": "de",
  "timezone": "Europe/Berlin"
}
```

Response example:

```json
{
  "data": {
    "locale": "de",
    "timezone": "Europe/Berlin"
  }
}
```

Validation:

- `locale` is required and must be a supported BCP 47 UI locale.
- `timezone` is required and must be an IANA timezone.

Content ownership:

- Backend owns learning content and feedback display strings.
- Frontend owns app chrome translations.

Registration:

- The backend MVP may provide registration support for API tests and local setup.
- The first frontend slice does not include signup or password-reset UI.

## 2. Today

### GET /api/today

Status: **V1 required**

Purpose: Return up to three recommended actions for the authenticated user.

Request example. V1 sends no query parameters:

```json
{}
```

Response example:

```json
{
  "data": [
    {
      "type": "review",
      "title": "Lineare Gleichungen kurz auffrischen",
      "estimated_minutes": 5,
      "priority": 1,
      "target": {
        "type": "review",
        "id": 501
      }
    }
  ],
  "meta": {
    "limit": 3
  }
}
```

Validation:

- V1 accepts no Today filters or query parameters.
- Requests such as `GET /api/today?mode=red` are outside V1. Energy Mode is B4 through `POST /api/today/mode`.

Side effects:

- None. Today actions are computed on request.

Error cases:

- `401` if unauthenticated.

Acceptance criteria:

- Returns at most three actions.
- Response includes `meta.limit`.
- Each action title is concrete enough to start without planning, such as a LearningNode-based task or review title.
- Selects actions in this deterministic order:
  1. Due reviews ordered by `due_at`.
  2. Start Path when the user has no active enrollment.
  3. The next Task in the active LearningPath.
- Does not expose review backlog counts.
- Does not expose streak, XP, badge, achievement, rank, reward level, catch-up, or lost-progress fields.
- Does not expose `reason`, `mode`, `hidden_due_reviews`, `overdue_count`, missed-day, catch-up, debt, or pressure-state fields.
- Review actions are ready-to-review work, not overdue work.

### POST /api/today/mode

Status: **B4 hardening**. V1 does not accept a Today mode query parameter and does not persist Energy Mode.

Purpose: Store or update the user's current energy mode.

Request example:

```json
{
  "mode": "yellow"
}
```

Response example:

```json
{
  "data": {
    "mode": "yellow"
  }
}
```

Validation:

- `mode` is required and must be `red`, `yellow`, or `green`.

Side effects:

- Updates user preference for later Today requests.

Error cases:

- `401` if unauthenticated.
- `422` if mode is invalid.

Acceptance criteria:

- Stored mode affects later Today selection.
- Red mode keeps recommended work short.

## 3. Learning Paths

### GET /api/learning-paths

Status: **B4 hardening**. V1 enters the single Algebra Foundations path through the `start_path` Today action.

Purpose: List active learning paths.

Request example:

```json
{
  "query": {
    "subject": "math"
  }
}
```

Response example:

```json
{
  "data": [
    {
      "id": 10,
      "slug": "algebra-foundations",
      "title": "Algebra Foundations",
      "subject": "Math",
    "estimated_minutes": 15,
    "node_count": 3
    }
  ]
}
```

Validation:

- `subject` is optional and must reference an active subject slug if present.

Side effects:

- None.

Error cases:

- `422` if filter is invalid.

Acceptance criteria:

- Returns only active paths by default.
- Supports the single MVP seed subject and can later list additional active subjects.

### GET /api/learning-paths/{id}

Status: **B4 hardening**.

Purpose: Show a learning path with ordered LearningNodes.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "id": 10,
    "title": "Algebra Foundations",
    "nodes": [
      {
        "id": 101,
        "title": "Lineare Gleichungen loesen",
        "position": 1
      }
    ],
    "intro_explanations": {
      "new": {
        "title": "Lineare Gleichungen sind kleine Rückwärtsrätsel.",
        "body": "Du suchst die Zahl, die eine Aussage wahr macht: 2x + 3 = 11. Erst entfernst du, was x stört, dann bleibt x allein.",
        "usefulness": "Du kannst unbekannte Werte ausrechnen, statt sie zu raten."
      },
      "rough": {
        "title": "Du kennst die Idee wahrscheinlich schon: beide Seiten bleiben im Gleichgewicht.",
        "body": "Der Trick ist, jeden Schritt auf beiden Seiten gleich zu machen. So wird aus einer vollen Gleichung nach und nach x = ...",
        "usefulness": "Du rechnest sauberer und erkennst schneller, welcher Schritt als Nächstes passt."
      },
      "confident": {
        "title": "Hier geht es nicht um die Regel, sondern um Tempo und Sicherheit.",
        "body": "Du prüfst, ob du Gleichungen ohne Umweg umformen kannst und wo Klammern oder Textaufgaben dich kurz ausbremsen.",
        "usefulness": "Du machst die Grundlagen automatisch genug für schwierigere Aufgaben."
      }
    }
  }
}
```

Validation:

- `{id}` must reference an active LearningPath.

Side effects:

- None.

Error cases:

- `404` if path does not exist or is inactive.

Acceptance criteria:

- Nodes are returned in path order.
- Intro explanations include `new`, `rough`, and `confident`.
- Intro explanations are backend-owned learning content and must stay short, concrete, and pressure-free.

### POST /api/learning-paths/{id}/start

Status: **V1 required**

Purpose: Enroll the user in a learning path and optionally store the learner's self-assessment for this path.

Request example:

```json
{}
```

Self-assessment request example:

```json
{
  "self_assessment": "rough"
}
```

Response example:

```json
{
  "data": {
    "id": 300,
    "learning_path_id": 10,
    "status": "active",
    "self_assessment": "rough",
    "started_at": "2026-06-13T09:00:00Z"
  }
}
```

Validation:

- Path must be active.
- `self_assessment` is optional and may be `new`, `rough`, or `confident`.

Side effects:

- Creates Enrollment if missing.
- Persists `self_assessment` per user and LearningPath when provided.
- May initialize MasteryStates lazily or immediately.

Error cases:

- `401` if unauthenticated.
- `404` if path not found.
- `422` if `self_assessment` is invalid.

Acceptance criteria:

- Starting the same path twice returns the existing active Enrollment.
- Starting without `self_assessment` remains valid for compatibility.
- Providing a new `self_assessment` updates the user's Enrollment for that path only.
- Enrollment affects `GET /api/today`.
- Self-assessment does not create scores, placement logic, levels, rewards, or pressure state.

## 4. Learning Nodes

Status: **B4 hardening** for the full section. V1 can use LearningNodes internally without exposing the node browsing API.

### GET /api/nodes

Status: **B4 hardening**

Purpose: List active LearningNodes.

Request example:

```json
{
  "query": {
    "subject": "math",
    "type": "skill"
  }
}
```

Response example:

```json
{
  "data": [
    {
      "id": 201,
      "slug": "solve-linear-equations",
      "type": "skill",
      "title": "Lineare Gleichungen loesen"
    }
  ]
}
```

Validation:

- Optional `type` must be a supported node type.

Side effects:

- None.

Error cases:

- `422` for invalid filters.

Acceptance criteria:

- Supports nodes belonging to multiple subjects.

### GET /api/nodes/{id}

Status: **B4 hardening**

Purpose: Show one LearningNode.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "id": 201,
    "title": "Lineare Gleichungen loesen",
    "description": "Loese einfache Gleichungen mit einer Variablen.",
    "subjects": ["Math"]
  }
}
```

Validation:

- Node must exist and be active.

Side effects:

- None.

Error cases:

- `404` if not found.

Acceptance criteria:

- Includes subject membership.

### GET /api/nodes/{id}/tasks

Status: **B4 hardening**

Purpose: List available tasks linked to a LearningNode.

Request example:

```json
{}
```

Response example:

```json
{
  "data": [
    {
      "id": 800,
      "type": "numeric",
      "difficulty": 1,
      "estimated_minutes": 5
    }
  ]
}
```

Validation:

- Node must exist and be active.

Side effects:

- None.

Error cases:

- `404` if node not found.

Acceptance criteria:

- Does not expose answer schemas.
- Includes official tasks only in the MVP.

### GET /api/nodes/{id}/prerequisites

Status: **B4 hardening**

Purpose: List prerequisite LearningNodes.

Request example:

```json
{}
```

Response example:

```json
{
  "data": [
    {
      "id": 101,
      "title": "Evaluate arithmetic expressions",
      "relation": "prerequisite"
    }
  ]
}
```

Validation:

- Node must exist.

Side effects:

- None.

Error cases:

- `404` if node not found.

Acceptance criteria:

- Reads NodeRelations of type `prerequisite`.

## 5. Tasks

### GET /api/tasks/{id}

Status: **V1 required**

Purpose: Show a task prompt and active TaskVersion.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "id": 800,
    "task_version_id": 1200,
    "type": "numeric",
    "prompt": "Loese 2x + 3 = 11.",
    "input": {
      "kind": "number"
    },
    "estimated_minutes": 5
  }
}
```

Validation:

- Task must be active and accessible.

Side effects:

- None.

Error cases:

- `403` for inaccessible private task.
- `404` if not found.

Acceptance criteria:

- Does not expose correct answer.
- Includes TaskVersion ID.
- Does not expose answer schemas, accepted values, grading tolerances, canonical solutions, or hidden correctness metadata.

### POST /api/task-attempts/start

Status: **V1 required**

Purpose: Start a task attempt. Starting a task creates a TaskAttempt.

Request example:

```json
{
  "task_id": 800,
  "task_version_id": 1200
}
```

Response example:

```json
{
  "data": {
    "id": 4000,
    "task_id": 800,
    "task_version_id": 1200,
    "status": "started"
  }
}
```

Validation:

- Task must be accessible.
- TaskVersion must belong to Task and be active.

Side effects:

- Creates TaskAttempt with started state.

Error cases:

- `401` if unauthenticated.
- `403` if task is private to another user.
- `422` if TaskVersion does not match Task.

Acceptance criteria:

- Starting a task creates a TaskAttempt.
- The attempt freezes the TaskVersion being attempted.
- The attempt belongs only to the authenticated user.

### POST /api/task-attempts/{id}/submit

Status: **V1 required**

Purpose: Submit an auto-graded task attempt, or mark the attempt as unsure or skipped.

Request example:

```json
{
  "answer": {
    "value": 4
  }
}
```

Unsure or skipped request example:

```json
{
  "result": "unsure"
}
```

Response example:

```json
{
  "data": {
    "id": 4000,
    "result": "correct",
    "feedback_key": "numeric_correct",
    "feedback_text": "Richtig. Ziehe zuerst 3 ab und teile dann durch 2.",
    "mastery": {
      "learning_node_id": 201,
      "status": "practiced"
    },
    "review_created": false,
    "review_scheduled": false,
    "next_state": "practiced"
  }
}
```

Validation:

- Attempt must belong to user.
- Attempt must not already be submitted.
- Request must include exactly one of `answer` or `result`.
- `answer` must match task type when present.
- `result` may be `unsure` or `skipped` when present.

Side effects:

- Updates TaskAttempt with answer and result.
- Updates MasteryStates.
- Creates Review if result is `incorrect`, `unsure`, or `skipped`.

Error cases:

- `403` if attempt belongs to another user.
- `409` if already submitted.
- `422` for malformed answer.

Acceptance criteria:

- Submit is atomic: TaskAttempt result, Review creation/update, and MasteryState update commit together or roll back together.
- If atomicity is not available in the first implementation, feature tests must prove no partial state remains after grading, scheduler, or mastery failures.
- Duplicate submit behavior is consistent: either always `409` after completion or idempotently returns the stored result. The chosen behavior must be documented in implementation tests.
- Concurrent weak submissions for the same user, LearningNode, and Task must not create duplicate active Reviews.
- Incorrect submissions create Reviews.
- Unsure and skipped submissions create Reviews.
- Attempt result is deterministic for the stored TaskVersion, even if the Task has a newer active TaskVersion.
- Attempt and Review side effects remain isolated by authenticated user.
- Response includes stable `feedback_key` plus short German `feedback_text`.
- Response includes `next_state` and `review_scheduled` so the frontend can close the loop without pressure copy.
- Incorrect, unsure, and skipped responses should communicate that Nuvio will bring the work back later.
- Response does not expose `completion_state`, `challenge_options`, `mastery_score`, XP, badges, achievements, streaks, reward levels, or pressure state.

### POST /api/task-attempts/{id}/self-check

Status: **Later**

Purpose: Submit a self-check result for a non-auto-graded task.

Request example:

```json
{
  "result": "unsure",
  "reflection": "I knew the formula but used the wrong units."
}
```

Response example:

```json
{
  "data": {
    "id": 4001,
    "result": "unsure",
    "review_created": true
  }
}
```

Validation:

- `result` must be `correct`, `incorrect`, `unsure`, or `skipped`.
- Attempt must belong to user and be self-check compatible.

Side effects:

- Updates TaskAttempt.
- Updates MasteryStates conservatively.
- Creates Review for `incorrect`, `unsure`, or `skipped`.

Error cases:

- `409` if attempt already completed.
- `422` for invalid result.

Acceptance criteria:

- Unsure self-check creates a Review.
- Feedback should avoid shame-based language.

## 6. Reviews

### GET /api/reviews/due

Status: **B4 hardening**

Purpose: List due reviews without overwhelming the user.

Request example:

```json
{
  "query": {
    "limit": 3
  }
}
```

Response example:

```json
{
  "data": [
    {
      "id": 501,
      "learning_node_id": 301,
      "task_id": 800,
      "due_at": "2026-06-13T08:00:00Z",
      "estimated_minutes": 5
    }
  ],
  "meta": {
    "returned": 1,
    "cap": 3
  }
}
```

Validation:

- `limit` optional, max 10. UI-facing calls should use max 3.

Side effects:

- None.

Error cases:

- `401` if unauthenticated.

Acceptance criteria:

- Capped response prevents overwhelming backlog.
- Response does not expose hidden backlog counts.
- Due reviews can feed Today selection.

### GET /api/reviews/{id}

Status: **V1 required**

Purpose: Return enough review detail to render and answer one review action.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "id": 501,
    "learning_node": {
      "id": 301,
      "title": "Lineare Gleichungen loesen"
    },
    "task": {
      "id": 800,
      "task_version_id": 1200,
      "type": "numeric",
      "prompt": "Loese 2x + 3 = 11.",
      "input": {
        "kind": "number"
      },
      "estimated_minutes": 5
    },
    "due_at": "2026-06-13T08:00:00Z"
  }
}
```

Validation:

- Review must belong to the authenticated user.

Side effects:

- None.

Error cases:

- `403` if review belongs to another user.
- `404` if review not found.

Acceptance criteria:

- Response can render the review prompt.
- Response does not expose the correct answer.

### POST /api/reviews/{id}/answer

Status: **V1 required**

Purpose: Answer a review and update its schedule.

Request example:

```json
{
  "answer": {
    "value": 4
  }
}
```

Unsure or skipped request example:

```json
{
  "result": "unsure"
}
```

Response example:

```json
{
  "data": {
    "review_id": 501,
    "attempt_id": 4100,
    "result": "correct",
    "feedback_key": "numeric_correct",
    "feedback_text": "Richtig. Ziehe zuerst 3 ab und teile dann durch 2.",
    "status": "completed",
    "next_due_at": null,
    "interval_days": null,
    "mastery": {
      "learning_node_id": 201,
      "previous_status": "review_due",
      "status": "retained"
    },
    "review_scheduled": false,
    "next_state": "retained",
    "mastery_transition": {
      "previous_status": "review_due",
      "status": "retained"
    }
  }
}
```

Validation:

- Review must belong to user.
- Request must include exactly one of `answer` or `result`.
- Answer must match linked task type when present.
- `result` may be `unsure` or `skipped` when present.

Side effects:

- Creates or updates TaskAttempt.
- Completes the Review when correct, or reschedules it near-term when incorrect, unsure, or skipped.
- Updates MasteryStates.

Error cases:

- `403` if review belongs to another user.
- `404` if review not found.
- `409` if review is already `completed` or `suspended`.
- `422` for malformed answer.

Acceptance criteria:

- Review answer is atomic: TaskAttempt result, Review status/schedule, and MasteryState update commit together or roll back together.
- Completed or suspended Reviews cannot be answered through the normal answer path.
- Parallel weak review answers must keep one active scheduled Review and must not create duplicate active Reviews.
- Review attempts and side effects remain isolated by authenticated user.
- Review grading uses the TaskVersion stored for the created review attempt or linked immutable task version, not a later TaskVersion.
- Correct review completes the Review and can move MasteryState to `retained`.
- Incorrect, unsure, or skipped review keeps review near-term.
- Response includes `next_state`, `review_scheduled`, and `mastery_transition`.
- V1 response does not expose `mastery_moment` or `mastery_score`.
- Review feedback frames the work as ready to review or reactivated, not as failure or punishment.

### POST /api/reviews/{id}/snooze

Status: **B4 hardening**

Purpose: Delay a review without marking it learned.

Request example:

```json
{
  "minutes": 60
}
```

Response example:

```json
{
  "data": {
    "id": 501,
    "due_at": "2026-06-13T10:00:00Z",
    "status": "scheduled"
  }
}
```

Validation:

- `minutes` required, minimum 15, maximum 1440.
- Review must belong to user.

Side effects:

- Updates Review due date.

Error cases:

- `403` if review belongs to another user.
- `422` for invalid snooze duration.

Acceptance criteria:

- Snoozing does not improve mastery.
- Snoozed reviews can return later without creating backlog pressure.

## 7. Progress

### GET /api/progress/summary

Status: **V1 required**

Purpose: Return a compact progress summary.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "active_paths": 1,
    "practiced_nodes": 8,
    "review_due_nodes": 2,
    "retained_nodes": 3,
    "reviews_due": 3
  }
}
```

Validation:

- Requires authenticated user.

Side effects:

- None.

Error cases:

- `401` if unauthenticated.

Acceptance criteria:

- Progress is based on MasteryStates, attempts, and reviews, not only time.
- Progress fields describe competence status and scheduled review work only.
- `reviews_due` is neutral orientation for currently due review work, not a debt, remainder, missed-work count, or obligation counter.
- V1 Progress Summary stays compact and must not create a backlog-debt representation.
- The response must not include XP totals, badges, achievements, streaks, streak freezes, leaderboard rank, reward levels, catch-up debt, missed-day penalty, or lost-progress state.
- If B4 adds `next_useful_action`, it must remain a single orientation field and must not expose a missed-work list.

Optional metadata:

- V1 and B4 do not expose `returning_after_break`, missed-day counts, catch-up goals, loss state, or streak repair.

### GET /api/progress/paths/{id}

Status: **B4 hardening**

Purpose: Return progress for one LearningPath.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "learning_path_id": 10,
    "title": "Algebra Foundations",
    "node_counts": {
      "unknown": 0,
      "practiced": 1,
      "review_due": 1,
      "retained": 1
    },
    "nodes": [
      {
        "id": 101,
        "title": "Lineare Gleichungen loesen",
        "status": "retained"
      }
    ]
  }
}
```

Validation:

- User must be enrolled or path must be public.

Side effects:

- None.

Error cases:

- `404` if path not found.

Acceptance criteria:

- Path progress is derived from ordered LearningNodes and MasteryStates.
- Path progress must not expose `percent_complete`, `mastery_score`, reward levels, or collection completion as a substitute for competence status.

### GET /api/progress/paths/{id}/skill-map

Status: **Later**

Purpose: Return a compact Skill-Map view for one LearningPath.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "learning_path_id": 10,
    "title": "Algebra Foundations",
    "nodes": [
      {
        "learning_node_id": 201,
        "title": "Lineare Gleichungen",
        "status": "review_due",
        "is_next_available": true,
        "is_review_ready": true,
        "position": 1,
        "prerequisite_ids": []
      }
    ]
  }
}
```

Validation:

- User must be enrolled or path must be public.

Side effects:

- None.

Error cases:

- `404` if path not found.

Acceptance criteria:

- Skill-Map is derived from LearningPath order, LearningNodes, NodeRelations, Reviews, and MasteryStates.
- Node status is one of `unknown`, `practiced`, `review_due`, or `retained`.
- Response does not include stars, level numbers, badge slots, achievements, XP, streaks, ranks, reward locks, catch-up requirements, or backlog lists.
- Today remains the main action surface; this endpoint supports compact secondary navigation only.

## 8. Later API Boundaries

Status: **Later** or **Out of scope**

The narrow MVP must not implement endpoints for:

- Custom task creation.
- Self-check tasks.
- Challenge options.
- Skill-Map.
- Simulations or simulation runs.
- XP events, achievements, badges, streaks, streak freezes, leaderboards, reward levels, or gamified catch-up flows.
- Lootbox-like reward reveals, countdown pressure, artificial scarcity, attendance rewards, or comeback streaks.
- AI teacher interactions.
- Equation transformation tasks are the first planned interactive Algebra format. Their future read API may expose only the initial equation, allowed operation types, and goal; canonical solutions and accepted steps must remain server-side for deterministic grading.

When those phases begin, their API contracts should be added in separate sections and tested separately from the MVP learning loop.

Motivation boundary:

- Future APIs may add richer learning evidence, simulations, or project work.
- Future APIs must still avoid reward currency, collection, rank, series-maintenance, and loss-repair mechanics unless this product principle is explicitly changed.
- Snooze and review scheduling are recovery and learning-structure features, not reward protection features.
- B4 playful fields are limited to Completion States and Mastery Moments after real learning evidence. Deterministic next learning choices and richer interaction formats are Later.
