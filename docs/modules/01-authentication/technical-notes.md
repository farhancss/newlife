# Authentication — Technical Notes

## Services

| Service | Responsibility |
|---------|----------------|
| `AccountProvisioningService` | Create user from Squarespace payload, set status to `INVITED` |
| `InvitationMailService` | Send branded invitation email |
| `UserStatusService` | Transition `users.status` between lifecycle states |
| `PasswordResetService` | Token generation, validation, reset (Phase 2 — forgot-password flow) |

## Models / tables

| Table | Key fields |
|-------|------------|
| `users` | `email`, `password`, `role`, `status`, `must_reset_password`, `password_changed_at`, `squarespace_contact_id` |
| `student_profiles` | `onboarding_step`, `onboarding_completed_at` (drives completion state) |

Migration path: `database/migrations/` — extend `users` as needed.

## Account status lifecycle

```
INVITED ──▶ INCOMPLETE ──▶ ACTIVE
   │                          │
   ▼                          ▼
SUSPENDED ◀──────────── (admin action — Phase C)
```

| Status | Set by | Meaning |
|--------|--------|---------|
| `INVITED` | `AccountProvisioningService` on new user creation | Webhook received, invitation email queued, user must change password |
| `INCOMPLETE` | `ChangePasswordController` first reset | Password set; profile sections still missing |
| `ACTIVE` | `ProfileCompletionService::syncCompletionStatus()` when all sections complete | Full portal access granted |
| `SUSPENDED` | Admin action (Phase C) | Locked out at login and mid-session via `EnsureAccountActive` |

## Squarespace integration (MVP)

| Approach | MVP recommendation |
|----------|-------------------|
| Webhook | `POST /api/webhooks/squarespace` (signed) |
| Batch | Nightly CSV import job |

**Minimum payload fields:** email, first name, last name, package SKU/tier, order ID, phone (optional).

## Middleware

| Middleware | Action |
|------------|--------|
| `EnsureUserHasRole` (`role:`) | Role gate |
| `EnsurePasswordChanged` (`password.changed`) | Redirect to change-password if `must_reset_password` |
| `EnsureOnboardingComplete` (`onboarding.complete`) | Redirect to profile if not complete |
| `EnsureAccountActive` (`account.active`) | Log out users whose status becomes `SUSPENDED` mid-session |

## Customer emails

All transactional emails share one branded HTML layout — `<x-email.layout>` at
`resources/views/components/email/`. The layout is table-based for Outlook/Gmail
compatibility, responsive on mobile, and uses the brand color (`#0827be`),
the `NL` mark, and the footer pulled from `config/brand.php`.

| Email | Trigger | View |
|-------|---------|------|
| `StudentInvitationMail` | New user provisioned with temp password | `emails.student-invitation` |
| `PasswordChangedMail` | Password updated (flagged for first-reset vs subsequent change) | `emails.password-changed` |
| `OnboardingCompleteMail` | Profile first becomes 100% complete | `emails.onboarding-complete` |

### Building a new email

1. Render with the shared layout — pass `:title`, `:preheader`, `:heading`:

    ```blade
    <x-email.layout title="..." preheader="..." heading="...">
        <p>Body copy.</p>
        <x-email.info-box label="Key value">…</x-email.info-box>
        <x-email.button :href="$ctaUrl">Call to action</x-email.button>
    </x-email.layout>
    ```

2. Available components: `x-email.layout`, `x-email.button` (variants:
   `primary`, `secondary`), `x-email.info-box`.

3. Brand identity (name, mark, tagline, support contact, colors) is read from
   `config/brand.php` — override via `.env` (`BRAND_NAME`, `BRAND_MARK`,
   `BRAND_TAGLINE`, `BRAND_SUPPORT_EMAIL`, `BRAND_SUPPORT_PHONE`,
   `BRAND_ADDRESS`, `BRAND_WEBSITE_URL`).

### Local preview

In `local` env only, browse to:

- `/dev/email-preview/student-invitation`
- `/dev/email-preview/password-changed-first`
- `/dev/email-preview/password-changed`
- `/dev/email-preview/onboarding-complete`

## Security

- Bcrypt password hashing (Laravel default)
- Login rate limit: 5 attempts / 60s per email+IP via `Illuminate\Support\Facades\RateLimiter`
- Change-password rate limit: route-level `throttle:10,1` (10/min)
- Suspended-account lockout at login and via `account.active` middleware
- CSRF on all forms

## Tests (Pest)

- Login success/failure per role (`StudentGatesTest`)
- Onboarding redirect when incomplete (`StudentGatesTest`)
- Profile completion + section navigation (`ProfileCompletionTest`)
- Status lifecycle: INVITED → INCOMPLETE → ACTIVE, suspension block, login rate limit (`UserStatusLifecycleTest`)
- Webhook signature + idempotency (`Squarespace/WebhookTest`)
- Role middleware blocks cross-portal access
