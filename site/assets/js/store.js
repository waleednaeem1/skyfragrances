(function () {
  'use strict';
  var doc = document;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

  function initRails() {
    doc.querySelectorAll('[data-rail]').forEach(function (rail) {
      var prev = doc.querySelector('[data-rail-prev][aria-controls="' + rail.id + '"]');
      var next = doc.querySelector('[data-rail-next][aria-controls="' + rail.id + '"]');
      if (!prev || !next) {
        return;
      }
      function sync() {
        var max = rail.scrollWidth - rail.clientWidth - 2;
        prev.disabled = rail.scrollLeft <= 2;
        next.disabled = rail.scrollLeft >= max;
      }
      function step(direction) {
        rail.scrollBy({ left: direction * rail.clientWidth * 0.8, behavior: reduced.matches ? 'auto' : 'smooth' });
      }
      prev.addEventListener('click', function () { step(-1); });
      next.addEventListener('click', function () { step(1); });
      rail.addEventListener('scroll', sync, { passive: true });
      window.addEventListener('resize', sync);
      sync();
    });
  }

  function mistBurst(image) {
    var host = image && image.closest ? image.closest('.product-card__media, [data-gallery-main], .gallery__slide') : null;
    if (!host || reduced.matches) {
      return;
    }
    for (var i = 0; i < 7; i++) {
      var puff = doc.createElement('span');
      var angle = (i / 7) * Math.PI * 2 - Math.PI / 2;
      puff.className = 'mist-puff';
      puff.style.setProperty('--dx', Math.round(Math.cos(angle) * (90 + i * 12)) + 'px');
      puff.style.setProperty('--dy', Math.round(Math.sin(angle) * (70 + i * 10) - 40) + 'px');
      puff.style.setProperty('--s', (34 + (i % 3) * 14) + 'px');
      puff.style.animationDelay = (i * 0.03).toFixed(2) + 's';
      host.appendChild(puff);
      window.setTimeout(puff.remove.bind(puff), 1600);
    }
  }

  function init() {
    initRails();
    doc.addEventListener('sf:cart-added', function (event) {
      var detail = event.detail || {};
      mistBurst(detail.thumb);
      if (window.SF && typeof window.SF.toast === 'function') {
        window.SF.toast('Added to your cart', { type: 'success' });
      }
    });
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
