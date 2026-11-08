#!/usr/bin/env bash
# Trusted entrypoint inside a network-isolated, non-privileged Podman container.
set -euo pipefail
umask 077
cd /work/source
export HOME=/tmp
# Dependencies are frozen inside the reviewed :2 image.  Do not run npm on
# the untrusted Lovable project: even --offline may resolve newer transitive
# versions whose tarballs were never cached (ENOTCACHED).
printf 'CMD: node --version; npm --version\n'
node --version; npm --version
printf 'CMD: use approved node_modules snapshot from immutable worker image\n'
if [[ -e node_modules || -L node_modules ]]; then
  echo 'STOP: Le projet préparé ne doit pas fournir node_modules.' >&2
  exit 2
fi
if [[ ! -d /opt/campusfr-build-cache/node_modules/vite || \
      ! -d /opt/campusfr-build-cache/node_modules/react ]]; then
  echo 'STOP: Image du worker sans dépendances approuvées.' >&2
  exit 2
fi
# Vite may write temporary config files under node_modules/.vite-temp.  Copy the
# approved snapshot to the private, throwaway /work filesystem rather than
# symlinking to the read-only image, to preserve normal Vite behavior.
printf 'CMD: cp -R /opt/campusfr-build-cache/node_modules/. /work/source/node_modules/\n'
mkdir -p node_modules
cp -R /opt/campusfr-build-cache/node_modules/. node_modules/
printf 'OK: dépendances approuvées, aucun npm install, aucun accès réseau\n'
printf 'CMD: node scripts/campusfr-texts.cjs\n'
node scripts/campusfr-texts.cjs
printf 'CMD: node node_modules/vite/bin/vite.js build --config vite.bridge.config.ts\n'
export CAMPUSFR_BRIDGE_PREVIEW=1
node node_modules/vite/bin/vite.js build --config vite.bridge.config.ts
printf 'CMD: node scripts/campusfr-finalize.mjs\n'
node scripts/campusfr-finalize.mjs
printf 'CMD: cp -a ../draft/<design>/. /result/\n'
shopt -s nullglob
outputs=(../draft/*)
if (( ${#outputs[@]} != 1 )); then echo 'STOP: sortie brouillon non unique' >&2; exit 2; fi
cp -a "${outputs[0]}/." /result/
echo 'OK: compilation de brouillon terminée, sans publication'
