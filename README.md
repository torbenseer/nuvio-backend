# Nuvio API

Nuvio is a planned Laravel API backend for an adult learning platform. The MVP focuses on a small, testable learning loop:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

This repository lives in `backend/` and is an independent Git repository. The frontend lives in a separate `frontend/` Git repository.

The backend is API-first. Frontend, mobile app, payments, community features, production AI integrations, simulations, and gamification systems are outside the current MVP slice unless a later product phase explicitly adds them.

## Current Release

V1 Integrated Learning Loop: implemented and covered by backend tests.

Implemented:

- Laravel application skeleton.
- API route registration through `routes/api.php`.
- `GET /api/status` baseline API endpoint.
- `GET /api/user` with locale and timezone defaults.
- V1 learning loop routes for Today, Start Path, Task, Attempt, Review, and Progress Summary.
- Minimal German Algebra Foundations seed data.
- Web session login/logout routes with focused B4 hardening coverage.
- In-memory SQLite test configuration through `phpunit.xml`.
- Application boot, endpoint, service, and V1 loop tests.

## Local Commands

```bash
composer install
php artisan test
php artisan serve
```

## Documentation

Start with:

- `docs/00_PRODUCT_VISION.md`
- `docs/02_MVP_SCOPE.md`
- `CONTEXT.md`
- `docs/04_DOMAIN_MODEL.md`
- `docs/05_API_SPEC.md`
- `docs/10_IMPLEMENTATION_PLAN.md`
- `docs/11_TEST_PLAN.md`
- `docs/13_POST_MVP_FRONTEND_PROMPT.md`
- `docs/14_RELEASE_ROADMAP.md`

Implementation should follow the documented ticket order and keep each change small enough to test directly. Product release names, audiences, and gates are defined in `docs/14_RELEASE_ROADMAP.md`.

`docs/05_API_SPEC.md` is the canonical source for MVP route names and response shapes. If another document disagrees, align that document to the API spec rather than inventing a third contract.

## Assumptions

- The next backend work starts from B4 API hardening in `docs/14_RELEASE_ROADMAP.md`.
- Laravel's default SQLite setup is sufficient for local verification.
- Full Sanctum package/configuration hardening, CORS credentials, preferences, node APIs, review due/snooze, path progress, validation matrix, and ownership matrix remain B4 work.
