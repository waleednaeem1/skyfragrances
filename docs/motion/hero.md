# Motion — hero section (scent ribbons, plates, headline reveal)

Owner files (paths relative to `site/`): `app/views/home.php` (`section.hero` markup),
`assets/js/motion/hero.js`, `assets/js/motion/ribbons-gl.js`, `assets/css/motion/10-hero.css`,
`assets/img/motion/ribbons-{a,b}-{540,960}.webp`. Numbers live in `assets/js/motion/config.js` →
`hero` (scene, gates, plates) and the `css` tokens `hero-clip`, `hero-sweep`, `hero-sweep-delay`,
`hero-actions`, `hero-delay-*`, `plate-drift`, `plate-fade`, `plate-cycles`, `hero-gl-fade`.
Implements PLAN.md §2.1 and §3 with §8 #1 (in-house WebGL2 module, no Three.js) and §8 #13
(one clip wipe of the whole h1, static on mobile).

## Markup (`home.php`)

| Element | Meaning |
|---|---|
| `section.hero[data-motion="hero"]` | root the core loader hands to `hero.js` after `load` + idle (flag `hero: true`, every class except reduced). |
| `.hero__object[data-motion-slot="object"]` | the bottle slot. Today it holds the real brand monogram (`setting('hero_object')` overrides the image, no code change); the ribbons are framed around it (`config.hero.frame`). |
| `h1.hero__title.text-gold-grad` | one unsplit text node, never `opacity: 0`, never transformed; desktop gets the clip wipe + gold sweep, mobile is static from first paint. |
| `.hero__actions`, `.hero__trust` | outside `.hero__enter`; painted at first paint and only settle `opacity .6 → 1` in 400 ms. Eyebrow and subheading keep the `.hero__enter` stagger (≤ 300 ms mobile, ≤ 600 ms desktop). |
| `.hero__glow[data-plate-a/-a-desktop/-b/-b-desktop]` | zero-size anchor centred on the object. Its `::before` is the static glow (a CSS radial gradient, 0 bytes). `hero.js` appends the plates and the WebGL canvas inside it. |

The hero CTA, the COD trust line, the scroll cue and the nav are hit-testable from the first
frame on every class; nothing motion-owned has `pointer-events` (canvas, plates, glow).

## Desktop (`html.motion--desktop`)

1. First paint: glow gradient, h1 clip wipe `inset(0 0 100% 0) → inset(0)` over `--sf-hero-clip`
   (1100 ms, starts at first paint, CSS only), gold sweep after `--sf-hero-sweep-delay` (300 ms).
   Under `sf-intro-full` the sweep is paused and resumes when the veil is gone (PLAN §4: sweep at
   dissolve); the clip completes beneath the opaque veil. Eyebrow/sub/actions/trust are paused the
   same way, so the "product alone" beat runs after the veil.
2. `hero.js` (after `load` + idle, GSAP already resolved by core but unused here) gates the scene:
   `config.hero.webgl === true`, `SF.motion.gpu !== 'weak'`, not `slow`, and the admin hero photo
   (if any) darker than `groundMaxOpacity` (0.6). It then `import()`s `ribbons-gl.js` (4.3 KB gz)
   and starts rendering only when the hero is ≥ 50 % in view and the tab is visible.
3. `ribbons-gl.js`: WebGL2, 4 Catmull-Rom tubes (72 × 6 segments, ≈ 6.9k triangles with halos),
   additive blending, gold → amber shader with a travelling pulse, vertex breathe; faux bloom = a
   halo copy per tube (`halo.scale`/`halo.alpha`) + one radial sprite; `dpr ≤ 1.5`; canvas sized
   to the hero, framed to the object slot via `setFrame()`. Pointer: `rotY = mx·0.05`,
   `rotX = 0.12 + my·0.03`, lerp 0.06. Scroll: hero progress `p` → `rotX += p·0.15`,
   `posY −= p·0.6`, `uFade = 1 − p`.
4. Lifecycle: IntersectionObserver pauses below 5 % visible, `visibilitychange` pauses, mean frame
   time over 120 frames > 20 ms twice (`config.device.frameKill`) → dispose + plates,
   `webglcontextlost` → dispose + plates, `pagehide` → dispose, `device` change → dispose.
   Fallback state and reason are readable at `SF.motion.hero` (`state`, `reason`, `frame`,
   `triangles`, `plates`) and on the `hero` event.
5. Fallback on desktop = the two 960 w plates (a drifts on `transform`, b cross-fades on opacity),
   never loaded while the GL scene is live.

## Mobile / mobile-high / low / reduced / no-JS

- Mobile: 0 B of library, no canvas. `hero.js` (2.8 KB gz, loaded after `load` + idle) decodes
  `ribbons-a-540.webp` (14.5 KB) and paints it into a `<canvas class="hero__plate hero__plate--a">`
  that fades in (`.is-ready`) and drifts on one 14 s `transform` keyframe, 6 alternate iterations
  (= 3 round trips) then stops; `animation-play-state: paused` while the hero is < 10 % visible
  (`.is-offscreen`). Mobile-high adds `ribbons-b-540.webp` (14.4 KB) with the 12 s opacity
  cross-fade. Budget: ≤ 2 animating layers, 28.9 KB of images, `.hero { contain: paint }`.
- Low (`saveData`, 2g/3g, ≤ 2 GB / ≤ 2 cores): glow gradient only, no plate (`hero.js` adds
  nothing; `10-hero.css` also hides `.hero__plate--a` as a belt and braces).
- Reduced: no module runs, no plates exist, h1 static, actions/trust at full opacity, glow static.
- No JS: no `html.motion--*` class, so the h1 is static, the CTA and trust line are painted, the
  glow gradient shows; the ribbons are the one thing that needs a script.

## Why the plates are canvases and the glow is a gradient

An `<img>` plate is an LCP candidate. With the plates as `<img loading="lazy">` Chrome reported
`.hero__plate--glow` as the LCP element on both 1440 and 360 (its painted area beats the h1), so a
late plate on 4G would have moved LCP. A `<canvas>` painted from the decoded WebP is not an LCP
candidate and a CSS gradient is not either, so the hero text stays the LCP element on mobile
(`DIV.hero__body`, ≈ 55 ms on the dev box) and the plates can arrive whenever they like. The
low-quality glow WebP also banded on a dark ground at dpr 3; the gradient does not.

Known accounting: on desktop the clip wipe makes Chrome record the h1 with its first-paint
clipped rect (≈ 0), so the desktop LCP element is the next largest thing (header logo, ~56 ms).
LCP time is not hurt. If the client prefers the h1 to be the LCP element on desktop as well, set
`--sf-hero-clip` to `0ms` (config `css['hero-clip']` and the `00-core.css` token): the wipe goes,
the sweep stays. Starting the keyframe unclipped for one frame also works but flashes the headline.

## Kill switches (config only)

`hero.webgl: false` → plates on desktop too. `hero.plates.mobileHigh: false` → one drifting plate
everywhere. `flags.hero: false` → no module, glow gradient only. `hero.bloom` stays `false`
(PLAN §8 #2; the shader's halo copies are the bloom).

## Verified 2026-09-28 (Playwright, Chromium 1208, `php -S 127.0.0.1:8091`)

1440 × 900: h1 clip running from the first snapshot with the sweep paused under the full intro and
running after `sf-intro-done`; `ribbons-gl.js` imported after load + idle, `SF.motion.hero.state`
`gl`, 6,914 triangles, 120 fps over 3 s idle and 1.5 s of pointer movement (p95 frame 9.1 ms, kill
switch mean 8.33 ms), CTA / cue / trust hit-testable at every snapshot, scroll to 60 % of the hero
keeps the canvas live and settles the plates, forced `WEBGL_lose_context` → canvas removed, state
`plates` with reason `context-lost`, 960 w plates a + b drifting. 360 × 780 touch (dpr 3):
`motion--mobile`, no canvas, only `hero.js` + the two 540 w plates requested, h1 `clip-path: none`,
plates paused when scrolled off; `saveData` → `motion--low`, no plate, glow only; reduced motion →
no module, no plates; JS off → h1, CTA and trust at opacity 1, glow present, no veil. Zero console
errors, page errors or failed requests in every run. Cost: `hero.js` 2.8 KB gz, `ribbons-gl.js`
4.3 KB gz (desktop only), `10-hero.css` 1.2 KB gz, plates 28.9 KB (mobile) / 74 KB (desktop
fallback only).
