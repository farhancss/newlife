# Module 11 — Add-Ons

**FSD:** §12  
**Priority:** P2  
**Dependencies:** [01-authentication](../01-authentication/) (Squarespace external payment)

## Summary

Students browse add-ons and complete payment on Squarespace; admin manually activates after payment confirmation.

## Deferred (explicit)

Per FSD §21.2 — **in-portal payments are out of scope**. No Stripe/Apple Pay in portal.

## Current codebase

| Portal | Route |
|--------|-------|
| Student | `/student/add-ons` |
| Admin | `/admin/add-ons` |

Both UI shells.

## Phase 2 enhancements

- Webhook from Squarespace auto-activates add-on
- Add-on catalog CMS in admin
