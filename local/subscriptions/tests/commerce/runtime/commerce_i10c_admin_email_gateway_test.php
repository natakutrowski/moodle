<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\read\admin\CommerceAdminReadGateway;
use local_subscriptions\commerce\read\email\CommerceEmailReadGateway;

final class commerce_i10c_admin_email_gateway_test extends advanced_testcase {
    public function test_gateways_are_explicit_read_side_dependencies(): void {
        $this->assertInstanceOf(CommerceAdminReadGateway::class, new CommerceAdminReadGateway());
        $this->assertInstanceOf(CommerceEmailReadGateway::class, new CommerceEmailReadGateway());
    }
}
