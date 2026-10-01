import { chromium } from '/Users/dev/Documents/B2B Projects/rangeaahan/node_modules/playwright/index.mjs'
import lighthouse from './node_modules/lighthouse/core/index.js'
import * as chromeLauncher from './node_modules/chrome-launcher/dist/index.js'
import { mkdirSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'

const baseA = (process.argv[2] || '').replace(/\/$/, '')
const baseB = (process.argv[3] || '').replace(/\/$/, '')
const outDir = process.argv[4] || 'shots/lighthouse-theme'
if (!baseA || !baseB) {
  console.error('Usage: node dev-tools/lighthouse-theme.mjs <reference-base-url> <candidate-base-url> [out-dir]')
  process.exit(2)
}
const runs = Number(process.env.RUNS || 3)
const allowance = Number(process.env.ALLOWANCE || 2)
const pages = process.env.PAGES ? process.env.PAGES.split(',') : ['/', '/shop', '/product/azure-oud']
const labelA = process.env.LABEL_A || 'reference'
const labelB = process.env.LABEL_B || 'candidate'
mkdirSync(outDir, { recursive: true })

const chrome = await chromeLauncher.launch({ chromePath: chromium.executablePath(), chromeFlags: ['--headless=new', '--no-first-run', '--disable-extensions'] })
const median = (xs) => { const s = xs.slice().sort((a, b) => a - b); return s.length % 2 ? s[(s.length - 1) / 2] : (s[s.length / 2 - 1] + s[s.length / 2]) / 2 }
const results = {}
const failures = []

async function audit(url) {
  const res = await lighthouse(url, { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance'], formFactor: 'mobile', screenEmulation: { mobile: true, width: 412, height: 823, deviceScaleFactor: 1.75, disabled: false }, throttlingMethod: 'simulate' })
  const lhr = res.lhr
  const theme = await fetch(url).then((r) => r.text()).then((h) => (h.match(/<html[^>]*data-theme="([a-z]+)"/) || [])[1] || null).catch(() => null)
  return {
    score: Math.round((lhr.categories.performance.score || 0) * 100),
    lcp: Math.round(lhr.audits['largest-contentful-paint'].numericValue),
    fcp: Math.round(lhr.audits['first-contentful-paint'].numericValue),
    tbt: Math.round(lhr.audits['total-blocking-time'].numericValue),
    cls: +lhr.audits['cumulative-layout-shift'].numericValue.toFixed(3),
    theme,
    error: lhr.runtimeError ? lhr.runtimeError.code : '',
  }
}

try {
  for (const p of pages) {
    results[p] = { [labelA]: [], [labelB]: [] }
    for (let i = 0; i < runs; i++) {
      for (const [label, base] of [[labelA, baseA], [labelB, baseB]]) {
        const r = await audit(base + p)
        results[p][label].push(r)
        console.log(`${p.padEnd(22)} ${label.padEnd(10)} run ${i + 1}: perf ${r.score} lcp ${r.lcp}ms tbt ${r.tbt}ms cls ${r.cls} (data-theme ${r.theme})${r.error ? ' ERROR ' + r.error : ''}`)
        if (r.error) failures.push(`${p} ${label} run ${i + 1}: ${r.error}`)
      }
    }
  }
} finally {
  await chrome.kill()
}

const summary = { reference: { label: labelA, base: baseA }, candidate: { label: labelB, base: baseB }, runs, allowance, pages: {} }
console.log('\n' + 'page'.padEnd(22) + `${labelA} median`.padEnd(20) + `${labelB} median`.padEnd(20) + 'delta')
for (const [p, byLabel] of Object.entries(results)) {
  const m = (label, key) => median(byLabel[label].map((r) => r[key]))
  const row = {}
  for (const label of [labelA, labelB]) row[label] = { score: m(label, 'score'), lcp: m(label, 'lcp'), fcp: m(label, 'fcp'), tbt: m(label, 'tbt'), cls: m(label, 'cls'), themes: [...new Set(byLabel[label].map((r) => r.theme))] }
  row.delta = row[labelB].score - row[labelA].score
  summary.pages[p] = row
  console.log(p.padEnd(22) + `${row[labelA].score} (lcp ${row[labelA].lcp})`.padEnd(20) + `${row[labelB].score} (lcp ${row[labelB].lcp})`.padEnd(20) + (row.delta > 0 ? '+' : '') + row.delta)
  if (row.delta < -allowance) failures.push(`${p}: ${labelB} median ${row[labelB].score} is ${-row.delta} points below ${labelA} median ${row[labelA].score} (allowance ${allowance})`)
}
if (process.env.EXPECT_THEME_A) for (const [p, row] of Object.entries(summary.pages)) if (row[labelA].themes.some((t) => t !== process.env.EXPECT_THEME_A)) failures.push(`${p}: ${labelA} served data-theme ${row[labelA].themes.join('/')}, expected ${process.env.EXPECT_THEME_A}`)
if (process.env.EXPECT_THEME_B) for (const [p, row] of Object.entries(summary.pages)) if (row[labelB].themes.some((t) => t !== process.env.EXPECT_THEME_B)) failures.push(`${p}: ${labelB} served data-theme ${row[labelB].themes.join('/')}, expected ${process.env.EXPECT_THEME_B}`)
summary.failures = failures
summary.raw = results
writeFileSync(join(outDir, 'lighthouse-theme.json'), JSON.stringify(summary, null, 2))
console.log(`report: ${join(outDir, 'lighthouse-theme.json')}`)
console.log(failures.length ? `\nFAIL (${failures.length}):\n- ` + failures.join('\n- ') : `\nPASS: ${labelB} is within ${allowance} points of ${labelA} on every page`)
process.exit(failures.length ? 1 : 0)
