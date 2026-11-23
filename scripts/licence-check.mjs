// Licence check (ADR 0010). It reads the lockfiles, so it needs no installed packages.
//   backend/composer.lock     a list of licences means "any one of them" (Composer rule)
//   frontend/package-lock.json  an SPDX expression, for example "(MIT OR CC0-1.0)"
// Runtime packages must use a licence from ALLOWED.
// Dev packages (tools that are not in the shipped app) can also use a licence from DEV_ONLY.
// Usage (from frontend/, in the web container): node ../scripts/licence-check.mjs
import { readFileSync } from 'node:fs'

const ALLOWED = new Set([
  'MIT',
  'ISC',
  '0BSD',
  'BSD-2-Clause',
  'BSD-3-Clause',
  'Apache-2.0',
  'BlueOak-1.0.0',
  'CC0-1.0',
  'Unlicense',
])
// Weak or non-code licences. They are safe for build and test tools that we do not ship or change.
const DEV_ONLY = new Set(['MPL-2.0', 'PSF-2.0', 'CC-BY-4.0', 'Python-2.0'])
// Single dev packages with a licence that is not on a list. Each entry needs a reason.
const DEV_EXCEPTIONS = new Map([
  ['sentry', 'FSL-1.1-Apache-2.0. A Lighthouse dependency. It becomes Apache-2.0 after 2 years.'],
])

const root = new URL('../', import.meta.url)
const readJson = (path) => JSON.parse(readFileSync(new URL(path, root), 'utf8'))

// true when the SPDX expression is allowed. "A OR B": one must be allowed. "A AND B": both.
function isAllowed(expr, dev) {
  const ok = (id) => ALLOWED.has(id) || (dev && DEV_ONLY.has(id))
  const clean = String(expr).replace(/[()]/g, ' ').trim()
  return clean
    .split(/\s+OR\s+/)
    .some((part) => part.split(/\s+AND\s+/).every((id) => ok(id.trim())))
}

const problems = []
let count = 0

const lock = readJson('frontend/package-lock.json')
for (const [path, pkg] of Object.entries(lock.packages ?? {})) {
  if (path === '' || pkg.link) continue
  count++
  const name = path.replace(/^.*node_modules\//, '')
  const dev = Boolean(pkg.dev || pkg.devOptional)
  if (dev && DEV_EXCEPTIONS.has(name)) continue
  if (!isAllowed(pkg.license ?? 'NONE', dev)) {
    problems.push(`npm ${name} (${dev ? 'dev' : 'runtime'}): ${pkg.license ?? 'no licence'}`)
  }
}

const composer = readJson('backend/composer.lock')
for (const [key, dev] of [
  ['packages', false],
  ['packages-dev', true],
]) {
  for (const pkg of composer[key] ?? []) {
    count++
    const licences = pkg.license ?? []
    if (!licences.some((id) => isAllowed(id, dev))) {
      const list = licences.join(' OR ') || 'no licence'
      problems.push(`composer ${pkg.name} (${dev ? 'dev' : 'runtime'}): ${list}`)
    }
  }
}

if (problems.length > 0) {
  console.log('ERROR: these packages have a licence that is not on the allow-list (ADR 0010):')
  for (const line of problems) console.log(`  ${line}`)
  process.exit(1)
}
console.log(`licence-check: ${count} packages, all licences are allowed.`)
