# Student Dashboard

**FSD:** §5.1–§5.2

## 1. Purpose

Single view for move-in status, next actions, and confidence-building progress visibility.

## 2. User stories

| ID | Story |
|----|-------|
| S-D1 | As a student, I see my name and package tier on login. |
| S-D2 | As a student, I see where I am in the move-in timeline. |
| S-D3 | As a student, I see what I need to do next (action items). |
| S-D4 | As a student, I see upcoming deadlines at a glance. |
| S-D5 | As a student, I access retail, add-ons, and support quickly. |

## 3. Route

| Route | View |
|-------|------|
| `/student/dashboard` | `student.dashboard` |

## 4. Dashboard components

| Component | Data source | FSD ref |
|-----------|-------------|---------|
| Welcome | Profile + package tier | §5.2 |
| Progress tracker | Container/move workflow state | §5.2, §9.2 |
| Action items | Rules engine from profile/packages | §5.2 |
| Upcoming deadlines | Deadline service | §5.2, §7 |
| Latest notifications | Notification service (preview) | §5.2 |
| Retail package overview | Count by status | §5.2 |
| Add-on section | Add-on service | §5.2 |
| Quick links | Static routes | Existing UI |
| Support access | Link to support | §5.2 |

## 5. Progress tracker stages

Align with container workflow (module 07):

1. Reservation Confirmed  
2. Profile Completed  
3. Containers Preparing  
4. Containers Shipped to Home  
5. Containers Delivered to Home  
6. Customer Packing  
7. Return Shipment In Transit  
8. Received at New Life Hub  
9. Stored at Receiving Hub  
10. Scheduled for Dorm Delivery  
11. Out for Delivery  
12. Delivered to Dorm  

*UI currently shows 6 steps — expand to full FSD list or map substeps.*

## 6. Action items (examples)

| Condition | Action |
|-----------|--------|
| Onboarding incomplete | Complete profile (link) |
| No retail packages | Add retail packages |
| Move-in window not set | Select move-in window |
| Shipment not triggered | Confirm address & trigger shipment |
| Pending add-on payment | Complete payment (Squarespace link) |

## 7. Design principles

- Clean, modern, parent-friendly
- Mobile-first responsive
- New Life branding (brand colors in `app.css`)

## 8. MVP vs Phase 2

| MVP | Phase 2 |
|-----|---------|
| Static quick links + dynamic widgets | Personalized widget ordering |
| Preview notifications (5 items) | Full notification center embed |

## 9. Acceptance criteria

- [ ] Dashboard loads only when onboarding complete.
- [ ] Welcome shows correct name and package label.
- [ ] Progress tracker reflects actual workflow state from DB.
- [ ] Action items update when underlying tasks complete.
- [ ] Deadline snippets show next 3 urgent deadlines.
- [ ] Retail summary shows counts by status.
- [ ] All quick links navigate to correct routes.
- [ ] Layout responsive on mobile and desktop.
