(function () {
  'use strict';
  var doc = document;
  var reducedQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  var hasObserver = typeof window.IntersectionObserver !== 'undefined';

  function observe(nodes, options, onChange) {
    var observer = new IntersectionObserver(function (entries) {
      for (var i = 0; i < entries.length; i++) {
        onChange(entries[i], observer);
      }
    }, options);
    for (var j = 0; j < nodes.length; j++) {
      observer.observe(nodes[j]);
    }
  }
  function markAll(nodes, className) {
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.add(className);
    }
  }
  function onceInView(selector, className, margin) {
    var nodes = doc.querySelectorAll(selector);
    if (!nodes.length) {
      return;
    }
    if (reducedQuery.matches || !hasObserver) {
      markAll(nodes, className);
      return;
    }
    observe(nodes, { rootMargin: '0px 0px ' + margin + ' 0px', threshold: 0 }, function (entry, observer) {
      if (entry.isIntersecting) {
        entry.target.classList.add(className);
        observer.unobserve(entry.target);
      }
    });
  }

  function initReveal() {
    var native = window.CSS && window.CSS.supports && window.CSS.supports('animation-timeline: view()');
    if (!native) {
      onceInView('[data-reveal]', 'is-visible', '-10%');
    }
    onceInView('[data-inview]', 'is-inview', '-12%');
  }
  function initLoops() {
    var loops = doc.querySelectorAll('[data-loop]');
    if (!loops.length || !hasObserver) {
      return;
    }
    observe(loops, { rootMargin: '120px 0px', threshold: 0 }, function (entry) {
      entry.target.classList.toggle('is-paused', !entry.isIntersecting);
    });
  }
  function initHeader() {
    var header = doc.querySelector('.js-site-header');
    if (!header) {
      return;
    }
    var sentinel = doc.querySelector('.header-sentinel');
    if (sentinel && hasObserver) {
      observe([sentinel], { rootMargin: '-80px 0px 0px 0px', threshold: 0 }, function (entry) {
        header.classList.toggle('is-solid', !entry.isIntersecting);
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
        if (!doc.body.classList.contains('is-locked') && y > lastY + 6 && y > 240) {
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
  function initBottomBar(barSelector, anchorSelector, bodyClass, visibleWhen) {
    var bar = doc.querySelector(barSelector);
    var anchor = doc.querySelector(anchorSelector);
    if (!bar || !anchor || !hasObserver) {
      return;
    }
    var desktop = window.matchMedia('(min-width: 1024px)');
    observe([anchor], { threshold: 0 }, function (entry) {
      var shown = visibleWhen(entry, bar) && !desktop.matches;
      bar.classList.toggle('is-visible', shown);
      doc.body.classList.toggle(bodyClass, shown);
      raiseFloating(shown);
    });
  }

  function init() {
    initReveal();
    initLoops();
    initHeader();
    initBottomBar('.js-sticky-bar', '.js-sticky-anchor', 'has-sticky-bar', function (entry, bar) {
      return !entry.isIntersecting && (bar.hasAttribute('data-sticky-when-hidden') || entry.boundingClientRect.top < 0);
    });
    initBottomBar('.js-filter-bar', '.js-filter-bar-anchor', 'has-filter-bar', function (entry) {
      return entry.isIntersecting;
    });
  }
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
