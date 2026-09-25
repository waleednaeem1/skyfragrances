# Sky Fragrances — Motion Brief (client, verbatim intent, 2026-09-26)

Role: senior motion designer / creative front-end (Awwwards/FWA level). Add a signature animation
layer so the store stands out from competing perfume stores in Pakistan, while staying fast and easy
to buy from.

Brand: gold SF monogram on black, "More Than Just A Scent". Mood: dark, luxurious, mysterious, sensual;
black/charcoal, warm gold, soft smoke/mist, glowing light. Customers mostly Pakistan, many on mid-range
Android on 4G. Hostinger shared hosting; keep the existing stack; nothing that needs Node in production.

References (inspiration only, never copy):
1. Dribbble "Kumo" matcha landing — product-as-hero storytelling, big confident type, scroll reveals.
2. tarot.meetyourpsychic.com — layered parallax hero (depth layers), mystical dark palette, fanned cards.
3. austensor.com/page5 — Three.js flowing neon light tubes with bloom, reacting to drag and scroll.

Concepts: (1) intro loader <2s: monogram stroke draws, gold shimmer fill, dissolves to mist revealing the
hero; full version first visit only (sessionStorage), quick fade later. (2) Hero "scent trails": bottle
centred, 3–5 flowing gold/amber light ribbons (Three.js TubeGeometry, additive blending, subtle bloom)
reacting gently to mouse and scroll; headline revealed line by line with a mask; mobile/low-power →
CSS/SVG or short looping video. (3) Layered depth parallax of ingredients (rose petals, oud chips,
citrus, smoke wisps) drifting at different speeds on scroll and mouse. (4) Pinned fragrance story:
bottle fixed, slowly rotating/tilting while Top → Heart → Base notes reveal beside it; background shifts
per note's mood. (5) Collection as fanned cards spreading on scroll; desktop hover 3D tilt + lift + gold
light sweep; mobile swipeable snap carousel. (6) Signature-scent quiz, 3–4 questions, card-flip
transitions (tarot feel), dramatic reveal of the recommended perfume with Add to Cart. (7) Micro:
magnetic buttons; subtle custom cursor growing over products (desktop only); gold underline draws on
links; Add to Cart mist-spray particle burst + item flies to the cart icon with a bounce; numbers and
prices tick up on reveal. (8) Smooth scrolling (Lenis) and soft mist page transitions.

Tech: GSAP + ScrollTrigger; Lenis; Three.js only in the hero, lazy-loaded after first paint. Animate
only transform and opacity. All timings/easings in ONE config file. Easing language: slow, silky
(expo/power3 out); nothing bouncy except the small cart bounce.

Hard rules: 60fps and Lighthouse mobile Performance ≥ 85; lazy-load heavy assets, WebP/AVIF, code-split
animation code. prefers-reduced-motion → calm fades only. Mobile first, every effect simplified on
mobile, test at 360px. Animations never block buying: price, Add to Cart, COD/payment info visible and
clickable immediately. Accessible: focus states, nothing hidden only inside canvas, readable gold on
black. Do not break cart, checkout, product pages.

How to work: (1) review codebase + references, give a short motion plan (per section: what, how, mobile
fallback), WAIT for OK; (2) implement in order hero → loader → collection → story → micro → quiz → page
transitions; (3) after each section check desktop + mobile + performance and report briefly; (4) if a
concept hurts performance or conversion, propose a better alternative.
