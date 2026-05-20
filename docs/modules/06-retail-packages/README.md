# Module 06 — Retail Packages

**FSD:** §8  
**Priority:** P0 — **Critical MVP**  
**Dependencies:** [02-onboarding-profile](../02-onboarding-profile/), [17-new-life-id](../17-new-life-id/)

## Summary

Students log external retailer shipments; warehouse receives and delivers to dorm. Manual status updates in MVP.

## Current codebase

| Portal | Route | View |
|--------|-------|------|
| Student | `/student/retail-packages` | `student.retail-packages` |
| Admin | `/admin/retail-packages` | `admin.retail-packages` |

Both use DataTables with mock rows.

## Open questions

| ID | Question |
|----|----------|
| Q1 | Final cap per tier (default: 10 active) |
| Q2 | Carrier tracking link URL pattern per retailer |
