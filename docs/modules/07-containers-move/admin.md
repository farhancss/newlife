# Containers & Move — Admin

**FSD:** §9, §16.2

## 1. Purpose

Assign containers, update statuses, manage tracking numbers, align with student timeline.

## 2. User stories

| ID | Story |
|----|-------|
| A-C1 | As an admin, I view all containers with status and location. |
| A-C2 | As an admin, I assign container to student. |
| A-C3 | As an admin, I update status and FedEx tracking. |
| A-C4 | As an admin, I mark delivered at each stage. |

## 3. Route

`/admin/containers`

## 4. List columns

| Column | Notes |
|--------|-------|
| Container ID | e.g. CTN-101 |
| Student | Name + NL ID |
| Status | Workflow state |
| Location | City/hub |
| Tracking # | FedEx outbound/return |
| Actions | Edit, history |

## 5. Admin actions

- Create/assign container to student
- Advance or set status (with validation)
- Enter/update tracking numbers
- Add internal note
- Manual override any status (module 14)

## 6. Acceptance criteria

- [ ] Container list searchable by ID, student, tracking.
- [ ] Status transitions validated (or override with reason).
- [ ] Student timeline updates when admin changes status.
- [ ] Tracking number visible to student when set.
