#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../.." && pwd)"
cd "$ROOT"

PHPUNIT="${PHPUNIT:-vendor/bin/phpunit}"
SUITE="local_subscriptions_testsuite"

tests=(
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_bulk_reconciliation_m42a_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_search_ui_m42a_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_similarity_m42b_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_similarity_ui_m42b_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_merge_planner_m42c_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_merge_ui_m42c_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_merge_execution_m42d_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_merge_execution_ui_m42d_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_similarity_fullname_m42e_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_legacy_digital_provisioning_m42e_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_legacy_digital_bulk_provisioning_m42e_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_legacy_digital_provisioning_ui_m42e_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_operations_safety_m42f_test.php"
  "local/subscriptions/tests/commerce/customer/commerce_customer_identity_operations_certification_m42f_test.php"
)

echo "======================================================================"
echo "CampusFR Commerce — Identity Operations M4.2 PHPUnit certification"
echo "======================================================================"

for testfile in "${tests[@]}"; do
  echo
  echo ">>> $testfile"
  "$PHPUNIT" --testsuite "$SUITE" "$testfile"
done

echo
echo ">>> Read-only Identity Operations certification CLI"
php local/subscriptions/cli/commerce/customer/certify_identity_operations.php

echo
echo "======================================================================"
echo "IDENTITY OPERATIONS M4.2 CERTIFICATION: GREEN"
echo "======================================================================"
