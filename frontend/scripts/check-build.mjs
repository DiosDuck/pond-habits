// Checks the production build in dist/ (ADR 0009). Run it after "npm run build".
// 1. The initial JS (the scripts that index.html loads) is 200 KB gzip or less.
// 2. The PWA can be installed: the manifest has the required fields, and the service worker exists.
import { existsSync, readFileSync } from 'node:fs'
import { join } from 'node:path'
import { gzipSync } from 'node:zlib'

const dist = new URL('../dist/', import.meta.url).pathname
const budgetKb = 200
const errors = []

const html = readFileSync(join(dist, 'index.html'), 'utf8')
const scripts = [...html.matchAll(/<script[^>]+src="\/([^"]+\.js)"/g)].map(
  (m) => m[1],
)
const preloads = [
  ...html.matchAll(/<link[^>]+rel="modulepreload"[^>]+href="\/([^"]+\.js)"/g),
].map((m) => m[1])
const initial = [...new Set([...scripts, ...preloads])]
if (initial.length === 0) errors.push('index.html loads no script.')

let totalBytes = 0
for (const file of initial) {
  const size = gzipSync(readFileSync(join(dist, file))).length
  totalBytes += size
  console.log(`  ${file}: ${(size / 1024).toFixed(1)} KB gzip`)
}
const totalKb = totalBytes / 1024
console.log(`Initial JS: ${totalKb.toFixed(1)} KB gzip (budget ${budgetKb} KB)`)
if (totalKb > budgetKb)
  errors.push(
    `Initial JS is ${totalKb.toFixed(1)} KB gzip. The budget is ${budgetKb} KB.`,
  )

const manifestPath = join(dist, 'manifest.webmanifest')
if (!existsSync(manifestPath)) {
  errors.push('dist/manifest.webmanifest is missing.')
} else {
  const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'))
  for (const field of ['name', 'short_name', 'start_url', 'display', 'icons']) {
    if (
      !manifest[field] ||
      (Array.isArray(manifest[field]) && manifest[field].length === 0)
    ) {
      errors.push(`The manifest has no "${field}".`)
    }
  }
  if (!['standalone', 'fullscreen', 'minimal-ui'].includes(manifest.display)) {
    errors.push(
      `The manifest "display" is "${manifest.display}". The app cannot be installed with it.`,
    )
  }
}
if (!html.includes('manifest.webmanifest'))
  errors.push('index.html has no link to the manifest.')
if (!existsSync(join(dist, 'sw.js')))
  errors.push('dist/sw.js (the service worker) is missing.')

if (errors.length > 0) {
  for (const e of errors) console.error(`ERROR: ${e}`)
  process.exit(1)
}
console.log('Build check: OK')
