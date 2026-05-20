# Add-Ons — Technical Notes

## Models

| Model | Fields |
|-------|--------|
| `AddOnCatalog` | `slug`, `name`, `description`, `squarespace_url`, `active` |
| `StudentAddOn` | `student_profile_id`, `catalog_id`, `status`, `squarespace_order_id`, `activated_at`, `activated_by` |

## Config

Squarespace URLs per add-on in `config/addons.php` or database catalog.

## Service

`AddOnService::request()`, `activate()`, `applyEntitlements()`

## Phase 2

- Squarespace webhook → auto-activate
- See [00-platform-overview.md](../../00-platform-overview.md) deferred list

## Migration

`database/migrations/xxxx_create_add_ons_tables.php`
