# Delivery Confirmation — Technical Notes

## Service

`DeliveryConfirmationService::complete($delivery, $photos, $notes)`:
1. Store photos via ContainerPhotoService
2. Update delivery.status = completed
3. Update container.status = delivered_to_dorm
4. Dispatch `DeliveryCompleted` event → notifications

## Mobile UX

- Large touch targets on delivery complete form
- Camera capture via `<input type="file" accept="image/*" capture="environment">`

## Tests

- Complete without photo fails when required
- Event triggers notification job
- Student can view photos after complete
