import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import sharp from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/sharp/lib/index.js'
import { mkdirSync } from 'node:fs'
const base = (process.argv[2] || 'http://127.0.0.1:8089').replace(/\/$/, '')
const out = process.argv[3] || 'docs/shots/intro'
const expectTheme = process.env.EXPECT_THEME || ''
const suffix = (theme) => (expectTheme || theme) === 'light' ? '--light' : ''
mkdirSync(out, { recursive: true })
const failures = []
const browser = await chromium.launch()
const lin = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4) }
async function frameStats(buf) {
  const px = await sharp(buf).resize(160, 100, { fit: 'fill' }).removeAlpha().raw().toBuffer()
  const lums = []
  for (let i = 0; i < px.length; i += 3) lums.push(0.2126 * lin(px[i]) + 0.7152 * lin(px[i + 1]) + 0.0722 * lin(px[i + 2]))
  const sorted = lums.slice().sort((a, b) => a - b)
  return { mean: +(lums.reduce((a, b) => a + b, 0) / lums.length).toFixed(3), median: +sorted[Math.floor(sorted.length / 2)].toFixed(3) }
}
const skipContrast = () => {
  const root = document.getElementById('sf-intro')
  if (!root) return null
  const skip = [...root.querySelectorAll('a, button')].find((n) => /skip/i.test(n.textContent || n.getAttribute('aria-label') || ''))
  if (!skip) return { found: false }
  const rgb = (s) => { const m = String(s).match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(',').map(parseFloat); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 } }
  const over = (f, b) => ({ r: f.r * f.a + b.r * (1 - f.a), g: f.g * f.a + b.g * (1 - f.a), b: f.b * f.a + b.b * (1 - f.a), a: 1 })
  const L = (c) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4) }; return 0.2126 * f(c.r) + 0.7152 * f(c.g) + 0.0722 * f(c.b) }
  const stack = []
  for (let n = skip; n; n = n.parentElement) { const c = rgb(getComputedStyle(n).backgroundColor); if (c && c.a > 0) { stack.push(c); if (c.a >= 1) break } }
  let bg = { r: 255, g: 255, b: 255, a: 1 }
  for (let i = stack.length - 1; i >= 0; i--) bg = over(stack[i], bg)
  const fg = over(rgb(getComputedStyle(skip).color), bg)
  const l1 = L(fg), l2 = L(bg)
  return { found: true, text: skip.textContent.trim().slice(0, 30), color: getComputedStyle(skip).color, ratio: +((Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05)).toFixed(2) }
}
async function probe(name, ctxOpts) {
  const ctx = await browser.newContext(ctxOpts)
  const page = await ctx.newPage()
  const errors = []
  page.on('pageerror', e => errors.push(e.message.slice(0, 120)))
  page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 120)) })
  await page.goto(base + '/', { waitUntil: 'domcontentloaded' })
  const t0 = Date.now()
  const shots = []
  let skip = null
  const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'))
  const sfx = suffix(theme)
  for (const at of [150, 700, 1200, 1700, 2300]) {
    const wait = at - (Date.now() - t0)
    if (wait > 0) await page.waitForTimeout(wait)
    const buf = await page.screenshot({ path: `${out}/${name}-${at}ms${sfx}.png` })
    const st = await page.evaluate(() => { const el = document.getElementById('sf-intro'); return { html: document.documentElement.className, overlay: el ? el.className : 'gone', opacity: el ? getComputedStyle(el).opacity : null, shown: !!el && getComputedStyle(el).display !== 'none' && getComputedStyle(el).visibility !== 'hidden' } })
    if (!skip && st.shown) skip = await page.evaluate(skipContrast)
    const lum = await frameStats(buf)
    shots.push({ at, ...st, ...lum })
  }
  const ctaClickable = await page.evaluate(() => { const b = document.querySelector('.hero .btn'); if (!b) return null; const r = b.getBoundingClientRect(); const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return !!(hit && (hit === b || b.contains(hit))) })
  console.log(`\n== ${name} (data-theme ${theme})\n  ${shots.map((s) => `${s.at}ms: ${s.overlay}${s.shown ? '' : ' [not displayed]'} (opacity ${s.opacity}) lum mean ${s.mean} median ${s.median}`).join('\n  ')}\n  skip link: ${skip ? (skip.found ? `"${skip.text}" ${skip.color} ${skip.ratio}:1` : 'not found') : 'no overlay seen'}\n  hero CTA clickable at 2.3s: ${ctaClickable}\n  errors: ${errors.length ? errors.join(' | ') : 'none'}`)
  if (expectTheme && theme !== expectTheme) failures.push(`${name}: data-theme is ${theme === null ? 'absent' : '"' + theme + '"'}, expected "${expectTheme}"`)
  if (errors.length) failures.push(`${name}: ${errors.length} error(s)`)
  if (ctaClickable === false) failures.push(`${name}: hero CTA not clickable at 2.3s`)
  if (expectTheme === 'light') {
    const overlayFrames = shots.filter((s) => s.shown && parseFloat(s.opacity) > 0.5)
    overlayFrames.forEach((s) => { if (s.median < 0.7) failures.push(`${name} ${s.at}ms: intro ground is dark (median luminance ${s.median})`) })
    if (skip && skip.found && skip.ratio < 4.5) failures.push(`${name}: Skip link contrast ${skip.ratio}:1 (< 4.5)`)
  }
  await ctx.close()
}
const mediaRects = () => {
  const out = []
  const vw = innerWidth, vh = innerHeight
  const decor = (el) => !!el.closest('[data-motion="hero"], .hero, #sf-intro, .sf-veil, .site-header')
  for (const el of document.querySelectorAll('img, picture, video, svg image')) {
    if (decor(el)) continue
    const r = el.getBoundingClientRect()
    if (r.width < 2 || r.height < 2 || r.bottom <= 0 || r.top >= vh || r.right <= 0 || r.left >= vw) continue
    const cs = getComputedStyle(el)
    if (cs.visibility === 'hidden' || cs.display === 'none') continue
    out.push({ x: r.left, y: r.top, w: r.width, h: r.height })
  }
  for (const el of document.querySelectorAll('body *')) {
    const bg = getComputedStyle(el).backgroundImage
    if (!bg || !bg.includes('url(') || decor(el)) continue
    const r = el.getBoundingClientRect()
    if (r.width < 2 || r.height < 2 || r.bottom <= 0 || r.top >= vh) continue
    out.push({ x: r.left, y: r.top, w: r.width, h: r.height })
  }
  return { vw, vh, rects: out }
}
const mistProbe = () => {
  const lumOf = (c) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4) }; return 0.2126 * f(c[0]) + 0.7152 * f(c[1]) + 0.0722 * f(c[2]) }
  const tail = (s) => {
    const t = String(s || '').trim()
    let m = t.match(/#([0-9a-f]{6})\s*$/i)
    if (m) return [0, 2, 4].map((i) => parseInt(m[1].slice(i, i + 2), 16))
    m = t.match(/rgba?\(([^)]+)\)\s*$/i)
    return m ? m[1].split(',').slice(0, 3).map(parseFloat) : null
  }
  const mist = getComputedStyle(document.documentElement).getPropertyValue('--sf-mist')
  const mc = tail(mist)
  const veil = document.querySelector('.sf-veil')
  const vc = veil ? tail(getComputedStyle(veil).backgroundColor) : null
  return { mist: mist.trim().slice(-40), mistLum: mc ? +lumOf(mc).toFixed(3) : null, veilColor: veil ? getComputedStyle(veil).backgroundColor : null, veilLum: vc ? +lumOf(vc).toFixed(3) : null }
}
async function chromeStats(buf, layout) {
  const W = 160, H = 100
  const px = await sharp(buf).resize(W, H, { fit: 'fill' }).removeAlpha().raw().toBuffer()
  const sx = W / layout.vw, sy = H / layout.vh
  let sum = 0, n = 0
  for (let y = 0; y < H; y++) {
    for (let x = 0; x < W; x++) {
      const cx = (x + 0.5) / sx, cy = (y + 0.5) / sy
      if (layout.rects.some((r) => cx >= r.x && cx < r.x + r.w && cy >= r.y && cy < r.y + r.h)) continue
      const i = (y * W + x) * 3
      sum += 0.2126 * lin(px[i]) + 0.7152 * lin(px[i + 1]) + 0.0722 * lin(px[i + 2])
      n++
    }
  }
  return { chromeMean: n ? +(sum / n).toFixed(3) : null, chromeShare: +(n / (W * H)).toFixed(2) }
}
async function transition(name, ctxOpts) {
  const ctx = await browser.newContext(ctxOpts)
  await ctx.addInitScript(() => { try { sessionStorage.setItem('sfIntro', '1') } catch (e) {} })
  const page = await ctx.newPage()
  await page.goto(base + '/', { waitUntil: 'load' })
  await page.waitForTimeout(1500)
  const sfx = suffix(await page.evaluate(() => document.documentElement.getAttribute('data-theme')))
  const link = page.locator('.site-header a[href$="/shop"]:visible').first()
  if (!(await link.count())) { console.log(`\n== ${name}: no header /shop link`); await ctx.close(); return }
  const mist = await page.evaluate(mistProbe)
  const sourceBuf = await page.screenshot()
  const sourceLayout = await page.evaluate(mediaRects)
  const raw = []
  await link.click({ noWaitAfter: true }).catch(() => {})
  const t0 = Date.now()
  for (const at of [40, 100, 160, 240, 340]) {
    const wait = at - (Date.now() - t0)
    if (wait > 0) await page.waitForTimeout(wait)
    const buf = await page.screenshot({ path: `${out}/${name}-${at}ms${sfx}.png`, timeout: 3000 }).catch(() => null)
    if (!buf) continue
    const own = await Promise.race([page.evaluate(mediaRects).catch(() => null), new Promise((r) => setTimeout(() => r(null), 300))])
    raw.push({ at, buf, own })
  }
  await page.waitForLoadState('load').catch(() => {})
  await page.waitForTimeout(1200)
  const destBuf = await page.screenshot()
  const destLayout = await page.evaluate(mediaRects)
  const union = (own) => ({ vw: sourceLayout.vw, vh: sourceLayout.vh, rects: [...sourceLayout.rects, ...destLayout.rects, ...((own && own.rects) || [])] })
  const source = { ...(await frameStats(sourceBuf)), ...(await chromeStats(sourceBuf, sourceLayout)) }
  const dest = { ...(await frameStats(destBuf)), ...(await chromeStats(destBuf, destLayout)) }
  const frames = []
  for (const f of raw) frames.push({ at: f.at, ...(await frameStats(f.buf)), ...(await chromeStats(f.buf, union(f.own))) })
  const restChrome = Math.min(source.chromeMean ?? 1, dest.chromeMean ?? 1)
  const worst = frames.reduce((w, f) => (!w || (f.chromeMean ?? 1) < (w.chromeMean ?? 1) ? f : w), null)
  console.log(`\n== ${name}\n  mist token: ${mist.mist} (lum ${mist.mistLum}); veil ${mist.veilColor ?? 'absent'} (lum ${mist.veilLum ?? '-'})\n  at rest, whole frame: home ${source.mean}, /shop ${dest.mean}; outside media: home ${source.chromeMean}, /shop ${dest.chromeMean}\n  ${frames.map((f) => `${f.at}ms: whole mean ${f.mean} median ${f.median}; outside media ${f.chromeMean} (${Math.round(f.chromeShare * 100)}% of frame)`).join('\n  ')}\n  darkest frame outside media: ${worst ? worst.at + 'ms ' + worst.chromeMean : 'none'}${expectTheme === 'light' ? ' (must be above 0.8)' : ''}`)
  if (expectTheme === 'light') {
    if (mist.mistLum === null || mist.mistLum <= 0.8) failures.push(`${name}: --sf-mist ends in a dark ground (${mist.mist}, lum ${mist.mistLum})`)
    if (mist.veilColor !== null && (mist.veilLum === null || mist.veilLum <= 0.8)) failures.push(`${name}: .sf-veil background ${mist.veilColor} is dark (lum ${mist.veilLum})`)
    if (worst && worst.chromeMean !== null && worst.chromeMean <= 0.8) {
      if (restChrome > 0.8 || worst.chromeMean < restChrome - 0.03) failures.push(`${name}: ${worst.at}ms frame luminance outside product media is ${worst.chromeMean} (needs > 0.8; pages at rest ${source.chromeMean}, ${dest.chromeMean}): a dark flash`)
    }
  }
  await ctx.close()
}
const only = process.env.ONLY ? process.env.ONLY.split(',') : null
const wanted = (name) => !only || only.includes(name)
if (wanted('calm-reduced-motion')) await probe('calm-reduced-motion', { viewport: { width: 1280, height: 800 }, reducedMotion: 'reduce' })
if (wanted('full-360')) await probe('full-360', { viewport: { width: 360, height: 740 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })
if (wanted('full-1440')) await probe('full-1440', { viewport: { width: 1440, height: 900 } })
if (wanted('transition-1440')) await transition('transition-1440', { viewport: { width: 1440, height: 900 } })
await browser.close()
console.log(failures.length ? `\nFAIL (${failures.length}):\n- ` + failures.join('\n- ') : `\nPASS (${expectTheme || 'theme not asserted'})`)
process.exit(failures.length ? 1 : 0)
