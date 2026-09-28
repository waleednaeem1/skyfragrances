# Motion — micro section (magnetic, cursor, underline, cart burst + flight, count tick)

Owner files (paths relative to `site/`): `assets/js/motion/micro.js`, `assets/css/motion/40-micro.css`,
the `sf:cart-added` dispatch in `assets/js/cart.js`. Root: `<body data-motion="micro">` in
`app/views/layout.php` (flag `micro`). Numbers live in `assets/js/motion/config.js` → `micro` and the
`css` tokens `underline`, `tick`, `burst`, `flight`, `cursor-*`. Implements PLAN.md §2.5 with §8 #5, #9,
#12, #15 and #17 applied. Views are enhanced by selector; no view was edited for this section.

## Hooks

| Hook | Where | Meaning |
|---|---|---|
| `sf:cart-added` | `document`, dispatched by `cart.js add()` right before `openDrawer()` | `detail = {button, form, thumb, target, count, source}`; `thumb` is the product `<img>` nearest the form (card image or gallery main), `target` the drawer's `.js-cart-count`. Nothing is awaited: the drawer opens in the same tick, `sf:dialog-open` fires synchronously inside it, then `.is-loading` is cleared. Forms with `data-cart-reload` are ignored by the module. |
| `.sf-burst > i.sf-spray` | appended to `body` at the button's centre | 14 spans (desktop, Mobile-high), 8 (mobile), 0 (`low`), removed after `burst.ms`. |
| `.sf-fly` | appended to `body`, desktop only (`flags.cartFly`) | 28 px clone of `thumb` flown to the drawer count's final position (the drawer's in-flight `translate` is subtracted), then `SF.bump(count, 'is-bumped')`. Without a flight the bump runs after `duration.base`. |
| `.sf-tick` / `.sf-tick__real` / `.sf-tick__num` | inside `.collection-card__count`, `.stars__count`, `[data-tick]`, `[data-countup]` | real text stays in `.sf-tick__real` (visually hidden); the `aria-hidden` twin counts 0→N in `tick.ms` with `tabular-nums` and `min-width: Nch`; the node is restored to plain text at the end. Skipped inside `tick.never` (`.price__now`, `[data-price-now]`, `[data-sticky-price]`, `.quiz-match` — owned by `quiz.js` — and any `[role=status]`/`[aria-live]`, which is why the listing "N fragrances" `.toolbar__count` does not tick). Waits for the host `.sf-reveal` to be `.is-visible`. |
| `.sf-cursor > i.sf-cursor__ring`, `html.sf-cursor-on` | desktop only, created once GSAP is present | 22 px gold ring, `pointer-events: none`, `quickTo` follow; `.is-grown` over `cursor.targets`, `.is-native` (hidden) over `cursor.nativeInside`, `.is-hidden` between `sf:dialog-open` and the last `sf:dialog-close`. `cursor: none` only on `.product-card`, `.collection-card`, `.gallery__open`. |
| magnetic | `.btn--ghost, .btn--text, .section-header__link` outside `magnetic.never` | `quickTo` x/y ≤ `maxPx` inside a `radius`× ellipse, released to 0; skipped while `.is-loading` or a dialog is open; rects re-measured on scroll/resize/`refresh`. `.btn--primary`, `.js-add-to-cart`, sticky bar, drawer, modal, cart/checkout links never move. |

## Device classes

| Class | Behaviour |
|---|---|
| desktop | all of the above; cursor + magnetism only after `ensureGsap()` resolves and only on pages that link `motion.css`. |
| mobile / mobile-high | burst (8 / 14) + drawer count bump, tick via IntersectionObserver + rAF; no cursor, no magnetism, no flight, no GSAP. |
| low | count bump only (`burst.low: 0`), tick still runs (text only). |
| reduced (`sf-reduce`) | module never loads; `cart.js` updates the counts as before; `40-micro.css` also hides `.sf-cursor/.sf-burst/.sf-fly` defensively. |
| commerce pages | nothing loads (core), `motion.css` is not linked. |

Underline draw: `.site-footer__link` gets the same `background-size 0→100% 1px` pattern as `.btn--text`,
`.section-header__link` and `.site-header__link`; `:focus-visible` draws it on all four; duration is
`--sf-underline`.

## Verified (Playwright, 2026-09-28, `/product/azure-oud`, `/collections`, `/`, `/cart`)

Desktop 1440×900: cursor ring exists with `pointer-events: none`, grows over `.product-card`, hides over
the card button and a form control; a ghost button moves ≤ 6 px and returns to `translate(0px, 0px)`;
Add to Cart has no inline transform; add → drawer opens, drawer and header counts 0→1, event carries
`thumb` (`IMG`) and `target`, 14-span burst and one `.sf-fly` created and removed, drawer count bumped,
button not `.is-loading`; collection counts tick on an `aria-hidden` twin and end as the original text;
no `.price__now` ever gets a twin. Pixel 5: `motion--mobile`, no cursor, no GSAP, 8-span burst, no
flight, counts update. Reduced motion: `sf-reduce`, no module, no burst/flight, counts update. JS off:
price and Add to Cart painted, counts plain text. `/cart`: no sections, no cursor, no `motion.css`.
Zero console errors, page errors or failed requests in every run.
