#!/usr/bin/env bash

# CampusFR certification helper.
# Runs AMD builds, purges Moodle caches, then executes the three PHPUnit suites.
# Full command output is written to one timestamped file under /tmp.

set -u

MOODLE_ROOT="${MOODLE_ROOT:-/var/www/moodle-dev}"
TIMESTAMP="$(date '+%Y%m%d-%H%M%S')"
LOG_FILE="${LOG_FILE:-/tmp/campusfr-certification-${TIMESTAMP}.txt}"

cd "${MOODLE_ROOT}" || {
    echo "ERREUR : impossible d'accéder à ${MOODLE_ROOT}" >&2
    exit 1
}

: > "${LOG_FILE}" || {
    echo "ERREUR : impossible de créer ${LOG_FILE}" >&2
    exit 1
}

GLOBAL_STATUS=0

write_header() {
    {
        echo
        echo "============================================================"
        echo "$1"
        echo "Date : $(date '+%Y-%m-%d %H:%M:%S')"
        echo "============================================================"
    } >> "${LOG_FILE}"
}

run_step() {
    local label="$1"
    shift

    echo ">> ${label}..."
    write_header "${label}"

    "$@" >> "${LOG_FILE}" 2>&1
    local status=$?

    if [[ ${status} -eq 0 ]]; then
        echo "   OK"
        echo "STATUT : OK" >> "${LOG_FILE}"
    else
        echo "   ÉCHEC (code ${status})"
        echo "STATUT : ÉCHEC (code ${status})" >> "${LOG_FILE}"
        GLOBAL_STATUS=1
    fi

    return 0
}

{
    echo "CampusFR — certification technique"
    echo "Début : $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Racine Moodle : ${MOODLE_ROOT}"
    echo "Log : ${LOG_FILE}"
} >> "${LOG_FILE}"

echo "CampusFR — certification technique"
echo "Log complet : ${LOG_FILE}"
echo

run_step \
    "Compilation AMD local/subscriptions" \
    npx grunt amd --root=local/subscriptions

run_step \
    "Compilation AMD local/campus" \
    npx grunt amd --root=local/campus

run_step \
    "Purge des caches Moodle" \
    sudo -u www-data php admin/cli/purge_caches.php

run_step \
    "PHPUnit local/campus" \
    vendor/bin/phpunit --testsuite local_campus_testsuite --colors=never

run_step \
    "PHPUnit local/subscriptions" \
    vendor/bin/phpunit --testsuite local_subscriptions_testsuite --colors=never

run_step \
    "PHPUnit theme/edly" \
    vendor/bin/phpunit --testsuite theme_edly_testsuite --colors=never

{
    echo
    echo "============================================================"
    echo "RÉSUMÉ FINAL"
    echo "Fin : $(date '+%Y-%m-%d %H:%M:%S')"
    if [[ ${GLOBAL_STATUS} -eq 0 ]]; then
        echo "STATUT GLOBAL : OK"
    else
        echo "STATUT GLOBAL : ÉCHEC"
    fi
    echo "============================================================"
} >> "${LOG_FILE}"

echo
if [[ ${GLOBAL_STATUS} -eq 0 ]]; then
    echo "✅ Certification terminée sans échec."
else
    echo "❌ Certification terminée avec des échecs."
fi

echo "Résultats complets : ${LOG_FILE}"
exit "${GLOBAL_STATUS}"
