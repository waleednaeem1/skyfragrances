# Reference study: austensor.com/page5 — "Motion — Flowing Light"

Studied 2026-09-26 for the Sky Fragrances hero "scent trails" concept. Inspiration only; nothing here is to be copied.

## What it does

A full-viewport black WebGL scene. Five thin neon tubes (blue → purple gradient) snake horizontally
through space, glowing with bloom. A bright pulse travels along each tube; the whole bundle slowly
yaws and rocks. Drag orbits the camera, wheel zooms, phone gyroscope tilts the group, and if the
site's audio is playing, low/mid/high bands make the tubes wobble and brighten. A HUD overlay
(monospace "EXP_05 // LIGHT CONDUIT" labels) sits on top in plain DOM. It is an art demo, not a
sales page: the canvas is the product.

## How it is actually built (read from the shipped chunks)

Stack: Next.js + React Three Fiber (`@react-three/fiber`), `@react-three/drei` (OrbitControls),
`@react-three/postprocessing` (which wraps the `postprocessing` library, not three's own
UnrealBloomPass despite the marketing copy). The scene component `FlowLightScene` is `dynamic()`
imported with `ssr:false`, so it code-splits into four lazy chunks: three.js core (~800 KB raw),
postprocessing (~330 KB), R3F/drei (~190 KB), scene (~30 KB). Roughly 1.3 MB of raw JS after first
paint, all for the hero.

Geometry: five `CatmullRomCurve3` splines from five hand-placed `Vector3` control points each
(x from about -3.8 to 3.8, y/z within ±1.2). Each becomes a `TubeGeometry(curve, 96 tubular
segments, radius, 8 radial segments, closed=false)`. Radii step down 0.025 → 0.02 → 0.018 → 0.01 →
0.008, so one "lead" tube and four progressively finer companions. Each tube also has its own
speed (0.8–2.5) and wave frequency (10–16), which is what makes the bundle look alive rather than
cloned.

Material: a custom `ShaderMaterial`, `transparent`, `depthWrite:false`, `AdditiveBlending` (dark
theme) or NormalBlending (light theme). Fragment shader:
- colour = `mix(colorA, colorB, sin(uv.x * waveFreq - time*speed) * 0.5 + 0.5)` — the gradient
  scrolls along the tube's length via UV.x.
- a travelling pulse: `pulsePos = fract(flow * 0.1)`, `pulse = exp(-d*d*40)`, tinted with the
  mid-mix colour at 0.6 strength (they explicitly removed a white flash — a comment says it was
  too harsh).
- alpha = `smoothstep(0,0.15,uv.x) * smoothstep(1,0.85,uv.x)` (fade both ends) ×
  `sin(uv.y*π)` (soft edges around the tube circumference, so it reads as a light rod, not a pipe).
- output multiplied by ~1.8 so it exceeds 1.0 and trips the bloom threshold.
Vertex shader: adds `sin(pos.x*1.5 + time*2 + tubeIndex*1.5) * audioLow*0.28` to y and a cosine
to z — i.e. the wobble is only audio-driven; with no audio the tubes are rigid and only the group
rotates.

Post-processing: `EffectComposer` + `Bloom({ intensity: 1, luminanceThreshold: 0.15,
luminanceSmoothing: 0.8, mipmapBlur: true })`. Mipmap blur is the cheaper, softer bloom in the
`postprocessing` lib. There is no "selective" bloom in code — the low threshold on a black
background does the selecting.

Pointer coupling: none of it is custom. `OrbitControls` with `enableDamping`, `dampingFactor
0.08`, zoom clamped 2–12, pan off. Scroll = camera dolly, drag = orbit. Group animation in
`useFrame`: `rotation.y += dt*(0.12 + 0.15*audioLow)`, `rotation.x = 0.15 + 0.08*sin(t*0.0006)`,
`rotation.z = 0.06*cos(t*0.0004)`. Audio bands are lerped (0.2–0.25) so nothing snaps. Gyroscope
adds ±0.12 rad on phones after a first touch grants permission.

Renderer: `antialias:true`, `alpha:false`, `powerPreference:'high-performance'`, camera fov 55 at
z 5.5, `dpr` capped at [1,2] on desktop and forced to [1,1] on mobile (their own `useIsMobile`).
There is a DOM `WebGLFallback` component rendered when WebGL is unavailable. No reduced-motion
handling was found in the scene chunk.

## Cost

Five tubes × 96 × 8 segments ≈ 4k triangles — trivially cheap geometry. The cost is entirely
(a) the JS payload (~1.3 MB raw, maybe 350–400 KB gzipped) and (b) the full-screen bloom: a
render-to-HDR-target plus 5–6 downsample/upsample mip passes every frame, at device resolution.
On a mid-range Android that is the frame-time killer, and the `antialias:true` on a
post-processed scene is wasted (MSAA is lost once you render to a target). Additive blending of
transparent tubes is fine; the overdraw is small.

## Principles to take for Sky Fragrances (dark + gold)

1. Few, thin, unequal ribbons. One lead trail and 2–3 finer ones at different speeds/frequencies
   reads as organic. Equal tubes read as cables. Use radius, speed and wave frequency as the
   three per-ribbon knobs, and keep them in the single motion config file.
2. Glow from the shader, not only from bloom. The `sin(uv.y*π)` edge falloff plus end fades makes
   each ribbon look like light before any post pass. For us: ramp from warm gold (#d4af37-ish) to
   amber, add a slow travelling brighter pulse in the same hue family — never white. This
   is exactly the "scent trail" feel and it costs nothing.
3. Additive blending on black is the whole trick. Where ribbons cross they get brighter, which is
   what makes them feel luminous. Our hero background must actually be black/charcoal for this to
   work; a photo background kills it.
4. Damped, slow coupling. Everything on that page is lerped (0.08–0.25). Our mouse/scroll
   influence should be a target vector eased with expo/power3 out, moving the group a few
   degrees, not the camera through the scene. No OrbitControls; the user should never be able to
   "lose" the bottle.
5. Lazy and gated. They dynamic-import the scene after hydration and drop dpr to 1 on mobile.
   We do the same and go further: only load Three after first paint, only on desktop/pointer:fine
   with enough deviceMemory, and only when the hero is in view.
6. Keep all copy, price and CTA in DOM above the canvas. They already do this (HUD is HTML,
   `pointer-events:none` except for buttons). Nothing sellable lives inside the canvas.

## What to avoid on mid-range Android / 4G

- The postprocessing bloom pass. Full-screen mip-chain bloom at 360×800 on a Snapdragon 6xx is
  where 60fps dies. Fake it: render the tubes twice (a wider, dimmer additive copy behind the
  sharp one) or bake a soft radial gradient sprite along the curve. If bloom is wanted on desktop
  only, keep it behind the same capability gate as Three itself.
- ~1.3 MB of raw JS for a hero. We vendor `three.min` core only (no drei, no R3F, no
  postprocessing); target ≤ 200 KB gzipped for the entire hero bundle, loaded after LCP.
- `antialias:true` with a composer — pure waste. Without post, at dpr 1 on mobile, AA is fine.
- OrbitControls / drag-to-orbit and wheel-to-zoom. Hijacking scroll on a shop hero is a
  conversion killer and fights Lenis. Scroll should only nudge the ribbons.
- Gyroscope coupling and the touchstart permission prompt. Interrupts the first tap on a
  product page.
- Audio-driven motion. Autoplay audio is blocked, so without it their tubes are static; ours must
  animate from `uTime` alone.
- No reduced-motion path. Ours needs `prefers-reduced-motion` → static SVG/CSS glow gradient,
  no canvas at all.
- Mobile fallback: do not render Three on phones at all. A 3–4 s looping H.264/WebM of the
  desktop scene (≤ 400 KB, poster JPEG) or an SVG path with an animated stroke-dashoffset and a
  CSS `filter: blur()` glow layer gives the same read at zero GPU risk.
