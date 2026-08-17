<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1031_test extends advanced_testcase {
    public function test_configuration_field_param_uses_moodle_param_string_type(): void {
        $path = __DIR__ . '/../../../admin/commerce/configuration/section.php';
        $source = file_get_contents($path);
        $this->assertIsString($source);
        $this->assertStringContainsString('string $param = PARAM_RAW_TRIMMED', $source);
        $this->assertStringNotContainsString('int $param = PARAM_RAW_TRIMMED', $source);
        $this->assertStringContainsString('PARAM_INT', $source);
    }
}
