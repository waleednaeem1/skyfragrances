import { chromium, devices } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync } from 'node:fs'
const base = (process.argv[2] || 'http://127.0.0.1:8091').replace(/\/$/, '')
const out = process.argv[3] || 'docs/shots/hero-mobile'
const expectTheme = process.env.EXPECT_THEME || ''
mkdirSync(out, { recursive: true })
const failures = []
const browser = await chromium.launch()
const ctx = await browser.newContext({ ...devices['Pixel 5'] })
const page = await ctx.newPage()
const errors = []
const motionUrls = []
page.on('pageerror', (e) => errors.push('page: ' + e.message.slice(0, 160)))
page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 160)) })
page.on('request', (r) => { if (/\/assets\/img\/motion\//.test(r.url())) motionUrls.push(r.url().split('/').pop().split('?')[0]) })
await page.addInitScript(() => { sessionStorage.setItem('sfIntro', '1') })
await page.goto(base + '/', { waitUntil: 'networkidle' })
await page.waitForTimeout(3000)
const st = await page.evaluate(() => { const h = document.querySelector('.hero'); return { theme: document.documentElement.getAttribute('data-theme'), html: document.documentElement.className, hero: h.className, plates: [...h.querySelectorAll('.hero__plate')].map(p => p.className.replace('hero__plate ', '') + ' op=' + getComputedStyle(p).opacity), gl: !!h.querySelector('.hero__ribbons') } })
console.log(JSON.stringify(st))
const label = expectTheme || st.theme || ''
await page.screenshot({ path: `${out}/hero-393${label === 'light' ? '--light' : ''}.png`, clip: { x: 0, y: 0, width: 393, height: 851 } })
await browser.close()
const plateUrls = [...new Set(motionUrls)]
console.log('motion assets requested:', plateUrls.join(', ') || 'none')
if (expectTheme && st.theme !== expectTheme) failures.push(`data-theme is ${st.theme === null ? 'absent' : '"' + st.theme + '"'}, expected "${expectTheme}"`)
if (st.gl) failures.push('GL ribbons ran on a phone')
if (label === 'light') {
  if (st.plates.length && !plateUrls.some((u) => /-light-/.test(u))) failures.push('plates shown but no ribbons-*-light-*.webp requested')
  const darkPlates = plateUrls.filter((u) => /^ribbons-[ab]-\d+\.webp$/.test(u))
  if (darkPlates.length) failures.push('dark plate requested on light: ' + darkPlates.join(', '))
} else if (plateUrls.some((u) => /-light-/.test(u))) failures.push('light plate requested on dark')
if (errors.length) failures.push('console errors: ' + errors.join(' | '))
console.log(failures.length ? `\nFAIL (${failures.length}):\n- ` + failures.join('\n- ') : `\nPASS (${label || 'theme not asserted'})`)
process.exit(failures.length ? 1 : 0)
