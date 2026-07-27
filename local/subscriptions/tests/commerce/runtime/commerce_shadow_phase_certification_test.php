<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\audit\CommerceShadowPhaseAuditor;

final class commerce_shadow_phase_certification_test extends \advanced_testcase {
    public function test_shadow_phase_is_structurally_certified(): void {
        $pluginroot = dirname(__DIR__, 3);
        $result = (new CommerceShadowPhaseAuditor($pluginroot))->audit();

        $this->assertTrue($result['certified'], implode(PHP_EOL, $result['errors']));
        $this->assertNotContains(false, $result['checks'], 'At least one Shadow certification check failed.');
    }
}
