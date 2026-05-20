# Add-Ons — Admin

**FSD:** §12.2

## 1. Purpose

Confirm external payments and activate entitlements.

## 2. User stories

| ID | Story |
|----|-------|
| A-AO1 | As an admin, I see pending add-on requests. |
| A-AO2 | As an admin, I activate add-on after verifying Squarespace payment. |
| A-AO3 | As an admin, I cancel fraudulent or duplicate requests. |

## 3. Route

`/admin/add-ons`

## 4. List columns

| Column | Notes |
|--------|-------|
| Student | |
| Add-on type | |
| Status | |
| Requested at | |
| Squarespace order ID | Manual entry |
| Actions | Activate, cancel |

## 5. Activation effects

| Add-on | System effect |
|--------|---------------|
| Additional containers | Increment container allocation |
| Retail cap increase | Update profile entitlement |
| Storage extension | Update deadline/notes |

## 6. Acceptance criteria

- [ ] Activate changes status to Active with timestamp and admin ID.
- [ ] Entitlement changes apply immediately.
- [ ] Student notified on activation.
- [ ] Cancel requires reason.
