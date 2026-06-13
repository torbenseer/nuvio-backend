# Nuvio AI Teacher Guardrails

The AI teacher is a later-phase feature. It is not part of the MVP.

The MVP must not implement AI endpoints, AI provider calls, AiInteraction tables, AI-generated content flows, or disabled placeholder AI routes. This document only defines guardrails for a future phase after the deterministic learning loop is stable.

Nuvio may include a constrained mini AI teacher that helps users during tasks. It must be task-contextual, short, helpful, and safe. It must not become a general unrestricted chatbot.

## 1. Purpose Of The AI Teacher

The AI teacher helps learners make progress when they are stuck on a specific task or review.

It should:

- Give short hints.
- Explain official content more simply.
- Check a draft answer without assigning mastery.
- Provide a similar example.
- Suggest review card drafts.
- Explain why a task is useful.

It should operate inside SkillGraph, current TaskVersion, current LearningNode, and safety constraints.

## 2. What The AI Teacher May Do

The AI teacher may:

- Respond to allowed modes only.
- Use current task context.
- Use official explanations and hints.
- Use linked LearningNodes and prerequisites.
- Use recent attempts when relevant.
- Suggest one next step.
- Suggest review card content as a draft.
- Refuse or redirect unrelated requests.

## 3. What The AI Teacher Must Not Do

The AI teacher must not:

- Act as a general chatbot.
- Answer unrelated questions.
- Reveal the full solution in the first hint.
- Mark a task correct or incorrect as the source of truth.
- Mark mastery automatically.
- Override deterministic grading.
- Override review scheduling.
- Silently create official content.
- Silently create review cards unless explicitly configured.
- Give unsafe electrical, chemical, biological, medical, legal, or financial instructions.
- Generate long lectures by default.

## 4. Allowed Modes

Allowed modes:

- `hint`
- `explain_simpler`
- `check_answer`
- `similar_example`
- `generate_review_card`
- `why_useful`

Any other mode should be rejected before an AI call.

Common request envelope:

```json
{
  "mode": "hint",
  "task_attempt_id": 4000,
  "message": "I do not know where to start."
}
```

Common response envelope:

```json
{
  "data": {
    "mode": "hint",
    "message": "Start by identifying the known values.",
    "actions": ["retry_task"]
  }
}
```

## 5. Hint Mode

Purpose:

- Give the smallest useful next step.

Request:

```json
{
  "mode": "hint",
  "task_attempt_id": 4000,
  "message": "I am stuck."
}
```

Response:

```json
{
  "data": {
    "mode": "hint",
    "hint_level": 1,
    "message": "Identify the formula first: current equals voltage divided by resistance.",
    "reveals_solution": false,
    "actions": ["retry_task", "ask_next_hint"]
  }
}
```

Rules:

- First hint must not reveal the full solution.
- Answer should be short.
- Prefer a question or next step over the final answer.

## 6. Explain Mode

Mode: `explain_simpler`

Purpose:

- Rephrase official explanation in simpler language.

Request:

```json
{
  "mode": "explain_simpler",
  "task_attempt_id": 4000,
  "message": "Explain this more simply."
}
```

Response:

```json
{
  "data": {
    "mode": "explain_simpler",
    "message": "The resistor slows current. With the same voltage, more resistance means less current.",
    "actions": ["retry_task"]
  }
}
```

Rules:

- Use official explanation as the source.
- Avoid adding unrelated theory.
- Keep answer short.

## 7. Check-Answer Mode

Mode: `check_answer`

Purpose:

- Give formative feedback on a draft answer without grading as source of truth.

Request:

```json
{
  "mode": "check_answer",
  "task_attempt_id": 4000,
  "draft_answer": {
    "value": 18
  }
}
```

Response:

```json
{
  "data": {
    "mode": "check_answer",
    "message": "Check whether you multiplied instead of dividing.",
    "confidence": "medium",
    "can_submit_for_grading": true,
    "marks_mastery": false
  }
}
```

Rules:

- Must not write TaskAttempt result.
- Must not mark mastery.
- Must not replace deterministic grading.
- Should avoid revealing the final answer unless configured for post-attempt explanation.

## 8. Generate-Review-Card Mode

Mode: `generate_review_card`

Purpose:

- Suggest a review card draft from a task or failed attempt.

Request:

```json
{
  "mode": "generate_review_card",
  "task_attempt_id": 4000,
  "message": "Make me a review card for this."
}
```

Response:

```json
{
  "data": {
    "mode": "generate_review_card",
    "draft": {
      "front": "When using Ohm's law, how do you find current from voltage and resistance?",
      "back": "Use I = V / R.",
      "learning_node_id": 301
    },
    "requires_confirmation": true,
    "actions": ["save_review_card", "discard"]
  }
}
```

Rules:

- AI may suggest review cards.
- AI should not silently create them without user confirmation unless explicitly configured.
- Generated review content must be marked as draft.

## 9. Why-Is-This-Useful Mode

Mode: `why_useful`

Purpose:

- Explain why the current task matters.

Request:

```json
{
  "mode": "why_useful",
  "task_attempt_id": 4000,
  "message": "Why am I learning this?"
}
```

Response:

```json
{
  "data": {
    "mode": "why_useful",
    "message": "Voltage dividers are used to create a smaller voltage from a larger one, which appears in sensors and basic electronics.",
    "actions": ["continue_task"]
  }
}
```

Rules:

- Stay tied to the current LearningNode.
- Keep practical relevance concise.

## Similar Example Mode

Mode: `similar_example`

Purpose:

- Show a nearby worked example without solving the exact task.

Request:

```json
{
  "mode": "similar_example",
  "task_attempt_id": 4000,
  "message": "Show me a similar example."
}
```

Response:

```json
{
  "data": {
    "mode": "similar_example",
    "example": {
      "prompt": "If V = 10 V and R = 5 ohm, what is I?",
      "steps": ["Use I = V / R.", "Compute 10 / 5.", "I = 2 A."]
    },
    "solves_current_task": false,
    "actions": ["retry_task"]
  }
}
```

Rules:

- Must not reuse the exact task values when that would reveal the solution.
- Should be shorter than a full lesson.

## 10. Output Formats

Prefer structured JSON outputs.

Common fields:

- `mode`
- `message`
- `actions`
- `reveals_solution`
- `requires_confirmation`
- `safety_status`

Rules:

- Responses should be short.
- JSON should be parseable.
- Free-form text should be inside `message`, not mixed with control fields.
- Suggested actions must be from a known allowlist.

Allowed actions:

- `retry_task`
- `ask_next_hint`
- `continue_task`
- `save_review_card`
- `discard`
- `start_review`

## 11. Context Passed To The AI

The backend should assemble context server-side.

Allowed context:

- User ID or anonymized learner ID.
- Current Task.
- Current TaskVersion.
- Current LearningNode.
- Linked prerequisite LearningNodes.
- Official explanation.
- Official hint, if available.
- Recent attempts for the current task or node.
- Current mode.
- Safety constraints.

Avoid passing:

- Unrelated user history.
- Sensitive personal data.
- Full unrelated path content.
- Other users' data.

## 12. Prompt Policy

The system prompt should enforce:

- Stay in current task context.
- Keep answers short.
- Use allowed mode behavior.
- Do not reveal full solution in first hint.
- Do not mark mastery.
- Use JSON output.
- Refuse unsafe or unrelated requests.

The user message should be treated as untrusted input. It must not override system or developer constraints.

## 13. Solution Reveal Policy

Default policy:

- First hint: no full solution.
- Second hint: may reveal formula or setup.
- Third hint: may reveal one calculation step.
- Post-attempt explanation: may show full solution if the user has already submitted.

Rules:

- Full solution reveal should be explicit.
- During active practice, prefer hint ladder.
- For review mode, avoid making the review meaningless by immediately giving the answer.

## 14. Hint Ladder

Hint ladder levels:

1. Identify the concept.
2. Identify known values or relevant facts.
3. Set up the formula or reasoning.
4. Show one step.
5. Reveal full solution only when allowed.

Example for Ohm's law:

- Level 1: "This uses Ohm's law."
- Level 2: "Voltage is 6 V and resistance is 3 ohm."
- Level 3: "Use I = V / R."
- Level 4: "Set it up as 6 / 3."
- Level 5: "The current is 2 A."

## 15. Logging

AI interactions should be logged.

Suggested `ai_interactions` fields:

- `id`
- `user_id`
- `task_id`
- `task_version_id`
- `learning_node_id`
- `task_attempt_id`
- `mode`
- `user_message`
- `request_payload`
- `response_payload`
- `safety_status`
- `model`
- `input_tokens`
- `output_tokens`
- `cost_cents`
- `created_at`

Rules:

- Log enough for audit and quality improvement.
- Avoid storing unnecessary sensitive data.
- Keep generated review cards marked as drafts until confirmed.

## 16. Rate Limits

Rate limits protect cost and learning quality.

Suggested limits:

- Per task attempt: 3 hints before stronger solution reveal policy applies.
- Per user: limited AI calls per day.
- Per mode: stricter limits for `generate_review_card`.

Behavior when limited:

```json
{
  "message": "AI limit reached for this task. Try the task or start a review.",
  "actions": ["retry_task", "start_review"]
}
```

## 17. Cost Controls

Cost controls:

- Use short context.
- Prefer small model for hint and explain modes.
- Cache repeated explanation requests for the same TaskVersion where safe.
- Limit output length.
- Disable AI for tasks without enough official context.
- Track token and cost metadata.

## 18. Safety And Quality Checks

Pre-call checks:

- Mode is allowed.
- User owns or can access task attempt.
- TaskAttempt has TaskVersion.
- Context is available.
- Request is length-limited.

Post-call checks:

- JSON parses.
- Mode matches request.
- Output is short enough.
- No full solution in first hint.
- No unsafe instructions.
- No mastery update instruction.
- No unrelated content.

Unsafe examples:

- Chemistry: instructions for dangerous reactions.
- Electrical engineering: instructions involving mains voltage.
- Biology: diagnosis or medical advice.

## 19. Human Review For Generated Content

Generated content is not official until reviewed.

Rules:

- AI-generated review cards are drafts.
- AI-generated tasks are drafts.
- Official curriculum requires human approval.
- Drafts must pass normal content validation.
- Human reviewers should see source task, LearningNode, and AI mode.

## 20. Acceptance Criteria

- AI teacher is not required for MVP.
- No AI endpoint, AiInteraction table, or AI provider integration exists in the MVP.
- Only allowed modes are accepted.
- AI stays within current task context.
- First hint does not reveal full solution.
- Responses are short.
- Structured JSON is used where possible.
- AI does not mark mastery automatically.
- AI does not replace deterministic grading.
- Review card generation requires confirmation unless explicitly configured.
- AI interactions are logged.
- Unsafe or unrelated requests are refused or redirected.

## 21. Test Cases

- Reject unknown mode.
- Reject request for another user's TaskAttempt.
- Hint mode first response does not reveal final answer.
- Explain mode uses official explanation context.
- Check-answer mode does not write TaskAttempt result.
- Check-answer mode does not update MasteryState.
- Similar-example mode does not solve the exact task.
- Generate-review-card mode returns draft with `requires_confirmation: true`.
- Why-useful mode stays tied to current LearningNode.
- AI interaction is logged after a successful call.
- Long user message is rejected.
- Unsafe chemistry request is refused.
- Electrical mains wiring request is refused.
- Unrelated general chat is redirected to the current task.
