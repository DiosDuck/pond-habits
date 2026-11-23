# CI bot: the project's own GitHub App

Renovate and release-please open PRs. A PR from the built-in `GITHUB_TOKEN` starts no workflows,
so it gets no CI run and cannot merge. These two workflows use a token from the project's own
GitHub App instead. The owner owns the app. No third-party app gets access (ADR 0010).

Each workflow run gets a new token from the app. The token is valid for 1 hour.
Each workflow asks only for the permissions that it needs.

## What uses it

| Workflow | Permissions it asks for |
|---|---|
| `renovate.yml` | Contents, Pull requests, Issues (Dependency Dashboard), Workflows (action pins) |
| `release.yml` | Contents (tags, releases), Pull requests, Issues (labels) |

## Setup (one time, in the GitHub web UI)

1. Open your profile Settings → Developer settings → GitHub Apps → New GitHub App.
2. Name: a unique name, for example `pond-habits-bot`. Homepage URL: the repository URL.
3. Webhook: clear the "Active" box. The app does not receive events.
4. Repository permissions, "Read and write": Contents, Issues, Pull requests, Workflows.
   Metadata is "Read-only" automatically. Leave all other permissions at "No access".
5. "Where can this GitHub App be installed?": Only on this account. Click "Create GitHub App".
6. On the app page, copy the Client ID.
7. "Private keys" → "Generate a private key". The browser downloads a `.pem` file.
8. "Install App" → your account → "Only select repositories" → `pond-habits`.
9. In the repository: Settings → Secrets and variables → Actions.
   - Variables tab: `BOT_CLIENT_ID` = the Client ID.
   - Secrets tab: `BOT_PRIVATE_KEY` = the full text of the `.pem` file.
10. Save the `.pem` file in the password manager. Then delete the downloaded file.
11. Test: Actions → Renovate → "Run workflow". The Dependency Dashboard issue appears.

## Change the key (rotation)

Do this once a year, or at once if the key leaks.

1. Generate a new private key on the app page.
2. Replace the secret `BOT_PRIVATE_KEY`. Run the Renovate workflow by hand.
3. Delete the old key on the app page.
