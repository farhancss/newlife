# Containers & Move — Technical Notes

## Models

| Model | Key fields |
|-------|------------|
| `Container` | `code`, `student_profile_id`, `size`, `status`, `location`, `outbound_tracking`, `return_tracking` |
| `ContainerStatusHistory` | audit trail |

## Enum

`ContainerStatus` — 12 states from FSD §9.2

## Services

| Service | Responsibility |
|---------|----------------|
| `ContainerWorkflowService` | Valid transitions, trigger shipment |
| `FedExLinkService` | Build tracking URLs |

## Student trigger

`POST /student/move-tracking/trigger-shipment`:
- Validates address_confirmed_at
- Sets status → label_generated or shipped_to_home
- Queues notifications

## Migrations

`database/migrations/xxxx_create_containers_table.php`
`database/migrations/xxxx_create_container_status_histories_table.php`

## Link to dashboard

`MoveProgressService` reads container status for progress tracker (module 03).

## Tests

- Shipment trigger preconditions
- Invalid status transition rejected
- Timeline order enforced
