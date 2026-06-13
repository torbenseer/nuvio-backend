# Nuvio API

Nuvio is a planned Laravel API backend for an adult learning platform. The MVP focuses on a small, testable learning loop:

**Today -> Task -> Attempt -> Feedback -> Review -> Progress**

This repository lives in `backend/` and is an independent Git repository. The frontend lives in a separate `frontend/` Git repository.

The backend is API-first. Frontend, mobile app, payments, community features, production AI integrations, simulations, and gamification systems are outside the current MVP slice unless a later phase explicitly adds them.

## Current Phase

Phase 0: Laravel API foundation.

Implemented baseline:

- Laravel application skeleton.
- API route registration through `routes/api.php`.
- `GET /api/status` baseline API endpoint.
- In-memory SQLite test configuration through `phpunit.xml`.
- Application boot tests.

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
- `docs/04_DOMAIN_MODEL.md`
- `docs/05_API_SPEC.md`
- `docs/10_IMPLEMENTATION_PLAN.md`
- `docs/11_TEST_PLAN.md`
- `docs/13_POST_MVP_FRONTEND_PROMPT.md`

Implementation should follow the documented phase order and keep each change small enough to test directly.

## Assumptions

- The MVP begins with the earliest incomplete ticket from `docs/10_IMPLEMENTATION_PLAN.md`.
- Laravel's default SQLite setup is sufficient for local Phase 0 verification.
- Authentication is documented for the MVP, but Sanctum and auth endpoints are not part of Phase 0 unless explicitly selected in a later ticket.
