# Student Management — Admin

**FSD:** §15.1–§15.2, §17

## 1. Purpose

Central student registry for operations with powerful search and issue flagging.

## 2. User stories

| ID | Story |
|----|-------|
| A-SM1 | As an admin, I search students by name, email, or New Life ID. |
| A-SM2 | As an admin, I filter by dorm, move-in date, tier, status. |
| A-SM3 | As an admin, I open student detail with full profile. |
| A-SM4 | As an admin, I flag students for operational issues. |
| A-SM5 | As an admin, I add internal notes. |

## 3. Routes

| Route | Purpose |
|-------|---------|
| `/admin/customers` | List |
| `/admin/customers/{id}` | Detail (to build) |
| `/admin/customers/create` | Manual add (optional MVP) |

## 4. Search filters

| Filter | Field |
|--------|-------|
| Student name | `first_name`, `last_name` |
| Parent name | `parent_guardians.name` |
| Dorm/building | `housing_infos` |
| Move-in date | Range |
| Package tier | From Squarespace order |
| Status | Onboarding, move stage, delivery readiness |
| New Life ID | Exact match |

## 5. List columns

| Column | Notes |
|--------|-------|
| Name | Link to detail |
| New Life ID | |
| Move ID | Internal reference |
| University | |
| Move-in date | |
| Package tier | |
| Status | Composite badge |
| Flags | Icon indicators |
| Actions | View, edit |

## 6. Operational flags (FSD §17)

| Flag | Color suggestion |
|------|------------------|
| Missing Information | Warning |
| VIP Customer | Info |
| Delivery Issue | Danger |
| Late Shipment | Warning |
| Payment Issue | Warning |
| Escalation Needed | Danger |

Multiple flags allowed per student.

## 7. Student detail tabs (proposed)

1. Overview (profile summary + flags)
2. Profile (editable sections)
3. Containers
4. Retail packages
5. Deliveries
6. Notes & activity
7. Support tickets

## 8. Acceptance criteria

- [ ] Search returns results < 500ms for 10k students.
- [ ] Filters work in combination.
- [ ] Detail page shows all profile sections.
- [ ] Flags add/remove with audit trail.
- [ ] Notes chronological, admin-only.
- [ ] Export selected students (links to module 16).
