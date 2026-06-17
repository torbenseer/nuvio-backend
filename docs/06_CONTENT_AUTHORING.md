# Nuvio Content Authoring Specification

Nuvio content must be extensible, versionable, importable, and testable. The MVP starts with one small subject path, but the same model must later support math, physics, electrical engineering, chemistry, biology, computer science, career paths, project-based learning, and simulations.

## 1. Content Philosophy

Nuvio content should be small, clear, and practice-oriented.

Principles:

- Teach through short tasks, reviews, and visible progress.
- Link every task to SkillGraph through at least one LearningNode.
- Allow tasks to belong to multiple LearningNodes.
- Keep content suitable for adult learners.
- Prefer durable concepts over trivia.
- Make review behavior explicit.
- Treat simulations and projects as graph-linked content, not separate systems.
- Validate content before publishing it.

## 2. Content Types

MVP content types:

- Subject.
- LearningNode.
- NodeRelation.
- LearningPath.
- Task.
- TaskVersion.

Should-have or later content types:

- SimulationDefinition.
- Project step.
- Career path.
- AI authoring draft.

## 3. Subject Format

Subjects represent broad domains.

Required fields:

- `slug`
- `name`
- `description`

Optional fields:

- `sortOrder`
- `active`

Example:

```yaml
slug: chemistry
name: Chemistry
description: Foundations of matter, reactions, pH, and energy.
sortOrder: 4
active: true
```

## 4. LearningNode Format

LearningNodes are reusable units in SkillGraph.

Required fields:

- `slug`
- `type`
- `title`
- `description`
- `subjects`

Optional fields:

- `level`
- `estimatedMinutes`
- `tags`
- `active`

Allowed MVP types:

- `skill`

Future types:

- `concept`
- `project_step`
- `career_competency`
- `simulation_goal`

Example:

```yaml
slug: linear-functions-slope-intercept
type: skill
title: Understand slope-intercept form
description: Interpret y = mx + b as a linear relationship.
subjects:
  - math
level: 1
estimatedMinutes: 30
tags:
  - algebra
  - functions
active: true
```

## 5. NodeRelation Format

NodeRelations connect LearningNodes.

Required fields:

- `from`
- `to`
- `type`

Optional fields:

- `strength`
- `description`

Allowed MVP relation types:

- `prerequisite`

Example:

```yaml
from: one-step-equations
to: two-step-equations
type: prerequisite
strength: 1.0
description: One-step equations should usually be practiced before two-step equations.
```

## 6. LearningPath Format

LearningPaths are ordered views over LearningNodes.

Required fields:

- `slug`
- `title`
- `type`
- `nodes`

Optional fields:

- `subject`
- `description`
- `estimatedMinutes`
- `active`

Allowed MVP type:

- `subject_path`

Future types:

- `career_path`
- `project_path`

Example:

```yaml
slug: circuit-fundamentals
title: Circuit Fundamentals
type: subject_path
subject: electrical-engineering
description: Learn voltage, current, resistance, and simple circuit analysis.
estimatedMinutes: 240
nodes:
  - slug: voltage-current-resistance
    required: true
  - slug: ohms-law
    required: true
  - slug: voltage-divider-ratio
    required: true
active: true
```

## 7. Task Format

Tasks are practice items linked to one or more LearningNodes.

Required fields:

- `slug`
- `type`
- `learningNodes`
- `estimatedMinutes`
- `difficulty`
- `answerType`
- `reviewPolicy`
- `versions`

Optional fields:

- `tags`
- `source`
- `active`

Allowed V1 task types:

- `numeric`

Allowed B4 task types:

- `multiple_choice`

Later task types:

- `self_check`
- `short_text`
- `equation_transformation`

Rules:

- Every task must belong to at least one LearningNode.
- A task can belong to multiple LearningNodes.
- Task content lives in TaskVersion entries.
- Imported tasks should be inactive until validation passes.

## 8. TaskVersion Rules

TaskVersion freezes task content for attempts.

Required fields:

- `version`
- `prompt`
- `answerSchema`
- `explanation`

Optional fields:

- `body`
- `hint`
- `choices`
- `publishedAt`

Rules:

- Never mutate a published TaskVersion if attempts may reference it.
- If prompt, answer, choices, or explanation changes meaningfully, create a new version.
- TaskAttempts must store the TaskVersion attempted.
- Only one TaskVersion should be active for normal task delivery.

## 9. Review Policy Per Task

Each task should declare how attempts generate reviews.

Required fields:

- `onIncorrect`
- `onUnsure`

Optional fields:

- `onSkipped`
- `reviewAfterDays`
- `reviewNodeStrategy`

Example:

```yaml
reviewPolicy:
  onIncorrect: create_review
  onUnsure: create_review
  onSkipped: create_review
  reviewAfterDays: 1
  reviewNodeStrategy: primary_nodes
```

Rules:

- Incorrect attempts should create or update Reviews.
- Unsure attempts should create or update Reviews.
- Review policies must not create unbounded duplicate reviews.

## 10. Tags And Difficulty Levels

Tags support filtering, authoring review, and future recommendations.

Example tags:

- `algebra`
- `circuits`
- `stoichiometry`
- `pH`
- `energy`
- `cross-disciplinary`

Difficulty levels:

- `1`: introductory.
- `2`: basic application.
- `3`: multi-step.
- `4`: transfer or unfamiliar context.
- `5`: project-level or advanced.

Rules:

- Every task must have `difficulty`.
- Every task should have at least one tag.
- Difficulty should reflect cognitive demand, not only calculation length.

## 11. Self-Check Tasks

Self-check tasks ask the learner to judge their own success.

Use for:

- Reflection.
- Worked examples.
- Project steps.
- Tasks that cannot be reliably auto-graded in the MVP.

Example schema:

```yaml
answerType: self_check
versions:
  - version: 1
    prompt: Explain in your own words why increasing resistance lowers current when voltage is fixed.
    answerSchema:
      type: self_check
      allowedResults:
        - correct
        - unsure
        - incorrect
    explanation: Current equals voltage divided by resistance, so larger resistance means smaller current.
```

Rules:

- `unsure` and `incorrect` self-check results create Reviews.
- Self-check success may update mastery more conservatively than auto-graded tasks.

## 12. Numeric Tasks

Numeric tasks are auto-graded by value and tolerance.

Required answer schema fields:

- `type: numeric`
- `correctValue`
- `tolerance`

Optional fields:

- `unit`
- `acceptedUnits`
- `significantFigures`

Example:

```yaml
answerType: numeric
versions:
  - version: 1
    prompt: A cart accelerates from 0 m/s to 12 m/s in 4 s. What is its acceleration?
    answerSchema:
      type: numeric
      correctValue: 3
      tolerance: 0.01
      unit: m/s^2
    explanation: Acceleration is change in velocity divided by time, so 12 / 4 = 3 m/s^2.
```

## 13. Multiple Choice Tasks

Multiple choice tasks are auto-graded by selected choice.

Rules:

- At least two choices.
- Exactly one correct choice in the MVP.
- Distractors should reflect plausible misconceptions.
- Choices must not be trick answers.

Example:

```yaml
answerType: multiple_choice
versions:
  - version: 1
    prompt: In y = 3x + 2, what does 3 represent?
    choices:
      - key: a
        text: The slope
        correct: true
      - key: b
        text: The y-intercept
        correct: false
      - key: c
        text: The x-intercept
        correct: false
    answerSchema:
      type: multiple_choice
      correctChoice: a
    explanation: In y = mx + b, m is the slope.
```

## 14. Simulation Tasks

Simulation tasks connect a task or goal to a SimulationDefinition.

MVP status:

- Later phase only.
- Do not implement simulation tasks, SimulationDefinition records, or SimulationRun flows in the MVP.

Required fields later:

- `simulationSlug`
- `learningNodes`
- `goal`
- `successCriteria`

Rules:

- Simulation completion should create SimulationRun, not TaskAttempt, unless it includes a task.
- Mastery should update only through explicit rules.

## 15. Project Steps

Project steps are future LearningNodes or tasks that support project-based learning.

Example:

```yaml
slug: build-voltage-divider-calculator-step-1
type: project_step
title: Define voltage divider inputs
subjects:
  - electrical-engineering
  - math
```

Rules:

- Project steps should reuse existing LearningNodes where possible.
- Career and project paths should be LearningPaths over graph nodes, not separate models.

## 16. Import And Validation Rules

Content should be importable by Laravel artisan commands.

Recommended commands:

```bash
php artisan nuvio:content:validate
php artisan nuvio:content:import
```

Validation must check:

- Required fields exist.
- Slugs are unique in their scope.
- Referenced subjects exist.
- Referenced LearningNodes exist.
- Every task has at least one LearningNode.
- TaskVersion schema matches `answerType`.
- Numeric tasks include value and tolerance.
- Multiple choice tasks have exactly one correct choice.
- LearningPath node order is valid.
- NodeRelation does not point from a node to itself.
- Review policy is valid.

Publishing rules:

- Validate before import.
- Import unpublished or inactive content first if needed.
- Publish only after validation passes.
- Failed imports should report file path and field errors.

## 17. File Structure Under /content

Preferred format: **pure YAML**.

Reason:

- YAML is readable for authors.
- YAML supports nested structures for tasks and versions.
- It is easier to validate consistently than Markdown with frontmatter.
- Markdown can still be used inside string fields such as `prompt`, `body`, or `explanation`.

Recommended structure:

```text
content/
  subjects/
    math.yaml
    physics.yaml                 # later
    electrical-engineering.yaml  # later
    chemistry.yaml               # later
  nodes/
    math/
      linear-functions.yaml
    physics/                     # later
      acceleration.yaml
    electrical-engineering/      # later
      voltage-dividers.yaml
    chemistry/                   # later
      ph-and-redox.yaml
  relations/
    core-relations.yaml
  paths/
    algebra-foundations.yaml
    mechanics-basics.yaml        # later
    circuit-fundamentals.yaml    # later
    chemistry-foundations.yaml   # later
  tasks/
    math/
      linear-functions.yaml
    physics/                     # later
      acceleration.yaml
    electrical-engineering/      # later
      voltage-dividers.yaml
    chemistry/                   # later
      ph.yaml
    cross-disciplinary/          # later
      ratios-and-voltage-dividers.yaml
```

## 18. Examples

### Math Task: Linear Functions

```yaml
slug: identify-slope-in-linear-function
type: multiple_choice
learningNodes:
  - linear-functions-slope-intercept
estimatedMinutes: 5
difficulty: 1
answerType: multiple_choice
tags:
  - algebra
  - functions
reviewPolicy:
  onIncorrect: create_review
  onUnsure: create_review
versions:
  - version: 1
    prompt: In y = 4x - 7, what does 4 represent?
    choices:
      - key: a
        text: The slope
        correct: true
      - key: b
        text: The y-intercept
        correct: false
      - key: c
        text: The output value
        correct: false
    answerSchema:
      type: multiple_choice
      correctChoice: a
    explanation: In y = mx + b, m is the slope. Here m = 4.
```

### Electrical Engineering Task: Voltage Divider

```yaml
slug: calculate-voltage-divider-output
type: numeric
learningNodes:
  - voltage-divider-ratio
estimatedMinutes: 10
difficulty: 2
answerType: numeric
tags:
  - circuits
  - voltage-divider
reviewPolicy:
  onIncorrect: create_review
  onUnsure: create_review
versions:
  - version: 1
    prompt: A voltage divider has Vin = 12 V, R1 = 3 kohm, and R2 = 1 kohm. What is Vout across R2?
    answerSchema:
      type: numeric
      correctValue: 3
      tolerance: 0.01
      unit: V
    explanation: Vout = Vin * R2 / (R1 + R2), so 12 * 1 / 4 = 3 V.
```

### Chemistry Task: pH

```yaml
slug: calculate-ph-from-hydrogen-concentration
type: numeric
learningNodes:
  - calculate-ph
estimatedMinutes: 8
difficulty: 2
answerType: numeric
tags:
  - chemistry
  - ph
reviewPolicy:
  onIncorrect: create_review
  onUnsure: create_review
versions:
  - version: 1
    prompt: What is the pH of a solution with [H+] = 1e-3 M?
    answerSchema:
      type: numeric
      correctValue: 3
      tolerance: 0.01
    explanation: pH = -log10([H+]). For 1e-3 M, pH = 3.
```

### Physics Task: Acceleration

```yaml
slug: calculate-acceleration-from-velocity-change
type: numeric
learningNodes:
  - calculate-acceleration
estimatedMinutes: 6
difficulty: 1
answerType: numeric
tags:
  - physics
  - mechanics
reviewPolicy:
  onIncorrect: create_review
  onUnsure: create_review
versions:
  - version: 1
    prompt: A cyclist speeds up from 2 m/s to 10 m/s in 4 s. What is the acceleration?
    answerSchema:
      type: numeric
      correctValue: 2
      tolerance: 0.01
      unit: m/s^2
    explanation: Acceleration is change in velocity divided by time: (10 - 2) / 4 = 2 m/s^2.
```

### Cross-Disciplinary Task: Math And Electrical Engineering

```yaml
slug: use-ratio-reasoning-for-voltage-divider
type: numeric
learningNodes:
  - linear-ratio-reasoning
  - voltage-divider-ratio
estimatedMinutes: 12
difficulty: 3
answerType: numeric
tags:
  - cross-disciplinary
  - algebra
  - circuits
reviewPolicy:
  onIncorrect: create_review
  onUnsure: create_review
versions:
  - version: 1
    prompt: In a voltage divider, R1 and R2 are equal. Vin is 10 V. What is Vout across R2?
    answerSchema:
      type: numeric
      correctValue: 5
      tolerance: 0.01
      unit: V
    explanation: Equal resistors split the voltage equally, so Vout is half of 10 V.
```

## 19. Quality Checklist

Before content is published:

- Subject slugs are valid.
- LearningNode slugs are valid and linked to subjects.
- NodeRelations reference existing nodes.
- LearningPaths use ordered existing nodes.
- Every task belongs to at least one LearningNode.
- Multi-node tasks have a clear primary learning purpose.
- Every task has `estimatedMinutes`, `difficulty`, and `answerType`.
- Every task has at least one TaskVersion.
- TaskVersion answer schema validates.
- Explanations teach the concept briefly.
- Incorrect, unsure, and skipped attempts have review behavior.
- Numeric tolerances are explicit.
- Multiple choice distractors are plausible.
- Content is respectful and suitable for adults.
- Content can be imported by artisan command without manual database edits.

## 20. Future AI-Assisted Authoring

AI may later help draft tasks, explanations, distractors, and hints.

Rules:

- AI-authored content must be saved as draft.
- AI output must pass the same validation as human-authored content.
- Official publication requires human review.
- AI should not silently generate official curriculum.
- AI should use existing LearningNodes and NodeRelations as context.
- AI should suggest review policy and difficulty, but validation decides whether the content is publishable.
