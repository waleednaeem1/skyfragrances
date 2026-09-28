const cfg = window.SF_MOTION || {};
const doc = document;
const root = doc.documentElement;
const listeners = new Map();
const scriptTag = doc.querySelector('script[data-motion-v]');
const version = scriptTag ? scriptTag.getAttribute('data-motion-v') || '' : '';
const moduleBase = new URL('./', import.meta.url);
const vendorBase = new URL('../vendor/', import.meta.url);
const bodyClasses = doc.body ? doc.body.className.split(/\s+/) : [];
const loaderCfg = cfg.loader || { sections: [], rootMargin: '60% 0px', idleTimeout: 2000, libTimeout: 8000, commercePages: [] };
const commercePage = (loaderCfg.commercePages || []).some((name) => bodyClasses.includes(name));
const motionCssLinked = !!doc.getElementById('sf-motion-css');
const flagFor = { hero: 'hero', cards: 'fan', story: 'story', micro: 'micro', quiz: 'quiz', transitions: 'transitions' };
const revealCfg = cfg.reveal || { rootMargin: '0px 0px -12% 0px', threshold: 0, y: 18, duration: 0.6, stagger: 0.07, delayStep: 70, ease: 'expo.out' };

function readFlagOverrides() {
  try {
    return JSON.parse(localStorage.getItem('sfMotionFlags') || '{}') || {};
  } catch (error) {
    return {};
  }
}

function fallbackClassify() {
  const reduced = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
  const fine = !!(window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches);
  const wide = !!(window.matchMedia && matchMedia('(min-width: 1024px)').matches);
  return { device: reduced ? 'reduced' : wide && fine ? 'desktop' : 'mobile', reduced, touch: !fine, slow: false, gpu: 'ok' };
}

const classify = typeof cfg.classify === 'function' ? cfg.classify : fallbackClassify;
const flags = Object.assign({}, cfg.flags || {}, readFlagOverrides());
const info = classify();

function versioned(file, base) {
  return new URL(file + (version ? '?v=' + version : ''), base).href;
}

function on(name, handler) {
  if (!listeners.has(name)) {
    listeners.set(name, new Set());
  }
  listeners.get(name).add(handler);
  return () => off(name, handler);
}

function off(name, handler) {
  const set = listeners.get(name);
  if (set) {
    set.delete(handler);
  }
}

function emit(name, detail) {
  const set = listeners.get(name);
  if (set) {
    set.forEach((handler) => {
      try {
        handler(detail);
      } catch (error) {
        console.error(error);
      }
    });
  }
  doc.dispatchEvent(new CustomEvent('sf:motion:' + name, { detail, bubbles: false }));
}

function allows(flag) {
  if (motion.reduced) {
    return false;
  }
  return flag === true || (flag === 'desktop' && motion.device === 'desktop');
}

function snapshot() {
  return { device: motion.device, reduced: motion.reduced, touch: motion.touch, slow: motion.slow, gpu: motion.gpu, mobileHigh: motion.mobileHigh };
}

function applyCssTokens() {
  Object.keys(cfg.css || {}).forEach((key) => {
    root.style.setProperty('--sf-' + key, String(cfg.css[key]));
  });
}

function loadScript(file) {
  return new Promise((resolve, reject) => {
    const tag = doc.createElement('script');
    tag.src = versioned(file, vendorBase);
    tag.async = true;
    tag.onload = () => resolve(file);
    tag.onerror = () => reject(new Error('motion: could not load ' + file));
    doc.head.appendChild(tag);
  });
}

function withTimeout(promise, ms, fallback) {
  return new Promise((resolve) => {
    const timer = setTimeout(() => resolve(fallback), ms);
    promise.then((value) => {
      clearTimeout(timer);
      resolve(value);
    }, () => {
      clearTimeout(timer);
      resolve(fallback);
    });
  });
}

const motion = {
  device: info.device,
  reduced: info.reduced,
  touch: info.touch,
  slow: info.slow,
  gpu: info.gpu,
  mobileHigh: false,
  commercePage,
  version,
  config: cfg,
  flags,
  gsap: null,
  ScrollTrigger: null,
  lenis: null,
  sections: {},
  on,
  off,
  emit,
  allows,
  snapshot,
  loadScript
};

let gsapPromise = null;

function ensureGsap() {
  if (gsapPromise) {
    return gsapPromise;
  }
  if (motion.device !== 'desktop') {
    gsapPromise = Promise.resolve(null);
    return gsapPromise;
  }
  const load = loadScript('gsap.min.js').then(() => loadScript('ScrollTrigger.min.js')).then(() => {
    const gsap = window.gsap;
    const ScrollTrigger = window.ScrollTrigger;
    if (!gsap || !ScrollTrigger) {
      throw new Error('motion: gsap globals missing');
    }
    gsap.registerPlugin(ScrollTrigger);
    ScrollTrigger.config({ ignoreMobileResize: true });
    gsap.defaults({ ease: (cfg.ease && cfg.ease.gsap && cfg.ease.gsap.silk) || 'power3.out' });
    motion.gsap = gsap;
    motion.ScrollTrigger = ScrollTrigger;
    emit('gsap', { gsap, ScrollTrigger });
    return { gsap, ScrollTrigger };
  });
  gsapPromise = withTimeout(load, loaderCfg.libTimeout || 8000, null);
  return gsapPromise;
}

function preventInnerScrollers() {
  ((cfg.scroll && cfg.scroll.prevent) || []).forEach((selector) => {
    doc.querySelectorAll(selector).forEach((el) => el.setAttribute('data-lenis-prevent', ''));
  });
}

function dialogsOpen() {
  const SF = window.SF;
  return !!(SF && typeof SF.dialogIsOpen === 'function' && SF.dialogIsOpen());
}

function initLenis() {
  if (motion.lenis || motion.device !== 'desktop' || commercePage || !allows(flags.lenis)) {
    return Promise.resolve(motion.lenis);
  }
  const options = (cfg.scroll && cfg.scroll.lenis) || {};
  return ensureGsap().then((libs) => (libs ? loadScript('lenis.min.js') : Promise.reject(new Error('motion: gsap first')))).then(() => {
    const Lenis = window.Lenis;
    if (!Lenis || motion.lenis) {
      return motion.lenis;
    }
    const windows = /Win/.test(navigator.platform || '');
    const lenis = new Lenis({
      lerp: windows ? options.lerpWindows || 0.12 : options.lerp || 0.1,
      wheelMultiplier: options.wheelMultiplier || 1,
      smoothWheel: options.smoothWheel !== false,
      syncTouch: !!options.syncTouch,
      anchors: options.anchors !== false,
      autoRaf: false
    });
    preventInnerScrollers();
    lenis.on('scroll', motion.ScrollTrigger.update);
    motion.gsap.ticker.add((time) => lenis.raf(time * 1000));
    motion.gsap.ticker.lagSmoothing(0);
    doc.addEventListener('sf:dialog-open', () => lenis.stop());
    doc.addEventListener('sf:dialog-close', () => {
      if (!dialogsOpen()) {
        lenis.start();
      }
    });
    if (doc.body.classList.contains('is-locked') || dialogsOpen()) {
      lenis.stop();
    }
    motion.lenis = lenis;
    emit('lenis', { lenis });
    return lenis;
  }).catch(() => null);
}

function destroyLenis() {
  if (!motion.lenis) {
    return;
  }
  motion.lenis.destroy();
  motion.lenis = null;
  emit('lenis', { lenis: null });
}

function scrollTo(target, options) {
  const opts = options || {};
  const el = typeof target === 'string' ? doc.querySelector(target) : target && target.nodeType === 1 ? target : null;
  const instant = !!opts.immediate || motion.reduced;
  if (motion.lenis && !motion.lenis.isStopped) {
    motion.lenis.scrollTo(el || target, { offset: opts.offset || 0, immediate: instant });
    return;
  }
  if (el) {
    el.scrollIntoView({ behavior: instant ? 'auto' : 'smooth', block: opts.block || 'start' });
    return;
  }
  if (typeof target === 'number') {
    window.scrollTo({ top: target + (opts.offset || 0), left: 0, behavior: instant ? 'auto' : 'smooth' });
  }
}

let refreshTimer = 0;

function refresh(delay) {
  clearTimeout(refreshTimer);
  refreshTimer = setTimeout(() => {
    if (motion.ScrollTrigger) {
      motion.ScrollTrigger.refresh();
    }
    emit('refresh');
  }, typeof delay === 'number' ? delay : (cfg.scroll && cfg.scroll.refreshDebounce) || 150);
}

function bindRefresh() {
  const afterToggle = (cfg.scroll && cfg.scroll.refreshAfterToggle) || 420;
  doc.addEventListener('sf:size-change', () => refresh());
  doc.addEventListener('sf:dialog-close', () => refresh());
  window.addEventListener('resize', () => refresh(), { passive: true });
  doc.addEventListener('load', (event) => {
    if (event.target && event.target.tagName === 'IMG') {
      refresh();
    }
  }, true);
  doc.addEventListener('click', (event) => {
    if (event.target.closest && event.target.closest('.js-accordion-trigger, .js-footer-toggle, .js-expand')) {
      refresh(afterToggle);
    }
  });
}

function claimReveal() {
  if (!motionCssLinked || motion.device !== 'desktop' || !allows(flags.reveal)) {
    return false;
  }
  window.SFReveal = Object.assign(window.SFReveal || {}, { claimed: true });
  return true;
}

function revealNode(el) {
  if (el.classList.contains('is-visible')) {
    return;
  }
  const steps = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
  const delay = steps > 0 && steps <= 5 ? (steps * revealCfg.delayStep) / 1000 : 0;
  const gsap = motion.gsap;
  if (!gsap) {
    if (delay) {
      el.style.transitionDelay = delay * 1000 + 'ms';
    }
    el.classList.add('is-visible');
    emit('reveal', { el, engine: 'css' });
    return;
  }
  const staggered = el.classList.contains('sf-reveal--stagger');
  const targets = staggered ? Array.from(el.children) : [el];
  el.classList.add('sf-reveal--gsap');
  gsap.set(targets, { opacity: 0, y: revealCfg.y });
  el.classList.add('is-visible');
  gsap.to(targets, {
    opacity: 1,
    y: 0,
    duration: revealCfg.duration,
    ease: revealCfg.ease,
    delay,
    stagger: staggered ? revealCfg.stagger : 0,
    overwrite: true,
    clearProps: 'opacity,transform',
    onComplete: () => emit('reveal', { el, engine: 'gsap' })
  });
}

function initReveal() {
  const nodes = Array.from(doc.querySelectorAll('.sf-reveal'));
  if (!nodes.length) {
    return;
  }
  if (typeof window.IntersectionObserver === 'undefined') {
    nodes.forEach((el) => el.classList.add('is-visible'));
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }
      io.unobserve(entry.target);
      revealNode(entry.target);
    });
  }, { rootMargin: revealCfg.rootMargin, threshold: revealCfg.threshold });
  nodes.forEach((el) => io.observe(el));
}

function afterLoadIdle(fn) {
  const idle = () => {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => fn(), { timeout: loaderCfg.idleTimeout || 2000 });
    } else {
      setTimeout(fn, 200);
    }
  };
  if (doc.readyState === 'complete') {
    idle();
  } else {
    window.addEventListener('load', idle, { once: true });
  }
}

function collectRoots() {
  const map = new Map();
  doc.querySelectorAll('[data-motion]').forEach((el) => {
    String(el.getAttribute('data-motion') || '').split(/\s+/).forEach((name) => {
      if (!name || !(loaderCfg.sections || []).includes(name) || !allows(flags[flagFor[name]])) {
        return;
      }
      if (!map.has(name)) {
        map.set(name, []);
      }
      map.get(name).push(el);
    });
  });
  return map;
}

function loadSection(name, roots) {
  if (motion.sections[name]) {
    return motion.sections[name];
  }
  const ready = motion.device === 'desktop' ? ensureGsap() : Promise.resolve(null);
  motion.sections[name] = ready.then(() => import(versioned(name + '.js', moduleBase))).then((mod) => {
    const init = typeof mod.default === 'function' ? mod.default : mod.init;
    const instances = typeof init === 'function' ? roots.map((el) => init(el, window.SF)) : [];
    emit('section', { name, roots, instances });
    return instances;
  }).catch((error) => {
    emit('section-error', { name, error });
    return null;
  });
  return motion.sections[name];
}

function scheduleSections() {
  if (commercePage || motion.reduced) {
    return;
  }
  collectRoots().forEach((roots, name) => {
    if (name === 'hero' || typeof window.IntersectionObserver === 'undefined') {
      afterLoadIdle(() => loadSection(name, roots));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        io.disconnect();
        loadSection(name, roots);
      }
    }, { rootMargin: loaderCfg.rootMargin || '60% 0px' });
    roots.forEach((el) => io.observe(el));
  });
}

function frameBudget(frames) {
  const wanted = frames || 60;
  return new Promise((resolve) => {
    const deltas = [];
    let last = 0;
    const tick = (now) => {
      if (last) {
        deltas.push(now - last);
      }
      last = now;
      if (deltas.length < wanted) {
        requestAnimationFrame(tick);
        return;
      }
      const sorted = deltas.slice().sort((a, b) => a - b);
      resolve({
        mean: deltas.reduce((sum, d) => sum + d, 0) / deltas.length,
        p95: sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * 0.95))],
        interval: sorted[Math.floor(sorted.length * 0.1)] || 16.7
      });
    };
    requestAnimationFrame(tick);
  });
}

function whenIntroGone(fn) {
  if (!doc.getElementById('sf-intro') || root.classList.contains('sf-intro-done')) {
    fn();
    return;
  }
  let done = false;
  const finish = () => {
    if (!done) {
      done = true;
      observer.disconnect();
      fn();
    }
  };
  const observer = new MutationObserver(() => {
    if (!doc.getElementById('sf-intro')) {
      finish();
    }
  });
  observer.observe(doc.body, { childList: true });
  setTimeout(finish, 4000);
}

function checkMobileHigh() {
  const rule = (cfg.device && cfg.device.mobileHigh) || { minMemory: 6, minCores: 8, frames: 60, p95Ratio: 1.3, settleMs: 600 };
  const nav = navigator;
  const conn = nav.connection || {};
  if (motion.device !== 'mobile' || conn.saveData || !(nav.deviceMemory >= rule.minMemory) || !(nav.hardwareConcurrency >= rule.minCores)) {
    return;
  }
  whenIntroGone(() => {
    setTimeout(() => {
      frameBudget(rule.frames).then((result) => {
        emit('frame', result);
        if (result.p95 <= result.interval * rule.p95Ratio && motion.device === 'mobile') {
          motion.mobileHigh = true;
          root.classList.add('motion--mobile-high');
          emit('device', snapshot());
        }
      });
    }, rule.settleMs);
  });
}

function onMediaChange() {
  const next = classify();
  if (typeof cfg.applyClass === 'function') {
    cfg.applyClass(next);
  }
  const previous = motion.device;
  Object.assign(motion, { device: next.device, reduced: next.reduced, touch: next.touch, slow: next.slow, gpu: next.gpu });
  if (next.device !== 'mobile') {
    motion.mobileHigh = false;
  }
  if (next.reduced) {
    if (window.SFReveal && typeof window.SFReveal.showAll === 'function') {
      window.SFReveal.showAll();
    }
    destroyLenis();
  }
  if (previous !== next.device) {
    emit('device', snapshot());
  }
}

function watchMedia() {
  if (!window.matchMedia) {
    return;
  }
  ['(prefers-reduced-motion: reduce)', '(hover: hover) and (pointer: fine)', '(min-width: ' + ((cfg.device && cfg.device.desktopMin) || 1024) + 'px)'].forEach((query) => {
    const mq = matchMedia(query);
    if (mq.addEventListener) {
      mq.addEventListener('change', onMediaChange);
    } else if (mq.addListener) {
      mq.addListener(onMediaChange);
    }
  });
}

function boot() {
  if (revealClaimed) {
    initReveal();
  }
  bindRefresh();
  scheduleSections();
  afterLoadIdle(() => {
    if (motion.device === 'desktop' && !commercePage) {
      ensureGsap().then(() => initLenis());
    }
    checkMobileHigh();
  });
  emit('ready', snapshot());
}

Object.assign(motion, { ensureGsap, initLenis, destroyLenis, scrollTo, refresh, frameBudget, reveal: revealNode, loadSection });
window.SF = window.SF || {};
window.SF.motion = motion;
if (typeof window.SF.scrollTo !== 'function') {
  window.SF.scrollTo = scrollTo;
}
applyCssTokens();
const revealClaimed = claimReveal();
watchMedia();
if (doc.readyState === 'loading') {
  doc.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

export default motion;
