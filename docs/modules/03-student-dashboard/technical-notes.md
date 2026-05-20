# Student Dashboard — Technical Notes

## Controller / service

| Class | Responsibility |
|-------|----------------|
| `StudentDashboardController` | Aggregate dashboard DTO |
| `DashboardService` | Build widgets from domain services |
| `ActionItemService` | Compute pending actions |
| `MoveProgressService` | Map container status → tracker step |

## DTO structure (example)

```php
StudentDashboardData {
    welcome: WelcomeCardData,
    progress: ProgressTrackerData,
    actionItems: ActionItemData[],
    deadlines: DeadlineSummaryData[],
    notifications: NotificationPreviewData[],
    retailSummary: RetailSummaryData,
    addOnSummary: AddOnSummaryData,
}
```

## Caching

- Optional short TTL cache per user for dashboard aggregate (invalidate on status change).

## Tests

- Dashboard 200 for complete onboarding student
- Redirect when onboarding incomplete
- Progress step matches container status
- Action items include expected tasks for fixture state
