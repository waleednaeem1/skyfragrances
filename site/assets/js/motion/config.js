window.SF_MOTION = {
  version: 2,
  breakpoints: { mobile: 767, desktop: 1024 },
  ease: {
    out: 'cubic-bezier(0.16, 1, 0.3, 1)',
    silk: 'cubic-bezier(0.22, 1, 0.36, 1)',
    soft: 'cubic-bezier(0.4, 0, 0.2, 1)',
    in: 'cubic-bezier(0.7, 0, 0.84, 0)',
    gsap: { out: 'expo.out', silk: 'power3.out', in: 'power3.in', scrub: 'none' }
  },
  duration: { fast: 150, base: 280, slow: 600 },
  css: {
    'ease-out': 'cubic-bezier(0.16, 1, 0.3, 1)',
    'ease-silk': 'cubic-bezier(0.22, 1, 0.36, 1)',
    'ease-in': 'cubic-bezier(0.7, 0, 0.84, 0)',
    'dur-reveal': '600ms',
    'reveal-y': '18px',
    'hero-clip': '1100ms',
    'hero-sweep': '900ms',
    'hero-actions': '400ms',
    'hero-delay-mobile': '300ms',
    'hero-delay-desktop': '600ms',
    'plate-drift': '14s',
    'plate-fade': '12s',
    'plate-cycles': '6',
    'hero-gl-fade': '900ms',
    'fan-rotate': '16deg',
    'fan-rise': '24px',
    'fan-failsafe': '6000ms',
    'fan-settle': '900ms',
    'card-sweep': '700ms',
    'card-tilt': '6deg',
    'card-lift': '8px',
    'card-perspective': '1000px',
    'card-follow': '160ms',
    'card-return': '600ms',
    'rail-thumb': '0.38',
    'reveal-step': '70ms',
    'story-hue': '900ms',
    'story-hue-mobile': '300ms',
    'story-tilt': '900ms',
    'story-notes': '700ms',
    'story-notes-y': '16px',
    'story-glow-alpha': '0.22',
    'story-wash-alpha': '0.06',
    'quiz-slide': '320ms',
    'quiz-flip-out': '380ms',
    'quiz-flip-in': '520ms',
    'quiz-undo': '600ms',
    'quiz-deal': '700ms',
    'quiz-hold': '450ms',
    'quiz-turn': '900ms',
    'quiz-mist': '400ms',
    'quiz-notes-at': '1250ms',
    'quiz-notes-step': '60ms',
    'quiz-settle': '700ms',
    'veil': '200ms',
    'veil-failsafe': '900ms',
    'veil-out': '160ms',
    'veil-in': '200ms',
    'underline': '280ms',
    'tick': '900ms',
    'burst': '420ms',
    'flight': '400ms',
    'cursor-size': '22px',
    'cursor-grow': '3',
    'cursor-fill': '0.3',
    'cursor-follow': '180ms'
  },
  flags: {
    reveal: 'desktop',
    hero: true,
    loader: true,
    fan: 'desktop',
    story: true,
    layers: 'desktop',
    micro: true,
    magnetic: 'desktop',
    cursor: 'desktop',
    cartFly: 'desktop',
    tick: true,
    quiz: true,
    lenis: false,
    transitions: 'desktop'
  },
  device: {
    desktopMin: 1024,
    lowMemory: 2,
    lowCores: 2,
    slowTypes: ['slow-2g', '2g', '3g'],
    desktopGpu: { minMemory: 4, minCores: 4 },
    mobileHigh: { minMemory: 6, minCores: 8, frames: 60, p95Ratio: 1.3, settleMs: 600 },
    frameKill: { frames: 120, meanMs: 20, strikes: 2 }
  },
  loader: {
    sections: ['hero', 'cards', 'story', 'micro', 'quiz', 'transitions'],
    rootMargin: '60% 0px 60% 0px',
    idleTimeout: 2000,
    libTimeout: 8000,
    commercePages: ['cart', 'checkout', 'track', 'confirmation']
  },
  reveal: {
    rootMargin: '0px 0px -12% 0px',
    threshold: 0,
    y: 18,
    duration: 0.6,
    stagger: 0.07,
    delayStep: 70,
    ease: 'expo.out'
  },
  scroll: {
    lenis: { lerp: 0.1, lerpWindows: 0.12, wheelMultiplier: 1, smoothWheel: true, syncTouch: false, anchors: true },
    prevent: ['.drawer__body', '.nav-mobile', '.modal__body', '.lightbox__rail', '.rail', '.gallery__rail', '.suggest'],
    refreshDebounce: 150,
    refreshAfterToggle: 420
  },
  hero: {
    webgl: true,
    bloom: false,
    dprMax: 1.5,
    inViewStart: 0.5,
    pauseBelow: 0.05,
    groundMaxOpacity: 0.6,
    glFadeMs: 900,
    frame: { objectUnits: 0.9, plateUnits: 6, plateAspect: 2 },
    ribbons: {
      count: 4,
      radii: [0.05, 0.032, 0.022, 0.015],
      tubular: 72,
      radial: 6,
      bounds: { x: [-6.2, 6.2], y: [-3.0, 0.9], z: [-0.95, 0.6], headline: { x: 3.0, y: [-2.5, -1.0] } },
      paths: [
        [[-6.2, -2.4, 0.3], [-3.6, -0.9, -0.2], [-1.2, 0.25, -0.7], [0.9, 0.45, -0.6], [3.4, -0.5, -0.1], [6.2, -2.0, 0.4]],
        [[-6.2, -1.3, 0.6], [-3.8, 0.1, 0.1], [-1.4, 0.7, -0.4], [1.2, 0.2, -0.8], [3.8, -1.1, -0.3], [6.2, -2.8, 0.2]],
        [[-6.2, -0.4, -0.4], [-3.2, 0.6, -0.6], [-0.6, -0.15, -0.9], [1.6, 0.8, -0.5], [4.0, 0.1, 0.0], [6.2, -1.1, 0.5]],
        [[-6.2, -3.0, 0.1], [-4.0, -1.7, 0.4], [-1.6, -0.5, -0.2], [0.6, 0.0, -0.95], [2.8, 0.55, -0.4], [6.2, -0.3, -0.2]]
      ],
      colors: { gold: '#C29C6E', amber: '#E0A45C' },
      speed: [0.6, 1.6],
      freq: [9, 15],
      pulse: { speed: 0.06, spread: 40, mix: 0.5, gain: 0.5 },
      breathe: { amp: 0.06, freq: 1.4, speed: 0.7 },
      halo: { scale: 3.0, alpha: 0.14 },
      gain: 1.0
    },
    sprite: { size: 512, opacity: 0.35, y: 0, scale: 2.6 },
    camera: { fov: 50, z: 5.2 },
    pointer: { rotY: 0.05, rotX: 0.03, baseX: 0.12, lerp: 0.06 },
    scroll: { rotX: 0.15, y: 0.6 },
    title: { clipMs: 1100, sweepMs: 900, sweepDelayNoLoader: 300, actionsMs: 400 },
    delay: { mobile: 300, desktop: 600, productAlone: 600 },
    plates: { driftMs: 14000, crossfadeMs: 12000, cycles: 3, pauseBelow: 0.1, mobileHigh: true, maxAnimating: 2, settleMs: 400 }
  },
  cards: {
    fan: { rotate: 16, rise: 24, scrub: 0.6, start: 'top 85%', end: 'top 35%', replay: false, origin: '50% 110%', settleMs: 1100, staggerMs: 60, failsafeMs: 6000, failsafeSettleMs: 900, singleGroupRows: 2 },
    hover: { tilt: 6, lift: 8, sweepMs: 700, ease: 'power3.out', followMs: 160, returnMs: 600 },
    rail: { thumb: 0.38 },
    perspective: 1000
  },
  story: {
    hueMs: 900,
    hueMobileMs: 300,
    tilt: {
      enabled: true,
      ms: 900,
      ease: 'power3.out',
      states: {
        top: { rotY: -6, rotX: 2, scale: 1.02 },
        heart: { rotY: 0, rotX: 0, scale: 1.03 },
        base: { rotY: 6, rotX: -2, scale: 1.02 }
      }
    },
    notes: { y: 16, ms: 700, staggerMs: 60, ease: 'expo.out' },
    triggers: { start: 'top 62%', end: 'bottom 62%' },
    perspective: 1200,
    mobile: { rootMargin: '-38% 0px -42% 0px', threshold: 0 },
    bottle: { widths: [480, 640] },
    hues: {
      families: {
        'fresh-citrus': { top: '#E6CF8C', heart: '#D8DCC2', base: '#C4AE8E' },
        floral: { top: '#E8C2B4', heart: '#D9A3A0', base: '#B98A91' },
        'amber-spice': { top: '#E0A45C', heart: '#C8703A', base: '#96502E' },
        'oud-smoke': { top: '#B08458', heart: '#8E4E36', base: '#5E3B33' },
        'green-earthy': { top: '#B9C48E', heart: '#8AA07C', base: '#6A7554' },
        default: { top: '#D4B084', heart: '#C29C6E', base: '#8C6A3F' }
      },
      match: [
        ['citrus', 'fresh-citrus'], ['fresh', 'fresh-citrus'], ['aquatic', 'fresh-citrus'], ['musk', 'fresh-citrus'],
        ['floral', 'floral'], ['rose', 'floral'], ['jasmine', 'floral'],
        ['amber', 'amber-spice'], ['spice', 'amber-spice'], ['oriental', 'amber-spice'], ['gourmand', 'amber-spice'], ['vanilla', 'amber-spice'],
        ['oud', 'oud-smoke'], ['smoke', 'oud-smoke'], ['leather', 'oud-smoke'], ['incense', 'oud-smoke'], ['woody', 'oud-smoke'],
        ['green', 'green-earthy'], ['earth', 'green-earthy'], ['vetiver', 'green-earthy'], ['petrichor', 'green-earthy']
      ]
    }
  },
  micro: {
    magnetic: { maxPx: 6, radius: 1.6, duration: 0.45, ease: 'power3.out', targets: '.btn--ghost, .btn--text, .section-header__link', never: '.btn--primary, .js-add-to-cart, .sticky-bar, .drawer, .modal, [href*="/checkout"], [href*="/cart"]' },
    cursor: { size: 22, grow: 3, fillOpacity: 0.3, follow: 0.18, targets: '.product-card, .collection-card, .gallery__open', nativeInside: '.drawer, .modal, .nav-mobile, input, select, textarea, button, .btn, .product-card__action' },
    burst: { desktop: 14, mobile: 8, ms: 420, ease: 'power3.out' },
    flight: { size: 28, ms: 400, ease: 'power3.in' },
    tick: { ms: 900, ease: 'expo.out', targets: '.collection-card__count, .stars__count, [data-tick], [data-countup]', never: '.price__now, [data-price-now], [data-sticky-price], .quiz-match, [role="status"], [aria-live]', settleMs: 4000 },
    burstSpread: { minPx: 26, maxPx: 68, arcDeg: 150, delayMaxMs: 90, sizeMinPx: 4, sizeMaxPx: 8 },
    magneticStaleMs: 1500
  },
  quiz: {
    undoMs: 600,
    pointerWindowMs: 400,
    flipOut: { ms: 380, ease: 'power3.in', rotate: -90 },
    flipIn: { ms: 520, ease: 'expo.out', rotate: 90, overlapMs: 40 },
    perspective: 1400,
    slide: { ms: 320, x: 24, fallbackMs: 400 },
    result: { scale: 1.08, veilMs: 400, notesStaggerMs: 60, maxScore: 33, holdMs: 450, turnMs: 900, notesAtMs: 1250, mobileAtMs: 600, imageWaitMs: 1500, tick: { ms: 900, lateMs: 400 } }
  },
  transitions: {
    outMs: 160,
    inMs: 200,
    veil: { ms: 200, failsafeMs: 900 },
    skip: 'a[target], a[download], a[href^="#"], a[href^="mailto:"], a[href^="tel:"], a[href^="javascript:"], .skip-link, [role="button"], [aria-controls], .js-cart-open, [data-dialog-open], .drawer a, .sticky-bar a, [href*="/checkout"], [href*="/cart"], [href*="/track"], [href*="/admin"], [href*="/api/"], [href*="wa.me"], [href*="instagram.com"]',
    skipPaths: ['/cart', '/checkout', '/track', '/admin', '/api/', '/order/'],
    anchors: { skip: '.skip-link, [role="button"], [aria-controls], [data-dialog-open], .js-cart-open, form a', focus: true, pushState: true },
    restore: { hashRealign: true, settleMs: 80 }
  },
  intro: {
    enabled: true,
    particles: { desktop: 680, mobile: 260 },
    convergeMs: 1250,
    markInAt: 1000,
    shimmerAt: 1220,
    tagAt: 1280,
    dissolveAt: 1780,
    dissolveMs: 340,
    mobileScale: 0.86,
    calm: { markInMs: 300, holdMs: 520, fadeMs: 300 },
    quickFadeMs: 320,
    failsafeMs: 2600
  }
};
(function (cfg) {
  var root = document.documentElement;
  var rules = cfg.device;
  function matches(query) {
    return !!(window.matchMedia && window.matchMedia(query).matches);
  }
  cfg.classify = function () {
    var nav = navigator;
    var conn = nav.connection || {};
    var reduced = matches('(prefers-reduced-motion: reduce)');
    var fine = matches('(hover: hover) and (pointer: fine)');
    var wide = matches('(min-width: ' + rules.desktopMin + 'px)');
    var slow = !!conn.saveData || rules.slowTypes.indexOf(String(conn.effectiveType || '')) !== -1;
    var weak = (nav.deviceMemory > 0 && nav.deviceMemory <= rules.lowMemory) || (nav.hardwareConcurrency > 0 && nav.hardwareConcurrency <= rules.lowCores);
    var desktop = wide && fine;
    var gpuWeak = (nav.deviceMemory > 0 && nav.deviceMemory < rules.desktopGpu.minMemory) || (nav.hardwareConcurrency > 0 && nav.hardwareConcurrency < rules.desktopGpu.minCores);
    return {
      device: reduced ? 'reduced' : desktop ? 'desktop' : (slow || weak) ? 'low' : 'mobile',
      reduced: reduced,
      touch: !fine,
      slow: slow,
      gpu: gpuWeak ? 'weak' : 'ok'
    };
  };
  cfg.applyClass = function (info) {
    root.classList.remove('sf-reduce', 'motion--desktop', 'motion--mobile', 'motion--low');
    root.classList.add(info.reduced ? 'sf-reduce' : 'motion--' + info.device);
    if (info.device !== 'mobile') {
      root.classList.remove('motion--mobile-high');
    }
  };
  cfg.applyClass(cfg.classify());
})(window.SF_MOTION);
