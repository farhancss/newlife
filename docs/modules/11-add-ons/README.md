# Module 11 — Add-Ons

**FSD:** §12  
**Priority:** P2  
**Dependencies:** [01-authentication](../01-authentication/) (Squarespace external payment)

## Summary

Students browse add-ons and complete payment on Squarespace; admin manually activates after payment confirmation.

## Deferred (explicit)

Per FSD §21.2 — **in-portal payments are out of scope**. No Stripe/Apple Pay in portal.

## Current codebase

| Portal | Route | Status |
|--------|-------|--------|
| Student | `/student/add-ons` | Live — catalog cards (external links to Squarespace) + "My add-ons" listing |
| Student | `/student/add-ons/purchases/{studentAddOn}` | Add-on detail page (12-status journey for Additional Container) |
| Admin | `/admin/add-ons` | Live — stat cards + listing of all student purchases (search + status filter) |

Purchases are created **active** via `php artisan portal:buy-addon`. There is no
in-portal payment-pending state.

Catalog is hardcoded in `config/addons.php` from the live Squarespace storefront.

## Phase 2 enhancements

- Webhook from Squarespace auto-activates add-on
- Add-on catalog CMS in admin
