(function () {
  'use strict';
  var doc = document;
  var form = doc.querySelector('[data-quiz]');
  if (!form) {
    return;
  }
  var steps = Array.prototype.slice.call(form.querySelectorAll('[data-quiz-step]'));
  if (steps.length < 2) {
    return;
  }
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var live = form.querySelector('.js-quiz-live');
  var current = 0;
  var timer = 0;
  var pointerPick = false;

  function answered(step) {
    return !!step.querySelector('input:checked');
  }
  function setError(step, text) {
    var error = step.querySelector('.field__error');
    if (error) {
      error.textContent = text;
    }
  }
  function show(index, focus) {
    current = Math.max(0, Math.min(index, steps.length - 1));
    steps.forEach(function (step, i) {
      step.hidden = i !== current;
      step.classList.toggle('is-active', i === current);
    });
    form.classList.toggle('is-last', current === steps.length - 1);
    if (live) {
      live.textContent = 'Question ' + (current + 1) + ' of ' + steps.length;
    }
    if (focus) {
      var target = steps[current].querySelector('input:checked') || steps[current].querySelector('input');
      if (target) {
        target.focus({ preventScroll: true });
      }
      if (form.getBoundingClientRect().top < 0) {
        form.scrollIntoView({ behavior: reduced.matches ? 'auto' : 'smooth', block: 'start' });
      }
    }
  }

  form.classList.add('is-stepped');
  form.addEventListener('pointerdown', function (event) {
    pointerPick = !!event.target.closest('.quiz-option');
  });
  form.addEventListener('click', function (event) {
    var link = event.target.closest('[data-quiz-next], [data-quiz-back]');
    if (!link) {
      return;
    }
    event.preventDefault();
    var forward = link.hasAttribute('data-quiz-next');
    if (forward && !answered(steps[current])) {
      setError(steps[current], 'Pick one answer to continue.');
      show(current, true);
      return;
    }
    show(current + (forward ? 1 : -1), true);
  });
  form.addEventListener('change', function (event) {
    if (!event.target.matches('input[type="radio"]')) {
      return;
    }
    setError(steps[current], '');
    window.clearTimeout(timer);
    if (!pointerPick) {
      return;
    }
    pointerPick = false;
    if (current < steps.length - 1) {
      timer = window.setTimeout(function () {
        show(current + 1, true);
      }, reduced.matches ? 0 : 450);
    } else {
      var submit = form.querySelector('[type="submit"]');
      if (submit) {
        submit.focus();
      }
    }
  });
  form.addEventListener('submit', function (event) {
    for (var i = 0; i < steps.length; i++) {
      if (!answered(steps[i])) {
        event.preventDefault();
        event.stopPropagation();
        setError(steps[i], 'Pick one answer to continue.');
        show(i, true);
        return;
      }
    }
  });
  show(0, false);
})();
