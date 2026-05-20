# Onboarding & Profile — Admin

**FSD:** §6, §15.1, §16.3

## 1. Purpose

Operations view and edit student profiles; add internal notes; support manual overrides.

## 2. User stories

| ID | Story |
|----|-------|
| A-O1 | As an admin, I view complete student profile from student management. |
| A-O2 | As an admin, I edit profile fields when customer needs assistance. |
| A-O3 | As an admin, I add internal notes visible only to staff. |
| A-O4 | As an admin, I see onboarding completion status. |

## 3. Screens

| Screen | Route | Status |
|--------|-------|--------|
| Student list | `/admin/customers` | UI shell |
| Student detail/edit | `/admin/customers/{id}` | **Not built** |

## 4. Admin capabilities

- View all onboarding sections
- Edit any field (audit log recommended)
- Add/edit internal notes (timestamp + admin user)
- View New Life ID, package tier, flags (module 13)

## 5. Acceptance criteria

- [ ] Admin can open student detail from list.
- [ ] Incomplete profile flagged in list and dashboard widget.
- [ ] Internal notes save and display chronologically.
- [ ] Profile edits persist and reflect on student view.
