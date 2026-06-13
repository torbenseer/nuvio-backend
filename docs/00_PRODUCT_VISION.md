# Nuvio Product Vision

## 1. Product Summary

Nuvio is a Duolingo-like learning platform for adults. It helps users learn interdisciplinary knowledge through small tasks, clear progress, spaced reviews, simulations, and later a constrained AI teacher.

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

## 5. Product Principles

- Keep the app simple like Duolingo, but suitable for adult technical learning.
- Recommend at most three actions per day.
- Make reviews central to the product, not an optional extra.
- Base progress on practice, retention, and transfer, not only time spent.
- Make failed and unsure attempts useful by turning them into review work.
- Keep content extensible across subjects.
- Treat simulations, projects, gamification, and AI as pluggable layers over SkillGraph.
- Keep the MVP focused on the Laravel API and core learning loop.

## 6. ADHD-Friendly Learning Principles

Nuvio should reduce cognitive load.

Implementation implications:

- No overwhelming dashboards.
- No long lists of equally weighted options.
- Short tasks that can be completed in focused sessions.
- Clear daily recommendations.
- Immediate feedback after attempts.
- Visible progress after meaningful practice.
- Support for "unsure" as a valid attempt state.
- No shame-based streak mechanics.
- Missed days should not make the user feel punished or locked out.

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
- Get reviews after failed or unsure attempts.
- See progress by skill and path.
- Return later and continue without planning what to do next.

The backend foundation is successful when:

- SkillGraph can represent subjects, skills, prerequisites, paths, tasks, and reviews.
- New subjects can be added without changing core learning logic.
- Review scheduling is deterministic and tested.
- Recommendations are simple, explainable, and capped at three.
- API contracts are clear enough for a future frontend to build against.
