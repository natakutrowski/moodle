<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_grant_transactional_mail_k14g_test extends advanced_testcase {

    public function test_grant_access_is_a_first_class_transactional_mail_type(): void {
        $root = dirname(__DIR__, 3);

        $types = (string)file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailType.php'
        );
        $runtime = (string)file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailRuntime.php'
        );
        $defaults = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );

        $this->assertStringContainsString("GRANT_ACCESS = 'grant_access'", $types);
        $this->assertStringContainsString('CommerceGrantAccessTemplate', $runtime);
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($defaults, 'CommerceMailType::GRANT_ACCESS')
        );
    }

    public function test_grant_access_context_has_no_fake_purchase_dependency(): void {
        $root = dirname(__DIR__, 3);
        $factory = (string)file_get_contents(
            $root . '/classes/commerce/mail/context/CommerceGrantAccessMailContextFactory.php'
        );
        $service = (string)file_get_contents(
            $root . '/classes/commerce/mail/service/CommerceGrantAccessMailService.php'
        );

        $this->assertStringContainsString('CommerceEntitlementGrantPlan', $factory);
        $this->assertStringContainsString('digital_native_download.php', $factory);
        $this->assertStringContainsString('/course/view.php', $factory);
        $this->assertStringNotContainsString('CommercePurchaseMailContextFactory', $factory);
        $this->assertStringNotContainsString('purchaseid', $factory);

        $this->assertStringContainsString('for_grant_source', $service);
        $this->assertStringContainsString('CommerceMailType::GRANT_ACCESS', $service);
    }

    public function test_manual_grant_can_immediately_queue_access_email(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents($root . '/admin/subscriptions/add.php');
        $renderer = (string)file_get_contents($root . '/renderer/user_subs_renderer.php');

        $this->assertStringContainsString('CommerceGrantAccessMailService', $page);
        $this->assertStringContainsString("'send_access_email'", $page);
        $this->assertStringContainsString("'send_access_email'", $renderer);
        $this->assertStringContainsString('true', $page);
    }

    public function test_bulk_grant_queues_mail_without_immediate_transport(): void {
        $root = dirname(__DIR__, 3);
        $campaign = (string)file_get_contents(
            $root . '/classes/commerce/grant/CommerceBulkGrantCampaignService.php'
        );
        $bulk = (string)file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );

        $this->assertStringContainsString('CommerceGrantAccessMailService', $campaign);
        $this->assertStringContainsString('!empty($campaign->sendemail)', $campaign);
        $this->assertStringContainsString("'send_access_email'", $bulk);

        // Explicit false means queue-only: shared mail cron/throttling owns transport.
        $this->assertStringContainsString(
            "\$result['plan'],\n                    false",
            $campaign
        );
    }

    public function test_grant_access_mail_is_one_per_root_source(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/mail/service/CommerceGrantAccessMailService.php'
        );
        $idempotency = (string)file_get_contents(
            $root . '/classes/commerce/mail/CommerceMailIdempotencyKey.php'
        );

        $this->assertStringContainsString(
            '$plan->get_purchase_reference()',
            $service
        );
        $this->assertStringContainsString(
            'grant-source:',
            $idempotency
        );
    }
    public function test_direct_native_download_endpoint_supports_email_variants(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents($root . '/digital_native_download.php');

        $this->assertStringContainsString("optional_param('version', 'desktop'", $source);
        $this->assertStringContainsString("['desktop', 'mobile']", $source);
        $this->assertStringContainsString('$resolver->resolve($token, time(), $version)', $source);
    }


}
