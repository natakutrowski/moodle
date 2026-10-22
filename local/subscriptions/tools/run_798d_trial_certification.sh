#!/usr/bin/env bash

# CampusFR 7.98D — final Legacy Trial certification gate.
# Non-destructive: this script never applies lifecycle mutations.
# It runs targeted PHPUnit coverage, Commerce compatibility smokes, then the strict DEV audit.

set -u

MOODLE_ROOT="${MOODLE_ROOT:-/var/www/moodle-dev}"
TIMESTAMP="$(date '+%Y%m%d-%H%M%S')"
LOG_FILE="${LOG_FILE:-/tmp/campusfr-798d-certification-${TIMESTAMP}.txt}"

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
    echo "CampusFR 7.98D — certification finale Trial Legacy"
    echo "Début : $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Racine Moodle : ${MOODLE_ROOT}"
    echo "Log : ${LOG_FILE}"
    echo "Mode : NON DESTRUCTIF"
} >> "${LOG_FILE}"

echo "CampusFR 7.98D — certification finale Trial Legacy"
echo "Log complet : ${LOG_FILE}"
echo "Mode non destructif : aucune révocation n'est appliquée."
echo

run_step \
    "Purge des caches Moodle" \
    php admin/cli/purge_caches.php

run_step \
    "PHPUnit 7.98D local/subscriptions (D1 à D7)" \
    vendor/bin/phpunit --testsuite local_subscriptions_testsuite --filter 798d --colors=never

run_step \
    "PHPUnit 7.98D local/campus" \
    vendor/bin/phpunit --testsuite local_campus_testsuite --filter 798d --colors=never

run_step \
    "Compatibilité Commerce — Trial conversion bridge" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/trial/commerce_trial_conversion_bridge_test.php --colors=never

run_step \
    "Compatibilité Commerce — Trial native cart pricing" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/trial/commerce_trial_native_cart_discount_test.php --colors=never

run_step \
    "Compatibilité Commerce — Trial conversion completion" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/trial/commerce_trial_conversion_completion_test.php --colors=never

run_step \
    "Compatibilité historique — bandeau Trial / fenêtre de conversion" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/trial/commerce_trial_banner_completion_test.php --colors=never

run_step \
    "Compatibilité historique — navigation Commerce" \
    vendor/bin/phpunit local/subscriptions/tests/crm/commerce/commerce_dashboard_795n2_test.php --colors=never

run_step \
    "Compatibilité Commerce — storefront Trial" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/trial/commerce_trial_storefront_j66b_test.php --colors=never

run_step \
    "Compatibilité Commerce — storefront product pricing" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/trial/commerce_trial_storefront_product_j66c_test.php --colors=never

run_step \
    "Compatibilité Commerce — ownership/cart standard" \
    vendor/bin/phpunit local/subscriptions/tests/commerce/cart/commerce_795g23_cart_operations_test.php --colors=never

run_step \
    "Audit 7.98D strict" \
    php local/subscriptions/cli/commerce/audit/audit_798d_trial_transition.php --strict

{
    echo
    echo "============================================================"
    echo "RÉSUMÉ FINAL 7.98D"
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
    echo "✅ Gate 7.98D terminé sans échec."
else
    echo "❌ Gate 7.98D terminé avec des échecs."
fi

echo "Résultats complets : ${LOG_FILE}"
exit "${GLOBAL_STATUS}"
