<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showrooms_n93_test extends \advanced_testcase {
    public function test_n93_splits_showroom_editor_into_business_pages(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/';
        $edit = file_get_contents($root . 'edit.php');
        $seo = file_get_contents($root . 'seo.php');
        $builder = file_get_contents($root . 'builder.php');
        $history = file_get_contents($root . 'history.php');

        $this->assertStringContainsString("\$showroomsection = \$showroomsection ?? 'information'", $edit);
        $this->assertStringContainsString("\$showroomsection = 'seo'", $seo);
        $this->assertStringContainsString("\$showroomsection = 'builder'", $builder);
        $this->assertStringContainsString("\$showroomsection === 'seo'", $edit);
        $this->assertStringContainsString("\$showroomsection === 'information'", $edit);
        $this->assertStringContainsString("\$showroomsection === 'builder'", $edit);
        $this->assertStringContainsString('commerce-showroom-subnav', $edit);
        $this->assertStringContainsString('commerce-showroom-subnav', $history);
    }
}
