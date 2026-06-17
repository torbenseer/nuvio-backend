# V1 Frontend Planning Prompt

Use this prompt after the V1 API subset exists in backend routes and tests.

```text
You are working in the Nuvio workspace. There are two independent Git repositories:

- backend/: Laravel API backend. The V1 API subset is implemented in routes and tests. Full B4 API hardening may still be incomplete.
- frontend/: frontend repository. No frontend application has been implemented yet.

Goal: create a frontend implementation plan for Nuvio after the V1 API subset.

First, inspect the backend repository docs and current API behavior:

- backend/docs/00_PRODUCT_VISION.md
- backend/docs/01_PRD.md
- backend/docs/02_MVP_SCOPE.md
- backend/docs/03_UX_FLOWS.md
- backend/docs/05_API_SPEC.md
- backend/docs/11_TEST_PLAN.md
- backend/routes/api.php
- backend/tests/

Do not start coding yet. Produce a frontend plan that answers:

1. Which frontend stack should be used and why?
2. What is the smallest V1 frontend slice?
3. Which screens are required for the first usable version?
4. Which backend endpoints does each screen consume?
5. What authentication/session approach should the frontend use with the Laravel API?
6. What states, loading states, error states, and empty states are required?
7. What should explicitly remain out of scope for the first frontend slice?
8. What tests or browser checks should be required?
9. What small implementation tickets should be created for the frontend repo?

Respect the product constraints:

- Keep the Today screen focused on at most three actions.
- Avoid overwhelming dashboards.
- Support red/yellow/green energy modes.
- Treat incorrect, unsure, and skipped attempts as review input, not shame.
- Do not add streaks, streak freezes, XP, badges, achievements, leaderboards, reward levels, gamified catch-up flows, simulations, AI teacher features, payments, community features, or mobile app work unless a new explicit scope says so.
- Motivation must come from clear next actions, small completed tasks, competence status, Review as normal learning work, and recovery paths such as Unsure, Skip, and Snooze.
- Do not use copy such as "catch up", "behind", "failed", "lost progress", or "save your streak".

End with a recommended first frontend ticket that can be implemented independently.
```
