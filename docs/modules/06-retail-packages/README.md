# Module 06 — Retail Packages

**FSD:** §8  
**Priority:** P0 — **Critical MVP**  
**Dependencies:** [02-onboarding-profile](../02-onboarding-profile/), [17-new-life-id](../17-new-life-id/)

## Summary

Students log external retailer shipments; warehouse receives and delivers to dorm. Manual status updates in MVP.

## Current codebase

| Portal | Route | Controller | View |
|--------|-------|------------|------|
| Student | `/student/retail-packages` (GET/POST/PUT/DELETE) | `StudentRetailPackageController` | `student.retail-packages` |
| Admin | `/admin/retail-packages` (GET/POST/PUT/DELETE) | `AdminRetailPackageController` | `admin.retail-packages` |

Backed by the `retail_packages` table with a `retail_package_status_histories` audit trail. Students CRUD their own packages within the active cap and edit lock; admins manage status across all students, add on behalf, and remove with a reason. Status changes write an audit entry and route through the unified notification pipeline (Module 10): `received_at_hub`, `staged_for_delivery`, and `delivered_to_dorm` create an in-app notification plus a branded email (respecting student preferences and parent CC).

Key classes: `App\Enums\RetailPackageStatus`, `App\Services\RetailPackageService`, `App\Services\CarrierLinkBuilder`, `App\Policies\RetailPackagePolicy`, `App\Services\NotificationService`.

## Resolved questions

| ID | Question | Resolution |
|----|----------|------------|
| Q1 | Final cap per tier (default: 10 active) | Flat configurable active cap via `config('portal.retail_packages.active_cap')` (default 10). |
| Q2 | Carrier tracking link URL pattern per retailer | `CarrierLinkBuilder` maps FedEx/UPS/USPS/DHL/Amazon to public tracking deep links; unknown retailers fall back to a tracking search. |
