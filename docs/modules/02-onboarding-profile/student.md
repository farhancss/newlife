# Onboarding & Profile — Student

**FSD:** §6.1–§6.5

## 1. Purpose

Collect required move-in data before portal access; allow ongoing profile maintenance.

## 2. User stories

| ID | Story |
|----|-------|
| S-O1 | As a new student, I complete onboarding before using the portal. |
| S-O2 | As a student, I enter my info, parent/guardian, home address, and housing details. |
| S-O3 | As a student, I confirm my shipping address with triple-check before shipment. |
| S-O4 | As a student, I upload an optional profile photo. |
| S-O5 | As a student, I edit my profile later from My Profile. |

## 3. Screens and routes

| Screen | Route | Status |
|--------|-------|--------|
| Onboarding wizard | `/student/onboarding` (steps 1–4) | **Not built** |
| My Profile | `/student/profile` | UI shell exists |
| Change password | `/student/change-password` | Exists |

## 4. Onboarding steps

| Step | Section | Required fields |
|------|---------|-----------------|
| 1 | Student info | First name, last name, email, phone, school, incoming year/classification |
| 2 | Parent/guardian | Parent name, email, phone, relationship |
| 3 | Home shipping | Full address, unit, preferred contact, shipping notes |
| 4 | Housing | University, residence hall, building, room, move-in date, window, delivery notes |

**Optional:** Student ID, profile photo.

## 5. Field validations

| Field | Rules |
|-------|-------|
| Email | Valid email, unique per user |
| Phone | US format (configurable) |
| Move-in date | Future or current season date |
| Address | Required components for FedEx shipment |

## 6. Business rules

| Rule | Detail |
|------|--------|
| Onboarding gate | `onboarding_completed_at` null → only onboarding routes allowed |
| Address confirmation | Triple-check modal before `shipment_triggered` (module 07) |
| Housing editable | Always editable after onboarding |
| Parent notifications | Parent email used for CC notifications (module 10) |

## 7. Notifications

| Event | Channel |
|-------|---------|
| Onboarding completed | Email confirmation to student + parent |

## 8. MVP vs Phase 2

| MVP | Phase 2 |
|-----|---------|
| 4-step wizard | Save progress per step |
| Single school focus | Multi-university picker |

## 9. Acceptance criteria

- [ ] Incomplete onboarding blocks dashboard and other nav items.
- [ ] All required fields validated before completion.
- [ ] Profile photo upload optional with size/type limits.
- [ ] My Profile shows all sections editable post-onboarding.
- [ ] Address confirmation warnings display before shipment (linked to module 07).
