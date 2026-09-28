const CARD_SELECTOR = '[data-motion-card], .collection-card';
const SWEEP_ANIMATION = 'sf-card-sweep';

export default function init(root, SF) {
  const motion = SF && SF.motion;
  if (!root || !motion) {
    return null;
  }
  const raw = motion.config.cards || {};
  const cfg = {
    fan: Object.assign({ rotate: 16, rise: 24, scrub: 0.6, start: 'top 85%', end: 'top 35%', replay: false, origin: '50% 110%', settleMs: 1100, minSettleMs: 260, staggerMs: 60, failsafeMs: 6000, failsafeSettleMs: 900, singleGroupRows: 2 }, raw.fan || {}),
    hover: Object.assign({ tilt: 6, lift: 8, sweepMs: 700, followMs: 160, returnMs: 600 }, raw.hover || {})
  };
  const state = { root, motion, cfg, cleanups: [], hover: null, tweens: [] };
  if (motion.device === 'desktop') {
    startDesktop(state);
  } else {
    markSpread(root);
  }
  state.cleanups.push(motion.on('device', (snapshot) => onDevice(state, snapshot)));
  return {
    destroy() {
      teardown(state);
    }
  };
}

function directChildren(root) {
  return Array.from(root.children);
}

function markSpread(root) {
  root.setAttribute('data-cards-state', 'spread');
  root.classList.remove('is-fanning', 'is-settling');
}

function readPose(el) {
  const style = getComputedStyle(el);
  const number = (name, fallback) => {
    const value = parseFloat(style.getPropertyValue(name));
    return Number.isFinite(value) ? value : fallback;
  };
  return { r: number('--sf-fan-r', 0), x: number('--sf-fan-x', 0), ry: number('--sf-fan-ry', 1) };
}

function groupRows(children, singleGroupRows) {
  const rows = new Map();
  children.forEach((el) => {
    const key = Math.round(el.offsetTop / 8);
    if (!rows.has(key)) {
      rows.set(key, []);
    }
    rows.get(key).push(el);
  });
  const ordered = Array.from(rows.keys()).sort((a, b) => a - b).map((key) => rows.get(key));
  return ordered.length <= singleGroupRows ? [children] : ordered;
}

function inViewport(el) {
  const rect = el.getBoundingClientRect();
  return rect.bottom > 0 && rect.top < window.innerHeight;
}

function startDesktop(state) {
  const { root, motion, cfg } = state;
  const children = directChildren(root);
  const gsap = motion.gsap;
  root.classList.add('is-visible');
  if (!children.length || !gsap || !motion.ScrollTrigger) {
    root.classList.add('is-settling');
    markSpread(root);
    enableHover(state);
    return;
  }
  const fan = cfg.fan;
  const settled = performance.now() > fan.failsafeMs;
  if (settled && inViewport(root)) {
    markSpread(root);
    enableHover(state);
    return;
  }
  gsap.killTweensOf(children);
  gsap.set(children, { clearProps: 'opacity,transform' });
  const poses = children.map(readPose);
  const groups = groupRows(children, fan.singleGroupRows);
  root.setAttribute('data-cards-state', 'live');
  root.classList.add('is-fanning');
  children.forEach((el, index) => {
    gsap.set(el, { xPercent: poses[index].x, y: poses[index].ry * fan.rise, rotation: poses[index].r * fan.rotate, transformOrigin: fan.origin });
  });
  let remaining = groups.length;
  const finish = (els) => {
    gsap.set(els, { clearProps: 'transform,transformOrigin' });
    remaining -= 1;
    if (remaining === 0) {
      markSpread(root);
      enableHover(state);
    }
  };
  const eases = (motion.config.ease && motion.config.ease.gsap) || {};
  groups.forEach((els) => {
    const trigger = groups.length === 1 ? root : els[0];
    const target = { xPercent: 0, y: 0, rotation: 0, overwrite: 'auto' };
    if (!fan.scrub) {
      const tween = gsap.to(els, Object.assign(target, {
        duration: fan.settleMs / 1000,
        ease: eases.out || 'expo.out',
        stagger: fan.staggerMs / 1000,
        scrollTrigger: { trigger, start: fan.start, once: true },
        onComplete() { finish(els); }
      }));
      state.tweens.push(tween);
      return;
    }
    let settling = false;
    const settle = (tween) => {
      if (settling) {
        return;
      }
      settling = true;
      releaseTrigger(tween);
      const settler = gsap.to(tween, {
        progress: 1,
        duration: Math.max(fan.minSettleMs, (1 - tween.progress()) * fan.settleMs) / 1000,
        ease: eases.out || 'expo.out',
        onComplete() {
          tween.kill();
          finish(els);
        }
      });
      state.tweens.push(settler);
    };
    const tween = gsap.to(els, Object.assign(target, {
      ease: eases.scrub || 'none',
      scrollTrigger: {
        trigger,
        start: fan.start,
        end: fan.end,
        scrub: fan.scrub,
        invalidateOnRefresh: false,
        onLeave(self) { settle(self.animation); },
        onUpdate(self) { if (self.progress >= 1) { settle(self.animation); } },
        onRefresh(self) { if (self.progress >= 1) { settle(self.animation); } }
      }
    }));
    state.tweens.push(tween);
    if (tween.scrollTrigger && tween.scrollTrigger.progress > 0) {
      settle(tween);
    }
  });
}

function releaseTrigger(tween) {
  const trigger = tween.scrollTrigger;
  if (!trigger) {
    return;
  }
  const scrub = typeof trigger.getTween === 'function' ? trigger.getTween() : null;
  if (scrub) {
    scrub.kill();
  }
  trigger.kill(false, true);
}

function enableHover(state) {
  const { root, cfg } = state;
  if (state.hover || state.motion.device !== 'desktop') {
    return;
  }
  const tilt = cfg.hover.tilt;
  const lift = cfg.hover.lift;
  const returnMs = cfg.hover.returnMs;
  let frame = 0;
  let pending = null;
  const cardFrom = (event) => {
    const card = event.target && event.target.closest ? event.target.closest(CARD_SELECTOR) : null;
    return card && root.contains(card) ? card : null;
  };
  const leaving = (event, card) => event.relatedTarget && card.contains(event.relatedTarget);
  const apply = () => {
    frame = 0;
    if (!pending) {
      return;
    }
    const { card, x, y } = pending;
    const rect = card.getBoundingClientRect();
    if (!rect.width || !rect.height) {
      return;
    }
    const dx = Math.max(-1, Math.min(1, ((x - rect.left) / rect.width - 0.5) * 2));
    const dy = Math.max(-1, Math.min(1, ((y - rect.top) / rect.height - 0.5) * 2));
    card.style.setProperty('--sf-tilt-y', (dx * tilt).toFixed(2) + 'deg');
    card.style.setProperty('--sf-tilt-x', (-dy * tilt).toFixed(2) + 'deg');
    card.style.setProperty('--sf-tilt-lift', -lift + 'px');
  };
  const onOver = (event) => {
    const card = cardFrom(event);
    if (!card || leaving(event, card) || card.classList.contains('is-tilting')) {
      return;
    }
    card.classList.remove('is-untilting');
    card.classList.add('is-tilting', 'is-sweeping');
  };
  const onMove = (event) => {
    const card = cardFrom(event);
    if (!card) {
      return;
    }
    pending = { card, x: event.clientX, y: event.clientY };
    if (!frame) {
      frame = requestAnimationFrame(apply);
    }
  };
  const onOut = (event) => {
    const card = cardFrom(event);
    if (!card || leaving(event, card)) {
      return;
    }
    card.classList.remove('is-tilting');
    card.classList.add('is-untilting');
    card.style.setProperty('--sf-tilt-x', '0deg');
    card.style.setProperty('--sf-tilt-y', '0deg');
    card.style.setProperty('--sf-tilt-lift', '0px');
    setTimeout(() => card.classList.remove('is-untilting'), returnMs + 60);
  };
  const onSweepEnd = (event) => {
    if (event.animationName === SWEEP_ANIMATION) {
      const card = cardFrom(event);
      if (card) {
        card.classList.remove('is-sweeping');
      }
    }
  };
  root.addEventListener('pointerover', onOver);
  root.addEventListener('pointermove', onMove, { passive: true });
  root.addEventListener('pointerout', onOut);
  root.addEventListener('animationend', onSweepEnd);
  state.hover = () => {
    root.removeEventListener('pointerover', onOver);
    root.removeEventListener('pointermove', onMove);
    root.removeEventListener('pointerout', onOut);
    root.removeEventListener('animationend', onSweepEnd);
    if (frame) {
      cancelAnimationFrame(frame);
    }
    root.querySelectorAll(CARD_SELECTOR).forEach((card) => {
      card.classList.remove('is-tilting', 'is-untilting', 'is-sweeping');
      ['--sf-tilt-x', '--sf-tilt-y', '--sf-tilt-lift'].forEach((name) => card.style.removeProperty(name));
    });
  };
}

function stopFan(state) {
  const { motion, root } = state;
  state.tweens.forEach((tween) => {
    if (tween.scrollTrigger) {
      tween.scrollTrigger.kill();
    }
    tween.kill();
  });
  state.tweens = [];
  if (motion.gsap) {
    motion.gsap.set(directChildren(root), { clearProps: 'transform,transformOrigin' });
  }
  markSpread(root);
}

function onDevice(state, snapshot) {
  if (snapshot.device === 'desktop') {
    markSpread(state.root);
    enableHover(state);
    return;
  }
  stopFan(state);
  if (state.hover) {
    state.hover();
    state.hover = null;
  }
}

function teardown(state) {
  stopFan(state);
  if (state.hover) {
    state.hover();
    state.hover = null;
  }
  state.cleanups.splice(0).forEach((fn) => fn());
}
