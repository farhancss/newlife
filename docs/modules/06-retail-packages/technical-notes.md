# Retail Packages — Technical Notes

## Model

`RetailPackage`:
- `student_profile_id`, `retailer`, `description`, `tracking_number`, `estimated_arrival`, `notes`, `status`, timestamps

## Enum

`RetailPackageStatus`: logged, in_transit, received_at_hub, staged_for_delivery, delivered_to_dorm

## Services

| Service | Responsibility |
|---------|----------------|
| `RetailPackageService` | CRUD, cap check, status transitions |
| `CarrierLinkBuilder` | Build external tracking URLs |

## Policies

- Student: own packages only
- Admin: all packages

## Migrations

`database/migrations/xxxx_create_retail_packages_table.php`

## Config

`config/portal.php`:
- `retail_package_active_cap` => 10
- `retailers` => array list

## Tests

- Cap enforcement
- Status transition validation
- Student cannot edit delivered package
- Admin override with audit
