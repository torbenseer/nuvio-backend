# Nuvio Product Requirements Document

## 1. Product Overview

Nuvio is a learning platform for adults. It helps users learn technical and scientific subjects through small tasks, review loops, visible competence progress, and later simulations and a constrained AI teacher.

The MVP is a Laravel API backend only. No frontend implementation is part of the MVP documentation except where API behavior must support future UX flows.

Internally, Nuvio uses a Learning Graph called SkillGraph. Subjects, learning paths, career paths, project paths, simulations, skills, prerequisites, and tasks should be modeled as connected learning structures rather than isolated courses.

## 2. User Personas

### Focused Refresher

An adult who has learned a subject before and wants to rebuild fluency.

Example: A developer reviewing algebra and physics before studying machine learning.

Needs:

- Fast path selection.
- Short learning blocks.
- Review-heavy practice.
- Clear weak-skill indicators.

### Career Builder

An adult learning foundations for a career change or technical role.

Example: A learner studying circuit fundamentals for electrical technician work.

Needs:

- Career-relevant learning paths.
- Applied tasks.
- Later project-based learning.
- Progress that reflects practical competence.

### Curious Explorer

An adult learning out of interest.

Example: A learner exploring chemistry through atoms, formulas, and reactions.

Needs:

- Simple entry points.
- Motivating progress.
- Simulations later.
- Low-pressure review and practice.

## 3. Main User Problems

- Users do not know what to study next.
- Existing learning tools often show too many options.
- Users forget material when review is not central.
- Progress is often measured by time spent rather than retained skill.
- Interdisciplinary learning is hard to organize across subjects.
- Adult learners need practical, respectful, low-friction learning loops.

## 4. Product Goals

- Make daily learning simple and clear.
- Show at most three recommended actions per day.
- Build durable knowledge through practice, retention, and transfer.
- Make reviews a core part of the learning experience.
- Support extensible interdisciplinary content through SkillGraph.
- Keep simulations, project paths, career paths, and AI teacher features pluggable for later phases.

## 5. MVP Goals

The MVP should prove the core backend learning loop:

1. A user enrolls in a learning path.
2. Nuvio recommends up to three daily actions.
3. The user completes small tasks.
4. Attempts update progress.
5. Incorrect, unsure, or skipped attempts generate reviews.
6. Due reviews are recommended before new learning.
7. Progress can be queried by skill and path.

The MVP should include Laravel API support for:

- Authentication.
- Subjects.
- Skills.
- Skill prerequisites.
- Learning paths.
- Tasks.
- Task attempts.
- Skill progress.
- Review scheduling.
- Daily recommendations.

## 6. Core User Journeys

### Select A Path

The user chooses a subject and enrolls in a learning path.

API support:

- List subjects.
- List paths for a subject.
- Create enrollment.

### Do Today's Work

The user opens Nuvio and sees up to three recommended actions.

Recommendation priority:

1. Due reviews.
2. Review-due skills.
3. Next task in an enrolled path.

### Complete A Task

The user answers a small task or marks it as unsure.

The API must:

- Validate the answer.
- Grade the attempt.
- Store the attempt.
- Update progress.
- Create or update a review when needed.

### Review A Weak Skill

The user completes review work generated from incorrect, unsure, or skipped attempts.

The API must:

- Return due reviews.
- Link review attempts to task attempts.
- Update next review date.
- Update skill progress.

### View Progress

The user can see path and skill progress.

Progress should show competence, not only activity time.

## 7. Functional Requirements

### Authentication

- Users can register through the backend API, authenticate through Laravel Sanctum SPA auth, log out, and fetch their current profile.
- `GET /api/user` should include locale preferences needed by the first frontend slice.

### SkillGraph

- The system must store subjects, skills, prerequisites, paths, and tasks.
- A skill can belong to a subject.
- A path can contain ordered skills.
- A skill can depend on prerequisite skills.
- Tasks must link to skills.
- Career paths and project paths should be possible later without changing the core model.

### Daily Recommendations

- The API must return zero to three recommended actions.
- V1 actions must include a type, concrete title, priority, estimated duration, and target.
- V1 actions must not expose `reason`; explainability belongs in deterministic ordering and tests, not learner-facing pressure text.
- Due reviews must be prioritized before new learning.
- Review actions must be framed as ready to review or ready to refresh, not overdue work.

### Task Attempts

- The API must support correct, incorrect, unsure, and skipped outcomes.
- Attempts must be stored immutably.
- Attempts must update progress.
- Incorrect, unsure, and skipped attempts must create or update reviews.

### User-Created Tasks Later

- The model should allow tasks to be official or user-owned.
- User-created tasks are not required in the first MVP slice, but the data model should not block them.
- Later, user-created tasks should be private by default and linkable to existing skills.

## 8. Non-Functional Requirements

- API responses must be consistent and predictable.
- Core learning services must be testable without HTTP.
- Review scheduling must be deterministic.
- Recommendation logic must be explainable.
- The relational model should be sufficient for the MVP; no graph database is required.
- Adding biology or computer science later should not require rewriting review, progress, or recommendation logic.
- The system should use Laravel conventions for models, migrations, controllers, requests, resources, and tests.

## 9. ADHD-Friendly UX Requirements

Although the MVP is API-only, backend responses must support an ADHD-friendly frontend.

Requirements:

- Return at most three daily actions.
- Include concrete action titles and estimated minutes for recommendations.
- Avoid API shapes that require the frontend to build overwhelming dashboards.
- Support short task-focused learning blocks.
- Support an `unsure` attempt state.
- Avoid shame-based streak mechanics.
- Allow progress recovery after missed days.
- Make next actions explicit.
- Keep Review, Unsure, Skip, and Snooze available as normal learning and recovery paths.
- Avoid API fields that require the frontend to present backlog size as pressure or loss.
- Do not add reward counters, collection state, streak state, leaderboard rank, or achievement state to V1 or B4 responses.

## 9.1 Motivation Without Pressure Requirements

Nuvio should motivate through competence, structure, and recovery.

Motivating competence feedback means:

- Feedback describes the current learning evidence and the next useful step.
- Progress states communicate what is unknown, practiced, due for review, or retained.
- Correct work can improve MasteryState.
- Incorrect, unsure, skipped, or review-lapse work can create or update Review without shame language.

Controlling gamification means:

- The learner is asked to maintain a visible series, rank, currency, collection, or reward ladder.
- Missing a day, taking a break, or using recovery actions creates loss, debt, repair work, or social comparison.
- Friendly copy does not make these mechanics acceptable; the controlling effect comes from the mechanic.

Helpful structure means:

- Today returns at most three actions.
- Review appears before new learning.
- Tasks are small enough to finish.
- Completion states close the loop without demanding more work.

Pressure mechanics means:

- XP, badges, achievements, streaks, streak freezes, leaderboards, reward levels, daily pressure copy, repeated confetti, or backlog debt signals.
- Copy such as "catch up", "behind", "failed", "lost progress", "save your streak", or "don't break your run".

Implementation requirements:

- API progress fields must be learning-evidence fields such as MasteryState counts, Review counts, and path progress derived from LearningNodes.
- API responses must not include XP totals, badge collections, streak counts, streak freezes, leaderboard ranks, or achievement unlocks.
- UI copy keys and backend display strings must be neutral enough to render after correct, incorrect, unsure, skipped, snoozed, and resumed-after-break states.
- Any due review count must be presented as available learning work, not debt.

## 9.2 Playful Without Pressure Requirements

Nuvio may feel playful when play comes from learning interaction and visible competence.

Allowed product behavior:

- A Skill-Map can show Algebra Foundations as a compact graph of LearningNodes.
- Map nodes show competence status only: `unknown`, `practiced`, `review_due`, or `retained`.
- Review nodes may show "ready to review" or "reactivate".
- Completion States can appear after real learning actions.
- Mastery Moments can appear after a correct Review, a `review_due` to `retained` transition, a formerly unsure task later solved correctly, or a short return-after-break action.
- Optional challenge choices can offer `similar_task`, `slightly_harder`, or `short_review` when supported by Today or task selection.

Forbidden product behavior:

- Skill-Map nodes must not show stars, level numbers, badge slots, reward slots, streak state, or collection completion.
- No XP, badges, achievements, streaks, streak freezes, comeback streaks, leaderboards, ranks, daily pressure, loss logic, lootbox feeling, artificial scarcity, countdown pressure, or reward for attendance.
- Completion States must not use "+50 XP", "Badge earned", "Streak saved", "Back in the race", "catch up", "behind", "failed", or "wrong again".

Implementation requirements:

- V1 keeps numeric tasks and may use short Completion States only.
- B4 may add Completion States and Mastery Moments after real learning evidence. Skill-Map response fields and optional challenge choice metadata are Later and require separate API contracts.
- Later interactive Algebra tasks may add equation-step transformation, term balancing, error marking, and graph manipulation without changing grading or review scheduling guarantees.
- All playful UI must preserve Review-before-new-learning, max-three Today actions, deterministic grading, deterministic review scheduling, and no answer leaks.

## 9.3 Motivation And Enjoyment Requirements

Nuvio makes learning enjoyable by reducing resistance and making competence visible. Enjoyment means: the learner can start without planning, complete a small useful action, understand the result, and trust that Nuvio will manage the next review.

Requirements:

- Today must create a positive opening moment.
- Today must not start with review guilt, backlog debt, missed-day counts, or catch-up framing.
- Review actions must appear as "bereit zum Wiederholen", not as "überfällig".
- Feedback must always create a good next state: continue, review scheduled, retained, or done for now.
- Incorrect, Unsure, and Skip must communicate: "Nuvio kümmert sich darum", not "Ich habe versagt".
- Progress Summary must provide orientation, not performance accounting.
- Enjoyment metrics must measure low friction and willingness to return, not raw activity pressure.

Acceptance criteria:

- A learner can open Nuvio after 14 days away and sees no guilt, loss, catch-up, missed-day, or debt language.
- Today still shows at most three actions.
- No visible list of all missed or due reviews is shown.
- Unsure and Skip are as reachable as Submit.
- Feedback explains the fachlicher nächster Schritt.
- Completion States appear only after real learning actions.

## 10. Learning Science Requirements

Nuvio should support learning through:

- Retrieval practice.
- Spaced review.
- Immediate feedback.
- Interleaving over time.
- Prerequisite-aware progression.
- Transfer through later projects and simulations.

Implementation requirements:

- Reviews are generated from incorrect, unsure, or skipped attempts.
- Progress is based on attempts, mastery, retention, and review outcomes.
- Time spent alone must not be the main progress metric.
- Skills should be small enough to practice and review.

## 11. Content Requirements

Post-MVP subject expansion targets:

- Math.
- Physics.
- Electrical engineering.
- Chemistry.

MVP seed content is narrower: one German Math path, Algebra Foundations. Additional subjects are content expansion after the backend MVP and first usable learning loop are stable.

Initial examples:

- Math: solve linear equations.
- Physics: calculate speed or acceleration.
- Electrical engineering: apply Ohm's law.
- Chemistry: count atoms in formulas or balance simple equations.

Content must support:

- Subjects.
- Skills.
- Prerequisites.
- Learning paths.
- Tasks.
- Explanations.
- Difficulty levels.
- Extensible task types.

The MVP can use seeders or structured fixtures. A complex admin CMS is out of scope.

## 12. Progress Requirements

Progress must be tracked by user and skill.

Progress should include:

- Status.
- Mastery score or equivalent.
- Correct attempt count.
- Incorrect attempt count.
- Unsure attempt count.
- Last practiced timestamp.
- Learned or completed timestamp where applicable.

Progress states may include:

- `unknown`
- `practiced`
- `review_due`
- `retained`

Acceptance requirements:

- Correct attempts improve progress.
- Incorrect, unsure, or skipped attempts do not improve mastery.
- Repeated failures can move a retained skill back to review_due.
- Path progress is derived from skill progress.
- Progress must not include XP, badge, streak, rank, or level reward fields.
- `review_due` means normal scheduled learning work, not failure or lost progress.

## 13. Review Requirements

Reviews are central to Nuvio.

The review engine must:

- Create reviews from incorrect attempts.
- Create reviews from unsure attempts.
- Create reviews from skipped attempts.
- Prioritize due reviews in daily recommendations.
- Update review intervals after review attempts.
- Avoid creating unlimited duplicate reviews for the same user, skill, and task.

Review records should support:

- User.
- Skill.
- Optional task.
- Due date.
- Interval.
- Lapse count.
- Status.

## 14. Simulation Requirements

Simulations are later-phase features.

The MVP should not implement a simulation engine. It should keep the model open for simulations to link to SkillGraph later.

Future simulations should:

- Link to one or more skills.
- Be usable as recommended actions.
- Support subjects like physics, electrical engineering, chemistry, and math.
- Record completion or interaction summaries only through explicit backend rules.

Examples:

- Physics: change mass and force to observe acceleration.
- Electrical engineering: change voltage and resistance to observe current.
- Chemistry: balance atoms in a reaction.
- Math: manipulate slope and intercept on a graph.

## 15. AI Teacher Requirements For Later Phases

AI is optional and constrained. It is not part of the core MVP.

Later AI teacher features may include:

- Hints for the current task.
- Short explanations.
- Rephrasing official explanations.
- Help understanding why an answer was wrong.
- Guidance toward prerequisite skills.

The AI teacher must not be:

- A general-purpose chatbot.
- The source of deterministic grading.
- A replacement for review scheduling.
- An unrestricted content generator.

AI context should be constrained by:

- Current user.
- Current task.
- Current skill.
- Current path.
- Official explanation.
- Recent relevant attempts.

## 16. Out Of Scope For MVP

- Payments or subscriptions.
- Community features.
- Native mobile apps.
- Complex admin CMS.
- Full AI tutoring.
- Open-ended AI chat.
- AI endpoints or AiInteraction tables.
- Advanced simulation execution.
- Simulation endpoints or SimulationRun tables.
- XP, achievements, badges, or streak mechanics.
- Streak freezes, leaderboards, reward levels, daily pressure mechanics, or gamified catch-up flows.
- Team, classroom, or organization management.
- Certificates.
- Marketplace or shared user-generated content.

## 17. Risks And Assumptions

### Risks

- SkillGraph could become too abstract too early.
- Recommendation logic could become hard to explain.
- Review scheduling could become inconsistent without tests.
- Content authoring could become slow without validation.
- Progress metrics could accidentally reward time spent instead of learning.
- Motivation features could accidentally introduce pressure through loss, rank, collection, or daily obligation mechanics.

### Assumptions

- A relational database is sufficient for the MVP SkillGraph.
- Laravel API conventions are enough for the initial backend.
- Seeded content is acceptable before a CMS exists.
- The first useful product loop is task, attempt, progress, review, recommendation.
- Career paths and project paths can be modeled later as graph views over skills.

## 18. Acceptance Criteria

The PRD is satisfied when the MVP backend can support these behaviors:

- A user can register through the backend API and authenticate through the Sanctum SPA flow.
- A user can list subjects and learning paths.
- A user can enroll in a path.
- The system can represent SkillGraph with subjects, skills, prerequisites, paths, and tasks.
- The daily recommendation API returns at most three actions.
- Due reviews are recommended before new learning.
- A user can submit task attempts.
- Attempts can be correct, incorrect, unsure, or skipped.
- Incorrect, unsure, and skipped attempts create or update reviews.
- Correct attempts update skill progress.
- Progress can be queried by path and skill.
- Progress responses expose competence and review status without XP, badges, streaks, leaderboards, reward levels, or loss-state fields.
- Unsure, Skip, and Snooze paths remain valid recovery behavior and do not produce shame or loss copy.
- V1 acceptance includes no controlling gamification fields or UI requirements; B4 acceptance preserves the same boundary while hardening the wider API.
- Initial seed content exists for one subject path with enough nodes and tasks to exercise the full loop.
- The model can later support biology, computer science, career paths, project paths, simulations, and user-created tasks without changing the core learning model.
