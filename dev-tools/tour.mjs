import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync, writeFileSync } from 'node:fs'

const base = process.argv[2] || 'http://127.0.0.1:8088'
const out = process.argv[3] || 'shots'
const paths = process.argv.slice(4)
const cookieArg = process.env.TOUR_COOKIE || ''
const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 375, height: 812, isMobile: true, hasTouch: true, deviceScaleFactor: 2 },
]

mkdirSync(out, { recursive: true })
const browser = await chromium.launch()
const report = []

for (const vp of viewports) {
  const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, isMobile: !!vp.isMobile, hasTouch: !!vp.hasTouch, deviceScaleFactor: vp.deviceScaleFactor || 1 })
  if (cookieArg) {
    const [name, value] = cookieArg.split('=')
    await ctx.addCookies([{ name, value, url: base }])
  }
  const page = await ctx.newPage()
  const errors = []
  page.on('pageerror', e => errors.push(e.message.slice(0, 200)))
  page.on('console', m => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 200)) })
  page.on('response', r => { if (r.status() >= 400 && !r.url().includes('favicon')) errors.push(`${r.status()} ${r.url()}`) })
  for (const p of paths) {
    const slug = p.replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '') || 'home'
    const file = `${out}/${slug}--${vp.name}.png`
    errors.length = 0
    const res = await page.goto(base + p, { waitUntil: 'networkidle', timeout: 30000 }).catch(e => ({ status: () => 'ERR ' + e.message.slice(0, 80) }))
    await page.evaluate(async () => {
      const step = Math.max(400, window.innerHeight * 0.8)
      for (let y = 0; y < document.body.scrollHeight; y += step) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 90)) }
      window.scrollTo(0, 0); await new Promise(r => setTimeout(r, 250))
    }).catch(() => {})
    await page.screenshot({ path: file, fullPage: true })
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1).catch(() => null)
    report.push({ path: p, viewport: vp.name, status: res.status(), file, horizontalOverflow: overflow, errors: [...errors] })
  }
  await ctx.close()
}
await browser.close()
writeFileSync(`${out}/report.json`, JSON.stringify(report, null, 2))
for (const r of report) console.log(`${String(r.status).padEnd(4)} ${r.viewport.padEnd(7)} ${r.path.padEnd(32)} ${r.horizontalOverflow ? 'H-OVERFLOW ' : ''}${r.errors.length ? r.errors.length + ' err' : ''}`)
