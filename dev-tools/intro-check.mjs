import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync } from 'node:fs'
const base = process.argv[2] || 'http://127.0.0.1:8089'
const out = process.argv[3] || 'docs/shots/intro'
mkdirSync(out, { recursive: true })
const browser = await chromium.launch()
async function probe(name, ctxOpts) {
  const ctx = await browser.newContext(ctxOpts)
  const page = await ctx.newPage()
  const errors = []
  page.on('pageerror', e => errors.push(e.message.slice(0, 120)))
  await page.goto(base + '/', { waitUntil: 'domcontentloaded' })
  const t0 = Date.now()
  const shots = []
  for (const at of [150, 700, 1200, 1700, 2300]) {
    const wait = at - (Date.now() - t0)
    if (wait > 0) await page.waitForTimeout(wait)
    await page.screenshot({ path: `${out}/${name}-${at}ms.png` })
    const st = await page.evaluate(() => { const el = document.getElementById('sf-intro'); return { html: document.documentElement.className, overlay: el ? el.className : 'gone', opacity: el ? getComputedStyle(el).opacity : null } })
    shots.push(`${at}ms: ${st.overlay} (opacity ${st.opacity})`)
  }
  const ctaClickable = await page.evaluate(() => { const b = document.querySelector('.hero .btn'); if (!b) return null; const r = b.getBoundingClientRect(); const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2); return !!(hit && (hit === b || b.contains(hit))) })
  console.log(`\n== ${name}\n  ${shots.join('\n  ')}\n  hero CTA clickable at 2.3s: ${ctaClickable}\n  errors: ${errors.length ? errors.join(' | ') : 'none'}`)
  await ctx.close()
}
await probe('calm-reduced-motion', { viewport: { width: 1280, height: 800 }, reducedMotion: 'reduce' })
await probe('full-360', { viewport: { width: 360, height: 740 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })
await browser.close()
