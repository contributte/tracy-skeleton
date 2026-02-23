![](https://heatbadger.now.sh/github/readme/contributte/tracy-skeleton/)

# Contributte Tracy Skeleton

Nette skeleton based on `contributte/nella-skeleton`, focused on secure Tracy BlueScreen sharing for AI tooling.

## Features

- Installed `contributte/tracy` package.
- Minimal AI report action in Tracy BlueScreen.
- Sanitized AI report action with aggressive redaction.
- Local-only mode (default) to avoid outbound data transfer.
- Optional internal AI gateway action with HMAC signature.

## Installation

```bash
composer install
cp config/local.neon.example config/local.neon
make setup
```

## Run

```bash
make dev
```

Open `http://localhost:8000`, then trigger a crash from the homepage and use BlueScreen actions.

## AI Sharing Modes

Configure in `config/local.neon` under `parameters.tracyAi`:

- `localOnly: true` (recommended default): only local report actions are available.
- `localOnly: false` + `gatewayUrl`: enables internal gateway action.
- `gatewaySecret`: optional HMAC signature for gateway payload validation.

## Security Notes

- Minimal report never includes cookies, env dump, request body, or full headers.
- Sanitized report redacts sensitive keys/patterns, normalizes project paths, and truncates long values.
- Review report content before sharing with external AI providers.
