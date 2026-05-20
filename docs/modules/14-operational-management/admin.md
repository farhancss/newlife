# Operational Management — Admin

**FSD:** §16.1–§16.3

## 1. Purpose

Operations requires flexibility to correct data, override statuses, and handle edge cases without developer intervention.

## 2. Manual override capabilities

| Area | Actions | Location |
|------|---------|----------|
| Profile | Edit any field | Student detail |
| Container | Set any status, edit tracking | Container admin |
| Retail package | Add, remove, any status | Retail admin |
| Delivery | Reschedule, cancel, complete | Deliveries admin |
| Notifications | Resend any event | Notification log |
| Flags | Add/clear operational flags | Student detail |
| Deadlines | Per-student extension | Deadline admin |

## 3. Retail package management (§16.1)

- View all logged packages
- Update statuses through intake workflow
- Search tracking numbers
- Track delivery readiness

## 4. Container management (§16.2)

- View containers
- Update statuses and tracking
- Mark delivered at each stage
- Manage shipment workflow

## 5. Audit requirements

Every override must log:
- Admin user ID
- Timestamp
- Previous value → new value
- Reason (required text field for destructive actions)

## 6. Warehouse coordination (process)

Document operational SOP (not software):
1. Intake scan by New Life ID
2. Match retail package tracking
3. Update status in portal
4. Stage for dorm delivery batch

## 7. Acceptance criteria

- [ ] All override actions write audit log.
- [ ] Destructive actions require reason.
- [ ] Override triggers appropriate student notification (configurable).
- [ ] Permissions: all admins or role-gated (MVP: all admins).
