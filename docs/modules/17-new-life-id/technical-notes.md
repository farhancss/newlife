# New Life ID — Technical Notes

## Implementation

| Component | Detail |
|-----------|--------|
| Column | `student_profiles.new_life_id` (unique, indexed) |
| Generation | `NewLifeIdGenerator` service on profile create |
| Display | Accessor on `StudentProfile` model |

## Migration

`database/migrations/xxxx_create_student_profiles_table.php` (or add to profiles table).

## Generator options

1. Sequential: `NL-{auto_increment}`
2. Encoded: `NL-{year}{sequence}`

Recommend sequential with gap-free sequence table for warehouse simplicity.

## Tests

- Uniqueness constraint
- Generated on provisioning
- Search by ID returns correct student
