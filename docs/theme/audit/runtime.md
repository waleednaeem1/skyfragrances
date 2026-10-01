# Light theme: runtime audit (everything outside the three stylesheets)

Scope: what a site-wide, admin-switchable light theme touches in PHP templates, the settings system, image assets, motion JavaScript and the dev-tools verification scripts. The three stylesheets (critical.css, site.css, motion parts) are covered in `critical.md` and `site-motion.md` next to this file.

Verified on 2026-10-01 against the working tree and a local server on port 8120 (stopped afterwards). No code was changed.

Legend used in every table:

- **THEME**: must change (or gain a light variant) for the light theme.
- **KEEP**: unaffected, or already theme-safe because it reads tokens / `currentColor`.
- **BRAND**: a brand constant that stays the same in both themes on purpose (off-site previews, OS chrome, emails, invoices).

---

## 1. Applying the theme with no flash

### 1.1 Where `<html>` is emitted

| File | What it renders | Verdict |
|---|---|---|
| `site/app/views/layout.php` line 26: `<html lang="en">` | every storefront page, **including the 404 / error view** (`app/views/404.php` renders through the layout; runtime check: `/nope-404` returns `<body class="error error-404">` inside the layout) | THEME: add `data-theme="<?= e($theme) ?>"` here |
| `site/app/lib/response.php` `abort_plain()` line 90 | bare 4xx/5xx page, inline `style=` attributes: `background:#0a0a0a;color:#f5f0e8`, h1 `#d4b084` | THEME (small): swap the three literals when the setting is light; settings are already loaded by the time `abort_plain` runs |
| `site/app/bootstrap.php` `bootstrap_render_500()` line 65 | fatal / uncaught error page, `<style>` block `#0A0A0A / #F5F0E8 / #D4B084 / #151515` | KEEP dark. It can fire before `settings_load()` or because the DB is down, so it must not depend on settings. A one-dark-page exception is acceptable |
| `site/app/bootstrap.php` `bootstrap_render_unconfigured()` line 115 | pre-install page | KEEP (no settings exist yet) |
| `site/app/lib/maintenance.php` line 106 | maintenance page, full `<style>` block with 10 hard-coded colours (ground, ivory text, gold h1, WhatsApp ghost button, bypass form input/button, `.err` red) | THEME: it already reads settings when `$loaded`; give it a second palette string. Fall back to dark when settings did not load |
| `site/app/tools/reset-password.php` line 27 | CLI-ish recovery tool page (own palette `#0b0b0d`, `#c9a962`) | KEEP (operator tool, not storefront) |
| `site/app/lib/mail.php` line 69 | email HTML | excluded by brief; KEEP (emails stay on their own palette) |
| `site/admin/views/layout.php` line 31 `class="adm-root"`, `print-invoice.php`, `print-packing-slip.php` | admin panel and print views | KEEP: the admin is already light (`t-light` in body class) and is not part of the storefront toggle |
| offline page / service worker / web manifest | none exist (grep for `offline`, `manifest`, `sw.js` found nothing) | n/a |

### 1.2 How the head loads assets (`site/app/partials/head-meta.php`)

Order inside `<head>`, all external files, versioned with `asset()` (`?v=filemtime`):

1. `<meta name="theme-color" content="#0A0A0A">` (line 132), hard-coded. **THEME**: emit `#F5F0E8` (ivory-100) or the final light ground token when light.
2. `js/motion/gate.js`: a **blocking** classic script (no defer), carries `data-page="home"` and `data-intro=".../intro.js"` on the home page. It adds `sf-reduce` / `motion--*` and `sf-intro-full|calm` classes to `<html>` and injects `intro.js` synchronously (`async=false`). Because `data-theme` is already on `<html>` in the HTML source, gate.js can read `root.dataset.theme` with no ordering risk and publish it as `SF_GATE.theme`.
3. CSS. Three branches: (a) home/listing/product (not `/search`): `critical.css` render-blocking + `site.css` as `media="print" data-media="all"` swapped by `js/css.js` (async) with a `<noscript>` fallback; (b) other pages: `critical.css` + `site.css` both blocking; (c) no critical.css: `site.css` only.
4. `css/motion.css` (id `sf-motion-css`) only on motion pages (`home`, `listing`, `collections`, `product`, `quiz` body classes).
5. The single inline script `document.documentElement.classList.add('js');`.
6. `js/css.js` async (deferred-CSS pages only), `js/motion/config.js` defer, `js/motion/core.js` module defer with `data-motion-v`.
7. JSON-LD blocks (`type="application/ld+json"`, allowed by the build check).

Flash analysis: the ground colour and all token remaps must live in **critical.css** under `html[data-theme="light"]` so the first paint is already light. Anything that is only in site.css will flash dark-then-light on the three deferred-CSS pages (home, listing, product) for the time `css.js` takes to swap the print stylesheet. motion.css is render-blocking where present, so light overrides for motion parts are safe there.

### 1.3 CSP constraint

- `response_csp()` in `site/app/lib/response.php` line 104: `script-src 'self' 'sha256-/x7W7R75k8Roq0WaVRQX9blP4OufE5xbAdzklGxsgpw='`. The hash is `CSP_BOOTSTRAP_SCRIPT_HASH` (line 94) and covers exactly `document.documentElement.classList.add('js');`.
- `dev-tools/build-zip.php` lines 172-178 assert that `head-meta.php` has **exactly one** `<script>…</script>` and that its sha256 matches the constant; lines 186-195 abort the build if any other file under `app/views`, `app/partials`, `admin/views`, `admin/partials` carries an inline script without `src=`.
- `style-src 'self' 'unsafe-inline'` means inline `style=""` attributes (for example the maintenance page `<style>` and the intro `--sf-intro-mark` custom property) stay allowed.

Consequence: the theme **must be server-rendered**. Put `data-theme="dark|light"` on `<html>` in `layout.php` from `setting('site_theme', 'dark')`. Do not touch the bootstrap inline script (changing it breaks the hash and the build). No `localStorage`/`prefers-color-scheme` switcher is needed; the owner picks one theme for everyone.

### 1.4 `color-scheme`

There is no `color-scheme` anywhere today (no meta, no CSS property; grep over all CSS returned nothing). So the UA currently treats the dark storefront as a light scheme: native scrollbars, `<select>` popups, date pickers and autofill are light-styled on the dark site. Recommendation for the implementation step:

- `<meta name="color-scheme" content="dark">` or `content="light"` emitted next to `theme-color` in head-meta.php, matching the setting (server-side, no script), and
- `color-scheme: dark` on `:root` / `color-scheme: light` on `html[data-theme="light"]` in critical.css.

This fixes a latent dark-theme wart as well; flag it to the owner as a visible change (scrollbars turn dark on the dark theme).

---

## 2. The settings system

### 2.1 How a setting is declared (`site/admin/controllers/settings.php`)

- `SETTINGS_TABS` (line 4) is an ordered `slug => label` constant: `store, contact, home, shipping, payments, seo, advanced`. The tab strip, `?tab=` whitelist and restore flow all derive from it.
- `settings_definitions()` returns `key => [tab, type, label, opts]`, normalised into `['tab','type','label'] + opts`. Types in use: `text`, `textarea`, `image`, `readonly`, `phone`, `email`, `url`, `bool`, `int`, `money`, `secret`, `baseurl`, `cidr`, `https`, `capability`. Options: `required`, `maxlength`, `rows`, `min`, `max`, `relative`, `help`.
- Validation is `settings_clean()`: a `switch` on type. **Any type not listed falls through to `return [null, null]`**, which the POST loop then stores as an empty string. So a new enum type needs its own `case`.
- `bool` is handled before `settings_clean`: `request_post($key) === '1' ? '1' : '0'` (unchecked checkbox = `'0'`).
- POST flow: loop `settings_tab_keys($tab)`, skip `readonly/secret/https/capability`, build `$changes`, tab-specific rules (only payments has one), then `settings_write($changes, $tab)` and `settings_log()` into `admin_activity_log` (secrets masked via `SETTINGS_SECRET_KEYS`).
- `settings_write()` upserts `settings(setting_key, setting_value, setting_group=tab, updated_at)`, then `settings_cache_clear()` and `settings_load()`.
- Route: `admin.settings` GET|POST `/admin/settings`, capability `admin-settings` (`site/app/routes-admin.php` line 82). A new tab needs **no new route**.

### 2.2 Reading settings (`site/app/lib/settings.php`)

- `settings_defaults()` (line 4): code-level defaults merged under DB rows. Add `'site_theme' => 'dark'` here so a missing row, a fresh install and a failed DB read all mean dark.
- `setting($key, $default)`: returns `$default` when the value is missing, blank or contains `REPLACE ME`. `setting_bool()` uses `FILTER_VALIDATE_BOOLEAN`.
- Cache: `storage/cache/settings.json`, reused when its mtime is **under 300 s** old (`settings_load()` line 102), written atomically via temp file + rename. `settings_cache_clear()` unlinks it (and a legacy `storage/cache/settings.php`).
- Invalidation: every admin save goes through `settings_write()`, which clears and immediately reloads the cache, so the toggle takes effect on the next storefront request. The 5-minute window only bites when someone edits the `settings` table directly (phpMyAdmin, seed import); document "wait up to 5 minutes or delete storage/cache/settings.json" for that case.
- HTML is not cached anywhere else: storefront responses are rendered per request (`app/lib/view.php` output buffering only). Browsers may still show the old theme from the back/forward cache on pages the visitor already opened; acceptable.

### 2.3 How fields render (`site/admin/views/settings.php` + `site/admin/partials/field.php`)

- The view maps definition types to field.php types through `$inputType` (`url/baseurl/cidr/int/money/email/phone/textarea`, default `text`). `bool` is special-cased to `field.php` type `checkbox` (renders `.adm-check` with a custom box; there is **no switch/toggle style** in the admin today). `image` has its own inline block.
- `field.php` already supports `select`, `radio`, `segment` and `checkgroup` with an `$options` array (`value => label`), proper `role="radiogroup"` and `aria-labelledby`. `segment` renders `.adm-segment` / `.adm-segment__item` radio pills, which reads like a two-state toggle.

### 2.4 Adding an Appearance tab: the concrete change list

1. `SETTINGS_TABS`: add `'appearance' => 'Appearance'` (suggest placing it after `store`).
2. `settings_definitions()`: `'site_theme' => ['appearance', 'choice', 'Storefront theme', ['options' => ['dark' => 'Dark', 'light' => 'Light'], 'help' => 'Changes the whole shop for every visitor. The admin panel is not affected.']]`.
   - Alternative that needs no new type: `'theme_light' => ['appearance', 'bool', 'Use the light theme', []]`, read with `setting_bool('theme_light')`. It is literally a single toggle, but the value name is less extensible and it renders as a checkbox. The `choice` + `segment` route is recommended.
3. `settings_clean()`: add `case 'choice': return array_key_exists($value, $def['options']) ? [$value, null] : [null, 'Choose one of the listed options for ' . $label . '.'];` (and treat blank as the default, not an error).
4. `admin/views/settings.php`: map `'choice' => 'segment'` in `$inputType` and pass `'options' => $def['options']` into `$fieldArgs`. Optional delight: two small swatch previews (ink/gold and ivory/gold) inside the segment labels, CSS-only in admin.css.
5. `app/lib/settings.php` `settings_defaults()`: `'site_theme' => 'dark'`.
6. `db/seed.sql`: optional `INSERT IGNORE ... ('site_theme', 'dark', 'appearance', NULL)` so the row exists; not required because of the default.
7. Storefront read point: one helper, for example `storefront_theme(): string` returning `'light'` only when `setting('site_theme') === 'light'`, used by `layout.php`, `head-meta.php` (theme-color, color-scheme), `abort_plain()` and `maintenance.php`.
8. Activity log: nothing to do, `settings_log()` records `Settings changed (Appearance): site_theme` with before/after automatically.

### 2.5 Restore-wording allow-lists: must NOT include the theme key

There are two allow-lists and neither may gain `site_theme`:

- `SETTINGS_WORDING_KEYS` in `admin/controllers/settings.php` line 13 (11 copy keys). `settings_wording_keys($tab)` keeps a key only if it is in this list, is on the tab, is not secret, and exists in `default_copy()['settings']`. The action bar only shows "Restore default wording" when that list is non-empty (`admin/views/settings.php` line ~103), so an Appearance tab with no wording keys correctly shows no restore button.
- `COPY_SETTINGS_ALLOW` in `dev-tools/extract-default-copy.php` line 8, which builds `app/data/default-copy.php` from seed.sql. Keep the theme key out so a "restore wording" can never flip the look of the shop.

---

## 3. Images and graphics in the storefront chrome

Runtime check (Playwright, reduced motion, `/`, `/shop`, `/collections`, `/product/azure-oud`, `/scent-finder`, `/cart`, a 404): the only chrome raster files fetched were `brand/monogram-transparent-256.webp` and `brand/lockup-on-black-800.webp`, plus product and collection uploads. The hero plates load only when motion is allowed; favicons are fetched by the browser outside the page.

Contrast reference: the current mark gold (about `#D4B084`) on ivory-100 `#F5F0E8` is roughly **1.8:1**, below the 3:1 a logo should hold. Gold-700 `#8A6538` on the same ivory is roughly **4.5:1**. That gap is why every gold-on-transparent chrome mark needs a deep-gold variant rather than being reused as is.

| Asset | Where used | Backing | Verdict |
|---|---|---|---|
| `assets/img/brand/monogram-transparent-256.webp/.png` | header logo (`partials/header.php` `<picture>`), intro `<img>` and intro shimmer mask (`--sf-intro-mark` inline style in `partials/intro.php`, mask in critical.css line 2801) | pale champagne-gold gradient on transparent | THEME: needs `monogram-deep-256.webp/.png` (same alpha, recoloured gold-600 to gold-800 gradient). The shimmer mask only uses alpha, so it can keep the current file |
| `assets/img/brand/monogram-transparent-512.png` | product gallery placeholder (`partials/gallery.php`), related-products fallback (`partials/related.php`), sticky bar thumb (`views/product.php` line 31) | same pale gold on transparent | THEME: `monogram-deep-512.png` |
| `assets/img/brand/lockup-on-black-800.webp` | footer logo (`partials/footer.php` line 51) | **opaque black square** with pale gold lockup | THEME: a black tile in an ivory footer is the single most jarring item. Needs `lockup-deep-800.webp` on transparent (deep gold wordmark), selected server-side |
| `assets/img/brand/lockup-transparent-400/800.png` | admin order print only (`admin/controllers/order-print.php`) | pale gold on transparent | KEEP (admin print) |
| `assets/img/brand/monogram-on-black-512.png`, `assets/img/logo.svg`, `assets/img/logo-mark.svg`, `assets/img/favicon-16.png` | not referenced by any PHP/CSS/JS | dark-backed (`logo-mark.svg` has a `#0A0A0A` rect) | KEEP (unused; candidates for cleanup, not theme work) |
| `assets/img/logo.png` | `logo_path` default: JSON-LD Organization logo, emails, admin login | pale gold on transparent | BRAND (off-site and email use) |
| `favicon.ico`, `assets/img/favicon-32.png`, `assets/img/apple-touch-icon-180.png` | head-meta.php lines 149-151 | opaque dark tiles with gold monogram | BRAND: browser tabs and home screens stay the brand tile in both themes |
| `assets/img/og-default.jpg` (1200x630) | default `og:image` / `twitter:image` | black with gold lockup | BRAND: shared previews are off-site |
| `assets/img/motion/ribbons-a-540/960.webp`, `ribbons-b-540/960.webp` | hero plates: URLs in `data-plate-*` attributes on `.hero__glow` (`views/home.php` line 64), drawn onto canvases by `motion/hero.js` | **opaque**: near-black ground, warm radial glow, gold ribbons | THEME: need `ribbons-{a,b}-light-{540,960}.webp`; `home.php` picks the set from the theme. See 3.1 |
| `assets/img/placeholder-4x5.svg` | product card and product page fallback image (`partials/product-card.php`, `controllers/product.php`) loaded as `<img>` | hard-coded `#141312` rect, `#D4B084` stroke, `#9C968C` text | THEME: `<img>` cannot inherit `currentColor`, so add `placeholder-4x5-light.svg` (ivory-200 ground, gold-700 stroke at 0.45, ivory-500 text) chosen server-side |
| `partials/placeholder.php` inline SVG "SF" | generic placeholders | `fill="currentColor"` | KEEP (token-driven) |
| `partials/icon.php` (25 icons), inline SVGs in `views/cart.php`, `checkout.php`, `track.php`, `confirmation.php`, `product.php`, `partials/stars.php` | UI icons | `stroke="currentColor"` / `fill="currentColor"`, no literal colours | KEEP |
| WhatsApp FAB (`partials/whatsapp-button.php`) | fixed button | icon is `currentColor`; ground `var(--surface-overlay)` in critical.css; `--whatsapp-green` accent | KEEP in markup; colours resolve through tokens (the CSS audits decide its light look) |
| `uploads/products/sample/*` (252 files, 12 sample products) | sample catalogue cards, gallery, story figure | dark teal/violet mist behind an outline bottle, fully opaque | KEEP (content). Owner photos replace them. For demo screenshots of the light theme a light-backed sample set would flatter it; optional, not a blocker |
| `uploads/collections/*` (5 sample collections) | collection cards, listing hero | dark gradients (for example azure-heights is a near-black navy) | KEEP (content). In light theme the listing hero keeps a dark photo band with ivory text, which is a deliberate, rich contrast moment; the CSS must keep hero text on the dark scrim rather than switching it to ink |
| Owner-uploaded `hero_image_desktop/mobile`, gender tiles, Instagram tiles | settings uploads | unknown, usually photography | KEEP; the home hero overlay (`--hero-opacity`, `hero_overlay_opacity`) is a dark scrim today; the CSS audit decides whether light mode uses an ivory scrim instead |

### 3.1 How `dev-tools/render-plates.mjs` produces the plates

- Launches Chromium with SwiftShader WebGL at 1920x1080, DPR 1, sets `sessionStorage.sfIntro` to skip the intro, opens `BASE/?plates=1` (the query flag is not read by any JS; it is inert), waits for `.hero.is-gl`.
- Injects a style that hides everything except `.hero__glow` and `.hero__ribbons`, hides the plates themselves, zeroes `.hero__glow::before`, and **forces `body { background: #0A0A0A }`**.
- Measures `.hero__object` to get the GL unit size, then screenshots a 9-units-wide, 2:1 clip at t = 2.5 s (plate a) and 6.5 s (plate b) to `docs/shots/plates/plate-{a,b}-raw.png`, plus `frame.json`.
- Conversion from the raw PNGs to the shipped 540/960 webp files is **not scripted** (done by hand). The light pipeline should add that step so both sets are reproducible.

Light run needs: the storefront serving `data-theme="light"` (setting flipped in the local DB), the GL ribbons already using the light palette and normal blending (section 4), the forced body background changed to the light ground (make it a third CLI argument), and output names with a `-light` suffix.

---

## 4. Motion JavaScript colour decisions

### 4.1 How the code can learn the theme (no new inline script)

`data-theme` is in the HTML source on `<html>`, so it exists before any script runs:

- `gate.js` (blocking, in `<head>`): add `theme: root.getAttribute('data-theme') === 'light' ? 'light' : 'dark'` to `window.SF_GATE`.
- `config.js` (deferred) already merges `SF_GATE` into `SF_MOTION` at its tail (`cfg.flags`, `cfg.device`, `cfg.classify`). Add `cfg.theme = gate.theme || 'dark'` there, and keep every colour in config as a `{ dark: …, light: … }` pair resolved once, for example a small `pick(obj)` that returns `obj[cfg.theme] || obj.dark`. Modules then read plain values as today and stay theme-agnostic.
- `intro.js` is injected by gate.js before config.js runs, so it reads `document.documentElement.dataset.theme` directly (it already reads `SF_MOTION` lazily for timings).
- Prefer CSS custom properties for anything a module paints into the DOM (spray dots, cursor, veil, story glows): the JS keeps setting geometry only and the colour follows `html[data-theme="light"]` rules. Only canvas and WebGL need numeric colours in JS.

### 4.2 Module by module

| File | Colour decision today | Light-ground problem | Change | Verdict |
|---|---|---|---|---|
| `motion/config.js` `hero.ribbons.colors` | `gold #C29C6E`, `amber #E0A45C` | pale warm gold nearly vanishes on ivory and reads as a stain where it accumulates | light pair such as gold-700 `#8A6538` and gold-600 `#A87F4E` (amber replaced by a deeper bronze, not a brighter one) | THEME |
| `motion/config.js` `hero.ribbons.halo` `{ scale 3.4, alpha 0.18 }`, `gain 0.6`, `pulse.gain 0.2` | wide soft halo that glows on black | a halo on ivory is a grey-brown smear | light: halo alpha about 0.06, gain about 0.75 so the core line is crisp, pulse gain 0.1 | THEME |
| `motion/config.js` `hero.sprite` `{ opacity 0.35 }` | warm backlight sprite in the average of gold/amber | on ivory it muddies the centre | light: opacity 0.12 to 0.15 in gold-200, or off | THEME |
| `motion/config.js` `hero.groundMaxOpacity 0.6` + `hero.js` `groundTooBright()` | blocks WebGL when the hero photo is too opaque (light-on-dark legibility guard) | the guard assumes additive light; with normal blending on ivory the concern is dark photo vs dark ribbons | keep logic, give a light threshold in config if the light hero keeps a photo | KEEP (logic), tune later |
| `motion/config.js` `story.hues.families` (6 families x 3 notes) and `css['story-glow-alpha'] 0.22`, `css['story-wash-alpha'] 0.06` | pastel hues painted as glows | on ivory with `mix-blend-mode: screen` (30-story.css line 85) they are invisible; with normal blending pastels become weak tints | light: deepen each hue (roughly 35-45 % lower lightness, same hue angle) and raise glow alpha to about 0.30; the blend mode lives in CSS (multiply or normal under `[data-theme=light]`) | THEME |
| `app/views/product.php` line 161 `$storyHueMap` | per-collection hue overrides as `data-motion-hue-top/heart/base` attributes (5 collections) | same pastels as above | either a second map for light, or keep one map and let story.js darken by a factor when `cfg.theme === 'light'` (preferred: one source, one transform) | THEME |
| `motion/ribbons-gl.js` context | `webgl2`, `alpha: true`, `premultipliedAlpha: true`, `clearColor(0,0,0,0)` | fine: transparent canvas over the page | none | KEEP |
| `motion/ribbons-gl.js` blending, line 268 `gl.blendFunc(gl.ONE, gl.ONE)` | additive: overlaps and halos sum toward `uAmber * 1.15` and alpha 1, i.e. glow | on ivory additive light pushes toward white, so crossings become pale holes | light: `gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA)` (premultiplied over). Pass `opts.blend: 'over' or 'add'` from hero.js based on `cfg.theme` | THEME |
| `motion/ribbons-gl.js` fragment clamp `min(..., uAmber * 1.15)` and pulse `* (1.0 + pulse * uPulse.w)` | the travelling pulse brightens the ribbon | brightening on ivory reads as fading | light: pulse should darken or add saturation: `uPulse.w` negative, or a `uPulseSign` uniform; the clamp becomes `max(..., uGold * 0.85)` style floor | THEME |
| `motion/ribbons-gl.js` sprite shader `outColor = vec4(uColor * a, a)` | premultiplied warm blob | see sprite row | driven by config | THEME (via config) |
| `motion/hero.js` plates | reads `data-plate-a/-b[-desktop]` from markup and `drawImage`s the opaque webp into canvases | dark plates on a light page | none in JS: `home.php` emits the light URLs | KEEP (JS), THEME (markup + assets, section 3) |
| `intro.js` particles, line 142 `ctx.fillStyle = 'rgb(214, 180, 136)'`, line 149 `globalCompositeOperation = 'lighter'` | pale gold dust summed with `lighter` over the black intro ground (critical.css line 2734) | pale dust on ivory is invisible and `lighter` saturates overlaps to white | light: fill gold-700 `rgb(138, 101, 56)`, `source-over`, slightly lower per-particle alpha (0.25 to 0.6); read `document.documentElement.dataset.theme` | THEME |
| `intro.js` mist | none in JS: `.sf-intro__mist` radial `rgba(255, 232, 190, 0.12)` and shimmer are in critical.css | CSS concern | none in JS | KEEP |
| `motion/micro.js` cursor, magnetic, spray, cart flight, tick | JS only sets geometry (`--dx/--dy/--d/--sz`), classes `sf-burst`, `sf-spray`; colours are `var(--accent)` and a black drop shadow in `40-micro.css` | accent remaps via tokens; the `rgba(0,0,0,0.45)` flight shadow is CSS | none in JS | KEEP |
| `motion/cards.js` fan, tilt, sweep | only transform custom properties; sweep gradient and `mix-blend-mode: soft-light` are in `20-cards.css` | soft-light ivory sweep on light cards is nearly invisible (CSS fix) | none in JS | KEEP |
| `motion/story.js` | `paint()` converts a hex to `--sf-story-rgb`; layer switching only | hue values (above) | optional `darken(hex, k)` when light, if a single hue map is kept | THEME (small) |
| `motion/quiz.js` | geometry, `--sf-quiz-dir`, fill ratios only; mist, card faces and glows are in `50-quiz.css` with `rgba(10,10,10,0)` stops | CSS concern | none in JS | KEEP |
| `motion/transitions.js` | toggles `.sf-veil` classes; veil colour `--sf-mist` with `#0A0A0A` in `60-transitions.css` | a black veil between light pages would flash | none in JS; CSS must give the light veil an ivory mist | KEEP |
| `motion/core.js` | writes `cfg.css` keys to `--sf-*` on `<html>` | if light values for `story-glow-alpha` etc. are resolved in config first, core.js needs no change | none | KEEP |
| `motion/gate.js` | device classification | gains the `theme` read (4.1) | one line | THEME (small) |

---

## 5. Storefront templates with hard-coded theme classes or colours

Grep over `site/app/views`, `site/app/partials` and `site/app/controllers` for `t-light`, `t-dark`, hex and `rgb(a)` literals and `style="`:

| Location | What is hard-coded | Verdict |
|---|---|---|
| `app/views/home.php` line 132 `<section class="section t-light" id="why-us">` | the only `t-light` band in the storefront; no `t-dark` exists anywhere | THEME: in light mode this band becomes the same ivory as the page and the rhythm of the home page is lost. Emit a theme-aware class instead, for example `t-light` in dark mode and a new `t-ink` (dark band) or `t-cream` (ivory-200 band) in light mode. A dark ink band in the middle of a light page is the more striking choice |
| `app/partials/head-meta.php` line 132 `theme-color #0A0A0A` | browser UI colour | THEME (section 1) |
| `app/views/product.php` line 161 `$storyHueMap` | 15 hex hues for 5 collections | THEME (section 4) |
| `app/views/home.php` line 64 `data-plate-*` | dark plate URLs | THEME (section 3) |
| `app/partials/header.php` lines 56-57, `footer.php` line 51, `gallery.php` line 7, `related.php` line 8, `views/product.php` line 31, `intro.php` lines 7-8 | brand image paths (pale gold marks, black lockup tile) | THEME: route through one helper, for example `brand_asset('monogram-256')`, that returns the deep variant when light |
| `app/partials/product-card.php` lines 43 and 51, `app/controllers/product.php` line 41 | `img/placeholder-4x5.svg` | THEME: same helper |
| `text-gold-grad` class in `views/home.php` 51, `listing.php` 116, `404.php` 34, `quiz-result.php` 14, `confirmation.php` 47 | gold gradient headline text; colour comes from `--grad-gold` in CSS | KEEP in markup; the gradient needs a deeper light stop set in CSS (pale `#F2E6D2` highlights disappear on ivory) |
| inline `style="--fill:…"`, `--i`, `--hero-opacity`, `--sf-intro-mark` (cart, quiz, meters, reviews, stars, home, listing, intro) | geometry, stagger index, opacity, mask URL only | KEEP |
| `app/lib/response.php` `abort_plain()` and `app/lib/maintenance.php` | inline dark palettes | THEME (section 1) |
| `app/bootstrap.php` 500 / unconfigured pages | inline dark palettes | KEEP (must not depend on settings) |
| `app/views/listing.php` hero, collection landing pages | photo hero with dark scrim; text is designed for dark | KEEP in markup; CSS keeps these heroes as dark photographic bands in both themes |

Side findings noticed during the sweep (not theme work, worth a separate ticket):

- `app/partials/intro.php` line 11 reads `setting('tagline', …)`, but the stored key is `store_tagline`, so the intro always shows the fallback text rather than the owner's tagline.
- The `favicon_path` setting (Store tab, default `assets/img/favicon.png`, a file that does not exist) is never read: head-meta.php hard-codes `favicon.ico`, `favicon-32.png` and `apple-touch-icon-180.png`. Uploading a favicon in the admin changes nothing.

---

## 6. Verification tools in `dev-tools/`

None of the tools has a theme parameter today. Since the theme is a server setting, the cleanest switch is on the server side: flip `site_theme` in the local DB (then delete `site/storage/cache/settings.json`) or let the dev router accept a local-only override. Given a light server, each tool needs:

| Tool | Today | Needed for light |
|---|---|---|
| `tour.mjs` (`node tour.mjs BASE OUT path…`, env `TOUR_COOKIE`, `TOUR_UA`) | full-page shots at 1440 and 375, scroll pass, `.sf-reveal` forced visible, transitions zeroed; reports status, overflow, console errors | no code change if the server is light; add an optional `TOUR_THEME` label to the file names (for example `home--desktop--light.png`) so dark and light sets can live side by side. It does not set `reducedMotion`; for prototype shots add `reducedMotion: 'reduce'` to the context as the brief asks |
| `motion-check.mjs` (`BASE JSON_OUT`) | 3 profiles (desktop-1440, mobile-360 Android UA, reduced-1440) over 6 pages; buy-path clickability, hero states, frame stats | no colour assertions, so it runs unchanged; add a `theme` field to the JSON output and one assertion that `document.documentElement.dataset.theme` matches the expected value so a stale settings cache cannot pass silently |
| `a11y.mjs` (env `BASE`, `OUT`, `ONLY`) | 12 pages, contrast via computed colours composited over a white fallback (`A.background` starts from `{255,255,255}`), focus rings, tab order | runs unchanged and is the most valuable check for light. Note two blind spots: elements over a `background-image` return early (hero text over plates is never measured), and the white fallback hides a missing page ground in dark mode. Add an `EXPECT_THEME` env to assert `data-theme` |
| `intro-check.mjs` (`BASE OUT`) | intro frames at 150-2300 ms for calm reduced-motion and full-360 | unchanged code; review screenshots for particle visibility and the ivory intro ground. The full desktop path is not covered by this tool (it probes 360 mobile and reduced) |
| `hero-gl-probe.mjs` (`BASE OUT`) | SwiftShader WebGL, 1440x900, dumps hero classes, canvas list and opacities, shots at 6 s and 10 s | unchanged; add a pixel sample of the ribbon canvas (read back via `toDataURL` or screenshot crop) to confirm ribbons are darker than the ground in light mode, which is the regression the blend change could introduce |
| `hero-mobile-probe.mjs` (`BASE OUT`) | Pixel 5 hero | unchanged; confirms the light plates load on mobile |
| `render-plates.mjs` (`BASE OUT`) | forces `body { background: #0A0A0A }`, writes `plate-{a,b}-raw.png` | needs a ground argument (third CLI arg, default `#0A0A0A`, light run `#F5F0E8` or the final light ground) and a suffix for output names; the webp conversion step should be scripted for both sets |
| `build-zip.php` | asserts one inline bootstrap script and the CSP hash | unchanged; it will catch any attempt to add a theme script inline |

---

## Tally

- **Must change (30)**: `<html data-theme>` in layout.php; `abort_plain` palette; maintenance palette; `theme-color`; new `color-scheme` meta; gate.js theme read; config.js theme resolution; `SETTINGS_TABS`; `site_theme` definition; `choice` case in `settings_clean`; settings view type/options mapping; `settings_defaults`; deep-gold monogram 256; deep-gold monogram 512; light footer lockup; light hero plates (4 files); light placeholder SVG; ribbon colours; halo/gain/pulse; sprite; story families and alphas; product.php hue map; GL blend function; GL pulse/clamp; intro.js particle fill and composite; story.js darken; home.php `#why-us` band class; home.php plate URLs; brand-asset helper for the 7 image call sites; render-plates ground argument.
- **Unaffected (35)**: 500 and unconfigured pages; reset-password tool; emails; admin layout and prints; admin-only lockup; 4 unused files; icons and inline SVGs; `placeholder.php`; WhatsApp markup; sample product art; collection art; owner uploads; GL context and clear colour; `groundTooBright` logic; hero.js plate loader; intro mist (CSS); micro.js; cards.js; quiz.js; transitions.js; core.js; `text-gold-grad` markup; geometry-only inline styles; listing hero markup; tour, motion-check, a11y, intro-check, hero-gl-probe, hero-mobile-probe (run unchanged against a light server); build-zip; the hashed bootstrap script; the settings cache mechanism; the two wording allow-lists (stay as they are, theme key kept out).
- **Brand constants (6)**: `logo.png`, `favicon.ico`, `favicon-32.png`, `apple-touch-icon-180.png`, `og-default.jpg`, `--whatsapp-green`.

## Risks

1. Light overrides placed in site.css instead of critical.css will flash dark on home, listing and product, because site.css is deferred there.
2. Editing the inline bootstrap script, or adding any inline script for the theme, breaks the CSP hash and fails `build-zip.php`.
3. Keeping additive GL blending on ivory makes ribbon crossings look like pale holes. Blend mode and palette have to change together, and the plates have to be re-rendered after that change, not before.
4. A `choice` type without its own `settings_clean` case saves an empty string, and the storefront silently falls back to dark.
5. Adding `site_theme` to `SETTINGS_WORDING_KEYS` or `COPY_SETTINGS_ALLOW` would let "Restore default wording" flip the look of the shop.
6. Direct DB edits of the setting take up to 5 minutes to show because of the settings cache; admin saves take effect at once.
7. The black `lockup-on-black-800.webp` tile in the footer and the pale header monogram (about 1.8:1 on ivory) are the most visible failures if the brand variants are skipped.
8. The a11y tool does not measure text over background images, so hero and plate legibility in light mode needs a manual or screenshot check.
