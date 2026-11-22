#!/usr/bin/env bash
# Package V4 after a successful build. Writes in /tmp only; does not install in Moodle.
set -Eeuo pipefail
BUILD="${1:?Usage: bash scripts/pack-dev.sh /tmp/campusfr-advent-build.XXXXXXXX}"
DEV=/var/www/moodle-dev
CLI="$DEV/local/subscriptions/cli/bridge_lovable.py"
[[ -d "$BUILD" && -s "$BUILD/dist/main.js" && -s "$BUILD/dist/style.css" ]] || { echo 'STOP : build incomplet' >&2; exit 1; }
[[ -f "$CLI" ]] || { echo 'STOP : Design Bridge absent sur DEV' >&2; exit 1; }
grep -q CAMPUSFR_ADVENT_BRIDGE_VISUAL_V4 "$BUILD/dist/style.css" || { echo 'STOP : styles de finition V4 absents du build' >&2; exit 1; }
python3 - "$BUILD/manifest.json" <<'PY'
import json,sys
m=json.load(open(sys.argv[1],encoding='utf-8'))
assert m.get('id')=='advent_calendar_2026' and m.get('version')=='0.3.1', 'STOP : manifeste inattendu'
PY
PACKDIR="$(mktemp -d /tmp/campusfr-advent-v4-pack.XXXXXXXX)"
STAGE="$PACKDIR/source"
ZIP="$PACKDIR/CampusFR_Avent_DesignPack_V4.zip"
mkdir -p "$STAGE/content"
cp "$BUILD/manifest.json" "$STAGE/"
for lang in fr en ru; do cp "$BUILD/content/$lang.json" "$STAGE/content/"; done
cp -a "$BUILD/dist" "$STAGE/"
python3 "$CLI" pack "$STAGE" "$ZIP"
unzip -t "$ZIP" | tail -n 1
sha256sum "$ZIP"
echo "DESIGN_PACK=$ZIP"
