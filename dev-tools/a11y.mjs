import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs';
import { readFileSync, writeFileSync } from 'node:fs';

const BASE = (process.argv[2] || process.env.BASE || 'http://127.0.0.1:8103').replace(/\/$/, '');
const EXPECT_THEME = process.env.EXPECT_THEME || '';
const OUT = process.argv[3] || process.env.OUT || (EXPECT_THEME === 'light' ? '/tmp/skyfr-a11y--light.json' : '/tmp/skyfr-a11y.json');
const TAB_ORDER_REF = process.env.TAB_ORDER_REF || '';
const themeIssues = new Map();
const ONLY = process.env.ONLY ? process.env.ONLY.split(',') : null;
const PAGES = ['/', '/shop', '/collections', '/for-her', '/product/azure-oud', '/scent-finder', '/cart', '/checkout', '/track', '/contact', '/faq', '/about'];
const MAX_TABS = 600;

const HELPERS = `(() => {
  const A = window.__a11y = {};
  const vis = (el) => {
    if (!el || !(el instanceof Element)) return false;
    if (el.closest('[hidden]')) return false;
    let n = el;
    while (n && n !== document.documentElement) {
      const cs = getComputedStyle(n);
      if (cs.display === 'none' || cs.visibility === 'hidden') return false;
      n = n.parentElement;
    }
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  };
  A.vis = vis;
  A.srOnly = (el) => { const cs = getComputedStyle(el); return cs.position === 'absolute' && cs.width === '1px' && cs.height === '1px' && cs.overflow === 'hidden'; };
  A.desc = (el) => {
    const id = el.id ? '#' + el.id : '';
    const kept = el.className && typeof el.className === 'string' ? el.className.trim().split(/\\s+/).filter((c) => c && !/^(is|has)-/.test(c)).slice(0, 3) : [];
    const cls = kept.length ? '.' + kept.join('.') : '';
    const text = (el.getAttribute('aria-label') || el.textContent || '').trim().replace(/\\s+/g, ' ').slice(0, 40);
    return el.tagName.toLowerCase() + id + cls + (text ? ' "' + text + '"' : '');
  };
  const parseRgb = (s) => { const m = String(s).match(/rgba?\\(([^)]+)\\)/); if (!m) return null; const p = m[1].split(',').map(v => parseFloat(v)); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 }; };
  A.parseRgb = parseRgb;
  const lum = (c) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(c.r) + 0.7152 * f(c.g) + 0.0722 * f(c.b); };
  const over = (fg, bg) => ({ r: fg.r * fg.a + bg.r * (1 - fg.a), g: fg.g * fg.a + bg.g * (1 - fg.a), b: fg.b * fg.a + bg.b * (1 - fg.a), a: 1 });
  A.ratio = (fg, bg) => { const l1 = lum(fg), l2 = lum(bg); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); };
  A.background = (el) => {
    let n = el;
    let stack = [];
    let opacity = 1;
    while (n && n !== document.documentElement.parentNode) {
      const cs = getComputedStyle(n);
      opacity *= parseFloat(cs.opacity || '1');
      const bg = parseRgb(cs.backgroundColor);
      if (cs.backgroundImage && cs.backgroundImage !== 'none') {
        return { image: cs.backgroundImage.slice(0, 60), node: A.desc(n), stack, opacity };
      }
      if (bg && bg.a > 0) {
        stack.push(bg);
        if (bg.a >= 1) break;
      }
      n = n.parentElement;
    }
    let out = { r: 255, g: 255, b: 255, a: 1 };
    for (let i = stack.length - 1; i >= 0; i--) out = over(stack[i], out);
    return { color: out, opacity };
  };
  A.over = over;
  A.focusableSelector = 'a[href], button, input:not([type="hidden"]), select, textarea, [tabindex], summary, [contenteditable="true"]';
  A.ringState = (el) => {
    const grab = (node, pseudo) => {
      if (!node) return null;
      const cs = getComputedStyle(node, pseudo || null);
      if (pseudo && (cs.content === 'none' || cs.content === 'normal')) return null;
      return { outline: cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0 ? cs.outlineWidth + ' ' + cs.outlineStyle + ' ' + cs.outlineColor : 'none', shadow: cs.boxShadow, border: cs.borderColor, bg: cs.backgroundColor, color: cs.color };
    };
    const out = { self: grab(el), before: grab(el, '::before'), after: grab(el, '::after'), next: grab(el.nextElementSibling), label: grab(el.closest('label')) };
    const parentLabel = el.id ? document.querySelector('label[for="' + CSS.escape(el.id) + '"]') : null;
    out.forLabel = grab(parentLabel);
    return out;
  };
  A.ringChanged = (rest, now) => {
    const keys = ['self', 'before', 'after', 'next', 'label', 'forLabel'];
    for (const k of keys) {
      const a = rest[k], b = now[k];
      if (!b) continue;
      if (b.outline !== 'none' && (!a || a.outline !== b.outline)) return 'outline:' + k;
      if (b.shadow !== 'none' && (!a || a.shadow !== b.shadow)) return 'shadow:' + k;
    }
    return '';
  };
  A.rest = new Map();
  A.snapshotRest = () => {
    A.rest = new Map();
    document.querySelectorAll(A.focusableSelector).forEach((el) => { A.rest.set(el, A.ringState(el)); });
  };
  A.accName = (el) => {
    const byId = (ids) => ids.split(/\\s+/).map(id => document.getElementById(id)).filter(Boolean).map(n => n.textContent.trim()).join(' ').trim();
    if (el.getAttribute('aria-labelledby')) { const t = byId(el.getAttribute('aria-labelledby')); if (t) return t; }
    if (el.getAttribute('aria-label') && el.getAttribute('aria-label').trim()) return el.getAttribute('aria-label').trim();
    if (el.id) { const l = document.querySelector('label[for="' + CSS.escape(el.id) + '"]'); if (l && l.textContent.trim()) return l.textContent.trim(); }
    const wrap = el.closest('label');
    if (wrap && wrap.textContent.trim()) return wrap.textContent.trim();
    const alt = Array.from(el.querySelectorAll('img[alt], svg[aria-label], [role="img"][aria-label]')).map(n => n.getAttribute('alt') || n.getAttribute('aria-label')).join(' ').trim();
    const text = (el.textContent || '').trim();
    if (text) return text;
    if (alt) return alt;
    if (el.getAttribute('title')) return el.getAttribute('title').trim();
    if (el.tagName === 'INPUT' && ['submit', 'button', 'reset'].includes(el.type) && el.value) return el.value;
    return '';
  };
})();`;

async function open(page, path) {
  await page.goto(BASE + path, { waitUntil: 'load' });
  await page.waitForFunction(() => !document.getElementById('sf-intro'), null, { timeout: 9000 }).catch(() => {});
  await page.evaluate(HELPERS);
  const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
  if (EXPECT_THEME && theme !== EXPECT_THEME) themeIssues.set(path, theme);
  await page.waitForTimeout(150);
}

async function tabPass(page, path, result) {
  await page.evaluate(() => { window.scrollTo(0, 0); window.__a11y.snapshotRest(); if (document.activeElement && document.activeElement.blur) document.activeElement.blur(); });
  const seen = new Set();
  const order = [];
  let escapedOnce = false;
  for (let i = 0; i < MAX_TABS; i++) {
    await page.keyboard.press('Tab');
    const info = await page.evaluate(() => {
      const A = window.__a11y;
      const el = document.activeElement;
      if (!el || el === document.body || el === document.documentElement) return { end: true };
      const rest = A.rest.get(el) || A.ringState(el);
      const now = A.ringState(el);
      const key = A.desc(el) + '|' + Array.from(document.querySelectorAll(A.focusableSelector)).indexOf(el);
      return { key, desc: A.desc(el), visible: A.vis(el), focusVisible: el.matches(':focus-visible'), ring: A.ringChanged(rest, now), inDialog: !!el.closest('[role="dialog"]'), now: now.self };
    });
    if (info.end) break;
    if (seen.has(info.key)) {
      if (escapedOnce) break;
      escapedOnce = true;
      break;
    }
    seen.add(info.key);
    order.push(info.desc);
    if (!info.visible) result.fail('focus-hidden', path, `Tab reached an invisible element: ${info.desc}`);
    if (info.inDialog) result.fail('focus-dialog', path, `Tab reached a closed dialog: ${info.desc}`);
    if (info.visible && info.focusVisible && !info.ring) result.fail('focus-ring', path, `No visible focus ring on ${info.desc} (outline ${info.now.outline}, shadow ${info.now.shadow})`);
    if (info.visible && !info.focusVisible) result.fail('focus-visible', path, `:focus-visible did not match on ${info.desc}`);
  }
  result.note(path, `tab order: ${order.length} stops`);
  return order;
}

async function dialogTest(page, path, result, opener, panelSel, label) {
  const hasOpener = await page.$(opener);
  if (!hasOpener) { result.fail('dialog-missing', path, `${label}: opener ${opener} not found`); return; }
  await page.evaluate((sel) => { document.querySelector(sel).focus(); }, opener);
  await page.keyboard.press('Enter');
  await page.waitForSelector(`${panelSel}.is-open`, { timeout: 3000 }).catch(() => {});
  await page.waitForTimeout(250);
  const state = await page.evaluate((sel) => {
    const panel = document.querySelector(sel);
    const el = document.activeElement;
    return { open: !!panel && panel.classList.contains('is-open') && !panel.hidden, ariaHidden: panel && panel.getAttribute('aria-hidden'), focusInside: !!panel && panel.contains(el), focused: window.__a11y.desc(el), role: panel && panel.getAttribute('role'), modal: panel && panel.getAttribute('aria-modal'), label: panel && (panel.getAttribute('aria-label') || panel.getAttribute('aria-labelledby')), locked: document.body.classList.contains('is-locked') };
  }, panelSel);
  if (!state.open) { result.fail('dialog-open', path, `${label}: did not open via keyboard`); return; }
  if (!state.focusInside) result.fail('dialog-focus', path, `${label}: focus not moved inside on open (on ${state.focused})`);
  if (state.role !== 'dialog' || state.modal !== 'true' || !state.label) result.fail('dialog-semantics', path, `${label}: role=${state.role} aria-modal=${state.modal} label=${state.label}`);
  if (!state.locked) result.fail('dialog-lock', path, `${label}: body scroll not locked`);
  let escaped = 0;
  for (let i = 0; i < 24; i++) {
    await page.keyboard.press(i % 7 === 6 ? 'Shift+Tab' : 'Tab');
    const inside = await page.evaluate((sel) => { const p = document.querySelector(sel); return p.contains(document.activeElement) ? '' : window.__a11y.desc(document.activeElement); }, panelSel);
    if (inside) { escaped++; result.fail('dialog-trap', path, `${label}: Tab #${i + 1} escaped to ${inside}`); break; }
  }
  const ringInside = await page.evaluate((sel) => {
    const A = window.__a11y; const el = document.activeElement; const p = document.querySelector(sel);
    if (!p.contains(el)) return { ok: true };
    const rest = A.rest.get(el) || { self: { outline: 'none', shadow: 'none' } };
    return { ok: !!A.ringChanged(rest, A.ringState(el)) || !el.matches(':focus-visible'), desc: A.desc(el) };
  }, panelSel);
  if (!ringInside.ok) result.fail('focus-ring', path, `${label}: no focus ring on ${ringInside.desc}`);
  await page.keyboard.press('Escape');
  await page.waitForFunction((sel) => { const p = document.querySelector(sel); return p && p.hidden; }, panelSel, { timeout: 3000 }).catch(() => {});
  await page.waitForTimeout(80);
  const after = await page.evaluate(([sel, op]) => {
    const p = document.querySelector(sel); const t = document.querySelector(op);
    return { closed: p.hidden && !p.classList.contains('is-open'), ariaHidden: p.getAttribute('aria-hidden'), returned: document.activeElement === t, focused: window.__a11y.desc(document.activeElement), expanded: t.getAttribute('aria-expanded'), locked: document.body.classList.contains('is-locked') };
  }, [panelSel, opener]);
  if (!after.closed) result.fail('dialog-escape', path, `${label}: Escape did not close`);
  if (!after.returned) result.fail('dialog-return', path, `${label}: focus not returned to opener (on ${after.focused})`);
  if (after.expanded === 'true') result.fail('dialog-expanded', path, `${label}: aria-expanded still true after close`);
  if (after.locked) result.fail('dialog-lock', path, `${label}: body still locked after close`);
  if (escaped === 0 && after.closed && after.returned) result.note(path, `${label}: trap, Escape and focus return OK`);
}

async function spaceKeyTest(page, path, result, opener, panelSel, label) {
  await open(page, path);
  await page.evaluate((sel) => { const t = document.querySelector(sel); if (t) t.focus(); }, opener);
  await page.keyboard.press('Space');
  await page.waitForTimeout(250);
  const opened = await page.evaluate((sel) => { const p = document.querySelector(sel); return !!p && p.classList.contains('is-open'); }, panelSel);
  if (!opened) result.fail('space-key', path, `${label}: Space on the role=button trigger did not open it`);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(150);
}

async function searchTest(page, path, result) {
  await open(page, path);
  await page.evaluate(() => window.__a11y.snapshotRest());
  await page.evaluate(() => { const t = document.querySelector('.js-search-open'); if (t) t.focus(); });
  await page.keyboard.press('Enter');
  await page.waitForTimeout(200);
  const state = await page.evaluate(() => {
    const A = window.__a11y;
    const overlay = document.getElementById('site-search');
    const el = document.activeElement;
    const rest = A.rest.get(el) || { self: { outline: 'none', shadow: 'none' } };
    return { open: !!overlay && !overlay.hidden, onInput: el && el.id === 'header-search', ring: A.ringChanged(rest, A.ringState(el)), expanded: document.querySelector('.js-search-open').getAttribute('aria-expanded') };
  });
  if (!state.open || !state.onInput) { result.fail('search-open', path, `header search did not open on the input (open=${state.open}, onInput=${state.onInput})`); return; }
  if (!state.ring) result.fail('focus-ring', path, 'No visible focus ring on the header search input');
  if (state.expanded !== 'true') result.fail('search-expanded', path, 'search trigger aria-expanded not true while open');
  await page.keyboard.press('Escape');
  await page.waitForTimeout(150);
  const after = await page.evaluate(() => ({ closed: document.getElementById('site-search').hidden, returned: document.activeElement === document.querySelector('.js-search-open'), expanded: document.querySelector('.js-search-open').getAttribute('aria-expanded') }));
  if (!after.closed || !after.returned || after.expanded !== 'false') result.fail('search-escape', path, `Escape on search: closed=${after.closed} returned=${after.returned} expanded=${after.expanded}`);
  else result.note(path, 'header search: opens on input with ring, Escape closes and returns focus');
}

async function staticChecks(page, path, result) {
  const found = await page.evaluate(() => {
    const A = window.__a11y;
    const out = { names: [], alts: [], headings: [], svgs: [] };
    const inHidden = (el) => !!el.closest('[hidden], [aria-hidden="true"]');
    document.querySelectorAll('input:not([type="hidden"]), select, textarea, button, a[href], [role="button"], [role="combobox"]').forEach((el) => {
      if (inHidden(el) && !el.closest('[role="dialog"]')) return;
      if (!A.accName(el)) out.names.push(A.desc(el));
    });
    document.querySelectorAll('img').forEach((img) => { if (!img.hasAttribute('alt')) out.alts.push(A.desc(img) + ' src=' + (img.getAttribute('src') || '').slice(-40)); });
    document.querySelectorAll('svg').forEach((svg) => {
      if (svg.closest('[aria-hidden="true"]')) return;
      const labelled = svg.getAttribute('aria-label') || svg.querySelector('title') || svg.getAttribute('aria-labelledby');
      const parentNamed = svg.parentElement && A.accName(svg.parentElement);
      if (!labelled && !parentNamed) out.svgs.push(A.desc(svg.parentElement || svg));
    });
    document.querySelectorAll('h1, h2, h3, h4, h5, h6, [role="heading"]').forEach((h) => {
      if (h.closest('[hidden]')) return;
      const level = h.getAttribute('aria-level') ? parseInt(h.getAttribute('aria-level'), 10) : parseInt(h.tagName.slice(1), 10);
      out.headings.push({ level, text: h.textContent.trim().replace(/\s+/g, ' ').slice(0, 50), srOnly: A.srOnly(h) });
    });
    return out;
  });
  found.names.forEach((d) => result.fail('acc-name', path, `Control without accessible name: ${d}`));
  found.alts.forEach((d) => result.fail('img-alt', path, `Image without alt attribute: ${d}`));
  found.svgs.forEach((d) => result.fail('svg-name', path, `Exposed SVG without a name inside ${d}`));
  const h1s = found.headings.filter((h) => h.level === 1);
  if (h1s.length !== 1) result.fail('heading-h1', path, `Expected one h1, found ${h1s.length}`);
  let prev = 0;
  found.headings.forEach((h) => {
    if (prev && h.level > prev + 1) result.fail('heading-order', path, `Heading skips from h${prev} to h${h.level}: "${h.text}"`);
    prev = h.level;
  });
  if (found.headings.length && found.headings[0].level !== 1) result.fail('heading-order', path, `First heading is h${found.headings[0].level}: "${found.headings[0].text}"`);
  result.note(path, `headings: ${found.headings.map((h) => 'h' + h.level).join(' ')}`);
}

async function tapTargets(page, path, result) {
  await page.evaluate(() => { window.scrollTo(0, 0); if (window.SFReveal && window.SFReveal.showAll) window.SFReveal.showAll(); });
  await page.waitForTimeout(200);
  const small = await page.evaluate(() => {
    const A = window.__a11y;
    const out = [];
    const owner = (node) => node && node.closest ? node.closest('a[href], button, input, select, textarea, [role="button"], label') : null;
    const isInlineText = (el) => {
      if (el.tagName !== 'A' || getComputedStyle(el).display !== 'inline') return false;
      let block = el.parentElement;
      while (block && getComputedStyle(block).display === 'inline') block = block.parentElement;
      if (!block) return false;
      const words = block.textContent.trim().replace(/\s+/g, ' ');
      return words.length > el.textContent.trim().length + 3 && !block.matches('li.cluster > *, .cluster, nav, ul, ol');
    };
    const fixedAncestor = (el) => { let n = el; while (n && n !== document.body) { const pos = getComputedStyle(n).position; if (pos === 'fixed' || pos === 'sticky') return n; n = n.parentElement; } return null; };
    const effective = (el) => {
      const fixed = fixedAncestor(el);
      if (!fixed) el.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'instant' });
      const r = el.getBoundingClientRect();
      if (fixed && (r.bottom <= 0 || r.top >= window.innerHeight || r.right <= 0 || r.left >= window.innerWidth)) return { ok: true, r, offscreen: true };
      if (r.width >= 44 && r.height >= 44) return { ok: true, r };
      const cx = r.left + r.width / 2;
      const cy = r.top + r.height / 2;
      const control = el.tagName === 'LABEL' ? el.control : null;
      let hits = 0;
      let total = 0;
      for (let dx = -21; dx <= 21; dx += 7) {
        for (let dy = -21; dy <= 21; dy += 7) {
          total++;
          const x = Math.min(window.innerWidth - 1, Math.max(0, cx + dx));
          const y = Math.min(window.innerHeight - 1, Math.max(0, cy + dy));
          const hit = document.elementFromPoint(x, y);
          const o = owner(hit);
          if (hit === el || el.contains(hit) || o === el || (control && (o === control || hit === control))) hits++;
        }
      }
      return { ok: hits === total, r, hits, total };
    };
    document.querySelectorAll('a[href], button, input:not([type="hidden"]), select, textarea, [role="button"], label').forEach((el) => {
      if (el.tagName === 'LABEL') {
        const control = el.control;
        if (!control || !['radio', 'checkbox'].includes(control.type)) return;
      }
      if (['radio', 'checkbox'].includes(el.type)) return;
      if (el.closest('[aria-hidden="true"], .field__honeypot') || el.getAttribute('tabindex') === '-1') return;
      if (!A.vis(el) || A.srOnly(el)) return;
      if (isInlineText(el)) return;
      if (window.__a11yBarsOnly && !el.closest('.sticky-bar, .filter-bar')) return;
      const e = effective(el);
      if (!e.ok) out.push(A.desc(el) + ` ${Math.round(e.r.width)}x${Math.round(e.r.height)} (hit ${e.hits}/${e.total})`);
    });
    window.scrollTo(0, 0);
    return out;
  });
  small.forEach((d) => result.fail('tap-target', path, `Tap target under 44px at 375: ${d}`));
  const bars = await page.evaluate(() => !!document.querySelector('.sticky-bar, .filter-bar'));
  if (!bars) return;
  let shownOnce = false;
  const reported = new Set();
  for (const y of [300, 900, 1600, 2600]) {
    await page.evaluate((top) => { window.scrollTo({ top, behavior: 'instant' }); }, y);
    await page.waitForTimeout(450);
    const barSmall = await page.evaluate(() => {
      const A = window.__a11y;
      const out = [];
      document.querySelectorAll('.sticky-bar.is-visible a[href], .sticky-bar.is-visible button, .filter-bar.is-visible a[href], .filter-bar.is-visible button').forEach((el) => {
        if (!A.vis(el)) return;
        const r = el.getBoundingClientRect();
        const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        let hits = 0, total = 0;
        for (let dx = -21; dx <= 21; dx += 7) for (let dy = -21; dy <= 21; dy += 7) { total++; const hit = document.elementFromPoint(cx + dx, cy + dy); if (hit === el || el.contains(hit)) hits++; }
        if (hits !== total) out.push(A.desc(el) + ` ${Math.round(r.width)}x${Math.round(r.height)} (hit ${hits}/${total})`);
      });
      return { out, shown: !!document.querySelector('.sticky-bar.is-visible, .filter-bar.is-visible') };
    });
    if (barSmall.shown) shownOnce = true;
    barSmall.out.forEach((d) => { if (!reported.has(d)) { reported.add(d); result.fail('tap-target', path, `Tap target under 44px at 375 (fixed bar): ${d}`); } });
  }
  if (!shownOnce) result.note(path, 'sticky/filter bar never became visible while scrolling');
  else result.note(path, 'fixed bar controls hit-tested while visible');
  await page.evaluate(() => window.scrollTo(0, 0));
}

async function contrast(page, path, result) {
  await page.evaluate(() => { if (window.SFReveal && window.SFReveal.showAll) window.SFReveal.showAll(); document.querySelectorAll('.sf-reveal').forEach((n) => n.classList.add('is-visible')); });
  await page.waitForTimeout(150);
  const found = await page.evaluate(() => {
    const A = window.__a11y;
    const fails = [];
    const unverifiable = [];
    const overMedia = [];
    const mediaRects = Array.from(document.querySelectorAll('img, picture, canvas, video')).filter((m) => A.vis(m)).map((m) => ({ m, r: m.getBoundingClientRect() })).filter((o) => o.r.width * o.r.height > 400);
    A.mediaUnder = (el) => {
      const r = el.getBoundingClientRect();
      for (const { m, r: mr } of mediaRects) {
        if (el.contains(m)) continue;
        const ix = Math.min(r.right, mr.right) - Math.max(r.left, mr.left);
        const iy = Math.min(r.bottom, mr.bottom) - Math.max(r.top, mr.top);
        if (ix <= 0 || iy <= 0 || ix * iy < r.width * r.height * 0.25) continue;
        let plated = false;
        for (let n = el; n && !n.contains(m); n = n.parentElement) {
          const c = getComputedStyle(n);
          const b = A.parseRgb(c.backgroundColor);
          if ((b && b.a >= 0.9) || (c.backgroundImage && c.backgroundImage !== 'none')) { plated = true; break; }
        }
        if (!plated) return A.desc(m).slice(0, 70);
      }
      return '';
    };
    let checked = 0;
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_ELEMENT);
    let el;
    while ((el = walker.nextNode())) {
      if (['SCRIPT', 'STYLE', 'SVG', 'NOSCRIPT', 'TEMPLATE'].includes(el.tagName)) continue;
      const own = Array.from(el.childNodes).some((n) => n.nodeType === 3 && n.textContent.trim());
      if (!own) continue;
      if (!A.vis(el) || A.srOnly(el) || el.closest('[aria-hidden="true"], [disabled], .is-disabled')) continue;
      if (el.closest('label') && el.closest('label').control && el.closest('label').control.disabled) continue;
      const cs = getComputedStyle(el);
      const size = parseFloat(cs.fontSize);
      const weight = parseInt(cs.fontWeight, 10) || 400;
      const text = el.textContent.trim().replace(/\s+/g, ' ').slice(0, 40);
      if (cs.webkitTextFillColor === 'rgba(0, 0, 0, 0)' || cs.webkitBackgroundClip === 'text' || cs.backgroundClip === 'text') {
        if (size < 28) fails.push({ desc: A.desc(el), text, why: `gradient text at ${size}px (< 28px)` });
        continue;
      }
      const fg0 = A.parseRgb(cs.color);
      if (!fg0) continue;
      const bg = A.background(el);
      if (bg.opacity < 0.02) continue;
      if (bg.image) { unverifiable.push({ desc: A.desc(el), text, over: bg.node }); continue; }
      const media = A.mediaUnder(el);
      if (media) { overMedia.push({ desc: A.desc(el), text, over: media, color: cs.color, size, weight }); continue; }
      const fg = A.over({ ...fg0, a: fg0.a * bg.opacity }, bg.color);
      const ratio = A.ratio(fg, bg.color);
      const large = size >= 24 || (size >= 18.66 && weight >= 700);
      const need = large ? 3 : 4.5;
      checked++;
      if (ratio < need) fails.push({ desc: A.desc(el), text, why: `${ratio.toFixed(2)}:1 (${cs.color} on rgb(${Math.round(bg.color.r)},${Math.round(bg.color.g)},${Math.round(bg.color.b)}), ${size}px/${weight}, opacity ${bg.opacity.toFixed(2)})` });
    }
    return { fails, unverifiable, overMedia, checked };
  });
  found.fails.forEach((f) => result.fail('contrast', path, `${f.desc} "${f.text}" — ${f.why}`));
  result.note(path, `contrast: ${found.checked} text nodes checked, ${found.unverifiable.length} over background images skipped, ${found.overMedia.length} over media listed for manual review`);
  found.overMedia.forEach((o) => result.review(path, `${o.desc} "${o.text}" (${o.color}, ${o.size}px/${o.weight}) sits over ${o.over}`));
  found.unverifiable.slice(0, 6).forEach((u) => result.note(path, `contrast unverifiable (image behind): ${u.desc} "${u.text}"`));
}

async function reducedMotion(page, path, result) {
  const state = await page.evaluate(() => {
    const A = window.__a11y;
    const out = { reveals: [], hidden: [], introClass: document.documentElement.className };
    document.querySelectorAll('.sf-reveal, .sf-reveal--stagger > *').forEach((el) => {
      const cs = getComputedStyle(el);
      if (cs.opacity !== '1' || (cs.transform !== 'none' && cs.transform !== 'matrix(1, 0, 0, 1, 0, 0)')) out.reveals.push(A.desc(el) + ` opacity=${cs.opacity} transform=${cs.transform}`);
    });
    const durs = (el) => getComputedStyle(el).transitionDuration.split(',').map((d) => parseFloat(d) * (d.trim().endsWith('ms') ? 1 : 1000));
    ['.product-card__img', '.collection-card__img', '.gallery__img', '.drawer', '.nav-mobile', '.sf-reveal', '.hero__enter', '.btn--primary'].forEach((sel) => {
      const el = document.querySelector(sel);
      if (!el) return;
      const cs = getComputedStyle(el);
      const anim = parseFloat(cs.animationDuration) * (cs.animationDuration.trim().endsWith('ms') ? 1 : 1000);
      const slow = durs(el).filter((d) => d > 1);
      if (slow.length) out.hidden.push(`${sel} transition-duration ${cs.transitionDuration}`);
      if (cs.animationName !== 'none' && anim > 1) out.hidden.push(`${sel} animation ${cs.animationName} ${cs.animationDuration}`);
    });
    return out;
  });
  state.reveals.forEach((d) => result.fail('reduced-reveal', path, `Reveal not disabled under reduced motion: ${d}`));
  state.hidden.forEach((d) => result.fail('reduced-transition', path, `Transition still timed under reduced motion: ${d}`));
  if (/sf-intro-(full|calm)/.test(state.introClass)) result.fail('reduced-intro', path, `Intro still runs under reduced motion (${state.introClass})`);
  const card = await page.$('.product-card__img, .collection-card__img');
  if (card) {
    const rest = await card.evaluate((el) => getComputedStyle(el).transform);
    await card.hover().catch(() => {});
    await page.waitForTimeout(120);
    const t = await card.evaluate((el) => getComputedStyle(el).transform);
    const scaleOf = (m) => { const v = /matrix\(([^)]+)\)/.exec(m); return v ? parseFloat(v[1].split(',')[0]) : 1; };
    if (Math.abs(scaleOf(t) - scaleOf(rest)) > 0.01) result.fail('reduced-zoom', path, `Hover zoom still applied under reduced motion: ${rest} -> ${t}`);
  }
  const opener = await page.$('.js-cart-open');
  if (opener) {
    await opener.click();
    await page.waitForTimeout(80);
    const drawer = await page.evaluate(() => { const d = document.querySelector('.js-cart-drawer'); if (!d) return null; const cs = getComputedStyle(d); return { open: d.classList.contains('is-open'), transform: cs.transform, dur: cs.transitionDuration }; });
    if (drawer && drawer.open && drawer.transform !== 'none' && drawer.transform !== 'matrix(1, 0, 0, 1, 0, 0)') result.fail('reduced-drawer', path, `Drawer still sliding under reduced motion 80ms after open: ${drawer.transform} (${drawer.dur})`);
    await page.keyboard.press('Escape');
    await page.waitForTimeout(100);
  }
}

function makeResult() {
  const failures = [];
  const notes = [];
  const reviews = [];
  const tabOrders = {};
  return {
    failures,
    notes,
    reviews,
    tabOrders,
    review(path, message) { reviews.push({ path, message }); },
    fail(kind, path, message) { failures.push({ kind, path, message }); },
    note(path, message) { notes.push({ path, message }); }
  };
}

async function run() {
  const pages = ONLY || PAGES;
  const browser = await chromium.launch();
  const result = makeResult();
  const desktop = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const mobile = await browser.newContext({ viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
  const reduced = await browser.newContext({ viewport: { width: 1280, height: 800 }, reducedMotion: 'reduce' });
  const dPage = await desktop.newPage();
  const mPage = await mobile.newPage();
  const rPage = await reduced.newPage();
  for (const path of pages) {
    console.log('==', path);
    await open(dPage, path);
    await staticChecks(dPage, path, result);
    result.tabOrders[path] = await tabPass(dPage, path, result);
    await open(dPage, path);
    await dPage.evaluate(() => window.__a11y.snapshotRest());
    await dialogTest(dPage, path, result, '.js-cart-open', '.js-cart-drawer', 'cart drawer');
    await searchTest(dPage, path, result);
    await open(mPage, path);
    await mPage.evaluate(() => window.__a11y.snapshotRest());
    await dialogTest(mPage, path, result, '.js-nav-toggle', '.js-nav-mobile', 'mobile nav');
    await spaceKeyTest(mPage, path, result, '.js-nav-toggle', '.js-nav-mobile', 'mobile nav');
    if (await mPage.$('.toolbar__filter-toggle')) {
      await open(mPage, path);
      await mPage.evaluate(() => window.__a11y.snapshotRest());
      await dialogTest(mPage, path, result, '.toolbar__filter-toggle', '#filters-drawer', 'filters drawer');
    }
    if (await dPage.$('[data-lightbox-open]')) {
      await open(dPage, path);
      await dPage.evaluate(() => window.__a11y.snapshotRest());
      await dialogTest(dPage, path, result, '[data-lightbox-open]', '#lightbox', 'lightbox');
    }
    await open(mPage, path);
    await tapTargets(mPage, path, result);
    await open(rPage, path);
    await contrast(rPage, path, result);
    await reducedMotion(rPage, path, result);
  }
  await browser.close();
  themeIssues.forEach((theme, path) => result.fail('theme', path, `data-theme is ${theme === null ? 'absent' : '"' + theme + '"'}, expected "${EXPECT_THEME}"`));
  if (TAB_ORDER_REF) {
    const ref = JSON.parse(readFileSync(TAB_ORDER_REF, 'utf8')).tabOrders || {};
    for (const path of pages) {
      const want = ref[path];
      const got = result.tabOrders[path] || [];
      if (!want) { result.note(path, `tab order: no reference in ${TAB_ORDER_REF}`); continue; }
      const stateless = (d) => String(d || '').replace(/^(\S+)/, (head) => head.split('.').filter((c, i) => i === 0 || !/^(is|has)-/.test(c)).join('.'));
      const at = want.findIndex((d, i) => stateless(d) !== stateless(got[i]));
      if (at >= 0 || want.length !== got.length) {
        const i = at >= 0 ? at : Math.min(want.length, got.length);
        result.fail('tab-order', path, `tab order differs from reference at stop ${i + 1}: "${want[i] || '(end)'}" vs "${got[i] || '(end)'}" (${want.length} vs ${got.length} stops)`);
      } else result.note(path, `tab order identical to reference (${got.length} stops)`);
    }
  }
  const byKind = {};
  result.failures.forEach((f) => { byKind[f.kind] = (byKind[f.kind] || 0) + 1; });
  writeFileSync(OUT, JSON.stringify({ base: BASE, expectTheme: EXPECT_THEME || null, pages, failures: result.failures, notes: result.notes, byKind, manualReview: result.reviews, tabOrders: result.tabOrders }, null, 2));
  result.reviews.forEach((r) => console.log(`REVIEW [text over media] ${r.path}: ${r.message}`));
  result.failures.forEach((f) => console.log(`FAIL [${f.kind}] ${f.path}: ${f.message}`));
  console.log('---');
  console.log(`${result.failures.length} failure(s)`, JSON.stringify(byKind), `${result.reviews.length} item(s) for manual review`);
  console.log(`report: ${OUT}`);
  process.exit(result.failures.length ? 1 : 0);
}

run().catch((error) => { console.error(error); process.exit(2); });
