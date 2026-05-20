# Module 18 — Squarespace Integration

**Priority:** P0  
**Dependencies:** [01-authentication](../01-authentication/), [02-onboarding-profile](../02-onboarding-profile/)

## Summary

Syncs Squarespace Commerce contacts, orders, and addresses into the campus portal. Provisions student accounts on `contact.create`, enriches package tier and subscriptions on `order.create` / `order.update`, and gates portal access until password reset and onboarding are complete.

## Password policy

Squarespace does **not** expose customer passwords. The portal:

1. Creates a random temporary password on new account provisioning.
2. Sends an invitation email with login URL and temp password.
3. Sets `users.must_reset_password = true`.
4. Forces `/student/change-password` before other routes.
5. Redirects to `/student/onboarding` until `onboarding_completed_at` is set.

## Webhook endpoint

| Method | Path | Auth |
|--------|------|------|
| POST | `/api/webhooks/squarespace` | `Squarespace-Signature` HMAC header |

### Subscribed topics

| Topic | Action |
|-------|--------|
| `contact.create` | Create/update `User` + `StudentProfile` stub; send invitation if new |
| `contact.update` | Merge contact fields |
| `address.create` / `address.update` | Upsert home `shipping_addresses` |
| `order.create` | Fetch order via API (or embedded in simulation); set `package_tier`, `student_subscriptions` |
| `order.update` | Refresh subscription status |

## Field mapping

| Squarespace | Portal |
|-------------|--------|
| `contact.contactId` | `users.squarespace_contact_id`, `student_profiles.squarespace_contact_id` |
| `primaryEmail.value` | `users.email` |
| `firstName` / `lastName` | `student_profiles.first_name`, `last_name` |
| `defaultShippingAddress.address` | `shipping_addresses` (type `home`) |
| Order `lineItems[].sku` | `student_profiles.package_tier` via `config/squarespace.php` SKU map |
| Order `id` | `student_subscriptions.squarespace_order_id` |

**Not synced:** Module 06 retail packages (Amazon/Walmart tracking) — separate student-entered data.

## SKU → package tier

Configure in `config/squarespace.php`:

```php
'sku_tier_map' => [
    'SQSP-BASIC' => 'basic',
    'SQSP-STANDARD' => 'standard',
    'SQSP-PREMIUM' => 'premium',
],
```

## Dev simulation API

| Method | Path | Guard |
|--------|------|-------|
| POST | `/api/dev/squarespace/simulate` | `APP_ENV` local/testing/staging + `Authorization: Bearer {SQUARESPACE_SIMULATION_TOKEN}` |

```bash
curl -X POST http://localhost:8000/api/dev/squarespace/simulate \
  -H "Authorization: Bearer local-dev-sim-token" \
  -H "Content-Type: application/json" \
  -d '{"topic":"contact.create","payload":{...}}'
```

### Artisan helpers

The webhook job and the invitation mailable are both queued, so locally you either need a running worker (`php artisan queue:work`) or the `--sync` flag below.

```bash
# Simulate a webhook (queued — requires queue:work)
php artisan squarespace:simulate contact.create --email=test@example.com

# Simulate a webhook AND process the job + email inline (no worker needed)
php artisan squarespace:simulate contact.create --email=test@example.com --sync
```

For ops workflows that need to invite a real student before the production webhook is wired up, use the dedicated command. It always runs synchronously and prints the temporary password:

```bash
php artisan portal:invite-student student@example.com \
    --first-name=Jane --last-name=Doe
```

| Option | Default | Notes |
|--------|---------|-------|
| `--first-name=` | email local-part | Used in the invitation email |
| `--last-name=` | empty | |
| `--contact-id=` | `null` | Optional external id (Squarespace, CRM) for future webhook linking |
| `--no-email` | off | Provision the account silently (no invitation email) |

The command outputs the New Life ID, status, and the temporary password so it can be shared if email delivery fails.

Fixtures: `tests/fixtures/squarespace/*.json`

## Environment variables

See `.env.example`: `SQUARESPACE_CLIENT_ID`, `SQUARESPACE_CLIENT_SECRET`, `SQUARESPACE_WEBHOOK_SECRET`, `SQUARESPACE_WEBSITE_ID`, `SQUARESPACE_SIMULATION_TOKEN`, `SQUARESPACE_SKIP_SIGNATURE` (testing only).

## Migrations

All schema changes in `database/migrations/`:

- `2026_05_19_000002_extend_users_for_squarespace.php`
- `2026_05_19_000003_create_squarespace_webhook_events_table.php`
- `2026_05_19_000004_create_student_profiles_and_related_tables.php`
- `2026_05_19_000005_create_student_subscriptions_table.php`
- `2026_05_19_000006_create_squarespace_address_entries_table.php`

## Related modules

- [01-authentication](../01-authentication/) — login, temp password, invitation email
- [02-onboarding-profile](../02-onboarding-profile/) — onboarding wizard and profile tables
- [13-student-management](../13-student-management/) — admin views package tier from profile
