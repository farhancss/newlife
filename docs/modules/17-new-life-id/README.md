# Module 17 — New Life ID

**FSD:** §20  
**Priority:** P0 (cross-cutting)  
**Dependencies:** [01-authentication](../01-authentication/)  
**Used by:** [06-retail-packages](../06-retail-packages/), [07-containers-move](../07-containers-move/), [13-student-management](../13-student-management/), warehouse ops

## Summary

Every customer receives a unique **New Life ID** used for warehouse intake, package sorting, support tickets, and operational tracking. Displayed prominently in student portal and all admin student views.

## Format (proposed MVP)

- Pattern: `NL-` + numeric or alphanumeric (e.g. `NL-1001842`)
- Immutable after assignment
- Unique index in database

## Open questions

| ID | Question |
|----|----------|
| Q1 | Final ID format and generation algorithm? |
| Q2 | Display on shipping labels (external process)? |
