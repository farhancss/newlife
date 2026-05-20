# Deadline Center — Technical Notes

## Models

| Model | Fields |
|-------|--------|
| `DeadlineTemplate` | `type`, `season`, `due_at`, `description` |
| `StudentDeadline` | `student_profile_id`, `type`, `due_at`, `completed_at`, `override_reason` |

## Service

`DeadlineService::forStudent($profile)` — merge templates + overrides + computed deadlines (e.g. from move-in date).

## Scheduled job

Daily: queue reminder notifications for deadlines in 7/3/1 day windows.

## Migration

`database/migrations/xxxx_create_deadline_tables.php`
