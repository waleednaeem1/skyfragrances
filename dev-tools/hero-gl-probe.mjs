import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
const base = process.argv[2] || 'http://127.0.0.1:8091'
const out = process.argv[3] || 'docs/shots/hero-gl'
import { mkdirSync } from 'node:fs'
mkdirSync(out, { recursive: true })
const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] })
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 })
const page = await ctx.newPage()
const events = []
await page.addInitScript(() => { sessionStorage.setItem('sfIntro', '1'); window.__heroEvents = []; document.addEventListener('sf:hero', e => window.__heroEvents.push(JSON.stringify(e.detail))) })
await page.goto(base + '/', { waitUntil: 'networkidle' })
await page.waitForTimeout(6000)
const st = await page.evaluate(() => { const h = document.querySelector('[data-motion="hero"]'); return { cls: h.className, events: window.__heroEvents, canvases: [...h.querySelectorAll('canvas')].map(c => c.className + ' ' + c.width + 'x' + c.height + ' op=' + getComputedStyle(c).opacity), gl: (() => { try { const c = document.createElement('canvas'); return !!c.getContext('webgl2') } catch (e) { return false } })(), mem: navigator.deviceMemory, cores: navigator.hardwareConcurrency } })
console.log(JSON.stringify(st, null, 1))
await page.screenshot({ path: `${out}/hero-1440-6s.png`, clip: { x: 0, y: 0, width: 1440, height: 900 } })
await page.waitForTimeout(4000)
await page.screenshot({ path: `${out}/hero-1440-10s.png`, clip: { x: 0, y: 0, width: 1440, height: 900 } })
await browser.close()
