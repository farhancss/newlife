# Module 10 — Notifications

**FSD:** §11  
**Priority:** P1  
**Dependencies:** All workflow modules

## Summary

SMS, email, and in-app notifications for account, shipment, retail, and deadline events. User preference management.

## Current codebase

| Portal | Route |
|--------|-------|
| Student | `/student/notifications` (inbox + mark read/all) |
| Student | `/student/settings` (channel + parent CC preferences) |
| Admin | `/admin/notifications` (delivery log, filters, resend) |

### Implementation

A unified pipeline funnels every domain event through `App\Services\NotificationService`. Each
event creates an in-app `PortalNotification` (which doubles as the student inbox, the admin
delivery log, and the email audit trail) and dispatches the enabled channels.

- **Channels:** Email (`App\Mail\PortalNotificationMail`, branded generic template) and in-app are
  live. SMS is preference-ready (`notification_preferences.sms_enabled` / `sms_number`) but the
  sending driver (Twilio) is not yet wired — toggles persist for a future driver.
- **Preferences:** `App\Models\NotificationPreference` per user — email on, sms on, sms number,
  parent CC on. Respected by the send pipeline. Parent/guardian is CC'd on shipment & retail
  events when enabled.
- **Events hooked:** container milestones (`shipped_to_home`, `delivered_to_home`,
  `pickup_scheduled`, `out_for_delivery`, `delivered_to_dorm`) via `ContainerWorkflowService`;
  retail statuses (`received_at_hub`, `staged_for_delivery`, `delivered_to_dorm`) via
  `RetailPackageService`.
- **Delivery status:** `email_status` (`sent` / `queued` / `failed` / `skipped` / `none`) with
  attempt count powers the admin log and the resend action.
- **Sidebar badge:** unread count shared by `StudentProfileCompletionComposer`.

Key classes: `App\Services\NotificationService`, `App\Models\PortalNotification`,
`App\Models\NotificationPreference`, `App\Enums\NotificationCategory`,
`App\Mail\PortalNotificationMail`.

### Deferred (out of MVP scope here)

- SMS delivery driver (Twilio/SNS).
- Deadline reminders (7/3/1 day) — needs a scheduled command/cron.
- Admin manual one-off send (in the FSD/admin spec, intentionally omitted per product decision).
