const doc = document;
const html = doc.documentElement;

function qsa(selector, scope) {
  return selector ? Array.from((scope || doc).querySelectorAll(selector)) : [];
}

function within(el, selector) {
  return !!(selector && el && el.closest && el.closest(selector));
}

function make(tag, className) {
  const el = doc.createElement(tag);
  if (className) {
    el.className = className;
  }
  return el;
}

const easings = {
  'expo.out': (t) => (t >= 1 ? 1 : 1 - Math.pow(2, -10 * t)),
  'power3.out': (t) => 1 - Math.pow(1 - t, 3),
  'power3.in': (t) => t * t * t
};

function tween(gsap, ms, ease, onUpdate, onComplete) {
  if (gsap) {
    const state = { p: 0 };
    return gsap.to(state, { p: 1, duration: ms / 1000, ease, onUpdate: () => onUpdate(state.p), onComplete });
  }
  const fn = easings[ease] || easings['expo.out'];
  let start = 0;
  let paint = true;
  let frame = requestAnimationFrame(function step(now) {
    start = start || now;
    const t = Math.min(1, (now - start) / ms);
    if (paint || t >= 1) {
      onUpdate(fn(t));
    }
    paint = !paint;
    if (t < 1) {
      frame = requestAnimationFrame(step);
    } else if (onComplete) {
      onComplete();
    }
  });
  return { kill: () => cancelAnimationFrame(frame) };
}

function formatNumber(value, grouped) {
  const text = String(Math.round(value));
  return grouped ? text.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : text;
}

function whenRevealed(el, settleMs, fn) {
  const host = el.closest('.sf-reveal');
  if (!host || host.classList.contains('is-visible') || !html.classList.contains('js')) {
    fn();
    return () => {};
  }
  let done = false;
  const finish = () => {
    if (!done) {
      done = true;
      observer.disconnect();
      clearTimeout(timer);
      fn();
    }
  };
  const observer = new MutationObserver(() => {
    if (host.classList.contains('is-visible')) {
      finish();
    }
  });
  observer.observe(host, { attributes: true, attributeFilter: ['class'] });
  const timer = setTimeout(finish, settleMs);
  return () => {
    done = true;
    observer.disconnect();
    clearTimeout(timer);
  };
}

function tickNode(el, tick, gsap) {
  const text = el.textContent;
  const numbers = text.match(/\d[\d,]*/g);
  if (!numbers || numbers.length !== 1) {
    return null;
  }
  const raw = numbers[0];
  const value = parseInt(raw.replace(/,/g, ''), 10);
  if (!(value > 0) || el.children.length) {
    return null;
  }
  const at = text.indexOf(raw);
  const before = text.slice(0, at);
  const after = text.slice(at + raw.length);
  const grouped = raw.indexOf(',') !== -1;
  const twin = make('span', 'sf-tick');
  twin.setAttribute('aria-hidden', 'true');
  const num = make('span', 'sf-tick__num');
  num.style.minWidth = raw.length + 'ch';
  num.textContent = formatNumber(0, grouped);
  twin.append(before, num, after);
  const real = make('span', 'sf-tick__real');
  real.textContent = text;
  el.setAttribute('data-tick-done', '');
  el.textContent = '';
  el.append(real, twin);
  const restore = () => {
    if (el.contains(twin)) {
      el.textContent = text;
    }
  };
  const anim = tween(gsap, tick.ms, tick.ease, (p) => {
    num.textContent = formatNumber(value * p, grouped);
  }, restore);
  return () => {
    anim.kill();
    restore();
  };
}

function initTick(tick, motion) {
  const all = qsa(tick.targets).filter((el) => !within(el, tick.never) && !el.hasAttribute('data-tick-done'));
  const nodes = all.filter((el) => !all.some((other) => other !== el && other.contains(el)));
  if (!nodes.length || typeof IntersectionObserver === 'undefined') {
    return () => {};
  }
  const cleanups = [];
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }
      io.unobserve(entry.target);
      cleanups.push(whenRevealed(entry.target, tick.settleMs || 4000, () => {
        const stop = tickNode(entry.target, tick, motion.gsap);
        if (stop) {
          cleanups.push(stop);
        }
      }));
    });
  }, { rootMargin: (motion.config.reveal && motion.config.reveal.rootMargin) || '0px 0px -12% 0px', threshold: 0 });
  nodes.forEach((el) => io.observe(el));
  return () => {
    io.disconnect();
    cleanups.forEach((fn) => fn());
  };
}

function centerOf(el) {
  const rect = el.getBoundingClientRect();
  return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
}

function drawerCount() {
  return doc.querySelector('.js-cart-drawer .drawer__head .js-cart-count');
}

function bumpDrawerCount(SF, delayMs) {
  setTimeout(() => {
    const count = drawerCount();
    if (count && SF && typeof SF.bump === 'function') {
      SF.bump(count, 'is-bumped');
    }
  }, delayMs || 0);
}

function spray(origin, count, micro) {
  const spread = micro.burstSpread || { minPx: 26, maxPx: 68, arcDeg: 150, delayMaxMs: 90, sizeMinPx: 4, sizeMaxPx: 8 };
  const layer = make('div', 'sf-burst');
  layer.setAttribute('aria-hidden', 'true');
  layer.style.left = origin.x + 'px';
  layer.style.top = origin.y + 'px';
  const arc = (spread.arcDeg * Math.PI) / 180;
  for (let i = 0; i < count; i++) {
    const angle = -Math.PI / 2 + (i / Math.max(1, count - 1) - 0.5) * arc + (Math.random() - 0.5) * 0.25;
    const distance = spread.minPx + Math.random() * (spread.maxPx - spread.minPx);
    const dot = make('i', 'sf-spray');
    dot.style.setProperty('--dx', (Math.cos(angle) * distance).toFixed(1) + 'px');
    dot.style.setProperty('--dy', (Math.sin(angle) * distance).toFixed(1) + 'px');
    dot.style.setProperty('--d', Math.round(Math.random() * spread.delayMaxMs) + 'ms');
    dot.style.setProperty('--sz', (spread.sizeMinPx + Math.random() * (spread.sizeMaxPx - spread.sizeMinPx)).toFixed(1) + 'px');
    layer.appendChild(dot);
  }
  doc.body.appendChild(layer);
  setTimeout(() => layer.remove(), micro.burst.ms + spread.delayMaxMs + 120);
}

function thumbFor(detail) {
  if (typeof detail.thumb === 'string') {
    return detail.thumb;
  }
  if (detail.thumb && detail.thumb.nodeType === 1) {
    return detail.thumb.currentSrc || detail.thumb.src || '';
  }
  const scope = detail.form || detail.button;
  const card = scope && scope.closest ? scope.closest('.product-card, .quiz-card, .split') : null;
  const img = (card && card.querySelector('.product-card__img')) ||
    doc.querySelector('[data-gallery-main] .gallery__img, .gallery__img, .sticky-bar__thumb, .product-card__img');
  return img ? img.currentSrc || img.src || '' : '';
}

function panelShift(panel) {
  const transform = getComputedStyle(panel).transform;
  const match = transform && transform !== 'none' ? transform.match(/matrix\(([^)]+)\)/) : null;
  if (!match) {
    return { x: 0, y: 0 };
  }
  const parts = match[1].split(',').map(parseFloat);
  return { x: parts[4] || 0, y: parts[5] || 0 };
}

function fly(origin, detail, micro, motion, SF) {
  const gsap = motion.gsap;
  const panel = doc.querySelector('.js-cart-drawer');
  const count = drawerCount();
  if (!gsap || !panel || !count) {
    bumpDrawerCount(SF, motion.config.duration.base);
    return;
  }
  const src = thumbFor(detail);
  const clone = make(src ? 'img' : 'span', 'sf-fly');
  clone.setAttribute('aria-hidden', 'true');
  if (src) {
    clone.src = src;
    clone.alt = '';
  }
  clone.style.width = micro.flight.size + 'px';
  clone.style.height = micro.flight.size + 'px';
  clone.style.left = origin.x - micro.flight.size / 2 + 'px';
  clone.style.top = origin.y - micro.flight.size / 2 + 'px';
  doc.body.appendChild(clone);
  requestAnimationFrame(() => {
    if (panel.hidden) {
      clone.remove();
      bumpDrawerCount(SF, 0);
      return;
    }
    const rect = count.getBoundingClientRect();
    const shift = panelShift(panel);
    const target = { x: rect.left + rect.width / 2 - shift.x, y: rect.top + rect.height / 2 - shift.y };
    gsap.fromTo(clone, { x: 0, y: 0, scale: 1, opacity: 1 }, {
      x: target.x - origin.x,
      y: target.y - origin.y,
      scale: 0.35,
      opacity: 0.85,
      duration: micro.flight.ms / 1000,
      ease: micro.flight.ease,
      onComplete: () => {
        clone.remove();
        bumpDrawerCount(SF, 0);
      }
    });
  });
}

function initCartAdded(micro, motion, SF, styled) {
  const handler = (event) => {
    const detail = event.detail || {};
    const button = detail.button;
    if (!button || !doc.contains(button) || (detail.form && detail.form.hasAttribute('data-cart-reload'))) {
      return;
    }
    const desktop = motion.device === 'desktop';
    const origin = centerOf(button);
    const sprayCount = motion.device === 'low' ? micro.burst.low || 0 : desktop || motion.mobileHigh ? micro.burst.desktop : micro.burst.mobile;
    if (styled && sprayCount > 0) {
      spray(origin, sprayCount, micro);
    }
    if (desktop && styled && motion.allows(motion.flags.cartFly)) {
      fly(origin, detail, micro, motion, SF);
    } else {
      bumpDrawerCount(SF, motion.config.duration.base);
    }
  };
  doc.addEventListener('sf:cart-added', handler);
  return () => doc.removeEventListener('sf:cart-added', handler);
}

function initMagnetic(mag, motion, SF, staleMs) {
  const gsap = motion.gsap;
  const targets = qsa(mag.targets).filter((el) => !within(el, mag.never));
  if (!gsap || !targets.length) {
    return () => {};
  }
  const items = targets.map((el) => ({
    el,
    xTo: gsap.quickTo(el, 'x', { duration: mag.duration, ease: mag.ease }),
    yTo: gsap.quickTo(el, 'y', { duration: mag.duration, ease: mag.ease }),
    rect: null,
    active: false
  }));
  let stale = true;
  let staleTimer = 0;
  let frame = 0;
  let pointer = null;

  function markStale() {
    stale = true;
  }
  function measure() {
    const sx = window.scrollX;
    const sy = window.scrollY;
    items.forEach((item) => {
      const r = item.el.getBoundingClientRect();
      item.rect = r.width && r.height ? { cx: r.left + r.width / 2 + sx, cy: r.top + r.height / 2 + sy, rx: (r.width / 2) * mag.radius, ry: (r.height / 2) * mag.radius } : null;
    });
    stale = false;
    clearTimeout(staleTimer);
    staleTimer = setTimeout(markStale, staleMs);
  }
  function release(item) {
    if (item.active) {
      item.active = false;
      item.xTo(0);
      item.yTo(0);
    }
  }
  function update() {
    frame = 0;
    if (!pointer) {
      return;
    }
    if (stale) {
      measure();
    }
    const blocked = SF && typeof SF.dialogIsOpen === 'function' && SF.dialogIsOpen();
    items.forEach((item) => {
      const rect = item.rect;
      if (!rect || blocked || item.el.classList.contains('is-loading')) {
        release(item);
        return;
      }
      const dx = (pointer.x - rect.cx) / rect.rx;
      const dy = (pointer.y - rect.cy) / rect.ry;
      if (dx * dx + dy * dy <= 1) {
        item.active = true;
        item.xTo(dx * mag.maxPx);
        item.yTo(dy * mag.maxPx);
      } else {
        release(item);
      }
    });
  }
  function onMove(event) {
    if (event.pointerType && event.pointerType !== 'mouse') {
      return;
    }
    pointer = { x: event.pageX, y: event.pageY };
    if (!frame) {
      frame = requestAnimationFrame(update);
    }
  }
  function onLeave() {
    pointer = null;
    items.forEach(release);
  }
  const offRefresh = motion.on('refresh', markStale);
  doc.addEventListener('pointermove', onMove, { passive: true });
  doc.addEventListener('pointerleave', onLeave);
  window.addEventListener('scroll', markStale, { passive: true });
  window.addEventListener('resize', markStale, { passive: true });
  return () => {
    offRefresh();
    doc.removeEventListener('pointermove', onMove);
    doc.removeEventListener('pointerleave', onLeave);
    window.removeEventListener('scroll', markStale);
    window.removeEventListener('resize', markStale);
    clearTimeout(staleTimer);
    cancelAnimationFrame(frame);
    gsap.set(targets, { clearProps: 'transform' });
  };
}

function initCursor(cur, motion, SF) {
  const gsap = motion.gsap;
  if (!gsap || doc.querySelector('.sf-cursor')) {
    return () => {};
  }
  const ring = make('div', 'sf-cursor');
  ring.setAttribute('aria-hidden', 'true');
  ring.appendChild(make('i', 'sf-cursor__ring'));
  doc.body.appendChild(ring);
  html.classList.add('sf-cursor-on');
  const xTo = gsap.quickTo(ring, 'x', { duration: cur.follow, ease: motion.config.ease.gsap.silk });
  const yTo = gsap.quickTo(ring, 'y', { duration: cur.follow, ease: motion.config.ease.gsap.silk });
  let shown = false;

  function onMove(event) {
    if (event.pointerType && event.pointerType !== 'mouse') {
      return;
    }
    if (!shown) {
      shown = true;
      gsap.set(ring, { x: event.clientX, y: event.clientY });
      ring.classList.add('is-visible');
    }
    xTo(event.clientX);
    yTo(event.clientY);
    const target = event.target;
    const native = within(target, cur.nativeInside);
    ring.classList.toggle('is-native', native);
    ring.classList.toggle('is-grown', !native && within(target, cur.targets));
  }
  function onOut(event) {
    if (!event.relatedTarget) {
      ring.classList.remove('is-visible');
      shown = false;
    }
  }
  function onDown() {
    ring.classList.add('is-down');
  }
  function onUp() {
    ring.classList.remove('is-down');
  }
  function onDialogOpen() {
    ring.classList.add('is-hidden');
  }
  function onDialogClose() {
    if (!(SF && typeof SF.dialogIsOpen === 'function' && SF.dialogIsOpen())) {
      ring.classList.remove('is-hidden');
    }
  }
  doc.addEventListener('pointermove', onMove, { passive: true });
  doc.addEventListener('mouseout', onOut);
  doc.addEventListener('pointerdown', onDown, { passive: true });
  doc.addEventListener('pointerup', onUp, { passive: true });
  doc.addEventListener('sf:dialog-open', onDialogOpen);
  doc.addEventListener('sf:dialog-close', onDialogClose);
  if (SF && typeof SF.dialogIsOpen === 'function' && SF.dialogIsOpen()) {
    onDialogOpen();
  }
  return () => {
    doc.removeEventListener('pointermove', onMove);
    doc.removeEventListener('mouseout', onOut);
    doc.removeEventListener('pointerdown', onDown);
    doc.removeEventListener('pointerup', onUp);
    doc.removeEventListener('sf:dialog-open', onDialogOpen);
    doc.removeEventListener('sf:dialog-close', onDialogClose);
    html.classList.remove('sf-cursor-on');
    ring.remove();
  };
}

export default function init(root, SF) {
  const motion = SF && SF.motion;
  if (!motion || motion.reduced || motion.commercePage) {
    return { destroy() {} };
  }
  const micro = motion.config.micro || {};
  const styled = !!doc.getElementById('sf-motion-css');
  let cleanups = [];
  let destroyed = false;

  function teardown() {
    cleanups.forEach((fn) => fn());
    cleanups = [];
  }
  function desktopExtras() {
    if (destroyed || motion.device !== 'desktop' || !motion.gsap || !styled) {
      return;
    }
    if (micro.magnetic && motion.allows(motion.flags.magnetic)) {
      cleanups.push(initMagnetic(micro.magnetic, motion, SF, micro.magneticStaleMs || 1500));
    }
    if (micro.cursor && motion.allows(motion.flags.cursor)) {
      cleanups.push(initCursor(micro.cursor, motion, SF));
    }
  }
  function setup() {
    if (destroyed || motion.reduced) {
      return;
    }
    if (micro.tick && motion.allows(motion.flags.tick)) {
      cleanups.push(initTick(micro.tick, motion));
    }
    if (micro.burst && micro.flight) {
      cleanups.push(initCartAdded(micro, motion, SF, styled));
    }
    if (motion.device === 'desktop') {
      if (motion.gsap) {
        desktopExtras();
      } else {
        motion.ensureGsap().then(desktopExtras);
      }
    }
  }
  const offDevice = motion.on('device', () => {
    teardown();
    setup();
  });
  setup();
  return {
    destroy() {
      destroyed = true;
      offDevice();
      teardown();
    }
  };
}
