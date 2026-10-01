import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import sharp from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/sharp/lib/index.js'
import { spawn } from 'node:child_process'
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { basename, dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const here = dirname(fileURLToPath(import.meta.url))
const base = (process.argv[2] || '').replace(/\/$/, '')
const baselineDir = process.argv[3] || process.env.BASELINE || ''
const outDir = process.argv[4] || 'shots/theme-diff'
if (!base || !baselineDir) {
  console.error('Usage: node dev-tools/theme-diff.mjs <base-url> <baseline-dir> [out-dir]')
  process.exit(2)
}
const tolerance = Number(process.env.TOLERANCE ?? 2)
const pad = Number(process.env.MASK_PAD ?? 6)
const fixedPad = Number(process.env.MASK_PAD_FIXED ?? 32)
const expectTheme = process.env.EXPECT_THEME || 'dark'
const only = process.env.ONLY ? process.env.ONLY.split(',') : null
const DEFAULT_MASKS = [
  '.hero__ribbons', '.hero__plate', '.hero__glow', '.hero canvas',
  '#sf-intro', '.sf-intro', '.sf-cursor', '.sf-veil', '.sf-spray', '.sf-burst', '.sf-fly',
  '.whatsapp-fab', 'canvas', 'video',
]
const masks = [...DEFAULT_MASKS, ...(process.env.MASK_EXTRA ? process.env.MASK_EXTRA.split(',') : [])].join(', ')
const STYLE_PAGES = ['/', '/product/azure-oud', '/shop', '/checkout']
const STYLE_PROPS = ['color', 'background-color', 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color', 'box-shadow', 'filter', 'background-image']
const STYLE_PROFILES = [
  { name: 'desktop-1440-reduced', ctx: { viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' } },
  { name: 'mobile-375', ctx: { viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } },
]

mkdirSync(outDir, { recursive: true })
const shotsDir = join(outDir, 'shots')
const diffDir = join(outDir, 'diff')
mkdirSync(diffDir, { recursive: true })

const baselineReport = JSON.parse(readFileSync(join(baselineDir, 'report.json'), 'utf8'))
const paths = [...new Set(baselineReport.map((r) => r.path))].filter((p) => !only || only.includes(p))
const summary = { base, baselineDir, tolerance, maskPad: pad, maskPadFixed: fixedPad, masks, expectTheme, pixels: [], styles: null, failures: [] }

function runTour() {
  return new Promise((resolve) => {
    const child = spawn(process.execPath, [join(here, 'tour.mjs'), base, shotsDir, ...paths], {
      env: { ...process.env, TOUR_MASKS: masks, EXPECT_THEME: expectTheme },
      stdio: ['ignore', 'inherit', 'inherit'],
    })
    child.on('exit', (code) => resolve(code))
  })
}

async function readRaw(file) {
  const { data, info } = await sharp(file).ensureAlpha().raw().toBuffer({ resolveWithObject: true })
  return { data, width: info.width, height: info.height }
}

function maskBitmap(width, height, rects, scale) {
  const m = new Uint8Array(width * height)
  for (const r of rects) {
    const p = r.fixed ? fixedPad : pad
    const py = r.fixed ? Math.max(fixedPad, r.h + fixedPad) : pad
    const x0 = Math.max(0, Math.floor((r.x - p) * scale))
    const y0 = Math.max(0, Math.floor((r.y - py) * scale))
    const x1 = Math.min(width, Math.ceil((r.x + r.w + p) * scale))
    const y1 = Math.min(height, Math.ceil((r.y + r.h + py) * scale))
    for (let y = y0; y < y1; y++) m.fill(1, y * width + x0, y * width + x1)
  }
  return m
}

async function diffPair(entry, current) {
  const baseFile = join(baselineDir, basename(entry.file))
  const newFile = current.file
  if (!existsSync(baseFile)) return { error: 'baseline missing ' + baseFile }
  if (!existsSync(newFile)) return { error: 'capture missing ' + newFile }
  const a = await readRaw(baseFile)
  const b = await readRaw(newFile)
  const width = Math.min(a.width, b.width)
  const height = Math.min(a.height, b.height)
  const scale = entry.viewport === 'mobile' ? 2 : 1
  const mask = maskBitmap(width, height, current.masks || [], scale)
  const vis = Buffer.alloc(width * height * 4)
  let differing = 0
  let masked = 0
  let firstY = -1
  let lastY = -1
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const i = y * width + x
      const ia = (y * a.width + x) * 4
      const ib = (y * b.width + x) * 4
      const o = i * 4
      if (mask[i]) {
        masked++
        vis[o] = vis[o + 1] = vis[o + 2] = 90
        vis[o + 3] = 255
        continue
      }
      const d = Math.max(Math.abs(a.data[ia] - b.data[ib]), Math.abs(a.data[ia + 1] - b.data[ib + 1]), Math.abs(a.data[ia + 2] - b.data[ib + 2]))
      if (d > tolerance) {
        differing++
        if (firstY < 0) firstY = y
        lastY = y
        vis[o] = 255
        vis[o + 1] = 0
        vis[o + 2] = 40
      } else {
        const g = Math.round((b.data[ib] + b.data[ib + 1] + b.data[ib + 2]) / 12)
        vis[o] = vis[o + 1] = vis[o + 2] = g
      }
      vis[o + 3] = 255
    }
  }
  let diffFile = ''
  if (differing) {
    diffFile = join(diffDir, basename(newFile))
    await sharp(vis, { raw: { width, height, channels: 4 } }).png({ compressionLevel: 3 }).toFile(diffFile)
  }
  const sizeMismatch = a.width !== b.width || a.height !== b.height ? `${a.width}x${a.height} -> ${b.width}x${b.height}` : ''
  return { differing, masked, sizeMismatch, band: differing ? [Math.round(firstY / scale), Math.round(lastY / scale)] : null, diffFile }
}

const captureStyles = (props) => {
  const skip = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE', 'LINK', 'META', 'TITLE', 'HEAD'])
  const norm = (v) => v.replace(/https?:\/\/(127\.0\.0\.1|localhost|\[::1\])(:\d+)?/g, '').replace(/([?&])v=[^"')&]*/g, '$1v=*')
  const key = (el) => {
    const parts = []
    for (let n = el; n && n.nodeType === 1; n = n.parentElement) {
      const idx = n.parentElement ? Array.prototype.indexOf.call(n.parentElement.children, n) : 0
      parts.unshift(n.tagName.toLowerCase() + (n.id ? '#' + n.id : '') + ':' + idx)
    }
    return parts.join('>')
  }
  const out = {}
  const grab = (el, pseudo) => {
    const cs = getComputedStyle(el, pseudo)
    if (pseudo && (cs.content === 'none' || cs.content === 'normal')) return null
    const row = {}
    for (const p of props) row[p] = norm(cs.getPropertyValue(p))
    return row
  }
  for (const el of [document.documentElement, ...document.querySelectorAll('body, body *')]) {
    if (skip.has(el.tagName) || el.tagName === 'CANVAS' || el.closest('svg') && el.tagName.toLowerCase() !== 'svg') continue
    const k = key(el)
    out[k] = grab(el, null)
    const before = grab(el, '::before')
    const after = grab(el, '::after')
    if (before) out[k + '::before'] = before
    if (after) out[k + '::after'] = after
  }
  return out
}

async function styleCapture(browser, url) {
  const result = {}
  for (const profile of STYLE_PROFILES) {
    const ctx = await browser.newContext(profile.ctx)
    await ctx.addInitScript(() => { try { sessionStorage.setItem('sfIntro', '1') } catch (e) {} })
    const page = await ctx.newPage()
    for (const p of STYLE_PAGES) {
      await page.goto(url + p, { waitUntil: 'load', timeout: 30000 })
      await page.waitForFunction(() => !document.getElementById('sf-intro'), null, { timeout: 6000 }).catch(() => {})
      await page.waitForTimeout(1200)
      await page.evaluate(() => window.scrollTo(0, 0))
      await page.waitForTimeout(300)
      result[`${profile.name} ${p}`] = await page.evaluate(captureStyles, STYLE_PROPS)
    }
    await ctx.close()
  }
  return result
}

function compareStyles(ref, cur) {
  const ignored = (k) => /(^|>)canvas:\d+/.test(k)
  for (const map of [...Object.values(ref), ...Object.values(cur)]) for (const k of Object.keys(map)) if (ignored(k)) delete map[k]
  const diffs = []
  const counts = {}
  for (const [pageKey, refMap] of Object.entries(ref)) {
    const curMap = cur[pageKey]
    let n = 0
    if (!curMap) { diffs.push({ page: pageKey, el: '*', prop: 'page', before: 'captured', after: 'missing' }); counts[pageKey] = 1; continue }
    for (const [el, refRow] of Object.entries(refMap)) {
      const curRow = curMap[el]
      if (!curRow) { n++; diffs.push({ page: pageKey, el, prop: 'element', before: 'present', after: 'missing' }); continue }
      for (const [prop, value] of Object.entries(refRow)) {
        if (curRow[prop] !== value) { n++; diffs.push({ page: pageKey, el, prop, before: value, after: curRow[prop] }) }
      }
    }
    for (const el of Object.keys(curMap)) if (!refMap[el]) { n++; diffs.push({ page: pageKey, el, prop: 'element', before: 'missing', after: 'present' }) }
    counts[pageKey] = n
  }
  return { counts, diffs }
}

const t0 = Date.now()
if (!process.env.SKIP_PIXELS) {
  console.log(`capturing ${paths.length} routes x 2 viewports from ${base} with tour.mjs`)
  const code = await runTour()
  if (code !== 0) summary.failures.push(`tour.mjs exited ${code} (theme assertion or crash)`)
  const current = JSON.parse(readFileSync(join(shotsDir, expectTheme === 'light' ? 'report--light.json' : 'report.json'), 'utf8'))
  for (const entry of baselineReport) {
    if (!paths.includes(entry.path)) continue
    const cur = current.find((r) => r.path === entry.path && r.viewport === entry.viewport)
    if (!cur) { summary.failures.push(`${entry.viewport} ${entry.path}: not captured`); continue }
    const r = await diffPair(entry, cur)
    const row = { path: entry.path, viewport: entry.viewport, status: cur.status, ...r, masks: (cur.masks || []).length, errors: cur.errors.length }
    summary.pixels.push(row)
    if (r.error) summary.failures.push(`${entry.viewport} ${entry.path}: ${r.error}`)
    else if (r.differing || r.sizeMismatch) summary.failures.push(`${entry.viewport} ${entry.path}: ${r.differing} differing px${r.sizeMismatch ? ', size ' + r.sizeMismatch : ''}`)
    if (cur.errors.length) summary.failures.push(`${entry.viewport} ${entry.path}: ${cur.errors.length} console/network error(s)`)
  }
}

if (!process.env.SKIP_STYLES) {
  const browser = await chromium.launch()
  const styleOut = process.env.STYLE_OUT || join(outDir, 'styles-current.json')
  const current = await styleCapture(browser, base)
  writeFileSync(styleOut, JSON.stringify(current))
  let ref = null
  let refLabel = ''
  if (process.env.STYLE_REF_BASE) {
    const refUrl = process.env.STYLE_REF_BASE.replace(/\/$/, '')
    ref = await styleCapture(browser, refUrl)
    refLabel = refUrl
    writeFileSync(join(outDir, 'styles-reference.json'), JSON.stringify(ref))
  } else if (process.env.STYLE_REF && existsSync(process.env.STYLE_REF)) {
    ref = JSON.parse(readFileSync(process.env.STYLE_REF, 'utf8'))
    refLabel = process.env.STYLE_REF
  }
  await browser.close()
  if (ref) {
    const cmp = compareStyles(ref, current)
    summary.styles = { reference: refLabel, counts: cmp.counts, diffs: cmp.diffs.slice(0, 500), total: cmp.diffs.length }
    if (cmp.diffs.length) summary.failures.push(`computed styles: ${cmp.diffs.length} difference(s) against ${refLabel}`)
  } else {
    summary.styles = { reference: null, note: `no STYLE_REF or STYLE_REF_BASE; capture written to ${styleOut} for use as a reference` }
  }
}

writeFileSync(join(outDir, 'theme-diff.json'), JSON.stringify(summary, null, 2))
const col = (v, n) => String(v ?? '').padEnd(n)
if (summary.pixels.length) {
  console.log('\n' + col('viewport', 9) + col('path', 30) + col('differing', 11) + col('masked', 10) + col('size', 26) + 'band (css px)')
  for (const r of summary.pixels) console.log(col(r.viewport, 9) + col(r.path, 30) + col(r.error ? 'ERR' : r.differing, 11) + col(r.masked, 10) + col(r.sizeMismatch || 'same', 26) + (r.band ? r.band.join('-') : '') + (r.error ? ' ' + r.error : ''))
  const total = summary.pixels.reduce((a, r) => a + (r.differing || 0), 0)
  console.log(`\npixel diff: ${total} differing px outside masks across ${summary.pixels.length} shots (tolerance ${tolerance}/channel)`)
}
if (summary.styles && summary.styles.counts) {
  console.log(`computed styles vs ${summary.styles.reference}:`)
  for (const [k, n] of Object.entries(summary.styles.counts)) console.log(`  ${col(k, 44)} ${n}`)
  for (const d of summary.styles.diffs.slice(0, 15)) console.log(`  - ${d.page} ${d.el.split('>').slice(-3).join('>')} ${d.prop}: ${d.before} -> ${d.after}`)
} else if (summary.styles) console.log(summary.styles.note)
console.log(`report: ${join(outDir, 'theme-diff.json')}  (${Math.round((Date.now() - t0) / 1000)}s)`)
console.log(summary.failures.length ? `\nFAIL (${summary.failures.length}):\n- ` + summary.failures.join('\n- ') : '\nPASS: dark is identical to the baseline outside the masks')
process.exit(summary.failures.length ? 1 : 0)
