import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import { mkdirSync, writeFileSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const base = process.argv[2] || 'http://127.0.0.1:8091'
const out = process.argv[3] || 'docs/shots/plates'
const ground = process.argv[4] || '#0A0A0A'
const suffix = process.argv[5] || ''
const scene = process.argv[6] === 'scene'
const arch = [
  [[-5.6, -1.0, 0.2], [-3.9, 0.7, 0.1], [-2.8, 1.85, 0.0], [-1.1, 2.05, -0.1], [0.6, 1.97, -0.1], [2.3, 1.7, 0.0], [3.9, 0.42, 0.1], [5.6, -1.5, 0.2]],
  [[-5.6, -2.6, 0.2], [-3.9, -0.85, 0.1], [-2.8, 0.47, -0.1], [-1.1, 1.54, -0.2], [0.6, 1.66, -0.1], [2.3, 1.33, -0.1], [3.9, -0.2, 0.0], [5.6, -2.0, 0.1]],
  [[-5.6, -3.2, 0.1], [-3.9, -1.5, 0.2], [-2.8, -0.13, 0.0], [-1.1, 1.19, -0.2], [0.6, 1.19, -0.1], [2.3, 0.73, -0.1], [3.9, -1.2, 0.0], [5.6, -3.0, 0.1]]
]
const seconds = [2.5, 6.5]
const assets = fileURLToPath(new URL('../site/assets/img/motion/', import.meta.url))
mkdirSync(out, { recursive: true })

const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] })
const ctx = await browser.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 })
const page = await ctx.newPage()
await page.addInitScript(() => { sessionStorage.setItem('sfIntro', '1') })
if (!scene) {
  await page.addInitScript(([paths, bare]) => {
    let held
    Object.defineProperty(window, 'SF_MOTION', {
      configurable: true,
      get: () => held,
      set: (value) => {
        held = value
        if (value && value.hero && value.hero.ribbons) {
          value.hero.ribbons.paths = paths
          if (bare) {
            value.hero.sprite.opacity = 0.0001
          }
        }
      }
    })
  }, [arch, ground === 'transparent'])
}
await page.goto(base + '/?plates=1', { waitUntil: 'networkidle' })
await page.waitForSelector('.hero.is-gl', { timeout: 15000 })
await page.evaluate((ground) => {
  const style = document.createElement('style')
  style.textContent = '.hero > *:not(.hero__glow):not(.hero__ribbons), .hero__object, .hero__plate, .announcement, .site-header, .whatsapp-fab, .hero__cue { visibility: hidden !important; } .hero__ribbons { visibility: visible !important; opacity: 1 !important; } .hero__glow::before { opacity: 0 !important; } body { background: ' + ground + ' !important; }' + (ground.toUpperCase() === '#0A0A0A' ? '' : ' html, .site-main, .hero { background: ' + ground + ' !important; } .hero::before, .hero::after { display: none !important; }')
  document.head.appendChild(style)
}, ground)
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
  const file = `${out}/plate-${letter}${suffix}-raw.png`
  await page.screenshot({ path: file, clip, omitBackground: ground === 'transparent' })
  info.push({ letter, clip, file })
  for (const width of [540, 960]) {
    const alpha = ground === 'transparent' ? ['-alpha_q', '50', '-alpha_filter', 'best', '-m', '6'] : []
    execFileSync('cwebp', ['-quiet', '-q', '82', ...alpha, '-resize', String(width), '0', file, '-o', `${assets}ribbons-${letter}${suffix}-${width}.webp`])
  }
}
writeFileSync(`${out}/frame${suffix}.json`, JSON.stringify({ frame, info }, null, 2))
console.log(JSON.stringify({ frame, info }, null, 1))
await browser.close()
