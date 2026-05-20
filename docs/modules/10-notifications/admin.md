# Notifications — Admin

**FSD:** §11, §16.3

## 1. Purpose

View notification history, resend failed messages, manual notifications to student.

## 2. Features

| Feature | MVP |
|---------|-----|
| Notification log | Per student + global |
| Resend | Manual resend button |
| Template preview | View email/SMS body |
| Manual send | Admin composes one-off message |
| Delivery status | Sent, failed, queued |

## 3. Route

`/admin/notifications`

## 4. Acceptance criteria

- [ ] Admin sees delivery log with channel and status.
- [ ] Resend creates new attempt with audit.
- [ ] Manual send respects student preferences (override option with warning).
