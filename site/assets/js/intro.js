(function () {
  'use strict';
  var doc = document;
  var root = doc.documentElement;
  var cfg = {};
  var mode = root.classList.contains('sf-intro-full') ? 'full' : root.classList.contains('sf-intro-calm') ? 'calm' : '';
  if (!mode) {
    return;
  }

  var mobile = root.classList.contains('motion--mobile') || root.classList.contains('motion--low');
  var scale = 1;
  var timers = [];
  var stopParticles = null;
  var finished = false;
  var light = root.dataset.theme === 'light';

  function clearMode() {
    root.classList.remove('sf-intro-full', 'sf-intro-calm');
    root.classList.add('sf-intro-done');
  }

  function later(fn, ms) {
    timers.push(setTimeout(fn, Math.round(ms * scale)));
  }

  function finish(el) {
    if (finished) {
      return;
    }
    finished = true;
    for (var i = 0; i < timers.length; i++) {
      clearTimeout(timers[i]);
    }
    if (stopParticles) {
      stopParticles();
    }
    el.classList.add('is-leaving');
    var dissolve = cfg.dissolveMs || 340;
    setTimeout(function () {
      el.style.pointerEvents = 'none';
    }, Math.round(dissolve * (cfg.passThroughAt || 0.8)));
    setTimeout(function () {
      if (el.parentNode) {
        el.parentNode.removeChild(el);
      }
      clearMode();
    }, dissolve + 60);
  }

  function armSkip(el) {
    var skip = function () {
      finish(el);
    };
    var opts = { once: true, passive: true };
    el.addEventListener('pointerdown', skip, opts);
    window.addEventListener('keydown', skip, opts);
    var btn = el.querySelector('[data-intro-skip]');
    if (btn) {
      btn.addEventListener('click', skip, opts);
    }
  }

  function sampleTargets(img, size) {
    var off = doc.createElement('canvas');
    off.width = size;
    off.height = size;
    var oc = off.getContext('2d');
    if (!oc) {
      return [];
    }
    oc.drawImage(img, 0, 0, size, size);
    var data;
    try {
      data = oc.getImageData(0, 0, size, size).data;
    } catch (e) {
      return [];
    }
    var pts = [];
    for (var y = 0; y < size; y++) {
      for (var x = 0; x < size; x++) {
        if (data[(y * size + x) * 4 + 3] > 70) {
          pts.push(x / size, y / size);
        }
      }
    }
    return pts;
  }

  function particles(el) {
    var canvas = el.querySelector('.sf-intro__canvas');
    var img = el.querySelector('.sf-intro__mark');
    if (!canvas || !img || !canvas.getContext) {
      return;
    }
    var start = function () {
      if (finished) {
        return;
      }
      var dpr = Math.min(window.devicePixelRatio || 1, mobile ? 1 : 1.5);
      var w = window.innerWidth;
      var h = window.innerHeight;
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      var ctx = canvas.getContext('2d');
      if (!ctx) {
        return;
      }
      ctx.scale(dpr, dpr);
      var pts = sampleTargets(img, 96);
      var count = pts.length / 2;
      if (!count) {
        return;
      }
      var rect = img.getBoundingClientRect();
      var want = mobile ? (cfg.particles && cfg.particles.mobile) || 260 : (cfg.particles && cfg.particles.desktop) || 680;
      var n = Math.min(want, count);
      var cx = w / 2;
      var cy = h / 2;
      var radius = Math.min(w, h) * 0.46;
      var list = [];
      for (var i = 0; i < n; i++) {
        var k = (Math.random() * count) | 0;
        var a = Math.random() * Math.PI * 2;
        var d = radius * (0.3 + Math.random() * 0.7);
        list.push({
          x0: cx + Math.cos(a) * d,
          y0: cy + Math.sin(a) * d,
          x1: rect.left + pts[k * 2] * rect.width,
          y1: rect.top + pts[k * 2 + 1] * rect.height,
          delay: Math.random() * 360 * scale,
          dur: (780 + Math.random() * 420) * scale,
          r: 0.7 + Math.random() * 1.5,
          o: 0.3 + Math.random() * 0.6
        });
      }
      var converge = (cfg.convergeMs || 1250) * scale;
      var end = converge + 300;
      var t0 = performance.now();
      var stopped = false;
      ctx.fillStyle = light ? 'rgb(122, 85, 48)' : 'rgb(214, 180, 136)';
      var dim = light ? 0.75 : 1;
      function frame(now) {
        if (stopped) {
          return;
        }
        var t = now - t0;
        ctx.clearRect(0, 0, w, h);
        ctx.globalCompositeOperation = light ? 'source-over' : 'lighter';
        var fade = t > converge ? Math.max(0, 1 - (t - converge) / 300) : 1;
        for (var j = 0; j < n; j++) {
          var p = list[j];
          var u = (t - p.delay) / p.dur;
          if (u < 0) {
            u = 0;
          } else if (u > 1) {
            u = 1;
          }
          var e = u >= 1 ? 1 : 1 - Math.pow(2, -10 * u);
          ctx.globalAlpha = p.o * fade * (0.55 + 0.45 * u) * dim;
          ctx.beginPath();
          ctx.arc(p.x0 + (p.x1 - p.x0) * e, p.y0 + (p.y1 - p.y0) * e, p.r, 0, 6.2832);
          ctx.fill();
        }
        if (t < end) {
          requestAnimationFrame(frame);
        } else {
          ctx.clearRect(0, 0, w, h);
        }
      }
      requestAnimationFrame(frame);
      stopParticles = function () {
        stopped = true;
        ctx.clearRect(0, 0, w, h);
      };
    };
    if (img.complete && img.naturalWidth) {
      start();
    } else {
      img.addEventListener('load', start, { once: true });
    }
  }

  function run() {
    cfg = (window.SF_MOTION || {}).intro || {};
    scale = mobile ? (cfg.mobileScale || 0.86) : 1;
    var el = doc.getElementById('sf-intro');
    if (!el) {
      clearMode();
      return;
    }
    armSkip(el);
    if (mode === 'calm') {
      var calm = cfg.calm || {};
      el.classList.add('is-mark', 'is-tag');
      later(function () {
        finish(el);
      }, (calm.markInMs || 300) + (calm.holdMs || 520));
      return;
    }
    particles(el);
    later(function () {
      el.classList.add('is-mark');
    }, cfg.markInAt || 1000);
    later(function () {
      el.classList.add('is-shimmer');
    }, cfg.shimmerAt || 1220);
    later(function () {
      el.classList.add('is-tag');
    }, cfg.tagAt || 1280);
    later(function () {
      finish(el);
    }, cfg.dissolveAt || 1780);
    later(function () {
      finish(el);
    }, cfg.failsafeMs || 2600);
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
