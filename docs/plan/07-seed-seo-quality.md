# 07 — Seed Data & Quality Bar (SEO / Performance / Security)

**Sky Fragrances — skyfragrances.com**
Status: specification. No application code here — DDL/DML fragments and config blocks are
illustrative of the exact shape required.

Companion documents: `00-brief.md` (source of truth). Schema, routing and folder structure are
owned by sibling documents in this plan folder; **this document assumes the table and column
names listed in §0.2 and flags every one of them as a cross-stream contract.**

---

## 0. Ground rules this document inherits

### 0.1 Hard constraints (from the brief — non-negotiable)

| Constraint | Consequence for everything below |
|---|---|
| Plain PHP 8.2, PDO/MySQL, no Composer, no Node, no build step | No SCSS, no minifier, no image CLI. CSS is hand-written and hand-minified-by-discipline; image derivatives come from **GD at upload time**. |
| Deployed by a non-developer via hPanel File Manager | Seed data ships **inside `install.php`** (and as `db/seed.sql` for manual phpMyAdmin import). No migration runner. |
| Must run on MySQL 8+ **and** MariaDB 10.4+ | `utf8mb4_unicode_ci` only (never `utf8mb4_0900_ai_ci`), no functional defaults, no `JSON_TABLE`, no CTE-only syntax, no `ALTER ... ALGORITHM`. Seed rows are literal `INSERT`s with explicit column lists. |
| Vendored libraries only | PHPMailer source files under `app/lib/vendor/PHPMailer/` (08 C-35) with manual `require`. Nothing else third-party, server-side or client-side. |
| Vanilla JS, one CSS file | No jQuery, no Swiper, no AOS, no Google Fonts JS, no Instagram embed script. |

### 0.2 Schema names assumed by this document (cross-stream contract)

These are the names the seed data and the SEO layer are written against. If the schema stream
chose different names, **the seed and the SEO helpers must be renamed to match it — the schema
wins, not this document.**

```
collections(id, name, slug, tagline, description, mood, image, sort_order, is_active,
            seo_title, seo_description, created_at, updated_at)
products(id, collection_id, name, slug, gender, scent_family, short_description,
         description, notes_top, notes_heart, notes_base, longevity, sillage,
         best_season, occasion, is_featured, is_new, is_active, sort_order,
         seo_title, seo_description, created_at, updated_at)
product_sizes(id, product_id, size_label, size_ml, sku, price, sale_price, stock,
              is_default, sort_order)
product_images(id, product_id, filename, alt_text, sort_order, is_primary)
reviews(id, product_id, customer_name, customer_city, rating, title, body,
        status, is_sample, created_at, approved_at)
coupons(id, code, type, value, min_order_total, usage_limit, used_count,
        starts_at, expires_at, is_active, created_at)
settings(setting_key, setting_value, setting_group, updated_at)
slug_redirects(id, entity_type, old_slug, new_slug, created_at)
admin_login_attempts(id, ip_hash, username, attempted_at, was_success)
-- 08-decisions-register.md §1.2 additions: scent_families, content_pages, admin_users, admin_activity_log,
-- products.published_at/sales_count/rating_avg/rating_count/deleted_at, product_sizes.is_active/low_stock_threshold,
-- collections.show_on_home, coupons.per_phone_limit, reviews.rejected_at/moderated_by, product_images.width/height
```

Enumerations are stored as **short strings**, not MySQL `ENUM` (portable, and editable from the
admin panel without DDL):

- `products.gender` → `him` | `her` | `unisex`
- `reviews.status` → `pending` | `approved` | `rejected`
- `coupons.type` → `percent` | `fixed`
- `products.longevity`, `products.sillage` → `TINYINT` 1–5 (drives the meters on the PDP)

Money is `DECIMAL(10,2)` in PKR. **No floats anywhere near a price.** Display format is
`Rs. 4,950` (`'Rs. ' . number_format($v, 0)`) — decimals are never shown because Pakistani retail
prices are whole rupees; the column keeps `.00` for arithmetic safety.

### 0.3 Character set discipline for the seed

The copy below contains em dashes and typographic quotes on purpose (it is the shop window).
That only survives if all three of these are true:

1. Tables are `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`.
2. `db/seed.sql` begins with `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;` and the file is
   saved as UTF-8 **without BOM** (a BOM breaks the phpMyAdmin import path).
3. The PDO DSN carries `;charset=utf8mb4`.

If any of those is missed the customer sees `â€”`. This is the single most common way a
File-Manager deployment ships looking cheap.

---

# PART A — Seed data

> **Everything in Part A is sample content.** It exists so the site is demonstrable on day one and
> so every admin screen has a realistic state to render. See §A.7 — *Killing the sample data* —
> which is a launch blocker, not a nicety.

## A.1 Collections (5)

| # | Name | Slug | One-line description | Mood it represents |
|---|---|---|---|---|
| 1 | Dawn Chorus | `dawn-chorus` | Luminous florals and soft citrus for the hour the light arrives. | Optimism, a clean start, the scent of a good morning. |
| 2 | Azure Heights | `azure-heights` | Crisp, weightless, high-altitude freshness that survives the heat. | Clarity and composure. Daylight, air, room to breathe. |
| 3 | Golden Hour | `golden-hour` | Saffron, amber and rose at the warmest point of the day. | Indulgence and glow. Being looked at, and not minding. |
| 4 | Midnight Meridian | `midnight-meridian` | Oud, leather and smoke for the darkest part of the sky. | Authority and mystery. The fragrance people remember. |
| 5 | Monsoon Veil | `monsoon-veil` | Petrichor, vetiver and wet green air after the first rain. | Nostalgia and relief. Barsaat, bottled. |

Collection copy (the paragraph that sits under the collection hero — 2 sentences each):

- **Dawn Chorus** — "The first hour, before the city is loud. Soft florals, milky woods and citrus
  that reads as light rather than sharpness."
- **Azure Heights** — "Fragrance built for a Pakistani summer. Clean, airy compositions that stay
  legible at 42°C and never turn sweet on skin."
- **Golden Hour** — "Warmth you can see. Saffron, amber, rose absolute and resins, blended to glow
  rather than shout."
- **Midnight Meridian** — "Our deepest work. Oud, leather, incense and smoke — the collection we
  are asked about most, and the one we are slowest to release."
- **Monsoon Veil** — "The smell of the first rain on hot ground. Green, mineral and earthy, with
  not one sweet note in the range."

Seed shape:

```sql
INSERT INTO collections
  (name, slug, tagline, description, mood, image, sort_order, is_active, seo_title, seo_description)
VALUES
  ('Midnight Meridian','midnight-meridian','The darkest part of the sky',
   'Our deepest work. Oud, leather, incense and smoke — the collection we are asked about most, and the one we are slowest to release.',
   'Authority and mystery. The fragrance people remember.',
   'collections/midnight-meridian.webp', 40, 1,
   'Midnight Meridian Collection | Sky Fragrances',
   'Oud, leather and incense fragrances for evening wear. Long-lasting eau de parfum in 50ml and 100ml. Cash on delivery across Pakistan.');
```

`sort_order` is seeded in tens (10, 20, 30, 40, 50) so the owner can insert a collection between
two others from the admin panel without renumbering everything.

## A.2 The 12 perfumes

Spread: **4 For Him, 4 For Her, 4 Unisex**, across all five collections (3 / 3 / 2 / 2 / 2).

### Master table

| # | Name | Slug | Collection | Gender | Scent family | Long. | Sill. | 50ml | 100ml | Flags |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Azure Oud | `azure-oud` | Midnight Meridian | Unisex | Woody Oud | 5 | 4 | 8,950 | 13,950 | featured |
| 2 | Cirrus | `cirrus` | Azure Heights | Unisex | Fresh Aromatic Musk | 3 | 2 | 5,450 | 8,450 | featured |
| 3 | Aurora Bloom | `aurora-bloom` | Dawn Chorus | For Her | Floral Fruity | 4 | 4 | 5,950 | ~~9,450~~ **7,950** | featured, sale |
| 4 | Stratus Noir | `stratus-noir` | Midnight Meridian | For Him | Smoky Leather | 5 | 4 | 7,450 | 11,450 | featured |
| 5 | Eclipse Velvet | `eclipse-velvet` | Midnight Meridian | For Her | Oriental Gourmand | 5 | 5 | 7,950 | 12,450 | **100ml out of stock** |
| 6 | Zephyr Blanc | `zephyr-blanc` | Azure Heights | For Her | White Floral Musk | 3 | 3 | 5,650 | 8,950 | new |
| 7 | Silver Lining | `silver-lining` | Azure Heights | For Him | Fresh Woody Citrus | 4 | 3 | ~~4,950~~ **3,950** | ~~7,950~~ **6,450** | featured, sale |
| 8 | Cumulus Cashmere | `cumulus-cashmere` | Dawn Chorus | Unisex | Soft Musk Powdery | 3 | 2 | ~~5,250~~ **4,450** | ~~8,250~~ **6,950** | new, sale |
| 9 | Saffron Zenith | `saffron-zenith` | Golden Hour | For Him | Spicy Amber Leather | 5 | 4 | 8,450 | 12,950 | featured, **50ml low stock (3)** |
| 10 | Halo Rose | `halo-rose` | Golden Hour | For Her | Rose Oud Amber | 5 | 4 | 7,250 | ~~11,250~~ **9,450** | sale |
| 11 | Nimbus Rain | `nimbus-rain` | Monsoon Veil | Unisex | Aquatic Green Petrichor | 4 | 3 | 5,150 | 7,950 | new |
| 12 | Vetiver Squall | `vetiver-squall` | Monsoon Veil | For Him | Woody Aromatic Vetiver | 4 | 3 | 6,450 | 9,950 | new |

Price band rationale (assumption, flagged in openQuestions): positioned above the Pakistani
mass-market house brands (J., Scentsation ≈ Rs. 3,000–6,500 for 100ml) and below imported niche.
Entry point Rs. 3,950 on sale; hero oud at Rs. 13,950. Four products carry a sale price, one size
is out of stock, one size is at low stock so the dashboard's low-stock alert has something to show.

### SKU convention

`SKY-<3-letter product code>-<3-digit ml>` → `SKY-AZO-050`, `SKY-AZO-100`.
Uppercase, ASCII, unique index on `product_sizes.sku`. The code is assigned at product creation
and never changes, even if the product is renamed.

### Full product records

---

**1. Azure Oud** — `azure-oud` — Midnight Meridian — Unisex — Woody Oud

> Oud, cooled. We set the warmest material in perfumery against a cold blue sky: juniper and
> violet leaf hovering over a heart of Laotian oud and Turkish rose. It stays close through the
> day and opens like night air after dark, which is why it is the one people recognise before
> they see you.

- **Top:** Calabrian Bergamot · Juniper Berry · Violet Leaf · Pink Pepper
- **Heart:** Laotian Oud · Turkish Rose · Nutmeg
- **Base:** Amberwood · Cedarwood · Labdanum · Vetiver
- Longevity **5/5** · Sillage **4/5** · Best season: Autumn & Winter · Occasion: Evening, Signature
- `SKY-AZO-050` 50ml — Rs. 8,950 — stock 24 — *default size*
- `SKY-AZO-100` 100ml — Rs. 13,950 — stock 15
- SEO title: `Azure Oud Eau de Parfum | Sky Fragrances`
- SEO description: `Laotian oud, Turkish rose and cool violet leaf — our signature unisex eau de parfum. 50ml and 100ml. Cash on delivery across Pakistan.`

---

**2. Cirrus** — `cirrus` — Azure Heights — Unisex — Fresh Aromatic Musk

> The cleanest thing we make. Bergamot and green mandarin over a weightless heart of iris and
> jasmine air, drying down to white musk and blond cedar. Wear it to be noticed at the second
> glance rather than the first.

- **Top:** Calabrian Bergamot · Green Mandarin · Pink Peppercorn
- **Heart:** Iris · Jasmine Petals · Lavender
- **Base:** White Musk · Blond Cedar · Ambrette
- Longevity **3/5** · Sillage **2/5** · Best season: Spring & Summer · Occasion: Daily, Office
- `SKY-CIR-050` 50ml — Rs. 5,450 — stock 40 — *default size*
- `SKY-CIR-100` 100ml — Rs. 8,450 — stock 26
- SEO title: `Cirrus Eau de Parfum — Fresh Unisex | Sky Fragrances`
- SEO description: `A weightless bergamot, iris and white musk eau de parfum built for Pakistani summers. 50ml and 100ml, cash on delivery nationwide.`

---

**3. Aurora Bloom** — `aurora-bloom` — Dawn Chorus — For Her — Floral Fruity

> Dawn, at the moment colour arrives. Lychee and blackcurrant break over peony and Damask rose,
> then settle into a warm vanilla musk that is still on skin long after the morning. Our most
> gifted fragrance, and the one customers come back for.

- **Top:** Lychee · Blackcurrant · Mandarin
- **Heart:** Peony · Damask Rose · Magnolia
- **Base:** Vanilla · White Musk · Sandalwood · Cashmeran
- Longevity **4/5** · Sillage **4/5** · Best season: Spring, wearable all year · Occasion: Daytime, Gifting, Celebrations
- `SKY-AUB-050` 50ml — Rs. 5,950 — stock 33 — *default size*
- `SKY-AUB-100` 100ml — Rs. 9,450, **sale Rs. 7,950** — stock 18
- SEO title: `Aurora Bloom Eau de Parfum for Her | Sky Fragrances`
- SEO description: `Lychee, peony and Damask rose over warm vanilla musk. A long-lasting floral eau de parfum for her in 50ml and 100ml. COD across Pakistan.`

---

**4. Stratus Noir** — `stratus-noir` — Midnight Meridian — For Him — Smoky Leather

> A low ceiling of cloud before a storm. Cardamom and grapefruit peel give way to smoked leather,
> incense and dry birch tar, held together by patchouli and tonka. Severe, expensive, and not
> built to be liked by everyone.

- **Top:** Grapefruit Peel · Cardamom · Black Pepper
- **Heart:** Smoked Leather · Incense · Orris
- **Base:** Birch Tar · Patchouli · Tonka Bean · Vetiver
- Longevity **5/5** · Sillage **4/5** · Best season: Autumn & Winter · Occasion: Evening, Formal
- `SKY-STN-050` 50ml — Rs. 7,450 — stock 21 — *default size*
- `SKY-STN-100` 100ml — Rs. 11,450 — stock 12
- SEO title: `Stratus Noir Eau de Parfum for Him | Sky Fragrances`
- SEO description: `Smoked leather, incense and birch tar — a dark, long-lasting eau de parfum for him. 50ml and 100ml, cash on delivery across Pakistan.`

---

**5. Eclipse Velvet** — `eclipse-velvet` — Midnight Meridian — For Her — Oriental Gourmand

> Total darkness, then warmth. Saffron and plum sink into tuberose and a black vanilla accord,
> with benzoin and cacao absolute closing it like velvet drawn across the shoulders. Two sprays
> at night are enough.

- **Top:** Saffron · Plum · Bitter Almond
- **Heart:** Tuberose · Jasmine Sambac · Osmanthus
- **Base:** Black Vanilla · Benzoin · Cacao Absolute · Sandalwood
- Longevity **5/5** · Sillage **5/5** · Best season: Winter · Occasion: Evening, Weddings
- `SKY-ECV-050` 50ml — Rs. 7,950 — stock 17 — *default size*
- `SKY-ECV-100` 100ml — Rs. 12,450 — **stock 0 (out of stock — seeded deliberately)**
- SEO title: `Eclipse Velvet Eau de Parfum for Her | Sky Fragrances`
- SEO description: `Saffron, tuberose and black vanilla — an intense evening eau de parfum for her. Long-lasting, 50ml and 100ml. COD nationwide.`

---

**6. Zephyr Blanc** — `zephyr-blanc` — Azure Heights — For Her — White Floral Musk

> White flowers in moving air. Neroli and pear open onto orange blossom and jasmine that never
> turn heavy, kept bright by musk and a whisper of coconut wood. For the woman who wants presence
> without volume.

- **Top:** Neroli · Pear · Italian Lemon
- **Heart:** Orange Blossom · Jasmine Grandiflorum · Ylang-Ylang
- **Base:** White Musk · Coconut Wood · Cedar
- Longevity **3/5** · Sillage **3/5** · Best season: Spring & Summer · Occasion: Daily, Daytime
- `SKY-ZEB-050` 50ml — Rs. 5,650 — stock 30 — *default size*
- `SKY-ZEB-100` 100ml — Rs. 8,950 — stock 19
- SEO title: `Zephyr Blanc Eau de Parfum for Her | Sky Fragrances`
- SEO description: `Neroli, orange blossom and white musk — a soft floral eau de parfum for her, made for warm weather. 50ml and 100ml, COD in Pakistan.`

---

**7. Silver Lining** — `silver-lining` — Azure Heights — For Him — Fresh Woody Citrus

> The light behind the cloud. Sicilian lemon and sage lift a clean heart of lavender and geranium,
> finishing on vetiver, ambroxan and moss — crisp in Karachi heat and still there at the end of
> the day. The one we hand people first.

- **Top:** Sicilian Lemon · Sage · Grapefruit
- **Heart:** Lavender · Geranium · Nutmeg
- **Base:** Vetiver · Ambroxan · Oakmoss · Musk
- Longevity **4/5** · Sillage **3/5** · Best season: Summer, wearable all year · Occasion: Daily, Office
- `SKY-SIL-050` 50ml — Rs. 4,950, **sale Rs. 3,950** — stock 45 — *default size*
- `SKY-SIL-100` 100ml — Rs. 7,950, **sale Rs. 6,450** — stock 28
- SEO title: `Silver Lining Eau de Parfum for Him | Sky Fragrances`
- SEO description: `Sicilian lemon, lavender and vetiver — a crisp everyday eau de parfum for him that survives the heat. 50ml and 100ml, COD nationwide.`

---

**8. Cumulus Cashmere** — `cumulus-cashmere` — Dawn Chorus — Unisex — Soft Musk Powdery

> Skin, softened. Almond milk and bergamot melt into iris and heliotrope over cashmere woods and
> a clean musk that reads as your own skin on a better day. The one to wear when you would rather
> not think about what you are wearing.

- **Top:** Bergamot · Almond Milk · Pink Pepper
- **Heart:** Iris · Heliotrope · Violet
- **Base:** Cashmeran · White Musk · Sandalwood · Tonka Bean
- Longevity **3/5** · Sillage **2/5** · Best season: All year · Occasion: Daily, Layering
- `SKY-CUC-050` 50ml — Rs. 5,250, **sale Rs. 4,450** — stock 36 — *default size*
- `SKY-CUC-100` 100ml — Rs. 8,250, **sale Rs. 6,950** — stock 22
- SEO title: `Cumulus Cashmere Eau de Parfum | Sky Fragrances`
- SEO description: `Iris, cashmere woods and clean musk — a soft unisex skin scent for everyday wear. 50ml and 100ml, cash on delivery across Pakistan.`

---

**9. Saffron Zenith** — `saffron-zenith` — Golden Hour — For Him — Spicy Amber Leather

> The sun at its highest point. Kashmiri saffron and cinnamon burn over an amber-leather heart,
> grounded in oud and Atlas cedar that hold for a full working day. Built for the Pakistani
> winter, and for men who get told they smell expensive.

- **Top:** Kashmiri Saffron · Cinnamon · Bergamot
- **Heart:** Amber Accord · Leather · Rose Absolute
- **Base:** Oud · Atlas Cedar · Labdanum · Musk
- Longevity **5/5** · Sillage **4/5** · Best season: Autumn & Winter · Occasion: Evening, Formal, Weddings
- `SKY-SAZ-050` 50ml — Rs. 8,450 — **stock 3 (low-stock demo row)** — *default size*
- `SKY-SAZ-100` 100ml — Rs. 12,950 — stock 14
- SEO title: `Saffron Zenith Eau de Parfum for Him | Sky Fragrances`
- SEO description: `Kashmiri saffron, amber leather and oud — a rich winter eau de parfum for him. Long-lasting, 50ml and 100ml. COD across Pakistan.`

---

**10. Halo Rose** — `halo-rose` — Golden Hour — For Her — Rose Oud Amber

> A ring of light around the sun. Turkish rose absolute at full strength, deepened with raspberry
> and saffron, then wrapped in oud, amber and a little smoke. Heirloom-grade rose for people who
> think they have smelled every rose.

- **Top:** Raspberry · Saffron · Pink Pepper
- **Heart:** Turkish Rose Absolute · Geranium · Clove
- **Base:** Oud · Amber · Patchouli · Vanilla
- Longevity **5/5** · Sillage **4/5** · Best season: Autumn & Winter · Occasion: Evening, Weddings, Gifting
- `SKY-HAR-050` 50ml — Rs. 7,250 — stock 20 — *default size*
- `SKY-HAR-100` 100ml — Rs. 11,250, **sale Rs. 9,450** — stock 11
- SEO title: `Halo Rose Eau de Parfum for Her | Sky Fragrances`
- SEO description: `Turkish rose absolute with saffron, oud and amber — a rich rose eau de parfum for her. 50ml and 100ml, cash on delivery in Pakistan.`

---

**11. Nimbus Rain** — `nimbus-rain` — Monsoon Veil — Unisex — Aquatic Green Petrichor

> The first rain on hot ground. Green mandarin and violet leaf over a wet-earth accord of vetiver,
> cypress and mineral petrichor — the air in Islamabad in August. Nostalgia, bottled, without a
> single sweet note.

- **Top:** Green Mandarin · Violet Leaf · Juniper
- **Heart:** Petrichor Accord · Cypress · Geranium
- **Base:** Vetiver · Patchouli · Ambergris Accord · Moss
- Longevity **4/5** · Sillage **3/5** · Best season: Monsoon & Summer · Occasion: Daily, Outdoors
- `SKY-NIR-050` 50ml — Rs. 5,150 — stock 29 — *default size*
- `SKY-NIR-100` 100ml — Rs. 7,950 — stock 17
- SEO title: `Nimbus Rain Eau de Parfum — Petrichor | Sky Fragrances`
- SEO description: `Green mandarin, petrichor and vetiver — the smell of the first rain, as a unisex eau de parfum. 50ml and 100ml, COD nationwide.`

---

**12. Vetiver Squall** — `vetiver-squall` — Monsoon Veil — For Him — Woody Aromatic Vetiver

> Wind ahead of the rain. Haitian vetiver, smoked and salted, cut with grapefruit and black pepper,
> finishing on cedar and dry tobacco leaf. Sharp, green and unmistakably masculine without
> raising its voice.

- **Top:** Grapefruit · Black Pepper · Bergamot
- **Heart:** Haitian Vetiver · Clary Sage · Cardamom
- **Base:** Cedarwood · Tobacco Leaf · Ambrox · Musk
- Longevity **4/5** · Sillage **3/5** · Best season: Autumn, wearable all year · Occasion: Daily, Office, Evening
- `SKY-VES-050` 50ml — Rs. 6,450 — stock 25 — *default size*
- `SKY-VES-100` 100ml — Rs. 9,950 — stock 16
- SEO title: `Vetiver Squall Eau de Parfum for Him | Sky Fragrances`
- SEO description: `Smoked Haitian vetiver with grapefruit, cedar and tobacco leaf. A green woody eau de parfum for him, 50ml and 100ml. COD in Pakistan.`

---

### Notes-pyramid storage

`notes_top` / `notes_heart` / `notes_base` are `VARCHAR(255)` holding a **comma-separated list**
(`'Calabrian Bergamot, Juniper Berry, Violet Leaf, Pink Pepper'`), exploded with
`array_map('trim', explode(',', $v))` for rendering. Reasons: MariaDB 10.4 has no usable JSON
type, the admin edits them as one text field, and nothing ever queries *inside* a pyramid.
If the schema stream prefers a `product_notes` child table, the seed converts trivially — but
do not use a `JSON` column.

### Seed shape (products + sizes)

```sql
INSERT INTO products
  (collection_id, name, slug, gender, scent_family, short_description, description,
   notes_top, notes_heart, notes_base, longevity, sillage, best_season, occasion,
   is_featured, is_new, is_active, sort_order, seo_title, seo_description)
VALUES
  (4, 'Azure Oud', 'azure-oud', 'unisex', 'Woody Oud',
   'Laotian oud and Turkish rose under a cold blue sky.',
   'Oud, cooled. We set the warmest material in perfumery against a cold blue sky: juniper and violet leaf hovering over a heart of Laotian oud and Turkish rose. It stays close through the day and opens like night air after dark, which is why it is the one people recognise before they see you.',
   'Calabrian Bergamot, Juniper Berry, Violet Leaf, Pink Pepper',
   'Laotian Oud, Turkish Rose, Nutmeg',
   'Amberwood, Cedarwood, Labdanum, Vetiver',
   5, 4, 'Autumn & Winter', 'Evening, Signature',
   1, 0, 1, 10,
   'Azure Oud Eau de Parfum | Sky Fragrances',
   'Laotian oud, Turkish rose and cool violet leaf — our signature unisex eau de parfum. 50ml and 100ml. Cash on delivery across Pakistan.');

INSERT INTO product_sizes
  (product_id, size_label, size_ml, sku, price, sale_price, stock, is_default, sort_order)
VALUES
  (1, '50ml',  50, 'SKY-AZO-050',  8950.00, NULL, 24, 1, 10),
  (1, '100ml',100, 'SKY-AZO-100', 13950.00, NULL, 15, 0, 20);
```

`sale_price` is `NULL` when not on sale — never `0.00`, never equal to `price`. The effective
price helper is one place only:

```php
function effective_price(array $size): float {
    return ($size['sale_price'] !== null && $size['sale_price'] > 0
            && $size['sale_price'] < $size['price'])
        ? (float)$size['sale_price']
        : (float)$size['price'];
}
```

Every price shown, every cart line, every JSON-LD offer and every order total goes through it.
A sale price that is not lower than the price is ignored rather than trusted.

### Product images the seed expects

Twelve products × 3 images = 36 source files under `/uploads/products/`, plus 5 collection
images and 1 default OG image. The seed inserts `product_images` rows pointing at
`sample/<slug>-1.webp` … `-3.webp`. **`install.php` must not fail if the files are absent** — the
image helper falls back to `/assets/img/placeholder-bottle.webp` whenever the file is missing, so
a client who uploads the code without the sample images still gets a working, non-broken shop.

Alt text is seeded, never blank — see §B.1.8.

---

## A.3 Coupons (3 — one usable, one expired, one exhausted)

The brief asks for `WELCOME10`. Two more are seeded so the admin coupon list is not a
one-row table and the developer can see every badge state render without editing the
database by hand.

| Code | Type | Value | Min order | Usage limit | Used | Starts | Expires | Active | State shown in admin |
|---|---|---|---|---|---|---|---|---|---|
| `WELCOME10` | `percent` | 10.00 | 3,000.00 | 500 | 0 | seed date | seed date + 1 year | 1 | **Active** (green) |
| `EIDSALE500` | `fixed` | 500.00 | 4,000.00 | 200 | 137 | seed − 120 days | seed − 45 days | 1 | **Expired** (grey) |
| `FIRST50` | `fixed` | 750.00 | 5,000.00 | 50 | 50 | seed − 60 days | seed + 180 days | 1 | **Limit reached** (amber) |

> 08 §2.4 — C-44: the seeded `used_count` values (137, 50) are the **authoritative** global counters — the guarded `UPDATE coupons … WHERE used_count < usage_limit` is the only global-limit check, so `FIRST50` is genuinely closed even though the seed writes **no** `coupon_redemptions` rows for it. There is no recount button that could reopen it. `WELCOME10` also seeds `per_phone_limit = 1` (01b §6); PLAN §14.2 asks the owner whether the one-per-phone rule and the one-year expiry are wanted (F-16).

Note that `EIDSALE500` and `FIRST50` are both `is_active = 1`. That is deliberate: the
admin toggle means *"the owner switched it off"*, not *"it is currently redeemable"*.
Redeemability is derived, never stored, and it is derived in exactly one function:

```
coupon_state(row, now, cart_subtotal):
  1. row.is_active = 0                          → 'disabled'      ("This code is not available.")
  2. row.starts_at IS NOT NULL AND now < starts_at → 'not_started' ("This code is not active yet.")
  3. row.expires_at IS NOT NULL AND now > expires_at → 'expired'   ("This code has expired.")
  4. row.usage_limit IS NOT NULL AND row.used_count >= usage_limit → 'exhausted'
                                                 ("This code has been fully redeemed.")
  5. cart_subtotal < row.min_order_total        → 'below_minimum'  ("Add Rs. X more to use this code.")
  6. otherwise                                  → 'valid'
```

Rules that fall out of it and must hold everywhere:

- **Order of the checks is the order of the messages.** An expired code that is also
  under the minimum says "expired", not "add Rs. 400 more". Telling a customer to add
  more to their basket for a code that will never work is the kind of small cruelty that
  loses the sale.
- `expires_at` is compared **inclusive of the whole day**. Store it as the end of the day
  in Asia/Karachi (`23:59:59`), not midnight, or every coupon quietly dies a day early.
  The seed writes `2027-09-25 23:59:59`.
- `usage_limit IS NULL` means unlimited (08 C-15; this document originally used `0`). The check is `usage_limit IS NULL OR used_count < usage_limit`, a plain integer
  comparison with no three-valued logic.
- Discount is computed on the **items subtotal only** — never on shipping, never on an
  already-discounted line a second time. A `percent` coupon rounds half-up to the whole
  rupee. A `fixed` coupon is clamped: `min(value, subtotal)`, so a Rs. 750 code on a
  Rs. 600 basket discounts Rs. 600 and never produces a negative total.
- `used_count` increments when the **order is created**, inside the same transaction that
  writes the order rows — not when the coupon is validated in the drawer, and not when the
  order is later marked paid. If the order is cancelled, `used_count` is decremented in
  the same transaction that restores stock.

Seed shape:

```sql
INSERT INTO coupons
  (code, type, value, min_order_total, usage_limit, used_count,
   starts_at, expires_at, is_active)
VALUES
  ('WELCOME10','percent',10.00,3000.00,500,0,
   '2026-09-25 00:00:00','2027-09-25 23:59:59',1),
  ('EIDSALE500','fixed',500.00,4000.00,200,137,
   '2026-05-28 00:00:00','2026-08-11 23:59:59',1),
  ('FIRST50','fixed',750.00,5000.00,50,50,
   '2026-07-27 00:00:00','2027-03-24 23:59:59',1);
```

`coupons.code` carries a `UNIQUE` index and is stored **uppercase**. Input is
`strtoupper(trim($code))` before lookup so `welcome10` and ` WELCOME10 ` both work — a
real customer types the code off a WhatsApp forward on a phone keyboard.

> **Dates in the seed are literals, not expressions.** `install.php` may compute them
> relative to "now" in PHP and bind them; `db/seed.sql` cannot, so it ships fixed dates and
> the notes file tells the owner that `EIDSALE500` is *supposed* to look expired.

## A.4 Sample reviews

The brief is explicit: **no fake reviews.** These rows exist so the moderation queue,
the star ratings and the review list have something to render during the build, and every
one of them is marked `is_sample = 1`. That column is the entire reason §A.7 is possible.

Eight rows: five `approved`, three `pending`.
> Superseded by 08 §2.4 — C-68: **all eight rows are seeded `pending`** with `approved_at = NULL`, so no invented rating ever renders on the storefront, in `aggregateRating` or in a stage screenshot (brief: *no fake reviews*). The "approved" column below reads as the state the developer reaches by approving those five in the admin during stage 4 (B.4.5 approves one to prove the PDP and the star bar), on the local build only. `products.rating_avg`/`rating_count` seed as `0`.

| Product | Name | City | ★ | Title | Status |
|---|---|---|---|---|---|
| Azure Oud | Bilal A. | Lahore | 5 | Compliments every single time | approved |
| Azure Oud | Hina S. | Karachi | 4 | Beautiful, but I wanted more sillage | approved |
| Aurora Bloom | Mariam K. | Islamabad | 5 | My signature now | approved |
| Aurora Bloom | Zoya R. | Faisalabad | 5 | Lasts a full working day | approved |
| Silver Lining | Usman T. | Rawalpindi | 4 | Perfect for Karachi summer | approved |
| Saffron Zenith | Ahmed N. | Multan | 5 | Worth the price | **pending** |
| Eclipse Velvet | Sana F. | Karachi | 3 | Lovely but too strong for me | **pending** |
| Nimbus Rain | Faisal M. | Peshawar | 2 | Not what I expected | **pending** |

The pending set is chosen on purpose: a 5★, a middling 3★ and a genuinely negative 2★.
A moderation screen that has only glowing reviews waiting teaches the owner nothing about
the decision they will actually have to make, and a build that has never rendered a 2★
review has never proved its star bar handles one.

- `approved_at` is set for approved rows and `NULL` for pending ones. It is what the
  "recently approved" sort uses; `created_at` is what the queue sorts by.
- `rating` is `TINYINT` 1–5, constrained in application code (MariaDB 10.4 ignores
  `CHECK` in some configurations — do not rely on it).
- `body` is 1–3 sentences of plain text, stored raw and escaped on output. No HTML is
  accepted from a review form, ever.
- There is no `reviews.is_verified_purchase` in v1. Adding a badge we cannot honestly
  compute would be the same lie as a fake review.

```sql
INSERT INTO reviews
  (product_id, customer_name, customer_city, rating, title, body, status, is_sample,
   created_at, approved_at)
VALUES
  (1,'Bilal A.','Lahore',5,'Compliments every single time',
   'Ordered the 100ml after trying a friend''s bottle. It is still on my shirt collar the next morning. Delivery to Lahore took two days.',
   'approved',1,'2026-08-14 19:22:00','2026-08-15 10:04:00'),
  (9,'Ahmed N.','Multan',5,'Worth the price',
   'Expensive but you can smell the quality. Wore it to a wedding and three people asked.',
   'pending',1,'2026-09-21 21:40:00',NULL);
```

**`is_sample` contract:** every table that carries seeded demonstration content gets a
`is_sample TINYINT(1) NOT NULL DEFAULT 0` column — `reviews` certainly, and `orders`,
`contact_messages` and `newsletter_subscribers` if those streams seed demo rows. Real
content written through the site always inserts `0`. Nothing in the storefront filters on
it; it exists only so §A.7 is a single, safe, reversible sweep.

## A.5 Settings seed

`settings` is a key/value table (`setting_key` PK, `setting_value` TEXT, `setting_group`).
`setting_group` exists only to split the admin Settings page into tabs. Reading is via one
`SELECT` at request start cached into an array; there is no per-key query.

### A.5.1 Store, contact and social

> 08 §2.4 — C-47 / F-10: `install.php` writes the admin email entered on Step 3 into `order_notify_email` **and** `contact_email` when they are blank, so the "new order" alert has a real recipient on day one; the `orders@skyfragrances.com` literal below is the example value, not a mailbox the build assumes exists.

| Key | Group | Seeded value |
|---|---|---|
| `store_name` | store | `Sky Fragrances` |
| `store_tagline` | store | `More Than Just A Scent` |
| `contact_email` (08 §1.4; was `store_email`) | store | `orders@skyfragrances.com` |
| `contact_phone` (08 §1.4; was `contact_phone`) | store | `+92 300 0000000` |
| `whatsapp` | store | `923000000000` |
| `address_line` | store | *(empty — the owner fills it or the footer line is omitted)* |
| `meta_description` | seo | `Luxury perfumes in Pakistan. Long-lasting eau de parfum for him, her and unisex. Cash on delivery nationwide, fast delivery, easy exchange.` |
| `og_default_image` | seo | `assets/img/og-default.jpg` |
| `instagram_url` / `facebook_url` / `tiktok_url` | social | `https://instagram.com/skyfragrances` etc. |
| `instagram_handle` | social | `@skyfragrances` |

### A.5.2 Commerce

| Key | Group | Seeded value | Note |
|---|---|---|---|
| `shipping_fee` | shipping | `250.00` | flat, nationwide |
| `free_shipping_threshold` | shipping | `3000.00` (08 §1.4; was 5000.00) | drives the cart progress bar |
| `delivery_time` | shipping | `2–4 working days` | rendered in the why-choose-us strip |
| `cod_enabled` | payments | `1` | |
| `bank_enabled` | payments | `1` | |
| `jazzcash_enabled` | payments | `1` | |
| `easypaisa_enabled` | payments | `1` | |
| `low_stock_threshold` | store | `5` | dashboard alert; makes `SKY-SAZ-050` (stock 3) fire |

### A.5.3 Payment account details — **placeholders, and obviously so**

These are the only seeded values a customer can lose money over. They are written so that
nobody could mistake them for real, and so that a screenshot of the checkout page during
testing cannot be sent to a customer by accident.

| Key | Seeded value |
|---|---|
| `bank_name` | `REPLACE ME — Your Bank Name` |
| `bank_account_title` | `REPLACE ME — Account Title` |
| `bank_account_number` | `0000-0000000-000 (REPLACE ME)` |
| `bank_iban` | `PK00XXXX0000000000000000 (REPLACE ME)` |
| `jazzcash_account_title` | `REPLACE ME — JazzCash Account Title` |
| `jazzcash_number` | `0300-0000000 (REPLACE ME)` |
| `easypaisa_account_title` | `REPLACE ME — Easypaisa Account Title` |
| `easypaisa_number` | `0345-0000000 (REPLACE ME)` |

Three rules enforce this rather than trusting a README:

1. Any settings value containing the literal string `REPLACE ME` is treated as **unset**.
2. The admin dashboard shows a **persistent red banner** listing every payment method that
   is enabled but still holds a `REPLACE ME` value. It cannot be dismissed.
3. The checkout page **hides** a manual payment method whose account details are still
   placeholders, and logs a warning. A customer must never be shown
   `0000-0000000 (REPLACE ME)` and asked to transfer money to it. If *every* method is
   unusable, COD is force-enabled so the store still takes orders.

> This is the single most likely way this project embarrasses its owner in public. The
> banner and the hide are launch-blocking acceptance items — §B.4.6.

### A.5.4 Copy blocks

`announcement_text` → `Free delivery on orders above Rs. 5,000 — cash on delivery nationwide`,
`hero_heading` → `More Than Just A Scent`, `hero_subheading` → one line about long-lasting
eau de parfum made for Pakistan, `hero_cta_label` → `Explore the Collections`,
`footer_blurb`, `newsletter_heading`, `quiz_band_heading`. Every one of them is editable;
none of them is hard-coded in a template.

## A.6 Scent Finder quiz seed

**Cross-stream contract addition** — these three tables are introduced by this document and
must be added to the schema:

```sql
quiz_questions(id, position, question_text, help_text, is_active)
quiz_options(id, question_id, position, option_label, option_help, option_icon)
quiz_option_scores(id, option_id, product_id, points)
```

Data-driven, not hard-coded, so the owner can retune recommendations from the admin panel
without a developer. Scoring is a plain weighted sum.

### A.6.1 The five questions

| # | Question | Options |
|---|---|---|
| 1 | Who is this fragrance for? | For Him · For Her · Doesn't matter |
| 2 | When will you wear it most? | Daytime & work · Evenings out · Weddings & events · All day, every day |
| 3 | Which of these smells best to you? | Fresh & citrusy · Flowers · Warm spice & amber · Oud & smoke · Rain & green earth |
| 4 | How much presence do you want? | Close to the skin · Noticeable · Fills the room |
| 5 | What is your budget? | Under Rs. 6,000 · Rs. 6,000–9,000 · Rs. 9,000+ · Show me everything |

Question 1 is a **filter**, not a score: choosing *For Her* excludes `gender = 'him'`
products and keeps `her` + `unisex`. *Doesn't matter* excludes nothing. Question 5 is also
a filter, applied against the cheapest active size's effective price. Questions 2, 3 and 4
score.

### A.6.2 Scoring

```
1. answers → filters (Q1 gender, Q5 price band) and scoring options (Q2, Q3, Q4)
2. candidates = active products passing both filters
3. score(p) = Σ points from quiz_option_scores for every chosen scoring option
4. tie-break: higher is_featured, then more approved reviews, then lower price
5. take the top 3; if fewer than 3 candidates, drop the price filter and retry once,
   and tell the visitor: "We widened your budget to find these."
6. if still empty (only possible if everything is inactive) → show the 3 best sellers
   and say so. The result page is never blank.
```

`points` is `TINYINT` 0–5. Seeded weights: **5** = this product is the answer to that
option, **3** = strong fit, **1** = acceptable. An option/product pair with no row scores 0;
we do not seed zero rows. Roughly 60–70 score rows total.

Worked example — *Oud & smoke* (Q3) seeds `Azure Oud 5`, `Stratus Noir 5`,
`Saffron Zenith 4`, `Halo Rose 4`, `Eclipse Velvet 3`. *Fills the room* (Q4) seeds every
product with `sillage >= 4` at 3 points and `sillage = 5` at 5. *Daytime & work* (Q2)
seeds `Cirrus 5`, `Silver Lining 5`, `Zephyr Blanc 4`, `Cumulus Cashmere 4`,
`Nimbus Rain 3`.

### A.6.3 Quiz mechanics

- The quiz runs **without JavaScript**: each question is a form that POSTs (or GETs) the
  accumulated answers, and `/scent-finder/result?a=1a,2c,3d,4b,5c` is the final state.
  With JS on, the steps swap client-side with a progress bar and only the result navigates.
- The answer string is **short, opaque and shareable** — a customer will WhatsApp their
  result. It is validated against the seeded option ids; anything unrecognised is dropped
  rather than erroring.
- `/scent-finder/result` is `noindex,follow` (route 16) — infinite answer permutations are
  exactly the kind of thin duplicate page that dilutes a small site's crawl budget.
- Result page: three `PRODUCT_CARD`s, one sentence per product saying *why* it matched
  ("You chose oud & smoke and wanted presence"), a `Retake the quiz` link, and an
  `Add all three to cart` button only if all three have stock.

## A.7 Killing the sample data — a launch blocker

Sample content that survives to production is how a real shop ends up with a review from
"Bilal A." under a product he never bought. The mechanism:

1. Every seeded review carries `is_sample = 1`. Every seeded product, collection, coupon
   and settings value is listed by slug/code/key in `db/sample-manifest.php`.
2. `/admin/tools/remove-sample-data` runs one transaction: delete sample reviews, delete
   sample orders/messages/subscribers, then delete the 12 products, their sizes, their
   images and the 5 collections **only if no order line references them**. A product that
   has been sold is kept and reported, never deleted.
3. The dashboard shows an amber banner — *"This store is still showing sample data"* —
   whenever any `is_sample = 1` row exists, linking to that tool.
4. The tool prints what it deleted and what it refused to delete, and it does not touch
   settings: the owner edits those by hand, because deleting `contact_phone` is worse than
   leaving it.

`install.php` deletes **itself** after a successful run (or, if `unlink` fails on the
shared host, writes `db/.installed` and refuses to run again, and says so loudly).
> Corrected by 08 §2.4 — C-47: the lock file is **`storage/.installed`** (written on every successful run, before the `unlink` attempt — `db/` is read-only), and the red "delete it in File Manager" panel of 02c §7.2 is shown only when `unlink` fails.

---

# PART B — The quality bar

Part B is written as a checklist. Every line is something a developer can tick or fail on a
running site; nothing here is a principle without a test. The numbering is stable so a code
review can cite `B.3.7` and mean one specific thing.

---

## B.1 SEO

### B.1.1 Title pattern per page type

Titles are built by the controller into `$head['title']` (doc 03 §1.2), capped at **60
characters**, truncated on a word boundary with no ellipsis. The brand suffix is dropped
first if the cap is hit — the page's own words matter more than the ninth repetition of
"Sky Fragrances".

| Page type | Pattern | Example |
|---|---|---|
| Home | `Sky Fragrances — Luxury Perfumes in Pakistan` | — |
| Product | `{seo_title}` if set, else `{name} — {scent_family} Perfume \| Sky Fragrances` | `Azure Oud Eau de Parfum \| Sky Fragrances` |
| Collection | `{collection} Collection — Perfumes \| Sky Fragrances` | `Golden Hour Collection — Perfumes \| Sky Fragrances` |
| Gender landing | `Perfumes For Him \| Sky Fragrances` | — |
| Scent family | `{family} Perfumes in Pakistan \| Sky Fragrances` | `Woody Oud Perfumes in Pakistan \| Sky Fragrances` |
| Listing page ≥2 | base title + ` — Page {n}` | `Shop All Perfumes — Page 3 \| Sky Fragrances` |
| Content page | `{page.seo_title \|\| page.title} \| Sky Fragrances` | — |
| Utility (cart, checkout, track, result) | plain descriptive title, `noindex,follow` | — |

### B.1.2 Meta description pattern

Capped at **155 characters**, always a complete sentence, never truncated mid-word, and
never auto-generated by chopping the product description — a description ending in "…which
is why it is the one people rec" reads as broken. Precedence per page:

1. The record's own `seo_description`, if non-empty. All 12 products and 5 collections ship
   with one written by hand (Part A) — this is the normal path, not the fallback.
2. `short_description` + a fixed commerce tail (`50ml and 100ml. Cash on delivery across
   Pakistan.`), assembled only if it fits inside 155.
3. `settings.meta_description`.

Every product description ends with a real commercial reason to click: size, COD,
nationwide delivery. Listing and filtered pages state the count
(`42 perfumes for him, from Rs. 3,950. Long-lasting eau de parfum…`) because the number
survives the SERP snippet better than adjectives do.

### B.1.3 Open Graph and Twitter

Emitted on every page from `$head['og']`, one helper, never hand-written in a view.

| Property | Value |
|---|---|
| `og:site_name` | `Sky Fragrances` |
| `og:type` | `product` on a PDP, `website` everywhere else |
| `og:title` | the page title **without** the ` | Sky Fragrances` suffix |
| `og:description` | the meta description verbatim |
| `og:url` | the canonical URL, absolute |
| `og:image` | absolute URL to a **1200×630** JPEG; PDP uses a GD-generated `-og.jpg` derivative of the primary image, others use `settings.og_default_image` |
| `og:image:width` / `:height` | `1200` / `630`, always present — WhatsApp will not render a large preview without them |
| `og:locale` | `en_PK` |
| `twitter:card` | `summary_large_image` |
| `twitter:title` / `:description` / `:image` | mirror the OG values |

WhatsApp is the primary sharing surface in this market; it caps preview images around
300 KB, so the `-og.jpg` derivative is JPEG (not WebP) at quality 82 and is checked against
that ceiling at generation time. A product image that is a tall 4:5 bottle shot is
letterboxed onto a `#0A0A0A` 1200×630 canvas rather than cropped — a cropped bottle preview
looks like a mistake.

### B.1.4 Product JSON-LD

One `<script type="application/ld+json">` per block, `Product` on the PDP:

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Azure Oud",
  "description": "Laotian oud, Turkish rose and cool violet leaf …",
  "sku": "SKY-AZO-050",
  "brand": { "@type": "Brand", "name": "Sky Fragrances" },
  "image": ["https://skyfragrances.com/uploads/products/azure-oud-1.jpg"],
  "offers": {
    "@type": "AggregateOffer",
    "priceCurrency": "PKR",
    "lowPrice": "8950.00",
    "highPrice": "13950.00",
    "offerCount": 2,
    "availability": "https://schema.org/InStock",
    "url": "https://skyfragrances.com/product/azure-oud"
  }
}
```

Non-negotiables:

1. `priceCurrency` is `PKR`. Prices are plain decimal strings — no `Rs.`, no thousands
   separator, no currency symbol inside the value.
2. Every price in the block comes from `effective_price()` (§A.2) — the same function that
   renders the page. A JSON-LD price that disagrees with the visible price is a Merchant
   Center suspension, not a warning.
3. Multi-size products use `AggregateOffer` with `lowPrice`/`highPrice`; a single-size
   product uses a single `Offer` with `price`.
4. `availability` is `InStock` only when at least one active size has `stock > 0`,
   otherwise `OutOfStock`. It is derived at render time, never cached.
5. **`aggregateRating` is emitted only when the product has at least one `approved` review,
   and it is computed from those rows.** If there are none, the key is absent. It is never
   defaulted to 5, never borrowed from a sibling product, never seeded. Sample reviews
   (`is_sample = 1`) are approved rows and do count while they exist — which is another
   reason §A.7 must run before launch.
   (08 C-68: sample reviews are seeded `pending`, so on a fresh install they count for nothing; only a review the developer approves locally does, and §A.7 still removes them before go-live.)
6. `review` objects are emitted for up to the 5 most recent approved reviews, with real
   `author`, `datePublished` and `reviewRating`. No review is invented to fill the block.

### B.1.5 Site-wide JSON-LD

- **`Organization`** — every page. `name`, `url`, `logo` (absolute), `sameAs` array built
  only from non-empty social settings, and `contactPoint` with the WhatsApp number,
  `contactType: "customer service"`, `areaServed: "PK"`, `availableLanguage: ["en","ur"]`.
- **`WebSite`** with `potentialAction` → `SearchAction` targeting
  `https://skyfragrances.com/search?q={search_term_string}` — **home page only**.
- **`BreadcrumbList`** — every page that has a trail, positions 1..n, ending on the current
  page. The PDP trail is `Home › Shop › {Collection} › {Product}`, matching the visible
  breadcrumb exactly. If the visible trail changes, the block changes with it; they are
  rendered from the same array.
- **`FAQPage`** — `/faq` only, built from the real accordion Q&A rows. Not duplicated on
  any other page.
- Nothing else. No `LocalBusiness` (there is no walk-in address), no `Offer` on listing
  pages, no invented `GTIN`.

### B.1.6 Canonicals

- Absolute, `https://skyfragrances.com`, no trailing slash, no query string — with two
  exceptions: paginated listings keep `?page=n` (page 3 is its own canonical, it is not
  self-collapsed onto page 1), and `/search` canonicalises to `/search` with no `q`.
- Filter and sort parameters are stripped from the canonical. `/shop?gender=him&sort=price_asc`
  canonicalises to `/shop`, and carries `noindex,follow` once more than one filter is
  applied — combinatorial filter URLs are the biggest crawl-waste risk on this site.
- The preset landing pages (`/for-him`, `/scent/woody-oud`) are the indexable versions of
  those filters. That is why they exist.
- Any non-canonical slug reaching a controller 301s via `slug_redirects` **before** the
  page renders, so a canonical tag never has to paper over a wrong URL.

### B.1.7 sitemap.xml and robots.txt

`/sitemap.xml` is **generated by `sitemap.php`**, not a static file. A static file on a
File-Manager deployment is a file the owner will never regenerate, and it will be lying
about the catalogue within a week.

- Includes: `/`, `/shop`, `/collections`, the 3 gender landings, `/new-arrivals`,
  `/best-sellers`, `/sale`, `/scent-finder`, `/track`, `/contact`, all content pages, every
  active collection, every active scent-family landing, and every **active** product.
- Excludes: cart, checkout, order confirmation, quiz results, search, unsubscribe, `/admin`,
  every API route, and anything `noindex`.
- `<lastmod>` is the record's `updated_at` in W3C format, Asia/Karachi offset. No
  `<changefreq>`, no `<priority>` — Google ignores both and they invite lying.
- Output is `Content-Type: application/xml; charset=utf-8`, buffered and echoed in one
  write, with a 1-hour file cache under `/cache/sitemap.xml` because the whole thing is two
  queries and there is no reason to hit the DB on every crawl.
- Under 200 URLs for the foreseeable future, so no sitemap index. Add one past 40,000.

`robots.txt` ships as a real static file:

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /cart
Disallow: /checkout
Disallow: /order/
Disallow: /track
Disallow: /search
Disallow: /unsubscribe
Disallow: /api/
Disallow: /scent-finder/result
Disallow: /*?sort=
Disallow: /*?page=
Sitemap: https://skyfragrances.com/sitemap.xml
```

`Disallow: /*?page=` is deliberate and it is a trade: paginated listings are canonical but
not crawled, because 12 products do not need 3 pages of crawl budget. Revisit past ~100
products.

### B.1.8 Slugs and alt text

- Slugs: lowercase ASCII, hyphen-separated, generated from the name, uniqueness enforced by
  suffixing `-2`, `-3`. Stop words are kept (`halo-rose`, not `halo`). Maximum 60 chars.
  Editing a name **never** silently changes a live slug; the admin offers the change and
  writes a `slug_redirects` row if accepted.
- Alt text is a required field on every image upload and defaults to
  `"{product name} {size range} eau de parfum by Sky Fragrances"` — never the filename,
  never empty, never `"image"`. Purely decorative imagery (hero texture, section dividers)
  carries `alt=""` so screen readers skip it, which is a different thing from missing alt.
- The admin product form **refuses to save** a product whose primary image has empty alt
  text. This is the only way alt text survives the first busy week.

---

## B.2 Performance — Lighthouse 90+ on mobile, with no build step

The target is measured on **mobile emulation, 4× CPU throttle, Slow 4G**, on the product
page and the home page (the two heaviest). Desktop scores are not evidence.

There is no bundler, so every optimisation below is something a human does once and the
deployment preserves. That is a feature: nothing here breaks when the owner edits a file in
File Manager.

### B.2.1 Critical CSS from a single stylesheet

One `assets/css/site.css`. It is split in the source by comment banners into
`— CRITICAL —` (reset, tokens, typography, header, announcement bar, hero, product-card
skeleton, grid) and `— DEFERRED —` (footer, drawer, modals, quiz, admin-facing utilities,
animations).

At render, the layout:

1. Inlines the critical block in a `<style>` tag in `<head>`. It is read from
   `assets/css/critical.css` — a **generated-at-deploy** copy, not a runtime string
   operation. A tiny `tools/build-critical.php` (run manually, output committed) slices
   `site.css` at the banners. Budget: **14 KB** uncompressed, and the file fails the build
   above 18 KB.
2. Loads the full stylesheet non-blockingly:
   `<link rel="preload" href="/assets/css/site.css?v={filemtime}" as="style" onload="this.rel='stylesheet'">`
   with a `<noscript>` plain `<link>` fallback.
3. `?v={filemtime}` is the cache buster on every static asset. It is automatic, it survives
   a File Manager edit, and it means `.htaccess` can set a one-year immutable cache header
   without ever serving a stale file.

### B.2.2 Fonts

Cormorant Garamond and Jost, **self-hosted**, not Google Fonts.

- Two weights each (400/600 Cormorant, 400/500 Jost), `woff2` only, Latin subset. Four
  files, ~90 KB total.
- `@font-face` with `font-display: swap` and a matched `size-adjust`/`ascent-override` so
  the fallback metric is close and the swap does not shift layout.
- `<link rel="preload" as="font" type="font/woff2" crossorigin>` for **exactly two** faces —
  Cormorant 600 (the hero H1) and Jost 400 (body). Preloading all four is slower, not
  faster.
- No icon font. Icons are inline SVG in the markup or a single sprite sheet.

### B.2.3 Images

The single largest lever on this site, and entirely GD's job at upload time.

- On upload, GD writes **WebP + JPEG** at widths **400, 600, 900, 1400**, plus the
  1200×630 `-og.jpg`. Originals over 2400px are downscaled before anything else; originals
  are kept only until the derivatives verify, then deleted.
- Every `<img>` is inside a `<picture>` with a WebP `<source>` and a JPEG fallback.
- **Every `<img>` carries explicit `width` and `height` attributes** matching the intrinsic
  aspect ratio (4:5 for products), with `height: auto` in CSS. This is the CLS fix; without
  it the grid reflows as each image lands and CLS alone costs the 90.
- `srcset` lists the real generated widths with a `sizes` attribute that matches the grid:
  `sizes="(max-width: 767px) 48vw, (max-width: 1199px) 31vw, 23vw"`.
- `loading="lazy"` on everything **except** the LCP element. The LCP element is the hero
  image on `/` and the primary product image on a PDP; those get `loading="eager"`,
  `fetchpriority="high"` and a `<link rel="preload" as="image" imagesrcset=…>` in `<head>`.
  A lazy-loaded LCP image is the most common self-inflicted Lighthouse failure.
- `decoding="async"` everywhere.
- Instagram section: static uploaded thumbnails linking to the posts. **No embed script.**

### B.2.4 JavaScript

One `assets/js/site.js`, loaded with `defer`, hand-written, target **under 15 KB**
uncompressed. (Superseded by 08 C-31: **five** storefront files — `reveal, ui, cart, product, forms`, ~11 KB — plus `admin.js`; the budget and rules below apply to their sum.) It contains: cart drawer + AJAX cart, size sheet, search suggest, mobile menu,
gallery/zoom, quiz step swapping, scroll reveal, footer accordions, toasts.

- Nothing in it is required for the page to be readable, navigable or purchasable. The site
  works with JS disabled: cart posts to `/cart`, the quiz posts between steps, search submits
  as a form. Progressive enhancement is not a nicety here — it is what keeps the critical
  path empty.
- Scroll reveal uses `IntersectionObserver` and adds a class; the CSS starts elements at
  `opacity:1` and the JS *opts in* to the animation, so a JS failure leaves content visible
  rather than blank.
- All animation is `transform`/`opacity` only. `prefers-reduced-motion: reduce` disables all
  of it.
- Third-party scripts: **zero** at launch. If the owner later wants analytics, one
  privacy-light snippet loaded after `window.load`, and the Lighthouse run is repeated.

### B.2.5 Server and transport

In `.htaccess` (LiteSpeed reads it):

- `mod_deflate` for `text/html`, `text/css`, `application/javascript`, `image/svg+xml`,
  `application/xml`. Not for images — they are already compressed.
- `mod_expires`: images/fonts/CSS/JS `access plus 1 year` with
  `Cache-Control: public, immutable` (safe because of `?v={filemtime}`); HTML `no-cache`.
- `KeepAlive` on. HTTP/2 is the host's default; do **not** domain-shard or sprite-merge for
  HTTP/1 reasons.
- PHP side: the home page is capped at 9 queries and the PDP at 7 (doc 03). Every listing
  query is covered by an index; `SELECT *` appears nowhere in a list context. Output is
  buffered and flushed once.
- `ob_start()` + a single echo means the response arrives as one packet on a shared host
  where TTFB is the least controllable number in the whole budget.

### B.2.6 What must NOT be done

Every line here has been tried on a project like this and made it worse:

1. **No CSS or JS framework.** No Bootstrap, no Tailwind CDN, no jQuery, no Alpine. A CDN
   Tailwind build alone is a 3-second mobile regression.
2. **No Google Fonts `<link>`.** Two extra DNS lookups and a render-blocking stylesheet on
   a connection that already struggles.
3. **No carousel/slider library.** The rail is `scroll-snap` CSS (doc 03 §1.3).
4. **No AOS / WOW.js / animate.css.** `IntersectionObserver` + two CSS classes.
5. **No Instagram, Facebook Pixel, TikTok pixel, chat widget or review-platform embed** at
   launch. A chat widget is typically 200 KB+ of JS for a store that already has a WhatsApp
   button.
6. **No runtime image resizing.** Never resize on request; never `imagecreatefromjpeg` in a
   page controller. GD runs at upload only.
7. **No base64 images in CSS** beyond a single sub-1 KB texture, if that.
8. **No minifier, no concatenator, no PHP-side CSS combiner.** One file, written tidily.
9. **No `@import` in CSS.** It serialises downloads.
10. **No render-blocking inline JS in `<head>`**, including "theme flash" guards — there is
    one theme.
11. **No web font for icons.**
12. **No database call inside a render loop.** Product cards receive a fully-hydrated array.

### B.2.7 Budgets that fail the build

| Metric (mobile, throttled) | Budget |
|---|---|
| LCP | ≤ 2.5 s |
| CLS | ≤ 0.05 |
| INP | ≤ 200 ms |
| Total JS (uncompressed) | ≤ 15 KB |
| Critical inline CSS | ≤ 14 KB |
| Full stylesheet | ≤ 60 KB |
| Home page total transfer | ≤ 900 KB |
| Product page total transfer | ≤ 800 KB |
| Lighthouse Performance / SEO / Best Practices / Accessibility | ≥ 90 each |

---

## B.3 Security checklist

Each item is stated as the concrete implementation, not the principle. A reviewer should be
able to grep for the thing named.

### B.3.1 Database access

1. One PDO instance, created in `includes/db.php`, DSN
   `mysql:host=…;dbname=…;charset=utf8mb4`.
2. `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION`, `ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC`,
   **`ATTR_EMULATE_PREPARES => false`**. Emulated prepares are the difference between a real
   prepared statement and string interpolation wearing a costume.
3. Every query with a variable uses placeholders. Zero exceptions, including `LIMIT` and
   `ORDER BY`: pagination binds integers with `PDO::PARAM_INT`, and sort direction comes from
   a **whitelist array lookup** (`$SORTS['price_asc'] => 'ps.price ASC'`), never from the
   query string.
4. `IN (...)` clauses build the placeholder list from `count()`, never from the values.
5. No `mysqli`, no `mysql_*`, no ORM, no query builder, no string-concatenated SQL anywhere.
6. The DB user in `config.php` needs `SELECT, INSERT, UPDATE, DELETE` only. No `DROP`, no
   `GRANT`. `install.php` needs `CREATE`, which is another reason it is deleted after use.
7. Stock decrements and order writes run inside one transaction (§B.3.10).

### B.3.2 Output escaping

1. One helper, `e($v)` → `htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
   Every echo of a database value or user input goes through it.
2. Attribute values are quoted and escaped with the same helper. URLs built from user input
   additionally pass `rawurlencode()` on the segment.
3. JSON printed into a page (the cart bootstrap, JSON-LD) uses
   `json_encode($x, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`.
4. **No HTML from users, anywhere.** Reviews, contact messages, order notes and addresses are
   plain text, stored raw, escaped on output, rendered with `nl2br(e($v))` when line breaks
   matter — in that order, never the reverse.
5. Admin-authored content pages (`/about`, `/faq`) are the one place HTML is allowed. It is
   stored as-is and rendered unescaped, and that is acceptable **only because the admin is
   the site owner**; it is documented as such so nobody later adds a second admin role
   without revisiting it.

### B.3.3 CSRF

1. A 32-byte token in `$_SESSION['csrf']`, one per session, regenerated on login and logout.
2. Every `POST` form — storefront and admin, including logout and every AJAX call — carries
   it as a hidden field or an `X-CSRF-Token` header.
3. Verification with `hash_equals()`, centrally, **before** any handler runs. A failure
   returns 419 and re-renders the form with the entered values preserved; it does not dump
   the customer's checkout basket.
4. Session cookie is `SameSite=Lax`, which is belt-and-braces alongside the token.
5. No state-changing `GET`. There is no `/admin/products/delete?id=5`; deletion is a POST.

### B.3.4 File upload validation (payment proofs and product images)

Uploads are the highest-risk surface on this site because a customer — not the admin —
uploads the payment screenshot.

1. Accept `image/jpeg`, `image/png`, `image/webp` only.
2. Type is decided by **`finfo_file()` on the temporary file**, then cross-checked with
   `getimagesize()`. The client `type` and the filename extension are advisory and are
   never trusted.
3. Size caps: 5 MB customer payment proof, 8 MB admin product image. Checked against
   `$_FILES['…']['size']` *and* enforced by `upload_max_filesize`/`post_max_size`, with an
   explicit check for `UPLOAD_ERR_INI_SIZE` so an oversize upload produces a message instead
   of a silently empty `$_POST`.
4. Dimension caps: reject above 6000×6000 (a GD decompression-bomb guard), and check the
   estimated memory (`w × h × 4`) against `memory_limit` before touching GD.
5. The uploaded file is **never stored under its original name**. Filename is
   `bin2hex(random_bytes(16)) . '.' . $ext`, where `$ext` is derived from the *detected*
   MIME type. This kills `shell.php.jpg`, double extensions, null bytes, path traversal and
   RTL-override tricks in one line.
6. Images are **re-encoded through GD** and the re-encoded file is what is saved. The
   original bytes — and any polyglot payload hiding in them — never reach disk.
7. EXIF is dropped by the re-encode, which also strips GPS coordinates from a customer's
   phone screenshot. That is a privacy win, not just a size win.
8. `move_uploaded_file()` only, into `/uploads/{products|proofs}/{YYYY}/{MM}/`, `0644`.

### B.3.5 Uploads directory hardening

`/uploads/.htaccess` (08 C-72: the block below is **void** — 02b §5 is the single canonical text, embedded verbatim in `install.php`; it adds the `<IfModule mod_headers.c>` wrapper, drops `-ExecCGI`, and starts with `ErrorDocument 404 "Not found"`):

```apache
# php_flag engine off — REMOVED (08 C-37: silently ignored under LiteSpeed/LSAPI; see 02b §5)
<FilesMatch "\.(php|php3|php4|php5|php7|php8|phtml|phar|cgi|pl|py|sh|htaccess)$">
  Require all denied
</FilesMatch>
RemoveHandler .php .phtml .phar
RemoveType .php .phtml .phar
Options -Indexes -ExecCGI
AddType text/plain .php .phtml
```

`php_flag engine off` is unavailable if PHP runs as CGI/FastCGI — which it does on
Hostinger. That is why the `FilesMatch` deny, the `RemoveHandler` and the `AddType` are all
present: the guard must not depend on one mechanism. **Acceptance test B.4.9 uploads a
`.jpg` containing `<?php echo 1; ?>` and requests it directly; it must download or render as
text, never execute.**

Payment proofs additionally are **not served from `/uploads` at all**. They live in
`/storage/proofs/` which is denied outright, and the admin views them through
`/admin/orders/{order_number}/proof` (08 C-25) after a session check, streaming the bytes with an explicit
`Content-Type` and `Content-Disposition: inline`, plus `X-Content-Type-Options: nosniff`.
A customer's bank screenshot with a visible account number must not be guessable by URL.

### B.3.6 Security headers

Sent from PHP (so they apply to every route regardless of host config), with `.htaccess` as
the backstop for static files:

```
Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';
                         script-src 'self' 'sha256-{HTML_JS_SNIPPET_HASH}'; font-src 'self';
                         connect-src 'self'; frame-src 'none'; frame-ancestors 'none';
                         base-uri 'self'; form-action 'self'; object-src 'none';
                         upgrade-insecure-requests
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), interest-cohort=()
X-Frame-Options: DENY
```

> 08 §2.4 — C-60: **this block is the only CSP on the wire** and PHP is its only sender; the root `.htaccess` sets no CSP/Referrer/Permissions header for HTML (02b §6 is void) so browsers never enforce an accidental intersection of two policies. `'sha256-{HTML_JS_SNIPPET_HASH}'` is the hash of the one constant 3-line `html.js` bootstrap in 04b Part 5 — computed once by hand, stored as a PHP constant, recomputed in B.4.9 whenever the snippet changes. **`Strict-Transport-Security` is struck from this block** (Q-23: HSTS is off at launch and is added later as one `.htaccess` line, never from PHP on day one).

`'unsafe-inline'` appears in `style-src` because the critical CSS is inlined; it is **not**
in `script-src`, and the only inline `<script>` is the hashed bootstrap. `frame-ancestors 'none'` replaces
`X-Frame-Options` (both are sent for old browsers). HSTS is added **only after** the SSL certificate is confirmed working —
setting it before a working cert locks the site out of its own domain for a year.

### B.3.7 Sessions

1. `session.cookie_httponly = 1`, `session.cookie_secure = 1`, `session.cookie_samesite = Lax`,
   `session.use_strict_mode = 1`, `session.use_only_cookies = 1`, set with `ini_set()` before
   `session_start()` so they do not depend on the host's `php.ini`.
2. Session name is changed from `PHPSESSID`.
3. `session_regenerate_id(true)` on successful admin login and on privilege-relevant changes
   (password change). The old session is destroyed, not orphaned.
4. Admin idle timeout **2 hours**, absolute lifetime **12 hours**, both tracked as session
   values and checked centrally — not left to the garbage collector.
5. The guest cart lives in the session. It stores **product/size ids and quantities only**.
   Prices, discounts, shipping and totals are recomputed server-side on every read. A price
   that came from the browser is never used for anything.

### B.3.8 Error handling in production

1. `display_errors = 0`, `log_errors = 1`, `error_reporting = E_ALL` in production;
   `display_errors = 1` only when `config.php` sets `APP_ENV = 'local'`.
2. A global exception handler and `set_error_handler` render the branded 500 page with a
   short reference id, log the full trace with that id, and never echo a file path, SQL
   statement or stack frame.
3. The error log is written to `/logs/` **outside the web root** if the host allows it, and
   to `/logs/` with `Require all denied` plus a `.htaccess`-protected random filename if it
   does not.
4. `config.php` sits outside `public_html` where possible; if the host forbids it, it is
   `config.php` in the root with its own `<Files>` deny rule and **no `.bak`, `.old`,
   `config.php.txt` or `.zip` beside it**. A leftover `config.php.bak` serves as plain text
   and hands over the database.
5. No `phpinfo()`, no `test.php`, no `adminer.php` on the server. Ever.

### B.3.9 Admin brute-force protection

1. `password_hash(..., PASSWORD_DEFAULT)`; verification with `password_verify`; rehash on
   login if `password_needs_rehash`.
2. `admin_login_attempts(ip_hash, username, attempted_at, was_success)`. `ip_hash` is
   `hash('sha256', $ip . $pepper)` — the raw IP is not stored.
3. Throttle: **5 failures from one `ip_hash` in 15 minutes → locked for 15 minutes**, and
   separately **10 failures against one username in 1 hour → that username locked for 1
   hour**, which blunts a distributed attempt. Successful login clears that ip's counters.
   > Superseded by 08 §2.4 — C-51 (05a §2.6 is canonical): the IP lock is **10** failures / 15 min; the username is **never hard-locked** — from 5 failures it slows to a 2 s delay per attempt, and a request carrying the owner's known-device cookie (`SFDEV`) skips the delay. A username lock lets anyone keep the owner out of her shop with five wrong passwords every 14 minutes. `ip_hash` is derived from `client_ip()` (02c §1, C-50), not `REMOTE_ADDR`.
4. The failure message is always the same — `Invalid username or password.` — whether the
   user exists or not, and whether or not the account is currently throttled. Constant-time
   comparison, plus a `usleep` jitter, so response time is not an oracle.
5. Rows older than 30 days are pruned on login (cheap, no cron needed on shared hosting).
6. `/admin` is `noindex, nofollow` and `Disallow`ed in robots.txt. That is obscurity, not
   security, and is listed here so nobody mistakes it for the control.

### B.3.10 Stock integrity

The one piece of business logic that is also a security control, because it is where money
and concurrency meet.

```
BEGIN;
  SELECT stock FROM product_sizes WHERE id = ? FOR UPDATE;   -- per line, ordered by id
  if stock < qty  → ROLLBACK, tell the customer which item, keep the rest of the cart
  UPDATE product_sizes SET stock = stock - ? WHERE id = ? AND stock >= ?;
  if affected_rows = 0 → ROLLBACK
  INSERT order, order_items (with the server-computed unit price frozen onto each row)
  UPDATE coupons SET used_count = used_count + 1 WHERE id = ?
COMMIT;
```

Rows are locked in ascending `product_sizes.id` order across every code path, so two
concurrent checkouts cannot deadlock each other. The `AND stock >= ?` guard on the `UPDATE`
is the actual defence — the `FOR UPDATE` is the optimisation. `stock` is `UNSIGNED`, so even
a bug cannot store a negative. Cancelling an order restores stock and decrements
`used_count` in one transaction, and is **idempotent**: an order already `cancelled` restores
nothing on a second click.

### B.3.11 Customer PII

What is stored, and nothing more: name, phone, optional email, city, address, optional order
note, and the payment proof image. No CNIC, no date of birth, no account password (checkout
is guest-only), no card data of any kind — the manual methods mean no card number ever
touches this server, and it must stay that way.

- The order lookup on `/track` requires **order number *and* the phone number on the order**,
  compared after normalising both to digits. Order numbers are `SF-YYMMDD-XXXX` (four random
  chars from `A-HJ-NP-Z2-9`; 08 C-10) — not sequential, so `SF-260925-K7QF` cannot be walked.
- `/order/{number}` (the confirmation page) additionally requires a one-time token issued at
  checkout and held in the session; a shared confirmation URL shows the track form instead.
- CSV export is admin-session-only, `Content-Disposition: attachment`, and every cell is
  prefixed with `'` when it begins with `= + - @` to prevent CSV formula injection into the
  owner's spreadsheet. Each export writes an audit row (who, when, how many orders).
- Retention: payment proofs are deleted **90 days** after the order reaches `delivered` or
  `cancelled`, by an admin-triggered cleanup tool (shared hosting cron is unreliable), and
  the dashboard nags when proofs older than 90 days exist. Order records themselves are kept.
  (08 C-65 names the tool: **`POST /admin/tools/purge`** (05a §1 route 40), which also runs on a 1-in-20 admin page load. It nulls `payment_proofs.file_path`, sets `purged_at` and deletes the file; stubs `email_outbox.body_html` — which holds the full rendered confirmation with name, phone, address and account details — on rows sent more than 60 days ago; prunes `rate_limits` and `admin_login_attempts` older than 30 days and expired session files. `admin_activity_log.before_json/after_json` never carry PII values (05a §5.1 rule 8). The privacy page states the 90-day and 60-day rules.)
- The privacy policy page states exactly this list, in plain English, including the WhatsApp
  contact and the 90-day proof deletion. It is seeded with real content, not lorem ipsum.

---

## B.4 Acceptance tests — the build is not done until every box is ticked

Run against a **fresh install** (empty database, `install.php` executed, sample data intact)
on a local PHP 8.2 + MariaDB instance, and then repeated on the real Hostinger account after
upload. Anything that passes locally and is not re-checked on the host has not been tested —
the host is where `php_flag`, mail, HTTPS and MariaDB collation differ.

### B.4.1 Install and deploy

- [ ] `install.php` runs on an empty database, creates every table, the admin account and all
      sample data, and reports success.
- [ ] Re-running `install.php` is refused, not destructive.
- [ ] `install.php` is gone (or `db/.installed` exists and the page says so). (08 C-47: the lock is `storage/.installed`.)
- [ ] `db/seed.sql` imports cleanly through **phpMyAdmin** on the real host. Em dashes and
      `°C` render correctly on the storefront afterwards — no `â€”`.
- [ ] **Re-importing `db/schema.sql` and then `db/seed.sql` on a database that already holds two
      real orders changes nothing**: no table dropped, order count unchanged, sample rows not
      duplicated (08 C-55 — no `DROP`, `IF NOT EXISTS`, `INSERT IGNORE`). Neither file contains
      `CREATE DATABASE`, `USE` or `SET SESSION`.
- [ ] The same SQL runs on MySQL 8 and MariaDB 10.4 with no collation error.
- [ ] `https://skyfragrances.com` forces HTTPS; `www` 301s to apex; a trailing slash 301s.
- [ ] `http://skyfragrances.com/admin/login` redirects to `https://` before any form renders (08 C-54).
- [ ] After the installer's Finish (or Settings › Advanced) https self-check, the redirect is a **301**;
      before it, a 302 (08 C-52).
- [ ] **Client IP on the live host** (08 C-50): two test orders placed from a phone on mobile data and
      from a laptop on Wi-Fi carry **different** `orders.ip_hash` values, and the installer's
      "Client IP detection" row read PASS. If both hashes match, `trusted_proxies` is wrong and every
      rate limit is shared by all visitors.
- [ ] Opening the admin panel on the `*.hostingersite.com` preview host and on the real domain both
      let the cart drawer work and admin forms save (08 C-49 — Origin is checked against the request
      host, not `base_url`); the dashboard shows the host-mismatch banner on the preview host only.
- [ ] `GET /storage/.htaccess`, `/storage/sessions/shop/`, `/admin/controllers/orders.php`,
      `/config.sample.php`, `/.user.ini`, `/db/schema.sql` all return 403/404 with no body.
- [ ] `install.php` Step 1 on the live host printed the effective `memory_limit` and the image
      megapixel budget (08 C-46).

### B.4.2 Orders — every payment method, end to end

For **each** of COD, Bank Transfer, JazzCash, Easypaisa:

- [ ] Place a real test order from a 375px viewport, start to finish.
- [ ] The manual methods show the account details from settings, accept a transaction id and
      an uploaded screenshot, and reject a PDF and a 12 MB file with a readable message.
- [ ] Confirmation page shows the correct order number and total.
- [ ] Customer email **and** admin email arrive, with correct totals and a working order link.
- [ ] The order appears in admin with the right status, method, proof and line items.
- [ ] `/track` finds it with order number + phone; wrong phone is refused.
- [ ] Line totals, subtotal, discount, shipping and grand total agree on the cart page, the
      checkout page, the confirmation page, the email and the admin detail view. All five.
- [ ] Editing the posted price in devtools changes nothing — the order stores the server price.

### B.4.3 Cart, coupons and shipping

- [ ] `WELCOME10` is rejected under Rs. 3,000, accepted above, and discounts exactly 10% of
      the items subtotal (not shipping), rounded to the rupee.
- [ ] `EIDSALE500` reports **expired**. `FIRST50` reports **fully redeemed**. Neither
      mentions the minimum spend.
- [ ] `welcome10` in lowercase with leading spaces works.
- [ ] A coupon worth more than the basket cannot produce a negative total.
- [ ] Free-shipping progress bar is accurate at Rs. 2,999 and Rs. 3,000, and shipping
      becomes Rs. 0 at the threshold (08 §1.4 / C-43: the threshold is Rs. 3,000, not 5,000).
- [ ] **Pre-discount rule (08 C-43):** a Rs. 3,100 cart with `WELCOME10` shows *Free delivery* on the
      cart page, the checkout page, the confirmation page, the email and `orders.shipping_fee = 0.00`
      — all five agree, and placing the order needs exactly one tap (no re-price bounce).
- [ ] An odd subtotal (e.g. Rs. 4,955 with `WELCOME10`) rounds the discount to Rs. 496 everywhere and
      the order places on the first tap (one pricing function, C-43).
- [ ] A price change made in admin between checkout render and *Place Order* — in **either**
      direction — writes no order and shows the "Your new total is …" notice; the second tap succeeds.
- [ ] **Per-phone race (08 C-44):** two checkouts with the same phone and `WELCOME10` fired
      simultaneously — exactly one gets the discount; the other is told the code was already used
      with this phone number and re-prices.
- [ ] `FIRST50` stays *Limit reached* after the coupon list is reloaded; there is no recount control.
- [ ] Ten wrong coupon codes from one IP in an hour → the eleventh is refused with the generic
      message; a scheduled (`starts_at` future) code reports *isn't valid*, not *isn't active yet*
      (08 C-66).
- [ ] Removing the last item empties the drawer to its empty state without an error.
- [ ] The cart survives a page reload and a browser restart within the session lifetime.

### B.4.4 Stock — it cannot go negative

- [ ] Eclipse Velvet 100ml (seeded stock 0) shows **Sold Out**, cannot be added, and the PDP
      JSON-LD says `OutOfStock`.
- [ ] Ordering 24 of Azure Oud 50ml succeeds and leaves stock 0; a 25th fails with a message
      naming the item and preserves the rest of the cart.
- [ ] **Concurrency:** two checkouts for the last unit fired simultaneously — exactly one
      succeeds, stock lands at 0, never −1. Re-run 10 times.
- [ ] Cancelling an order restores stock and decrements `used_count`; cancelling it twice
      changes nothing further. The `coupon_redemptions` row is `status = 'reverted'`, not deleted (08 C-44).
- [ ] **Strict mode (08 C-55):** `UPDATE product_sizes SET stock = stock - 5 WHERE id = {a size with stock 3}`
      run through the app's PDO connection raises error 1264, never stores 0.
- [ ] Cancelling a **shipped** order does **not** change stock; the order shows *Parcel received
      back — restore stock*, which restores it exactly once (08 C-48).
- [ ] A second bank-transfer order from a phone whose first transfer order is still awaiting
      verification is refused with the C-58 message; after the first is marked paid it is accepted.
- [ ] *Cancel unpaid transfer orders older than 48 h* cancels only qualifying orders, restores each
      order's stock once, and skips COD orders.
- [ ] Saffron Zenith 50ml (stock 3) appears in the dashboard low-stock alert.

### B.4.5 Every admin function

- [ ] Login works; 5 bad passwords lock the IP for 15 minutes; the message never reveals
      whether the username exists.
- [ ] Session regenerates on login; logout destroys it; the back button does not restore an
      authenticated page.
- [ ] Dashboard figures (today/month revenue, counts by status, best sellers) match the
      orders table by hand-count.
- [ ] Product create, edit, delete. Multi-image upload with auto-resize. Sizes with
      price/sale/stock/SKU. Featured, new, active toggles each change the storefront.
- [ ] Saving a product with empty primary-image alt text is refused.
- [ ] Renaming a product offers the slug change and writes a `slug_redirects` row; the old
      URL 301s to the new one.
- [ ] Collection CRUD; reordering with `sort_order` changes the storefront order.
- [ ] Order list filters and search; status change through the full chain; courier and
      tracking number; view proof; mark paid; print invoice and packing slip; restore stock
      on cancel; one-click WhatsApp message opens with the right prefilled text; CSV export
      opens correctly in Excel and a cell starting `=` is neutralised.
- [ ] Coupon CRUD, including creating one and redeeming it.
- [ ] Review moderation: approve the 2★ pending review and confirm it appears on the PDP,
      lowers the average, and updates `aggregateRating`. Reject one and confirm it does not.
- [ ] Contact messages list; newsletter subscribers list and CSV export; unsubscribe link
      works from a real email.
- [ ] Settings: change the announcement text, hero text, shipping fee, threshold, delivery
      time, and disable each payment method — each change is visible on the storefront
      immediately and disabling a method removes it from checkout.
- [ ] Admin password change works and invalidates the old password.
- [ ] Every admin screen is usable one-handed at 375px, including the order detail page.

### B.4.6 The placeholder guard

- [ ] With `REPLACE ME` bank details still in place, the dashboard shows the red banner and
      checkout does **not** offer Bank Transfer.
- [ ] Replacing them with real-looking values removes the banner and restores the method.
- [ ] With every manual method unconfigured, COD is still offered and orders still complete.

### B.4.7 Sample-data removal

- [ ] The dashboard shows the amber sample-data banner on a fresh install.
- [ ] `remove-sample-data` deletes all 8 sample reviews and the untouched sample products,
      **refuses** to delete a product that has been ordered, and reports both lists.
- [ ] After it runs, no product page shows `aggregateRating`, and no rating stars appear
      anywhere. The site looks like a new shop, not a broken one.

### B.4.8 Mobile pass at 375px

Every storefront page, in a 375×667 viewport, on a real phone as well as emulation:

- [ ] No horizontal scroll anywhere. Check the PDP notes pyramid and the order table.
- [ ] All tap targets ≥ 44×44px with ≥ 8px between them.
- [ ] Sticky add-to-cart bar does not cover the WhatsApp button or the last line of content.
- [ ] Cart drawer is full-height, scrolls internally, and closes on backdrop tap and Esc.
- [ ] Filters open as a bottom sheet; applying them closes it and keeps scroll position.
- [ ] Forms show the right keyboard: `inputmode="tel"` for phone, `type="email"` for email.
      No iOS zoom on focus — every input is ≥ 16px.
- [ ] The quiz is completable with one thumb, and completable with JS disabled.
- [ ] Long product names, a Rs. 13,950 price and a `SALE −29%` badge all coexist in a card
      without overlapping.

### B.4.9 SEO and security spot-checks

- [ ] Every indexable page has a unique title ≤ 60 and description ≤ 155. Crawl the site and
      diff the list — no duplicates.
- [ ] Product JSON-LD validates in the Rich Results Test; the price in it equals the price on
      the page; `priceCurrency` is `PKR`.
- [ ] A product with zero approved reviews emits **no** `aggregateRating`.
- [ ] `/sitemap.xml` returns valid XML, lists exactly the active catalogue, and excludes
      cart/checkout/admin/search. Hiding a product removes it within the cache window.
- [ ] `/robots.txt` is served and points at the sitemap.
- [ ] A WhatsApp share of a product URL shows the large image, title and description.
- [ ] `'; DROP TABLE products; --` typed into search, coupon, contact and track fields does
      nothing but return no results.
- [ ] `<script>alert(1)</script>` submitted as a review body, a customer name and an order
      note renders as visible text in admin and on the storefront, never executes.
- [ ] **Content-page sanitiser (08 C-62):** saving `<a href="javascript:alert(1)">x</a>`,
      `<p onmouseover="alert(1)">x</p>`, `<img src=x onerror=alert(1)>` and `<iframe src=//evil>` into
      `/privacy` stores and renders `<a>x</a>` (no href), `<p>x</p>`, nothing, nothing.
- [ ] **CSP (08 C-60):** the HTML response carries exactly one `Content-Security-Policy` header, and the
      browser console shows **zero** CSP violations on home, a product page, cart, checkout, track and an
      admin order page; `html.js` is set (no FOUC) — the hashed bootstrap ran.
- [ ] A paid manual order's confirmation link shows the receipt only; `POST`ing a new proof to it
      returns 403 and `payment_status` stays `paid` (08 C-59).
- [ ] `/order/SF-000000-XXXX` (non-existent) and `/order/{real}?t=wrong` render the identical
      pre-filled track form (08 C-76).
- [ ] The contact auto-reply contains none of the message body; 31 contact submissions in an hour
      from rotating IPs produce at most 30 auto-replies (08 C-63).
- [ ] Five wrong passwords on the admin username from one IP, then a correct login from the owner's
      phone (known-device cookie) succeeds immediately; a sixth wrong attempt from the attacker's IP is
      delayed ~2 s and the tenth locks that IP only (08 C-51).
- [ ] A form POSTed without a CSRF token is rejected with 419.
- [ ] `evil.jpg` containing `<?php echo 1; ?>` uploads, and requesting it directly does not
      execute PHP. `shell.php.jpg` is rejected or neutralised.
- [ ] A payment proof URL cannot be reached without an admin session.
- [ ] Triggering a database error in production mode shows the branded 500 page with a
      reference id and no path, no SQL, no trace.
- [ ] `config.php`, `/app`, `/db`, `/storage`, `/admin/controllers`, `/admin/views`, `/admin/partials`
      and directory listings are all inaccessible over HTTP (08 C-23 / C-54; there is no `/includes`
      or `/logs`). No `.bak`, `.zip`, `.disabled`, `phpinfo.php`, `adminer.php` or leftover
      `reset-password.php` on the server.

### B.4.10 Performance verification

- [ ] Lighthouse **mobile** on `/` and `/product/azure-oud`: Performance, SEO, Best Practices
      and Accessibility all ≥ 90, run three times against the **live host**, not localhost.
- [ ] Every budget in §B.2.7 met.
- [ ] Every `<img>` has `width`, `height` and non-empty `alt` (decorative images excepted with
      `alt=""`). Grep the rendered HTML — do not eyeball it.
- [ ] The LCP image is not lazy-loaded and is preloaded.
- [ ] With JavaScript disabled: the site is browsable, the cart works, checkout completes and
      the quiz finishes.
- [ ] With `prefers-reduced-motion: reduce`: nothing animates.
- [ ] Keyboard only, no mouse: the header, the drawer, the size sheet, the filters and
      checkout are all fully operable, with a visible focus ring throughout.

---

## B.5 Open questions for the client

1. **Price band** (§A.2) is an assumption. The 12 seeded prices need the owner's sign-off
   before they appear in any screenshot he shares.
2. **Money type conflict:** doc 03 §1.4 says prices are unsigned integers in whole rupees;
   §0.2 of this document and the brief's DECIMAL guidance say `DECIMAL(10,2)`. **This
   document assumes `DECIMAL(10,2)` with whole-rupee display.** One of the two documents must
   be amended before the schema is written — this is a one-line change now and a data
   migration later.
   **Resolved** — 08-decisions-register.md §2 C-01: `DECIMAL(10,2)`; doc 03 §1.4 has been amended.
3. **Real product photography** — the whole performance and OG plan assumes 4:5 bottle shots
   on a consistent dark ground. Mixed aspect ratios will cost the CLS budget.
4. **Courier** — the invoice, packing slip and tracking-number field assume TCS/Leopards-style
   manual tracking numbers with no API. Confirm.
5. **Email deliverability** — PHPMailer over the host's SMTP will land Gmail order
   confirmations in spam without SPF and DKIM on the domain. The owner must set both, or
   accept that WhatsApp is the real confirmation channel.
6. **Urdu** is explicitly out of scope for v1 (doc 03 §1.4). Confirm the owner agrees.

---

# PART C — The non-developer go-live guide (outline this document owns)

> Added by the hardening review (08 §2.4 — C-78, F-11). PLAN §1 promises a step-by-step guide
> and PLAN §12 stage 6 delivers it, but no specification owned its content. This Part is the
> outline the finished guide must follow; the guide itself is written in stage 6 as
> `docs/GO-LIVE-GUIDE.md` (and a PDF export), one numbered step per screen, every step with a
> screenshot of hPanel or the installer at that moment, and every instruction phrased for
> someone who has never opened a terminal. Steps are in the **only** order that works on
> Hostinger: domain first, padlock second, files third, installer fourth.

## C.1 Before you start (one page)

1. What you need open: hPanel, this guide, your phone. Roughly 45 minutes.
2. **What not to touch in hPanel** (C-52, C-70, hosting finding 12):
   - *SSL → Force HTTPS* stays **off** — the site's own `.htaccess` does this correctly behind
     Hostinger's proxy; hPanel's toggle writes a second rule that loops.
   - *Advanced → PHP Configuration* stays at defaults except the PHP version (C.2 step 2);
     the site ships its own `.user.ini`.
   - No LiteSpeed Cache plugin, no "Website Builder", no WordPress auto-installer on this domain.
   - File Manager: turn **Show hidden files** on (screenshot) — files starting with a dot
     (`.htaccess`, `.user.ini`) are invisible otherwise, and the checks below depend on seeing them.
3. Where to get help: the WhatsApp number of the developer, and what to send (the installer's
   requirements screen screenshot, or the incident id from a branded error page).

## C.2 Domain and padlock first (steps 1–3)

1. **Point the domain.** hPanel → Domains → `skyfragrances.com` → nameservers/DNS to this hosting
   plan. Wait until hPanel shows the domain as connected (up to a few hours).
2. **Select PHP 8.2** (or newer 8.x) in hPanel → Advanced → PHP Configuration. The installer
   refuses to run below 8.2 and says so.
3. **Wait for the padlock.** hPanel → Security → SSL shows *Active* for `skyfragrances.com`
   and `www`. Open `https://skyfragrances.com` in the phone browser: the default Hostinger page
   with a padlock is what "ready" looks like. **Do not upload anything before this.** (C-52: the
   site's HTTPS redirect ships as temporary and is made permanent by the installer only once
   the certificate is confirmed, but the guide still front-loads SSL so step 8 never shows a
   certificate warning.)

## C.3 Database and mailbox (steps 4–5)

4. **Create the database.** hPanel → Databases → MySQL Databases → create. Write down the three
   values hPanel shows (database name `u…_skyfrag`, user `u…_sky`, password). You do **not**
   create tables — the installer does. You never open phpMyAdmin in the normal path; it is only
   the fallback in C.7.
5. **Create the mailbox** `orders@skyfragrances.com` in hPanel → Emails **before** the installer
   (it asks for the mailbox password on its Step 3 and sends a test email; without the mailbox
   that step cannot pass). Note the SMTP host hPanel shows (normally `smtp.hostinger.com`, port
   587). If your email is with Google Workspace or another provider, use *its* SMTP details —
   Hostinger's server cannot send for an address it does not host.

## C.4 Upload and check (steps 6–7)

6. **Upload the ZIP.** File Manager → `public_html` → delete Hostinger's `default.php` if it is
   there → Upload `skyfragrances.zip` → right-click → Extract **here** (the ZIP has no wrapper
   folder, so files land directly in `public_html`). Delete the ZIP and any `__MACOSX` folder
   afterwards. Wait five minutes before the next step (`.user.ini` is read every 300 s).
7. **Check the files landed** (Show hidden files on): `index.php`, `install.php`, `.htaccess`,
   `.user.ini`, and folders `app`, `admin`, `assets`, `db`, `storage`, `uploads`. Inside
   `storage` and `uploads` there must be a `.htaccess` too. If any is missing, the installer
   will recreate it — but tell the developer, because it means the ZIP tool dropped hidden files.

## C.5 The installer (steps 8–11, one per screen)

8. **Open `https://skyfragrances.com/install.php`.** Screen 1 — *Requirements*: every row
   green, or amber with a plain sentence. A red row about PHP version or a folder not being
   writable stops here (fix per the sentence, reload). Amber "could not verify" rows are not
   blockers: open the URL they name on your phone and confirm what they ask (C-47). Note the
   *Memory limit* and *Client IP detection* rows — send a screenshot if either is amber.
9. **Screen 2 — Database:** paste the three values from step 4. The installer tests them before
   saving anything; a wrong password is a friendly message, not a blank page.
10. **Screen 3 — Your account and email:** choose your admin username (not `admin`) and a
    12+ character password; enter your email (this becomes both the login email and the
    address that receives *New order* alerts); enter the mailbox password from step 5; press
    **Send test email** and confirm it arrives on your phone before continuing.
11. **Screen 4 — Sample data and finish:** leave *Install sample perfumes* ticked (you remove
    them later from the admin panel). Press Finish. The page confirms `install.php` deleted
    itself, that HTTPS was confirmed and made permanent, and that `config.php` is locked
    read-only. If it instead shows a red panel asking you to delete `install.php` in File
    Manager, do exactly that, then reload the page to confirm it is gone.

## C.6 First hour in the admin panel (steps 12–17)

12. **Log in** at `https://skyfragrances.com/admin`. Your phone is now a *known device*
    (C-51); log in once from your laptop too if you use one.
13. **Settings → Payments:** replace every `REPLACE ME` bank / JazzCash / Easypaisa detail; the
    red dashboard banner disappears when none remain and checkout starts offering those methods.
    Decide whether COD needs a maximum (default: no cap).
14. **Settings → Store / Home:** upload the real logo and hero images; check the announcement
    text, WhatsApp number, delivery time and shipping fee.
15. **Place one test order per payment method** from your phone (COD, bank, JazzCash,
    Easypaisa), confirm the *New order* email reaches you and the customer email reaches a
    test address, move one order through Confirmed → Shipped → Delivered, cancel one, and
    check stock came back. Mark the bank order paid from its screenshot.
16. **Tools → Remove sample data** when you are ready to launch — sample reviews must go before
    launch (they are seeded unapproved, so nothing fake ever showed, but they must not be
    approved by accident). Keep or remove the 12 sample perfumes as you prefer.
17. **Settings → Advanced:** confirm *Site address* reads `https://skyfragrances.com` and
    *Search engines may index this site* is **on** (it is off automatically on the preview
    host). Confirm *HTTPS permanent* shows done; if not, press it now that the padlock shows.

## C.7 After launch (steps 18–21)

18. **Email deliverability:** hPanel → Emails → DNS / SPF & DKIM — follow Hostinger's
    one-click setup for `skyfragrances.com` (Q-19). Send yourself a test order afterwards and
    check it is not in spam. Until then, WhatsApp is the reliable confirmation channel.
19. **Optional — faster email retries:** hPanel → Advanced → Cron Jobs → every 15 minutes:
    `php /home/<user>/public_html/cron.php`. Nothing breaks without it; emails still go out
    whenever you open the admin panel (C-56).
20. **If you forget your password (recovery, step 9 of the login page footer):** File Manager →
    `app/tools/reset-password.php` → copy to `public_html` → open it in the browser → it tells
    you to create an empty file with a specific name inside `storage` → create it → reload →
    set the new password. It deletes itself and the marker file when done (C-64). Confirm
    `https://skyfragrances.com/reset-password.php` shows the branded 404 afterwards.
21. **If the site must be paused** while you re-upload: Settings → Advanced → *Maintenance
    mode*. If the admin panel itself is unreachable, create an empty file named `MAINTENANCE`
    inside `storage` in File Manager; delete it to reopen (C-70).

## C.8 Things the guide deliberately does not include

- Installing in a sub-folder (`skyfragrances.com/shop/`) — not supported in v1 (C-71). Staging
  happens on the Hostinger preview address instead.
- Editing `config.php` or `.htaccess` by hand. Every value the owner controls is in Settings.
- Importing `db/schema.sql` / `db/seed.sql` in phpMyAdmin. They exist only as a fallback the
  developer may ask for; since C-55 they cannot delete anything, but the installer is the path.
- Turning on HSTS (Q-23) — the developer does this after the certificate has been stable.

## C.9 Acceptance for the guide itself

- [ ] A person who has never seen the project follows the guide on a fresh Hostinger account,
      from the ZIP, without asking a question, in under one hour (the stage-6 rehearsal).
- [ ] Every step has a screenshot taken on the real hPanel at the moment of that step.
- [ ] The guide's step order matches this outline; steps 1–3 precede step 6 without exception.
- [ ] Every C-nn cited above resolves to a behaviour the installer or panel actually shows.
