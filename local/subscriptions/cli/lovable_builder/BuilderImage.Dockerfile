# One-time reviewed offline npm cache for a rootless, network-disabled runtime.
# Image construction may fetch npm packages. Runtime compilation never has network access.
FROM docker.io/library/node:22-bookworm-slim
WORKDIR /opt/campusfr-build-cache
COPY builder-articles-deps.json ./package.json
RUN npm install --ignore-scripts --no-audit --no-fund --no-package-lock \
       --cache=/opt/npm-cache && \
    rm -rf /opt/campusfr-build-cache/node_modules && \
    chmod -R a+rX /opt/npm-cache
ENV NODE_ENV=development
