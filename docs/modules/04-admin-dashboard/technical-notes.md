# Admin Dashboard — Technical Notes

## Service

`AdminDashboardService::getMetrics()` returns aggregated counts.

## Activity feed

`activity_log` table or Laravel model events:
- student registered
- package received
- container status changed
- delivery scheduled

## Performance

- Single query per widget or one optimized aggregate query
- Index on `containers.status`, `retail_packages.status`, `student_profiles.onboarding_completed_at`

## Tests

- Widget counts for seeded fixture data
- Activity feed ordering (newest first)
