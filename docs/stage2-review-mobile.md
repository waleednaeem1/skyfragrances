# Stage 2 review — mobile UX and accessibility

Reviewer scope: every `*--mobile.png` in `docs/shots/stage2/` (25 pages, 375 px @2x, full-page
captures) read alongside `app/views/*.php`, `app/partials/*.php`, `assets/css/site.css` and
`assets/js/{ui,cart,product,forms,reveal}.js`. Specs consulted: 08 register, 04a §2 (contrast) and
§3 (type, font loading), 04b §5, §12, §16, §21 and Part 4, 03 §5.9, §5.12, §5.13, §6.8, §6.9, §7.2,
§8. Line numbers refer to the files as they were on 2026-09-26.

Severity: **Blocker** = a customer cannot complete a core task on a phone · **High** = visibly
broken or a WCAG 2.1 AA / spec-mandated rule failure on a core path · **Medium** = degrades the
phone experience or a Lighthouse-mobile score · **Low** = polish.

---

## Blocker

### B1. Filters and sort cannot be applied when JS is on (mobile drawer and desktop sidebar)

- `assets/css/site.css:5144` — `html.js .filters__form-submit { display: none; }`
- `assets/css/site.css:5145` — `html.js .toolbar__submit { display: none; }`
- `app/views/listing.php:92` (the drawer/sidebar `Show results` / `Apply filters` button),
  `:177-182` (sort `<select>` + hidden `Sort` submit)
- No script in `ui.js`, `forms.js`, `cart.js`, `product.js` or `reveal.js` references
  `.filters`, `.toolbar`, `#sort`, `requestSubmit` or `.submit(` — nothing auto-submits.

With JS enabled (every real phone) the "Filter & Sort" drawer opens, boxes tick, and there is
no way to apply them: the only submit is hidden and no change handler exists. The sort
`<select>` in the toolbar likewise changes nothing. Filters and sort work only with JS
disabled. The screenshots pass because the tour drove the URL directly.

Fix (either, spec 03 §5.9 prefers staged apply): remove the two `html.js … display:none`
rules so the `Show results` button stays visible (its label should then update live with the
count), **or** add in `forms.js` a delegated `change` handler on `.toolbar__sort select` that
calls `form.requestSubmit()` and keep the drawer button visible. Do not live-apply the drawer.

---

## High

### H1. Cart page trust row renders broken (icon left, title floated right, text below)

- `app/views/cart.php:142-147` — each `.trust__item` has three direct children (svg, `h3`, `p`).
- `assets/css/site.css:4710-4715` — `.trust--row .trust__item { grid-template-columns: auto minmax(0,1fr) }`
  expects exactly two children (icon + wrapper `div`), as `app/views/product.php:130-136` does.

Seen in `cart--mobile.png` below the summary: "Cash on Delivery" sits at the far right of the
icon row, the sentence wraps underneath, repeated four times.

Fix: wrap `h3` + `p` in a `<div>` in `cart.php` (mirror `product.php:132-135`), or add
`.trust--row .trust__text { grid-column: 2; }` and `.trust--row .trust__icon { grid-row: 1 / span 2; }`.

### H2. Collections index is a 19,174 px scroll on a phone; thumbnails are 1-up and upscaled

- `assets/css/site.css:439` — `.grid--3 { grid-template-columns: 1fr; }` (3-up only from 768px, `:564`).
- `app/views/collections-index.php:50-58` — the "three cheapest" thumbs use `.grid.grid--3`, so
  each thumb becomes a full-width 4:5 card (343 × 429 CSS px); five collections = 15 full-screen
  images plus the wide cards. `collections--mobile.png` is 19,174 px tall (~34 screens).
- `collections-index.php:55` — `sizes="(min-width: 768px) 16vw, 30vw"` while the rendered width is
  ~91vw, so the browser fetches the ~225 px candidate and upscales it 1.5×.

03 §5.12 asks for "a 3-up strip". Fix: give the strip its own class, e.g.
`.grid--thumbs { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--sp-2); }` at base,
and set `sizes="(min-width: 768px) 16vw, 30vw"` to match (it then becomes correct).

### H3. Sticky add-to-cart bar: the size/price button is an ~18 px tap target

- `app/views/product.php:210` — `<button class="sticky-bar__size js-open-sizes" aria-label="Change size">`
- `assets/css/site.css:4457-4470` — `.sticky-bar__size { min-height: auto; padding: 0; font-size: var(--step-micro) … }`

The only interactive way to change size from the bar is a 12 px text line with no vertical
padding — well under the 44 px minimum (04b Part 4.2) and it also carries the live price at 12 px
(04a §3.5: micro is never used for something the customer must read to transact).

Fix: `.sticky-bar__size { min-height: var(--tap); padding-block: var(--sp-1); font-size: var(--step-small); }`
and keep the thumbnail/name column aligned with `align-items: center`, or extend the hit area
with `::after { content:""; position:absolute; inset:-0.75rem 0; }` on a `position: relative` button.

### H4. Payment-method validation error can never be shown; quiz answers have the same gap

- `app/views/checkout.php:122` — `<fieldset class="form__section has-error">` (no `.field`)
- `app/views/checkout.php:151` — `<p class="field__error" id="err-payment">` sits outside any `.field`
- `assets/css/site.css:1924` — `.field.has-error .field__error { display: flex; }` is the only rule that reveals an error, so the server-side `$errors['payment_method']` text stays `display:none`.
- `assets/js/forms.js:7-9` — `fieldOf()` looks for `.field, .size-picker, .star-input-group`; the checkout radios (`checkout.php:128`) and quiz radios (`quiz.php:32-33`) have none of these ancestors, so `setError()` returns early and no message is written.
- `assets/js/forms.js:45-50, 83-86` — the invalid radio (a 1 px, opacity-0 input) still receives focus, so nothing visible happens on the phone.
- `assets/js/forms.js:76-78` — the summary link is built from `c.id`; the radios have no `id`, so the link is `href="#"` and the label lookup falls back to `payment_method`.

Fix: wrap the radiogroup and its error in `<div class="field">` (checkout `:124-151`, quiz `:32-47`),
give the first radio of each group an `id` (`id="pm-cod"`, `id="q1-1"`) for the summary link, and in
`forms.js` extend `fieldOf()` with `.choice-group` closest-parent fallback (`control.closest('.field, .choice-group, …')`)
so the error `<p>` is found. Also add `.form__section.has-error .field__error { display:flex }` if the
fieldset keeps carrying the class.

### H5. Listing filter bar is always pinned, has no body padding, and the WhatsApp FAB sits on top of it

- `app/views/listing.php:270` — `<div class="filter-bar js-filter-bar is-visible">` hard-coded visible; no JS toggles `.js-filter-bar` (grep is empty).
- `assets/css/site.css:2266-2280` — `position: fixed; bottom: 0` full-width bar, 68 px tall; there is no `body.has-filter-bar { padding-block-end }` equivalent of `:5148`.
- `app/partials/whatsapp-button.php:11` — `--raised` only on product pages; `site.css:4763-4775` FAB `bottom: 16px` → the 56 px circle sits inside the bar's vertical band.

Screenshots (`shop`, `sale`, `for_her`, `for_him`, `unisex`, `best_sellers`, `new_arrivals`,
`search_q_oud`, `shop_gender_her_sort_price_asc`): the FAB overlaps the bar's right end; with the
"(1)" count the button's right edge is 27 px from the FAB. At the page bottom the bar permanently
covers the footer's last 68 px (copyright). 03 §5.9: pinned "while the grid is in view".

Fix: observe `.grid--products` with the existing `observe()` helper in `reveal.js` and toggle
`is-visible` + a `body.has-filter-bar` class (`padding-block-end: calc(4.25rem + var(--safe-bottom))`);
give listing pages `whatsapp-fab--raised` (pass `isListing` from `layout.php:50` the way `isProduct` is),
or shrink the bar to a left-anchored pill `max-width: calc(100vw - 6rem)`.

### H6. Collection and gender landings open with a full-viewport hero on a phone

- `app/views/listing.php:100-125` — landings reuse `<section class="hero …">`.
- `assets/css/site.css:2452-2460` — `.hero { min-height: 88svh; padding-top: calc(var(--header-h) + var(--sp-8)) … }`.

`for_her`, `for_him`, `unisex` and `collections/*` show a whole screen of gradient, a title and
"4 fragrances" before the first product. 03 §5.13 specifies a "hero strip (not full-viewport):
… 3:2 mobile". Fix: add `.hero--strip { min-height: 0; aspect-ratio: 3 / 2; padding-block: var(--sp-6); }`
(21:9 from 768px) and use it in `listing.php:100`.

### H7. Font preload hints point at files that do not exist; font payload exceeds the cap

- `app/partials/head-meta.php:61-66` — preloads `jost-400.woff2` and `cormorant-garamond-300.woff2` only if `is_file()`.
- `assets/fonts/` holds `jost-variable.woff2` (50.4 KB), `cormorant-garamond-variable.woff2` (37.8 KB), `cormorant-garamond-italic-variable.woff2` (39.3 KB) — so **no** font is ever preloaded and the three files are discovered only after `site.css` (149 KB raw / 22 KB gz) is parsed.
- `assets/css/site.css:198-222` — three `@font-face` blocks, `font-display: swap`, no metric-matched fallback. Total 127.5 KB vs the 118 KB hard cap in 04a §3.4; the italic face serves only the `<em>` mood line.

Effect on mobile: late FOUT on every H1/price (the LCP element on most pages is text), a visible
reflow when Cormorant (narrow, low x-height) replaces Times, and a Lighthouse "preload key
requests" / "font-display" flag.

Fix: change the loop in `head-meta.php:62` to `['jost-variable.woff2', 'cormorant-garamond-variable.woff2']`;
drop the italic file (let the browser synthesise, or subset it to the one weight used);
add fallback faces with `size-adjust`/`ascent-override` (`@font-face { font-family: "Cormorant Fallback"; src: local("Times New Roman"); size-adjust: 112%; … }`)
and put them second in `--font-display` / `--font-body` so the swap does not shift layout.

---

## Medium

### M1. Body scroll lock does not hold on iOS Safari

- `assets/css/site.css:5149-5152` — `body.is-locked { overflow: hidden; padding-inline-end: … }`
- `assets/js/ui.js:198-211` — `lockBody()` records `scrollY` but only adds the class; `unlockBody()` calls `window.scrollTo(0, scrollY)` (a no-op while overflow is hidden).
- `assets/css/site.css:1633` — `overscroll-behavior: contain` is on `.drawer`, which is not the scroll container (`.drawer__body` is, `:1688-1695`).

iOS ignores `overflow:hidden` on `<body>`; the page rubber-bands behind the cart drawer, filter
drawer and nav. Fix: `body.is-locked { position: fixed; inset-inline: 0; top: calc(var(--scroll-y, 0px) * -1); width: 100%; }`,
set `--scroll-y` in `lockBody()`, restore with `window.scrollTo({ top: scrollY, behavior: 'instant' })`
(needed because `html { scroll-behavior: smooth }` at `:230`), and move `overscroll-behavior: contain`
to `.drawer__body` and `.nav-mobile` (which does scroll itself, `:2870`).

### M2. Two controls are under 16 px and trigger iOS auto-zoom on focus

- `assets/css/site.css:2241-2250` — `.toolbar__select { font-size: var(--step-small) }` (14 px) — the sort select on every listing.
- `assets/css/site.css:3471-3474` — `.qty--sm .qty__input { font-size: var(--step-small) }` — cart page quantity input.

iOS Safari zooms the page when a focused form control is below 16 px, leaving the customer
zoomed in after choosing a sort or editing a quantity. Fix: `font-size: 1rem` on both (every
`.field__input` already uses `1rem`, `:1834`).

### M3. Checkout shows the transfer-only fields to every customer, including COD

- `app/views/checkout.php:152-181` — the "Paying by transfer?" panel with Transaction ID, Sender name and the screenshot upload is rendered unconditionally after the radio cards.

On a phone (`checkout--mobile.png`) a COD customer scrolls past a dashed upload box and an ID
field between choosing COD and the Place Order button; 03 §8.5 makes the branch conditional.
Fix: keep it in the DOM for no-JS, but hide it with
`html.js .form__section:has(.choice__input[value="cod"]:checked) .js-manual-panel { display: none; }`
(Safari 15.4+, Chrome 105+) and a tiny `change` handler in `forms.js` as the fallback; also
move the panel directly under the selected card so context is adjacent.

### M4. Transactional text at 12 px

04a §3.5: `--step-micro` (12 px) "is never used for anything a customer must read to transact".
Instances that break the rule:

- `assets/css/site.css:1906-1913` — `.field__error` 12 px (every validation message on checkout, contact, track, review).
- `assets/css/site.css:1901-1905` — `.field__hint` 12 px ("JPG, PNG or WebP, under 5 MB", "5 to 30 letters and digits", "We confirm every order by call or WhatsApp").
- `assets/css/site.css:4267-4275` — `.size-chip__price` 12 px (the per-size price inside the selector).
- `assets/css/site.css:1340-1343` — `.summary__line-meta` 12 px ("50ml × 2" on checkout/receipt).
- `assets/css/site.css:1196-1205` — `.cart-line__remove` 12 px button label.
- `assets/css/site.css:4457` — `.sticky-bar__size` 12 px incl. the live price (see H3).

Fix: move these six to `var(--step-small)` (14 px). Spec-sanctioned smaller text that is fine to
leave: eyebrows/breadcrumb/card meta at 11 px (`--step-eyebrow`), labels at 12 px, badges 9 px
(04b §7 "375px — 9px"), cart bubble 10 px (`:4072`, duplicated by `aria-label`).

### M5. Tap targets under 44 × 44 CSS px (04b Part 4.2)

- `assets/css/site.css:3469-3475` — `.qty--sm` 40 px tall, 40 px buttons, 40 px input on the cart page (04b §5 375px: 44 tall, 44×44 buttons, 52 px input).
- `assets/css/site.css:2149-2153` — `.chip` (active-filter remove links, `listing.php:189`) `min-height: 2.25rem` = 36 px.
- `assets/css/site.css:2105-2110` — `.filters__option` rows in the drawer 36 px, checkbox 20 px (`:1942`).
- `assets/css/site.css:3895-3899` — `.site-footer__link` 36 px rows with 4 px gap (adjacent-target clearance is 8 px in 04b 4.2).
- `assets/css/site.css:725-750` — announcement link inside a 36 px bar (`--announcement-h: 2.25rem`).

Fix: raise each `min-height` to `var(--tap)`; for the chip keep the visual 36 px and add a
`::before { inset: -4px 0 }` hit extension; footer links `min-height: var(--tap)` with `gap: 0`.

### M6. Announcement marquee slides over its own dismiss button

- `assets/css/site.css:782-788` — `.announcement--long .announcement__text { overflow: visible; padding-inline-start: 100%; animation: announcement-marquee 40s … }`
- `assets/css/site.css:740-750` — `.announcement__inner` only reserves `padding-inline: var(--tap)`; nothing clips the text before it reaches the absolutely positioned `.announcement__dismiss` (`:764-776`).

Every mobile screenshot shows "FR×E D…" — the × glyph drawn through the moving text at the
top-right. Fix: `.announcement__inner { overflow: hidden; }` plus a wrapper
`.announcement__track { overflow: hidden; flex: 1; }` around the text, and give the dismiss a
`background: inherit` (or a 44 px wide gold gradient) so text passes underneath, not through it.
(Reduced-motion fallback at `:5228-5237` is correct.)

### M7. Checkout: the order summary comes after Place Order, and the "sticky" summary class is inert

- `app/views/checkout.php:183-187` — Place Order + terms, then `:188-236` the `<aside>` with lines, coupon, totals.
- `assets/css/site.css:1348-1352` — `.summary--sticky-mobile { position: sticky; bottom: 0; z-index: 200 }`, but the element is the only child of its `<aside>` so it has no room to stick; on `cart.php:87` the same class is dead, and 03 §7.2 promised "the checkout button also appears as a sticky bottom bar" below 1024 px.

On a phone the customer sees a Rs. 16,850 button before any line item, and on the cart page
must scroll past every line to reach checkout. Fix: on `<1024px` render a collapsed
`<details class="summary-peek">` ("2 items · Rs. 16,850 — View") at the top of the checkout
form via CSS `order: -1` on `.checkout-layout__summary`; on the cart page add a real fixed
bar (reuse `.sticky-bar` with the total + `Proceed to Checkout`) and drop the dead class.

### M8. Filter facet counts fail contrast

- `assets/css/site.css:2123-2127` — `.filters__option-count { color: var(--ink-400) }` (#4A443C) on the drawer ground #1C1A18 ≈ 1.7:1.

04a §2.1 lists ink-400 as "FAIL (intentional)" for **disabled** labels only; the counts are
information. Fix: `color: var(--text-muted)` (5.7:1 on the overlay ground).

### M9. In-page anchors land under the sticky header

- No `scroll-margin-top` / `scroll-padding-top` anywhere in `site.css` (grep is empty); `.site-header` is `position: sticky; top: 0` (`:3947`).
- Targets: `app/views/quiz.php:57-62` (`#question-n` Next/Back links), `app/views/home.php:60` (hero cue → `#collections`), `app/views/product.php:66` (stars → `#reviews`), `layout.php:32` skip link → `#main`.

The first 56 px of every anchor target hides under the header. Fix (one line, `@layer base`):
`[id] { scroll-margin-top: calc(var(--header-h) + var(--sp-4)); }`.

### M10. Mobile nav is opened as a dialog but lacks dialog semantics; hamburger is dead without JS

- `app/partials/nav-mobile.php:20` — `<nav id="site-nav" … aria-hidden="true" hidden>` has no `role="dialog"` / `aria-modal="true"`, although `ui.js:352-363` traps focus in it. VoiceOver users can swipe out of the menu into the page behind.
- `app/partials/header.php:22` — the burger is a `<button>`; with JS off it does nothing, while 04b §16.2 says it is a plain `<a href="#site-nav">` to the footer nav (`layout.php:41-47` renders that list, hidden only via `html.js`, `site.css:5143`).

Fix: add `role="dialog" aria-modal="true" aria-label="Menu"` to the nav (or wrap it in a `<div role="dialog">`), and render the burger as `<a class="site-header__burger js-nav-toggle" href="#site-nav-fallback" role="button">` with `id="site-nav-fallback"` on the fallback `<nav>`; `ui.js:357` already `preventDefault()`s.

### M11. Font swap layout shift on every page (see H7)

With `font-display: swap` and no metric-compatible fallback, the H1 in Times → Cormorant swap
changes line count on 375 px (e.g. "Frequently Asked Questions" wraps differently). Counted with
H7; fix is the same fallback `@font-face` pair.

---

## Low

### L1. "Prepaid" badge creates an implicit third grid column
- `assets/css/site.css:1452-1456` — `.choice__badge { grid-column: 3 }` in a two-column `.choice__card` (`:1401-1403`); the copy text is squeezed to ~55 % width and "Prepaid" is unstyled body text (`checkout.php:134`). Fix: `grid-column: 2; grid-row: 1; justify-self: end;` and style it as `.status-pill status-pill--muted`.

### L2. Transfer panel: hint paragraph touches the next label
- `assets/css/site.css:4888` — only `.panel > p + p` gets spacing; `.field` after `p` has none (`checkout.php:155-157`, `:159-163`). Fix: `.panel > * + * { margin-block-start: var(--sp-3); }`.

### L3. "All collections" / "View all new arrivals" buttons flush under the rail
- `assets/css/site.css:1084-1088` — `.btn-row` has no top margin; `home.php:71-73` and `:102-104` place it right after `.rail`/`.grid`. Fix: `.section .rail + .btn-row, .section .grid + .btn-row { margin-block-start: var(--sp-5); }`. (Already noted as cosmetic in the run report.)

### L4. Header logo is illegible at 44 px and costs 77 KB per page
- `app/partials/header.php:56` — `logo.png` (160×181, 76.7 KB) shown at `max-height: calc(3.5rem - 0.75rem)` = 44 px (`site.css:4032-4038`); "SKY FRAGRANCES" under the monogram becomes ~5 px text. `logo.svg` (102 KB) and `logo-mark.svg` (165 KB) are larger, not smaller. Fix: use `brand/lockup-on-black-800.webp` (18 KB) or an optimised 2-path SVG monogram for the mobile header (`<picture>` with `media="(max-width: 1023px)"`), keep the lockup from 1024px.

### L5. Toasts cover the WhatsApp FAB and the filter bar
- `assets/css/site.css:4597-4607` — `.toast-region { bottom: 16px; left/right: 16px; z-index: 500 }` sits over the FAB (z 300) and the listing filter bar (z 200); 04b §14 wants toasts "stacked above the sticky add-to-cart bar and the WhatsApp button". Fix: `bottom: calc(var(--fab-size) + var(--sp-6) + var(--safe-bottom))` on mobile, `.is-raised` adds the sticky-bar height on top.

### L6. Search-suggest keyboard highlight is not announced
- `assets/js/forms.js:190,195` — suggestion `<a role="option">` items have no `id`; `:239` sets `aria-activedescendant` to `''`. Fix: assign `id = panel.id + '-opt-' + i` when rendering.

### L7. Native validation switched off in markup
- `app/partials/newsletter.php:22` and `app/views/quiz.php:16` hard-code `novalidate`, so with JS off nothing validates client-side; `forms.js:91` already adds it for `.js-validate` forms. Fix: remove the attribute from the markup.

### L8. Listing pages skip from `h1` to the card `h3`
- `app/partials/product-card.php:180` — every card name is `<h3>`; `listing.php` has no `h2` between the page `h1` and the grid (axe "heading-order"). Fix: add `<h2 class="u-sr-only">Results</h2>` above `.grid--products` in `listing.php:197`, or make the card heading level a partial parameter.

### L9. Second skip link missing on listing pages
- 04b Part 4.3 asks for "Skip to filters" on listings; `layout.php:32` renders only "Skip to content". Fix: emit a second `.skip-link` (`href="#filters-drawer"` opening the drawer via `data-dialog-open`) when `$landingKind` is a listing.

### L10. WhatsApp FAB is raised on the PDP before the sticky bar exists
- `app/partials/whatsapp-button.php:11` — `--raised` is static; until the customer scrolls past Add to Cart there is an 80 px gap under the FAB. Fix: toggle `whatsapp-fab--raised` from `reveal.js:96-103` together with `has-sticky-bar`.

### L11. Every page loads `product.js`
- `app/views/layout.php:53-57` — five deferred scripts on every page (59 KB raw); `product.js` (11.5 KB) is PDP-only. Fix: emit it only when `$isProduct`. `site.css` at 149 KB raw / 22 KB gz as one render-blocking file is acceptable for now; if Lighthouse mobile flags it, inline the `tokens`/`reset`/`base` layers (≈ lines 1–405) as critical CSS.

### L12. Lightbox double-tap conflicts with browser zoom
- `assets/js/product.js:287-289` toggles `is-zoomed` on `dblclick`; `site.css:2812-2824` `touch-action: pinch-zoom` still allows the double-tap page zoom on iOS, and the `scale(2)` image cannot be panned. Fix: `touch-action: manipulation` on `.lightbox__img` and rely on pinch-zoom only, or make `.lightbox__slide` `overflow: auto` when zoomed.

### L13. Quiz is one 11,000 px form on a phone
- `app/views/quiz.php:16-70` — all five questions render at once; "Next →" only scrolls. Works, but the progress bar ("Question 2 of 5 · 40 %") reads oddly when everything is visible. Optional: with JS, show one fieldset at a time (`hidden` on the rest) and let the anchors advance.

---

## Checked and passing (no action)

- **Horizontal overflow**: none in any of the 25 mobile captures (all content within the gutters; `body { overflow-x: clip }` at `site.css:293` is a safety net, not masking a bug).
- **Images with dimensions**: every `<img>` in views/partials/JS carries `width`/`height` or lives in an `aspect-ratio` box (`.hero__media`, `.product-card__media`, `.collection-card__media`, `.suggest__media`, `.upload__preview`); the hidden desktop gallery and the mobile rail share one `srcset`, so the LCP image is fetched once.
- **Reduced motion**: one `@media (prefers-reduced-motion: reduce)` block (`site.css:5221-5268`) removes reveal, hover zoom, marquee, skeleton sheen, sticky-bar/drawer blur and meter fills; `reveal.js:31-34, 47-53`, `ui.js:171, 323, 395`, `product.js:142, 240, 245` honour it and re-check on `change`.
- **Drawer/modal semantics**: `ui.js:212-247, 249-324` — focus moves to the close button, Tab/Shift+Tab trapped with a live query, Escape closes, overlay tap closes, focus returns to the trigger (or `#main`), `transitionend` guarded by a timeout (04b 4.7), FAB and sticky bar hidden while locked (`site.css:5154-5158`).
- **Skip link**: `layout.php:32` + `site.css:501-519`, `transform` off-screen, visible on `:focus`, lands on `main[tabindex=-1]`.
- **Form labels and error association**: every control has a `<label for>` (or `aria-labelledby` for the upload), a persistent `aria-describedby="err-*"`, `aria-invalid` on failure, a `role="alert"` summary, and focus moves to the first invalid control (`forms.js:83-86`) — except the radio groups in H4.
- **Phone keyboard and autofill on checkout** (`checkout.php:73-117`): `type="tel" inputmode="tel" autocomplete="tel"`, `type="email" inputmode="email"`, `autocomplete="name|address-level2|postal-code|street-address"`, `inputmode="numeric"` on postal code, `autocapitalize="characters" spellcheck="false"` on the transaction ID and coupon, `<datalist>` cities, `accept="image/jpeg,image/png,image/webp"` on the proof upload. Quantity inputs use `type="text" inputmode="numeric" pattern="[0-9]*"` (04b §5).
- **Contrast (04a §2)**: all text/ground pairs in use are the audited tokens — ivory 17.5:1, muted 6.75:1, gold 9.75:1, ink on gold ≥ 5.2:1 at the darkest gradient stop, gold-800 on the ivory band 5.56:1, gradient text only on `h1`/display sizes with a flat `color` fallback (`site.css:398-404`). Focus ring is 04a §2.7 gold 1 px / 3 px offset; every `outline: none` has a replacement border + halo.
- **Heading order**: one `h1` per page; `h2 → h3` respected on home, PDP, cart, checkout, track, receipt, contact, FAQ, 404 (L8 covers the listing exception).
- **Safe areas**: `viewport-fit=cover` and `env(safe-area-inset-bottom)` on the sticky bar, filter bar, drawer footer, FAB, toast region, lightbox counter and mobile nav.
- **Scripts**: all five are `defer`; no inline handlers; CSP-safe.
