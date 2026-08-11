#!/usr/bin/env bash
set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
REPORT_DIR="${ROOT_DIR}/local/subscriptions/certification"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
REPORT_FILE="${REPORT_DIR}/J6-certification-${TIMESTAMP}.log"

mkdir -p "${REPORT_DIR}"
cd "${ROOT_DIR}"

{
    echo "========================================================="
    echo "CampusFR Commerce 7.95 - Certification J6"
    echo "Date : $(date)"
    echo "Commit : $(git rev-parse --short HEAD 2>/dev/null || echo UNKNOWN)"
    echo "========================================================="
    echo

    echo "================ local_subscriptions ================"
    vendor/bin/phpunit --testsuite local_subscriptions_testsuite
    SUB_EXIT=$?

    echo
    echo "================ local_campus ========================"
    vendor/bin/phpunit --testsuite local_campus_testsuite
    CAMPUS_EXIT=$?

    echo
    echo "========================================================="
    echo "Résumé"
    echo "local_subscriptions : ${SUB_EXIT}"
    echo "local_campus        : ${CAMPUS_EXIT}"

    if [ "${SUB_EXIT}" -eq 0 ] && [ "${CAMPUS_EXIT}" -eq 0 ]; then
        echo
        echo "CERTIFICATION : SUCCESS"
        FINAL_EXIT=0
    else
        echo
        echo "CERTIFICATION : FAILED"
        FINAL_EXIT=1
    fi

    echo "Rapport : ${REPORT_FILE}"
    exit "${FINAL_EXIT}"
} 2>&1 | tee "${REPORT_FILE}"

exit "${PIPESTATUS[0]}"
