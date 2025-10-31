# Development environment

> ⚠️ **Vue 2 Maintenance Mode**
>
> This branch (`cross-project-balances-and-settlement-legacy`) represents the **final version** of Cospend for Vue 2.7 (v3.0.13-final).
> No further development or changes are planned for this version.
>
> If you wish to contribute or add features, please work with the Vue 3-based version of Cospend instead.

## Setting Up a Development Environment

Clone this repository and build:

``` bash
cd /var/www/.../nextcloud/apps
git clone https://github.com/julien-nc/cospend-nc cospend
cd cospend
npm ci
npm run watch
```

Or if you want to use HMR (hot module replacement),
install the [Nextcloud HMR Enabler app](https://github.com/nextcloud/hmr_enabler)
and run this in cospend directory:
``` bash
npm run serve
```

# Architecture Overview

## Cross-Project Settlement Feature

The cross-project settlement feature allows users to settle balances with other members across multiple projects simultaneously. This consists of several components:

### Frontend Components

- **CrossProjectBalanceView.vue**: Displays cumulative balances across all non-archived projects, grouped by currency. Shows per-person balances and settlement buttons.
- **CrossProjectSettlement.vue**: The main settlement creation dialog. Handles:
  - Currency and settlement type selection
  - Per-project optional fields (date/time, payment mode, comment)
  - Project breakdown preview and confirmation
  - API communication for settlement creation

- **CospendNavigation.vue**: Enhanced sidebar navigation showing cumulative balance across all projects with multi-currency support and quick settlement access.

### Backend API

**Endpoint:** `POST /ocs/v2.php/apps/cospend/api/v1/cross-project-settlement`

**Parameters:**
- `targetUserId` (string): ID of the user to settle with
- `targetUserName` (string): Display name of the user to settle with
- `currency` (string): Currency code to settle in
- `totalAmount` (float): Total amount being settled
- `isPayment` (boolean): Whether the current user is paying (true) or receiving (false)
- `projectBreakdown` (array): Array of project settlement objects:
  - `projectId` (string): Project ID
  - `billAmount` (float): Amount to settle in this project
  - `timestamp` (int, optional): Unix timestamp for the settlement bill date/time
  - `paymentModeId` (int, optional): Payment mode ID (e.g., Cash, Card)
  - `comment` (string, optional): Settlement comment (max 300 chars)

**Validation Rules:**
- Comment must not exceed 300 characters
- Payment mode ID must be valid for the project
- Current user must have access to all projects involved
- All users must be members of the projects being settled
- Timestamp must be numeric (Unix timestamp)

**Response:**
- Success: Empty response with HTTP 200
- Error: JSON error object with message and HTTP error code

### Data Flow

1. User navigates to Cumulative Balance view and clicks "Settle" on another member
2. CrossProjectSettlement component calculates project breakdown based on balances
3. User configures optional fields per project
4. On confirmation, frontend sends API request with all project breakdowns
5. Backend validates all data and member access
6. For each project, backend creates a reimbursement bill using LocalProjectService::createBill()
7. Bills are created with optional fields (timestamp, payment mode, comment) if provided
8. Frontend displays success message and refreshes balance view

### Component Integration

- Settlement is triggered from CrossProjectBalanceView.vue
- CrossProjectSettlement component is conditionally rendered in App.vue when mode is 'cross-project-balances'
- CospendNavigation.vue provides quick access to Cumulative Balance view
- All components use global `cospend` state for project and user data

### Important Implementation Details

**Member Lookup:** When creating bills, the system must find each member in the target project. It first tries to match by `userid` (Nextcloud users), then falls back to matching by name for local project members.

**Currency Handling:** The frontend aggregates balances by currency and allows settlement in any currency with balances. The backend creates bills in the specified currency.

**Per-Project Optional Fields:** Each project can have different optional field configurations. The frontend stores these per-project and includes them in the API request payload.

## Complete Implementation Details

For comprehensive documentation of all changes made to implement this feature, including:
- Detailed component architecture
- All bug fixes and console error resolutions
- UI/UX improvements and standardization
- Testing scenarios and validation procedures
- File modification summary

See `docs/IMPLEMENTATION_DETAILS.md`

## User Preferences and Settings

### Hide Own Balance Feature

**Purpose:** Allow users to hide their own balance display in project member lists, reducing redundancy in 2-person projects.

**Implementation:**

1. **State Management** (`src/state.js`)
   - Added `hideOwnBalance: false` property to global cospend state
   - Persists user preference across sessions via Nextcloud settings API

2. **Settings UI** (`src/components/CospendSettingsDialog.vue`)
   - Toggle added to "Misc" section: "Hide my balance"
   - Data binding: `hideOwnBalance` property with `onCheckboxChange` handler
   - Watch handler: Syncs UI with cospend state changes via `'cospend.hideOwnBalance'`
   - Dialog refresh: `handleShowSettings()` refreshes value when dialog opens

3. **Balance Display** (`src/components/AppNavigationMemberItem.vue`)
   - Computed property `shouldHideBalance()`:
     - Returns `false` if setting is disabled (show all balances)
     - Returns `true` if setting is enabled AND member is current user
     - Checks `member.userid === getCurrentUser().uid` for Nextcloud users
   - Template condition: `v-if="inNavigation && !shouldHideBalance"` on balance counter

**Data Flow:**

1. User toggles "Hide my balance" in settings
2. `onCheckboxChange()` emits `save-option` event with key/value
3. Backend persists setting to user preferences
4. `cospend.hideOwnBalance` state updated
5. Watch handler syncs component local state
6. Vue reactivity updates balance display instantly

**State Persistence:**

- Setting saved to Nextcloud user preferences (via `save-option` event)
- Loaded from server when app initializes
- Survives page reload and Nextcloud sessions
- Per-user setting (not per-project)

**Technical Notes:**

- Only hides balances for Nextcloud users (those with `userid`)
- Local project members (no `userid`) continue to display balances
- Watch handler ensures UI stays in sync with state changes
- `handleShowSettings()` refresh ensures dialog shows correct value when reopened

# Public API

Plan was to make Cospend public API strictly identical to [IHateMoney API](https://ihatemoney.readthedocs.io/en/latest/api.html) but there is a restriction i couldn't bypass : the authentication system. IHateMoney uses the basic HTTP authentication, just like Nextcloud user authentication. So, to get a guest access to a Cospend project, this type of authentication was first rejected by Nextcloud user auth system and then accepted by Cospend with a huge latency.

So the only differences between IHateMoney API and Cospend API are :

* The password has to be included in the URL path, just after the project ID, like that : `https://mynextcloud.org/index.php/apps/cospend/api/myproject/projectPassword/bills`
* The parameter `payed_for` cannot be given multiple times like in IHateMoney. It has to be given once with coma separated values.

That's it.

Detailed API description will come later.
