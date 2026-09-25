# Reference: tarot.meetyourpsychic.com — layered mystical hero

Studied 2026-09-26 (WebFetch + live browser inspection at 742px and 800px viewports).
Angle: layered parallax hero, dark mystical palette, stacked/fanned cards, mouse/scroll response.
Purpose: inspiration only. Nothing here is to be copied.

## What it does (as seen)

- Full-bleed hero on a deep indigo ground (`#1E154D` -> `#251d57` radial glow behind the
  headline). Warm gold is the only accent: gold foil on the card backs, candle flames,
  the logo mark. Headline is white serif, two lines, centred.
- Composition is a staged tableau: headline + six round icon "portals" + two pill CTAs
  sit in the top half; the bottom half is a "table" scene — an engraved astrological
  disc (plate), candles left and right at three depths, and four fanned tarot cards
  along the bottom edge that bleed off-screen. The tableau is drawn from ~7 cut-out
  PNG/WebP layers positioned with percentage `top/left/width` per breakpoint.
- The candle flames are live: 22 small `<canvas>` elements driven by Rive (found
  `@rive` in the chunks), each a looping flame or icon animation. This is the only
  thing that moves once the page has settled.
- Entrance: headline lines, icon list items and the card plate each start at
  `opacity:0; translateY(20-40px)` and settle to `transform:none` with a staggered
  ease — the rendered DOM shows `style="opacity:1; transform:none"` on every animated
  node, which is Framer Motion's signature. `whileInView` / `whileHover` are present
  in the bundle, so lower sections fade in as they enter the viewport.
- Mouse: **no parallax**. Moving the pointer across the hero changed no computed
  transform on any layer. Hover states are Tailwind transitions (`transition-all`,
  scale/opacity on the icon circles and cards).
- Scroll: **no scroll-linked parallax either**. After scrolling 800px every hero
  layer kept its original matrix. The hero simply scrolls away under a fixed top
  gradient (`fixed inset-x-0 top-0 h-24 bg-linear-to-b`) that fades content into the
  nav. Smooth scrolling is Lenis (`<html class="lenis">`).
- Card "stack" below the hero: six game cards laid out with a fixed
  `rotateX(65deg)` (`matrix3d(... 0.4226, 0.9063 ...)`) so they read as lying on a
  table, each with an icon badge and a `will-change: filter` blur placeholder SVG.
  The three-card fan uses fixed `rotate(±16deg) translateY(24px)` — a static fan,
  not scroll-driven.

## How it appears to be built

- Next.js (Turbopack chunks, `/_next/image` optimisation, RSC payload), Tailwind v4
  (`bg-radial-[...]`, `size-17`, container queries), Framer Motion for entrance and
  in-view reveals, Lenis for smooth scroll, Rive for flame/icon canvases.
- No Three.js, no GSAP, no ScrollTrigger, no WebGL, no shaders. `IntersectionObserver`
  and `requestAnimationFrame` appear via Framer Motion and Rive, not custom code.
- Asset strategy: every foreground object is its own transparent WebP/PNG
  (`hero-fg.webp` plate at up to 1920w q=100, `hero-fg-candle-l-2.png` 127x385,
  `hero-fg-candle-r-3.png` 259x290, card backs 371x660 WebP q=75). The layered look
  comes from **art direction and z-order**, not from motion.
- Depth cue is painted in: perspective on the disc, soft drop shadows under candles,
  darker vignette at the edges, cards cropped by the viewport bottom. Six large
  `will-change` hints, 0 CSS keyframe animations.
- Fonts: DM Sans (UI) + two display serifs (ginger, serlio) self-hosted with
  fallback metrics.

## Principles to take for Sky Fragrances (dark + gold)

1. **The tableau is the hero, motion is seasoning.** This page feels rich with zero
   parallax because the composition already has foreground / mid / background layers,
   painted shadows and a cropped object breaking the bottom edge. Build the SF hero
   the same way first — bottle centre, ingredients as cut-outs at three depths, mist
   as a soft top gradient — and it will look finished even where Three.js never loads.
2. **One live element carries "alive".** Their flames do what our gold ribbons should:
   a single, small, looping light source is enough to make a still scene breathe.
   For the mobile fallback, one looping SVG/CSS glow behind the bottle beats five
   ribbons at 20fps.
3. **Gold on near-black works when gold is scarce.** Gold is reserved for the object
   edges, the flames, the CTA outline. Everything else is a single dark hue with a
   slightly lighter radial behind the headline. Keep SF's palette to black/charcoal
   plus one warm gold and one glow tint; no second accent.
4. **Entrance by translateY + opacity, staggered, then settle to `transform:none`.**
   Exactly what GSAP `from({y:24, autoAlpha:0}, stagger)` gives us. Headline lines
   as separate nodes with a mask = our line-by-line reveal.
5. **Cards read as physical because of a single fixed 3D pose.** `rotateX(65deg)`
   for "lying on the table", `rotate(±16deg)` for the fan. Do the fan with static
   transforms and let ScrollTrigger only *spread* them (translateX/rotate on scroll);
   the resting pose already sells it.
6. **Cropping by the viewport edge adds depth for free.** Cards half off-screen at the
   bottom, candles cut by the sides — no transforms, no cost. Use for ingredient
   cut-outs at 360px.
7. **Percent-positioned layers per breakpoint, not absolute px.** Their layers use
   `top-[28%] left-[13.2%] w-[21%]` with md/lg overrides; the scene re-composes at
   every width without JS.

## What to avoid on mid-range Android / 4G

- **22 Rive canvases.** Each is a WebGL/Canvas2D context with its own rAF loop; that
  is a battery and jank budget we cannot spend. SF: at most one canvas (hero Three.js,
  lazy, desktop only) and CSS/SVG for everything else.
- **q=100, 1920w foreground plates.** A 1920px q=100 WebP for a decorative plate is
  several hundred KB on 4G. Ship ingredient cut-outs at 2 sizes max (≤720w mobile,
  ≤1440w desktop), q≈75, AVIF with WebP fallback, `loading="lazy"` below the fold.
- **Framer Motion + React runtime for entrance fades.** Not an option for us (no
  Node); GSAP core + ScrollTrigger vendored does the same in ~70KB gzipped total.
- **Blur placeholders via `will-change: filter`.** Filter animations are not
  compositor-only; stick to transform/opacity per the brief.
- **Hero content that is invisible until JS runs.** Their headline and CTAs are
  `opacity:0` until hydration. Ours must render visible by default and only be
  hidden by a `.js-ready` class set synchronously in `<head>`, so price and Add to
  Cart are usable before any animation script arrives.
- **`transition-all`.** Cheap to write, expensive on paint; transition named
  properties only.
- **No `prefers-reduced-motion` handling was observed** (flames keep looping). SF must
  gate Rive-style loops and ribbons behind the media query and fall back to a static
  glow.

## Verdict

Strong art direction, weak motion. The lesson is that the "layered parallax" feel
comes almost entirely from the painted composition and z-ordering; the actual
mouse/scroll parallax in our brief is an upgrade on top of that base, and the base
must stand on its own at 360px on 4G.
