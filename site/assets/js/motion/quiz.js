const doc = document;

function fillPercent(step) {
  const fill = step.querySelector('.progress__fill');
  const value = fill ? parseFloat(fill.style.getPropertyValue('--fill')) : NaN;
  return Number.isFinite(value) && value > 0 ? value : 0;
}

function scrollIfAbove(motion, el) {
  const rect = el.getBoundingClientRect();
  const headerH = parseFloat(getComputedStyle(doc.documentElement).getPropertyValue('--header-h')) || 0;
  if (rect.top < headerH) {
    const go = (window.SF && typeof window.SF.scrollTo === 'function') ? window.SF.scrollTo : motion.scrollTo;
    go(el, { block: 'start' });
  }
}

function initSteps(form, motion, cfg) {
  const deck = form.querySelector('.quiz-deck') || form;
  const steps = Array.from(form.querySelectorAll('.form__section'));
  const actions = form.querySelector('.form__actions');
  const live = form.querySelector('.js-quiz-live');
  if (steps.length < 2) {
    return null;
  }
  let busy = false;
  let armTimer = 0;
  let armed = null;
  let pointer = { label: null, at: 0 };
  const current = () => steps.findIndex((step) => step.classList.contains('is-active'));
  const answered = (step) => !!step.querySelector('input[type="radio"]:checked');
  const useGsap = () => motion.device === 'desktop' && !!motion.gsap && !motion.reduced;
  const position = (index) => 'Question ' + (index + 1) + ' of ' + steps.length;
  const announce = (text) => {
    if (live) {
      live.textContent = text;
    }
  };

  function describeFlow() {
    const hint = doc.createElement('p');
    hint.className = 'u-sr-only';
    hint.id = form.id ? form.id + '-flow-hint' : 'sf-quiz-flow-hint';
    hint.textContent = 'Choosing an answer moves to the next question.';
    form.prepend(hint);
    steps.forEach((step, index) => {
      const group = step.querySelector('[role="radiogroup"]');
      if (group) {
        const described = (group.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        described.push(hint.id);
        group.setAttribute('aria-describedby', described.join(' '));
      }
      const legend = step.querySelector('legend');
      if (legend && !legend.querySelector('.js-quiz-position')) {
        const label = doc.createElement('span');
        label.className = 'u-sr-only js-quiz-position';
        label.textContent = position(index) + '. ';
        legend.prepend(label);
      }
    });
    announce(position(Math.max(0, current())));
  }

  function disarm() {
    clearTimeout(armTimer);
    if (armed) {
      armed.classList.remove('is-armed');
      armed = null;
    }
  }

  function finish(leaving, entering, index) {
    leaving.classList.remove('is-leaving');
    entering.classList.remove('is-entering');
    deck.classList.remove('is-flipping');
    entering.style.removeProperty('--sf-quiz-fill-from');
    if (motion.gsap) {
      motion.gsap.set([leaving, entering], { clearProps: 'all' });
    }
    busy = false;
    const legend = entering.querySelector('legend');
    if (legend) {
      legend.setAttribute('tabindex', '-1');
      legend.focus({ preventScroll: true });
    }
    announce(position(index));
    motion.refresh();
  }

  function flipGsap(leaving, entering, dir, index) {
    const gsap = motion.gsap;
    const out = cfg.flipOut || { ms: 380, ease: 'power3.in', rotate: -90 };
    const inn = cfg.flipIn || { ms: 520, ease: 'expo.out', rotate: 90, overlapMs: 40 };
    gsap.set([leaving, entering], { transformPerspective: cfg.perspective || 1400 });
    gsap.set(entering, { rotationY: dir * inn.rotate, x: dir * 48, autoAlpha: 0 });
    gsap.timeline({ onComplete: () => finish(leaving, entering, index) })
      .to(leaving, { rotationY: dir * out.rotate, x: dir * -48, autoAlpha: 0, duration: out.ms / 1000, ease: out.ease })
      .to(entering, { rotationY: 0, x: 0, autoAlpha: 1, duration: inn.ms / 1000, ease: inn.ease }, '>-' + (inn.overlapMs || 0) / 1000);
  }

  function slideCss(leaving, entering, dir, index) {
    const slide = cfg.slide || { ms: 320, fallbackMs: 400 };
    let done = false;
    const end = () => {
      if (!done) {
        done = true;
        clearTimeout(timer);
        finish(leaving, entering, index);
      }
    };
    deck.style.setProperty('--sf-quiz-dir', String(dir));
    entering.addEventListener('animationend', end, { once: true });
    const timer = setTimeout(end, motion.reduced ? 0 : Math.max(slide.ms, slide.fallbackMs));
  }

  function go(to) {
    const from = current();
    if (busy || from < 0 || to < 0 || to === from) {
      return;
    }
    disarm();
    busy = true;
    const leaving = steps[from];
    const entering = steps[to];
    const dir = to > from ? 1 : -1;
    const before = fillPercent(leaving);
    const after = fillPercent(entering);
    if (before && after) {
      entering.style.setProperty('--sf-quiz-fill-from', String(before / after));
    }
    deck.classList.add('is-flipping');
    leaving.classList.add('is-leaving');
    leaving.classList.remove('is-active');
    entering.classList.add('is-active', 'is-entering');
    if (actions) {
      actions.hidden = to !== steps.length - 1;
    }
    scrollIfAbove(motion, entering);
    if (useGsap()) {
      flipGsap(leaving, entering, dir, to);
    } else {
      slideCss(leaving, entering, dir, to);
    }
  }

  function arm(step, index) {
    disarm();
    armed = step;
    step.classList.add('is-armed');
    announce('Answer saved. Next question in a moment; press any key to stay.');
    armTimer = setTimeout(() => {
      step.classList.remove('is-armed');
      armed = null;
      if (current() === index && answered(step) && !busy) {
        go(index + 1);
      }
    }, cfg.undoMs || 600);
  }

  function onAnchor(event) {
    const link = event.target.closest ? event.target.closest('a[href^="#question-"]') : null;
    if (!link || !form.contains(link)) {
      return;
    }
    const to = steps.indexOf(doc.getElementById(link.getAttribute('href').slice(1)));
    const from = current();
    if (to < 0 || (to > from && !answered(steps[from]))) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    go(to);
  }

  function onPointerUp(event) {
    const label = event.target.closest ? event.target.closest('.choice--answer') : null;
    pointer = { label, at: performance.now() };
  }

  function onPointerDown(event) {
    if (!(event.target.closest && event.target.closest('.choice--answer'))) {
      disarm();
    }
  }

  function onChoiceClick(event) {
    if (!event.target.matches || !event.target.matches('.choice__input')) {
      return;
    }
    const label = event.target.closest('.choice--answer');
    const recent = pointer.label === label && performance.now() - pointer.at < (cfg.pointerWindowMs || 400);
    if (!label || !recent) {
      return;
    }
    const step = event.target.closest('.form__section');
    const index = steps.indexOf(step);
    if (index < 0 || index >= steps.length - 1) {
      return;
    }
    arm(step, index);
  }

  describeFlow();
  form.addEventListener('click', onAnchor, true);
  form.addEventListener('pointerup', onPointerUp, true);
  doc.addEventListener('pointerdown', onPointerDown, true);
  doc.addEventListener('keydown', disarm, true);
  form.addEventListener('click', onChoiceClick);
  return {
    destroy() {
      disarm();
      form.removeEventListener('click', onAnchor, true);
      form.removeEventListener('pointerup', onPointerUp, true);
      doc.removeEventListener('pointerdown', onPointerDown, true);
      doc.removeEventListener('keydown', disarm, true);
      form.removeEventListener('click', onChoiceClick);
    }
  };
}

function tickMatch(el, tickCfg, startAt) {
  const twin = el.querySelector('.quiz-match__tick');
  const target = parseInt(el.getAttribute('data-match') || '0', 10);
  const now = performance.now();
  if (!twin || !(target > 0) || now > startAt + (tickCfg.lateMs || 400)) {
    return;
  }
  twin.style.minWidth = String(target).length + 'ch';
  const ms = tickCfg.ms || 900;
  const ease = (t) => 1 - Math.pow(2, -10 * t);
  const frame = (time) => {
    const progress = Math.min(1, Math.max(0, (time - startAt) / ms));
    twin.textContent = String(Math.round(target * ease(progress)));
    if (progress < 1) {
      requestAnimationFrame(frame);
    }
  };
  twin.textContent = '0';
  requestAnimationFrame(frame);
}

function initResult(root, motion, cfg) {
  const result = cfg.result || {};
  const card = root.querySelector('.quiz-card');
  const inner = card ? card.querySelector('.quiz-card__inner') : null;
  const img = card ? card.querySelector('img') : null;
  const match = root.querySelector('[data-match]');
  const desktop = motion.device === 'desktop';
  let waitTimer = 0;
  if (card && inner && desktop) {
    const release = () => {
      clearTimeout(waitTimer);
      card.classList.remove('is-waiting');
    };
    if (img && !img.complete) {
      card.classList.add('is-waiting');
      img.addEventListener('load', release, { once: true });
      img.addEventListener('error', release, { once: true });
      waitTimer = setTimeout(release, result.imageWaitMs || 1500);
    }
    const onTurned = (event) => {
      if (event.target !== inner || event.animationName !== 'sf-quiz-turn') {
        return;
      }
      inner.removeEventListener('animationend', onTurned);
      card.classList.add('is-revealed');
      motion.refresh();
    };
    inner.addEventListener('animationend', onTurned);
  }
  if (match) {
    const tick = result.tick || { ms: 900, lateMs: 400 };
    tickMatch(match, tick, desktop ? (result.notesAtMs || 1250) : (result.mobileAtMs || 600));
  }
  return {
    destroy() {
      clearTimeout(waitTimer);
    }
  };
}

export default function init(root, SF) {
  const motion = SF && SF.motion;
  if (!motion) {
    return null;
  }
  const cfg = (motion.config && motion.config.quiz) || {};
  return root.matches('form') ? initSteps(root, motion, cfg) : initResult(root, motion, cfg);
}
