<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
final class commerce_customer_identity_search_ui_m42a_test extends advanced_testcase {
 public function test_identity_ui_exposes_advanced_filters_and_bulk_dryrun(): void {
  global $CFG;
  $index=file_get_contents($CFG->dirroot.'/local/subscriptions/admin/commerce/customer-identities/index.php');
  self::assertStringContainsString("'candidateuserid'",$index);
  self::assertStringContainsString("'sku'",$index);
  self::assertStringContainsString("'status'",$index);
  self::assertStringContainsString("customer-identities/bulk.php",$index);
  self::assertStringContainsString("commerce_identity_bulk_preview",$index);
 }
 public function test_showroom_import_has_single_crm_title_source(): void {
  global $CFG;
  $source=file_get_contents($CFG->dirroot.'/local/subscriptions/admin/commerce/showrooms/import.php');
  self::assertSame(1,substr_count($source,'CrmPageHeader::render('));
  self::assertStringNotContainsString('$OUTPUT->heading(', $source);
 }
}
