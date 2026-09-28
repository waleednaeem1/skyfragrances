function clamp(v, lo, hi) {
  return v < lo ? lo : v > hi ? hi : v;
}

function groundTooBright(root, max) {
  const img = root.querySelector('.hero__img');
  if (!img) {
    return false;
  }
  return parseFloat(getComputedStyle(img).opacity || '1') > max;
}

export default function init(root, SF) {
  const m = SF.motion;
  const cfg = m.config.hero || {};
  const plates = cfg.plates || {};
  const frame = cfg.frame || { objectUnits: 0.9 };
  const kill = (m.config.device && m.config.device.frameKill) || { frames: 120, meanMs: 20, strikes: 2 };
  const glow = root.querySelector('.hero__glow');
  const object = root.querySelector('.hero__object');
  if (!glow) {
    return null;
  }
  const status = { state: 'plates', reason: '', frame: null, triangles: 0, plates: [] };
  m.hero = status;
  let visible = 1;
  let scene = null;
  let destroyed = false;

  function addPlate(letter, desktop) {
    const src = glow.getAttribute('data-plate-' + letter + (desktop ? '-desktop' : ''));
    if (!src || status.plates.indexOf(letter) !== -1) {
      return;
    }
    status.plates.push(letter);
    const img = new Image();
    img.decoding = 'async';
    const place = () => {
      if (destroyed || !img.naturalWidth || glow.querySelector('.hero__plate--' + letter)) {
        return;
      }
      const plate = document.createElement('canvas');
      plate.className = 'hero__plate hero__plate--' + letter + ' sf-plate';
      plate.width = img.naturalWidth;
      plate.height = img.naturalHeight;
      const ctx = plate.getContext('2d');
      if (!ctx) {
        return;
      }
      ctx.drawImage(img, 0, 0);
      glow.appendChild(plate);
      requestAnimationFrame(() => requestAnimationFrame(() => plate.classList.add('is-ready')));
    };
    img.onload = () => {
      if (typeof img.decode === 'function') {
        img.decode().then(place, place);
      } else {
        place();
      }
    };
    img.src = src;
  }

  function fallback(reason) {
    status.state = 'plates';
    status.reason = reason;
    root.classList.remove('is-gl', 'is-gl-settled');
    if (m.device === 'desktop' && reason !== 'destroy' && reason !== 'pagehide') {
      addPlate('a', true);
      addPlate('b', true);
    }
    m.emit('hero', { state: 'plates', reason });
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      visible = entry.isIntersecting ? entry.intersectionRatio : 0;
      root.classList.toggle('is-offscreen', visible < (plates.pauseBelow || 0.1));
      if (scene) {
        scene.visibility(visible);
      }
    });
  }, { threshold: [0, cfg.pauseBelow || 0.05, plates.pauseBelow || 0.1, cfg.inViewStart || 0.5] });
  io.observe(root);

  if (m.device === 'mobile') {
    addPlate('a', false);
    if (plates.mobileHigh) {
      if (m.mobileHigh) {
        addPlate('b', false);
      } else {
        m.on('device', (snap) => {
          if (snap.mobileHigh && snap.device === 'mobile' && !destroyed) {
            addPlate('b', false);
          }
        });
      }
    }
  }

  function startScene(mod) {
    const canvas = document.createElement('canvas');
    canvas.className = 'hero__ribbons';
    canvas.setAttribute('aria-hidden', 'true');
    glow.appendChild(canvas);
    let renderer = null;
    try {
      renderer = mod.default(canvas, cfg, {});
    } catch (error) {
      renderer = null;
    }
    if (!renderer) {
      canvas.remove();
      fallback('no-webgl2');
      return;
    }
    const pointer = cfg.pointer || { rotY: 0.05, rotX: 0.03, baseX: 0.12, lerp: 0.06 };
    const scroll = cfg.scroll || { rotX: 0.15, y: 0.6 };
    const dpr = Math.min(window.devicePixelRatio || 1, cfg.dprMax || 1.5);
    const target = { rotX: pointer.baseX, rotY: 0 };
    const cur = { rotX: pointer.baseX, rotY: 0 };
    let heroTop = 0;
    let heroH = 1;
    let running = false;
    let raf = 0;
    let last = 0;
    let frames = 0;
    let acc = 0;
    let strikes = 0;
    let live = false;
    let settleTimer = 0;
    let resizeRaf = 0;
    let ended = false;

    function layout() {
      const heroRect = root.getBoundingClientRect();
      const glowRect = glow.getBoundingClientRect();
      const objRect = object ? object.getBoundingClientRect() : glowRect;
      heroTop = heroRect.top + window.scrollY;
      heroH = Math.max(1, heroRect.height);
      canvas.style.left = heroRect.left - glowRect.left + 'px';
      canvas.style.top = heroRect.top - glowRect.top + 'px';
      canvas.style.width = heroRect.width + 'px';
      canvas.style.height = heroRect.height + 'px';
      renderer.resize(heroRect.width, heroRect.height, dpr);
      renderer.setFrame({
        unitPx: (objRect.height || 100) / (frame.objectUnits || 0.9),
        originX: objRect.left + objRect.width / 2 - heroRect.left,
        originY: objRect.top + objRect.height / 2 - heroRect.top
      });
    }

    function tick(now) {
      if (!running) {
        return;
      }
      raf = requestAnimationFrame(tick);
      const dt = last ? now - last : 16.7;
      last = now;
      acc += dt;
      frames += 1;
      if (frames >= kill.frames) {
        const mean = acc / frames;
        status.frame = { mean: Math.round(mean * 100) / 100 };
        strikes = mean > kill.meanMs ? strikes + 1 : 0;
        frames = 0;
        acc = 0;
        if (strikes >= kill.strikes) {
          end('budget');
          return;
        }
      }
      cur.rotX += (target.rotX - cur.rotX) * pointer.lerp;
      cur.rotY += (target.rotY - cur.rotY) * pointer.lerp;
      const p = clamp((window.scrollY - heroTop) / heroH, 0, 1);
      const ok = renderer.render(now / 1000, { rotX: cur.rotX + p * scroll.rotX, rotY: cur.rotY, posY: -p * scroll.y, fade: 1 - p });
      if (!ok) {
        end('context-lost');
        return;
      }
      if (!live) {
        live = true;
        canvas.classList.add('is-live');
        root.classList.add('is-gl');
        status.state = 'gl';
        status.triangles = renderer.triangles();
        settleTimer = setTimeout(() => root.classList.add('is-gl-settled'), cfg.glFadeMs || 900);
        m.emit('hero', { state: 'gl' });
      }
    }

    function start() {
      if (running || ended) {
        return;
      }
      running = true;
      last = 0;
      raf = requestAnimationFrame(tick);
    }

    function stop() {
      running = false;
      cancelAnimationFrame(raf);
    }

    function onPointer(event) {
      const mx = (event.clientX / window.innerWidth) * 2 - 1;
      const my = (event.clientY / window.innerHeight) * 2 - 1;
      target.rotY = mx * pointer.rotY;
      target.rotX = pointer.baseX + my * pointer.rotX;
    }

    function onResize() {
      cancelAnimationFrame(resizeRaf);
      resizeRaf = requestAnimationFrame(layout);
    }

    function onVisibility() {
      if (document.hidden) {
        stop();
      } else if (visible >= (cfg.pauseBelow || 0.05)) {
        start();
      }
    }

    function onPageHide() {
      end('pagehide');
    }

    const offRefresh = m.on('refresh', onResize);

    function end(reason) {
      if (ended) {
        return;
      }
      ended = true;
      stop();
      clearTimeout(settleTimer);
      window.removeEventListener('pointermove', onPointer);
      window.removeEventListener('resize', onResize);
      document.removeEventListener('visibilitychange', onVisibility);
      window.removeEventListener('pagehide', onPageHide);
      offRefresh();
      renderer.dispose();
      canvas.remove();
      scene = null;
      fallback(reason);
    }

    renderer.onLost(() => end('context-lost'));
    window.addEventListener('pointermove', onPointer, { passive: true });
    window.addEventListener('resize', onResize, { passive: true });
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('pagehide', onPageHide);
    layout();
    scene = {
      visibility(ratio) {
        if (ratio < (cfg.pauseBelow || 0.05)) {
          stop();
        } else if (ratio >= (cfg.inViewStart || 0.5) || live) {
          start();
        }
      },
      kill: end
    };
    if (visible >= (cfg.inViewStart || 0.5) && !document.hidden) {
      start();
    }
  }

  if (m.device === 'desktop') {
    let blocked = '';
    if (cfg.webgl !== true) {
      blocked = 'flag';
    } else if (m.gpu === 'weak' || m.slow) {
      blocked = 'device';
    } else if (groundTooBright(root, cfg.groundMaxOpacity || 0.6)) {
      blocked = 'ground';
    }
    if (blocked) {
      fallback(blocked);
    } else {
      import(new URL('./ribbons-gl.js' + (m.version ? '?v=' + m.version : ''), import.meta.url).href).then((mod) => {
        if (!destroyed) {
          startScene(mod);
        }
      }).catch(() => fallback('import'));
    }
  }

  m.on('device', (snap) => {
    if (snap.device !== 'desktop' && scene) {
      scene.kill('device');
    }
  });

  return {
    destroy() {
      destroyed = true;
      io.disconnect();
      if (scene) {
        scene.kill('destroy');
      }
    }
  };
}
