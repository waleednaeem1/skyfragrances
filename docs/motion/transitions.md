# Motion — page transitions + scroll polish

Owner files (paths relative to `site/`): `assets/js/motion/transitions.js`,
`assets/css/motion/60-transitions.css`, `app/partials/transition-overlay.php` (one include in
`app/views/layout.php`, after the toast region). Numbers live in `assets/js/motion/config.js` →
`transitions` and the `css` tokens `veil`, `veil-failsafe`, `veil-out`, `veil-in`. Implements
PLAN.md §2.7 as changed by §8 #11 (desktop only, nothing on touch) with §11's critique applied.

## Hooks

| Hook | Where | Meaning |
|---|---|---|
| `data-motion="transitions"` on `.sf-veil` | `transition-overlay.php`, printed only on `home`, `listing`, `collections`, `product`, `quiz` bodies | root the core loader hands to `transitions.js` (flag `transitions: 'desktop'`); never printed on cart, checkout, track, confirmation. |
| `html.sf-transitions-native` / `html.sf-transitions-veil` | set by `transitions.js` | which path is live; QA hook only, no CSS keys on it. |
| `SF.motion` event `leave` `{href, veil}` | emitted by the veil path before `location.href` | lets the hero dispose its GL early (PLAN §3 lifecycle). The native path has no pre-swap moment other than `pageswap`/`pagehide`, which the hero already listens to. |

## Desktop (`html.motion--desktop`)

1. Native path (Chromium 126+, `CSSViewTransitionRule` and `pageswap` present): zero JS in the hot
   path. `60-transitions.css` opts in with `@view-transition { navigation: auto }` inside
   `@media (min-width: 1024px) and (hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)`;
   `::view-transition-old(root)` fades to the mist tint in `--sf-veil-out` (160 ms, `scale 1.012`),
   `::view-transition-new(root)` clears in `--sf-veil-in` (200 ms); both run concurrently. Both
   documents must opt in, so a hop to `/cart`, `/checkout`, `/track`, `/order/*` (no `motion.css`)
   never transitions. `transitions.js` additionally listens to `navigation.navigate` and skips the
   transition on `pageswap` for reloads, downloads, POST form submissions, cross-origin
   destinations and `skipPaths`. Back/forward (`traverse`) transitions natively.
2. Veil path (every other desktop browser): a plain left click (no modifier, button 0, not
   `defaultPrevented`) on a same-origin `http(s)` `<a href>` that is not in `config.transitions.skip`
   (`target`, `download`, `#`, `mailto:`, `tel:`, `.js-cart-open`, `[data-dialog-open]`,
   `[aria-controls]`, `.drawer a`, `.sticky-bar a`, cart/checkout/track/admin/api, wa.me, Instagram)
   and not on a `skipPaths` path, and not a same-page hash link, adds `.is-active` to `.sf-veil`
   (opacity 0→1 in `--sf-veil` 200 ms, `z-index 590`: above toasts, below the intro) and sets
   `location.href` after `veil.ms`. Forms, buttons, POST and 303s are never touched.
3. Stuck-veil guards: JS resets after `veil.failsafeMs` (900 ms) if the page has not unloaded
   (slow server) and the CSS `sf-veil-failsafe` keyframe hides it at the same delay with JS dead;
   `pagehide`, `pageshow` (bfcache, `persisted` → `lenis.resize()`) and `popstate` all reset it
   instantly (`.is-restored` disables the transition for that frame).
4. Anchor scroll: same-page hash links (hero `.js-hero-cue`, `#reviews`, `#sizes`) are intercepted
   in the **capture** phase on `document` unless they match `anchors.skip` (`.skip-link`,
   `[role="button"]`, `[aria-controls]`, `[data-dialog-open]`, `.js-cart-open`, `form a` — the quiz
   owns its Back/Next); the handled click is `preventDefault`ed and its propagation stopped, because
   core creates Lenis with `anchors: true` and Lenis's own `<html>` click handler would otherwise
   run a second, competing scroll (it also lands 5 px short). With Lenis on →
   `lenis.scrollTo(<number>, …)` — a **numeric** target, since Lenis 1.3.26 applies `offset` twice
   for element targets (element + `-96` landed at 737 instead of 833); otherwise a GSAP progress tween
   (`anchors.ms` 900, `anchors.ease` power3.out) writes `window.scrollTo(…, 'instant')` every
   frame and re-reads the target's position each frame, so lazy images loading below the fold
   (which make core call `ScrollTrigger.refresh()`) cannot cancel or misplace it — native smooth
   scroll did both. `html{scroll-behavior}` is forced to `auto` only while the tween runs. On
   completion (GSAP `onComplete`, Lenis `onComplete` + one rAF) a final instant settle corrects any
   remaining drift > 1 px. Wheel, touch or a key stops the tween. The hash is `pushState`d and the
   target receives focus (`tabindex="-1"` added and removed on blur, `preventScroll`).
5. Scroll restoration: on a load with a hash, once `load` + `restore.settleMs` has passed the target
   is re-aligned under the header instantly, and again on every core `refresh` for
   `restore.windowMs` (2.5 s), unless the user has already scrolled. History restoration itself is
   the browser's (native scroll mode).
6. Device flip → all listeners removed, tween killed, veil reset, classes removed.

## Mobile / low / reduced / no-JS

- Mobile and low: the module never loads (flag `'desktop'`), `.sf-veil` stays `display: none`, and
  the `@view-transition` media query is false on both documents, so taps navigate at once (PLAN §2.7:
  a held snapshot over a 4G round-trip reads as a dead tap).
- Reduced: no `motion--*` class, no module, veil `display: none`, media query false.
- No JS: no `html.motion--desktop`, veil `display: none`, every link and form native.

## Verified 2026-09-28 (Playwright, Chromium 1208, `php -S 127.0.0.1:8091`, 32/32 checks)

1440×900: native mode detected, `@view-transition` present, home → shop → product → cart → checkout
(→ cart redirect) → back ×2 → back/forward with the veil idle at every arrival, add-to-cart and
checkout forms unchanged (method/action/hidden inputs), `/cart` has no veil, no `motion.css`, no
sections; hero cue and `/#collections` land 96 px under the header with hash and focus. Veil mode
(`CSSViewTransitionRule` deleted): veil shows and `leave` fires before navigation, modifier click
never veils, hash link never veils, a navigation held for 2.6 s shows the veil at the click and the
failsafe clears it at 901 ms (MutationObserver log carried over in `sessionStorage`), back/forward
never leave a veil, cart icon opens the drawer with no veil. Pixel 5, reduced motion and JS-off: no module, veil `display: none`. Lenis on via
`localStorage.sfMotionFlags`: cue and realign land through Lenis. Zero console, page or network
errors from these files.
