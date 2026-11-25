(function () {
  'use strict';
  var doc = document;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

  function initHero(hero) {
    var stage = hero.querySelector('[data-hero-stage]');
    var link = hero.querySelector('[data-hero-link]');
    var frame = 0;
    var x = 0;
    var y = 0;
    var burstTimer = 0;

    function paint() {
      frame = 0;
      hero.style.setProperty('--mx', x.toFixed(3));
      hero.style.setProperty('--my', y.toFixed(3));
    }
    function queue() {
      if (!frame) {
        frame = window.requestAnimationFrame(paint);
      }
    }
    hero.addEventListener('pointermove', function (event) {
      if (event.pointerType !== 'mouse' || !finePointer.matches || reduced.matches) {
        return;
      }
      var rect = hero.getBoundingClientRect();
      x = (event.clientX - rect.left) / rect.width - 0.5;
      y = (event.clientY - rect.top) / rect.height - 0.5;
      queue();
    });
    hero.addEventListener('pointerleave', function () {
      x = 0;
      y = 0;
      queue();
    });

    hero.querySelectorAll('[data-hero-spray]').forEach(function (button) {
      button.addEventListener('click', function () {
        if (!stage || reduced.matches) {
          return;
        }
        stage.classList.remove('is-burst');
        void stage.offsetWidth;
        stage.classList.add('is-burst');
        window.clearTimeout(burstTimer);
        burstTimer = window.setTimeout(function () {
          stage.classList.remove('is-burst');
        }, 1500);
      });
    });

    if (link) {
      hero.querySelectorAll('[data-hero-caption]').forEach(function (caption) {
        function select() {
          link.href = caption.getAttribute('data-href');
          link.setAttribute('aria-label', 'Shop ' + caption.getAttribute('data-name'));
        }
        caption.addEventListener('animationstart', select);
        caption.addEventListener('animationiteration', select);
      });
    }
  }

  function initVoices(section) {
    var voices = section.querySelectorAll('[data-voice]');
    var dots = section.querySelectorAll('.voices__dots span');
    var current = 0;
    var held = false;
    if (voices.length < 2 || reduced.matches) {
      return;
    }
    function show(index) {
      voices[current].classList.remove('is-active');
      voices[current].setAttribute('aria-hidden', 'true');
      if (dots[current]) {
        dots[current].classList.remove('is-active');
      }
      current = index;
      voices[current].classList.add('is-active');
      voices[current].removeAttribute('aria-hidden');
      if (dots[current]) {
        dots[current].classList.add('is-active');
      }
    }
    ['mouseenter', 'focusin'].forEach(function (type) {
      section.addEventListener(type, function () { held = true; });
    });
    ['mouseleave', 'focusout'].forEach(function (type) {
      section.addEventListener(type, function () { held = false; });
    });
    window.setInterval(function () {
      if (!held && !doc.hidden && !section.classList.contains('is-paused')) {
        show((current + 1) % voices.length);
      }
    }, 6000);
  }

  function init() {
    var hero = doc.querySelector('[data-hero]');
    var voices = doc.querySelector('.voices');
    if (hero) {
      initHero(hero);
    }
    if (voices) {
      initVoices(voices);
    }
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
