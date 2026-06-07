# Cospend 4.0.1 implementation details

## Scope

Release `4.0.1` introduces cross-project balances and cross-project settlement, with related UI settings and validation hardening.

## Architecture summary

### Frontend

- `src/App.vue`
  - hosts cross-project balances mode and settlement flow
- `src/components/CrossProjectBalanceView.vue`
  - renders aggregated person/currency balances
- `src/components/CrossProjectSettlement.vue`
  - creates full or partial settlement payloads
- `src/components/CospendNavigation.vue`
  - displays cumulative footer totals
- `src/components/CospendSettingsDialog.vue`
  - stores cumulative-balance and sorting preferences

### Backend

- Routes (`appinfo/routes.php`)
  - `GET /apps/cospend/api/v1/cross-project-balances`
  - `POST /apps/cospend/api/v1/cross-project-settlement`
- Controller (`lib/Controller/ApiController.php`)
  - `getCrossProjectBalances()`
  - `createCrossProjectSettlement(...)`
- Service (`lib/Service/CospendService.php`)
  - aggregates balances across projects
  - validates and creates settlement reimbursement bills

## Runtime flow

### Cross-project balances

1. Frontend calls `network.getCrossProjectBalances()`.
2. API controller delegates to `CospendService`.
3. Service filters archived projects and deactivated members.
4. Service aggregates by person and currency and returns summary + details.

### Cross-project settlement

1. User starts settlement from cumulative balances view.
2. Frontend builds payload and posts to settlement endpoint.
3. Service validates request and access.
4. Service creates one reimbursement bill per project entry.

## URL routing and restore flow

Cross-project balances and settlement now have dedicated page routes so they can be bookmarked, reloaded, and restored with browser back/forward:

- `/apps/cospend/cross-project`
- `/apps/cospend/cross-project/settle/{personKey}`

The `personKey` is a stable cross-project identity, not a project-local member id:

- Nextcloud user: `user={userid}`
- Anonymous member: `name={lowercased-hyphenated-name}`

The backend validates the settlement key on page load and exposes the restored state through `IInitialState`. `App.vue` parses the current pathname, restores the requested cross-project view after projects load, and handles `popstate` transitions. Because the balances view is loaded asynchronously, settlement restoration waits for the balances component to finish loading before resolving the target person.

## Server-side validation rules

- `projectBreakdown` must not be empty.
- `totalAmount` must be positive.
- Caller must have access to each project in the breakdown.
- Very small project amounts are ignored (`< 0.01`).
- Optional comments are limited to 300 characters.

## Settlement payload shape

```json
{
  "targetUserId": "target-user-id",
  "targetUserName": "Target User",
  "currency": "EUR",
  "totalAmount": 16.9,
  "isPayment": true,
  "projectBreakdown": [
    {
      "projectId": "project-a",
      "billAmount": 12.34,
      "timestamp": 1767225600,
      "paymentModeId": 2,
      "comment": "optional note"
    }
  ]
}
```

## User settings used

- `showMyBalance`
- `hideOwnBalance`
- `showSummaryFirst`
- `hideProjectsByDefault`
- `personSortBy`
- `personSortOrder`
- `summarySortBy`
- `summarySortOrder`

## Verification commands

```bash
php vendor/bin/phpunit --config tests/phpunit.xml tests/php/service/CrossProjectServiceTest.php
npm run build
```
