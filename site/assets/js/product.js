(function () {
  'use strict';
  var SF = window.SF;
  var doc = document;

  function readSizes() {
    var node = doc.getElementById('sf-sizes');
    if (!node) {
      return null;
    }
    try {
      return JSON.parse(node.textContent || '{}');
    } catch (error) {
      return null;
    }
  }
  function setText(selector, value) {
    SF.qsa(selector).forEach(function (el) {
      el.textContent = value == null ? '' : String(value);
    });
  }
  function applySize(size, product) {
    if (!size) {
      return;
    }
    var onSale = !!size.sale_price_display;
    SF.qsa('.js-price-block').forEach(function (block) {
      block.classList.toggle('price--sale', onSale);
    });
    setText('[data-price-now]', onSale ? size.sale_price_display : size.price_display);
    SF.qsa('[data-price-was]').forEach(function (el) {
      el.hidden = !onSale;
      el.textContent = onSale ? size.price_display : '';
    });
    SF.qsa('[data-save-pill]').forEach(function (el) {
      el.hidden = !onSale || !size.save_percent;
      el.textContent = size.save_percent ? 'Save ' + size.save_percent + '%' : '';
    });
    setText('[data-sku]', size.sku || '');
    var stock = parseInt(size.stock, 10) || 0;
    SF.qsa('[data-stock-line]').forEach(function (el) {
      el.classList.remove('stock-line--low', 'stock-line--out');
      if (stock <= 0) {
        el.classList.add('stock-line--out');
        el.textContent = 'Out of stock';
      } else if (stock < 10) {
        el.classList.add('stock-line--low');
        el.textContent = 'Only ' + stock + ' left';
      } else {
        el.textContent = 'In stock';
      }
    });
    var max = Math.max(0, Math.min(10, stock));
    SF.qsa('.js-qty[data-product-qty]').forEach(function (group) {
      group.setAttribute('data-max', String(max || 1));
      var input = group.querySelector('.qty__input');
      if (input && parseInt(input.value, 10) > max) {
        input.value = String(Math.max(1, max));
      }
      syncQtyButtons(group);
    });
    SF.qsa('.js-add-to-cart').forEach(function (button) {
      var label = button.querySelector('.btn__label') || button;
      button.disabled = stock <= 0;
      button.setAttribute('aria-disabled', stock <= 0 ? 'true' : 'false');
      if (button.hasAttribute('data-label-add')) {
        label.textContent = stock <= 0 ? (button.getAttribute('data-label-sold-out') || 'Sold Out') : button.getAttribute('data-label-add');
      }
    });
    SF.qsa('[data-size-input-mirror]').forEach(function (el) {
      el.value = size.id;
    });
    setText('[data-sticky-size]', size.label);
    setText('[data-sticky-price]', onSale ? size.sale_price_display : size.price_display);
    SF.qsa('.js-whatsapp-order').forEach(function (link) {
      var template = stock <= 0 ? link.getAttribute('data-wa-out') : link.getAttribute('data-wa-in');
      if (!template) {
        return;
      }
      var text = template.replace('{size}', size.label).replace('{price}', onSale ? size.sale_price_display : size.price_display);
      link.href = (link.getAttribute('data-wa-base') || link.href.split('?')[0]) + '?text=' + encodeURIComponent(text);
    });
    SF.qsa('[data-sticky-whatsapp]').forEach(function (el) {
      el.hidden = stock > 0;
    });
    SF.qsa('[data-sticky-add]').forEach(function (el) {
      el.hidden = stock <= 0;
    });
    doc.dispatchEvent(new CustomEvent('sf:size-change', { detail: { size: size, product: product } }));
  }
  function syncQtyButtons(group) {
    var input = group.querySelector('.qty__input');
    var dec = group.querySelector('[data-qty-dec]');
    var inc = group.querySelector('[data-qty-inc]');
    var max = parseInt(group.getAttribute('data-max') || '10', 10);
    var value = Math.max(1, Math.min(max, parseInt(input.value, 10) || 1));
    input.value = String(value);
    if (dec) {
      dec.disabled = value <= 1;
    }
    if (inc) {
      inc.disabled = value >= max;
    }
    var maxNote = group.parentNode ? group.parentNode.querySelector('[data-qty-max]') : null;
    if (maxNote) {
      maxNote.hidden = value < max;
      maxNote.textContent = 'Max ' + max;
    }
  }
  function initSizes() {
    var data = readSizes();
    var inputs = SF.qsa('[data-size-input]');
    if (!data || !inputs.length) {
      return;
    }
    var sizes = data.sizes || data;
    function byId(id) {
      for (var i = 0; i < sizes.length; i++) {
        if (String(sizes[i].id) === String(id)) {
          return sizes[i];
        }
      }
      return null;
    }
    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        var picker = input.closest('.size-picker');
        if (picker) {
          picker.classList.remove('has-error');
        }
        applySize(byId(input.value), data.product);
      });
    });
    var checked = inputs.filter(function (i) { return i.checked; })[0];
    if (checked) {
      applySize(byId(checked.value), data.product);
    }
    SF.on(doc, 'click', '.js-open-sizes', function (event) {
      event.preventDefault();
      var picker = SF.qs('.size-picker');
      if (picker) {
        picker.scrollIntoView({ behavior: SF.reducedMotion() ? 'auto' : 'smooth', block: 'center' });
        var first = picker.querySelector('[data-size-input]:checked') || picker.querySelector('[data-size-input]:not(:disabled)');
        if (first) {
          first.focus({ preventScroll: true });
        }
      }
    });
  }
  function initQty() {
    SF.qsa('.js-qty').forEach(syncQtyButtons);
    SF.on(doc, 'click', '[data-qty-inc]', function (event, button) {
      var group = button.closest('.js-qty');
      var input = group.querySelector('.qty__input');
      input.value = String((parseInt(input.value, 10) || 1) + 1);
      syncQtyButtons(group);
    });
    SF.on(doc, 'click', '[data-qty-dec]', function (event, button) {
      var group = button.closest('.js-qty');
      var input = group.querySelector('.qty__input');
      input.value = String((parseInt(input.value, 10) || 1) - 1);
      syncQtyButtons(group);
    });
    SF.on(doc, 'change', '.js-qty .qty__input', function (event, input) {
      syncQtyButtons(input.closest('.js-qty'));
    });
  }

  function initGallery() {
    var gallery = SF.qs('.js-gallery');
    if (!gallery) {
      return;
    }
    var main = gallery.querySelector('[data-gallery-main]');
    var img = main ? main.querySelector('.gallery__img') : null;
    var source = main ? main.querySelector('source') : null;
    var thumbs = SF.qsa('[data-gallery-thumb]', gallery);
    var rail = gallery.querySelector('[data-gallery-rail]');
    var dots = SF.qsa('[data-gallery-dot]', gallery);
    var current = 0;
    function show(index, fromThumb) {
      var thumb = thumbs[index];
      if (!thumb || !img) {
        return;
      }
      current = index;
      thumbs.forEach(function (t, i) {
        t.classList.toggle('is-active', i === index);
        t.setAttribute('aria-current', i === index ? 'true' : 'false');
      });
      if (thumb.getAttribute('data-src') === img.getAttribute('src')) {
        return;
      }
      main.classList.add('is-swapping');
      var next = new Image();
      next.onload = function () {
        img.src = thumb.getAttribute('data-src');
        if (thumb.getAttribute('data-srcset')) {
          img.srcset = thumb.getAttribute('data-srcset');
        }
        if (source && thumb.getAttribute('data-webp')) {
          source.srcset = thumb.getAttribute('data-webp');
        }
        img.alt = thumb.getAttribute('data-alt') || img.alt;
        main.classList.remove('is-swapping');
      };
      next.onerror = function () { main.classList.remove('is-swapping'); };
      next.src = thumb.getAttribute('data-src');
      if (fromThumb) {
        main.setAttribute('data-index', String(index));
      }
    }
    thumbs.forEach(function (thumb, index) {
      thumb.addEventListener('click', function (event) {
        event.preventDefault();
        show(index, true);
      });
      thumb.addEventListener('focus', function () { show(index, true); });
    });
    if (rail && dots.length) {
      var slides = SF.qsa('.gallery__slide', rail);
      var ticking = false;
      rail.addEventListener('scroll', function () {
        if (ticking) {
          return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
          var index = Math.round(rail.scrollLeft / Math.max(1, rail.clientWidth));
          dots.forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === index);
            dot.setAttribute('aria-current', i === index ? 'true' : 'false');
          });
          ticking = false;
        });
      }, { passive: true });
      dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () {
          if (slides[i]) {
            rail.scrollTo({ left: slides[i].offsetLeft, behavior: SF.reducedMotion() ? 'auto' : 'smooth' });
          }
        });
      });
    }
    if (main && window.matchMedia('(hover: hover) and (pointer: fine)').matches && !SF.reducedMotion()) {
      var frame = null;
      main.addEventListener('mousemove', function (event) {
        if (frame) {
          return;
        }
        frame = window.requestAnimationFrame(function () {
          var rect = main.getBoundingClientRect();
          main.style.setProperty('--mx', ((event.clientX - rect.left) / rect.width * 100).toFixed(1) + '%');
          main.style.setProperty('--my', ((event.clientY - rect.top) / rect.height * 100).toFixed(1) + '%');
          frame = null;
        });
      });
      main.addEventListener('mouseleave', function () {
        main.style.setProperty('--mx', '50%');
        main.style.setProperty('--my', '50%');
      });
    }
    var lightbox = doc.getElementById('lightbox');
    if (lightbox) {
      var lbRail = lightbox.querySelector('.lightbox__rail');
      var lbSlides = SF.qsa('.lightbox__slide', lightbox);
      var counter = lightbox.querySelector('.lightbox__counter');
      function openAt(index) {
        SF.openDialog(lightbox, { trigger: doc.activeElement });
        window.requestAnimationFrame(function () {
          if (lbRail && lbSlides[index]) {
            lbRail.scrollTo({ left: lbSlides[index].offsetLeft, behavior: 'auto' });
          }
        });
      }
      SF.on(gallery, 'click', '[data-lightbox-open]', function (event, trigger) {
        event.preventDefault();
        openAt(parseInt(trigger.getAttribute('data-lightbox-open') || String(current), 10) || 0);
      });
      if (lbRail) {
        lbRail.addEventListener('scroll', function () {
          if (counter) {
            counter.textContent = (Math.round(lbRail.scrollLeft / Math.max(1, lbRail.clientWidth)) + 1) + ' / ' + lbSlides.length;
          }
        }, { passive: true });
      }
      SF.on(lightbox, 'dblclick', '.lightbox__img', function (event, image) {
        image.classList.toggle('is-zoomed');
      });
    }
  }
  function initReviews() {
    SF.on(doc, 'click', '.js-show-all-reviews', function (event, button) {
      var list = doc.getElementById(button.getAttribute('aria-controls') || '');
      if (list) {
        list.classList.remove('is-collapsed');
        button.hidden = true;
      }
    });
  }
  function init() {
    initSizes();
    initQty();
    initGallery();
    initReviews();
  }
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
