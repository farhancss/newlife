# Photo Uploads — Technical Notes

## Model

`ContainerPhoto`: `container_id`, `uploaded_by_user_id`, `type` (exterior, hub_intake, dorm_delivery), `path`, `customer_visible`, `created_at`

## Storage

- Disk: `s3` or `public` per env
- Path: `containers/{container_id}/{uuid}.jpg`
- Use Laravel Storage + intervention/image optional resize

## Service

`ContainerPhotoService`: upload, delete, list, enforce limits

## Migration

`database/migrations/xxxx_create_container_photos_table.php`

## Tests

- Cap enforcement
- Authorization student vs admin
- File type validation
