# Containers & Move — Student

**FSD:** §9.1–§9.6

## 1. Purpose

Visibility and control over container shipment, return, and dorm delivery stages ("My Move").

## 2. User stories

| ID | Story |
|----|-------|
| S-C1 | As a student, I see my container status on My Move page. |
| S-C2 | As a student, I confirm address and trigger shipment to home. |
| S-C3 | As a student, I view FedEx tracking numbers and external links. |
| S-C4 | As a student, I follow instructions to schedule FedEx pickup. |

## 3. Route

`/student/move-tracking`

## 4. Container workflow states

| Order | Status |
|-------|--------|
| 1 | Container Prepared |
| 2 | Label Generated |
| 3 | Shipped to Home |
| 4 | Delivered to Home |
| 5 | Customer Packing |
| 6 | Pickup Scheduled |
| 7 | Return Shipment In Transit |
| 8 | Received at New Life Hub |
| 9 | Stored at Receiving Hub |
| 10 | Scheduled for Dorm Delivery |
| 11 | Out for Delivery |
| 12 | Delivered to Dorm |

## 5. Shipment trigger

Before trigger, student must:
1. Confirm shipping address (triple-check from module 02)
2. Confirm container quantity
3. Confirm order details
4. Click "Trigger Shipment"

Portal displays:
- Ship-by date
- Tracking numbers (when available)
- Status timeline

## 6. FedEx (MVP)

| Feature | MVP |
|---------|-----|
| Tracking display | Yes |
| External tracking link | Yes |
| API auto-sync | No (Phase 2) |
| Return labels | Pre-printed in container; numbers entered by admin |
| Pickup scheduling | Instructions + link only (student schedules externally) |

## 7. Notifications

Container shipped, delivered to home, pickup reminders, return updates, dorm delivery updates.

## 8. Acceptance criteria

- [ ] Timeline reflects current container status.
- [ ] Shipment trigger blocked until address confirmed.
- [ ] Tracking links open FedEx when number present.
- [ ] Pickup instructions page/section visible at appropriate stage.
- [ ] Multiple containers shown if package tier includes >1.
