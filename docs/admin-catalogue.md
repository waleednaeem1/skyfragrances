# Admin catalogue — products, collections, scent families

Stage-4 admin screens for the catalogue. Everything here runs through `admin/index.php`
(auth, origin check, size check, CSRF on every POST) and the stage-1–3 libraries; nothing is
duplicated from `app/lib`.

## Files

| File | Role |
|---|---|
| `admin/controllers/catalogue.php` | Shared helpers `require_once`'d by the catalogue controllers: activity log writer, slug rename (01a §5.2), CSV note parsing, money/int input parsing, image URL resolution, tile HTML, image insert/copy/delete. |
| `admin/controllers/products.php` | List (filters, search, sort, pagination), bulk actions, per-row delete / duplicate / toggle. |
| `admin/controllers/product-form.php` | Add / edit form: validation, sizes repeater persistence, slug redirects, alt text, activity log. |
| `admin/controllers/api/images.php` | JSON endpoints for the uploader: upload, reorder, make primary, delete. Answers a plain POST (JS off) with a flash + redirect. |
| `admin/controllers/collections.php`, `collection-form.php` | Collections list (reorder, hide, delete) and form (single image). |
| `admin/controllers/scent-families.php` | Scent families list, form and hide/show. **Routes are not yet in `app/routes-admin.php`** — see "Routes still needed". |
| `admin/views/products.php`, `product-form.php`, `collections.php`, `collection-form.php`, `scent-families.php`, `scent-family-form.php` | Views, all built from the shell partials (`table`, `filters`, `field`, `button`, `badge`, `money`, `action-bar`, `page-header`, `empty`). |
| `assets/js/admin-catalogue.js` | Screen-only progressive JS loaded by the product form: note chip preview, Google-style SEO preview, SKU suggestion, default-size radio renumbering, image tile actions (make primary / delete) through `SFAdmin.postJson`. |

## Products list

- Filters are all GET parameters (`q`, `collection_id`, `gender`, `scent_family`, `status`, `stock`,
  `flag`, `sort`, `page`) so any view is a bookmarkable URL.
- `status` blank = live + hidden (retired excluded); `live`, `hidden`, `retired`, `all`.
- `stock=low` = any active size with `0 < stock <= COALESCE(size threshold, settings.low_stock_threshold)`;
  `stock=out` = every active size at 0. The global threshold `0` disables low alerts (05a §6.1).
- Sorts: `date_desc|date_asc|name_asc|name_desc|price_asc|price_desc|stock_asc|stock_desc|best`
  (best = units sold in the last 30 days on non-cancelled orders).
- Bulk actions post to `/admin/products/bulk` with `ids[]` + `action`. `move` without a
  `collection_id` re-renders the list with an inline "move n products to" panel (no JS needed).
  `delete` requires `confirm_word=DELETE`. Ids that no longer exist are subtracted from the count.

## Delete rules (01a §1.3, 05a §7.5)

- A product with order lines is **retired**: `is_active=0`, `deleted_at=now`. It stays on orders and
  can be restored from the `Retired` filter (restore = clear `deleted_at`, stays hidden).
- A product with no order lines is hard-deleted; `product_sizes`, `product_images` and `reviews`
  cascade, and the image files are unlinked.
- A size removed from the repeater is hard-deleted when no order line references it, otherwise
  soft-deleted (`is_active=0`). Its SKU stays reserved in that case; the form says so.
- Collections delete only when empty and with typed `DELETE`; hiding is always available.
- Scent families are never deleted, only hidden (each owns a `/scent/{slug}` URL).

## Slugs

`catalogue_slug_rename()` implements 01a §5.2 inside the caller's transaction: delete any existing
row for the old slug, insert `old → new`, collapse the chain (`… → old` becomes `… → new`), delete
the loop row (`old_slug = new`). Entity types: `product`, `collection`, `scent`.

## Images

- Upload: `POST /admin/products/{id}/images`, field `image`, one file per request (C-46).
  `upload_validate_product()` then `image_derive_product()` (thumb/card/zoom WebP+JPEG + OG JPEG).
- Stored `product_images.filename` is `{stem}.webp` (e.g. `product-12-3f9a1c2b7d.webp`), and a real
  base file with that name is written next to the derivatives (a copy of the zoom WebP). This keeps
  both storefront conventions working: `/uploads/products/{id}/{filename}` is a real file
  (`cart_image_url`, OG), and `{basename-without-ext}-{size}.{ext}` derivatives exist for
  `product_image_variant()`. Seed rows (`sample/...webp`) follow the same shape.
- Collections store `collections/{stem}.webp` the same way (`collection_image_set()` reads it).
- Reorder posts `{ids:[…]}` and the reply `{ok:true, ids:[…]}` is the authoritative order; the first
  image is always `is_primary=1`. Make-primary moves the image to position 1.
- Deleting unlinks every derivative plus the base file and OG image, unless another row still
  references the same filename.
- Duplicate copies the files under a fresh stem in the new product's folder (shared files across
  per-product folders cannot be resolved by the storefront).
- Alt text is posted with the product form (`images[ID][alt]`), required, ≤125 chars; the default
  is `"{name} — {sizes} perfume by Sky Fragrances"`.
- Limit: 12 photos per product.

## Product form contract

- Sizes post as `sizes[i][id|size_label|size_ml|sku|price|sale_price|stock|low_stock_threshold]`
  plus `default_size` (row index). Row order becomes `sort_order` in tens. Blank SKU is generated as
  `SF-{initials}-{ml}` and uniquified.
- `best_season[]` and `occasion[]` are checkgroups; unknown stored values (the seed's
  "Autumn & Winter") are shown as extra checked options so nothing is lost on save.
- Notes are stored as `A, B, C` (01a §5.1): commas inside a note are stripped, empties dropped.
- `after` = `list` (default), `stay` (new products always return to their edit page so photos can be
  added), `add` (Save & add another).
- Activity log: `product.create`, `product.update` (summary names changed fields), `product.slug`,
  `product.stock` (before → after per size), `product.retire|delete|duplicate|live|hide|restore`,
  `product.image_add|image_primary|image_delete`, `product.bulk_*`; collections and scent families
  log the equivalent `collection.*` / `scent_family.*` actions.

## Routes still needed (owner: app/routes-admin.php)

```php
$r('admin.scent_families', 'GET', '/scent-families', 'scent-families.php', 'scent-families.php', 'admin-scent-families'),
$r('admin.scent_families.new', 'GET|POST', '/scent-families/new', 'scent-families.php', 'scent-family-form.php', 'admin-scent-family-form'),
$r('admin.scent_families.edit', 'GET|POST', '/scent-families/{id}', 'scent-families.php', 'scent-family-form.php', 'admin-scent-family-form', $n),
$r('admin.scent_families.toggle', 'POST', '/scent-families/{id}/toggle', 'scent-families.php', null, 'admin-scent-families', $n),
```

Place them before the `admin.coupons` entries. The "More" sheet / sidebar may also want a
"Scent families" link; until then it is reachable from the Collections page header.
