# Add-Ons — Technical Notes

## Catalog (hardcoded)

Available add-ons live in `config/addons.php` under `catalog` (slug, name,
`price_cents`, description, icon, `url`). Pricing/title/description were captured
from the live Squarespace storefront. No DB-backed catalog yet.

## Model

| Model | Fields |
|-------|--------|
| `StudentAddOn` | `student_profile_id`, `add_on_slug`, `name`, `price_cents`, `squarespace_url`, `status`, `container_id`, `squarespace_order_id`, `requested_at`, `activated_at`, `activated_by_user_id` |

Name + price + URL are snapshotted onto the row at purchase time so history is
stable even if the catalog changes.

## Trackable add-on container (Additional Container)

The `additional-container` add-on (`StudentAddOn::ADDITIONAL_CONTAINER_SLUG`) is
special: fulfilling it provisions a `Container` (via
`ContainerWorkflowService::createForStudent(..., Container::SOURCE_ADD_ON)`) and
links it through `student_add_ons.container_id`. That container follows the full
12-status move journey, surfaced on the student add-on detail page
(`/student/add-ons/purchases/{id}`) and managed by admins from **Admin →
Containers** (badged "Add-on").

`containers.source` (`move` | `add_on`) separates the package move shipment from
add-on containers. The "primary"/move-shipment lookups
(`ContainerWorkflowService::primaryContainer`/`ensureMoveShipment`,
`StudentPackageService::containersAssigned`, `MoveProgressService`, the student
My Move page) all scope to `source = move` so add-on containers never count
against the package allowance or hijack the main move view.

## Purchasing (temporary command)

There is no in-portal payment-pending flow. The "Add-On" buttons on the catalog
cards are plain external links to the Squarespace product pages. Purchases are
recorded (as **active**) only via the temporary console command:

```
php artisan portal:buy-addon {student} {slug}
```

`{student}` is an email or New Life ID. `AddOnService::purchase($profile, $addOn,
$actor)` creates the active `StudentAddOn` and, for the Additional Container
add-on, provisions the linked `Container` in one transaction.

## Status (`App\Enums\AddOnStatus`)

`active` / `cancelled`. (No payment-pending state — purchases are recorded as
active.)

## Service

`AddOnService::catalog()`, `findInCatalog($slug)`,
`purchase($profile, $addOn, $actor)` (creates an active `StudentAddOn`, provisions
the container for Additional Container), `purchasesFor($profile)`.

## Routes

Student (`StudentAddOnController`):

| Method | Route | Name |
|--------|-------|------|
| GET | `/student/add-ons` | `student.add-ons` |
| GET | `/student/add-ons/purchases/{studentAddOn}` | `student.add-ons.show` |

Admin (`AdminAddOnController`): `GET /admin/add-ons` (`admin.add-ons`) — stats +
purchases listing.

Catalog "Add-On" buttons link straight to Squarespace; there is no in-portal
purchase endpoint.

## Migration

`database/migrations/2026_06_19_000001_create_student_add_ons_table.php`

## Phase 2

- Squarespace webhook → auto-activate (set `active`, `squarespace_order_id`)
- Admin activation UI + entitlement application
- See [00-platform-overview.md](../../00-platform-overview.md) deferred list
