#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../.." && pwd)"
cd "$ROOT"

PHPUNIT="${PHPUNIT:-vendor/bin/phpunit}"
SUITE="local_subscriptions_testsuite"

tests=(
  "local/subscriptions/tests/commerce/catalog/commerce_product_discovery_routing_l2_l3_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_builder_first_l4_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_builder_cta_l43_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_currency_selector_l43b_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_editorial_builder_l44a_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_editorial_builder_l44b_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_features_editor_l44c_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_features_section_save_l44d_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_features_reload_l44e_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_features_public_render_l44f_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_media_fit_l45_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_content_alignment_l46_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_locale_translation_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_package_j87_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_localised_quickfacts_l62a_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_course_light_layout_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_bundle_component_localisation_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_bundle_light_layout_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_owned_bundle_navigation_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_customer_state_matrix_l73_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_return_navigation_l73b_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_795f6c_polish_and_fixes_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_navigation_consistency_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_navigation_certification_l75_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_navigation_ux_l75_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_delta_l_runtime_contract_test.php"
  "local/subscriptions/tests/commerce/storefront/commerce_storefront_delta_l_certification_test.php"
)

echo "======================================================================"
echo "CampusFR Commerce — Delta L PHPUnit certification"
echo "======================================================================"

for testfile in "${tests[@]}"; do
  echo
  echo ">>> $testfile"
  "$PHPUNIT" --testsuite "$SUITE" "$testfile"
done

echo
echo ">>> Read-only Delta L CLI certification"
php local/subscriptions/cli/commerce/storefront/certify_delta_l.php

echo
echo "======================================================================"
echo "DELTA L CERTIFICATION: GREEN"
echo "======================================================================"
