import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import sharp from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/sharp/lib/index.js'
import { mkdirSync } from 'node:fs'
const base = (process.argv[2] || 'http://127.0.0.1:8091').replace(/\/$/, '')
const out = process.argv[3] || 'docs/shots/hero-gl'
const expectTheme = process.env.EXPECT_THEME || ''
mkdirSync(out, { recursive: true })
const failures = []
const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] })
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 })
const page = await ctx.newPage()
const errors = []
const motionUrls = []
page.on('pageerror', (e) => errors.push('page: ' + e.message.slice(0, 160)))
page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 160)) })
page.on('request', (r) => { if (/\/assets\/img\/motion\//.test(r.url())) motionUrls.push(r.url().split('/').pop().split('?')[0]) })
await page.addInitScript(() => {
  sessionStorage.setItem('sfIntro', '1')
  window.__heroEvents = []
  const t0 = performance.now()
  document.addEventListener('sf:motion:hero', (e) => {
    const d = e.detail || {}
    const status = window.SF && window.SF.motion && window.SF.motion.hero
    window.__heroEvents.push({ t: Math.round(performance.now() - t0), state: d.state || '', reason: d.reason || '', frameMean: status && status.frame ? status.frame.mean : null })
  })
})
await page.goto(base + '/', { waitUntil: 'networkidle' })
await page.waitForTimeout(6000)
const st = await page.evaluate(() => { const h = document.querySelector('[data-motion="hero"]'); return { theme: document.documentElement.getAttribute('data-theme'), cls: h.className, events: window.__heroEvents, canvases: [...h.querySelectorAll('canvas')].map(c => c.className + ' ' + c.width + 'x' + c.height + ' op=' + getComputedStyle(c).opacity), gl: (() => { try { const c = document.createElement('canvas'); return !!c.getContext('webgl2') } catch (e) { return false } })(), mem: navigator.deviceMemory, cores: navigator.hardwareConcurrency } })
console.log(JSON.stringify(st, null, 1))
const label = expectTheme || st.theme || ''
const sfx = label === 'light' ? '--light' : ''
await page.screenshot({ path: `${out}/hero-1440-6s${sfx}.png`, clip: { x: 0, y: 0, width: 1440, height: 900 } })
await page.waitForTimeout(4000)
await page.screenshot({ path: `${out}/hero-1440-10s${sfx}.png`, clip: { x: 0, y: 0, width: 1440, height: 900 } })

await page.waitForSelector('.hero__ribbons', { state: 'attached', timeout: 4000 }).catch(() => {})
const glState = () => page.evaluate(() => {
  const events = window.__heroEvents || []
  const last = events[events.length - 1] || null
  const status = window.SF && window.SF.motion && window.SF.motion.hero
  return { events, last, canvas: !!document.querySelector('.hero__ribbons'), status: status ? { state: status.state, reason: status.reason, frameMean: status.frame ? status.frame.mean : null } : null }
})
const fellBack = (g) => {
  const fb = [...g.events].reverse().find((e) => e.state === 'plates')
  if (!fb && g.last && g.last.state === 'gl' && g.canvas) return ''
  const reason = (fb && fb.reason) || (g.status && g.status.reason) || ''
  const mean = (fb && fb.frameMean) ?? (g.status && g.status.frameMean)
  if (reason === 'budget') return `GL fell back: budget at ${mean ?? '?'} ms mean frame time (t=${fb ? fb.t : '?'} ms)`
  if (reason) return `GL fell back: ${reason}${fb ? ' (t=' + fb.t + ' ms)' : ''}`
  if (!g.events.length) return `GL path did not run: no sf:motion:hero event (state ${g.status ? g.status.state : 'unknown'})`
  return `GL not live: last event ${JSON.stringify(g.last)}, canvas ${g.canvas ? 'present' : 'missing'}`
}
const rect = await page.evaluate(() => {
  const c = document.querySelector('.hero__ribbons')
  if (!c) return null
  const r = c.getBoundingClientRect()
  const x = Math.max(0, Math.floor(r.left)), y = Math.max(0, Math.floor(r.top))
  return { x, y, width: Math.min(innerWidth, Math.ceil(r.right)) - x, height: Math.min(innerHeight, Math.ceil(r.bottom)) - y, opacity: getComputedStyle(c).opacity }
})
let sample = null
const glBefore = await glState()
const glFailure = fellBack(glBefore)
console.log('hero events:', JSON.stringify(glBefore.events))
if (glFailure) {
  failures.push(glFailure)
} else if (!rect || rect.width < 8 || rect.height < 8) {
  const now = await page.evaluate(() => { const h = document.querySelector('[data-motion="hero"]'); return h ? h.className + ' plates=' + h.querySelectorAll('.hero__plate').length : 'no hero' })
  failures.push(`ribbon canvas .hero__ribbons not present (GL path did not run): ${now}`)
} else {
  await page.addStyleTag({ content: '.hero__body > :not(.hero__glow), .hero__cue, .site-header, .announcement, .whatsapp-fab, .sf-cursor { visibility: hidden !important }' })
  await page.waitForTimeout(120)
  const withBuf = await page.screenshot({ clip: rect })
  const glAfter = await glState()
  await page.addStyleTag({ content: '.hero__glow canvas { visibility: hidden !important }' })
  await page.waitForTimeout(120)
  const groundBuf = await page.screenshot({ clip: rect })
  const midFailure = fellBack(glAfter)
  if (midFailure) {
    failures.push(midFailure + ' while the ribbon sample was being taken; sample discarded')
  } else {
    await sharp(withBuf).toFile(`${out}/hero-ribbons-sample${sfx}.png`)
    await sharp(groundBuf).toFile(`${out}/hero-ribbons-ground${sfx}.png`)
    const a = await sharp(withBuf).removeAlpha().raw().toBuffer()
    const g = await sharp(groundBuf).removeAlpha().raw().toBuffer()
    const lin = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4) }
    const lum = (b, i) => 0.2126 * lin(b[i]) + 0.7152 * lin(b[i + 1]) + 0.0722 * lin(b[i + 2])
    let total = 0, ribbon = 0, darker = 0, pale = 0, groundSum = 0, ribbonSum = 0
    for (let i = 0; i < a.length; i += 3) {
      total++
      const la = lum(a, i), lg = lum(g, i)
      groundSum += lg
      const d = Math.max(Math.abs(a[i] - g[i]), Math.abs(a[i + 1] - g[i + 1]), Math.abs(a[i + 2] - g[i + 2]))
      if (d <= 12) continue
      ribbon++
      ribbonSum += la
      if (la < lg - 0.01) darker++
      else if (la > lg + 0.01) pale++
    }
    sample = {
      rect, pixels: total, ribbonPx: ribbon, ribbonShare: +(ribbon / total * 100).toFixed(2),
      darkerPx: darker, palePx: pale, paleShareOfRibbon: ribbon ? +(pale / ribbon * 100).toFixed(2) : 0,
      groundLum: +(groundSum / total).toFixed(4), ribbonLum: ribbon ? +(ribbonSum / ribbon).toFixed(4) : null,
    }
    console.log('ribbon sample:', JSON.stringify(sample))
    if (sample.ribbonShare < 0.5) failures.push(`ribbons barely visible: ${sample.ribbonShare}% of the canvas differs from the ground`)
    if (label === 'light' && sample.paleShareOfRibbon > 5) failures.push(`pale holes: ${sample.paleShareOfRibbon}% of ribbon pixels are lighter than the ground`)
    if (label === 'light' && sample.ribbonLum !== null && sample.ribbonLum >= sample.groundLum) failures.push(`ribbons are not darker than the ground (${sample.ribbonLum} vs ${sample.groundLum})`)
  }
}

const plateUrls = [...new Set(motionUrls)]
console.log('motion assets requested:', plateUrls.join(', ') || 'none')
if (expectTheme && st.theme !== expectTheme) failures.push(`data-theme is ${st.theme === null ? 'absent' : '"' + st.theme + '"'}, expected "${expectTheme}"`)
if (label === 'light') {
  if (plateUrls.length && !plateUrls.some((u) => /^ribbons-[ab]-light-\d+\.webp$/.test(u))) failures.push('plates requested but no ribbons-*-light-*.webp among them')
  const darkPlates = plateUrls.filter((u) => /^ribbons-[ab]-\d+\.webp$/.test(u))
  if (darkPlates.length) failures.push('dark plate requested on light: ' + darkPlates.join(', '))
} else if (plateUrls.some((u) => /-light-/.test(u))) failures.push('light plate requested on dark: ' + plateUrls.filter((u) => /-light-/.test(u)).join(', '))
if (errors.length) failures.push('console errors: ' + errors.join(' | '))
await browser.close()
console.log(failures.length ? `\nFAIL (${failures.length}):\n- ` + failures.join('\n- ') : `\nPASS (${label || 'theme not asserted'})`)
process.exit(failures.length ? 1 : 0)
