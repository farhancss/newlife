# Module 02 — Onboarding & Profile

**FSD:** §6, §3.2 (parent/guardian)  
**Priority:** P0  
**Dependencies:** [01-authentication](../01-authentication/), [17-new-life-id](../17-new-life-id/)  
**Blocks:** All student portal features

## Summary

Multi-step onboarding gate before portal access. Captures student, parent/guardian, home shipping, and housing information. Profile editable post-onboarding via My Profile; admin can view/edit and add notes.

## Current codebase

| Item | Status |
|------|--------|
| Profile page | `student/profile.blade.php` (static UI) |
| Route | `/student/profile` |

## Open questions

| ID | Question |
|----|----------|
| Q1 | Final required field list (FSD §23) |
| Q2 | Schools/universities list — static or API? |
| Q3 | Profile photo max size and storage |
