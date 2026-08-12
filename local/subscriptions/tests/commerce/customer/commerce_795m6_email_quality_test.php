<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer;

use advanced_testcase;
use local_subscriptions\commerce\customer\quality\CommerceEmailQualityService;

final class commerce_795m6_email_quality_test extends advanced_testcase {
    public function test_common_gmail_typos_are_suggested(): void {
        $service = new CommerceEmailQualityService();
        foreach (['nata@gmai.com', 'nata@gmal.com', 'nata@gmial.com', 'nata@gmail.con'] as $email) {
            $result = $service->diagnose($email);
            $this->assertSame(CommerceEmailQualityService::STATUS_SUSPECT, $result->status);
            $this->assertSame('nata@gmail.com', $result->suggestion);
        }
    }

    public function test_unknown_custom_domain_is_not_flagged(): void {
        $result = (new CommerceEmailQualityService())->diagnose('client@campusfr.example');
        $this->assertSame(CommerceEmailQualityService::STATUS_OK, $result->status);
        $this->assertNull($result->suggestion);
    }

    public function test_invalid_syntax_is_flagged_without_guess(): void {
        $result = (new CommerceEmailQualityService())->diagnose('client@@gmail.com');
        $this->assertSame(CommerceEmailQualityService::STATUS_INVALID, $result->status);
        $this->assertNull($result->suggestion);
    }
}
