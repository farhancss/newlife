# Authentication — Admin

**FSD:** §4.2 (operations team)

## 1. Purpose

Internal users authenticate with the same email/password stack; accounts are created by system administrators (not Squarespace sync).

## 2. User stories

| ID | Story |
|----|-------|
| A-A1 | As an admin, I log in and land on the admin dashboard. |
| A-A2 | As an admin, I cannot access student-only routes. |
| A-A3 | As a super-admin, I can provision admin accounts (MVP: manual DB/artisan). |

## 3. Screens and routes

| Screen | Route | Notes |
|--------|-------|-------|
| Login | `/login` | Shared with students; role-based redirect |
| Logout | `/logout` | Shared |
| Change password | `/admin/change-password` | `common.change-password` |

## 4. Business rules

| Rule | Detail |
|------|--------|
| Role | `admin` role required for `/admin/*` |
| Middleware | `auth` + `role:admin` (existing `EnsureUserHasRole`) |
| No self-registration | Admin accounts created internally only |

## 5. MVP vs Phase 2

| MVP | Phase 2 |
|-----|---------|
| Shared login page | Optional separate admin login URL |
| Manual admin provisioning | Admin user management UI |
| Single admin role | Granular permissions |

## 6. Acceptance criteria

- [ ] Admin user redirects to `/admin/dashboard` after login.
- [ ] Student user cannot access `/admin/*` (403).
- [ ] Admin user cannot access `/student/*` (403).
- [ ] Admin logout works identically to student logout.
