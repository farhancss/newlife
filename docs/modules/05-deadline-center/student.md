# Deadline Center — Student

**FSD:** §7.1–§7.3

## 1. Purpose

Students never miss critical dates for containers, returns, retail packages, and housing updates.

## 2. User stories

| ID | Story |
|----|-------|
| S-DL1 | As a student, I see all my deadlines in one place. |
| S-DL2 | As a student, I see color-coded urgency (upcoming vs overdue). |
| S-DL3 | As a student, I receive SMS/email reminders before deadlines. |

## 3. Deadline types

| Type | Description |
|------|-------------|
| Container order | Last date to confirm/trigger shipment |
| Return shipment | Last date to schedule return pickup |
| Retail package arrival | Packages must arrive at hub by date |
| Housing update | Last date to finalize dorm details |

## 4. Urgency levels

| Level | Condition | UI |
|-------|-----------|-----|
| Normal | > 7 days | Gray/green |
| Upcoming | 3–7 days | Amber |
| Urgent | < 3 days | Orange |
| Overdue | Past due | Red |

## 5. Dashboard integration

- Show top 3 deadlines on student dashboard (module 03)
- Link "View all" → Deadline Center page

## 6. Notifications

SMS + email per module 10 for upcoming and expiring deadlines.

## 7. Acceptance criteria

- [ ] All applicable deadlines listed per student.
- [ ] Urgency colors correct based on date math.
- [ ] Overdue deadlines persist until resolved.
- [ ] Dashboard snippet matches full page data.
