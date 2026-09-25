# Admin — Orders (stage 4 build notes)

Files: `admin/controllers/{orders,order-detail,order-actions,order-print,orders-export}.php`,
`admin/views/{orders,orders-cancel-unpaid,order-detail,print-invoice,print-packing-slip}.php`,
`assets/js/admin-print.js`.

## Shape
- `order-actions.php` is a function library shared by the other four controllers: actor + activity
  log writer (`adm_order_log`, PII masked to `"(changed)"`), labels, WhatsApp templates (05b §7 verbatim),
  list filter parsing / WHERE builder, POST handlers for the detail routes, proof streaming.
- State changes never happen in the admin: `order_transition()`, `payment_review()`,
  `payment_mark_paid()`, `stock_restore_order()` in `app/lib` own the transactions and the C-12 guard.
  The admin validates, calls, logs one `admin_activity_log` row, flashes, 303s.
- Activity-log rows are written after the lib's transaction commits (nested transactions are not
  supported by `db_transaction`), so a failed log insert cannot roll a change back; it logs a warning.

## List (`/admin/orders`)
- Filters are plain GET; `range=today|yesterday|week|month|last_month` expands to `from`/`to`.
- Search classification: order number → 303 to detail; digits → `phone_normalize()` exact or prefix;
  else name prefix LIKE, falling back to contains when the prefix pass is empty.
- Attention counts cached 60 s in `storage/cache/orders-attention.json`; cleared by bulk actions.
- Bulk shipped is two steps because tracking is per order: the action bar posts ids, the controller parks
  them in the session and redirects to `?bulkship=1`, which renders the one-courier form.
- Bulk print/export redirect to the GET print/CSV routes with `?ids=`.

## Cancel unpaid transfers (`/admin/orders/cancel-unpaid`, C-58)
GET lists the rows; POST needs one reason + typed `CANCEL` (dialog word with JS, `<noscript>` field
without), then calls `order_transition(...,'cancelled')` per order so the C-12 stock guard, coupon
revert and cancellation email all happen exactly as a single cancel would.

## Print
Standalone HTML pages (no admin shell), inline print CSS for A4 and 80 mm rolls, `data-autoprint`
handled by `assets/js/admin-print.js` (CSP forbids inline scripts). `?print=0` disables auto-print.
Packing slips omit prices except the COD collect banner (Q-14). Every render logs
`order.invoice_printed`.

## CSV
`orders-export.php` is the orders half of the shared `export.php` route (05a §5): BOM, CRLF,
formula-injection guard on text cells, phone with a leading apostrophe, bare decimals for money,
5,000-row cap, one `orders.export` activity row.
