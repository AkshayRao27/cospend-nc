* [Introduction](#s1)
  * [Project](#s1-1)
  * [Balance](#s1-2)
  * [Member](#s1-3)
  * [Bill](#s1-4)
* [Create a project](#s2)
  * [Shared access](#s2-1)
    * [Share link permissions](#s2-1-1)
* [Create a member](#s3)
* [Create a bill](#s4)
* [Project statistics](#s5)
* [Settle the project](#s6)
* [Cumulative balances](#s7)
  * [Enable and customize cumulative view](#s7-1)
  * [Balances by person](#s7-2)
* [Cross-project settlement](#s8)
  * [Create a full settlement](#s8-1)
  * [Create a partial settlement](#s8-2)
  * [Per-project optional fields](#s8-3)
* [Deep links and shareable URLs](#s9)

# <a id='s1' />Introduction

Most fields are required. Validation messages in the UI explain missing or invalid values.

## <a id='s1-1' />Project

A project groups members and bills. It tracks who paid, who owes, and each member's running balance.

## <a id='s1-2' />Balance

A positive balance means the member paid more than their share.
A negative balance means the member owes money to the group.
Settlement operations move balances toward zero.

## <a id='s1-3' />Member

A member has a name, weight, and activation status.
A disabled member cannot be added to new bills, but remains visible until balance reaches zero.

Members can represent individuals or grouped entities.
Weight controls how bill shares are split.

## <a id='s1-4' />Bill

A bill records an expense paid by one member and owed by one or more members.

# <a id='s2' />Create a project

When you first visit the app, there is no project yet.

A project is defined by an ID and a name.

Cospend and SplitWise CSV project files can be imported in Cospend.

## <a id='s2-1' />Shared access

Cospend lets you share your projects with users, groups and circles.
It is also possible to create public share links to share a project with people who don't have an account on your Nextcloud instance.
Public share links can be password protected.

## <a id='s2-1-1' />Share link permissions

There are 4 permission levels for shared links: 

* Viewer: read-only access
* Participant: can create, modify or delete bills
* Maintainer: same as participant + can create, modify, disable or delete a project member + can create, modify and delete categories, payment modes and currencies
* Admin: same as maintainer + can rename and delete project + can toggle bill deletion and auto-export + can modify categories or payment modes order

# <a id='s3' />Create a member

Open the project menu, select **Add member**, and provide a name.
New members default to weight `1` and active status.

# <a id='s4' />Create a bill

Click **New bill**, fill required fields, and save.

# <a id='s5' />Project statistics

The filters on top of the statistics page apply to all the statistics charts and tables.

# <a id='s6' />Settle the project

This feature gives you an optimal project settlement/reimbursement plan to put everyone's balance back to 0.

# <a id='s7' />Cumulative balances

Cumulative balances provide a cross-project overview of what you owe and what is owed to you.

## <a id='s7-1' />Enable and customize cumulative view

1. Open Cospend settings.
2. Go to the **Cumulative balances** section.
3. Enable **Show cumulative balances**.
4. Optionally customize:
  - Initial section order
  - Project detail visibility
  - Sorting for people and summary sections

## <a id='s7-2' />Balances by person

The cumulative screen groups balances by person and currency across all active (non-archived) projects.

- **You owe X** means you need to pay that person.
- **Owes you X** means that person owes you.

For each person, you can expand project-level details and start settlement directly from the same view.

# <a id='s8' />Cross-project settlement

Cross-project settlement creates reimbursement bills across multiple projects in one operation.

## <a id='s8-1' />Create a full settlement

1. Open the cumulative balances view.
2. Click **Settle** on the target person.
3. Choose the settlement currency.
4. Select **Full settlement**.
5. Review the project breakdown and confirm.

The app creates reimbursement bills in each impacted project.

## <a id='s8-2' />Create a partial settlement

1. Start settlement from the target person.
2. Select **Partial settlement**.
3. Enter total partial amount.
4. Click **Set custom amounts**.
5. Adjust per-project amounts as needed.
6. Confirm settlement.

The interface displays remaining debt in real time as you edit project amounts.

## <a id='s8-3' />Per-project optional fields

Each project amount can include optional metadata:

- Date/time
- Payment mode
- Comment (max 300 characters)

These values are stored on the generated reimbursement bill for that specific project.

# <a id='s9' />Deep links and shareable URLs

Cospend uses path-based URLs for all major views. You can bookmark, reload, or share any of the following links and the correct view will restore automatically.

| URL | View |
|-----|------|
| `/apps/cospend/` | Home (no project selected) |
| `/apps/cospend/p/{projectId}` | Project view |
| `/apps/cospend/p/{projectId}/b/{billId}` | Specific bill |
| `/apps/cospend/cross-project` | Cumulative balances |
| `/apps/cospend/cross-project/settle/{personKey}` | Settlement with a specific person |

`{personKey}` identifies a person across projects:
- Nextcloud user: `user=userid` (e.g. `user=alice`)
- Anonymous member: `name=first-last` (e.g. `name=alice-bregenz`)

Names are lowercased and spaces replaced with hyphens.
