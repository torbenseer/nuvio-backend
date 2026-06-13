# Nuvio Simulation And Gamification Specification

Simulations and gamification should support learning without distracting from it in later phases. They are not part of the narrow MVP.

Do not implement simulation tables, simulation endpoints, XP events, achievements, badges, streaks, or gamification services until the core MVP learning loop is complete and the feature is explicitly requested.

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
- `session_id` nullable
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
- Evaluators may create XpEvents or update MasteryStates only through explicit rules.
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
5. API optionally creates XpEvent.
6. API optionally updates MasteryState by explicit rule.

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

## Part B: Gamification

### 1. Purpose Of Gamification

Gamification should make progress visible and motivate useful practice.

It should:

- Reinforce learning consistency.
- Celebrate retained knowledge.
- Make path progress feel clear.
- Encourage reviews without shame.
- Stay secondary to learning.

### 2. ADHD-Friendly Gamification Principles

- Show simple progress.
- Avoid overwhelming reward systems.
- Avoid shame-based streak penalties.
- Do not punish missed days.
- Prefer short feedback loops.
- Reward meaningful practice, review, and transfer.
- Keep badges adult-friendly.

### 3. Event-Based Gamification

Gamification should be event-based.

Events can be derived from:

- TaskAttempt completed.
- Review completed.
- LearningNode retained.
- LearningPath milestone reached.
- Simulation goal completed.

Rules:

- Store XP as XpEvent records.
- Store durable badges as Achievement records.
- Do not store gamification state as hidden logic inside TaskAttempt.

### 4. XP Events

Purpose:

- Record small point events for completed learning actions.

Suggested fields:

- `id`
- `user_id`
- `source_type`
- `source_id`
- `points`
- `reason`
- `created_at`

Rules:

- XP does not determine mastery.
- XP should not drive Today selection.
- XP should be explainable from source events.

### 5. Achievements

Purpose:

- Record durable badges or milestones.

Suggested fields:

- `id`
- `user_id`
- `key`
- `title`
- `description`
- `awarded_at`
- `metadata` JSON

Rules:

- Achievements should be based on learning evidence.
- Avoid achievements that shame missed days.
- Avoid achievements that reward empty time spent.

### 6. Milestones

Milestones are meaningful progress markers.

Examples:

- First review completed.
- First LearningNode retained.
- First path section completed.
- First cross-disciplinary transfer task completed.
- First simulation goal completed.

Milestones may create Achievement and XpEvent records.

### 7. Progress Bars

Progress bars should show:

- LearningPath completion.
- LearningNode mastery state.
- Review queue summary.

Rules:

- Progress bars should use MasteryStates, not only time.
- Do not show a huge review backlog as a threatening bar.
- Keep summaries compact.

### 8. Level Map

The level map is the future Duolingo-like path visualization.

Backend support:

- LearningPath ordered LearningNodes.
- MasteryState per node.
- Review status per node.
- Optional achievements per path section.

Rules:

- The map is a view over SkillGraph.
- Do not create a separate progression model for the map.

### 9. What Not To Gamify

Do not reward:

- Time spent without practice.
- Clicking through content.
- Avoiding difficult tasks.
- Maintaining perfect streaks at the cost of learning.
- Repeating trivial tasks for inflated points.

Do not penalize:

- Missed days.
- Wrong answers.
- Unsure answers.
- Returning after a break.

### 10. No Shame-Based Streak Penalties

Streaks, if implemented, must be gentle.

Rules:

- No punitive streak loss messaging.
- No "you failed" language.
- No locking progress after missed days.
- Missed days can simply mean no activity event was recorded.

Preferred framing:

- "Welcome back. Here are three useful actions."
- "Let's review this before moving on."

### 11. Example Events

XP examples:

```json
[
  {
    "source_type": "task_attempt",
    "source_id": 4000,
    "points": 10,
    "reason": "correct_task"
  },
  {
    "source_type": "review",
    "source_id": 501,
    "points": 8,
    "reason": "completed_review"
  },
  {
    "source_type": "simulation_run",
    "source_id": 600,
    "points": 12,
    "reason": "simulation_goal_completed"
  }
]
```

### 12. Example Achievement Definitions

```json
[
  {
    "key": "first-review",
    "title": "First Review",
    "description": "Completed your first review."
  },
  {
    "key": "node-retained",
    "title": "Retained Skill",
    "description": "Retained a LearningNode through review."
  },
  {
    "key": "cross-disciplinary-transfer",
    "title": "Transfer Ready",
    "description": "Applied knowledge across two subjects."
  },
  {
    "key": "simulation-goal",
    "title": "Experiment Complete",
    "description": "Completed a simulation goal."
  }
]
```

### 13. Acceptance Criteria

- Gamification is event-based.
- XP events can be traced to source learning actions.
- Achievements reward meaningful learning evidence.
- Progress bars use MasteryStates and path progress.
- The level map is a view over SkillGraph.
- Gamification does not determine mastery.
- Gamification does not distract from reviews or tasks.
- No shame-based streak penalties exist.
- Missed days do not punish the learner.
- None of these gamification mechanics are implemented in the narrow MVP.
