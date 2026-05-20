# Deliveries — Technical Notes

## Model

`Delivery`: `container_id`, `scheduled_date`, `window_start`, `window_end`, `status`, `completed_at`, `notes`

## Service

`DeliverySchedulingService`: create, reschedule, complete → triggers container status + notifications

## Migration

`database/migrations/xxxx_create_deliveries_table.php`

## Tests

- Schedule delivery updates container to scheduled_for_dorm_delivery
- Complete delivery sets delivered_to_dorm
