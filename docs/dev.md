# Development

This document keeps local setup and validation steps minimal and environment-agnostic.

## Prerequisites

- Node.js and npm
- PHP with Composer dependencies installed
- A Nextcloud instance where this app is available

## Build and watch

From the project root:

```bash
npm ci
npm run watch
```

For hot-module reload, use:

```bash
npm run serve
```

## Cross-project architecture (4.0.1)

### Frontend

- `src/components/CrossProjectBalanceView.vue`
  - Aggregated balances by person and currency
  - Settlement entry point
- `src/components/CrossProjectSettlement.vue`
  - Full and partial settlement flow
  - Optional per-project metadata (timestamp, payment mode, comment)
- `src/components/CospendNavigation.vue`
  - Cumulative balance footer rows
- `src/components/CospendSettingsDialog.vue`
  - Cumulative-balance display and sorting preferences

Integration points:

- `src/App.vue`
- `src/network.js`
- `src/state.js`

### Backend

- Routes in `appinfo/routes.php`
  - `GET /apps/cospend/api/v1/cross-project-balances`
  - `POST /apps/cospend/api/v1/cross-project-settlement`
  - `GET /apps/cospend/cross-project` (page route)
  - `GET /apps/cospend/cross-project/settle/{personKey}` (page route)
- Controller endpoints in `lib/Controller/ApiController.php`
- Page routes and initial-state restoration in `lib/Controller/PageController.php`
- Core logic in `lib/Service/CospendService.php`

## URL routing for cross-project views

Cross-project views are addressable via dedicated page routes, enabling bookmarks, reloads, and browser history.

### Route scheme

| Route | Handler |
|-------|---------|
| `GET /apps/cospend/cross-project` | `PageController::indexCrossProject()` |
| `GET /apps/cospend/cross-project/settle/{personKey}` | `PageController::indexCrossProjectSettlement()` |

These routes serve the same `main` template as the regular app, but provide additional initial state via `IInitialState`:

- `restoredCrossProjectMode` — `"balances"` or `"settlement"`
- `restoredCrossProjectPersonKey` — the `personKey` for the settlement target (validated server-side against live balance data)

### personKey format

Each person in a cross-project balance has a stable `personKey` that is safe to use in URLs:

- Nextcloud user: `user={userid}`
- Anonymous member: `name={lowercased-hyphenated-name}` (e.g. `name=alice-bregenz`)

This key is computed in `CospendService::getPersonIdentifier()` and included in every `personBalances` API response entry.

### Frontend restore flow

1. `App.vue::getProjects()` calls `restoreInitialRouteState()` after loading projects.
2. If `restoredCrossProjectMode` is set, `openCrossProjectBalances()` is called with the pending `personKey`.
3. Because `CrossProjectBalanceView` is a lazy async component, the settlement target cannot be resolved until after balances load. The `personKey` is stored as `pendingSettlementPersonKey`; `CrossProjectBalanceView` emits `balances-loaded` when its API call completes, and `App.vue` resolves the pending key in `onBalancesLoaded()`.
4. `popstate` events (browser back/forward) are handled by `onPopState()`, which re-parses the current pathname and transitions state accordingly.

## API contract summary

- `GET /apps/cospend/api/v1/cross-project-balances`
  - returns currency and person-level balance aggregates
- `POST /apps/cospend/api/v1/cross-project-settlement`
  - creates one reimbursement bill per project breakdown entry
  - validates project access, amounts, and optional metadata

## Public API compatibility notes

Compared with the IHateMoney API, guest usage differs in two places:

- Project password is provided in the URL path.
- `payed_for` is provided once as a comma-separated list.

## Validation and tests

Run backend tests from the project root:

```bash
php vendor/bin/phpunit --config tests/phpunit.xml tests/php/service/CrossProjectServiceTest.php
```

Run frontend production build check:

```bash
npm run build
```

## References

- `docs/IMPLEMENTATION_DETAILS.md`
- `docs/releases/v4.0.1.md`
