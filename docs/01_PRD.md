# Nuvio Product Requirements Document

## 1. Product Overview

Nuvio is a Duolingo-like learning platform for adults. It helps users learn technical and scientific subjects through small tasks, review loops, visible progress paths, and later simulations and a constrained AI teacher.

The MVP is a Laravel API backend only. No frontend implementation is part of the MVP documentation except where API behavior must support future UX flows.

Internally, Nuvio uses a Learning Graph called SkillGraph. Subjects, learning paths, career paths, project paths, simulations, skills, prerequisites, and tasks should be modeled as connected learning structures rather than isolated courses.

## 2. User Personas

### Focused Refresher

An adult who has learned a subject before and wants to rebuild fluency.

Example: A developer reviewing algebra and physics before studying machine learning.

Needs:

- Fast path selection.
- Short sessions.
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

- Users can register, log in, log out, and fetch their current profile.
- Laravel Sanctum is the preferred MVP authentication option.

### SkillGraph

- The system must store subjects, skills, prerequisites, paths, and tasks.
- A skill can belong to a subject.
- A path can contain ordered skills.
- A skill can depend on prerequisite skills.
- Tasks must link to skills.
- Career paths and project paths should be possible later without changing the core model.

### Daily Recommendations

- The API must return zero to three recommended actions.
- Actions must include a type, title, reason, priority, estimated duration, and target.
- Due reviews must be prioritized before new learning.

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
- Include concise reasons for recommendations.
- Avoid API shapes that require the frontend to build overwhelming dashboards.
- Support short task sessions.
- Support an `unsure` attempt state.
- Avoid shame-based streak mechanics.
- Allow progress recovery after missed days.
- Make next actions explicit.

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

Initial subjects:

- Math.
- Physics.
- Electrical engineering.
- Chemistry.

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

### Assumptions

- A relational database is sufficient for the MVP SkillGraph.
- Laravel API conventions are enough for the initial backend.
- Seeded content is acceptable before a CMS exists.
- The first useful product loop is task, attempt, progress, review, recommendation.
- Career paths and project paths can be modeled later as graph views over skills.

## 18. Acceptance Criteria

The PRD is satisfied when the MVP backend can support these behaviors:

- A user can register and authenticate.
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
- Initial seed content exists for one subject path with enough nodes and tasks to exercise the full loop.
- The model can later support biology, computer science, career paths, project paths, simulations, and user-created tasks without changing the core learning model.
