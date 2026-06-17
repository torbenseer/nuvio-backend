# Nuvio Simulation And Motivation Specification

Simulations and gamification should support learning without distracting from it in later phases. They are not part of the narrow MVP.

Do not implement simulation tables, simulation endpoints, XP events, achievements, badges, streaks, or gamification services. Nuvio may add simulations later, but the motivation model remains competence feedback, clear structure, and recovery without pressure.

## Part A: Simulations

### 1. Purpose Of Simulations

Simulations help learners see a concept behave.

They should:

- Make abstract relationships concrete.
- Support exploration after or before a task.
- Link to specific LearningNodes.
- Have a clear goal.
- Produce structured results when completed.

A simulation should not just be a toy. It should help the learner practice or transfer a concept.

### 2. SimulationDefinition Model

Purpose:

- Stores reusable simulation metadata and schemas.

Suggested fields:

- `id`
- `slug`
- `title`
- `description`
- `frontend_component_key`
- `parameter_schema` JSON
- `output_schema` JSON
- `goal_schema` JSON
- `backend_evaluator` nullable string
- `estimated_minutes`
- `is_active`

Relationships:

- Belongs to many LearningNodes.
- Belongs to many Tasks optionally.
- Has many SimulationRuns.

### 3. SimulationRun Model

Purpose:

- Stores one user's interaction with a simulation.

Suggested fields:

- `id`
- `user_id`
- `simulation_definition_id`
- `learning_session_id` nullable
- `task_id` nullable
- `status`
- `parameters` JSON
- `outputs` JSON nullable
- `goal_result` JSON nullable
- `started_at`
- `completed_at`

Statuses:

- `started`
- `completed`
- `abandoned`

### 4. Parameter Schema

The parameter schema describes inputs the frontend simulation accepts.

Example:

```json
{
  "type": "object",
  "required": ["vin", "r1", "r2"],
  "properties": {
    "vin": { "type": "number", "minimum": 0, "maximum": 24 },
    "r1": { "type": "number", "minimum": 100 },
    "r2": { "type": "number", "minimum": 100 }
  }
}
```

Rules:

- Parameters must be validated before storing a SimulationRun.
- Later phases can validate with Laravel rules or JSON schema.

### 5. Output Schema

The output schema describes expected simulation results.

Example:

```json
{
  "type": "object",
  "properties": {
    "vout": { "type": "number" },
    "ratio": { "type": "number" }
  }
}
```

Rules:

- Outputs should be structured.
- Outputs can be used by a backend evaluator.
- Outputs should not directly update mastery unless the evaluator approves a goal.

### 6. Frontend Component Key

`frontend_component_key` tells a future frontend which simulation component to render.

Examples:

- `voltage-divider`
- `linear-function`
- `force-acceleration`
- `ph-scale`

Backend rule:

- Treat this as metadata. The Laravel API should not depend on a frontend implementation.

### 7. Backend Evaluator

`backend_evaluator` identifies optional backend logic that checks whether a simulation goal was completed.

Examples:

- `voltage_divider_target_vout`
- `linear_function_match_slope`

Rules:

- Evaluators must be deterministic.
- Evaluators should return a structured goal result.
- Evaluators may update MasteryStates only through explicit rules.
- The MVP omits evaluators.

### 8. Linking Simulations To LearningNodes And Tasks

Simulations should link to concrete learning work.

Relationships:

- SimulationDefinition belongs to many LearningNodes.
- SimulationDefinition may belong to many Tasks.
- SimulationRun may reference the Task that launched it.

Rules:

- Every active SimulationDefinition should link to at least one LearningNode.
- A simulation recommended in Today should have a concrete goal.
- Simulations should reinforce a task, review, project step, or path node.

### 9. Starting A Simulation

Flow:

1. User selects simulation action.
2. API validates SimulationDefinition.
3. API validates initial parameters if provided.
4. API creates SimulationRun.
5. API returns launch metadata.

Endpoint:

- `POST /api/simulation-runs/start`

Side effects:

- Creates SimulationRun.

### 10. Completing A Simulation Goal

Flow:

1. Frontend submits parameters and outputs.
2. API validates output schema.
3. Backend evaluator checks goal if configured.
4. API completes SimulationRun.
5. API optionally updates MasteryState by explicit rule.

Endpoint:

- `POST /api/simulation-runs/{id}/submit`

Rules:

- Completion does not automatically mean mastery.
- Failed goals should give neutral feedback and may suggest a task or review.

### 11. Later Simulation Candidates

Good first candidates:

- Voltage divider simulation.
- Linear function simulation.

Other later candidates:

- Force and acceleration.
- Conservation of energy.
- pH scale.
- Balancing chemical equations.

Later-phase recommendation:

- Start with at most one basic SimulationDefinition and SimulationRun flow after the core task-review loop is stable.

### 12. Future Simulation Plugin System

Later, simulations can become plugins.

Potential plugin contract:

- Simulation slug.
- Frontend component key.
- Parameter schema.
- Output schema.
- Goal schema.
- Backend evaluator class.
- Linked LearningNodes.

Rules:

- Plugins should not bypass TaskAttempts, Reviews, or MasteryStates.
- Plugins should produce structured events.
- Plugins should be testable without the frontend.

### 13. Example: Voltage Divider Simulation

SimulationDefinition:

```json
{
  "slug": "voltage-divider",
  "title": "Voltage Divider Explorer",
  "frontend_component_key": "voltage-divider",
  "estimated_minutes": 15,
  "parameter_schema": {
    "required": ["vin", "r1", "r2"],
    "properties": {
      "vin": { "type": "number", "minimum": 0, "maximum": 24 },
      "r1": { "type": "number", "minimum": 100 },
      "r2": { "type": "number", "minimum": 100 }
    }
  },
  "output_schema": {
    "properties": {
      "vout": { "type": "number" }
    }
  },
  "goal_schema": {
    "target_vout": 3,
    "tolerance": 0.05
  },
  "backend_evaluator": "voltage_divider_target_vout"
}
```

Goal:

- Adjust `vin`, `r1`, and `r2` so `vout` is close to 3 V.

Linked LearningNodes:

- `voltage-divider-ratio`
- `linear-ratio-reasoning`

### 14. Example: Linear Function Simulation

SimulationDefinition:

```json
{
  "slug": "linear-function",
  "title": "Linear Function Explorer",
  "frontend_component_key": "linear-function",
  "estimated_minutes": 10,
  "parameter_schema": {
    "required": ["m", "b"],
    "properties": {
      "m": { "type": "number", "minimum": -10, "maximum": 10 },
      "b": { "type": "number", "minimum": -20, "maximum": 20 }
    }
  },
  "output_schema": {
    "properties": {
      "points": { "type": "array" }
    }
  },
  "goal_schema": {
    "target_slope": 2,
    "target_intercept": -1
  },
  "backend_evaluator": "linear_function_match_slope"
}
```

Goal:

- Adjust slope and intercept to match a target line.

Linked LearningNodes:

- `linear-functions-slope-intercept`

### 15. Acceptance Criteria

- A SimulationDefinition can link to LearningNodes.
- A SimulationRun stores one user interaction.
- Starting a simulation creates a SimulationRun.
- Completing a simulation stores structured outputs.
- A simulation has a concrete goal.
- A simulation can be linked to a Task.
- Simulation progress updates mastery only through explicit backend rules.
- The MVP ships without simulation tables, endpoints, or a simulation engine.

## Part B: Motivation Without Pressure

Nuvio does not use gamification as a reward system. The product should make learning feel possible and worth continuing, but it must not turn learning into compliance, loss avoidance, or daily obligation.

### 1. Didactic Position

Adults need competence feedback, clear structure, and recovery after interruption.

Nuvio therefore avoids XP, badges, achievements, streaks, streak freezes, comeback streaks, leaderboards, ranks, and reward levels. These mechanics are not fixed by softer copy. A friendly streak still asks the learner to protect a series. A badge still turns learning evidence into collection status. XP still shifts attention from "what can I do now?" to "how many points did I earn?".

For adults with ADHD, the product should reduce planning load and friction. It should not add a second task of maintaining the product's reward system. Breaks, variable energy, uncertainty, and restarts are expected parts of adult learning.

### 2. Distinctions

Motivating competence feedback:

- Describes current evidence: correct, incorrect, unsure, skipped, retained, review due.
- Explains the next useful action.
- Updates MasteryState and Review records deterministically.
- Helps the learner trust that work is captured and will return when useful.

Controlling gamification:

- Adds an external reward or loss layer: XP, points, coins, badges, streaks, ranks, leagues, collections, or reward levels.
- Creates pressure to maintain, repair, protect, catch up, or compare.
- Can be controlling even with supportive copy because the mechanic itself creates obligation.

Helpful structure:

- Caps Today at three actions.
- Prioritizes Review before new learning.
- Keeps tasks small and completable.
- Shows compact progress status.
- Lets learners choose Unsure and Skip without moral framing. B4 adds Snooze with the same boundary.

Playful learning interaction:

- Lets learners manipulate the learning object itself, such as transforming an equation or balancing terms.
- Shows competence state changes after real evidence.
- Uses calm motion to orient the learner to state, location, or next step.
- Does not create an external reward economy.

Pressure mechanics:

- Present missed work as debt.
- Present review due counts as failure.
- Turn daily activity into a requirement.
- Use loss, rank, scarcity, or collection completion as motivation.

### 3. Allowed Progress Patterns

Allowed backend fields and UI states:

- MasteryState statuses: `unknown`, `practiced`, `review_due`, `retained`.
- Compact counts: active paths, practiced nodes, review-due nodes, retained nodes, due reviews.
- Path progress derived from ordered LearningNodes and MasteryStates.
- Neutral completion states when there is no useful work now.
- Due review summary that stays capped or compact.
- Copy such as "Nuvio will bring this back later."
- Review scheduling that is deterministic and visible through normal Today behavior.
- B4 snooze scheduling that is deterministic and visible through normal Today behavior.
- Later compact Skill-Map node states for Algebra Foundations.
- B4 Completion States after real learning actions.
- B4 Mastery Moments after Review or MasteryState evidence.
- Later optional challenge choices that select more learning work without implying obligation.

Rules:

- Progress describes competence or scheduled learning work.
- Progress must not imply that a break caused damage.
- Review due means "ready to review", not "behind".
- Snooze changes timing only; it is not progress and not a streak freeze.
- "Next step available" means content is ready; it must not be phrased as "level unlocked".

### 4. Playful Without Pressure

Nuvio may feel playful through the learning surface itself.

Allowed playful patterns:

- Skill-Map instead of a dashboard.
- Quiet progress states: `unknown`, `practiced`, `review_due`, `retained`.
- Visual state changes after an actual TaskAttempt, Review answer, or MasteryState transition.
- "Next step available" instead of "level unlocked".
- Short Completion States.
- Mastery Moments after real retrieval or retention evidence.
- Interactive Algebra tasks that make the concept manipulable.
- Optional challenge choices after completion.
- Gentle animation for orientation and state change only.
- Recovery after pauses as a calm re-entry.

Forbidden playful patterns:

- XP, badges, achievements, streaks, streak freezes, comeback streaks, leaderboards, ranks, daily pressure, loss logic, lootbox or slot-machine feeling, artificial scarcity, countdown pressure, or rewards for attendance.
- Confetti after every task.
- Skill-Map stars, level numbers, badge slots, collectible slots, or locked reward nodes.

### 5. Skill-Map Rules

The Skill-Map is a secondary or compact view over SkillGraph. Today remains the main surface.

Allowed:

- Show Algebra Foundations as LearningNode markers connected by prerequisite/path order.
- Show node competence state: `unknown`, `practiced`, `review_due`, `retained`.
- Show a node as ready to review.
- Show a node as retained.
- Show next available learning work.
- Let the learner inspect a small node summary and return to Today.

Forbidden:

- Stars.
- Level numbers.
- Badge slots.
- Achievement slots.
- Reward locks.
- Leaderboard or rank overlays.
- Large dashboard analytics.
- Full backlog list.

API fields allowed for B4/Later Skill-Map:

```json
{
  "learning_node_id": 101,
  "title": "Lineare Gleichungen",
  "status": "review_due",
  "is_next_available": true,
  "is_review_ready": true,
  "position": 1,
  "prerequisite_ids": []
}
```

API fields forbidden:

- `stars`
- `level`
- `xp`
- `badge_slot`
- `achievement`
- `streak`
- `rank`
- `locked_reward`
- `catch_up_required`

### 6. Completion States

Completion States are short closures after real learning actions. They are not rewards.

Allowed examples:

- "Schritt abgeschlossen"
- "Lineare Gleichungen: geuebt"
- "Review geplant"
- "Nach der Pause wieder abgerufen"
- "Behalten"
- "Naechster Schritt verfuegbar"

Forbidden examples:

- "+50 XP"
- "Badge verdient"
- "Serie gerettet"
- "Du bist wieder im Rennen"
- "Level freigeschaltet"
- "Catch up abgeschlossen"

Rules:

- Completion States may be returned by task or review mutation responses as neutral `completion_state` metadata.
- Completion States must be derived from attempt result, Review creation, Review completion, or MasteryState transition.
- Completion States must not be created for app open, attendance, or passive time spent.

### 7. Mastery Moments

Mastery Moments make competence visible when the learner has produced meaningful evidence.

Allowed triggers:

- A Review is answered correctly.
- A LearningNode changes from `review_due` to `retained`.
- A formerly unsure task is later solved correctly.
- A return-after-break action is completed with a short Review-first flow.

Allowed UI behavior:

- Calm state change on the node or feedback panel.
- Short copy naming the competence state.
- Optional route back to Today or a challenge choice.

Allowed copy:

- "Behalten"
- "Das sass wieder."
- "Lineare Gleichungen: wieder aktiv"
- "Review abgeschlossen"
- "Nach der Pause wieder abgerufen"

Forbidden UI behavior:

- Confetti after every task.
- Reward inventory.
- Countdown or urgency.
- Streak repair.
- Lootbox-like reveal.

### 8. Interactive Algebra

V1 must not grow into a large interaction engine.

V1 required:

- Numeric task remains the first supported algebra interaction.
- Submit, Unsure, and Skip remain first-class paths.
- Feedback may include a short Completion State.

B4 hardening or Later:

- Error marking: learner marks the step or term where the error occurs.
- Similar task choice after completion when deterministic task selection supports it.

Later:

- Equation-step transformation: learner applies one operation to both sides.
- Term balancing: learner manipulates terms while preserving equality.
- Graph manipulation: learner adjusts slope/intercept or points and submits structured state.
- Slightly harder challenge choice when deterministic task selection supports difficulty.

Rules:

- Interactive tasks must still use deterministic grading.
- Read APIs must not leak answers.
- Review scheduling remains deterministic.
- Interactive tasks create TaskAttempts and Reviews through the same loop.
- No interactive task may reward mere attendance or clicking.

### 9. Recovery After Pauses

Return after a pause should be calm and short.

Rules:

- Do not count missed days.
- Do not show a backlog list.
- Do not create catch-up goals.
- Today still shows at most three actions.
- Prioritize a short Review-first action when useful.
- Use copy such as "Nuvio will bring the rest back gradually."
- Due work remains scheduled learning work, not debt.

Allowed API behavior:

- Today may return up to three actions.
- B4 may add Completion States and Mastery Moments after real learning evidence.

Forbidden API behavior:

- `missed_days`
- `catch_up_required`
- `overdue_debt`
- `streak_repair`
- `lost_progress`
- `hidden_due_reviews`
- `returning_after_break`

### 10. Forbidden Patterns

Do not implement:

- XP, points, coins, reward currency, or `xp_events`.
- Badges, achievements, trophies, collections, or `achievements`.
- Streaks, streak freezes, streak repairs, or missed-day recovery purchases.
- Leaderboards, ranks, leagues, competitive tiers, or social comparison.
- Level ladders used as a reward path.
- Daily pressure copy.
- "Catch up", "behind", "failed", "lost progress", "save your streak", or equivalent wording.
- Confetti, repeated celebration animations, or reward loops after every task.
- Backlog numbers displayed as debt, warning, or urgency.
- Lootbox-like reveals, slot-machine timing, artificial scarcity, countdown pressure, or rewards for attendance.

### 11. Copy Rules

Good copy:

- "Not sure yet"
- "Skip for now"
- "Snooze review" in B4 only
- "This is ready to review"
- "Nuvio will bring this back later"
- "Review this before the next new task"
- "No useful work right now"
- "Welcome back. Here are three useful actions."
- "Schritt abgeschlossen"
- "Lineare Gleichungen: geuebt"
- "Review geplant"
- "Nach der Pause wieder abgerufen"
- "Behalten"
- "Naechster Schritt verfuegbar"
- "Nuvio will bring the rest back gradually."

Bad copy:

- "You failed"
- "Wrong again"
- "You are behind"
- "Catch up now"
- "You lost progress"
- "Save your streak"
- "Keep your perfect run"
- "Only 6 reviews left"
- "Complete today's goal to avoid falling behind"
- "+50 XP"
- "Badge earned"
- "Level unlocked"
- "Back in the race"

### 12. API And Data Rules

- Do not add `xp_events`, `achievements`, `streaks`, `streak_freezes`, `leaderboard_entries`, or equivalent tables for V1 or B4.
- Do not add response fields named `xp`, `points`, `coins`, `badges`, `achievements`, `streak`, `streak_freeze`, `comeback_streak`, `rank`, `league`, `level_reward`, `catch_up_count`, `missed_days_penalty`, `overdue_debt`, `countdown`, `scarcity`, or `lost_progress`.
- If a future content map uses levels as content structure, the API must name them as LearningNodes, path positions, sections, or prerequisites, not reward levels.
- Today must not include `hidden_due_reviews` in V1/B4 UI-facing responses.
- Progress APIs must derive state from MasteryStates, TaskAttempts, Reviews, and LearningPath order.
- Allowed V1 fields: `status` and mastery status transitions exposed without scores.
- Allowed B4 playful fields: `completion_state` and `mastery_moment`.
- Later playful fields: `is_next_available`, `is_review_ready`, and `challenge_options`.
- `challenge_options` are Later and require a separate deterministic API contract.

### 13. Acceptance Criteria

V1 acceptance:

- Today returns at most three actions.
- Review is prioritized before new learning.
- Progress summary uses competence and review status only.
- Feedback handles correct, incorrect, unsure, skipped, and review-created states without pressure copy.
- Unsure and Skip are first-class actions.
- No V1 endpoint exposes XP, badges, achievements, streaks, ranks, reward levels, or loss-state fields.
- Numeric task remains the V1 algebra interaction.
- Completion States are not exposed in V1.

B4 acceptance:

- Completion States are short, neutral, and based on real learning actions.
- `GET /api/reviews/due` and `POST /api/reviews/{id}/snooze` preserve the same pressure boundary.
- Snooze does not improve mastery, protect a streak, or create an achievement.
- Path progress remains derived from LearningNodes and MasteryStates.
- Hidden backlog data is capped, summarized, or omitted, and never required for pressure UI.
- Tests or fixtures cover forbidden fields and copy where practical.
- Compact Skill-Map fields, if added, show competence state only.
- Error marking or challenge choice metadata, if added, uses deterministic selection and does not bypass Review.

Later acceptance:

- Interactive Algebra tasks use TaskAttempts, deterministic grading, deterministic review scheduling, and no answer leaks.
- Skill-Map remains secondary to Today.
- Animations orient state changes only and do not become reward loops.

### 14. Small Implementable Tickets

1. Add B4 neutral `completion_state` values to task/review response examples and tests.
2. Add B4 copy fixtures for Completion States and Mastery Moments. Return-after-break copy must not imply missed days or debt.
3. Add forbidden-field assertions for XP, badges, streaks, ranks, reward levels, catch-up debt, countdowns, and lost progress.
4. Specify compact Skill-Map B4 response shape from LearningPath, LearningNodes, and MasteryStates.
5. Define Later interactive Algebra task schemas for equation transformation, error marking, term balancing, and graph manipulation.
