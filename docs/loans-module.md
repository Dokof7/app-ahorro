# Loans Module — Technical Documentation

## Overview

The Loans module manages personal loans granted to group members during savings meetings. It tracks the full lifecycle of each loan: approval, disbursement, installment payments (principal + interest), and automatic closure when the balance reaches zero.

---

## Business Flow

```
Group + Member + Meeting selected
        ↓
Loan created (principal + interest rate)
        ↓
interest_amount and total_to_return auto-calculated on save
        ↓
Status: pending
        ↓
Payments recorded (amount_paid + interest_paid)
        ↓
Loan balance recalculated after each payment
        ↓
Status auto-transitions:
  balance = 0  → paid
  due_date past → overdue
  otherwise    → pending
```

---

## Database Schema

### `loans` table

| Column | Type | Default | Description |
|---|---|---|---|
| `id` | bigint PK | — | Primary key |
| `member_id` | bigint FK | — | → `members` (cascade delete) |
| `group_id` | bigint FK | — | → `groups` (cascade delete) |
| `meeting_id` | bigint FK | — | → `meetings` (cascade delete) |
| `amount` | decimal(10,2) | — | Principal amount |
| `interest_rate` | decimal(5,2) | 0 | Annual interest % |
| `interest_amount` | decimal(10,2) | 0 | Auto: amount × (rate / 100) |
| `total_to_return` | decimal(10,2) | — | Auto: amount + interest_amount |
| `amount_paid` | decimal(10,2) | 0 | Auto: SUM of all payments (principal + interest) |
| `balance` | decimal(10,2) | — | Auto: total_to_return − amount_paid |
| `delivery_date` | date | — | Loan disbursement date |
| `due_date` | date | — | Payment deadline |
| `status` | enum | `pending` | `pending` \| `paid` \| `overdue` |
| `observations` | text | NULL | Optional notes |
| `deleted_at` | timestamp | NULL | Soft delete |

### `loan_payments` table

| Column | Type | Default | Description |
|---|---|---|---|
| `id` | bigint PK | — | Primary key |
| `loan_id` | bigint FK | — | → `loans` (cascade delete) |
| `meeting_id` | bigint FK | NULL | → `meetings` (set null on delete) |
| `payment_date` | date | — | Date payment was made |
| `amount_paid` | decimal(10,2) | — | Principal portion of payment |
| `interest_paid` | decimal(10,2) | 0 | Interest portion of payment |
| `remaining_balance` | decimal(10,2) | — | Snapshot of loan balance after this payment |
| `observations` | text | NULL | Optional notes |

---

## Auto-Calculation Logic

### On `Loan::saving`

```
interest_amount = amount × (interest_rate / 100)
total_to_return = amount + interest_amount
balance         = total_to_return − amount_paid
status          = 'paid'    if balance ≤ 0
                  'overdue'  if due_date is past
                  'pending'  otherwise
```

### On `LoanPayment::saved` / `LoanPayment::deleted`

```
loan.amount_paid = SUM(lp.amount_paid) + SUM(lp.interest_paid) for all payments
loan.balance     = loan.total_to_return − loan.amount_paid
loan.status      recalculated (same rules as above)
meeting.summary  recalculated for both loan's meeting and payment's meeting
```

---

## Route Map

| Method | URI | Controller | Roles |
|---|---|---|---|
| GET | `/loans` | `LoanController@index` | All authenticated |
| GET | `/loans/{loan}` | `LoanController@show` | All authenticated |
| GET | `/loans/members/{groupId}` | `LoanController@getMembersByGroup` | All authenticated |
| GET | `/loans/meetings/{groupId}` | `LoanController@getMeetingsByGroup` | All authenticated |
| GET | `/loans/create` | `LoanController@create` | admin, tesorero, secretario |
| POST | `/loans` | `LoanController@store` | admin, tesorero, secretario |
| DELETE | `/loans/{loan}` | `LoanController@destroy` | admin, tesorero, secretario |
| POST | `/loan-payments` | `LoanPaymentController@store` | admin, tesorero, secretario |
| DELETE | `/loan-payments/{loanPayment}` | `LoanPaymentController@destroy` | admin, tesorero, secretario |

**Note**: Routes `/loans/{loan}` (GET) and `/loans/meetings/{groupId}` use a numeric constraint on `{loan}` to prevent wildcard conflicts.

---

## Controllers

### `LoanController`

| Method | Description |
|---|---|
| `index()` | DataTables server-side list. Non-admin users see only their groups' loans. |
| `create()` | Form with cascading group → member/meeting dropdowns (AJAX). |
| `store()` | Validates and creates the loan. Interest is calculated automatically by the model. |
| `show()` | Loan details + payment history + payment registration form. |
| `destroy()` | Soft-deletes the loan. |
| `getMembersByGroup($groupId)` | AJAX — returns active members for a group (JSON). |
| `getMeetingsByGroup($groupId)` | AJAX — returns open meetings for a group (JSON). |

### `LoanPaymentController`

| Method | Description |
|---|---|
| `store()` | Validates and records a payment. `remaining_balance` is pre-calculated before save. |
| `destroy()` | Deletes the payment. Loan recalculation happens automatically via the model `deleted` event. |

---

## Authorization

**Policy**: `LoanPolicy` — `view` and `delete` allowed for admin or group owner.  
**Route middleware**: `role:admin,tesorero,secretario` guards all write operations.

---

## Business Rules

- A member cannot be deleted or set to inactive if they have `pending` or `overdue` loans.
- A group cannot be deleted if it has `pending` or `overdue` loans or open meetings.
- A meeting cannot be deleted if it has `pending` or `overdue` loans.
- A loan's `meeting_id` is set to `null` if the linked meeting is deleted.
- Soft-deleted loans remain in the database and do not affect balance calculations.

---

## AdminLTE Menu

Located under the `FINANZAS` section in `config/adminlte.php`:

```php
[
    'text'   => 'Préstamos',
    'url'    => 'loans',
    'icon'   => 'fas fa-fw fa-hand-holding-usd',
    'active' => ['loans*'],
],
```

Visible to all authenticated users (including `observador`). The create/edit/delete buttons within views are conditionally rendered based on role.

---

## Views

| View | Description |
|---|---|
| `loans/index.blade.php` | DataTable with member, group, amount, interest, balance, due date, status badge, actions |
| `loans/create.blade.php` | New loan form. Group selection triggers AJAX to populate member and meeting dropdowns. |
| `loans/show.blade.php` | Loan details (left), payment form (right), payment history table (bottom). Form is hidden when balance = 0. |
| `loans/_actions.blade.php` | Action buttons: View, Delete. Shows overdue badge when `status = 'overdue'`. |
| `reports/pdf/loans_paid.blade.php` | PDF report — paid loans |
| `reports/pdf/loans_pending.blade.php` | PDF report — pending and overdue loans |

---

## Known Fixed Bugs

| # | Severity | File | Description |
|---|---|---|---|
| 1 | Critical | `LoanPaymentController.php:31` | `destroy()` called non-existent `recalculateBalance()` on Loan → 500 error. Removed; model event handles recalculation. |
| 2 | High | `routes/web.php:32` | `GET /loans/create` was unreachable: `GET /loans/{loan}` (registered earlier) matched the wildcard first. Fixed with `->where(['loan' => '[0-9]+'])`. |
| 3 | Medium | `LoanPayment.php:38` | `recalculateLoan()` summed only `amount_paid`, ignoring `interest_paid`. Loan balance never reached 0 when interest was paid separately. Fixed by summing both columns. |
| 4 | Low | `Loan.php:28` | `isOverdue()` checked `status === 'pending'` but persisted overdue loans have `status = 'overdue'`. Badge never showed. Fixed to `return $this->status === 'overdue'`. |
| 5 | Minor | `LoanController.php:67` | `group.meetings` not eager-loaded in `show()`. N+1 query when rendering the meeting dropdown in the payment form. |
