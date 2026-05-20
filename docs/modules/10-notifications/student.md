# Notifications — Student

**FSD:** §11.1–§11.3

## 1. Purpose

Keep students and parents informed; allow channel preferences.

## 2. Channels

| Channel | MVP |
|---------|-----|
| Email | Yes |
| SMS | Yes (Twilio or similar) |
| In-app | Yes |

## 3. Notification events (student-facing)

### Account
- Welcome / portal invitation
- Password reset

### Shipment
- Container shipped
- Container delivered to home
- Pickup reminders
- Return shipment updates
- Dorm delivery updates

### Retail
- Package received at hub
- Delivery scheduled
- Delivered to dorm

### Deadlines
- Upcoming deadline (7/3/1 day)
- Expiring/overdue deadline

## 4. Preferences (`/student/settings`)

| Preference | Default |
|------------|---------|
| Email enabled | On |
| SMS enabled | On |
| SMS number | From profile phone |
| Parent CC email | On for key events |

## 5. In-app inbox (`/student/notifications`)

| Feature | Detail |
|---------|--------|
| List | Title, body preview, timestamp, read/unread |
| Mark read | Single + mark all |
| Link | Deep link to relevant portal page |

## 6. Acceptance criteria

- [ ] Preferences persist and respected by send pipeline.
- [ ] In-app notifications created for each sent event.
- [ ] Unread count shown in sidebar badge.
- [ ] Parent receives email CC when configured.
