# Retail Packages — Admin

**FSD:** §8, §16.1

## 1. Purpose

Warehouse intake, status management, and delivery readiness for all logged retail packages.

## 2. User stories

| ID | Story |
|----|-------|
| A-R1 | As an admin, I view all retail packages across students. |
| A-R2 | As an admin, I search by tracking number or student. |
| A-R3 | As an admin, I update package status through workflow. |
| A-R4 | As an admin, I filter by delivery readiness. |

## 3. Route

`/admin/retail-packages`

## 4. List columns

| Column | Notes |
|--------|-------|
| New Life ID | Link to student |
| Student name | |
| Retailer | |
| Item | |
| Tracking # | Searchable |
| Status | Badge |
| ETA | |
| Actions | View, update status |

## 5. Admin actions

- Update status (dropdown with valid transitions)
- Add package on behalf of student (override)
- Remove package (override with reason)
- Resend notification (module 10)

## 6. Filters

- Status
- Retailer
- Move-in date range
- Dorm/building
- Received today

## 7. Acceptance criteria

- [ ] Global list with DataTables search/sort/pagination.
- [ ] Status update writes audit log entry.
- [ ] Tracking search returns correct package.
- [ ] Filters combine correctly.
- [ ] Manual add/remove requires admin permission + note.
