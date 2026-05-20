# Support & Help — Student

**FSD:** §13.1–§13.2

## 1. Purpose

Answer common questions and escalate issues to operations.

## 2. Help center (MVP options)

| Option | Description |
|--------|-------------|
| **A: Smart FAQ** | Searchable static FAQ articles by category |
| **B: AI assistant** | Chat widget with RAG on FAQ + process docs |

**Open question:** Client to choose A or B for MVP (see README).

## 3. FAQ topics (minimum)

- Move-in process overview
- Deadlines explained
- Container shipment workflow
- Retail package logging rules
- FedEx pickup instructions
- Add-on purchase process
- Contact escalation

## 4. Support ticket form

| Field | Required |
|-------|----------|
| Category | Yes |
| Subject | Yes |
| Description | Yes |
| Attachments | Optional |

### Categories (FSD §13.2)

- Missing package
- Delivery problem
- Incorrect status
- Account issue
- General support

Auto-attach: New Life ID, student email, current container status.

## 5. Route

`/student/support`

## 6. Acceptance criteria

- [ ] FAQ searchable and categorized.
- [ ] Ticket submission creates record and notifies admin.
- [ ] Student sees ticket status (open, in progress, resolved).
- [ ] AI option (if chosen) escalates to ticket when unable to answer.
