<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_certification_j731_test
        extends \advanced_testcase {

    public function test_certification_ignores_obsolete_default_template(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'certification/'
            . 'CommerceStorefrontSeoResponsiveCertificationService.php'
        );

        foreach ([
            "'standard'",
            "'editorial'",
            "'immersive'",
            "'course'",
            "'digital'",
            "'bundle'",
        ] as $layout) {
            $this->assertStringContainsString($layout, $source);
        }

        $this->assertStringNotContainsString(
            "glob(",
            $source
        );
    }

    public function test_current_certification_is_green(): void {
        $service = new \local_subscriptions\commerce\storefront\certification\CommerceStorefrontSeoResponsiveCertificationService();

        $findings = $service->certify();

        foreach ($findings as $finding) {
            $this->assertSame(
                'ok',
                $finding['status'],
                $finding['label'] . ': ' . $finding['detail']
            );
        }
    }
}
