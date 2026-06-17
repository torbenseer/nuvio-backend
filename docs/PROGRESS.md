# Nuvio Backend Progress

## 2026-06-17 - V1 Auth/User Foundation

Status: completed.

Changed:

- Added focused coverage for `GET /api/user` returning the authenticated learner profile with German locale and Europe/Berlin timezone defaults.
- Covered unauthenticated rejection for the V1 user endpoint.
- Prepared the first V1 ticket boundary: auth or pre-provisioned learner support before Today and task loop work.

Commit:

- `169e88e feat: add v1 user endpoint`

Checks:

- `php artisan test --filter=UserEndpointTest` passed: 2 tests, 3 assertions.

Open risks:

- The workspace already contains broader uncommitted V1 learning-loop implementation work. It must be split into coherent follow-up commits without staging unrelated local changes.
- Full Sanctum/browser login hardening remains B4 unless V1 chooses real login instead of pre-provisioned learner support.

Next:

- Split the existing German Algebra seed and minimal enrollment/Today work into coherent follow-up commits from `docs/00_START_HERE.md`.
