# Cross-Project Settlement Feature - Complete Implementation Details

> ⚠️ **Vue 2 Final Version (3.0.13-final)**
>
> This document describes features implemented in the final Vue 2 version of Cospend.
> These features are planned to be ported to the Vue 3-based version in the future. There is neither an ETA or a timeline.
> Help / contributions are welcome, of course.
> This branch will not receive further updates.

This document provides a comprehensive technical overview of all changes made to implement the cross-project settlement feature and related improvements since commit `c9603f1a585d0cd4b6b1c99926db4e3df81ba664`.

## Table of Contents

1. [Feature Overview](#feature-overview)
2. [Frontend Components](#frontend-components)
3. [Backend Implementation](#backend-implementation)
4. [Bug Fixes](#bug-fixes)
5. [Console Error Fixes](#console-error-fixes)
6. [UI/UX Improvements](#uiux-improvements)
7. [User Preferences - Hide Own Balance](#user-preferences---hide-own-balance)
8. [Testing and Validation](#testing-and-validation)
9. [File Modifications Summary](#file-modifications-summary)

---

## Feature Overview

### Cross-Project Settlement

The cross-project settlement feature enables users to settle outstanding balances with other members across multiple projects in a single transaction. Key aspects:

- **Multi-Project Scope:** Settles balances in multiple projects simultaneously
- **Per-Project Configuration:** Optional fields (date/time, payment mode, comment) can be configured per project
- **Multi-Currency Support:** Displays and aggregates balances across all currency types
- **Flexible Settlement:** Users can settle full balances or custom amounts
- **Cumulative Balance Display:** Always-visible summary of money owed/owed to current user

### Settlement Types

1. **Full Settlement:** Settles entire outstanding balance in a selected currency
2. **Partial Settlement:** User specifies custom amounts per project, which are then validated

### Optional Fields per Project

Each project can have optional settlement fields:
- **Settlement Date/Time:** Specify when the bill occurred
- **Payment Mode:** Select payment method (Cash, Card, Bank Transfer, etc.)
- **Comment:** Add notes about the settlement

---

## Frontend Components

### 1. CrossProjectSettlement.vue

**Location:** `src/components/CrossProjectSettlement.vue`

**Purpose:** Main settlement creation dialog component

**Key Features:**

- Multi-step settlement workflow
- Full and partial settlement modes
- Per-project optional field configuration
- Real-time calculation of remaining debt
- Confirmation dialog with detailed breakdown
- Visual feedback for currency and amount

**Key Methods:**

```javascript
performSettlement()
  - Initiates settlement workflow
  - Calculates project breakdown
  - Shows confirmation dialog

executeSettlement()
  - Validates settlement data
  - Extracts optional fields for each project
  - Makes API call to createCrossProjectSettlement endpoint
  - Handles success/error responses
  - Refreshes balance data

getProjectOptionalField(projectId, fieldName)
  - Retrieves configured optional field value for a specific project
  - Used by UI to populate form fields

setProjectOptionalField(projectId, fieldName, value)
  - Stores optional field value per project
  - Triggered when user modifies field in form
  - Persists in component state

validatePartialAmount()
  - Validates user-entered custom amount
  - Ensures amount is positive and reasonable
  - Updates form validation state

formatDateTime(timestamp)
  - Converts Unix timestamp to readable format
  - Used in confirmation dialog and summary display

memoizedFormatCurrencyWithDirection()
  - Formats currency amounts with payment/receipt indicators
  - Memoized for performance optimization
```

**Data Structure:**

```javascript
projectOptionalFields = {
  [projectId]: {
    datetime: timestamp,      // Unix timestamp or null
    paymentMode: { id, label }, // Selected payment mode or null
    comment: string           // Comment text or empty string
  }
}

confirmationBreakdown = [
  {
    id: projectId,
    name: projectName,
    currency: currencyCode,
    billAmount: amount,
    datetime: timestamp,      // Includes optional fields
    paymentMode: { id, label },
    comment: string
  }
]
```

**State Tracked:**

- `currentSettlementPerson` - Selected member to settle with
- `selectedCurrencyCode` - Currency for settlement
- `settlementTypeSelected` - Full vs Partial settlement
- `isPartialSettlement` - Boolean flag
- `partialAmount` - User-entered partial amount
- `totalCustomAmount` - Sum of custom per-project amounts
- `partialSettlementConfirmed` - Confirmation state
- `showConfirmationDialog` - Dialog visibility
- `configurationCollapsed` - UI state for collapsed config
- `projectOptionalFields` - Per-project settings store

**UI Sections:**

1. **Header:** Title, close button, settlement type indicator
2. **Currency Selection:** Dropdown of available currencies with balances
3. **Settlement Type Selection:** Radio buttons for full/partial
4. **Project Breakdown:**
   - For each project: amount to settle
   - Optional fields (date/time picker, payment mode selector, comment textarea)
5. **Partial Settlement Validation:** Shows validation errors for custom amounts
6. **Configuration Summary:** Collapsed view showing settlement details
7. **Confirmation Dialog:** Final preview before API call

**CSS Features:**

- Responsive design (mobile-optimized with back button)
- Flex-based layout with proper spacing
- Currency chip styling with tabular-nums for alignment
- Optional field styling with icons
- Deep selectors for Nextcloud component customization

### 2. CrossProjectBalanceView.vue

**Location:** `src/components/CrossProjectBalanceView.vue`

**Purpose:** Displays aggregated balances across all projects

**Key Features:**

- Grouping by currency and person
- Per-person settlement buttons
- Real-time balance updates
- Responsive card-based layout
- Integration with settlement component

**Key Methods:**

```javascript
aggregateBalances()
  - Combines balances from all projects
  - Groups by person and currency
  - Returns structured balance data

openSettlementDialog(person, currency)
  - Triggers CrossProjectSettlement component
  - Passes selected person and currency

formatBalanceAmount(amount, currency)
  - Formats amount with currency information
  - Uses tabular-nums for consistent alignment
```

**Data Structure:**

```javascript
peopleBalances = [
  {
    member: { id, name, color, avatar },
    balancesByCurrency: {
      USD: { balance: amount, projects: [...] },
      EUR: { balance: amount, projects: [...] }
    }
  }
]
```

**Layout:**

- Header: Title and navigation
- Card grid: One card per person
- Card content:
  - Member name and avatar
  - Currency chips showing balances
  - "Settle" button for positive balances
- Empty state: Message when no balances
- Loading state: Spinner during data fetch

### 3. CospendNavigation.vue

**Location:** `src/components/CospendNavigation.vue`

**Purpose:** Enhanced sidebar navigation with cumulative balance

**Key Features:**

- Multi-currency cumulative balance display
- Quick access to Cumulative Balance view
- Compact, always-visible balance summary
- Currency chip display with icons

**Key Methods:**

```javascript
myBalanceByCurrency()
  - Computed property aggregating balances across all projects
  - Groups by currency code
  - Returns map of currency -> balance

formatBalanceAmount(amount, code)
  - Formats amount with currency code
  - Applied to each currency chip
```

**Data Structure:**

```javascript
myBalanceByCurrency = {
  USD: 150.00,
  EUR: -75.50,
  GBP: 0.00
}
```

**Navigation Items:**

- Existing project navigation (unchanged)
- New "Cumulative Balance" item (if enabled via settings)
- Shows currency chips with balances inline
- Color-coded (green for owed-to-you, red for owed-by-you)

---

## Backend Implementation

### 1. API Controller (`lib/Controller/ApiController.php`)

**Endpoint:** `POST /ocs/v2.php/apps/cospend/api/v1/cross-project-settlement`

**Method Signature:**

```php
public function createCrossProjectSettlement(
    string $targetUserId,
    string $targetUserName,
    string $currency,
    float $totalAmount,
    bool $isPayment,
    array $projectBreakdown
): DataResponse
```

**Parameters:**

- `targetUserId` (string): Nextcloud user ID of settlement recipient/payer
- `targetUserName` (string): Display name for fallback member matching
- `currency` (string): Currency code (USD, EUR, etc.)
- `totalAmount` (float): Total amount being settled
- `isPayment` (boolean): `true` if current user pays, `false` if current user receives
- `projectBreakdown` (array): Array of per-project settlement objects:
  ```php
  [
    {
      'projectId': string,
      'billAmount': float,
      'timestamp': int (optional),
      'paymentModeId': int (optional),
      'comment': string (optional)
    }
  ]
  ```

**Response:**

- **Success (HTTP 200):** Empty response body
- **Error (HTTP 400):** JSON object with `message` field explaining error

**Error Handling:**

```php
try {
    // Validates and creates settlement
    $this->cospendService->createCrossProjectSettlement(...);
    return new DataResponse('');
} catch (CospendBasicException $e) {
    return new DataResponse($e->data, Http::STATUS_BAD_REQUEST);
} catch (\Exception $e) {
    return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
}
```

### 2. Cospend Service (`lib/Service/CospendService.php`)

**Method:** `createCrossProjectSettlement()`

**Implementation Details:**

#### Access Validation
```php
// Validates current user has access to all projects in breakdown
$userProjects = $this->localProjectService->getLocalProjects($currentUserId);
$userProjectIds = array_column($userProjects, 'id');

foreach ($projectBreakdown as $projectInfo) {
    $projectId = $projectInfo['projectId'];
    if (!in_array($projectId, $userProjectIds)) {
        throw new CospendBasicException(
            '', 400, 
            ['message' => "Access denied to project {$projectId}"]
        );
    }
}
```

#### Member Lookup Strategy
```php
// For each project, find member objects for both users
$members = $this->localProjectService->getMembers($projectId);

foreach ($members as $member) {
    // Match current user by userid
    if ($member['userid'] === $currentUserId) {
        $currentUserMember = $member;
    }
    
    // Match target user: first by userid, then by name
    if ($member['userid'] === $targetUserId || 
        ($member['userid'] === null && $member['name'] === $targetUserName)) {
        $targetUserMember = $member;
    }
}
```

This dual-matching approach handles:
- **Nextcloud Users:** Matched by `userid` field
- **Local Members:** Matched by name when `userid` is null

#### Bill Direction
```php
if ($isPayment) {
    // Current user is paying target user
    $payerId = $currentUserMember['id'];
    $owerId = $targetUserMember['id'];
} else {
    // Target user is paying current user
    $payerId = $targetUserMember['id'];
    $owerId = $currentUserMember['id'];
}
```

#### Optional Field Extraction
```php
$billTimestamp = isset($projectInfo['timestamp']) 
    ? $projectInfo['timestamp'] 
    : $timestamp; // Current time as default

$paymentModeId = isset($projectInfo['paymentModeId']) 
    ? $projectInfo['paymentModeId'] 
    : null;

$comment = isset($projectInfo['comment']) 
    ? $projectInfo['comment'] 
    : null;
```

#### Field Validation
```php
// Timestamp must be numeric (Unix timestamp)
if ($billTimestamp !== null && !is_numeric($billTimestamp)) {
    throw new CospendBasicException(
        '', 400,
        ['message' => "Invalid timestamp for project {$projectId}"]
    );
}

// Comment length limit
if ($comment !== null && strlen($comment) > 300) {
    throw new CospendBasicException(
        '', 400,
        ['message' => "Comment too long for project {$projectId} (max 300 characters)"]
    );
}

// Payment mode ID must be numeric
if ($paymentModeId !== null && !is_numeric($paymentModeId)) {
    throw new CospendBasicException(
        '', 400,
        ['message' => "Invalid payment mode ID for project {$projectId}"]
    );
}
```

#### Bill Creation
```php
$billId = $this->localProjectService->createBill(
    $projectId,
    null,                                    // date (null, use timestamp)
    $billTitle,                              // what (settlement title)
    $payerId,                                // payer member ID
    (string)$owerId,                         // payedFor (ower as string)
    $billAmount,                             // amount
    Application::FREQUENCY_NO,               // repeat frequency
    null,                                    // paymentMode (deprecated)
    $paymentModeId,                          // paymentModeId
    Application::CATEGORY_REIMBURSEMENT,    // categoryId
    0,                                       // repeatAllActive
    null,                                    // repeatUntil
    $billTimestamp,                          // timestamp (optional field)
    $comment,                                // comment (optional field)
    null,                                    // repeatFreq
    0,                                       // deleted
    true                                     // produceActivity
);
```

**Key Points:**

- Uses existing `LocalProjectService::createBill()` for bill creation
- All bills are created as "reimbursement" type (`CATEGORY_REIMBURSEMENT`)
- Activity is produced for each bill (visible in project timeline)
- Bills include optional fields when provided
- Type-safe string conversion: `(string)$owerId` ensures proper bill creation

---

## Bug Fixes

### 1. Member Matching for Local Members

**Issue:** Settlement bills were not being created silently when target user was a local project member without a Nextcloud user ID.

**Root Cause:** Member lookup was only checking `userid` field, which is null for local members.

**Solution:** Implemented fallback matching logic:
```php
if ($member['userid'] === $targetUserId || 
    ($member['userid'] === null && $member['name'] === $targetUserName)) {
    $targetUserMember = $member;
}
```

**Files Modified:** `lib/Service/CospendService.php`

### 2. Type Error with targetUserId

**Issue:** Type error when creating bills due to integer/string mismatch.

**Root Cause:** Frontend was passing numeric user IDs, but backend expected strings.

**Solution:** Added type conversion in bill creation call:
```php
(string)$owerId  // Explicit string conversion
```

**Files Modified:** `lib/Service/CospendService.php` line 1475

### 3. Currency Alignment Issues

**Issue:** Currency amounts were misaligned due to variable digit widths in different amounts.

**Root Cause:** Default font rendering uses proportional spacing.

**Solution:** Applied `font-variant-numeric: tabular-nums` CSS property for consistent digit width:
```scss
.currency-amount {
    font-variant-numeric: tabular-nums;
    font-size: 14px;
    font-weight: 600;
}
```

**Files Modified:**
- `src/components/CospendNavigation.vue`
- `src/components/CrossProjectSettlement.vue`
- `src/components/CrossProjectBalanceView.vue`

### 4. Balance Display Layout Wrapping

**Issue:** Balance amounts and currency chips were wrapping onto new lines.

**Root Cause:** Flex layout without proper constraints.

**Solution:** Applied flex constraints and nowrap:
```scss
.balance-display {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-shrink: 0;
}
```

**Files Modified:**
- `src/components/CrossProjectBalanceView.vue`
- `src/components/CospendNavigation.vue`

### 5. Navigation Centering Issues

**Issue:** Currency chips and balances were not vertically centered in navigation.

**Root Cause:** Nextcloud component structure required targeting internal elements.

**Solution:** Used deep selectors to target internal layout:
```scss
:deep(.nc-app-navigation-item__content) {
    align-items: center;
    gap: 12px;
}

:deep(.nc-app-navigation-item__icon) {
    flex-shrink: 0;
}
```

**Files Modified:** `src/components/CospendNavigation.vue`

---

## Console Error Fixes

### 1. Leftover Vue Event Handler

**Issue:** Console error: "Unknown custom element or component name: confirm-settlement"

**Root Cause:** Obsolete event handler `@confirm-settlement="onConfirmSettlement"` in App.vue referencing non-existent component.

**Solution:** Removed orphaned event listener:
```vue
<!-- REMOVED: @confirm-settlement="onConfirmSettlement" -->
```

**Files Modified:** `src/App.vue`

**Impact:** Eliminated Vue warning about unrecognized custom elements.

### 2. Undefined Property Errors in Sidebar.vue

**Issue:** Console errors when viewing Cumulative Balance due to undefined `project` property.

**Root Cause:** Sidebar components accessed project properties without null checks when in Cumulative Balance view (no active project).

**Solution:** Added defensive checks:
```javascript
// Computed property
project() {
    return this.$store.state.cospend.projects[this.$store.state.cospend.currentProjectId] || {};
}

// Method
onRenameClick() {
    if (!this.project || !this.project.id) {
        return;
    }
    // ... rest of logic
}
```

**Files Modified:** `src/components/Sidebar.vue`

**Impact:** Eliminated 3-4 related console errors.

### 3. Null Reference Errors in CurrencyManagement.vue

**Issue:** Console errors when component tries to access properties of undefined project.

**Root Cause:** Similar to Sidebar - no null safety for Cumulative Balance view.

**Solution:** Added null-safety checks throughout:
```javascript
get currency() {
    if (!this.project || !this.currentProjectId) {
        return {};
    }
    return this.$store.state.cospend.currencies[this.project.currency_id] || {};
}
```

**Files Modified:** `src/components/CurrencyManagement.vue`

**Impact:** Eliminated currency-related console errors.

### 4. Undefined Shares in SharingTabSidebar.vue

**Issue:** Console error accessing `this.shares` when not defined.

**Root Cause:** No null safety for computed property.

**Solution:** Added defensive fallback:
```javascript
shares() {
    return this.$store.state.cospend.projects[this.$store.state.cospend.currentProjectId]?.shares || [];
}
```

**Files Modified:** `src/components/SharingTabSidebar.vue`

**Impact:** Eliminated undefined reference error.

### 5. Undefined Members in SettingsTabSidebar.vue

**Issue:** Console error when accessing undefined members array.

**Root Cause:** Similar null safety issue.

**Solution:** Added optional chaining and fallback:
```javascript
members() {
    const project = this.$store.state.cospend.projects[this.$store.state.cospend.currentProjectId];
    return project?.members || [];
}
```

**Files Modified:** `src/components/SettingsTabSidebar.vue`

**Impact:** Eliminated members-related console error.

### 6. Optional Chaining in CategoryOrPmManagement.vue

**Issue:** Multiple console warnings about undefined properties.

**Root Cause:** Accessing nested properties without checking intermediate values.

**Solution:** Applied optional chaining:
```javascript
const sortOrderValue = this.project?.settings?.sort_order ?? '';
const elements = this.project?.categories || [];
```

**Files Modified:** `src/components/CategoryOrPmManagement.vue`

**Impact:** Eliminated property access warnings.

### 7. NcSelect Accessibility Warnings

**Issue:** Vue warnings about missing labels for form inputs.

**Root Cause:** NcSelect components without `labelOutside="true"` attribute.

**Solution:** Added accessibility attribute:
```vue
<NcSelect
    :id="`settlement-paymentmode-${project.id}`"
    :value="getProjectOptionalField(project.id, 'paymentMode')"
    labelOutside="true"
    @input="setProjectOptionalField(project.id, 'paymentMode', $event)" />
```

**Files Modified:** `src/components/CrossProjectSettlement.vue`

**Impact:** Fixed accessibility warnings and improved form semantics.

---

## UI/UX Improvements

### 1. Font Standardization

**Changes Applied:**
- Consistent font size: 14px for amounts
- Consistent font weight: 600 (semi-bold) for amounts
- Font variant: `tabular-nums` for numeric alignment

**Files Modified:**
- `src/components/CospendNavigation.vue`
- `src/components/CrossProjectSettlement.vue`
- `src/components/CrossProjectBalanceView.vue`

**Impact:** Professional, consistent appearance with proper numeric alignment.

### 2. Currency Chip Styling

**Changes:**
- Fixed width: 32px per chip
- Centered text alignment
- Dark background with light text
- Proper gap spacing between chips

**CSS:**
```scss
.currency-chip {
    width: 32px;
    text-align: center;
    background-color: var(--color-background-dark);
    color: var(--color-text);
    padding: 4px 2px;
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}
```

**Impact:** Clean, professional currency display with consistent formatting.

### 3. Responsive Mobile Layout

**Changes:**
- Mobile-optimized settlement dialog
- Back button on mobile, close button on desktop
- Touch-friendly input sizing
- Responsive grid for balance view

**Implementation:**
```scss
@media (max-width: 768px) {
    .desktop-only-close { display: none; }
    .settlement-content {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
    }
}
```

**Files Modified:** `src/components/CrossProjectSettlement.vue`

**Impact:** Seamless experience on mobile and tablet devices.

### 4. Settlement Confirmation Dialog

**Changes:**
- Detailed breakdown showing all projects and amounts
- Optional field values displayed in confirmation
- Visual indicators for payment vs. receipt direction
- Summary of total amount and currency

**Layout:**
```
┌─ Settlement Confirmation ─────────────┐
│ Total: $150.00                        │
│                                       │
│ Project: ABC                          │
│   Amount: $100.00                     │
│   When: 2025-10-31 14:30              │
│   Mode: Credit Card                   │
│   Notes: "Travel expenses"            │
│                                       │
│ Project: XYZ                          │
│   Amount: $50.00                      │
│   When: [not specified]               │
│                                       │
│ [Confirm Settlement] [Cancel]         │
└───────────────────────────────────────┘
```

### 5. Optional Field Display

**Changes:**
- Icons for visual identification
- Inline display in confirmation
- Icons: Calendar (date), Tag (payment mode), Text (comment)
- Proper spacing and alignment

**Implementation:**
```vue
<div v-if="project.datetime || project.paymentMode || project.comment" 
     class="project-optional-fields">
    <div v-if="project.datetime" class="optional-field-display">
        <CalendarIcon :size="16" />
        <span>{{ formatDateTime(project.datetime) }}</span>
    </div>
    <div v-if="project.paymentMode" class="optional-field-display">
        <TagIcon :size="16" />
        <span>{{ project.paymentMode.label }}</span>
    </div>
    <div v-if="project.comment" class="optional-field-display">
        <span class="comment-text">"{{ project.comment }}"</span>
    </div>
</div>
```

**Impact:** Clear, scannable presentation of settlement details.

### 6. Cumulative Balance View

**Changes:**
- Card-based layout for each person
- Currency chips showing individual balances
- Quick settle buttons
- Empty state message when no balances

**Layout:**
```
┌─ Cumulative Balances ─────────────────────┐
│                                           │
│ ┌─ John Smith ──────────────────────────┐ │
│ │ Avatar │ [USD: +150.00] [EUR: -75.50] │ │
│ │        │ [Settle] [Details]           │ │
│ └───────────────────────────────────────┘ │
│                                           │
│ ┌─ Jane Doe ────────────────────────────┐ │
│ │ Avatar │ [GBP: +200.00]               │ │
│ │        │ [Settle] [Details]           │ │
│ └───────────────────────────────────────┘ │
│                                           │
└───────────────────────────────────────────┘
```

**Impact:** Intuitive, at-a-glance view of all financial relationships.

---

## User Preferences - Hide Own Balance

### Overview

The "Hide Own Balance" feature allows users to hide their own balance display in project member lists. This is particularly useful in 2-person projects where the relationship between balances is inverse and self-evident.

**Example Use Case:**
- Project with user "admin" and member "Sally"
- Without setting: Shows `admin +40.00` and `Sally -40.00`
- With setting enabled: Shows only `Sally -40.00` (admin's balance is implicit)

### Implementation

**State Management** (`src/state.js`)
```javascript
hideOwnBalance: false, // Hide current user's balance display in member lists
```
- Simple boolean flag in global cospend state
- Persists across sessions via Nextcloud preferences API
- Default is `false` to maintain backward compatibility

**Settings UI** (`src/components/CospendSettingsDialog.vue`)

1. **Data Property:**
   ```javascript
   hideOwnBalance: cospend.hideOwnBalance ?? false,
   ```
   - Initialized from server state on component creation
   - Used with `v-model.sync` for two-way binding

2. **UI Element:**
   ```vue
   <NcCheckboxRadioSwitch
       :checked.sync="hideOwnBalance"
       @update:checked="onCheckboxChange($event, 'hideOwnBalance')">
       {{ t('cospend', 'Hide my balance') }}
   </NcCheckboxRadioSwitch>
   ```
   - Positioned in "Misc" settings section
   - Uses existing `onCheckboxChange()` handler pattern

3. **State Sync - Watch Handler:**
   ```javascript
   'cospend.hideOwnBalance'(newValue) {
       this.hideOwnBalance = newValue
   }
   ```
   - Watches global cospend state changes
   - Syncs UI when state updates from other sources
   - Ensures checkbox reflects persisted value

4. **Dialog Refresh:**
   ```javascript
   handleShowSettings() {
       this.showSettings = true
       // ... other refreshes ...
       this.hideOwnBalance = cospend.hideOwnBalance ?? false
   }
   ```
   - Refreshes value when settings dialog is opened
   - Ensures correct value displays after page reload
   - Follows same pattern as `displayOrder` and `hideProjectsVisibility`

**Balance Display** (`src/components/AppNavigationMemberItem.vue`)

1. **Computed Property:**
   ```javascript
   shouldHideBalance() {
       // Hide balance if setting is enabled AND this member is the current user
       if (!cospend.hideOwnBalance) {
           return false
       }
       const currentUser = getCurrentUser()
       return this.member.userid === currentUser.uid
   }
   ```
   - Returns `true` only when BOTH conditions met:
     1. Setting is enabled (`cospend.hideOwnBalance === true`)
     2. Member is current user (`member.userid === getCurrentUser().uid`)
   - Only applies to Nextcloud users (those with userid)
   - Local members continue to display

2. **Template Condition:**
   ```vue
   <template v-if="inNavigation && !shouldHideBalance"
       #counter>
       <NcCounterBubble class="balance">
           <span :class="balanceClass">{{ balanceCounter }}</span>
       </NcCounterBubble>
   </template>
   ```
   - Balance counter only renders if `shouldHideBalance` is `false`
   - Member is still visible in list, only the balance counter is hidden
   - No changes to member visibility or other UI elements

### Data Flow

1. User opens Settings → Misc section
2. User checks "Hide my balance" checkbox
3. `onCheckboxChange()` triggered:
   - Emits `save-option` event with `{key: 'hideOwnBalance', value: '1'}`
   - Updates local `this.hideOwnBalance = true`
   - Updates global `cospend.hideOwnBalance = true`
4. Backend persists setting to Nextcloud user preferences
5. In `AppNavigationMemberItem.vue`:
   - `shouldHideBalance` computed property recalculates
   - Returns `true` for current user
   - Balance counter is no longer rendered
   - UI updates instantly (Vue reactivity)
6. On page reload:
   - Settings dialog can be reopened
   - `handleShowSettings()` refreshes value from state
   - Checkbox shows correct checked state
   - Watch handler ensures sync

### Technical Details

**Per-User Setting:**
- Setting is user-specific, not project-specific
- Applies globally to all projects
- Each user has independent setting

**Only Affects Nextcloud Users:**
- Checks `member.userid` to identify Nextcloud users
- Local project members (no userid) always display
- This distinction preserves information for non-Nextcloud members

**Persistence:**
- Uses existing Nextcloud `save-option` event system
- Stored in user preferences table
- Loaded at app initialization
- Survives page reloads and sessions

**Performance:**
- Minimal impact: Simple boolean check in computed property
- No additional API calls needed
- Works entirely with client-side state

### Settings Dialog Sync Pattern

This feature follows the established pattern for complex settings:

1. **Watch handler** - Syncs state to UI when global state changes
2. **Dialog refresh** - Ensures UI is fresh when dialog opens
3. **Bidirectional binding** - UI changes persist to global state via `onCheckboxChange()`

Same pattern used by:
- `showSummaryFirst` ↔ `displayOrder`
- `hideProjectsByDefault` ↔ `hideProjectsVisibility`

---

## Testing and Validation

### Test Scenarios

#### Scenario 1: Full Settlement Single Currency
- **Setup:** Two projects with same currency
````
- **Action:** Settle full balance in USD with John
- **Expected:** 
  - Settlement dialog shows both projects
  - Bills created in both projects
  - Balances update to zero
  - No console errors

#### Scenario 2: Partial Settlement with Optional Fields
- **Setup:** Three projects, user specifies custom amounts
- **Action:** 
  - Set partial amount: $200
  - Set per-project dates and comments
  - Confirm settlement
- **Expected:**
  - API receives correct project breakdown with optional fields
  - Bills created with specified timestamps and comments
  - Balances adjusted correctly

#### Scenario 3: Mixed Currency Settlement
- **Setup:** Projects with USD, EUR, GBP balances
- **Action:** Settle EUR balance only with Jane
- **Expected:**
  - Only EUR projects included in settlement
  - Other currencies remain unchanged
  - Confirmation dialog shows EUR only

#### Scenario 4: Local Member Settlement
- **Setup:** Project with local members (no Nextcloud user IDs)
- **Action:** Settle with local member by name
- **Expected:**
  - Member matched by name (not userid)
  - Settlement bill created successfully
  - Balance updates correctly

#### Scenario 5: Navigation Switching
- **Setup:** App with multiple projects and Cumulative Balance view
- **Action:** 
  - Navigate to Cumulative Balance
  - Switch to project view
  - Switch back to Cumulative Balance
- **Expected:**
  - No console errors
  - UI renders correctly each time
  - State maintained properly

### Validation Checks

✅ **Backend Validation:**
- All projects accessible by current user
- All target members exist in respective projects
- Optional field values are valid
- Bill creation succeeds
- Amount calculations are correct

✅ **Frontend Validation:**
- Settlement type selection required
- Currency selection required
- Partial amounts must be positive
- Confirmation before API call
- Error handling for failed requests

✅ **Data Integrity:**
- Bills created with correct payer/ower
- Optional fields saved to bills
- Currency preserved
- Comments under 300 characters
- Timestamps are numeric

---

## File Modifications Summary

### New Files Created

| File | Purpose |
|------|---------|
| `src/components/CrossProjectSettlement.vue` | Main settlement dialog component |
| `src/components/CrossProjectBalanceView.vue` | Cumulative balance display |
| `docs/IMPLEMENTATION_DETAILS.md` | This comprehensive documentation |

### Modified Frontend Files

| File | Changes |
|------|---------|
| `src/components/CospendNavigation.vue` | Added cumulative balance display, multi-currency support, centering fixes |
| `src/components/Sidebar.vue` | Added null-safety checks for project property |
| `src/components/CurrencyManagement.vue` | Added null-safety checks for project access |
| `src/components/SharingTabSidebar.vue` | Added optional chaining for shares |
| `src/components/SettingsTabSidebar.vue` | Added null checks for members |
| `src/components/CategoryOrPmManagement.vue` | Added optional chaining and fallbacks |
| `src/components/CospendSettingsDialog.vue` | Added "Hide my balance" toggle in Misc section, added watch handler and dialog refresh for state sync |
| `src/components/AppNavigationMemberItem.vue` | Added `shouldHideBalance` computed property to conditionally hide current user's balance |
| `src/App.vue` | Removed orphaned `@confirm-settlement` event listener |
| `src/state.js` | Added `hideOwnBalance` preference flag, cumulative balance display state |

### Modified Backend Files

| File | Changes |
|------|---------|
| `lib/Controller/ApiController.php` | Added `createCrossProjectSettlement()` endpoint |
| `lib/Service/CospendService.php` | Implemented `createCrossProjectSettlement()` method with member lookup and optional field handling |

### Documentation Files Updated

| File | Changes |
|------|---------|
| `README.md` | Added cross-project settlement to features list |
| `docs/user.md` | Added sections on Cumulative Balance and Cross-Project Settlement |
| `docs/dev.md` | Added Architecture Overview section |
| `CHANGELOG.md` | Added [Unreleased] section with comprehensive feature list |
| `docs/IMPLEMENTATION_DETAILS.md` | Created this file with complete technical documentation |

### Configuration Files

| File | Changes |
|------|---------|
| `appinfo/info.xml` | Updated version and description if needed |

---

## Key Design Decisions

### 1. Per-Project Optional Fields

**Decision:** Store optional field configuration per project rather than global.

**Rationale:**
- Different projects may have different requirements
- Users might want date for some projects but not others
- Reduces API payload size by only sending needed fields
- More flexible and user-friendly

### 2. Member Lookup with Fallback

**Decision:** First match by userid, then by name for local members.

**Rationale:**
- Handles both Nextcloud users and local project members
- Nextcloud users have userid, local members don't
- Name fallback ensures local member settlement works
- Prevents silent failures and confusing errors

### 3. Reimbursement Category

**Decision:** All settlement bills are created as "reimbursement" type.

**Rationale:**
- Semantically correct - settlements are reimbursements
- Distinguishes from regular project bills
- Allows filtering/reporting on settlements
- Matches user expectations

### 4. Optional Field Validation

**Decision:** Validate comment length but allow any reasonable timestamp.

**Rationale:**
- Comment length is important for database performance
- Timestamps should be flexible for user needs
- Range restrictions (30 days past/365 future) may be too limiting
- Validation occurs at API boundary for security

### 5. Multi-Currency Display

**Decision:** Aggregate balances by currency code across all projects.

**Rationale:**
- Users need to see total exposure by currency
- Different currencies may need different settlement approaches
- Currency-specific view enables selective settlements
- Improves financial clarity

---

## Performance Considerations

### 1. Memoization

The `memoizedFormatCurrencyWithDirection()` method uses memoization to avoid re-formatting the same value multiple times during render cycles.

### 2. Computed Properties

Extensive use of computed properties (`myBalanceByCurrency`, `aggregateBalances`) to cache expensive calculations across re-renders.

### 3. Lazy Loading

Cumulative Balance view can be disabled to avoid computing balances when not needed.

### 4. Batch Bill Creation

All bills for a settlement are created in a single loop without intermediate waits, minimizing latency.

---

## Future Enhancements

Potential improvements for future versions:

1. **Scheduled Settlements:** Schedule recurring settlements
2. **Settlement Templates:** Save and reuse settlement configurations
3. **Multi-Currency Conversion:** Exchange rates for cross-currency settlements
4. **Settlement History:** View past settlements and revert if needed
5. **Approval Workflow:** Settlements require approval from recipient
6. **Notifications:** Email/push notifications for settlement requests
7. **Analytics:** Settlement statistics and trends

---

## Troubleshooting

### Issue: Settlement bill not created

**Possible Causes:**
1. User doesn't have access to one of the projects
2. Target member doesn't exist in one of the projects
3. Invalid payment mode ID for the project
4. Comment exceeds 300 characters

**Solution:** Check browser console and API response for specific error message.

### Issue: Balance not updating after settlement

**Possible Causes:**
1. Browser cache not cleared
2. Backend transaction failed silently
3. New bill not included in balance calculation

**Solution:** 
1. Clear browser cache
2. Check server logs for errors
3. Verify bills were created in project

### Issue: Cumulative balance showing incorrect amounts

**Possible Causes:**
1. Some projects not included in aggregation
2. Archived projects incorrectly included
3. Currency conversion error
4. Stale cached balance data

**Solution:**
1. Verify project filter settings
2. Check which projects are being aggregated
3. Refresh project data
4. Restart Nextcloud app

---

## Contributing

When working with the cross-project settlement feature:

1. **Maintain Member Lookup Logic:** Keep the userid/name fallback pattern
2. **Preserve Optional Fields:** Don't remove per-project field support
3. **Test All Scenarios:** Test with local and Nextcloud users
4. **Update Documentation:** Keep this file in sync with changes
5. **Follow Vue 2.7 Patterns:** Maintain compatibility with current Vue version

---

**Last Updated:** October 31, 2025  
**Implementation Started:** From commit c9603f1a585d0cd4b6b1c99926db4e3df81ba664  
**Version:** Implementation Details for Unreleased Version
