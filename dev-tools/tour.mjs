import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync, writeFileSync } from 'node:fs'

const base = process.argv[2] || 'http://127.0.0.1:8088'
const out = process.argv[3] || 'shots'
const paths = process.argv.slice(4)
const cookieArg = process.env.TOUR_COOKIE || ''
const userAgent = process.env.TOUR_UA || ''
const expectTheme = process.env.EXPECT_THEME || process.env.TOUR_THEME || ''
const maskSelectors = process.env.TOUR_MASKS || ''
const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 375, height: 812, isMobile: true, hasTouch: true, deviceScaleFactor: 2 },
]

mkdirSync(out, { recursive: true })
const browser = await chromium.launch()
const report = []

for (const vp of viewports) {
  const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, isMobile: !!vp.isMobile, hasTouch: !!vp.hasTouch, deviceScaleFactor: vp.deviceScaleFactor || 1, ...(userAgent ? { userAgent } : {}) })
  if (cookieArg) {
    const [name, value] = cookieArg.split('=')
    await ctx.addCookies([{ name, value, url: base }])
  }
  const page = await ctx.newPage()
  const errors = []
  page.on('pageerror', e => errors.push(e.message.slice(0, 200)))
  let documentUrl = ''
  page.on('console', m => { if (m.type() === 'error' && (m.location().url || '') !== documentUrl) errors.push('console: ' + m.text().slice(0, 200)) })
  page.on('response', r => { if (r.status() >= 400 && !r.url().includes('favicon') && !(r.request().isNavigationRequest() && r.request().frame() === page.mainFrame())) errors.push(`${r.status()} ${r.url()}`) })
  for (const p of paths) {
    const slug = p.replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '') || 'home'
    errors.length = 0
    documentUrl = base + p
    const res = await page.goto(base + p, { waitUntil: 'networkidle', timeout: 30000 }).catch(e => ({ status: () => 'ERR ' + e.message.slice(0, 80) }))
    await page.evaluate(async () => {
      const step = Math.max(400, window.innerHeight * 0.8)
      for (let y = 0; y < document.body.scrollHeight; y += step) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 250)) }
      window.scrollTo(0, 0); await new Promise(r => setTimeout(r, 900))
      document.querySelectorAll('.sf-reveal').forEach(e => e.classList.add('is-visible'))
      const style = document.createElement('style')
      style.textContent = '*,*::before,*::after{transition-duration:0s!important;transition-delay:0s!important;animation-duration:0s!important;animation-delay:0s!important}'
      document.head.appendChild(style)
      await new Promise(r => setTimeout(r, 400))
    }).catch(() => {})
    await page.evaluate(() => {
      document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager' })
      const pending = [...document.images].filter(i => !i.complete).map(i => new Promise(r => { i.addEventListener('load', r, { once: true }); i.addEventListener('error', r, { once: true }) }))
      return Promise.race([Promise.all(pending), new Promise(r => setTimeout(r, 4000))])
    }).catch(() => {})
    const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme')).catch(() => null)
    const isAdmin = /^\/admin(\/|$|\?)/.test(p)
    const label = isAdmin ? '' : (expectTheme || theme || '')
    const file = `${out}/${slug}--${vp.name}${label === 'light' ? '--light' : ''}.png`
    const measureMasks = () => maskSelectors ? page.evaluate((sel) => {
      const out = []
      for (const el of document.querySelectorAll(sel)) {
        const r = el.getBoundingClientRect()
        if (r.width < 1 || r.height < 1) continue
        let shown = true
        for (let n = el; n && n !== document; n = n.parentElement) { const cs = getComputedStyle(n); if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) < 0.01) { shown = false; break } }
        if (!shown) continue
        const fixed = (() => { for (let n = el; n && n !== document.body; n = n.parentElement) { if (getComputedStyle(n).position === 'fixed') return true } return false })()
        out.push({ x: Math.floor(r.left + (fixed ? 0 : scrollX)), y: Math.floor(r.top + (fixed ? 0 : scrollY)), w: Math.ceil(r.width), h: Math.ceil(r.height), fixed, sel: (el.id ? '#' + el.id : '') + '.' + String(el.className && el.className.baseVal !== undefined ? el.className.baseVal : el.className).trim().split(/\s+/).slice(0, 2).join('.') })
      }
      return out
    }, maskSelectors).catch(() => []) : Promise.resolve(undefined)
    const masksBefore = await measureMasks()
    await page.screenshot({ path: file, fullPage: true })
    const masksAfter = await measureMasks()
    const masks = masksBefore || masksAfter ? [...(masksBefore || []), ...(masksAfter || [])] : undefined
    await page.waitForTimeout(400)
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1).catch(() => null)
    let themeError = ''
    if (isAdmin && theme !== null) themeError = `admin <html> carries data-theme="${theme}"`
    else if (!isAdmin && expectTheme && theme !== expectTheme) themeError = `data-theme is ${theme === null ? 'absent' : '"' + theme + '"'}, expected "${expectTheme}"`
    report.push({ path: p, viewport: vp.name, status: res.status(), file, theme, themeError, horizontalOverflow: overflow, errors: [...errors], ...(masks ? { masks } : {}) })
  }
  await ctx.close()
}
await browser.close()
const reportName = expectTheme === 'light' ? 'report--light.json' : 'report.json'
writeFileSync(`${out}/${reportName}`, JSON.stringify(report, null, 2))
for (const r of report) console.log(`${String(r.status).padEnd(4)} ${r.viewport.padEnd(7)} ${r.path.padEnd(32)} ${String(r.theme ?? '-').padEnd(6)} ${r.horizontalOverflow ? 'H-OVERFLOW ' : ''}${r.errors.length ? r.errors.length + ' err ' : ''}${r.themeError ? 'THEME: ' + r.themeError : ''}`)
const themeFailures = report.filter(r => r.themeError).length
if (expectTheme || themeFailures) console.log(themeFailures ? `\n${themeFailures} theme assertion failure(s)` : `\nall pages report data-theme="${expectTheme}"`)
process.exit(themeFailures ? 1 : 0)
