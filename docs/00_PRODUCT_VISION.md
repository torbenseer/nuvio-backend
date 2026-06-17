# Nuvio Product Vision

## 1. Product Summary

Nuvio is a learning platform for adults. It helps users learn interdisciplinary knowledge through small tasks, clear progress, spaced reviews, simulations, and later a constrained AI teacher.

The MVP is a Laravel API only. It should prove the core learning loop before any frontend, mobile app, complex CMS, payments, community features, or full AI tutoring are added.

Internally, Nuvio uses a Learning Graph called SkillGraph. Subjects, learning paths, career paths, projects, and simulations are different views on the same graph.

## 2. Problem Statement

Adult learners often want to learn technical and scientific subjects but face three recurring problems:

- They do not know what to do next.
- They forget material because review is not built into the learning flow.
- Existing platforms are either too shallow, too academic, or too overwhelming.

Nuvio should reduce decision fatigue and help users build durable competence through short practice loops.

## 3. Target Users

Primary users are adults learning alongside work, study, or personal projects.

Examples:

- A developer reviewing math and physics for machine learning.
- An electrician learning circuit theory and electronics foundations.
- A career changer learning chemistry basics for lab work.
- A hobbyist learning physics through simulations.
- A learner building interdisciplinary foundations for a technical career path.

## 4. Core Promise

Nuvio answers three daily questions for the learner:

1. What should I do next?
2. What do I need to review?
3. Am I making real progress?

The user should see at most three recommended actions per day. They should not need to browse a large dashboard or manually plan their study session.

## 4.1 Opening Nuvio Should Feel Good

Opening Nuvio should feel like a calm entry into useful learning, not like entering a control center.

The learner should never have to plan first. Today should show at most three useful options and should not start by exposing everything unfinished, missed, overdue, or waiting in the background.

After a break, Nuvio should say: "Schön, dass du wieder da bist. Wir machen hier weiter." and show a capped set of available work. It should not ask why the learner was away, show missed-day counts, or create catch-up work.

Progress should become visible through competence: practiced skills, review-ready knowledge, retained knowledge, and the next useful action. It should not depend on activity pressure, streak maintenance, or daily obligation.

Nuvio is enjoyable when the learner can start without planning, complete a small meaningful task, immediately understand what their attempt means, and notice that knowledge is becoming more stable.

The feeling to optimize for is: "Ich kann wieder anfangen" and "Ich merke, dass ich besser werde."

## 5. Product Principles

- Keep the app simple, but suitable for adult technical learning.
- Recommend at most three actions per day.
- Make reviews central to the product, not an optional extra.
- Base progress on practice, retention, and transfer, not only time spent.
- Make incorrect and unsure attempts useful by turning them into review work.
- Motivate through competence feedback, clear structure, and recovery paths, not through reward ladders or loss pressure.
- Make Nuvio playful through learning interaction, visible competence, calm state changes, and Mastery Moments.
- Do not use XP, badges, achievements, streaks, streak freezes, leaderboards, or level ladders as motivation systems.
- Keep content extensible across subjects.
- Treat simulations, projects, and AI as pluggable layers over SkillGraph.
- Keep the MVP focused on the Laravel API and core learning loop.

Didactic rationale:

- Adult learners need to know what they can do, what needs review, and what the next useful action is.
- XP, badges, streaks, and level ladders do not become neutral just because the copy is friendly. They can still shift motivation from competence to compliance, create loss aversion, and make breaks feel like failure.
- For adults managing attention, variable energy, or ADHD, pressure mechanics can turn the product into another obligation to manage. Nuvio should reduce planning load and support return after interruption.
- The product should distinguish motivating competence feedback from controlling gamification. Progress status, review due state, and completion state are allowed when they describe learning evidence. Reward currencies, rankings, collection systems, and daily obligation language are not.
- Playful behavior is allowed when it helps the learner manipulate ideas, notice competence, or understand state. It is not allowed when it creates obligation, loss, rank, series maintenance, or collection pressure.

## 6. ADHD-Friendly Learning Principles

Nuvio should reduce cognitive load.

Implementation implications:

- No overwhelming dashboards.
- No long lists of equally weighted options.
- Short tasks that can be completed in focused learning blocks.
- Clear daily recommendations.
- Immediate feedback after attempts.
- Visible progress after meaningful practice.
- Support for "unsure" as a valid attempt state.
- No shame-based streak mechanics.
- Missed days should not make the user feel punished or locked out.
- Skip, Unsure, and Snooze are legitimate recovery actions. They should create appropriate review work or defer work without punitive language.
- Review is normal learning work, not a penalty for being wrong.

## 6.1 Motivation Without Pressure

Nuvio should create motivation through product behavior that helps learners act, recover, and see competence.

Allowed motivation patterns:

- A clear next action returned by Today.
- Small tasks with a complete state.
- Mastery states such as `unknown`, `practiced`, `review_due`, and `retained`.
- Compact progress summary based on MasteryStates, attempts, and Reviews.
- Neutral completion states when there is no useful work now.
- Copy such as "Nuvio will bring this back later" after unsure, skipped, or incorrect work.
- Snooze or defer behavior that changes scheduling without implying achievement or failure.
- A compact SkillGraph map that shows competence state, not reward status.
- Mastery Moments after real learning evidence, such as a successful Review or a `review_due` to `retained` transition.
- Gentle visual state changes that orient the learner after an attempt or review.

Disallowed pressure patterns:

- XP, points, coins, rewards, or other spendable or accumulating currencies.
- Badges, achievements, trophies, collections, or celebratory reward inventory.
- Streaks, streak freezes, daily streak repair, or missed-day loss messaging.
- Leaderboards, ranks, leagues, competitive tiers, or social comparison.
- Level ladders used as reward progression rather than content structure.
- Daily pressure copy such as "catch up", "behind", "failed", "lost progress", or "save your streak".
- Confetti or reward loops after each task.
- Backlog counts presented as debt, failure, or urgency.

## 6.2 Playful Without Pressure

Nuvio may feel playful through interaction, not through extrinsic reward machines.

Allowed playful patterns:

- Skill-Map as a secondary or compact view over Algebra Foundations.
- LearningNode states: `unknown`, `practiced`, `review_due`, and `retained`.
- Visual state changes after a real TaskAttempt, Review answer, or MasteryState transition.
- "Next step available" language instead of "level unlocked".
- Short Completion States such as "Schritt abgeschlossen", "Lineare Gleichungen: geübt", "Review geplant", "Nach der Pause wieder abgerufen", and "Behalten".
- Mastery Moments when a Review is answered correctly, a LearningNode moves from `review_due` to `retained`, a formerly unsure task is later solved correctly, or a short return-after-break action is completed.
- Optional challenge choices after completion: similar task, slightly harder task, or short review.
- Interactive algebra later: equation transformations, balancing terms, marking error locations, and graph manipulation.
- Gentle animation only for orientation and state change.

Forbidden playful patterns:

- XP, badges, achievements, streaks, streak freezes, comeback streaks, leaderboards, ranks, reward levels, lootbox or slot-machine behavior, artificial scarcity, countdown pressure, or rewards for attendance.
- Stars, badge slots, level numbers, or collectible slots on Skill-Map nodes.
- Confetti after every task.
- Copy that says "catch up", "behind", "failed", "wrong again", "lost progress", or "back in the race".

Implementation implications:

- Today remains the main surface and still shows at most three actions.
- Skill-Map is secondary, compact, and navigable without becoming a dashboard.
- Review remains prioritized before new learning and should appear as "ready to review" or "reactivate", not as punishment.
- Return after a break shows a capped Review-first path and no missed-day count.

## 7. What Makes Nuvio Different

Nuvio combines a simple daily learning experience with a flexible internal graph.

The user sees:

- A few recommended actions.
- Small practice tasks.
- Review prompts.
- Clear progress.
- Later, simulations and guided project steps.

The system manages:

- Skill prerequisites.
- Learning paths.
- Career paths.
- Review scheduling.
- Task attempts.
- Progress by skill.
- Future simulation and project links.

This keeps the product simple on the surface while allowing the backend to support many learning views.

## 8. Initial Subject Focus

The MVP should start with one subject path.

Recommended first path:

- Algebra Foundations.

The model must also allow later expansion into:

- Physics.
- Electrical engineering.
- Chemistry.
- Biology.
- Computer science.
- Career paths.
- Project-based learning.
- Other interdisciplinary subjects.

Adding a new subject should primarily require adding content and graph relationships, not rewriting core progress, review, or recommendation logic.

## 9. Long-Term Vision

Nuvio should become a graph-based learning platform where users can move between foundational subjects, career goals, simulations, and projects without losing progress.

Long-term capabilities may include:

- SkillGraph-powered career paths.
- Project-based learning built from existing skills.
- Simulations linked to concepts and tasks.
- User-created practice tasks.
- Adaptive reviews across subjects.
- A constrained AI teacher that can explain, hint, and redirect within the current learning context.

AI is optional and constrained. It is not the core MVP, not the grader, and not a general-purpose chatbot.

## 10. Non-Goals

The MVP should not include:

- Payments.
- Community features.
- Native mobile apps.
- Complex admin CMS.
- Full AI tutoring.
- Open-ended AI chat.
- Advanced simulation execution.
- Certificates.
- Team or classroom management.

## 11. Success Definition

The MVP is successful when a learner can:

- Enroll in a path.
- Receive at most three daily recommended actions.
- Complete small tasks.
- Mark an attempt as unsure.
- Get reviews after incorrect or unsure attempts.
- See progress by skill and path.
- Return later and continue without planning what to do next.

The backend foundation is successful when:

- SkillGraph can represent subjects, skills, prerequisites, paths, tasks, and reviews.
- New subjects can be added without changing core learning logic.
- Review scheduling is deterministic and tested.
- Recommendations are simple, explainable, and capped at three.
- API contracts are clear enough for a future frontend to build against.
