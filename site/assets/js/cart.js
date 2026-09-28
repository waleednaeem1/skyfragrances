(function () {
  'use strict';
  var SF = window.SF;
  var doc = document;
  var synced = false;
  var lastCart = null;
  var pending = false;

  function drawer() { return SF.qs('.js-cart-drawer'); }
  function overlay() { return SF.qs('.js-cart-overlay'); }
  function h(text) { return SF.escapeHtml(text); }

  function renderFreeShip(cart) {
    var fs = cart.free_shipping;
    if (!fs || !fs.enabled || !cart.count) {
      return '';
    }
    var unlocked = fs.remaining <= 0;
    var text = unlocked
      ? '<svg class="free-ship__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>Free delivery unlocked.'
      : 'You’re <span class="free-ship__amount">' + h(fs.remaining_display) + '</span> away from free delivery';
    return '<div class="free-ship js-free-ship' + (unlocked ? ' is-unlocked' : '') + '" role="status"><p class="free-ship__text">' + text + '</p>' +
      '<div class="free-ship__track"><div class="free-ship__fill" style="--fill:' + (unlocked ? 100 : Math.max(0, Math.min(100, parseInt(fs.percent, 10) || 0))) + '%"></div></div></div>';
  }
  function renderLine(line) {
    var qty = parseInt(line.qty, 10) || 1;
    var max = parseInt(line.max_qty, 10) || 10;
    var atMax = qty >= max;
    var media = line.image_url
      ? '<img class="cart-line__img" src="' + h(line.image_url) + '" alt="' + h(line.image_alt || '') + '" width="72" height="90" loading="lazy" decoding="async">'
      : '<div class="placeholder" role="img" aria-label="' + h(line.product_name) + ' — image coming soon"></div>';
    return '<li class="cart-line' + (line.unavailable ? ' is-unavailable' : '') + '" data-size-id="' + h(line.size_id) + '">' +
      '<a class="cart-line__media" href="' + h(line.product_url) + '" tabindex="-1" aria-hidden="true">' + media + '</a>' +
      '<div class="cart-line__body">' +
        '<a class="cart-line__name" href="' + h(line.product_url) + '">' + h(line.product_name) + '</a>' +
        '<span class="cart-line__total price">' + h(line.line_total_display) + '</span>' +
        '<p class="cart-line__meta"><span>' + h(line.size_label) + '</span><span class="cart-line__unit">' + h(line.unit_price_display) + (line.unit_was_display ? ' <s class="cart-line__was">' + h(line.unit_was_display) + '</s>' : '') + '</span></p>' +
        (line.unavailable ? '<p class="cart-line__warning">' + h(line.message || 'This size is no longer available.') + '</p>' : '') +
        '<div class="cart-line__controls">' +
          '<div class="qty qty--sm js-qty" data-max="' + max + '">' +
            '<button class="qty__btn" type="button" data-cart-dec aria-label="' + (qty <= 1 ? 'Remove ' : 'Decrease quantity of ') + h(line.product_name) + '">' + SF.icon(qty <= 1 ? 'trash' : 'minus') + '</button>' +
            '<input class="qty__input" type="text" inputmode="numeric" pattern="[0-9]*" value="' + qty + '" data-cart-qty aria-label="Quantity of ' + h(line.product_name) + '">' +
            '<button class="qty__btn" type="button" data-cart-inc aria-label="Increase quantity of ' + h(line.product_name) + '"' + (atMax ? ' disabled' : '') + '>' + SF.icon('plus') + '</button>' +
          '</div>' +
          (atMax ? '<span class="cart-line__max">Max ' + max + '</span>' : '') +
          '<button class="cart-line__remove" type="button" data-cart-remove data-qty="' + qty + '">Remove</button>' +
        '</div>' +
      '</div></li>';
  }
  function renderMessages(messages) {
    if (!messages || !messages.length) {
      return '';
    }
    return '<div class="cart-messages">' + messages.map(function (m) {
      var text = typeof m === 'string' ? m : (m.text || m.message || '');
      var type = typeof m === 'object' && m.type === 'error' ? ' cart-message--error' : '';
      return '<p class="cart-message' + type + '" role="status">' + h(text) + '</p>';
    }).join('') + '</div>';
  }
  function render(cart) {
    lastCart = cart;
    var panel = drawer();
    if (!panel) {
      return;
    }
    var bodyEl = panel.querySelector('.js-cart-body');
    var footEl = panel.querySelector('.js-cart-foot');
    var count = parseInt(cart.count, 10) || 0;
    if (bodyEl) {
      if (!count) {
        bodyEl.innerHTML = renderMessages(cart.messages) +
          '<p class="drawer__empty">Your cart is empty.</p>' +
          '<p class="drawer__empty-copy">Twelve compositions, made for Pakistan. Start with the ones people keep coming back to.</p>' +
          '<div class="drawer__empty-action"><a class="btn btn--ghost" href="' + h(SF.url('/shop')) + '">Shop All Fragrances</a></div>';
      } else {
        bodyEl.innerHTML = renderFreeShip(cart) + renderMessages(cart.messages) +
          '<ul class="cart-lines">' + cart.lines.map(renderLine).join('') + '</ul>';
      }
      bodyEl.setAttribute('aria-busy', 'false');
    }
    if (footEl) {
      footEl.hidden = !count;
      var subtotal = footEl.querySelector('.js-cart-subtotal');
      if (subtotal) {
        subtotal.textContent = cart.subtotal_display || '';
      }
    }
    updateCount(count);
    var live = panel.querySelector('.js-cart-live');
    if (live) {
      live.textContent = count === 1 ? 'Cart updated. 1 item.' : 'Cart updated. ' + count + ' items.';
    }
  }
  function updateCount(count) {
    SF.qsa('.js-cart-count').forEach(function (el) {
      el.textContent = String(count);
    });
    var cartLink = SF.qs('.js-cart-open');
    if (cartLink) {
      cartLink.setAttribute('aria-label', 'Cart, ' + count + (count === 1 ? ' item' : ' items'));
      var bubble = cartLink.querySelector('.site-header__count');
      if (count > 0 && !bubble) {
        bubble = doc.createElement('span');
        bubble.className = 'site-header__count js-cart-count';
        bubble.textContent = String(count);
        cartLink.appendChild(bubble);
      } else if (bubble && count === 0) {
        bubble.parentNode.removeChild(bubble);
      }
      if (bubble && count > 0) {
        SF.bump(bubble, 'is-bumped');
      }
    }
  }

  function request(path, payload) {
    if (pending) {
      return Promise.resolve(null);
    }
    pending = true;
    var bodyEl = drawer() ? drawer().querySelector('.js-cart-body') : null;
    if (bodyEl) {
      bodyEl.setAttribute('aria-busy', 'true');
    }
    return SF.fetch(path, { method: payload ? 'POST' : 'GET', body: payload }).then(function (result) {
      pending = false;
      if (bodyEl) {
        bodyEl.setAttribute('aria-busy', 'false');
      }
      if (result.ok && result.data && Array.isArray(result.data.lines)) {
        synced = true;
        render(result.data);
      } else if (!result.ok) {
        var message = (result.data && result.data.message) || 'We could not update your cart. Please try again.';
        SF.toast(message, { type: 'error' });
        if (lastCart) {
          render(lastCart);
        }
      }
      return result;
    });
  }
  function reloadIfPage(form) {
    if (form && form.hasAttribute('data-cart-reload')) {
      window.location.reload();
      return true;
    }
    return false;
  }
  function openDrawer(trigger) {
    var panel = drawer();
    if (!panel) {
      return;
    }
    SF.openDialog(panel, { trigger: trigger, overlay: overlay() });
    if (!synced) {
      var bodyEl = panel.querySelector('.js-cart-body');
      if (bodyEl && bodyEl.querySelector('.drawer__empty') && bodyEl.getAttribute('data-count') !== '0') {
        bodyEl.innerHTML = '<div class="skeleton-line" aria-hidden="true"><span class="skeleton skeleton--thumb"></span><div class="skeleton-line__text"><span class="skeleton skeleton--line"></span><span class="skeleton skeleton--line-short"></span></div></div>'.repeat(2);
      }
      request('/api/cart');
    }
  }
  function add(sizeId, qty, button, source) {
    if (!sizeId) {
      SF.toast('Choose a size.', { type: 'error' });
      return Promise.resolve(null);
    }
    SF.setLoading(button, true);
    return request('/api/cart/add', { size_id: sizeId, qty: qty || 1 }).then(function (result) {
      if (result && result.ok && source !== 'undo') {
        doc.dispatchEvent(new CustomEvent('sf:cart-added', { detail: { button: button, form: button ? button.form || button.closest('form') : null, count: parseInt(result.data && result.data.count, 10) || 0, source: source || 'form' } }));
        openDrawer(button);
      }
      SF.setLoading(button, false);
      return result;
    });
  }
  function lineOf(el) {
    var line = el.closest('.cart-line');
    return line ? { sizeId: line.getAttribute('data-size-id'), input: line.querySelector('[data-cart-qty]'), name: line.querySelector('.cart-line__name').textContent, size: line.querySelector('.cart-line__meta span').textContent } : null;
  }
  function setQty(line, qty, form) {
    if (!line) {
      return;
    }
    if (qty <= 0) {
      remove(line, form);
      return;
    }
    var group = line.input ? line.input.closest('.js-qty') : null;
    if (group) {
      group.setAttribute('aria-busy', 'true');
      line.input.value = String(qty);
    }
    request('/api/cart/update', { size_id: line.sizeId, qty: qty }).then(function () {
      reloadIfPage(form);
    });
  }
  function remove(line, form) {
    var qty = line.input ? parseInt(line.input.value, 10) || 1 : 1;
    request('/api/cart/remove', { size_id: line.sizeId }).then(function (result) {
      if (result && result.ok && !reloadIfPage(form)) {
        SF.toast('Removed ' + line.name + ' (' + line.size + ').', {
          type: 'success',
          duration: 6000,
          action: { label: 'Undo', onClick: function () { add(line.sizeId, qty, null, 'undo'); } }
        });
      }
    });
  }
  function bind() {
    SF.on(doc, 'click', '.js-cart-open', function (event, trigger) {
      if (!drawer()) {
        return;
      }
      event.preventDefault();
      openDrawer(trigger);
    });
    SF.on(doc, 'click', '.js-cart-close', function (event) {
      event.preventDefault();
      SF.closeDialog(drawer());
    });
    SF.on(doc, 'submit', '.js-add-to-cart-form', function (event, form) {
      event.preventDefault();
      var data = SF.formData(form);
      var button = event.submitter || form.querySelector('.js-add-to-cart') || form.querySelector('[type="submit"]');
      add(data.size_id, parseInt(data.qty, 10) || 1, button);
    });
    SF.on(doc, 'click', '[data-cart-inc]', function (event, button) {
      var line = lineOf(button);
      var form = button.closest('form');
      if (line) {
        setQty(line, (parseInt(line.input.value, 10) || 1) + 1, form);
      }
    });
    SF.on(doc, 'click', '[data-cart-dec]', function (event, button) {
      var line = lineOf(button);
      var form = button.closest('form');
      if (line) {
        setQty(line, (parseInt(line.input.value, 10) || 1) - 1, form);
      }
    });
    SF.on(doc, 'change', '[data-cart-qty]', function (event, input) {
      var line = lineOf(input);
      var max = parseInt((input.closest('.js-qty') || input).getAttribute('data-max') || '10', 10);
      var qty = Math.min(max, Math.max(0, parseInt(input.value, 10) || 0));
      if (line) {
        setQty(line, qty, input.closest('form'));
      }
    });
    SF.on(doc, 'click', '[data-cart-remove]', function (event, button) {
      var line = lineOf(button);
      var form = button.closest('form');
      if (line) {
        event.preventDefault();
        remove(line, form);
      }
    });
    SF.on(doc, 'submit', '.js-coupon-form', function (event, form) {
      event.preventDefault();
      var data = SF.formData(form);
      var button = event.submitter || form.querySelector('[type="submit"]');
      var field = form.querySelector('.field');
      var error = form.querySelector('.field__error');
      SF.setLoading(button, true);
      SF.fetch('/api/cart/coupon', { method: 'POST', body: { action: data.action || 'apply', code: data.code || '' } }).then(function (result) {
        SF.setLoading(button, false);
        if (result.ok) {
          if (result.data.message) {
            SF.toast(result.data.message, { type: 'success' });
          }
          if (!reloadIfPage(form) && Array.isArray(result.data.lines)) {
            render(result.data);
          }
          return;
        }
        var message = (result.data && result.data.message) || 'That coupon code isn’t valid.';
        if (field && error) {
          field.classList.add('has-error');
          error.textContent = message;
          var input = field.querySelector('.field__input');
          if (input) {
            input.setAttribute('aria-invalid', 'true');
            input.focus();
          }
        } else {
          SF.toast(message, { type: 'error' });
        }
      });
    });
    var panel = drawer();
    if (panel) {
      var bodyEl = panel.querySelector('.js-cart-body');
      if (bodyEl && bodyEl.getAttribute('data-count') === '0') {
        synced = true;
      }
    }
  }
  SF.cart = { add: add, open: openDrawer, refresh: function () { return request('/api/cart'); }, render: render };
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
