(function () {
  'use strict';
  var SF = window.SF;
  var doc = document;
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  function fieldOf(control) {
    return control.closest('.field, .size-picker, .star-input-group');
  }
  function setError(control, message) {
    var field = fieldOf(control);
    if (!field) {
      return;
    }
    var error = field.querySelector('.field__error, .size-picker__error');
    field.classList.toggle('has-error', !!message);
    control.setAttribute('aria-invalid', message ? 'true' : 'false');
    if (error) {
      error.textContent = message || '';
    }
  }
  function messageFor(control) {
    if (control.validity.valueMissing) {
      return control.getAttribute('data-msg-required') || 'This field is required.';
    }
    if (control.type === 'email' && !EMAIL.test(control.value.trim())) {
      return control.getAttribute('data-msg-email') || 'Enter a valid email address.';
    }
    if (control.validity.tooShort) {
      return control.getAttribute('data-msg-short') || ('Enter at least ' + control.minLength + ' characters.');
    }
    if (control.validity.tooLong) {
      return control.getAttribute('data-msg-long') || ('Use at most ' + control.maxLength + ' characters.');
    }
    if (control.validity.patternMismatch) {
      return control.getAttribute('data-msg-pattern') || 'Check the format of this field.';
    }
    return '';
  }
  function validateControl(control) {
    if (control.type === 'hidden' || control.disabled || control.closest('.field__honeypot')) {
      return true;
    }
    var message = '';
    if (control.type === 'radio') {
      var group = control.form ? control.form.querySelectorAll('input[name="' + control.name + '"]') : [];
      var any = Array.prototype.some.call(group, function (radio) { return radio.checked; });
      if (control.required && !any) {
        message = control.getAttribute('data-msg-required') || 'Choose an option.';
      }
    } else if (control.willValidate && !control.checkValidity()) {
      message = messageFor(control);
    } else if (control.type === 'email' && control.value && !EMAIL.test(control.value.trim())) {
      message = control.getAttribute('data-msg-email') || 'Enter a valid email address.';
    }
    setError(control, message);
    return !message;
  }
  function validateForm(form) {
    var controls = SF.qsa('input, select, textarea', form);
    var firstInvalid = null;
    var seen = {};
    controls.forEach(function (control) {
      if (control.type === 'radio' && seen[control.name]) {
        return;
      }
      seen[control.name] = true;
      if (!validateControl(control) && !firstInvalid) {
        firstInvalid = control;
      }
    });
    var summary = form.querySelector('.js-form-summary');
    if (summary) {
      summary.hidden = !firstInvalid;
      if (firstInvalid) {
        var links = SF.qsa('[aria-invalid="true"]', form).map(function (c) {
          var label = form.querySelector('label[for="' + c.id + '"]');
          return '<a class="form__summary-link" href="#' + SF.escapeHtml(c.id) + '">' + SF.escapeHtml(label ? label.textContent.trim() : c.name) + '</a>';
        });
        summary.innerHTML = '<p class="form__summary-title">Please check the highlighted fields.</p>' + links.join(' · ');
      }
    }
    if (firstInvalid) {
      firstInvalid.focus();
      firstInvalid.scrollIntoView({ behavior: SF.reducedMotion() ? 'auto' : 'smooth', block: 'center' });
    }
    return !firstInvalid;
  }
  function initValidation() {
    SF.qsa('form.js-validate').forEach(function (form) {
      form.setAttribute('novalidate', 'novalidate');
      form.addEventListener('submit', function (event) {
        if (!validateForm(form)) {
          event.preventDefault();
          return;
        }
        var button = event.submitter || form.querySelector('[type="submit"]');
        if (button && !form.classList.contains('js-newsletter-form') && !form.classList.contains('js-coupon-form')) {
          SF.setLoading(button, true);
          window.setTimeout(function () { SF.setLoading(button, false); }, 15000);
        }
      });
      SF.on(form, 'blur', 'input, select, textarea', function (event, control) {
        if (control.value !== '' || control.getAttribute('aria-invalid') === 'true') {
          validateControl(control);
        }
      });
      SF.on(form, 'input', '[aria-invalid="true"]', function (event, control) {
        validateControl(control);
      });
    });
    SF.qsa('[data-maxlength-count]').forEach(function (control) {
      var counter = doc.getElementById(control.getAttribute('data-maxlength-count'));
      if (!counter) {
        return;
      }
      var update = function () {
        counter.textContent = control.value.length + ' / ' + control.maxLength;
      };
      control.addEventListener('input', update);
      update();
    });
  }

  function initNewsletter() {
    SF.qsa('.js-newsletter-form').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var input = form.querySelector('input[type="email"]');
        if (input && !validateControl(input)) {
          input.focus();
          return;
        }
        var button = event.submitter || form.querySelector('[type="submit"]');
        SF.setLoading(button, true);
        if (input) {
          input.readOnly = true;
        }
        SF.fetch('/api/newsletter', { method: 'POST', body: SF.formData(form) }).then(function (result) {
          SF.setLoading(button, false);
          if (input) {
            input.readOnly = false;
          }
          if (result.ok) {
            var success = doc.createElement('div');
            success.className = 'newsletter__success';
            success.setAttribute('role', 'status');
            success.setAttribute('tabindex', '-1');
            success.innerHTML = '<svg class="newsletter__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg><p class="newsletter__success-title">Thank you — you’re on the list.</p><p class="text-small text-muted">' + SF.escapeHtml(result.data.message || 'We only write when there is something worth opening.') + '</p>';
            form.classList.add('is-done');
            form.parentNode.insertBefore(success, form);
            success.focus();
            return;
          }
          if (result.data && result.data.error === 'email' && input) {
            setError(input, result.data.message || 'Enter a valid email address.');
            input.focus();
            return;
          }
          SF.toast((result.data && result.data.message) || 'We could not sign you up right now. Please try again.', { type: 'error' });
        });
      });
    });
  }
  function initSuggest() {
    SF.qsa('.js-suggest-input').forEach(function (input) {
      var panel = doc.getElementById(input.getAttribute('data-suggest-target') || '');
      if (!panel) {
        return;
      }
      var timer = null;
      var active = -1;
      var lastQuery = '';
      input.setAttribute('autocomplete', 'off');
      input.setAttribute('aria-expanded', 'false');
      function close() {
        panel.classList.remove('is-open');
        panel.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        active = -1;
      }
      function items() {
        return SF.qsa('.suggest__item', panel);
      }
      function render(data) {
        var html = '';
        if (data.products && data.products.length) {
          html += '<div class="suggest__group"><p class="suggest__label">Fragrances</p>' + data.products.map(function (p) {
            var media = p.image ? '<img class="suggest__img" src="' + SF.escapeHtml(p.image) + '" alt="" loading="lazy" decoding="async">' : '';
            return '<a class="suggest__item" href="' + SF.escapeHtml(p.url) + '" role="option"><span class="suggest__media">' + media + '</span><span><span class="suggest__name">' + SF.escapeHtml(p.name) + '</span><br><span class="suggest__meta">' + SF.escapeHtml(p.family || '') + '</span></span><span class="suggest__price">' + SF.escapeHtml(p.price || '') + '</span></a>';
          }).join('') + '</div>';
        }
        if (data.collections && data.collections.length) {
          html += '<div class="suggest__group"><p class="suggest__label">Collections</p>' + data.collections.map(function (c) {
            return '<a class="suggest__item suggest__item--plain" href="' + SF.escapeHtml(c.url) + '" role="option"><span class="suggest__name">' + SF.escapeHtml(c.name) + '</span></a>';
          }).join('') + '</div>';
        }
        if (!html) {
          html = '<p class="suggest__empty">No matches for “' + SF.escapeHtml(data.q || '') + '”.</p>';
        }
        html += '<a class="suggest__all" href="' + SF.escapeHtml(SF.url('/search?q=' + encodeURIComponent(data.q || ''))) + '">See all results<span aria-hidden="true">→</span></a>';
        panel.innerHTML = html;
        items().forEach(function (item, i) {
          item.id = panel.id + '-opt-' + i;
          item.setAttribute('aria-selected', 'false');
        });
        panel.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
        active = -1;
      }
      input.addEventListener('input', function () {
        var q = input.value.trim();
        window.clearTimeout(timer);
        if (q.length < 3) {
          close();
          return;
        }
        timer = window.setTimeout(function () {
          lastQuery = q;
          panel.innerHTML = '<div class="suggest-skeleton" aria-hidden="true"><span class="skeleton skeleton--line"></span><span class="skeleton skeleton--line-short"></span></div>';
          panel.classList.add('is-open');
          SF.fetch('/api/search-suggest?q=' + encodeURIComponent(q)).then(function (result) {
            if (result.ok && lastQuery === q) {
              render(result.data);
            } else if (!result.ok) {
              close();
            }
          });
        }, 250);
      });
      input.addEventListener('keydown', function (event) {
        var list = items();
        if (!panel.classList.contains('is-open') || !list.length) {
          if (event.key === 'Escape') {
            close();
          }
          return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          active = event.key === 'ArrowDown' ? Math.min(list.length - 1, active + 1) : Math.max(0, active - 1);
          list.forEach(function (item, i) {
            item.classList.toggle('is-active', i === active);
            item.setAttribute('aria-selected', i === active ? 'true' : 'false');
          });
          input.setAttribute('aria-activedescendant', list[active].id || '');
        } else if (event.key === 'Enter' && active >= 0) {
          event.preventDefault();
          window.location.href = list[active].href;
        } else if (event.key === 'Escape') {
          close();
        }
      });
      doc.addEventListener('click', function (event) {
        if (!panel.contains(event.target) && event.target !== input) {
          close();
        }
      });
    });
  }
  function initUploads() {
    SF.on(doc, 'change', '.js-upload-input', function (event, input) {
      var wrapper = input.closest('.upload');
      var name = wrapper ? wrapper.querySelector('.upload__name') : null;
      var preview = wrapper ? wrapper.querySelector('.upload__preview') : null;
      var file = input.files && input.files[0];
      if (name) {
        name.textContent = file ? file.name : '';
      }
      if (preview && file && file.type.indexOf('image/') === 0 && window.URL) {
        preview.src = window.URL.createObjectURL(file);
        preview.hidden = false;
      }
    });
  }
  function initSortSelect() {
    SF.on(doc, 'change', '.toolbar__sort select', function (event, select) {
      if (!select.form) {
        return;
      }
      if (select.form.requestSubmit) {
        select.form.requestSubmit();
      } else {
        select.form.submit();
      }
    });
  }
  function initPaymentPanel() {
    var panel = SF.qs('.js-manual-panel');
    if (!panel) {
      return;
    }
    function sync() {
      var checked = doc.querySelector('input[name="payment_method"]:checked');
      panel.hidden = !!checked && checked.value === 'cod';
    }
    SF.on(doc, 'change', 'input[name="payment_method"]', sync);
    sync();
  }
  function initQuizSteps() {
    var form = SF.qs('.js-quiz');
    if (!form) {
      return;
    }
    var steps = SF.qsa('.form__section', form);
    var actions = form.querySelector('.form__actions');
    if (steps.length < 2) {
      return;
    }
    function show(index) {
      steps.forEach(function (step, i) {
        step.classList.toggle('is-active', i === index);
      });
      if (actions) {
        actions.hidden = index !== steps.length - 1;
      }
    }
    SF.on(form, 'click', 'a[href^="#question-"]', function (event, link) {
      var target = doc.getElementById(link.getAttribute('href').slice(1));
      var to = steps.indexOf(target);
      if (to < 0) {
        return;
      }
      event.preventDefault();
      var from = steps.indexOf(link.closest('.form__section'));
      if (to > from) {
        var radio = steps[from].querySelector('input[type="radio"]');
        if (radio && !validateControl(radio)) {
          radio.focus();
          return;
        }
      }
      show(to);
      var legend = steps[to].querySelector('legend');
      if (legend) {
        legend.setAttribute('tabindex', '-1');
        legend.focus({ preventScroll: true });
      }
      steps[to].scrollIntoView({ behavior: SF.reducedMotion() ? 'auto' : 'smooth', block: 'start' });
    });
    show(0);
  }
  function init() {
    initValidation();
    initNewsletter();
    initSuggest();
    initUploads();
    initSortSelect();
    initPaymentPanel();
    initQuizSteps();
  }
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
