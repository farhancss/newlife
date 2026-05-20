# Module 01 — Authentication

**FSD:** §4  
**Priority:** P0  
**Dependencies:** None (foundation)  
**Blocks:** [02-onboarding-profile](../02-onboarding-profile/), all portal modules

## Summary

Email-based authentication with no public self-registration. Accounts are provisioned after Squarespace purchase; students receive invitation email with temporary password and must reset password on first login.

## Current codebase

| Item | Status |
|------|--------|
| Login page | `resources/views/pages/portal/login.blade.php` |
| Login/logout routes | `routes/web.php` (`login`, `login.submit`, `logout`) |
| Role redirect | `UserRole` enum → student or admin dashboard |
| Demo users | Seeder (`student@demo.com`, `admin@demo.com`) |

## Open questions

| ID | Question |
|----|----------|
| Q1 | **Resolved:** Webhook-first — `contact.create` stubs account, `order.create` enriches package/subscription. See [18-squarespace-integration](../18-squarespace-integration/). |
| Q2 | Invitation email provider (SMTP, SendGrid, etc.)? |
| Q3 | Password policy (length, complexity)? |

## Related modules

- [17-new-life-id](../17-new-life-id/) — assigned at account creation
- [02-onboarding-profile](../02-onboarding-profile/) — gate after first login
