const NOTE_KEYS = ['top', 'heart', 'base'];
const A_LAYERS = '.story__glow--a, .story__halo--a';
const B_LAYERS = '.story__glow--b, .story__halo--b';

function hexToRgb(hex) {
  const clean = String(hex || '').replace('#', '');
  const full = clean.length === 3 ? clean.split('').map((c) => c + c).join('') : clean;
  if (!/^[0-9a-f]{6}$/i.test(full)) {
    return '';
  }
  return [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16)).join(' ');
}

function familyHues(cfg, root) {
  const table = (cfg.hues && cfg.hues.families) || {};
  const family = String(root.getAttribute('data-story-family') || '').toLowerCase();
  let picked = table[family];
  if (!picked) {
    const hit = ((cfg.hues && cfg.hues.match) || []).find((pair) => family.indexOf(pair[0]) !== -1);
    picked = hit ? table[hit[1]] : null;
  }
  const base = Object.assign({}, table.default || {}, picked || {});
  NOTE_KEYS.forEach((key) => {
    const override = root.getAttribute('data-motion-hue-' + key);
    if (override) {
      base[key] = override;
    }
  });
  return base;
}

function tierKey(tier) {
  return NOTE_KEYS.find((key) => tier.classList.contains('pyramid__tier--' + key)) || 'top';
}

function paint(nodes, hex) {
  const rgb = hexToRgb(hex);
  if (!rgb) {
    return;
  }
  nodes.forEach((node) => node.style.setProperty('--sf-story-rgb', rgb));
}

function switchLayers(on, off) {
  on.forEach((node) => node.classList.add('is-on'));
  off.forEach((node) => node.classList.remove('is-on'));
}

function desktop(root, tiers, hues, cfg, motion) {
  const { gsap, ScrollTrigger } = motion;
  const pyramid = root.querySelector('.pyramid');
  const tilt = root.querySelector('.story__tilt');
  const layersA = Array.from(root.querySelectorAll(A_LAYERS));
  const layersB = Array.from(root.querySelectorAll(B_LAYERS));
  const seen = new Set();
  let showingA = true;
  let current = 'top';
  const preRevealed = !!pyramid && pyramid.classList.contains('is-visible');
  root.classList.add('story--gsap');
  if (pyramid) {
    pyramid.classList.add('is-visible');
  }
  paint(layersA, hues.top);
  switchLayers(layersA, layersB);

  function reveal(tier, instant) {
    if (seen.has(tier)) {
      return;
    }
    seen.add(tier);
    const targets = tier.querySelectorAll('.pyramid__label, .pyramid__note');
    tier.classList.add('is-seen');
    if (instant || !targets.length) {
      return;
    }
    gsap.fromTo(targets, { y: cfg.notes.y, autoAlpha: 0 }, {
      y: 0,
      autoAlpha: 1,
      duration: cfg.notes.ms / 1000,
      stagger: cfg.notes.staggerMs / 1000,
      ease: cfg.notes.ease,
      overwrite: true,
      clearProps: 'opacity,visibility,transform'
    });
  }

  function setNote(key) {
    if (key === current) {
      return;
    }
    current = key;
    root.setAttribute('data-note', key);
    const next = showingA ? layersB : layersA;
    const prev = showingA ? layersA : layersB;
    if (next.length) {
      paint(next, hues[key]);
      switchLayers(next, prev);
      showingA = !showingA;
    } else {
      paint(prev, hues[key]);
    }
    const pose = cfg.tilt.states[key];
    if (tilt && cfg.tilt.enabled && pose) {
      gsap.to(tilt, { rotateY: pose.rotY, rotateX: pose.rotX, scale: pose.scale, duration: cfg.tilt.ms / 1000, ease: cfg.tilt.ease, overwrite: true });
    }
  }

  if (preRevealed) {
    tiers.forEach((tier) => reveal(tier, true));
  }
  const triggers = tiers.map((tier, index) => ScrollTrigger.create({
    trigger: tier,
    start: cfg.triggers.start,
    end: cfg.triggers.end,
    onToggle(self) {
      if (!self.isActive) {
        return;
      }
      tiers.slice(0, index).forEach((earlier) => reveal(earlier, true));
      reveal(tier);
      setNote(tierKey(tier));
    }
  }));
  triggers.push(ScrollTrigger.create({
    trigger: root,
    start: 'top bottom',
    end: 'bottom top',
    onToggle(self) {
      root.classList.toggle('is-active', self.isActive);
    },
    onLeave() {
      tiers.forEach((tier) => reveal(tier, true));
    }
  }));
  return {
    destroy() {
      triggers.forEach((trigger) => trigger.kill());
      if (tilt) {
        gsap.killTweensOf(tilt);
        gsap.set(tilt, { clearProps: 'all' });
      }
      tiers.forEach((tier) => {
        gsap.killTweensOf(tier.querySelectorAll('.pyramid__label, .pyramid__note'));
        tier.classList.add('is-seen');
      });
      root.classList.remove('story--gsap', 'is-active');
    }
  };
}

function lite(root, tiers, hues, cfg, motion) {
  const pyramid = root.querySelector('.pyramid');
  const layersA = Array.from(root.querySelectorAll(A_LAYERS));
  let current = 'top';
  let timer = 0;
  paint(layersA, hues.top);
  switchLayers(layersA, Array.from(root.querySelectorAll(B_LAYERS)));
  if (pyramid && !pyramid.classList.contains('is-visible')) {
    pyramid.classList.add('sf-reveal--stagger');
  }
  if (motion.device === 'low' || typeof window.IntersectionObserver === 'undefined') {
    return { destroy() {} };
  }

  function swap(key) {
    if (key === current) {
      return;
    }
    current = key;
    root.setAttribute('data-note', key);
    layersA.forEach((node) => node.classList.add('is-dip'));
    clearTimeout(timer);
    timer = setTimeout(() => {
      paint(layersA, hues[key]);
      layersA.forEach((node) => node.classList.remove('is-dip'));
    }, cfg.hueMobileMs);
  }

  const inBand = new Set();
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        inBand.add(entry.target);
      } else {
        inBand.delete(entry.target);
      }
    });
    const topmost = tiers.find((tier) => inBand.has(tier));
    if (topmost) {
      swap(tierKey(topmost));
    }
  }, { rootMargin: cfg.mobile.rootMargin, threshold: cfg.mobile.threshold });
  tiers.forEach((tier) => io.observe(tier));
  return {
    destroy() {
      io.disconnect();
      clearTimeout(timer);
      layersA.forEach((node) => node.classList.remove('is-dip'));
    }
  };
}

export default function init(root, SF) {
  const motion = SF && SF.motion;
  if (!motion || !root) {
    return null;
  }
  const cfg = motion.config.story;
  const hues = familyHues(cfg, root);
  const tiers = Array.from(root.querySelectorAll('.pyramid__tier'));
  let instance = null;

  function start() {
    if (motion.reduced || !motion.allows(motion.flags.story)) {
      return;
    }
    instance = motion.device === 'desktop' && motion.gsap && motion.ScrollTrigger
      ? desktop(root, tiers, hues, cfg, motion)
      : lite(root, tiers, hues, cfg, motion);
  }

  function stop() {
    if (instance) {
      instance.destroy();
      instance = null;
    }
  }

  const offDevice = motion.on('device', () => {
    stop();
    if (motion.device === 'desktop' && !motion.gsap) {
      motion.ensureGsap().then(() => {
        if (!instance) {
          start();
        }
      });
      return;
    }
    start();
  });
  start();
  return {
    destroy() {
      offDevice();
      stop();
    }
  };
}
