import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync, writeFileSync } from 'node:fs'

const base = process.argv[2] || 'http://127.0.0.1:8091'
const out = process.argv[3] || 'docs/shots/plates'
const seconds = [2.5, 6.5]
mkdirSync(out, { recursive: true })

const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] })
const ctx = await browser.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 })
const page = await ctx.newPage()
await page.addInitScript(() => { sessionStorage.setItem('sfIntro', '1') })
await page.goto(base + '/?plates=1', { waitUntil: 'networkidle' })
await page.waitForSelector('.hero.is-gl', { timeout: 15000 })
await page.evaluate(() => {
  const style = document.createElement('style')
  style.textContent = '.hero > *:not(.hero__glow):not(.hero__ribbons), .hero__object, .hero__plate, .announcement, .site-header, .whatsapp-fab, .hero__cue { visibility: hidden !important; } .hero__ribbons { visibility: visible !important; opacity: 1 !important; } .hero__glow::before { opacity: 0 !important; } body { background: #0A0A0A !important; }'
  document.head.appendChild(style)
})
const frame = await page.evaluate(() => {
  const hero = document.querySelector('.hero')
  const obj = document.querySelector('.hero__object')
  const hr = hero.getBoundingClientRect()
  const or = obj.getBoundingClientRect()
  const unit = or.height / 0.9
  return { unit, cx: or.left + or.width / 2, cy: or.top + or.height / 2, heroTop: hr.top, heroH: hr.height }
})
const info = []
for (let i = 0; i < seconds.length; i++) {
  await page.waitForTimeout(i === 0 ? seconds[0] * 1000 : (seconds[i] - seconds[i - 1]) * 1000)
  const letter = i === 0 ? 'a' : 'b'
  const w = 9 * frame.unit
  const h = w / 2
  const clip = { x: Math.round(frame.cx - w / 2), y: Math.round(frame.cy - h / 2), width: Math.round(w), height: Math.round(h) }
  const file = `${out}/plate-${letter}-raw.png`
  await page.screenshot({ path: file, clip })
  info.push({ letter, clip, file })
}
writeFileSync(`${out}/frame.json`, JSON.stringify({ frame, info }, null, 2))
console.log(JSON.stringify({ frame, info }, null, 1))
await browser.close()
