# Public repository (ADR 0023)

The repository is public. Do not write workstation or personal details into tracked files:

- Personal data: names, emails, home paths, locations. The owner's GitHub username is allowed.
- Workstation details: OS and version, CPU arch, installed tools, other projects, disk sizes.
- Credentials: real passwords, keys, tokens. Name only the place where a secret is kept.
- Live infrastructure values: server address, SSH user, host names, exact job times, rate limits, free tier limits.
- Costs and prices.

Private notes go in `local/`. Git ignores it. Do not link to `local/` from a tracked file.
