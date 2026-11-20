// Lighthouse CI (ADR 0009): mobile profile, against the production build behind Caddy.
// Run it with "make lighthouse". It runs in the Playwright image, which has Chromium.
// Lighthouse 13 has no PWA category. scripts/check-build.mjs checks the PWA install fields.
module.exports = {
  ci: {
    collect: {
      url: ['http://caddy:81/'],
      numberOfRuns: 1,
      settings: {
        chromeFlags: '--headless=new --no-sandbox',
        // The local site has no HTTPS. Production and staging have it (Caddy), and the
        // staging checks in Phase 4 test it. Skip only these two audits here.
        skipAudits: ['is-on-https', 'redirects-http'],
      },
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.9 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'categories:best-practices': ['error', { minScore: 0.95 }],
      },
    },
    upload: {
      target: 'filesystem',
      outputDir: './reports/lighthouse',
    },
  },
}
