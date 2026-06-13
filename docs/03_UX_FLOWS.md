# Nuvio UX Flows

This document describes user flows that the Laravel API must support. It is not a frontend specification. It exists so backend endpoints, data models, and service behavior match the intended Nuvio experience.

Nuvio should feel Duolingo-like: simple, motivating, focused on small daily actions, clear progress, reviews, and level-like learning paths.

## UX Principles

- Show at most three recommended actions per day.
- Keep the Today screen focused.
- Avoid overwhelming dashboards.
- Use short tasks and clear feedback.
- Treat failure as review input, not shame.
- Missed reviews should not create an overwhelming backlog.
- Progress should reflect practice, retention, and transfer.
- Energy mode should shape the size of recommended work.

## Energy Modes

Energy mode lets the learner choose the intensity of today's work.

- `red`: max 15 minutes. One short task or a small review set.
- `yellow`: normal 25 to 60 minute learning. Standard task or review session.
- `green`: deeper work. In the MVP this only affects ordering and duration; simulations and project work are later phases.

The backend should accept energy mode as input to Today selection and session creation.

## 1. Open Today Screen

### User Goal

The user wants to know what to do next without planning.

### Trigger

The user opens the app or refreshes the Today screen.

### Backend Action

- Fetch active enrollments.
- Fetch due reviews.
- Fetch MasteryStates.
- Run the today action selector.
- Return zero to three recommended actions.

### Data Created Or Updated

- None required.
- Optionally update a lightweight `last_seen_today_at` field later.

### Expected API Endpoints

- `GET /api/today`
- `GET /api/progress/summary`

### Success State

The user sees at most three actions with clear reasons.

### Edge Cases

- No enrollment: return an action to start a path.
- No due reviews and no path progress: return first path task.
- Many overdue reviews: return a capped, non-overwhelming set.
- Completed all available content: return progress summary and no forced action.

### Acceptance Criteria

- Today returns at most three actions.
- Due reviews are prioritized.
- Each action has type, title, reason, estimated minutes, and target.
- The response does not expose a large backlog.

## 2. Select Energy Mode

### User Goal

The user wants work that matches available time and energy.

### Trigger

The user selects red, yellow, or green mode before starting a session.

### Backend Action

- Store the selected mode on the Session or use it as a query parameter.
- Filter or rank Today actions by estimated duration and action type.

### Data Created Or Updated

- `sessions.energy_mode` when a session is created.
- Optional user preference later.

### Expected API Endpoints

- `GET /api/today?energy_mode=red`
- `POST /api/sessions`

### Success State

The recommended work fits the selected mode.

### Edge Cases

- Red mode with only long actions available: return the shortest review or task.
- Green mode with no longer tasks available: return normal path work.
- Invalid mode: return validation error.

### Acceptance Criteria

- Red mode returns actions with estimated duration of 15 minutes or less when possible.
- Yellow mode returns normal learning work between 25 and 60 minutes when available.
- Green mode can include deeper tasks when available.
- Energy mode must not increase Today actions beyond three.

## 3. Start A Task

### User Goal

The user wants to begin a small learning task.

### Trigger

The user selects a Today action or continues a learning path.

### Backend Action

- Authorize task access.
- Load Task and active TaskVersion.
- Create TaskAttempt with status `started`.
- Return prompt and answer UI metadata without revealing the answer.

### Data Created Or Updated

- `task_attempts`

### Expected API Endpoints

- `POST /api/task-attempts/start`

### Success State

The frontend can render the task prompt and input type.

### Edge Cases

- Task has no active TaskVersion.
- Task belongs to inaccessible private content.
- Task is no longer active.

### Acceptance Criteria

- Task response does not expose correct answer.
- Task response includes TaskVersion ID or version metadata.
- TaskAttempt stores the TaskVersion shown.
- User can only access official tasks or owned private tasks.

## 4. Submit A Task

### User Goal

The user wants feedback on an answer.

### Trigger

The user submits an answer.

### Backend Action

- Validate answer shape.
- Grade against the attempted TaskVersion.
- Complete the started TaskAttempt.
- Update MasteryState.
- Create or update Review if the attempt is `incorrect`, `unsure`, or `skipped`.
- Return feedback.

### Data Created Or Updated

- `task_attempts`
- `mastery_states`
- `reviews` when needed

### Expected API Endpoints

- `POST /api/task-attempts/{taskAttempt}/submit`

### Success State

The user receives clear feedback and progress updates.

### Edge Cases

- Malformed answer.
- TaskVersion mismatch.
- Duplicate submit from retrying a request.
- User submits after task was archived.

### Acceptance Criteria

- Attempt is stored immutably.
- Correct answer updates mastery positively.
- Incorrect answer creates or updates review.
- Skipped answer creates or updates review.
- Feedback is direct and non-shaming.

## 5. Complete A Self-Check Task

### User Goal

The user wants to honestly record whether they understood a task that may not be automatically graded.

### Trigger

The user completes a self-check task and marks outcome.

### Backend Action

- Validate self-check outcome.
- Complete the started TaskAttempt with the result selected by user.
- Update MasteryState conservatively.
- Create Review for unsure or failed self-check.

### Data Created Or Updated

- `task_attempts`
- `mastery_states`
- `reviews` when needed

### Expected API Endpoints

- `POST /api/task-attempts/{taskAttempt}/self-check`

### Success State

The user's self-assessment affects progress and reviews.

### Edge Cases

- User marks success repeatedly without evidence.
- Self-check task has no linked LearningNode.
- Outcome missing.

### Acceptance Criteria

- Self-check attempts are distinguishable from auto-graded attempts.
- Unsure, skipped, or failed self-check creates review work.
- Success can improve mastery but should be capped lower than auto-graded proof if needed.

## 6. Fail Or Mark A Task As Unsure

### User Goal

The user wants to continue without shame when they do not know an answer.

### Trigger

The user submits an incorrect answer, selects "unsure", or skips.

### Backend Action

- Store attempt as `incorrect` or `unsure`.
- Update MasteryState.
- Create or update Review.
- Return supportive feedback and next action.

### Data Created Or Updated

- `task_attempts`
- `mastery_states`
- `reviews`

### Expected API Endpoints

- `POST /api/task-attempts/{taskAttempt}/submit`
- `POST /api/task-attempts/{taskAttempt}/self-check`

### Success State

The system turns the weak attempt into future review work.

### Edge Cases

- Multiple incorrect attempts on the same task.
- User is unsure on many tasks in a row.
- Review already exists.

### Acceptance Criteria

- No shame-based failure message.
- Incorrect, unsure, and skipped all create or update review.
- Duplicate reviews are avoided for the same user, node, and task.

## 7. Generate A Review

### User Goal

The user does not directly trigger this. The system creates review work from learning evidence.

### Trigger

An incorrect, unsure, skipped, or failed self-check attempt is completed.

### Backend Action

- Find existing Review for user, LearningNode, and optional Task.
- Create or update Review due date and interval.
- Keep backlog manageable.

### Data Created Or Updated

- `reviews`
- Possibly `mastery_states`

### Expected API Endpoints

- Usually internal service call from task-attempt submit or self-check endpoints.
- `GET /api/reviews/due`

### Success State

A future review is scheduled and can appear in Today.

### Edge Cases

- Many failures in one session.
- Existing overdue review.
- LearningNode has no alternative tasks.

### Acceptance Criteria

- Incorrect, unsure, and skipped attempts create review work.
- Review generation is deterministic.
- Repeated failures update an existing review where appropriate.
- Today selector caps visible review actions instead of exposing a large backlog.

## 8. Complete A Review

### User Goal

The user wants to strengthen a weak or due skill.

### Trigger

The user starts a review action from Today.

### Backend Action

- Load due Review.
- Serve linked Task or a task from the same LearningNode.
- Record attempt.
- Update Review interval.
- Update MasteryState.

### Data Created Or Updated

- `task_attempts`
- `reviews`
- `mastery_states`

### Expected API Endpoints

- `GET /api/reviews/due`
- `POST /api/reviews/{review}/answer`

### Success State

Successful review moves the due date later. Failed or unsure review keeps it near-term.

### Edge Cases

- Review task was archived.
- User completes review after due date.
- Review has no linked task.

### Acceptance Criteria

- Correct review extends interval.
- Incorrect or unsure review shortens or resets interval.
- Review completion affects progress.

## 9. Start A Learning Path

### User Goal

The user wants to start a clear sequence in a subject.

### Trigger

The user selects a path from a subject list or Today recommendation.

### Backend Action

- Validate LearningPath exists and is active.
- Create Enrollment.
- Initialize MasteryStates lazily or immediately.

### Data Created Or Updated

- `enrollments`
- Optional initial `mastery_states`

### Expected API Endpoints

- `GET /api/subjects`
- `GET /api/subjects/{subject}/learning-paths`
- `POST /api/enrollments`

### Success State

The user is enrolled and Today can recommend the first useful action.

### Edge Cases

- Already enrolled.
- Path has no active nodes.
- Path is archived.

### Acceptance Criteria

- Enrollment is idempotent.
- Enrolled path influences Today actions.
- Path order is preserved.

## 10. Continue A Learning Path

### User Goal

The user wants to resume progress without deciding where to restart.

### Trigger

The user opens Today or selects a continue action.

### Backend Action

- Determine next LearningNode from path order, prerequisites, and MasteryStates.
- Select an appropriate Task or Review.
- Return action or task metadata.

### Data Created Or Updated

- None until session or attempt starts.

### Expected API Endpoints

- `GET /api/today`
- `GET /api/learning-paths/{learningPath}/progress`

### Success State

The user gets a sensible next task or review.

### Edge Cases

- Multiple active paths.
- Prerequisite node is weak.
- All current path nodes are learned but reviews remain due.

### Acceptance Criteria

- Due reviews take precedence over new path work.
- Continue action respects path order.
- Today remains capped at three actions.

## 11. Create A Custom Task Later Phase Only

This flow is not part of the MVP. Do not implement custom task endpoints, models beyond nullable ownership fields, or tests until custom tasks are explicitly requested.

### User Goal

The user wants to add private practice material.

### Trigger

The user creates a custom task for a LearningNode.

### Backend Action

- Validate task type, prompt, and answer schema.
- Create Task owned by the user.
- Create initial TaskVersion.
- Keep task private.

### Data Created Or Updated

- `tasks`
- `task_versions`

### Expected API Endpoints

- Later only.

### Success State

The custom task can be attempted by its owner.

### Edge Cases

- Missing answer schema.
- Invalid linked LearningNode.
- Another user tries to access the task.

### Acceptance Criteria

- Custom task is private by default.
- Custom task has at least one TaskVersion.
- Custom task attempts can generate reviews.

## 12. Start A Simulation Later Phase Only

This flow is not part of the MVP. Do not implement simulation endpoints, SimulationDefinition tables, SimulationRun tables, evaluators, or Today simulation actions until simulations are explicitly requested.

### User Goal

The user wants to learn by experimenting interactively.

### Trigger

The user selects a simulation action in a later phase.

### Backend Action

- Load simulation definition.
- Validate access.
- Create SimulationRun.
- Return launch metadata.

### Data Created Or Updated

- `simulation_runs`

### Expected API Endpoints

- Later only.

### Success State

The frontend can launch a simulation linked to a LearningNode.

### Edge Cases

- Simulation unavailable for selected energy mode.
- Definition is inactive.
- User has no access to linked path or node.

### Acceptance Criteria

- Simulation is linked to SkillGraph through LearningNodes.
- Starting a simulation does not bypass task and review logic.
- Green mode must not include simulations in the MVP.

## 13. Complete A Simulation Goal Later Phase Only

This flow is not part of the MVP.

### User Goal

The user wants simulation work to count when it demonstrates learning.

### Trigger

The frontend reports a completed simulation goal.

### Backend Action

- Validate SimulationRun.
- Store completion payload.
- Optionally update MasteryState through explicit rules.
- Optionally create XP event.

### Data Created Or Updated

- `simulation_runs`
- Optional `mastery_states`
- Optional `xp_events`

### Expected API Endpoints

- Later only.

### Success State

The completed simulation goal is recorded and may affect progress.

### Edge Cases

- Completion payload is invalid.
- Simulation run already completed.
- Simulation goal is exploratory and should not update mastery.

### Acceptance Criteria

- Simulation completion is recorded separately from TaskAttempts.
- Mastery changes only through explicit backend rules.
- Simulation completion can appear in progress history later.

## 14. Earn Progress Or Badge Later Phase Only

### User Goal

The user wants recognition for meaningful learning progress. Badge, XP, and achievement mechanics are not part of the MVP.

### Trigger

The user completes tasks, reviews, nodes, paths, or simulation goals.

### Backend Action

- Update MasteryState.
- Recalculate progress summary.
- Later: optionally create XP event or badge event.

### Data Created Or Updated

- `mastery_states`
- Later only: `xp_events`, `badges`, or `badge_awards`

### Expected API Endpoints

- `GET /api/progress/summary`
- `GET /api/learning-paths/{learningPath}/progress`
- Later only.

### Success State

The user sees progress based on practice, retention, and transfer.

### Edge Cases

- User repeats easy tasks.
- User misses reviews.
- User completes a path but has weak prerequisite nodes.

### Acceptance Criteria

- Progress is not based only on time spent.
- Badge or XP systems do not punish missed days.
- Repeated practice should not inflate mastery without useful evidence.

## 15. Ask AI Teacher For A Hint, Later Phase Only

### User Goal

The user wants a small hint without turning Nuvio into a general chatbot.

### Trigger

The user asks for help on a current task or review.

### Backend Action

- Validate user access to task.
- Validate allowed AI intent.
- Assemble constrained context from TaskVersion, LearningNode, path, and recent attempts.
- Request hint from AI service later.
- Store AI interaction later if implemented.

### Data Created Or Updated

- None in MVP.
- Later: `ai_teacher_interactions`.

### Expected API Endpoints

- Later only: `POST /api/ai/teacher/messages`

### Success State

The user receives a bounded hint related to the current task.

### Edge Cases

- User asks unrelated question.
- User asks for full answer.
- Prompt asks for unsafe chemistry or electrical instructions.
- Task has no official explanation.

### Acceptance Criteria

- AI teacher is not part of MVP.
- AI is constrained to current learning context.
- AI does not grade core tasks.
- AI does not replace reviews or Today selection.
