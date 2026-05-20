# Reporting & Exports — Technical Notes

## Service

`ReportExportService` with strategy per report type:

| Class | Report |
|-------|--------|
| `CustomerListExport` | Customers |
| `DeliveryManifestExport` | Deliveries |
| `RetailPackageListExport` | Retail |
| `MoveInReadinessExport` | Readiness |
| `DormReportExport` | By dorm |

## Implementation

- Laravel Excel (`maatwebsite/excel`) or native CSV streaming
- Streamed response for memory efficiency: `response()->streamDownload()`

## Queue

`GenerateReportJob` for > 1000 rows → store on S3 → notify admin with download link (24h expiry)

## Permissions

`export-reports` ability on admin users (MVP: all admins)

## Tests

- CSV row count matches query
- Filters applied correctly
- UTF-8 BOM present

## Cross-links

Deferred features: [00-platform-overview.md](../../00-platform-overview.md) §6
