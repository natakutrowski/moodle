<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\purchase\compatibility\CommerceLegacyPurchaseRedirector;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

final class commerce_legacy_purchase_redirector_test extends advanced_testcase {
    public function test_list_url_preserves_legacy_family_as_filter(): void {
        global $DB;
        $redirector = new CommerceLegacyPurchaseRedirector(new CommercePurchaseReadRepository($DB));
        self::assertStringContainsString('type=digital', $redirector->list_url('digital')->out(false));
    }
}
