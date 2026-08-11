<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../classes/commerce/tracking/CommerceTrackedActionUrl.php');

use local_subscriptions\commerce\tracking\CommerceTrackedActionUrl;

final class commerce_tracked_action_url_test extends \advanced_testcase {
    public function test_signed_local_action_url_is_valid_and_external_destination_is_rejected(): void {
        $this->resetAfterTest();
        $url = CommerceTrackedActionUrl::build(
            'cmp_test',
            'postpayment_view_order',
            'order_result',
            new \moodle_url('/local/subscriptions/order_details.php', ['reference' => 'cmp_test'])
        );
        $params = $url->params();
        CommerceTrackedActionUrl::validate(
            'cmp_test',
            'postpayment_view_order',
            'order_result',
            $params['destination'],
            $params['signature']
        );
        $this->expectException(\moodle_exception::class);
        CommerceTrackedActionUrl::build('cmp_test', 'postpayment_view_order', 'order_result', 'https://example.org/evil');
    }
}
