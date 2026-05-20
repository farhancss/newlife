# Deliveries — Admin

**FSD:** §9, admin operations

## 1. Purpose

Schedule dorm deliveries, assign time windows, track completion.

## 2. User stories

| ID | Story |
|----|-------|
| A-DV1 | As an admin, I schedule deliveries by date and window. |
| A-DV2 | As an admin, I assign driver/team (optional MVP). |
| A-DV3 | As an admin, I filter by status and date. |
| A-DV4 | As an admin, I mark out for delivery and completed. |

## 3. Route

`/admin/deliveries`

## 4. Fields

| Field | Notes |
|-------|-------|
| Container | FK |
| Student | Via container |
| Delivery date | Date |
| Time window | e.g. 9AM–12PM |
| Address | From housing info |
| Status | Scheduled, Assigned, Out for delivery, Completed, Cancelled |
| Notes | Internal |

## 5. Status filters (current UI chips)

- Scheduled
- Out for Delivery
- Completed

## 6. Acceptance criteria

- [ ] Create delivery links container + student housing address.
- [ ] List filterable by date range and status.
- [ ] Status update syncs container workflow state.
- [ ] Appears on admin dashboard upcoming deliveries widget.
