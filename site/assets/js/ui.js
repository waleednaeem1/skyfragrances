(function () {
  'use strict';
  var SF = window.SF || {};
  var doc = document;
  var body = doc.body;
  var reducedQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

  SF.base = body ? (body.getAttribute('data-base') || '') : '';
  SF.url = function (path) { return SF.base + path; };
  SF.reducedMotion = function () { return !!(reducedQuery && reducedQuery.matches); };
  SF.qs = function (selector, root) { return (root || doc).querySelector(selector); };
  SF.qsa = function (selector, root) { return Array.prototype.slice.call((root || doc).querySelectorAll(selector)); };
  SF.on = function (root, type, selector, handler) {
    root.addEventListener(type, function (event) {
      var target = event.target.closest ? event.target.closest(selector) : null;
      if (target && root.contains(target)) {
        handler.call(target, event, target);
      }
    });
  };

  SF.csrfToken = function () {
    var meta = doc.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  };
  SF.ensureCsrfToken = function () {
    var token = SF.csrfToken();
    if (token) {
      return Promise.resolve(token);
    }
    return window.fetch(SF.url('/api/session'), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
      credentials: 'same-origin',
      cache: 'no-store'
    }).then(function (response) {
      return response.json();
    }).then(function (json) {
      var fresh = (json && json.csrf) || '';
      var meta = doc.querySelector('meta[name="csrf-token"]');
      if (meta && fresh) {
        meta.setAttribute('content', fresh);
      }
      return fresh;
    }, function () {
      return '';
    });
  };
  SF.fetch = function (path, options) {
    var opts = options || {};
    var method = (opts.method || 'GET').toUpperCase();
    var headers = { 'Accept': 'application/json', 'X-Requested-With': 'fetch' };
    var payload = opts.body;
    var ready = Promise.resolve('');
    if (method !== 'GET' && method !== 'HEAD') {
      ready = SF.ensureCsrfToken();
      if (payload && typeof payload === 'object' && !(payload instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(payload);
      }
    }
    return ready.then(function (token) {
      if (token) {
        headers['X-CSRF-Token'] = token;
      }
      return SF.send(path, method, headers, payload);
    });
  };
  SF.send = function (path, method, headers, payload) {
    return window.fetch(SF.url(path), {
      method: method,
      headers: headers,
      body: payload,
      credentials: 'same-origin',
      cache: 'no-store'
    }).then(function (response) {
      return response.json().then(function (json) {
        return { ok: response.ok && json && json.ok !== false, status: response.status, data: json || {} };
      }, function () {
        return { ok: false, status: response.status, data: { ok: false, error: 'response', message: 'Something went wrong. Please try again.' } };
      });
    }, function () {
      return { ok: false, status: 0, data: { ok: false, error: 'network', message: 'You appear to be offline. Please check your connection.' } };
    });
  };
  SF.formData = function (form) {
    var data = {};
    var entries = new FormData(form);
    entries.forEach(function (value, key) {
      if (key !== '_csrf') {
        data[key] = value;
      }
    });
    return data;
  };
  SF.setLoading = function (control, isLoading) {
    if (!control) {
      return;
    }
    control.classList.toggle('is-loading', isLoading);
    control.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    if (isLoading) {
      control.setAttribute('data-was-disabled', control.disabled ? '1' : '0');
      control.disabled = true;
    } else if (control.getAttribute('data-was-disabled') !== '1') {
      control.disabled = false;
    }
  };
  SF.bump = function (el, className) {
    if (!el) {
      return;
    }
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
  };
  SF.escapeHtml = function (value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  };
  SF.icon = function (name) {
    var paths = {
      close: '<path d="M6 6l12 12M18 6L6 18"/>',
      minus: '<path d="M5 12h14"/>',
      plus: '<path d="M12 5v14M5 12h14"/>',
      trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
      check: '<path d="M5 12l5 5L20 7"/>',
      bag: '<path d="M6 7h12l1 13H5L6 7z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>'
    };
    return '<svg class="icon icon--sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + (paths[name] || '') + '</svg>';
  };
  window.SF = SF;
})();

(function () {
  'use strict';
  var SF = window.SF;
  var doc = document;
  var MAX_TOASTS = 3;

  function toastRegion() {
    return doc.querySelector('.js-toast-region');
  }
  SF.toast = function (message, options) {
    var opts = options || {};
    var region = toastRegion();
    if (!region || !message) {
      return null;
    }
    while (region.children.length >= MAX_TOASTS) {
      region.removeChild(region.firstElementChild);
    }
    var type = opts.type === 'error' ? 'error' : (opts.type === 'success' ? 'success' : 'info');
    var toast = doc.createElement('div');
    toast.className = 'toast toast--' + type;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.innerHTML = '<div class="toast__body"><span class="toast__text">' + SF.escapeHtml(message) + '</span>' +
      (opts.action ? '<button class="toast__action" type="button">' + SF.escapeHtml(opts.action.label) + '</button>' : '') +
      '</div><button class="toast__close" type="button" aria-label="Dismiss">' + SF.icon('close') + '</button>';
    region.appendChild(toast);
    var timer = null;
    var ttl = opts.duration || (type === 'error' ? 8000 : 5000);
    function remove() {
      window.clearTimeout(timer);
      toast.classList.add('is-leaving');
      toast.classList.remove('is-visible');
      window.setTimeout(function () {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, SF.reducedMotion() ? 20 : 300);
    }
    function arm() {
      window.clearTimeout(timer);
      timer = window.setTimeout(remove, ttl);
    }
    toast.querySelector('.toast__close').addEventListener('click', remove);
    if (opts.action) {
      toast.querySelector('.toast__action').addEventListener('click', function () {
        opts.action.onClick();
        remove();
      });
    }
    toast.addEventListener('mouseenter', function () { window.clearTimeout(timer); });
    toast.addEventListener('focusin', function () { window.clearTimeout(timer); });
    toast.addEventListener('mouseleave', arm);
    toast.addEventListener('focusout', arm);
    window.requestAnimationFrame(function () {
      toast.classList.add('is-visible');
    });
    arm();
    return toast;
  };

  var openDialogs = [];
  var scrollY = 0;

  function lockBody() {
    if (doc.body.classList.contains('is-locked')) {
      return;
    }
    scrollY = window.pageYOffset;
    var scrollbar = window.innerWidth - doc.documentElement.clientWidth;
    doc.body.style.setProperty('--scrollbar-w', scrollbar + 'px');
    doc.body.style.setProperty('--scroll-y', scrollY + 'px');
    doc.body.classList.add('is-locked');
  }
  function unlockBody() {
    doc.body.classList.remove('is-locked');
    doc.body.style.removeProperty('--scrollbar-w');
    doc.body.style.removeProperty('--scroll-y');
    window.scrollTo({ top: scrollY, left: 0, behavior: 'instant' });
  }
  function focusables(panel) {
    return SF.qsa(FOCUSABLE_SELECTOR, panel).filter(function (el) {
      return el.offsetParent !== null || el === doc.activeElement;
    });
  }
  var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function trapKeydown(event) {
    var current = openDialogs[openDialogs.length - 1];
    if (!current) {
      return;
    }
    if (event.key === 'Escape') {
      event.preventDefault();
      SF.closeDialog(current.panel);
      return;
    }
    if (event.key !== 'Tab') {
      return;
    }
    var items = focusables(current.panel);
    if (!items.length) {
      event.preventDefault();
      current.panel.focus();
      return;
    }
    var first = items[0];
    var last = items[items.length - 1];
    if (event.shiftKey && (doc.activeElement === first || !current.panel.contains(doc.activeElement))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && doc.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  SF.openDialog = function (panel, options) {
    if (!panel || panel.classList.contains('is-open')) {
      return;
    }
    var opts = options || {};
    var overlay = opts.overlay || SF.qs(panel.getAttribute('data-overlay') || '.js-none');
    var trigger = opts.trigger || doc.activeElement;
    panel.hidden = false;
    panel.setAttribute('aria-hidden', 'false');
    if (overlay) {
      overlay.hidden = false;
    }
    void panel.offsetWidth;
    panel.classList.add('is-open');
    if (overlay) {
      overlay.classList.add('is-open');
    }
    lockBody();
    openDialogs.push({ panel: panel, overlay: overlay, trigger: trigger, onClose: opts.onClose });
    if (openDialogs.length === 1) {
      doc.addEventListener('keydown', trapKeydown);
    }
    var target = panel.querySelector('[data-autofocus]') || panel.querySelector('.drawer__close, .modal__close, .nav-mobile__close') || focusables(panel)[0] || panel;
    window.setTimeout(function () { target.focus({ preventScroll: true }); }, 30);
    if (trigger && trigger.setAttribute && trigger.hasAttribute('aria-expanded')) {
      trigger.setAttribute('aria-expanded', 'true');
    }
    panel.dispatchEvent(new CustomEvent('sf:dialog-open', { bubbles: true }));
  };

  SF.closeDialog = function (panel) {
    var index = -1;
    for (var i = 0; i < openDialogs.length; i++) {
      if (openDialogs[i].panel === panel) {
        index = i;
      }
    }
    if (index < 0) {
      return;
    }
    var entry = openDialogs.splice(index, 1)[0];
    panel.classList.remove('is-open');
    if (entry.overlay) {
      entry.overlay.classList.remove('is-open');
    }
    var finished = false;
    function finish() {
      if (finished) {
        return;
      }
      finished = true;
      panel.removeEventListener('transitionend', finish);
      panel.hidden = true;
      panel.setAttribute('aria-hidden', 'true');
      if (entry.overlay) {
        entry.overlay.hidden = true;
      }
      if (!openDialogs.length) {
        unlockBody();
        doc.removeEventListener('keydown', trapKeydown);
      }
      var back = entry.trigger && doc.contains(entry.trigger) ? entry.trigger : doc.getElementById('main');
      if (back && back.focus) {
        back.focus({ preventScroll: true });
      }
      if (entry.trigger && entry.trigger.hasAttribute && entry.trigger.hasAttribute('aria-expanded')) {
        entry.trigger.setAttribute('aria-expanded', 'false');
      }
      if (typeof entry.onClose === 'function') {
        entry.onClose();
      }
      panel.dispatchEvent(new CustomEvent('sf:dialog-close', { bubbles: true }));
    }
    panel.addEventListener('transitionend', finish);
    window.setTimeout(finish, SF.reducedMotion() ? 30 : 400);
  };
  SF.closeAllDialogs = function () {
    openDialogs.slice().reverse().forEach(function (entry) { SF.closeDialog(entry.panel); });
  };
  SF.dialogIsOpen = function () { return openDialogs.length > 0; };

  function bindDialogTriggers() {
    SF.on(doc, 'click', '[data-dialog-open]', function (event, trigger) {
      var panel = doc.getElementById(trigger.getAttribute('data-dialog-open'));
      if (!panel) {
        return;
      }
      event.preventDefault();
      SF.openDialog(panel, { trigger: trigger });
    });
    SF.on(doc, 'click', '[data-dialog-close]', function (event, trigger) {
      var panel = trigger.closest('.drawer, .modal, .nav-mobile');
      if (panel) {
        event.preventDefault();
        SF.closeDialog(panel);
      }
    });
    SF.on(doc, 'click', '.drawer-overlay, .modal__overlay', function () {
      var current = openDialogs[openDialogs.length - 1];
      if (current) {
        SF.closeDialog(current.panel);
      }
    });
    SF.on(doc, 'click', '.js-nav-toggle', function (event, trigger) {
      var nav = SF.qs('.js-nav-mobile');
      if (!nav) {
        return;
      }
      event.preventDefault();
      if (nav.classList.contains('is-open')) {
        SF.closeDialog(nav);
      } else {
        SF.openDialog(nav, { trigger: trigger, overlay: SF.qs('.js-nav-overlay') });
      }
    });
    SF.on(doc, 'click', '.js-nav-close', function (event) {
      event.preventDefault();
      SF.closeDialog(SF.qs('.js-nav-mobile'));
    });
    SF.on(doc, 'keydown', 'a[role="button"], a.js-cart-open', function (event, trigger) {
      if (event.key === ' ' || event.key === 'Spacebar') {
        event.preventDefault();
        trigger.click();
      }
    });
  }

  SF.accordionSet = function (trigger, expanded) {
    var panel = doc.getElementById(trigger.getAttribute('aria-controls') || '');
    if (!panel) {
      return;
    }
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    if (expanded) {
      panel.hidden = false;
      void panel.offsetWidth;
      panel.classList.add('is-open');
      return;
    }
    panel.classList.remove('is-open');
    var done = false;
    function finish() {
      if (done) {
        return;
      }
      done = true;
      panel.removeEventListener('transitionend', finish);
      if (!panel.classList.contains('is-open')) {
        panel.hidden = true;
      }
    }
    panel.addEventListener('transitionend', finish);
    window.setTimeout(finish, SF.reducedMotion() ? 30 : 400);
  };
  function initAccordions() {
    SF.qsa('.js-accordion-trigger, .js-footer-toggle').forEach(function (trigger) {
      var panel = doc.getElementById(trigger.getAttribute('aria-controls') || '');
      if (!panel) {
        return;
      }
      var expanded = trigger.getAttribute('aria-expanded') === 'true';
      var isFooter = trigger.classList.contains('js-footer-toggle');
      if (isFooter && window.matchMedia('(min-width: 768px)').matches) {
        return;
      }
      panel.hidden = !expanded;
      panel.classList.toggle('is-open', expanded);
    });
    SF.on(doc, 'click', '.js-accordion-trigger, .js-footer-toggle', function (event, trigger) {
      event.preventDefault();
      if (trigger.classList.contains('js-footer-toggle') && window.matchMedia('(min-width: 768px)').matches) {
        return;
      }
      SF.accordionSet(trigger, trigger.getAttribute('aria-expanded') !== 'true');
    });
  }

  function initAnnouncement() {
    var bar = SF.qs('.js-announcement');
    if (!bar) {
      return;
    }
    var key = 'sf_ann_' + (bar.getAttribute('data-announcement-hash') || '');
    try {
      if (window.sessionStorage.getItem(key) === '1') {
        bar.classList.add('is-dismissed');
        bar.hidden = true;
      }
    } catch (error) {
      bar.hidden = false;
    }
    SF.on(bar, 'click', '.js-announcement-dismiss', function () {
      bar.classList.add('is-dismissed');
      bar.hidden = true;
      try {
        window.sessionStorage.setItem(key, '1');
      } catch (error) {
        bar.hidden = true;
      }
    });
  }
  function initHeaderSearch() {
    var overlay = SF.qs('.js-search-overlay');
    if (!overlay) {
      return;
    }
    SF.on(doc, 'click', '.js-search-open', function (event, trigger) {
      event.preventDefault();
      overlay.hidden = false;
      overlay.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
      var input = overlay.querySelector('input');
      if (input) {
        input.focus();
      }
    });
    function close() {
      overlay.classList.remove('is-open');
      overlay.hidden = true;
      var opener = SF.qs('.js-search-open');
      if (opener) {
        opener.setAttribute('aria-expanded', 'false');
        opener.focus();
      }
    }
    SF.on(overlay, 'click', '.js-search-close', function (event) {
      event.preventDefault();
      close();
    });
    overlay.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
      }
    });
  }
  function initMegaPanel() {
    SF.on(doc, 'keydown', '.js-panel-trigger', function (event, trigger) {
      var panel = doc.getElementById(trigger.getAttribute('aria-controls') || '');
      if (!panel) {
        return;
      }
      if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
        event.preventDefault();
        panel.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        var first = panel.querySelector('a');
        if (first) {
          first.focus();
        }
      }
    });
    doc.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }
      SF.qsa('.site-header__panel.is-open').forEach(function (panel) {
        panel.classList.remove('is-open');
        var trigger = SF.qs('[aria-controls="' + panel.id + '"]');
        if (trigger) {
          trigger.setAttribute('aria-expanded', 'false');
          trigger.focus();
        }
      });
    });
  }
  function initCopyButtons() {
    SF.on(doc, 'click', '.js-copy', function (event, button) {
      var text = button.getAttribute('data-copy') || '';
      if (!text || !navigator.clipboard) {
        return;
      }
      navigator.clipboard.writeText(text).then(function () {
        button.classList.add('is-copied');
        SF.toast('Copied ' + text, { type: 'success', duration: 2500 });
        window.setTimeout(function () { button.classList.remove('is-copied'); }, 2000);
      });
    });
  }
  function initReadMore() {
    SF.on(doc, 'click', '.js-expand', function (event, button) {
      var target = doc.getElementById(button.getAttribute('aria-controls') || '');
      if (!target) {
        return;
      }
      target.classList.add('is-expanded');
      target.classList.remove('is-collapsed');
      button.setAttribute('aria-expanded', 'true');
      button.hidden = true;
    });
  }
  function initHeroCue() {
    var cue = SF.qs('.js-hero-cue');
    if (!cue) {
      return;
    }
    window.addEventListener('scroll', function onScroll() {
      cue.classList.add('is-hidden');
      window.removeEventListener('scroll', onScroll);
    }, { passive: true });
  }
  function init() {
    bindDialogTriggers();
    initAccordions();
    initAnnouncement();
    initHeaderSearch();
    initMegaPanel();
    initCopyButtons();
    initReadMore();
    initHeroCue();
    if (window.location.hash === '#cart' && SF.qs('.js-cart-drawer')) {
      SF.openDialog(SF.qs('.js-cart-drawer'), { overlay: SF.qs('.js-cart-overlay') });
    }
  }
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
