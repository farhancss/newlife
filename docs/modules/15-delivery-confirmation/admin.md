# Delivery Confirmation — Admin

**FSD:** §18.1

## 1. Purpose

Field staff confirms physical delivery with photo evidence.

## 2. User stories

| ID | Story |
|----|-------|
| A-DC1 | As staff, I mark delivery complete from mobile-friendly UI. |
| A-DC2 | As staff, I upload dorm delivery photos at completion. |
| A-DC3 | As staff, I add placement notes (e.g. "left at RA desk"). |

## 3. Workflow

1. Open delivery from `/admin/deliveries` or daily manifest
2. Tap "Complete delivery"
3. Upload 1+ photos (required configurable)
4. Enter optional notes
5. Submit → updates delivery, container, sends notification

## 4. Photo types

| Type | Required MVP |
|------|--------------|
| Dorm delivery | Yes |
| Package placement | Recommended |

Uses `ContainerPhoto` from module 09 with `type=dorm_delivery`.

## 5. Acceptance criteria

- [ ] Completion requires photo when configured.
- [ ] Container status advances to delivered_to_dorm.
- [ ] Student notification queued immediately.
- [ ] Mobile UI usable on phone browser.
