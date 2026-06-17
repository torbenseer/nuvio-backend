# Nuvio UX Flows

This document describes user flows that the Laravel API must support. It is not a frontend specification. It exists so backend endpoints, data models, and service behavior match the intended Nuvio experience.

Nuvio should feel simple, adult-oriented, and product-specific: focused on small learning actions, clear competence evidence, reviews, and learning paths. It should not sound like a generic dashboard or motivational SaaS assistant.

## UX Principles

- Show at most three recommended actions per day.
- Keep the Today screen focused.
- Avoid overwhelming dashboards.
- Use short tasks and clear feedback.
- Prefer concrete task or node names over repeated generic helper copy.
- Treat failure as review input, not shame.
- Missed reviews should not create an overwhelming backlog.
- Progress should reflect practice, retention, and transfer.
- Energy mode is B4 and must not shape V1 Today selection.
- Do not use XP, badges, achievements, streaks, streak freezes, leaderboards, reward levels, catch-up flows, lost-progress states, or backlog pressure.
- Treat Unsure, Skip, and Snooze as normal recovery actions.

## Fun Without Pressure Flows

These flows define the desired feeling for the first visible interaction. They constrain copy, API shape, and frontend rendering so Nuvio feels useful without becoming a daily obligation.

| Flow | Trigger | UI State | Allowed Copy | Forbidden Copy | API Requirement | Pressure Risk | Concrete Improvement |
| --- | --- | --- | --- | --- | --- | --- | --- |
| A. Opening Today after a normal day | App opens | Today with max three actions | "Heute"; "Such dir einen Einstieg aus. Einer reicht." | "Tagesziel", "noch X offen" | `GET /api/today` remains capped | Dashboard feeling | Name the first action concretely, for example by node or task |
| B. Opening Today after a long break | Return after 7+ days | Normal Today, no accounting view | "Schön, dass du wieder da bist. Wir machen hier weiter." | "Du bist zurückgefallen" | No `missed_days`, `catch_up_required`, or debt fields | Guilt on return | Use return copy without a catch-up flow |
| C. Incorrect answer | Wrong answer submitted | Feedback plus scheduled Review | "Noch nicht ganz. Das ist ein guter Punkt für die nächste Wiederholung." | "Falsch", "Versagt" | `review_scheduled: true` and neutral `next_state` | Error as punishment | Name the fachlicher nächster Schritt |
| D. Unsure | Learner selects Unsure | Equal feedback path | "Unsicher markiert. Gute Entscheidung, das später noch einmal zu sehen." | "Aufgeben?" | `result=unsure` schedules Review | Hidden failure exit | Keep button visible next to Submit |
| E. Skip | Learner skips | Friendly stop path | "Übersprungen. Du kannst die Stelle später wieder aufnehmen." | "Abbrechen und verlieren" | `result=skipped` schedules or defers cleanly | Feeling of breaking the system | Confirm later resumption warmly |
| F. Successful Review | Review answered correctly | B4 small Mastery Moment | "Behalten. Du konntest es nach Abstand wieder abrufen." | "Level-Up", "Badge" | Optional `mastery_transition`; B4 `mastery_moment` only on evidence | Reward loop | Show only after real retention evidence |
| G. No useful work right now | Selector has no useful action | Compact empty/progress state | "Für den Moment ist alles in Ordnung. Du kannst später weitermachen." | "Komm morgen wieder sonst..." | Empty or `progress_only` state | Artificial obligation | Do not create fake work |

## Energy Modes

Energy mode lets the learner choose the intensity of today's work.

- `red`: max 15 minutes. One short task or a small review set.
- `yellow`: normal 25 to 60 minute learning. Standard task or review session.
- `green`: deeper work. In the MVP this only affects ordering and duration; simulations and project work are later phases.

B4 may store the selected mode through `POST /api/today/mode`. V1 `GET /api/today` accepts no `mode` input and does not persist Energy Mode. Energy Mode should not create a LearningSession just to support mode selection.

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

The user sees at most three actions with clear titles and targets.

### Edge Cases

- No enrollment: return an action to start a path.
- No due reviews and no path progress: return first path task.
- Many overdue reviews: return a capped, non-overwhelming set.
- Completed all available content: return progress summary and no forced action.

### Acceptance Criteria

- Today returns at most three actions.
- Due reviews are prioritized.
- Each V1 action has type, title, estimated minutes, priority, and target.
- V1 actions do not expose `reason`, `mode`, or `hidden_due_reviews`.
- The response does not expose a large backlog.

## 2. Select Energy Mode

### User Goal

The user wants work that matches available time and energy.

### Trigger

The user selects red, yellow, or green mode before starting a session.

### Backend Action

- B4 stores the selected mode through `POST /api/today/mode`.
- Filter or rank Today actions by estimated duration and action type.

### Data Created Or Updated

- Optional stored user mode for later Today requests.
- No LearningSession record is required in the narrow MVP.

### Expected API Endpoints

- V1: `GET /api/today`
- B4: `POST /api/today/mode`

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
- Accept `result: unsure` or `result: skipped` instead of an answer for auto-graded attempts.
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
- Request includes both `answer` and `result`.
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

Status: **Later**

### User Goal

The user wants to honestly record whether they understood a task that may not be automatically graded.

### Trigger

The user completes a self-check task and marks outcome.

### Backend Action

- Validate self-check outcome.
- Complete the started TaskAttempt with the result selected by user.
- Update MasteryState conservatively.
- Create Review for unsure or incorrect self-check.

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
- Unsure, skipped, or incorrect self-check creates review work.
- Success can improve mastery but should be capped lower than auto-graded proof if needed.

## 6. Fail Or Mark A Task As Unsure

### User Goal

The user wants to continue without shame when they do not know an answer.

### Trigger

The user submits an incorrect answer, selects "unsure", or skips.

### Backend Action

- Store attempt as `incorrect`, `unsure`, or `skipped`.
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

- No shame-based result message.
- Incorrect, unsure, and skipped all create or update review.
- Duplicate reviews are avoided for the same user, node, and task.

## 7. Generate A Review

### User Goal

The user does not directly trigger this. The system creates review work from learning evidence.

### Trigger

An incorrect, unsure, skipped, or self-check attempt needing review is completed.

### Backend Action

- Find existing Review for user, LearningNode, and optional Task.
- Create or update Review due date and interval.
- Keep backlog manageable.

### Data Created Or Updated

- `reviews`
- Possibly `mastery_states`

### Expected API Endpoints

- Usually internal service call from task-attempt submit or self-check endpoints.
- `GET /api/reviews/due` is B4. V1 surfaces one due Review through Today.

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

- `GET /api/reviews/{review}`
- `POST /api/reviews/{review}/answer`
- `GET /api/reviews/due` is B4

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

- `GET /api/learning-paths`
- `GET /api/learning-paths/{learningPath}`
- `POST /api/learning-paths/{learningPath}/start`

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
- `GET /api/progress/paths/{learningPath}`

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

### Data Created Or Updated

- `simulation_runs`
- Optional `mastery_states`

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

## 14. Show Progress Without Pressure

### User Goal

The user wants to understand meaningful learning progress without maintaining a reward system.

### Trigger

The user completes tasks, reviews, nodes, paths, or simulation goals.

### Backend Action

- Update MasteryState.
- Recalculate progress summary.

### Data Created Or Updated

- `mastery_states`

### Expected API Endpoints

- `GET /api/progress/summary`
- `GET /api/progress/paths/{learningPath}`

### Success State

The user sees progress based on practice, retention, and transfer.

The user does not see XP, badges, achievements, streaks, reward levels, ranks, catch-up debt, or lost-progress state.

### Edge Cases

- User repeats easy tasks.
- User misses reviews.
- User completes a path but has weak prerequisite nodes.

### Acceptance Criteria

- Progress comes from MasteryStates, Reviews, TaskAttempts, and LearningPath order.
- Review due state is presented as normal learning work.
- Missed days do not create loss, repair flows, or pressure copy.

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
