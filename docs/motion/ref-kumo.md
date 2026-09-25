# Reference: Kumo — Interactive Matcha Tea Landing Page (Dribbble #26971799)

Source: https://dribbble.com/shots/26971799-Kumo-Interactive-Matcha-Tea-Landing-Page-UI-UX
Designer: Kris Anfalova. Studied 2026-09-26 for inspiration only — never to copy.
Angle: product-as-hero storytelling, big confident type, scroll-driven reveals, section rhythm.

## What the shot actually is

- A 9-second looping MP4 (1800x1350) with a PNG poster. There is no live site, no source,
  no third-party script to inspect. Everything below about "how it is built" is inferred
  from the frames, not read from code.
- Single hero scene, one viewport, no scrolling shown. Olive-green/matcha palette, the
  cup sits on a mound of matcha powder against a dark green gradient wall.

## Frame-by-frame (what it does)

1. 0–1.5s: only the product. A layered matcha cup, centred, lit from above, on a powder
   mound. No UI, no type. Pure product portrait.
2. ~1.5–4s: the interface arrives around the product. Wordmark top-left, a pill-shaped
   flavour switcher top-centre (4 small icons, active one dark), a settings pill top-right.
   Four rounded white cards fan out to either side of the cup — two per side, the outer
   ones lower and further away, each holding one floating ingredient (strawberry, blueberry,
   cranberry, blackberry, mint leaf). A small label under the cup names the variant
   ("Original"), with a tiny cart/handle chip below it.
3. ~4–8s: flavour change. The cup's contents cross-fade to the strawberry variant (red
   layers inside the same glass), the label becomes "Strawberry", and the ingredient cards
   swap their contents. Cards drift slightly during the swap; the product never moves.
4. Throughout: one large serif headline pinned near the bottom, "Choose you matcha tea",
   cream on green, sized about a fifth of the viewport height. It is the only big type.

## How it appears to be built (best guess)

- Motion-graphics render (After Effects / Figma-to-video), not DOM. Layers: background
  gradient, powder mound photo, cup photo with masked interior for the variant swap,
  five ingredient PNG cutouts with soft drop shadows, UI chrome on top.
- Choreography: product first, UI second, headline last, then a state change. Stagger of
  ~100–150ms between cards; eases read as expo/power3 out, nothing bouncy.
- If rebuilt on the web it would be: fixed hero, absolutely positioned cutouts moved with
  transform only, a crossfade of two product images, text swap with a short mask reveal.
  No WebGL is needed for anything shown.

## Principles to take for Sky Fragrances (black + warm gold)

1. Product enters alone. Give the bottle 1–1.5s of solitude on first paint before any
   ribbons, cards or copy appear. The loader dissolving to mist can hand straight into this.
2. Ingredients orbit the product, never the type. Our rose petals, oud chips, citrus and
   smoke wisps belong on the same z-plane logic as Kumo's cards: near ones larger and
   lower, far ones smaller and higher, and they drift on scroll/mouse at speeds that
   encode their depth. The bottle stays still; everything else moves around it.
3. One headline, one weight, one size. Kumo commits to a single serif line at the bottom.
   Do the same: one line-masked serif headline in gold, revealed line-by-line, rather than
   several competing sizes. Confidence comes from restraint.
4. State change is the interaction. Their flavour switch is the whole idea. Our version is
   the pinned fragrance story (Top -> Heart -> Base): background hue shifts per note, the
   ingredient cutouts around the bottle swap, the caption under the bottle changes. Same
   structure, one product, three states.
5. Chrome stays small and pill-shaped. Nav, switcher and cart icon are tiny, low-contrast
   pills that never fight the product. Keep the cart icon in this chrome so the fly-to-cart
   burst has a fixed, always-visible target.
6. Section rhythm for the rest of the page: hero (product alone -> ornaments -> headline),
   story (pinned product, states), collection (cards fanning from the product, the same
   fan geometry as Kumo's ingredient cards), quiz, footer. Every section repeats
   "product centre, supporting elements orbit" so the page feels like one scene.
7. A label under the product doing double duty as name and price. Kumo's "Original" chip is
   where our name + PKR price + "Cash on Delivery" line should live: visible from the first
   frame, no animation gate.

## What to avoid on mid-range Android / 4G

- Do not ship the hero as a looping 1800px MP4 like the shot itself. Even at 720p a
  9-second loop is 2–4 MB and autoplay on 4G will stall LCP. If a video fallback is used
  for low-power devices, keep it under 600 KB, 3–4 seconds, poster-first, muted, and lazy.
- Five simultaneous floating PNG cutouts with soft shadows are five compositing layers.
  On a 360px screen show at most two ornaments, pre-baked shadows in the image, no
  CSS filter: drop-shadow or blur on them (blur repaints every frame on Adreno 6xx GPUs).
- The variant cross-fade needs both product images decoded before the swap; preload the
  second state or the fade will flash blank. Use one sprite or two same-sized WebPs.
- Kumo's cards fan outward horizontally. At 360px there is no horizontal room: stack
  ornaments above and below the bottle, or drop them to opacity-only fades.
- Parallax driven by mouse has no mobile equivalent; gyroscope is unreliable and needs a
  permission prompt on iOS. Use scroll progress only on touch.
- Nothing in Kumo's hero is clickable in the first 1.5s. That is fine for a mockup but not
  for us: Add to Cart, price and COD info must be in the DOM and enabled at first paint,
  with only the ornaments and headline delayed.
- The headline is huge cream serif on mid-green; ours is gold on black. Check contrast on
  the gold (#C9A227-style gold on #0B0B0B passes AA for large text, pale gold on charcoal
  may not) and never place it over the brightest part of the glow.
- prefers-reduced-motion: collapse the whole choreography into a single 400ms opacity fade
  of the finished hero; skip the state-change cross-fade and switch instantly.

## One-line verdict

Take the choreography (product first, ornaments orbit, one headline, state change as the
interaction) and the fan geometry for the collection; ignore the delivery format (video),
the five-layer ornament count on mobile, and the delayed-CTA opening.
