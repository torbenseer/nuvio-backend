# Nuvio Backend

Nuvio is an adult learning API built around a small learning loop: Today, Task, Attempt, Feedback, Review, and Progress. This glossary is the canonical domain language for the backend MVP.

## Language

**SkillGraph**:
The learning graph that connects subjects, reusable learning nodes, learning paths, tasks, reviews, and mastery states. It is implemented with relational tables in the MVP.
_Avoid_: Course tree, curriculum graph

**LearningNode**:
A reusable learning unit inside SkillGraph. In the MVP, a LearningNode is a `skill`.
_Avoid_: Lesson, module, level

**NodeRelation**:
A directed relationship between two LearningNodes. The MVP accepts only `prerequisite`; richer relation types are later extensions.
_Avoid_: Edge, dependency

**LearningPath**:
An ordered path through LearningNodes that a learner can enroll in.
_Avoid_: Course, track

**Enrollment**:
A learner's participation in one LearningPath.
_Avoid_: Subscription, registration

**Task**:
A practice item linked to one or more LearningNodes.
_Avoid_: Exercise, question

**TaskVersion**:
The immutable task prompt, answer schema, and explanation used by attempts. Attempts reference the TaskVersion they saw.
_Avoid_: Revision, variant

**TaskAttempt**:
One learner interaction with one TaskVersion. TaskAttempts are immutable learning evidence once completed.
_Avoid_: Answer, submission

**Review**:
Scheduled review work created from weak evidence such as incorrect, unsure, or skipped attempts.
_Avoid_: Retake, remedial task

**MasteryState**:
One learner's progress state for one LearningNode.
_Avoid_: Score, progress row

**Today Action**:
A capped recommendation returned by Today selection. The learner should see at most three Today Actions.
_Avoid_: Recommendation item, dashboard card

**Energy Mode**:
The learner's selected intensity for Today recommendations: `red`, `yellow`, or `green`.
_Avoid_: Session mode, difficulty mode

**Competence Feedback**:
Feedback or progress state that describes learning evidence and next useful work, such as `correct`, `incorrect`, `unsure`, `skipped`, `review_due`, or `retained`.
_Avoid_: Reward, achievement, XP result

**Recovery Action**:
A learner action that keeps learning moving without pressure, such as `unsure`, `skipped`, or review snooze.
_Avoid_: Failure escape, streak repair, catch-up

**Pressure Mechanic**:
A motivation pattern based on loss, rank, series maintenance, collection, or daily obligation. Examples: XP, badges, achievements, streaks, streak freezes, leaderboards, reward levels, catch-up debt, and lost-progress state.
_Avoid_: Gamification, engagement mechanic

**Completion State**:
A short neutral state shown after a real TaskAttempt or Review action, such as "Review geplant" or "Behalten".
_Avoid_: Reward, celebration, XP payout

**Mastery Moment**:
A short competence acknowledgement after meaningful evidence, such as a correct Review or a `review_due` to `retained` transition.
_Avoid_: Achievement, badge, level-up

**Skill-Map**:
A compact secondary view over SkillGraph that shows LearningNode competence state. Today remains the main surface.
_Avoid_: Dashboard, level map, reward path

**Challenge Option**:
An optional next learning choice such as `similar_task`, `slightly_harder`, or `short_review`.
_Avoid_: Reward choice, loot reveal

**LearningSession**:
A later-phase grouping of learning activity. It is not part of the narrow MVP and is distinct from Laravel's technical `sessions` table.
_Avoid_: Session
