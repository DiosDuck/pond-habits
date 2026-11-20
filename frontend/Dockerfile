# Node image for the web container (Vite dev server) and the frontend tools.
# The production build comes in Phase 4.
FROM node:26-alpine AS dev

# Run as a user with the same UID and GID as the developer on the host.
# The base image has a "node" user with UID 1000. Remove it, so that any host UID can be used.
ARG UID=1000
ARG GID=1000
RUN deluser --remove-home node \
    && addgroup -g "${GID}" dev \
    && adduser -D -u "${UID}" -G dev dev
USER dev

WORKDIR /workspace/frontend
