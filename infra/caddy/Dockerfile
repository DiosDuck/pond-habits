# Web image for staging and production: Caddy with the frontend build (ADR 0012).
# Build context: the repo root (see .dockerignore). Multi-arch: amd64 and arm64 (ADR 0017).

# The frontend build. "npm run build" also checks the types.
FROM node:26-alpine AS frontend

WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY frontend/ ./
RUN npm run -s build && npm run -s check:build

FROM caddy:2.11-alpine AS web

COPY infra/caddy/Caddyfile.prod /etc/caddy/Caddyfile
COPY backend/public /var/www/html/public
COPY --from=frontend /app/frontend/dist /srv/frontend
RUN caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
EXPOSE 80
