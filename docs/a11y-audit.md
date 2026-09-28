# Storefront accessibility audit (2026-09-29)

Scope: the storefront only (`/`, `/shop`, `/collections`, `/for-her`, `/product/azure-oud`,
`/scent-finder`, `/cart`, `/checkout`, `/track`, `/contact`, `/faq`, `/about`). The admin is
covered by its own phone-UX review. Specs applied: `04b §4` (fields), `04b §12–14` (drawer, modal,
toast), `04b §21` (reduced motion), `04a §2` (contrast and focus).

Everything below was measured in a real browser, not read off the source. The harness is
`dev-tools/a11y.mjs` (Playwright, Chromium 1208, imported from the rangeaahan `node_modules`; it is
dev tooling and never ships in the ZIP). Run it against a local server:

```bash
cd site && php -S 127.0.0.1:8103 dev/router.php &
BASE=http://127.0.0.1:8103 node dev-tools/a11y.mjs          # all twelve pages
ONLY=/shop,/cart node dev-tools/a11y.mjs                    # a subset
OUT=/tmp/a11y.json node dev-tools/a11y.mjs                  # JSON report (default /tmp/skyfr-a11y.json)
```

Exit code 0 means no failure; every failure is printed as `FAIL [kind] /path: detail`.

## What the harness checks, per page

| Check | Viewport | How it is measured |
|---|---|---|
| Visible focus ring on every Tab stop | 1280×800 | Presses Tab until focus wraps (up to 600 stops). For each stop it compares the computed `outline` and `box-shadow` of the element, its `::before`/`::after`, its next sibling (hidden radio + card patterns) and its `<label>` against the same values captured at rest, requires `:focus-visible` to match, and fails if focus lands on an invisible element or inside a closed dialog. |
| Cart drawer | 1280×800 | Focuses the header cart trigger, presses Enter, asserts the panel is `role="dialog" aria-modal="true"` with a name, that focus moved inside, that 24 Tab/Shift+Tab presses never leave the panel, that the focused control has a ring, that Escape closes it, unlocks the body and returns focus to the trigger with `aria-expanded="false"`. |
| Mobile navigation | 375×812 touch | Same dialog test on the burger; plus a second pass that opens it with the Space key, because the burger is an `<a role="button">`. |
| Filters drawer, lightbox | 375 / 1280 | Same dialog test on `/shop` and `/for-her` (filters) and `/product/azure-oud` (image lightbox). |
| Header search | 1280×800 | Enter on the search trigger must move focus to the input with a visible ring and `aria-expanded="true"`; Escape closes and returns focus. |
| Accessible names | 1280×800 | Every `input`, `select`, `textarea`, `button`, `a[href]`, `role=button` and `role=combobox` outside hidden regions must resolve a name through `aria-labelledby`, `aria-label`, `<label for>`, a wrapping label, `img[alt]`, text content, `title` or `value`. Exposed `<svg>` without a name and without a named parent also fails. |
| Images | 1280×800 | Every `<img>` must carry an `alt` attribute (empty is allowed for decorative images). |
| Headings | 1280×800 | Exactly one `h1`; the first heading is the `h1`; no level is skipped going down. Headings inside `[hidden]` dialogs are ignored. |
| Tap targets | 375×812 touch | Every visible link, button, field and radio/checkbox label is scrolled into view, then a 7×7 grid of points inside a 44×44 box around its centre is hit-tested with `elementFromPoint`; all 49 must land on the control (this credits stretched `::after` hit areas and negative-margin padding). Inline links inside running prose are exempt, as WCAG 2.5.8 allows. The sticky add-to-cart bar and the mobile filter bar are hit-tested again while visible, at four scroll depths. |
| Text contrast | 1280×800, reduced motion | Every element with its own text node that is visible, not sr-only and not `aria-hidden` is checked. The background is found by walking up to the first opaque `background-color`, compositing translucent layers and ancestor `opacity` on the way; text over a `background-image` is listed as unverifiable, not passed. Threshold 4.5:1, or 3:1 for text ≥24px (or ≥18.66px bold). Gradient-clipped text must be ≥28px (04a §2.5). Disabled controls are exempt. |
| Reduced motion | 1280×800, `prefers-reduced-motion: reduce` | All `.sf-reveal` nodes must compute `opacity: 1` and no transform before any scroll; product/collection/gallery images, drawers, hero and buttons must have no transition or animation longer than 1ms; hovering a card image must not change its scale; the cart drawer must not be mid-slide 80ms after opening; the intro classes must not be set. |

## Findings and fixes

First run: 132 failures (74 tap targets, 55 contrast, 3 reduced-motion). Of those, the contrast
and reduced-motion entries were harness mistakes (invisible hover-only labels at `opacity: 0`,
labels of *disabled* filter checkboxes, and the `scale(1.001)` GPU hint on card images read as
a zoom); the harness was corrected so those states are exempt for the right reason, and the
real failures below were fixed in the files this task owns.

### Fixed

| # | Page(s) | Failure | Fix (file) |
|---|---|---|---|
| 1 | all | Header logo link is 36×55 at 375 — narrower than the 44px tap minimum. | `.site-header__logo { min-width: var(--tap) }`; it is grid-centred so nothing moves (`site.css`). |
| 2 | `/shop`, `/collections`, `/for-her`, `/product/*`, `/scent-finder`, `/faq`, `/about` | Breadcrumb links are 34–37px wide (44 tall). | `.breadcrumb__link { padding-inline: .375rem; margin-inline: -.375rem }` — the hit area grows 6px each side, the text does not move (`site.css`). |
| 3 | `/product/*`, cart drawer | Quantity stepper buttons and input are 42px tall: the `.qty` box is 44px *including* its 1px borders, so the controls inside are 42. | `.qty`, `.qty--sm { height: calc(var(--tap) + 2 * var(--border-hair)) }` — the box is 46px outside, the controls 44 (`site.css`). |
| 4 | `/product/*` | The collection eyebrow link (16px tall), the scent-family link (20px) and the rating link to reviews (24px) are inline text targets under 44px. | Inline links get `padding-block` only (it does not change line layout): `.split__aside .u-track > a, .split__aside .text-small > a { padding-block: .875rem }`; the inline-flex rating link gets `.stars--link { padding-block: .625rem; margin-block: -.625rem }` (`site.css`). |
| 5 | `/contact` | `mailto:` and `tel:` links in the contact details list are 20px tall. | `.data-list__val > a { padding-block: .75rem }` (`site.css`). |
| 6 | all | The header search input has no focus ring: `outline: none` with only a `border-bottom-color` change (spec 04b §4 requires border + halo). | `.site-header__search-input:focus { box-shadow: 0 1px 0 0 var(--accent) }` — a 2px gold underline on focus, in the input's underline vocabulary (`site.css`). |
| 7 | all (mobile) | The burger is `<a role="button">`; Space did nothing (Enter worked). WCAG 2.1.1 — a `role=button` must activate on Space. | `ui.js`: a keydown delegate on `a[role="button"], a.js-cart-open` that prevents the page scroll and clicks the trigger. |
| 8 | header + mobile search | Suggestion options (`role="option"`) never carried `aria-selected`, so `aria-activedescendant` pointed at an option with no selected state. | `forms.js`: every rendered option starts `aria-selected="false"`, and arrow-key movement flips it with the `is-active` class. |

Items 1–5 change hit areas only; no visible value (colour, size, spacing) was altered except the
2px taller quantity box in item 3, which the spec's 44px control height requires.

### Verified passing (no change needed)

- **Focus rings**: 104 Tab stops on `/`, 98 on `/shop`, 72 on `/collections`, 71 on `/for-her`,
  75 on `/product/azure-oud`, 50 on `/scent-finder`, 49 on `/cart`, 49 on `/checkout`, 51 on
  `/track`, 56 on `/contact`, 58 on `/faq`, 49 on `/about` — every stop is visible, matches
  `:focus-visible`, and changes `outline` or `box-shadow` against its rest state (the generic
  1px gold outline with 3px offset from 04a §2.7; fields use the gold border + halo; hidden
  radios move the ring to their sibling card; product cards to the stretched `::after`).
- **Dialogs**: cart drawer (all 12 pages, desktop), mobile navigation (all 12, 375 touch),
  filters drawer (`/shop`, `/for-her`), lightbox (`/product/azure-oud`): keyboard open, focus moved
  inside, 24 Tab/Shift+Tab presses stay inside, ring on the focused control, Escape closes,
  `body.is-locked` released, focus returned to the trigger, `aria-expanded` reset. The mega panel
  opens on Enter/ArrowDown to its first link and Escape returns focus to the trigger; tabbing
  past the trigger without opening still reveals the panel via `:focus-within`, so focus never
  lands on an invisible link.
- **Names**: no control without an accessible name on any page; no `<img>` without `alt`; no
  exposed SVG without a name (all icon SVGs are `aria-hidden` via `partials/icon.php`).
- **Headings**: one `h1` per page, first in order, no skipped level (the FAQ uses `h2` per
  question, the product page `h2` sections with `h3` sub-blocks).
- **Contrast**: 151 / 128 / 69 / 70 / 115 / 23 / 30 / 31 / 32 / 46 / 42 / 35 text nodes checked
  per page in the order above; none under 4.5:1 (3:1 for large) on a solid background. Text
  over imagery (hero copy, collection tiles, Instagram tiles, gallery captions) is reported as
  unverifiable by computation; the motion review sampled those pixels at ≥7.7:1 for the hero
  eyebrow and ≥5.4:1 for the trust line.
- **Reduced motion** (04b §21): with the OS setting on, every `.sf-reveal` node is fully visible
  before any scroll, card/gallery images do not zoom on hover, the drawer appears without a
  slide, the sticky bar has no slide-up, and `html` never receives `sf-intro-full`/`sf-intro-calm`
  (the motion gate sets `sf-reduce` instead). All transitions on the sampled selectors compute to
  0.01ms. `reveal.js` and `SF.reducedMotion()` read the media query live and `reveal.js`
  re-checks on `change`.

## Notes for the other owners

- **CSS split.** While this audit ran, the performance owner split `site.css` into an inlined
  `critical.css` plus the remaining `site.css`. Fixes 1–5 above were made before the split and
  travelled into `critical.css` with the rules they belong to (`.site-header__logo`,
  `.breadcrumb__link`, `.qty`, `.stars--link`, `.split__aside … > a`); fix 5 stayed in
  `site.css`. Fix 6 was made after the split, so it lives as a small `@layer components` block at
  the end of `site.css` rather than beside `.site-header__search-input:focus` in `critical.css`.
  Whoever next regenerates `critical.css` should fold that one declaration into the
  `.site-header__search-input:focus` rule and drop the trailing block. The harness will catch it if
  the rule goes missing (`FAIL [focus-ring] … header search input`).
- **Inline prose links** (`/about`, FAQ answers, policy pages) are below 44px tall and are
  exempted by the harness because they sit in running text (WCAG 2.5.8 exception). The two
  links in the About prose (`Scent Finder`, `message us on WhatsApp`) each have a full-width
  duplicate elsewhere on the page (primary nav, WhatsApp button).
- **Text over imagery** cannot be computed from `getComputedStyle`; the harness lists those
  nodes as unverifiable rather than passing them. Hero, collection-tile and gallery copy were
  pixel-sampled in the motion review (`docs/motion/review-a11y.md`, "What holds").
- **Admin** was excluded on purpose (its own phone-UX critic covers it). `admin.css` shares the
  `:focus-visible` treatment and the `.field` rules, so fixes 3 and 6 do not apply there.
- **Remaining, out of this task's scope** (from `docs/motion/review-a11y.md`, still open at the
  time of writing): a "Pause animations" control for the >5s hero drift (2.2.2), the quiz
  auto-advance announcement (3.2.2), and the intro veil on reduced motion — all owned by the
  motion layer.

## Final state

`node dev-tools/a11y.mjs` against `http://127.0.0.1:8103` on 2026-09-29 (Chromium 1208, twelve
pages, desktop 1280×800, mobile 375×812 touch, and a `prefers-reduced-motion: reduce` context):
**0 failures**. The JSON report of that run is in the session scratchpad (`a11y-run-final.json`);
re-run the command above to regenerate it.
