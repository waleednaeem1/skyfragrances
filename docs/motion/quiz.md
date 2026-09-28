# Motion — quiz section (`quiz.js`, `50-quiz.css`)

Owner files: `site/app/views/quiz.php`, `site/app/views/quiz-result.php`,
`site/assets/js/motion/quiz.js`, `site/assets/css/motion/50-quiz.css`; one field added in
`site/app/controllers/quiz.php` (`hero.match`, 0–100 = `score / QUIZ_MAX_SCORE (33)`).
Timings live in `SF_MOTION.quiz` and `SF_MOTION.css['quiz-*']` (`config.js`). PLAN.md §2.6 is
the spec; §8 #7 and #16 are the two decisions this follows (five questions kept, no advance on
keyboard `change`).

## Quiz page (`/scent-finder`)

- Root: `form.js-quiz[data-motion="quiz"]`; fieldsets carry `data-motion-step="1..5"` inside
  `.quiz-deck`; `.js-quiz-live` is the `aria-live="polite"` announcer.
- `forms.js initQuizSteps` still owns first paint (`is-active` on step 1, submit hidden until the
  last step, validation + error focus). `quiz.js` intercepts `a[href^="#question-"]` clicks in the
  capture phase **only when the move is allowed** (going back, or forward with an answer picked);
  an unanswered Next falls through to `forms.js`, which shows the error and focuses the radio.
- Advance: Next control, or a pointer click on a `.choice--answer` (detected by a `pointerup` on
  the same label within `quiz.pointerWindowMs`, so Space/arrow keys never trigger it) after
  `quiz.undoMs` (600 ms) — the step gets `.is-armed` and a gold line fills under the chosen card
  for the whole window; any keydown, a pointerdown outside a choice, or a different choice cancels
  or restarts it. The last step never auto-submits.
- Desktop (GSAP): leaving card `rotationY 0→∓90` + `autoAlpha` in `flipOut` (380 ms `power3.in`),
  entering card `±90→0` in `flipIn` (520 ms `expo.out`), `transformPerspective 1400`; the leaving
  fieldset is `position:absolute` under `.is-leaving` so the deck takes the entering card's height;
  `clearProps` at the end, DOM identical to `forms.js` afterwards.
- Mobile (no library): `.is-leaving` / `.is-entering` keyframes, `translateX ±24px` + opacity
  over `--sf-quiz-slide` (320 ms), direction via `--sf-quiz-dir`, `animationend` handoff with a
  400 ms fallback. Low: 150 ms opacity only. Reduced: module never loads, `forms.js` steps as today.
- Progress: the entering card's `.progress__fill` scales from the previous percentage
  (`--sf-quiz-fill-from`) to its own, transform only.
- Focus moves to the new `legend` when the transition ends; live region says "Question N of 5";
  the deck scrolls into view only when its top is above the header.
- Card look (all motion classes): gold hairline frame + inner frame, raised surface, one 700 ms
  transform-only "deal" on desktop at load. Nothing is opacity-gated at first paint.

## Result page (`/scent-finder/result`)

- Root: `article.split--pdp[data-motion="quiz"]`. Media is wrapped in
  `.split__media.quiz-card > .quiz-card__inner > (.quiz-card__back + .product-card__media.quiz-card__front)`
  plus `.quiz-card__glow` and `.quiz-card__mist` (all decorative, `aria-hidden`, `display:none`
  unless `html.motion--desktop`). `.split__aside` (name, price, size, Add to Cart / Choose a Size,
  View Details) has no motion class and is painted at first frame on every class.
- Desktop is **CSS-driven from first paint** so it completes even if the module never arrives:
  hold `--sf-quiz-hold` (450 ms, card back breathes) → `.quiz-card__inner` turns `rotateY 180→0`
  over `--sf-quiz-turn` (900 ms) with the glow swelling and the mist clearing mid-turn → heading
  gold sweep → `.quiz-notes__group` rise in `--sf-quiz-notes-step` steps from
  `--sf-quiz-notes-at` (1.25 s) → `.quiz-match` fades in and ticks → meters fill (their
  `transition-delay` is pushed after the notes). `quiz.js` adds `.is-waiting` (pauses the turn) until
  the product image has loaded (max `imageWaitMs`), `.is-revealed` when the turn ends, and runs the
  match count-up on the `aria-hidden` twin (`.quiz-match__tick`, tabular, `min-width: Nch`) — the
  real "N% match" text is in a `u-sr-only` span. The tick is skipped when the module arrives more
  than `tick.lateMs` after its start time, so a late script never resets a number already shown.
- Mobile: no back, no mist, no opacity on the LCP image — the image settles `scale 1.06→1` only;
  match ticks if the module is there by `mobileAtMs`. Low / reduced / no-JS: static page as before.

## Verified (Playwright, 2026-09-26)

Desktop 1440×900: GSAP flip, undo window, unanswered Next blocked with the existing error, arrow
keys never advance, back flip, keyboard Next, submit → `?a=1-2-3-2-1`; result price/CTA opacity 1
and visible at first frame, card back shown then `.is-revealed`, match 76→76, notes and meters
visible; 50 flip frames mean 8.0 ms / p95 9.8 ms headless; zero console/page/request errors.
Pixel 5: no vendor requests, slide keyframes, absolute leaving card, live region, 44 px answers,
image visible at once, match ticks. Reduced: no module, plain fieldsets, `forms.js` stepping
intact, static result. JS off: five fieldsets + submit visible, GET submit reaches the result with
price, match text and image. 360 px: no horizontal overflow on either page.
