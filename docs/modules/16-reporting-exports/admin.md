# Reporting & Exports — Admin

**FSD:** §19

## 1. Purpose

Export operational data for warehouse, delivery teams, and management.

## 2. Export types (MVP = CSV)

| Report | Contents | Filters |
|--------|----------|---------|
| Customer list | Profile, NL ID, tier, status, housing | Date, university, status |
| Delivery manifest | Deliveries by date, address, window | Date range |
| Retail package list | All packages with tracking, status | Status, date |
| Move-in readiness | Students ready / not ready for delivery | Move-in date, dorm |
| Dorm-specific | Students grouped by residence hall | Building, date |

## 3. UI (`/admin/reports`)

| Feature | Detail |
|---------|--------|
| Report picker | Dropdown of report types |
| Filters | Dynamic per report |
| Preview | First 25 rows in browser |
| Download | CSV file generation |
| Async | Large exports via queued job + email link |

## 4. CSV standards

- UTF-8 with BOM for Excel
- Headers in first row
- ISO 8601 dates
- Filename: `{report}_{date}.csv`

## 5. Acceptance criteria

- [ ] Each report type generates valid CSV matching filters.
- [ ] Export completes < 30s for 5k rows (or async job).
- [ ] PII included only for authorized admin role.
- [ ] Export action logged in audit log.
- [ ] Empty result set returns headers-only file with message.

## 6. Phase 2 (deferred)

- Excel XLSX format
- Scheduled recurring exports
- Analytics charts (see FSD §21.3)
