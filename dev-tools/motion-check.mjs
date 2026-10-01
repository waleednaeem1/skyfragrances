import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync, writeFileSync } from 'node:fs'
import { dirname } from 'node:path'

const base = process.argv[2] || 'http://127.0.0.1:8091'
const jsonOut = process.argv[3] || ''
const expectTheme = process.env.EXPECT_THEME || ''
const PAGES = ['/', '/shop', '/product/azure-oud', '/scent-finder', '/cart', '/checkout']
const ANDROID_UA = 'Mozilla/5.0 (Linux; Android 13; Pixel 6a) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36'
const PROFILES = [
  { name: 'desktop-1440', ctx: { viewport: { width: 1440, height: 900 } }, desktop: true },
  { name: 'mobile-360', ctx: { viewport: { width: 360, height: 740 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2, userAgent: ANDROID_UA }, mobile: true },
  { name: 'reduced-1440', ctx: { viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' }, reduced: true },
]
const BUY_PATH = {
  '/': [['.hero .btn--primary', 'hero CTA']],
  '/shop': [['.product-card .price__now', 'card price'], ['.product-card__link', 'card link']],
  '/product/azure-oud': [['[data-price-now]', 'price'], ['#add-to-cart-form .js-add-to-cart', 'Add to Cart'], ['.split__aside .trust--row', 'COD trust row']],
  '/scent-finder': [['.js-quiz .form__section.is-active .choice--answer', 'quiz answer'], ['.js-quiz .form__section.is-active a.btn', 'quiz next']],
  '/cart': [['.cart-line__total, .summary__value--total', 'cart price'], ['main a[href*="/checkout"]', 'checkout button']],
  '/checkout': [['#checkout-form', 'checkout form'], ['label.choice:has(input[name="payment_method"][value="cod"])', 'COD option'], ['#checkout-form button[type="submit"]', 'place order']],
}
const failures = []
const rows = []
const extra = {}

function fail(label, detail) {
  failures.push(detail ? `${label}: ${detail}` : label)
}

function attachErrorCollector(page) {
  const errors = []
  page.on('pageerror', (e) => errors.push('page: ' + e.message.slice(0, 160)))
  page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 160)) })
  page.on('requestfailed', (r) => { if (!r.url().includes('favicon') && r.failure() && !/ERR_ABORTED/.test(r.failure().errorText)) errors.push('reqfail: ' + r.url().slice(-80)) })
  page.on('response', (r) => { if (r.status() >= 400 && !r.url().includes('favicon')) errors.push(`${r.status()} ${r.url().slice(-80)}`) })
  return errors
}

const probeBuyPath = (targets) => targets.map(([selector, label]) => {
  const el = document.querySelector(selector)
  if (!el) return { label, present: false }
  const nav = performance.getEntriesByType('navigation')[0]
  const afterLoad = Math.round(performance.now() - (nav && nav.loadEventStart ? nav.loadEventStart : performance.now()))
  let node = el
  let opaque = true
  while (node && node !== document) {
    const cs = getComputedStyle(node)
    if (cs.visibility === 'hidden' || cs.display === 'none' || parseFloat(cs.opacity) < 0.6) opaque = false
    node = node.parentElement
  }
  const r = el.getBoundingClientRect()
  const visible = opaque && r.width > 0 && r.height > 0
  if (r.top < 0 || r.bottom > innerHeight) el.scrollIntoView({ behavior: 'instant', block: 'center' })
  const rr = el.getBoundingClientRect()
  const hit = document.elementFromPoint(Math.min(innerWidth - 1, Math.max(0, rr.left + rr.width / 2)), Math.min(innerHeight - 1, Math.max(0, rr.top + rr.height / 2)))
  const card = el.closest('.product-card')
  const hitCard = hit && hit.closest ? hit.closest('.product-card') : null
  const sameCard = !!card && hitCard === card
  const sameLink = !!hit && !!card && hit.tagName === 'A' && !!card.querySelector('.product-card__link') && hit.href === card.querySelector('.product-card__link').href
  const labelOf = (node) => (node ? (node.className || node.tagName).toString().slice(0, 40) : 'none')
  const underIntro = !!hit && !!hit.closest && !!hit.closest('.sf-intro') && /sf-intro-(full|calm)/.test(document.documentElement.className)
  const clickable = !!hit && (hit === el || el.contains(hit) || hit.contains(el) || sameCard || sameLink || underIntro)
  const where = `rect=${Math.round(rr.left)},${Math.round(rr.top)} ${Math.round(rr.width)}x${Math.round(rr.height)} scrollY=${Math.round(scrollY)} hitCard=${labelOf(hitCard)}`
  window.scrollTo({ top: 0, behavior: 'instant' })
  return { label, present: true, visible, clickable, underIntro, afterLoad, cover: clickable ? '' : `${labelOf(hit)} (${where})` }
})

const startSampler = () => {
  const s = { frames: [], last: 0, run: true }
  window.__sf_fps = s
  const tick = (now) => {
    if (!s.run) return
    if (s.last) s.frames.push(now - s.last)
    s.last = now
    requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

const stopSampler = () => {
  const s = window.__sf_fps
  s.run = false
  const f = s.frames
  if (!f.length) return { fps: 0, p95: 0, long: 0, max: 0, frames: 0 }
  const total = f.reduce((a, b) => a + b, 0)
  const sorted = f.slice().sort((a, b) => a - b)
  return {
    fps: Math.round(f.length / (total / 1000)),
    p95: Math.round(sorted[Math.floor(sorted.length * 0.95)] * 10) / 10,
    long: f.filter((d) => d > 33.4).length,
    max: Math.round(sorted[sorted.length - 1]),
    frames: f.length,
  }
}

async function sampleScroll(page, ms) {
  await page.evaluate(startSampler)
  await page.evaluate(async (ms) => {
    const t0 = performance.now()
    let dir = 1
    await new Promise((done) => {
      const step = () => {
        const max = document.documentElement.scrollHeight - innerHeight
        if (scrollY >= max - 2) dir = -1
        if (scrollY <= 2 && dir === -1) dir = 1
        window.scrollBy({ top: dir * 14, behavior: 'instant' })
        if (performance.now() - t0 < ms) requestAnimationFrame(step)
        else done()
      }
      requestAnimationFrame(step)
    })
  }, ms)
  const result = await page.evaluate(stopSampler)
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }))
  return result
}

async function sampleHover(page, ms) {
  const points = await page.evaluate(() => {
    window.scrollTo({ top: 0, behavior: 'instant' })
    const out = []
    document.querySelectorAll('.product-card, .collection-card, .btn, .section-header__link').forEach((el) => {
      const r = el.getBoundingClientRect()
      if (r.width > 8 && r.height > 8 && r.top > 0 && r.bottom < innerHeight) out.push([r.left + r.width / 2, r.top + r.height / 2])
    })
    return out.slice(0, 12)
  })
  if (!points.length) return null
  await page.evaluate(startSampler)
  const t0 = Date.now()
  let i = 0
  while (Date.now() - t0 < ms) {
    const [x, y] = points[i % points.length]
    await page.mouse.move(x - 20, y + 10, { steps: 6 })
    await page.mouse.move(x + 20, y - 10, { steps: 6 })
    i += 1
  }
  await page.mouse.move(4, 4)
  return page.evaluate(stopSampler)
}

const overlayState = () => {
  const veil = document.querySelector('.sf-veil')
  const intro = document.getElementById('sf-intro')
  const drawer = document.querySelector('.js-cart-drawer')
  const veilStuck = !!veil && veil.classList.contains('is-active') && parseFloat(getComputedStyle(veil).opacity) > 0.05
  const introStuck = !!intro && getComputedStyle(intro).display !== 'none' && parseFloat(getComputedStyle(intro).opacity) > 0.05
  const drawerOpen = !!drawer && drawer.classList.contains('is-open')
  const locked = document.body.classList.contains('is-locked')
  const hit = document.elementFromPoint(innerWidth / 2, Math.min(innerHeight - 10, 300))
  const covered = !!hit && !!hit.closest && !!hit.closest('.sf-veil, .sf-intro, .drawer-overlay')
  return { ok: !veilStuck && !introStuck && !drawerOpen && !locked && !covered, veilStuck, introStuck, drawerOpen, locked, covered, html: document.documentElement.className }
}

const motionState = () => {
  const SF = window.SF || {}
  const m = SF.motion || {}
  const scripts = Array.from(document.scripts).map((s) => s.src).filter(Boolean)
  const hero = document.querySelector('.hero')
  return {
    html: document.documentElement.className,
    theme: document.documentElement.getAttribute('data-theme'),
    device: m.device,
    gsap: !!window.gsap,
    three: !!window.THREE || scripts.some((s) => /three/i.test(s)),
    heavy: scripts.filter((s) => /gsap|ScrollTrigger|lenis|three|ribbons-gl/i.test(s)).map((s) => s.split('/').pop().split('?')[0]),
    ribbons: !!document.querySelector('.hero__ribbons'),
    isGl: !!hero && hero.classList.contains('is-gl'),
    plates: document.querySelectorAll('.hero__plate').length,
    heroState: m.hero ? m.hero.state + (m.hero.reason ? ':' + m.hero.reason : '') : '',
    sections: Object.keys(m.sections || {}),
    cartLines: document.querySelectorAll('.cart-line, .summary-peek').length,
    cue: (() => { const c = document.querySelector('.js-hero-cue'); if (!c) return null; const r = c.getBoundingClientRect(); return { bottom: Math.round(r.bottom), vh: innerHeight, hidden: c.classList.contains('is-hidden') } })(),
    veilDisplay: (() => { const v = document.querySelector('.sf-veil'); return v ? getComputedStyle(v).display : 'absent' })(),
  }
}

async function checkIntro(browser, profile) {
  const ctx = await browser.newContext(profile.ctx)
  const page = await ctx.newPage()
  const errors = attachErrorCollector(page)
  await page.goto(base + '/', { waitUntil: 'commit' })
  const early = await page.evaluate(() => new Promise((r) => { const t0 = performance.now(); const poll = () => { const c = document.documentElement.className; if (/sf-intro-/.test(c) || performance.now() - t0 > 1500) r(c); else requestAnimationFrame(poll) }; poll() }))
  const mode = (early.match(/sf-intro-(full|calm|quick)/) || [])[1] || 'none'
  const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'))
  await page.waitForLoadState('load')
  const h1 = await page.evaluate(() => { const h = document.querySelector('h1.hero__title'); if (!h) return null; const cs = getComputedStyle(h); return { opacity: cs.opacity, visibility: cs.visibility } })
  const veilShown = await page.evaluate(() => { const el = document.getElementById('sf-intro'); return !!el && getComputedStyle(el).display !== 'none' })
  const done = mode === 'none' ? false : await page.waitForFunction(() => document.documentElement.classList.contains('sf-intro-done') && !document.getElementById('sf-intro'), null, { timeout: 5000 }).then(() => true).catch(() => false)
  const ctaAfter = await page.evaluate(() => { const b = document.querySelector('.hero .btn--primary'); if (!b) return null; const r = b.getBoundingClientRect(); const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return !!hit && (hit === b || b.contains(hit)) })
  await ctx.close()
  return { mode, theme, done, veilShown, h1, ctaAfter, errors: errors.slice() }
}

async function addToCart(page) {
  const before = await page.evaluate(() => parseInt((document.querySelector('.site-header__count') || {}).textContent || '0', 10) || 0)
  const button = page.locator('#add-to-cart-form .js-add-to-cart')
  if (!(await button.count())) return { ok: false, reason: 'no button' }
  await button.scrollIntoViewIfNeeded()
  await button.click()
  const opened = await page.waitForSelector('.js-cart-drawer.is-open', { timeout: 6000 }).then(() => true).catch(() => false)
  await page.waitForTimeout(500)
  const after = await page.evaluate(() => ({
    drawer: parseInt((document.querySelector('.js-cart-drawer .js-cart-count') || {}).textContent || '0', 10) || 0,
    header: parseInt((document.querySelector('.site-header__count') || {}).textContent || '0', 10) || 0,
    loading: !!document.querySelector('#add-to-cart-form .js-add-to-cart.is-loading'),
    spray: document.querySelectorAll('.sf-spray').length,
  }))
  await page.keyboard.press('Escape')
  await page.waitForFunction(() => !document.querySelector('.js-cart-drawer.is-open') && !document.body.classList.contains('is-locked'), null, { timeout: 3000 }).catch(() => {})
  return { ok: opened && after.drawer === before + 1 && after.header === before + 1 && !after.loading, before, ...after, opened }
}

async function quizWalk(page) {
  const steps = []
  const activeId = () => page.evaluate(() => { const el = document.querySelector('.js-quiz .form__section.is-active'); return el ? el.id : null })
  const isOn = (n, timeout) => page.waitForFunction((n) => { const el = document.querySelector('.js-quiz .form__section.is-active'); return !!el && el.id === 'question-' + n && !document.querySelector('.quiz-deck.is-flipping, .js-quiz .form__section.is-leaving') }, n, { timeout }).then(() => true).catch(() => false)
  for (let i = 1; i <= 5; i++) {
    const id = await activeId()
    if (id !== `question-${i}`) return { ok: false, reason: `expected question-${i}, active is ${id}`, steps }
    const point = await page.evaluate((i) => { const el = document.querySelector(`#question-${i} .choice--answer`); if (!el) return null; el.scrollIntoView({ behavior: 'instant', block: 'center' }); const r = el.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 } }, i)
    if (!point) return { ok: false, reason: `no answer on step ${i}`, steps }
    await page.mouse.click(point.x, point.y)
    if (i < 5) {
      const auto = await isOn(i + 1, 1500)
      if (!auto) {
        const clicked = await page.evaluate((i) => { const a = document.querySelector(`#question-${i} a.btn[href="#question-${i + 1}"]`); if (!a) return false; a.click(); return true }, i)
        if (!clicked) return { ok: false, reason: `no next on step ${i}`, steps }
      }
      const advanced = await isOn(i + 1, 4000)
      steps.push({ step: i, auto, advanced })
      if (!advanced) return { ok: false, reason: `did not settle on question-${i + 1}`, steps }
    }
  }
  const checked = await page.evaluate(() => document.querySelectorAll('.js-quiz input[type="radio"]:checked').length)
  if (checked !== 5) return { ok: false, reason: `${checked} of 5 answers checked`, steps }
  const submitVisible = await page.locator('.js-quiz .form__actions button[type="submit"]').isVisible().catch(() => false)
  if (!submitVisible) return { ok: false, reason: 'submit hidden on step 5', steps }
  await page.locator('.js-quiz .form__actions button[type="submit"]').click({ timeout: 5000 }).catch(() => {})
  await page.waitForLoadState('load').catch(() => {})
  const result = await page.evaluate(() => {
    const price = document.querySelector('.price__now, [data-price-now]')
    const cta = document.querySelector('.js-add-to-cart, a[href*="/product/"]')
    const box = (el) => { if (!el) return null; const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 }
    return { url: location.pathname + location.search, price: box(price), cta: box(cta), veil: !!document.querySelector('.quiz-card__veil, .sf-quiz-mist') }
  })
  return { ok: /\/scent-finder\/result/.test(result.url) && result.price !== false && result.cta !== false, steps, ...result }
}

async function navChain(page, profile) {
  const steps = []
  const go = async (label, action) => {
    await action()
    await page.waitForLoadState('load').catch(() => {})
    await page.waitForTimeout(700)
    const st = await page.evaluate(overlayState)
    steps.push({ label, url: new URL(page.url()).pathname, ...st })
    if (!st.ok) fail(`${profile.name} nav ${label} overlay`, JSON.stringify(st))
  }
  const clickOrGo = async (selector, path) => {
    const link = page.locator(selector).first()
    if (await link.count()) {
      const ok = await link.click({ timeout: 6000 }).then(() => true).catch(() => false)
      if (ok) return
    }
    await page.goto(base + path, { waitUntil: 'load' })
  }
  await go('home', () => page.goto(base + '/', { waitUntil: 'load' }))
  await go('shop', () => clickOrGo('.site-header a[href$="/shop"]:visible, main a[href$="/shop"]:visible', '/shop'))
  await go('product', () => clickOrGo('a.product-card__link[href*="/product/azure-oud"]:visible', '/product/azure-oud'))
  await go('cart', () => clickOrGo('main a[href$="/cart"]:visible, .site-footer a[href$="/cart"]:visible', '/cart'))
  await go('checkout', () => clickOrGo('main a[href*="/checkout"]:visible', '/checkout'))
  await go('back', () => page.goBack())
  return steps
}

const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] })
for (const profile of PROFILES) {
  extra[profile.name] = {}
  if (!profile.reduced) {
    const intro = await checkIntro(browser, profile)
    extra[profile.name].intro = intro
    if (profile.desktop && intro.mode === 'none') fail(`${profile.name} intro did not run on first visit`)
    if (profile.mobile && intro.mode !== 'none') fail(`${profile.name} intro ran on a phone (flags.loader is desktop)`, intro.mode)
    if (profile.mobile && intro.veilShown) fail(`${profile.name} intro veil displayed on a phone`)
    if (intro.mode !== 'none' && !intro.done) fail(`${profile.name} intro did not finish`, intro.mode)
    if (intro.h1 && (intro.h1.visibility !== 'visible' || parseFloat(intro.h1.opacity) < 1)) fail(`${profile.name} h1 not painted at load`, JSON.stringify(intro.h1))
    if (intro.ctaAfter === false) fail(`${profile.name} hero CTA not clickable after intro`)
    if (intro.errors.length) fail(`${profile.name} intro errors`, intro.errors.join(' | '))
    if (expectTheme && intro.theme !== expectTheme) fail(`${profile.name} intro page data-theme`, `${intro.theme} (expected ${expectTheme})`)
  }
  const ctx = await browser.newContext(profile.ctx)
  const page = await ctx.newPage()
  const errors = attachErrorCollector(page)
  for (const path of PAGES) {
    errors.length = 0
    const res = await page.goto(base + path, { waitUntil: 'load' })
    const buy = await page.evaluate(probeBuyPath, BUY_PATH[path] || [])
    const status = res ? res.status() : 0
    await page.waitForTimeout(2600)
    const state = await page.evaluate(motionState)
    const overlay = await page.evaluate(overlayState)
    const scroll = await sampleScroll(page, 3000)
    const hover = profile.desktop ? await sampleHover(page, 3000) : null
    let cart = null
    if (path === '/product/azure-oud') {
      cart = await addToCart(page)
      if (!cart.ok) fail(`${profile.name} add to cart`, JSON.stringify(cart))
    }
    if (path === '/scent-finder') {
      const quiz = await quizWalk(page).catch((e) => ({ ok: false, reason: 'threw: ' + e.message.split('\n')[0].slice(0, 120) }))
      extra[profile.name].quiz = quiz
      if (!quiz.ok) fail(`${profile.name} quiz walk`, JSON.stringify(quiz))
    }
    const overlayAfter = await page.evaluate(overlayState)
    if ((path === '/cart' || path === '/checkout') && !state.cartLines) fail(`${profile.name} ${path} cart empty after add to cart`)
    buy.forEach((b) => {
      if (!b.present) fail(`${profile.name} ${path} ${b.label} missing`)
      else if (!b.visible || !b.clickable) fail(`${profile.name} ${path} ${b.label} not ${b.visible ? 'clickable' : 'visible'} at load+${b.afterLoad}ms`, b.cover)
      else if (b.afterLoad > 100) fail(`${profile.name} ${path} ${b.label} checked late`, b.afterLoad + 'ms')
    })
    if (errors.length) fail(`${profile.name} ${path} errors`, errors.join(' | '))
    if (expectTheme && state.theme !== expectTheme) fail(`${profile.name} ${path} data-theme`, `${state.theme} (expected ${expectTheme})`)
    if (!overlay.ok || !overlayAfter.ok) fail(`${profile.name} ${path} overlay`, JSON.stringify(overlay.ok ? overlayAfter : overlay))
    if (profile.mobile) {
      if (!/motion--(mobile|low)/.test(state.html)) fail(`${profile.name} ${path} device class`, state.html)
      if (state.gsap || state.three || state.heavy.length || state.ribbons || state.isGl) fail(`${profile.name} ${path} heavy library or GL on mobile`, JSON.stringify(state.heavy))
      if (path === '/' && (!/plates/.test(state.heroState) || state.plates < 1)) fail(`${profile.name} hero fallback not active`, JSON.stringify({ heroState: state.heroState, plates: state.plates }))
    }
    if (profile.reduced) {
      if (!/sf-reduce/.test(state.html)) fail(`${profile.name} ${path} sf-reduce missing`, state.html)
      if (state.gsap || state.sections.length || state.heavy.length) fail(`${profile.name} ${path} modules ran under reduced motion`, JSON.stringify(state.sections))
    }
    if (profile.desktop && path === '/' && state.cue && !state.cue.hidden && state.cue.bottom > state.cue.vh) fail(`${profile.name} hero cue below the fold`, `${state.cue.bottom} > ${state.cue.vh}`)
    if (scroll.fps < 50) fail(`${profile.name} ${path} scroll fps`, `${scroll.fps} fps, p95 ${scroll.p95}ms`)
    if (hover && hover.fps < 50) fail(`${profile.name} ${path} hover fps`, `${hover.fps} fps, p95 ${hover.p95}ms`)
    rows.push({ profile: profile.name, path, status, theme: state.theme, errors: errors.length, buy: buy.map((b) => `${b.label}:${!b.present ? 'MISSING' : b.visible && b.clickable ? (b.underIntro ? 'ok(veil)' : 'ok') : 'FAIL'}@${b.afterLoad ?? '-'}ms`).join(' '), scroll, hover, overlay: overlay.ok && overlayAfter.ok, device: state.device, heroState: state.heroState, heavy: state.heavy.join(','), sections: state.sections.join(','), cart: cart ? (cart.ok ? `ok ${cart.before}->${cart.drawer}` : 'FAIL') : (path === '/cart' || path === '/checkout' ? `lines:${state.cartLines}` : '') })
  }
  extra[profile.name].nav = await navChain(page, profile)
  await ctx.close()
}
await browser.close()

const pad = (v, n) => String(v ?? '').padEnd(n)
console.log(`theme: ${[...new Set(rows.map((r) => r.theme))].join(', ')}${expectTheme ? ' (expected ' + expectTheme + ')' : ''}`)
console.log(pad('profile', 13) + pad('path', 20) + pad('st', 4) + pad('err', 4) + pad('scroll fps/p95/long', 21) + pad('hover fps/p95', 15) + pad('ovl', 5) + pad('hero', 16) + pad('cart', 10) + 'buy path')
for (const r of rows) {
  console.log(pad(r.profile, 13) + pad(r.path, 20) + pad(r.status, 4) + pad(r.errors, 4) + pad(`${r.scroll.fps}/${r.scroll.p95}/${r.scroll.long}`, 21) + pad(r.hover ? `${r.hover.fps}/${r.hover.p95}` : '-', 15) + pad(r.overlay ? 'ok' : 'FAIL', 5) + pad(r.heroState || r.device, 16) + pad(r.cart, 10) + r.buy)
}
for (const [name, info] of Object.entries(extra)) {
  if (info.intro) console.log(`intro ${name}: mode=${info.intro.mode} finished=${info.intro.done} h1=${JSON.stringify(info.intro.h1)} ctaAfter=${info.intro.ctaAfter}`)
  if (info.quiz) console.log(`quiz ${name}: ${info.quiz.ok ? 'ok' : 'FAIL'} ${info.quiz.url || info.quiz.reason || ''} price=${info.quiz.price} cta=${info.quiz.cta}`)
  if (info.nav) console.log(`nav ${name}: ` + info.nav.map((s) => `${s.label}(${s.url})${s.ok ? '' : ' STUCK'}`).join(' → '))
}
console.log(failures.length ? `\nFAILURES (${failures.length}):\n- ` + failures.join('\n- ') : '\nALL CHECKS PASSED')
if (jsonOut) {
  mkdirSync(dirname(jsonOut), { recursive: true })
  writeFileSync(jsonOut, JSON.stringify({ rows, extra, failures }, null, 2))
}
process.exit(failures.length ? 1 : 0)
