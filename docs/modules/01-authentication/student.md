# Authentication — Student

**FSD:** §4.1, §4.2

## 1. Purpose

Allow students who purchased via Squarespace to securely access the portal after account provisioning.

## 2. User stories

| ID | Story |
|----|-------|
| S-A1 | As a student, I receive an invitation email after purchase so I know how to log in. |
| S-A2 | As a student, I must reset my temporary password on first login. |
| S-A3 | As a student, I can log in on mobile or desktop. |
| S-A4 | As a student, I can reset a forgotten password via email. |
| S-A5 | As a student, I cannot create an account from the portal (no signup page). |

## 3. Screens and routes

| Screen | Route | View (current) | Target |
|--------|-------|----------------|--------|
| Login | `GET /login` | `pages.portal.login` | Keep |
| Login submit | `POST /login` | Closure | `AuthController` |
| Logout | `GET /logout` | Closure | `AuthController` |
| Forgot password | `GET /forgot-password` | **Not built** | New |
| Reset password | `GET /reset-password/{token}` | **Not built** | New |
| Force password change | `GET /student/change-password` (first login) | `common.change-password` | Extend |

## 4. Registration workflow (system)

1. Customer completes purchase on Squarespace.
2. Customer data synced to portal (webhook or import).
3. Backend creates `User` + `StudentProfile` stub.
4. Temporary password generated; invitation email sent (branded).
5. Student logs in → forced password reset.
6. Redirect to onboarding (module 02) until complete.

## 5. Business rules

| Rule | Detail |
|------|--------|
| No self-registration | Signup routes disabled; 404 or redirect to login |
| Onboarding gate | After login, if `onboarding_completed_at` is null → onboarding only |
| Session | Standard Laravel session; regenerate on login |
| Role | `student` role only for customer accounts |

## 6. Notifications triggered

| Event | Channel |
|-------|---------|
| Account created | Email (invitation + temp password) |
| Password reset requested | Email (reset link) |

## 7. MVP vs Phase 2

| MVP | Phase 2 |
|-----|---------|
| Email/password auth | SSO, magic link |
| Invitation + reset emails | Email template CMS |
| Session management | Device management |

## 8. Acceptance criteria

- [ ] Student cannot access `/student/*` without authentication.
- [ ] Invalid credentials show error on login page.
- [ ] Successful login redirects to onboarding if incomplete, else dashboard.
- [ ] Forgot password sends email with time-limited token.
- [ ] Reset password updates credential and invalidates token.
- [ ] First-login temp password forces change before portal access.
- [ ] Logout clears session and redirects to login.
- [ ] No public registration route exists.
