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
    'fan-rotate': '16deg',
    'fan-rise': '24px',
    'card-sweep': '700ms',
    'story-hue': '900ms',
    'story-hue-mobile': '300ms',
    'quiz-slide': '320ms',
    'veil': '200ms',
    'veil-failsafe': '900ms',
    'underline': '280ms'
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
    ribbons: {
      count: 4,
      radii: [0.028, 0.016, 0.011, 0.008],
      tubular: 72,
      radial: 6,
      bounds: { x: [-3.6, 3.6], y: [-1.1, 1.4], z: [-1.0, 0.8], headlineY: 1.6 },
      colors: { gold: '#C29C6E', amber: '#E0A45C' },
      speed: [0.6, 1.6],
      freq: [9, 15],
      pulse: { speed: 0.06, spread: 40, mix: 0.5 },
      breathe: { amp: 0.06, freq: 1.4, speed: 0.7 },
      halo: { scale: 2.6, alpha: 0.16 },
      gain: 1.35
    },
    sprite: { size: 512, opacity: 0.35 },
    camera: { fov: 50, z: 5.2 },
    pointer: { rotY: 0.05, rotX: 0.03, baseX: 0.12, lerp: 0.06 },
    scroll: { rotX: 0.15, y: 0.6 },
    title: { clipMs: 1100, sweepMs: 900, sweepDelayNoLoader: 300, actionsMs: 400 },
    delay: { mobile: 300, desktop: 600, productAlone: 600 },
    plates: { driftMs: 14000, crossfadeMs: 12000, cycles: 3, pauseBelow: 0.1, mobileHigh: true, maxAnimating: 2 }
  },
  cards: {
    fan: { rotate: 16, rise: 24, scrub: 0.6, start: 'top 85%', end: 'top 35%', replay: false },
    hover: { tilt: 6, lift: 8, sweepMs: 700, ease: 'power3.out' },
    perspective: 1000
  },
  story: {
    hueMs: 900,
    hueMobileMs: 300,
    tilt: { rotY: 6, rotX: 2, scale: 1.03, ms: 900, ease: 'power3.out', enabled: true },
    notes: { y: 16, staggerMs: 60 },
    perspective: 1200,
    mobileThreshold: 0.5,
    bottle: { widths: [480, 640] },
    hues: {
      citrus: '#E6CF8C',
      floral: '#D9A3A0',
      oriental: '#C8703A',
      oud: '#B8552F',
      woody: '#8C6A3F',
      fresh: '#E2D6BA',
      default: '#C29C6E'
    }
  },
  micro: {
    magnetic: { maxPx: 6, radius: 1.6, duration: 0.45, ease: 'power3.out', targets: '.btn--ghost, .btn--text, .section-header__link', never: '.btn--primary, .js-add-to-cart, .sticky-bar, .drawer, .modal, [href*="/checkout"], [href*="/cart"]' },
    cursor: { size: 22, grow: 3, fillOpacity: 0.3, follow: 0.18, targets: '.product-card, .collection-card, .gallery__open', nativeInside: '.drawer, .modal, .nav-mobile, input, select, textarea, button' },
    burst: { desktop: 14, mobile: 8, ms: 420, ease: 'power3.out' },
    flight: { size: 28, ms: 400, ease: 'power3.in' },
    tick: { ms: 900, ease: 'expo.out', targets: '.collection-card__count, .stars__count, [data-tick]', never: '.price__now, [data-price-now], [data-sticky-price]' }
  },
  quiz: {
    undoMs: 600,
    flipOut: { ms: 380, ease: 'power3.in', rotate: -90 },
    flipIn: { ms: 520, ease: 'expo.out', rotate: 90 },
    perspective: 1400,
    slide: { ms: 320, x: 24, fallbackMs: 400 },
    result: { scale: 1.08, veilMs: 400, notesStaggerMs: 60, maxScore: 33 }
  },
  transitions: {
    outMs: 160,
    inMs: 200,
    veil: { ms: 200, failsafeMs: 900 },
    skip: 'a[target], a[download], a[href^="#"], .js-cart-open, [data-dialog-open], .drawer a, .sticky-bar a, [href*="/checkout"], [href*="/cart"], [href*="/track"], [href*="/admin"], [href*="wa.me"], [href*="instagram.com"]'
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
