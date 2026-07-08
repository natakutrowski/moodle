<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationCronRunner {

    public function run(): void {
        $now = time();

        mtrace('[local_subscriptions] CRM automation runner started.');

        $this->run_scanner(
            'Expired trial automations processed',
            fn(): int => (new TrialExpiredScanner())->run($now)
        );

        $this->run_scanner(
            'Failed payment automations processed',
            fn(): int => (new PaymentFailedScanner())->run()
        );

        $this->run_scanner(
            'Paid digital purchase automations processed',
            fn(): int => (new DigitalPurchasePaidScanner())->run()
        );

        $this->run_scanner(
            'Expired subscription automations processed',
            fn(): int => (new SubscriptionExpiredScanner())->run($now)
        );

        mtrace('[local_subscriptions] CRM automation runner finished.');
    }

    private function run_scanner(string $label, callable $scanner): int {
        try {
            $count = (int)$scanner();
            mtrace('[local_subscriptions] ' . $label . ': ' . $count);

            return $count;
        } catch (\Throwable $e) {
            mtrace('[local_subscriptions] ' . $label . ' failed: ' . $e->getMessage());

            return 0;
        }
    }
}