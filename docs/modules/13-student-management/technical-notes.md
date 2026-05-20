# Student Management — Technical Notes

## Models

| Model | Notes |
|-------|-------|
| `OperationalFlag` | `student_profile_id`, `type`, `set_by`, `set_at`, `cleared_at` |
| `ProfileNote` | From module 02 |

## Enum

`OperationalFlagType`: missing_information, vip, delivery_issue, late_shipment, payment_issue, escalation_needed

## Services

| Service | Responsibility |
|---------|----------------|
| `StudentSearchService` | Query builder for filters |
| `OperationalFlagService` | Add/clear flags |

## Controller

`Admin\StudentController`: index, show, update, flag, note

## Migrations

`database/migrations/xxxx_create_operational_flags_table.php`

## Tests

- Search by NL ID
- Filter by move-in date range
- Flag persistence and display on list
