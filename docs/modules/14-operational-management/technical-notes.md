# Operational Management — Technical Notes

## Audit model

`AuditLog`: `user_id`, `auditable_type`, `auditable_id`, `action`, `old_values`, `new_values`, `reason`, `created_at`

Use Laravel model observers or explicit `AuditService::record()`.

## Trait

`Auditable` trait on Container, RetailPackage, StudentProfile.

## Permissions (Phase 2)

| Role | Overrides |
|------|-----------|
| Operator | Status updates |
| Manager | Deletes + flags |
| Super admin | All |

MVP: single `admin` role with full access.

## Service

`ManualOverrideService` centralizes dangerous operations with reason validation.

## Tests

- Override without reason fails for delete
- Audit row created on status change
- Notification fired on override when enabled
