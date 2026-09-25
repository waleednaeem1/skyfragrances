(function () {
  'use strict';
  var doc = document;
  var STEP_MS = 70;
  var reducedQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var handshake = window.SFReveal = window.SFReveal || {};

  function reduced() {
    return !!(reducedQuery && reducedQuery.matches);
  }
  function showAll(nodes) {
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.add('is-visible');
    }
  }
  function observe(nodes, options, onEnter) {
    var observer = new IntersectionObserver(function (entries) {
      for (var i = 0; i < entries.length; i++) {
        onEnter(entries[i], observer);
      }
    }, options);
    for (var j = 0; j < nodes.length; j++) {
      observer.observe(nodes[j]);
    }
    return observer;
  }
  function initReveal() {
    if (handshake.started) {
      return;
    }
    handshake.started = true;
    var nodes = doc.querySelectorAll('.sf-reveal');
    if (!nodes.length) {
      return;
    }
    if (reduced() || typeof window.IntersectionObserver === 'undefined') {
      showAll(nodes);
      return;
    }
    observe(nodes, { rootMargin: '0px 0px -12% 0px', threshold: 0 }, function (entry, observer) {
      if (!entry.isIntersecting) {
        return;
      }
      var el = entry.target;
      var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
      if (delay > 0 && delay <= 5) {
        el.style.transitionDelay = (delay * STEP_MS) + 'ms';
      }
      el.classList.add('is-visible');
      observer.unobserve(el);
    });
    if (reducedQuery && reducedQuery.addEventListener) {
      reducedQuery.addEventListener('change', function () {
        if (reduced()) {
          showAll(doc.querySelectorAll('.sf-reveal'));
        }
      });
    }
  }
  function initHeader() {
    var header = doc.querySelector('.js-site-header');
    if (!header) {
      return;
    }
    var sentinel = doc.querySelector('.header-sentinel');
    var startsTransparent = header.classList.contains('is-transparent');
    if (startsTransparent && sentinel && typeof window.IntersectionObserver !== 'undefined') {
      observe([sentinel], { rootMargin: '-80px 0px 0px 0px', threshold: 0 }, function (entry) {
        var solid = !entry.isIntersecting;
        header.classList.toggle('is-solid', solid);
        header.classList.toggle('is-transparent', !solid);
      });
    }
    var lastY = window.pageYOffset;
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) {
        return;
      }
      ticking = true;
      window.requestAnimationFrame(function () {
        var y = window.pageYOffset;
        var locked = doc.body.classList.contains('is-locked');
        if (!locked && y > lastY + 6 && y > 240) {
          header.classList.add('is-hidden');
        } else if (y < lastY - 6 || y <= 240) {
          header.classList.remove('is-hidden');
        }
        lastY = y;
        ticking = false;
      });
    }, { passive: true });
  }
  function raiseFloating(raised) {
    var region = doc.querySelector('.js-toast-region');
    var fab = doc.querySelector('.js-whatsapp-fab');
    if (region) {
      region.classList.toggle('is-raised', raised);
    }
    if (fab) {
      fab.classList.toggle('whatsapp-fab--raised', raised);
    }
  }
  function initStickyBar() {
    var bar = doc.querySelector('.js-sticky-bar');
    var anchor = doc.querySelector('.js-sticky-anchor');
    if (!bar || !anchor || typeof window.IntersectionObserver === 'undefined') {
      return;
    }
    var desktop = window.matchMedia('(min-width: 1024px)');
    var whenHidden = bar.hasAttribute('data-sticky-when-hidden');
    observe([anchor], { threshold: 0 }, function (entry) {
      var away = !entry.isIntersecting && (whenHidden || entry.boundingClientRect.top < 0);
      var passed = away && !desktop.matches;
      bar.classList.toggle('is-visible', passed);
      doc.body.classList.toggle('has-sticky-bar', passed);
      raiseFloating(passed);
    });
  }
  function initFilterBar() {
    var bar = doc.querySelector('.js-filter-bar');
    var anchor = doc.querySelector('.js-filter-bar-anchor');
    if (!bar || !anchor || typeof window.IntersectionObserver === 'undefined') {
      return;
    }
    var desktop = window.matchMedia('(min-width: 1024px)');
    observe([anchor], { threshold: 0 }, function (entry) {
      var shown = entry.isIntersecting && !desktop.matches;
      bar.classList.toggle('is-visible', shown);
      doc.body.classList.toggle('has-filter-bar', shown);
      raiseFloating(shown);
    });
  }
  function init() {
    handshake.start = initReveal;
    handshake.showAll = function () {
      showAll(doc.querySelectorAll('.sf-reveal'));
    };
    if (!handshake.claimed) {
      initReveal();
    }
    initHeader();
    initStickyBar();
    initFilterBar();
  }
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
