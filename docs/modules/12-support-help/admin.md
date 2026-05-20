# Support & Help — Admin

**FSD:** §13.2

## 1. Purpose

Operations resolves student support tickets.

## 2. User stories

| ID | Story |
|----|-------|
| A-SP1 | As an admin, I see open tickets queue. |
| A-SP2 | As an admin, I assign and update ticket status. |
| A-SP3 | As an admin, I reply to student (email notification). |

## 3. Route (proposed)

`/admin/support` or repurpose `/admin/communications`

## 4. Ticket statuses

| Status | Description |
|--------|-------------|
| Open | New |
| In progress | Assigned |
| Waiting on customer | Needs student reply |
| Resolved | Closed |
| Escalated | Manager review |

## 5. List columns

| Column | Notes |
|--------|-------|
| Ticket # | |
| Student | Name + NL ID |
| Category | |
| Subject | |
| Status | |
| Created | |
| Assigned to | |

## 6. Acceptance criteria

- [ ] Ticket list filterable by status and category.
- [ ] Status changes notify student.
- [ ] Internal notes separate from student-visible replies.
- [ ] Link to student profile from ticket.
