# Support & Help — Technical Notes

## Models

| Model | Fields |
|-------|--------|
| `SupportTicket` | `student_profile_id`, `category`, `subject`, `status`, `assigned_to` |
| `SupportMessage` | `ticket_id`, `author_id`, `body`, `is_internal` |
| `FaqArticle` | `slug`, `title`, `body`, `category` (if FAQ approach) |

## Services

| Service | Responsibility |
|---------|----------------|
| `SupportTicketService` | Create, assign, resolve |
| `FaqSearchService` | Full-text search (FAQ option) |

## AI option (Phase 1b)

- OpenAI API with system prompt + FAQ context
- Rate limit per user
- Log conversations; escalate creates ticket

## Migration

`database/migrations/xxxx_create_support_tickets_table.php`

## Tests

- Ticket creation notifies admin
- Student sees only non-internal messages
- Category validation
