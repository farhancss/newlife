# Onboarding & Profile — Technical Notes

## Models (proposed)

| Model | Table | Notes |
|-------|-------|-------|
| `StudentProfile` | `student_profiles` | 1:1 with `users` |
| `ParentGuardian` | `parent_guardians` | 1:1 or embedded JSON |
| `ShippingAddress` | `shipping_addresses` | Home shipping |
| `HousingInfo` | `housing_infos` | Dorm details |
| `ProfileNote` | `profile_notes` | Admin internal notes |

## Services

| Service | Methods |
|---------|---------|
| `OnboardingService` | `complete()`, `getProgress()`, `isComplete()` |
| `StudentProfileService` | `update()`, `confirmAddress()` |

## Migrations

All in `database/migrations/`:
- `create_student_profiles_table`
- `create_parent_guardians_table` (or columns on profile)
- `create_profile_notes_table`

## Enums

- `IncomingYear` or string field
- `OnboardingStep` for wizard state

## File storage

- Profile photos: `storage/app/public/profile-photos/{user_id}`

## Tests

- Onboarding middleware redirect
- Required field validation per step
- Admin edit updates student-visible data
- Notes not visible to student
