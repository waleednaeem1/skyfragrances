import { chromium, devices } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync } from 'node:fs'
const base = process.argv[2] || 'http://127.0.0.1:8091'
const out = process.argv[3] || 'docs/shots/hero-mobile'
mkdirSync(out, { recursive: true })
const browser = await chromium.launch()
const ctx = await browser.newContext({ ...devices['Pixel 5'] })
const page = await ctx.newPage()
await page.addInitScript(() => { sessionStorage.setItem('sfIntro', '1') })
await page.goto(base + '/', { waitUntil: 'networkidle' })
await page.waitForTimeout(3000)
const st = await page.evaluate(() => { const h = document.querySelector('.hero'); return { html: document.documentElement.className, hero: h.className, plates: [...h.querySelectorAll('.hero__plate')].map(p => p.className.replace('hero__plate ', '') + ' op=' + getComputedStyle(p).opacity), gl: !!h.querySelector('.hero__ribbons') } })
console.log(JSON.stringify(st))
await page.screenshot({ path: `${out}/hero-393.png`, clip: { x: 0, y: 0, width: 393, height: 851 } })
await browser.close()
