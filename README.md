# Pond Habits

Pond Habits is a personal tracker app for daily focus, tasks, habits and nutrition. The user earns or loses virtual credits and spends them on rewards that the user defines.

Status: no code exists yet.

## Documentation

Start at [docs/README.md](docs/README.md).

## Development

The dev setup needs only `docker` (with compose), `git` and `make`. All other tools run in containers.

```sh
make up      # build and start the local stack
make help    # show all commands
```

| URL | What |
|---|---|
| http://localhost:8080 | The app (API and frontend on one origin) |
| http://localhost:8025 | Mailpit: all mail that the app sends locally |

VS Code: open the folder and choose "Reopen in Container".

## Licence

MIT. See [LICENSE](LICENSE).
