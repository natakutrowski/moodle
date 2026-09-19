<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\hub\CommerceCustomerHubService;

final class commerce_797m634_learning_datetime_surfaces_test extends advanced_testcase {
    public function test_mon_campus_learning_metadata_uses_date_and_time(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/customer/hub/CommerceCustomerHubService.php'
        );

        self::assertIsString($source);
        self::assertGreaterThanOrEqual(
            2,
            substr_count($source, "get_string('strftimedatetimeshort', 'langconfig')")
        );
    }
}
