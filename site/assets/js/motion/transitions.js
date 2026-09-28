const doc = document;
const root = doc.documentElement;

function readConfig(SF) {
  const cfg = (SF.motion.config && SF.motion.config.transitions) || {};
  return {
    veilMs: (cfg.veil && cfg.veil.ms) || 200,
    failsafeMs: (cfg.veil && cfg.veil.failsafeMs) || 900,
    skip: cfg.skip || 'a[target], a[download], a[href^="#"]',
    skipPaths: cfg.skipPaths || ['/cart', '/checkout', '/track', '/admin'],
    anchorSkip: (cfg.anchors && cfg.anchors.skip) || '.skip-link, [role="button"], [aria-controls]',
    anchorFocus: !cfg.anchors || cfg.anchors.focus !== false,
    anchorPush: !cfg.anchors || cfg.anchors.pushState !== false,
    hashRealign: !cfg.restore || cfg.restore.hashRealign !== false,
    settleMs: (cfg.restore && cfg.restore.settleMs) || 80
  };
}

function supportsCrossDocumentTransitions() {
  return 'CSSViewTransitionRule' in window && 'onpageswap' in window;
}

function plainLeftClick(event) {
  return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey && !event.defaultPrevented;
}

function urlOf(link) {
  try {
    return new URL(link.href, location.href);
  } catch (error) {
    return null;
  }
}

function isHttp(url) {
  return url.protocol === 'http:' || url.protocol === 'https:';
}

function samePage(url) {
  return url.origin === location.origin && url.pathname === location.pathname && url.search === location.search;
}

function sitePath(url) {
  const base = ((doc.body && doc.body.getAttribute('data-base')) || '').replace(/\/$/, '');
  return base && url.pathname.indexOf(base + '/') === 0 ? url.pathname.slice(base.length) : url.pathname;
}

function hitsSkipPath(url, paths) {
  const pathname = sitePath(url);
  return paths.some((path) => pathname === path.replace(/\/$/, '') || pathname.indexOf(path) === 0);
}

function hashTarget(hash) {
  if (!hash || hash === '#') {
    return null;
  }
  let id = hash.slice(1);
  try {
    id = decodeURIComponent(id);
  } catch (error) {
    return null;
  }
  if (id === 'top') {
    return doc.body;
  }
  return doc.getElementById(id) || doc.querySelector('a[name="' + id.replace(/"/g, '\\"') + '"]');
}

function scrollOffset(target) {
  const margin = parseFloat(getComputedStyle(target).scrollMarginTop || '0');
  return Number.isFinite(margin) ? -margin : 0;
}

function focusTarget(target) {
  if (!target || target === doc.body) {
    return;
  }
  const hadTabindex = target.hasAttribute('tabindex');
  if (!hadTabindex) {
    target.setAttribute('tabindex', '-1');
  }
  try {
    target.focus({ preventScroll: true });
  } catch (error) {
    target.focus();
  }
  if (!hadTabindex) {
    target.addEventListener('blur', () => target.removeAttribute('tabindex'), { once: true });
  }
}

function createVeilLeave(veil, cfg) {
  let leaving = false;
  let timers = [];
  const reset = (restored) => {
    leaving = false;
    timers.forEach(clearTimeout);
    timers = [];
    if (!veil) {
      return;
    }
    veil.classList.toggle('is-restored', !!restored);
    veil.classList.remove('is-active');
    if (restored) {
      requestAnimationFrame(() => veil.classList.remove('is-restored'));
    }
  };
  const leave = (href) => {
    if (leaving) {
      return false;
    }
    leaving = true;
    if (veil) {
      veil.classList.add('is-active');
    }
    timers.push(setTimeout(() => { location.href = href; }, veil ? cfg.veilMs : 0));
    timers.push(setTimeout(() => reset(false), cfg.failsafeMs));
    return true;
  };
  return { leave, reset, isLeaving: () => leaving };
}

function eligibleLink(event, cfg) {
  if (!plainLeftClick(event) || !event.target || !event.target.closest) {
    return null;
  }
  const link = event.target.closest('a[href]');
  if (!link || link.matches(cfg.skip)) {
    return null;
  }
  const url = urlOf(link);
  if (!url || !isHttp(url) || url.origin !== location.origin || (url.hash && samePage(url)) || hitsSkipPath(url, cfg.skipPaths)) {
    return null;
  }
  return url;
}

function anchorLink(event, cfg) {
  if (!plainLeftClick(event) || !event.target || !event.target.closest) {
    return null;
  }
  const link = event.target.closest('a[href]');
  if (!link || link.matches(cfg.anchorSkip)) {
    return null;
  }
  const url = urlOf(link);
  if (!url || !isHttp(url) || !url.hash || !samePage(url)) {
    return null;
  }
  const target = hashTarget(url.hash);
  return target ? { link, url, target } : null;
}

function shouldSkipNativeTransition(event, cfg) {
  if (event.navigationType === 'reload' || event.downloadRequest !== null || event.formData) {
    return true;
  }
  if (event.hashChange) {
    return false;
  }
  try {
    const url = new URL(event.destination.url, location.href);
    return url.origin !== location.origin || hitsSkipPath(url, cfg.skipPaths);
  } catch (error) {
    return true;
  }
}

export default function init(rootEl, SF) {
  const motion = SF && SF.motion;
  if (!motion || motion.device !== 'desktop' || motion.commercePage) {
    return null;
  }
  const cfg = readConfig(SF);
  const veil = rootEl && rootEl.classList && rootEl.classList.contains('sf-veil') ? rootEl : (rootEl && rootEl.querySelector ? rootEl.querySelector('.sf-veil') : null) || doc.querySelector('.sf-veil');
  const native = supportsCrossDocumentTransitions();
  const exit = createVeilLeave(veil, cfg);
  const cleanups = [];
  let skipNative = false;
  let userScrolled = false;
  const listen = (target, name, handler, options) => {
    target.addEventListener(name, handler, options);
    cleanups.push(() => target.removeEventListener(name, handler, options));
  };
  const scrollToTarget = (target, immediate) => {
    motion.scrollTo(target, { offset: scrollOffset(target), immediate: !!immediate, block: 'start' });
  };
  root.classList.add(native ? 'sf-transitions-native' : 'sf-transitions-veil');
  listen(doc, 'click', (event) => {
    const anchor = anchorLink(event, cfg);
    if (!anchor) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    scrollToTarget(anchor.target, false);
    if (cfg.anchorPush && location.hash !== anchor.url.hash) {
      history.pushState(null, '', anchor.url.hash);
    }
    if (cfg.anchorFocus) {
      focusTarget(anchor.target);
    }
  });
  if (!native) {
    listen(doc, 'click', (event) => {
      const url = eligibleLink(event, cfg);
      if (!url || exit.isLeaving()) {
        return;
      }
      if (exit.leave(url.href)) {
        event.preventDefault();
      }
    });
  } else if (window.navigation && typeof window.navigation.addEventListener === 'function') {
    listen(window.navigation, 'navigate', (event) => {
      skipNative = shouldSkipNativeTransition(event, cfg);
    });
    listen(window, 'pageswap', (event) => {
      if (event.viewTransition && skipNative) {
        event.viewTransition.skipTransition();
      }
      skipNative = false;
    });
  }
  listen(window, 'pagehide', () => exit.reset(true));
  listen(window, 'pageshow', (event) => {
    exit.reset(true);
    if (event.persisted && motion.lenis && typeof motion.lenis.resize === 'function') {
      motion.lenis.resize();
    }
  });
  listen(window, 'popstate', () => exit.reset(true));
  const markScrolled = () => { userScrolled = true; };
  ['wheel', 'touchstart', 'keydown'].forEach((name) => listen(window, name, markScrolled, { passive: true, once: true }));
  if (cfg.hashRealign && location.hash && doc.readyState !== 'complete') {
    listen(window, 'load', () => {
      setTimeout(() => {
        const target = hashTarget(location.hash);
        if (target && !userScrolled) {
          scrollToTarget(target, true);
        }
      }, cfg.settleMs);
    }, { once: true });
  }
  const offDevice = motion.on('device', (info) => {
    if (info.device !== 'desktop') {
      destroy();
    }
  });
  function destroy() {
    offDevice();
    cleanups.splice(0).forEach((fn) => fn());
    exit.reset(true);
    root.classList.remove('sf-transitions-native', 'sf-transitions-veil');
  }
  return { destroy, native, veil };
}
