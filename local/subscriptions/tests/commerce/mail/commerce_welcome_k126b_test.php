<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
final class commerce_welcome_k126b_test extends advanced_testcase {
 public function test_welcome_polish_order_and_full_width_images(): void {
  $r=dirname(__DIR__,3); $w=(string)file_get_contents($r.'/templates/commerce/mail/account_activation.mustache'); $tr=(string)file_get_contents($r.'/classes/commerce/mail/template/CommerceTrialWelcomeTemplate.php');
  $this->assertStringContainsString('mailto:{{accountemail}}',$w);
  $this->assertStringContainsString('max-width:752px',$w);
  $this->assertStringContainsString('max-width:752px',$tr);
  $this->assertLessThan(strpos($w,'welcome_telegram'),strpos($w,'welcome_postactivation'));
 }
}
