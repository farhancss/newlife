# Admin Dashboard

**FSD:** §14.2

## 1. Purpose

At-a-glance operational metrics and recent activity for the operations team.

## 2. User stories

| ID | Story |
|----|-------|
| A-D1 | As an admin, I see total students and active move counts. |
| A-D2 | As an admin, I see how many profiles are incomplete. |
| A-D3 | As an admin, I see containers in transit and packages received. |
| A-D4 | As an admin, I see students ready for delivery vs delivered. |
| A-D5 | As an admin, I review recent activity and upcoming deliveries. |

## 3. Route

| Route | View |
|-------|------|
| `/admin/dashboard` | `admin.dashboard` |

## 4. Widgets (FSD §14.2)

| Widget | Metric | Source |
|--------|--------|--------|
| Total customers | Count active students | `StudentProfile` |
| Incomplete profiles | Onboarding not complete | Profile service |
| Ready for shipment | Address confirmed, not shipped | Container service |
| Containers in transit | Status filter | Container service |
| Packages received | Retail at hub | Retail package service |
| Ready for delivery | Scheduled, not delivered | Delivery service |
| Delivered students | Terminal dorm delivery state | Container service |
| Outstanding issues | Open flags/tickets | Flags + support |

## 5. Secondary panels (current UI)

| Panel | Purpose |
|-------|---------|
| Recent activity | Audit-style event feed |
| Move status overview | Donut chart by move stage |
| Upcoming deliveries | Next 7 days schedule |

## 6. MVP vs Phase 2

| MVP | Phase 2 |
|-----|---------|
| Static thresholds | Configurable alert thresholds |
| CSV drill-down links | Click widget → filtered list |

## 7. Acceptance criteria

- [ ] All widget counts match database queries.
- [ ] Widgets refresh on page load (real-time optional).
- [ ] Recent activity shows last N system events.
- [ ] Upcoming deliveries table matches delivery module data.
- [ ] Move status chart percentages sum to 100%.
- [ ] Mobile layout usable for field staff.
