# Nuvio MVP API Specification

Nuvio is a Laravel API backend for a Duolingo-like adult learning platform. The MVP API supports the loop:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

## Conventions

Base path: `/api`

Authentication: Laravel Sanctum for authenticated learner endpoints.

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

## 1. Today

### GET /api/today

Purpose: Return up to three recommended actions for the authenticated user.

Request example:

```json
{
  "query": {
    "mode": "red"
  }
}
```

Response example:

```json
{
  "data": [
    {
      "type": "review",
      "title": "Review Ohm's law",
      "reason": "You marked this unsure recently.",
      "estimated_minutes": 8,
      "priority": 1,
      "target": {
        "type": "review",
        "id": 501
      }
    }
  ],
  "meta": {
    "mode": "red",
    "limit": 3,
    "hidden_due_reviews": 6
  }
}
```

Validation:

- `mode` is optional and must be `red`, `yellow`, or `green`.

Side effects:

- None. Today actions are computed on request.

Error cases:

- `401` if unauthenticated.
- `422` if mode is invalid.

Acceptance criteria:

- Returns at most three actions.
- Selects actions in this deterministic order:
  1. Due reviews ordered by `due_at`.
  2. Review-due LearningNodes.
  3. The next Task in the active LearningPath.
- Red mode returns actions of max 15 minutes when possible.
- Does not expose an overwhelming review backlog.

### POST /api/today/mode

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

- Updates user preference or current session mode.

Error cases:

- `401` if unauthenticated.
- `422` if mode is invalid.

Acceptance criteria:

- Stored mode affects later Today selection.
- Red mode keeps recommended work short.

## 2. Learning Paths

### GET /api/learning-paths

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
      "estimated_minutes": 240,
      "node_count": 12
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
    "title": "Circuit Fundamentals",
    "nodes": [
      {
        "id": 101,
        "title": "Identify voltage, current, and resistance",
        "position": 1
      }
    ]
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

### POST /api/learning-paths/{id}/start

Purpose: Enroll the user in a learning path.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "id": 300,
    "learning_path_id": 10,
    "status": "active",
    "started_at": "2026-06-13T09:00:00Z"
  }
}
```

Validation:

- Path must be active.

Side effects:

- Creates Enrollment if missing.
- May initialize MasteryStates lazily or immediately.

Error cases:

- `401` if unauthenticated.
- `404` if path not found.

Acceptance criteria:

- Starting the same path twice returns the existing active Enrollment.
- Enrollment affects `GET /api/today`.

### GET /api/enrollments/{id}/progress

Purpose: Return progress for one enrollment.

Request example:

```json
{}
```

Response example:

```json
{
  "data": {
    "enrollment_id": 300,
    "learning_path_id": 10,
    "status": "active",
    "learned_nodes": 3,
    "active_nodes": 2,
    "weak_nodes": 1,
    "percent_complete": 25
  }
}
```

Validation:

- Enrollment must belong to the authenticated user.

Side effects:

- None.

Error cases:

- `401` if unauthenticated.
- `403` if enrollment belongs to another user.
- `404` if not found.

Acceptance criteria:

- Progress is derived from MasteryStates, not time spent.

## 3. Learning Nodes

### GET /api/nodes

Purpose: List active LearningNodes.

Request example:

```json
{
  "query": {
    "subject": "physics",
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
      "slug": "calculate-average-speed",
      "type": "skill",
      "title": "Calculate average speed"
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
    "title": "Calculate average speed",
    "description": "Use distance divided by time to find average speed.",
    "subjects": ["Physics"]
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

## 4. Tasks

### GET /api/tasks/{id}

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
    "prompt": "A cart moves 12 meters in 3 seconds. What is its average speed?",
    "input": {
      "kind": "number",
      "unit": "m/s"
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

### POST /api/task-attempts/start

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

### POST /api/task-attempts/{id}/submit

Purpose: Submit an auto-graded task attempt.

Request example:

```json
{
  "answer": {
    "value": 4
  }
}
```

Response example:

```json
{
  "data": {
    "id": 4000,
    "result": "correct",
    "feedback": "Correct. Average speed is distance divided by time.",
    "mastery": {
      "learning_node_id": 201,
      "status": "active",
      "mastery_score": 35
    },
    "review_created": false
  }
}
```

Validation:

- Attempt must belong to user.
- Attempt must not already be submitted.
- Answer must match task type.

Side effects:

- Updates TaskAttempt with answer and result.
- Updates MasteryStates.
- Creates Review if result is `incorrect`, `unsure`, or `skipped`.

Error cases:

- `403` if attempt belongs to another user.
- `409` if already submitted.
- `422` for malformed answer.

Acceptance criteria:

- Incorrect submissions create Reviews.
- Attempt result is deterministic for the TaskVersion.

### POST /api/task-attempts/{id}/self-check

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

## 5. Reviews

### GET /api/reviews/due

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
    "hidden_due_reviews": 5,
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
- Due reviews can feed Today selection.

### POST /api/reviews/{id}/answer

Purpose: Answer a review and update its schedule.

Request example:

```json
{
  "answer": {
    "value": 2
  }
}
```

Response example:

```json
{
  "data": {
    "review_id": 501,
    "attempt_id": 4100,
    "result": "correct",
    "status": "completed",
    "next_due_at": null,
    "interval_days": null
  }
}
```

Validation:

- Review must belong to user.
- Answer must match linked task type.

Side effects:

- Creates or updates TaskAttempt.
- Completes the Review when correct, or reschedules it near-term when incorrect, unsure, or skipped.
- Updates MasteryStates.

Error cases:

- `403` if review belongs to another user.
- `404` if review not found.
- `422` for malformed answer.

Acceptance criteria:

- Correct review completes the Review and can move MasteryState to `retained`.
- Incorrect, unsure, or skipped review keeps review near-term.

### POST /api/reviews/{id}/snooze

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

## 6. Progress

### GET /api/progress/summary

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

### GET /api/progress/paths/{id}

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
    "percent_complete": 33,
    "nodes": [
      {
        "id": 101,
        "title": "Solve one-step equations",
        "status": "retained",
        "mastery_score": 82
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

## 7. Later API Boundaries

The narrow MVP must not implement endpoints for:

- Custom task creation.
- Simulations or simulation runs.
- XP events, achievements, or badges.
- AI teacher interactions.

When those phases begin, their API contracts should be added in separate sections and tested separately from the MVP learning loop.
