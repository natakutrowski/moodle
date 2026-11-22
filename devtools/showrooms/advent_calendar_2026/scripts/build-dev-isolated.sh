#!/usr/bin/env bash
# Compile le showroom Avent dans un dossier temporaire, sans modifier Moodle.
set -Eeuo pipefail
SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK="$(mktemp -d /tmp/campusfr-advent-build.XXXXXXXX)"
echo "SOURCE=$SOURCE"
echo "DOSSIER_BUILD=$WORK"
command -v node >/dev/null || { echo "ERREUR : Node.js est absent" >&2; exit 1; }
command -v npm >/dev/null || { echo "ERREUR : npm est absent" >&2; exit 1; }
echo "Node: $(node --version); npm: $(npm --version)"
cp -a "$SOURCE/." "$WORK/"
# Le workspace temporaire appartient à un utilisateur non privilégié si lancé par root.
if [ "$(id -u)" -eq 0 ]; then
    BUILD_USER=nobody
    BUILD_GROUP="$(id -gn "$BUILD_USER")"
    chown -R "$BUILD_USER:$BUILD_GROUP" "$WORK"
    run_npm() {
        runuser -u "$BUILD_USER" -- env HOME="$WORK" npm_config_cache="$WORK/.npm-cache" npm --prefix "$WORK" "$@"
    }
else
    run_npm() {
        HOME="$WORK" npm_config_cache="$WORK/.npm-cache" npm --prefix "$WORK" "$@"
    }
fi
printf '\n=== Installation des dépendances dans le dossier temporaire ===\n'
run_npm install --no-audit --no-fund
printf '\n=== Vérification TypeScript ===\n'
run_npm run typecheck
printf '\n=== Compilation Design Bridge ===\n'
run_npm run build:bridge
printf '\n=== Contrôle des artefacts ===\n'
test -s "$WORK/dist/main.js"
test -s "$WORK/dist/style.css"
grep -q CAMPUSFR_ADVENT_BRIDGE_VISUAL_V4 "$WORK/dist/style.css" || { echo "ERREUR : CSS de finition absent" >&2; exit 1; }
test -f "$WORK/manifest.json"
test -d "$WORK/content"
ls -lh "$WORK/dist/main.js" "$WORK/dist/style.css"
echo "OK: compilation terminée, aucun fichier Moodle modifié."
echo "DOSSIER_BUILD=$WORK"
