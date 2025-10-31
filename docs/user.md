* [Introduction](#s1)
  * [What is a project :paperclip: ?](#s1-1)
  * [What are balances :balance_scale: ?](#s1-2)
  * [What is a member :ok_woman: ?](#s1-3)
  * [What is a bill :dollar: ?](#s1-4)
* [Create a project](#s2)
  * [Shared access](#s2-1)
    * [Share link permissions](#s2-1-1)
* [Create a member](#s3)
* [Create a bill](#s4)
* [Project statistics](#s5)
* [Settle within a project](#s6)
* [Cross-project settlements](#s7)
  * [Cumulative balances](#s7-1)
  * [Creating a cross-project settlement](#s7-2)
  * [Settlement optional fields](#s7-3)
* [User preferences and settings](#s8)
  * [Hide own balance display](#s8-1)

---

> ⚠️ **Note:** This documentation describes Cospend version 3.0.13-final, the final version for Vue 2.7.

---

# <a id='s1' />Introduction

Things you should know:

* "Sometimes small tools save big time" (:blond_haired_person: MacGyver)
* Most (all?) fields are mandatory in Cospend. The cold interface messages will tell you that.

## <a id='s1-1' />What is a project :paperclip: ?

A project contains members and bills. A project is a way to manage what is spent in a group of persons.
It's a way to know who paid what for whom and when and who owes how much to the group.
Debts are not personal, a member who has a debt in the group (negative balance) can pay anyone in the group
to bring his/her balance back to zero and leave the group. This will have an effect on other's balances.

## <a id='s1-2' />What are balances :balance_scale: ?

The balance value represents the situation of a member in a project.
A positive balance indicates that the member payed more for the group than the grouped payed for them.
By keeping an eye on the balance, one can stop taking care of exactly how much they owe to each project member.

If member A has a negative balance, -10 for example, it just means A owes 10$ to the group.
Any payment of 10$ to the group (or a sub part of the group) will bring the balance back to zero.
It does not matter who it was payed for.

All those actions have the same effect on the member A's balance => Make it raise of 10:

0. Member A pays 20$ for a cake that is eaten by A and B (one bill payed by A with A and B as owers)
1. Member A pays 10$ to member B (one bill payed by A with B as ower)
2. Member A pays 5$ to member B and 5$ to member C (one bill payed by A with B as ower, another bill payed by A with C as ower)
3. Member A pays a 15$ cake eaten by A, B and C (on bill payed by A with A, B and C as owers)

The only difference is the effect on other members balances:

0. -10 in B's balance
1. -10 in B's balance
2. -5 in B's balance and -5 in C's balance
3. -5 in each ower's balance

## <a id='s1-3' />What is a member :ok_woman: ?

A member has a name, a weight and can be activated or not. When a member is disabled, it cannot be part of a new bill (as a payer or an ower). A disabled member will appear in member list until their balance reaches 0.

A member can be one real person. This is the most common case. Just add one member for each person in the group you want to manage.

A member can also be a sub-group of persons. For example, if Alice and Bob are a couple and want to be considered as one member in MoneyBuster, it is possible. Just create a member named "Alice & Bob" with a weight of 2. This way, when they are concerned by a bill payed by someone else, the member "Alice & Bob" will owe 2 shares of this bill, not one.

For example if Roger, with a weight of 1, pays a 30 euros bill which concerns Robert and "Alice & Bob", the balance of Roger is going up of 30. The bill concerns Roger (weight = 1) and "Alice & Bob" (weight = 2). The sum of the members weight is 3, this means we have to split the bill in 3 shares. Roger will owe 1 share (10 euros) and "Alice & Bob" will owe 2 shares (20 euros).

It seems simple enough to do it intuitively with a small example but it gets really complicated for a bigger one. Let the tool do the job. :eyeglasses:

## <a id='s1-4' />What is a bill :dollar: ?

A bill is a spending from one member which concerns one or more members in the project. A bill is defined by a name, an amount, a payer, a date and a list of owers.

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

This is pretty simple. Press "+" in the project drop-down menu and then press "add a member".

Just provide a user name and that's it. Member is added with a weight of 1 and is activated by default.

# <a id='s4' />Create a bill

Pretty simple too. Press the "new bill" button. Fill all fields and press the "Save bill" button.

# <a id='s5' />Project statistics

The filters on top of the statistics page apply to all the statistics charts and tables.

# <a id='s6' />Settle within a project

This feature gives you an optimal project settlement/reimbursement plan to put everyone's balance back to 0.

# <a id='s7' />Cross-project settlements

When you have multiple projects and share members across these projects, you might want to settle balances across all projects at once rather than settling each project individually.

## <a id='s7-1' />Cumulative balances

The "Cumulative Balance" view shows your total balance across all non-archived projects, grouped by currency. This gives you an overview of:

* How much you owe or are owed across all your projects
* Multi-currency balances presented clearly with amounts in each currency
* Quick access to settle balances with any other member
* Complete financial picture across your entire Cospend usage

### Understanding cumulative balances

Your cumulative balance is calculated by combining your balance from each project. The system shows you:

* **Positive balances** (shown in green): You have spent more than others have spent for you. These are amounts you are owed.
* **Negative balances** (shown in red): You have spent less than others have spent for you. These are amounts you owe.

For example, if you have:
* USD +150 (you're owed $150)
* EUR -75.50 (you owe €75.50)

This means across all your projects, people owe you money in US dollars, but you owe money in euros.

### Accessing cumulative balance

To access this view:

1. Look in the left sidebar under your project list
2. Click on "Cumulative Balance"
3. You'll see a card-based view showing each person and their balance with you
4. Each person shows currency chips with their individual balance in each currency

### Sidebar balance indicator

If enabled, your cumulative balance also appears in the navigation sidebar footer, showing your total balance across all projects. This gives you a quick at-a-glance view without needing to navigate to the full Cumulative Balance view.

## <a id='s7-2' />Creating a cross-project settlement

Cross-project settlement allows you to settle balances with another member across multiple projects in a single transaction. This is useful when you:

* Share multiple projects with the same person
* Want to simplify finances across projects
* Need to settle accounts before leaving a group
* Prefer to settle once instead of project-by-project

### Step-by-step settlement process

**Step 1: Access Cumulative Balance**
- Click "Cumulative Balance" in the left sidebar
- You'll see all members you have balances with, organized by person

**Step 2: Initiate settlement**
- Find the person you want to settle with
- Click the "Settle" button next to their name
- The settlement dialog will open

**Step 3: Choose currency**
- If the person has multiple currency balances with you, select which currency to settle in
- Only projects with this currency will be included in the settlement

**Step 4: Select settlement type**
- **Full settlement**: Settles your entire balance in the chosen currency
  - Automatically calculates the total across all affected projects
  - Shows the breakdown of how much from each project
- **Partial settlement**: You specify a custom total amount
  - You can then adjust how much to settle from each individual project
  - Useful for partial payments or installments

**Step 5: Configure optional fields (optional)**
- For each project in the settlement, you can add optional details:
  - **Date/Time**: When the settlement occurred
  - **Payment mode**: How the payment was made
  - **Comment**: Notes about this settlement
- Fields are configured per-project, so you can have different values for different projects

**Step 6: Review confirmation**
- The confirmation dialog shows:
  - Total settlement amount and currency
  - Settlement type (Full or Partial)
  - Breakdown for each project including:
    - Amount to be settled
    - Any optional fields you configured
    - Visual indicators showing payment direction

**Step 7: Confirm and complete**
- Review all details one final time
- Click "Confirm Settlement" to complete
- The system creates reimbursement bills in all affected projects
- Your balance updates immediately

### What happens after settlement

When you confirm a settlement:

1. **Bills are created**: The system automatically creates reimbursement bills in each affected project
2. **Optional fields are saved**: Any date, payment mode, or comments you added are included in the bills
3. **Balances update**: Your balance and the other person's balance are adjusted to reflect the settlement
4. **Activity is recorded**: The settlement appears in the project activity stream
5. **You can view details**: You can see the settlement bills in each project, just like any other bills

### Settlement direction

The settlement direction depends on your relative balance:

* If you **owe** the other person money (negative balance), you are paying them
* If the other person **owes** you money (positive balance), they are paying you

The system automatically handles the correct payment direction based on your balance.

### Partial settlements

Partial settlements are useful for:

* Installment payments where you settle part of the debt now
* Adjusting shared expenses as situations change
* Testing settlement functionality before settling full amounts

When creating a partial settlement:
1. Choose "Partial settlement" in the settlement type
2. Enter the total amount you want to settle
3. The system automatically distributes this across projects proportionally
4. You can manually adjust per-project amounts if needed
5. The confirmation shows your custom breakdown

## <a id='s7-3' />Settlement optional fields

When creating a cross-project settlement, you can add optional details to the reimbursement bills that get created. Each project can have different optional fields, giving you flexibility in how detailed your settlement records should be.

### Available optional fields

**When? - Settlement date and time**
- Specify when the settlement occurred or when the payment was made
- Useful for tracking the chronological order of settlements
- Defaults to the current date and time if not specified
- Can be set to a past date for retroactive settlements

**Payment mode - How was it paid?**
- Select the payment method for the settlement (Cash, Card, Bank Transfer, etc.)
- Useful for tracking payment methods and creating reports
- Available payment modes depend on your project's configuration
- Optional - leave blank if payment method isn't important

**Comment - Additional notes**
- Add up to 300 characters of notes about the settlement
- Examples: "Travel expenses reimbursement", "Shared groceries", "Rent settlement"
- Helps document the purpose and context of the settlement
- Visible in the bill details and settlement records

### Configuring optional fields per project

**Important:** Optional fields are configured **per project**, not globally. This means:

* You can add a date for the settlement in Project A but not in Project B
* Different projects can have different payment modes for the same settlement
* You maintain flexibility in what information you record where

To configure optional fields:

1. In the settlement dialog, look for each project in the breakdown
2. Below each project amount, you'll see optional field inputs if available
3. Fill in any details you want to record for that project
4. Leave fields blank to not include them for that project
5. The confirmation dialog shows all fields you've configured

### Practical examples

**Example 1: Travel expense settlement**
- Settlement with roommate across two projects
- Project A (shared apartment): $500, when: 2025-10-31, payment: Cash
- Project B (travel fund): $250, when: 2025-11-02, payment: Credit Card, comment: "Flight reimbursement"
- Each project records the settlement details specific to that expense

**Example 2: Monthly household settlement**
- Regular settlement with family member
- Projects: Groceries, Utilities, Rent
- All dated for the end of the month
- Each project shows when and how that particular settlement occurred

**Example 3: Minimal recording**
- Quick settlement without tracking details
- Leave all optional fields blank
- Settlement is recorded with just the amounts and dates bills are created

### Field validation

The system validates optional fields to ensure data integrity:

* **Timestamp** must be a valid date/time format
* **Comment** cannot exceed 300 characters
* **Payment mode** must be valid for the project
* If validation fails, you'll see an error message indicating which field caused the issue

### Viewing settled bills

After a settlement:

1. Navigate to each affected project
2. The reimbursement bills appear in the bill list
3. You can view all optional fields you set:
   - Look in the bill details for the date/time
   - The bill shows the payment mode used
   - The bill comment displays your notes
4. You can edit these bills like any other bills in the project

# <a id='s8' />User preferences and settings

Cospend provides various settings to customize your experience. Access these settings by clicking the "Cospend settings" option in the left sidebar.

## <a id='s8-1' />Hide own balance display

In projects where you share with other members, your balance is typically displayed in the member list alongside other members' balances. This setting allows you to hide your own balance from the display.

### Why hide your own balance?

This setting is particularly useful in **2-person projects** where the relationship between balances is obvious:

* **Example:** In a project shared between you and Sally:
  - Without the setting: You see both `You +40.00` and `Sally -40.00`
  - With the setting: You only see `Sally -40.00`
  - Your balance is implicit (the inverse of Sally's), so the explicit display is redundant

### How to use this setting

1. Open **Cospend settings** from the left sidebar
2. Navigate to the **Misc** section
3. Check the box labeled **"Hide my balance"**
4. The setting is saved automatically

### Effect of the setting

**When enabled:**
- Your balance no longer displays in the member list counter bubbles
- Other members' balances display normally
- Your name still appears in the member list (you're still a member)
- The setting applies globally across all your projects
- Nextcloud users' balances are hidden; local-only members' balances still display

**When disabled:**
- Your balance displays alongside other members in all projects
- The behavior returns to the default

### Toggling the setting on and off

You can change this setting at any time:
- Open Cospend settings → Misc
- Check or uncheck "Hide my balance"
- The change applies immediately
- Your preference persists across page reloads and Nextcloud sessions
These details are stored with each reimbursement bill, just as they would be if you created a regular bill manually. This helps you keep track of exactly how settlements were made and when.
