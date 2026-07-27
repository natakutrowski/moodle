<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\certification\CommerceCertificationMatrix;
use local_subscriptions\validation\ValidationResult;

final class commerce_release_certification_test extends \advanced_testcase {

    public function test_matrix_contains_all_migrated_scenarios(): void {
        $keys = array_map(
            static fn($scenario): string => $scenario->get_key(),
            (new CommerceCertificationMatrix())->scenarios()
        );

        $this->assertSame([
            'digital_stripe_eur',
            'subscription_stripe_eur',
            'subscription_stripe_eur_recurring',
            'upgrade_stripe_eur',
            'retry_stripe_eur',
            'digital_alfa_rub',
            'subscription_alfa_rub',
            'retry_alfa_rub',
        ], $keys);
    }

    public function test_release_status_is_derived_from_severity(): void {
        $result = new ValidationResult();
        $this->assertSame('READY', $result->release_status());

        $result->warning('warning', 'warning');
        $this->assertSame('READY_WITH_WARNINGS', $result->release_status());

        $result->error('error', 'error');
        $this->assertSame('BLOCKED', $result->release_status());
    }
}
