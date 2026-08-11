<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\professional\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\compatibility\CommerceLegacyUrlCompatibilityService;

/** Read-only certification of the professional customer experience layer. */
final class CommerceCustomerExperienceCertificationService {
    public function certify(): array {
        global $CFG;

        $checks = [];
        $required = [
            'classes/commerce/support/CommerceSupportRequestService.php',
            'classes/commerce/tracking/CommerceTrackedActionUrl.php',
            'classes/event/commerce_customer_action_clicked.php',
            'classes/commerce/compatibility/CommerceLegacyUrlCompatibilityService.php',
            'order_details.php',
            'order_result.php',
            'order_invoice.php',
            'support_request.php',
            'commerce_action.php',
            'styles/order_print.css',
            'styles/order_details.css',
            'styles/order_result.css',
        ];
        $missing = [];
        foreach ($required as $relative) {
            if (!is_file($CFG->dirroot . '/local/subscriptions/' . $relative)) {
                $missing[] = $relative;
            }
        }
        $checks[] = $this->finding('Required customer experience components', $missing === [],
            $missing === [] ? count($required) . ' required components available.' : 'Missing: ' . implode(', ', $missing));

        $orderresult = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php') ?: '';
        $checks[] = $this->finding('Public order reference',
            str_contains($orderresult, 'CommercePublicOrderReference') && !str_contains($orderresult, "tag('h2', s(\$order->reference)"),
            'Order Result uses the public CFR reference for customer-visible output.');

        $checks[] = $this->finding('Tracked customer actions',
            str_contains($orderresult, 'CommerceTrackedActionUrl::build')
                && is_file($CFG->dirroot . '/local/subscriptions/commerce_action.php'),
            'Post-purchase CTA tracking endpoint and signed URLs are available.');

        $support = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/support/CommerceSupportRequestService.php') ?: '';
        $checks[] = $this->finding('CRM support integration',
            str_contains($support, 'InboxMessageData') && str_contains($support, 'provideruid'),
            'Support requests are persisted into CRM Inbox.');

        $details = file_get_contents($CFG->dirroot . '/local/subscriptions/order_details.php') ?: '';
        $checks[] = $this->finding('Printable order and invoice',
            str_contains($details, 'order_print.css') && is_file($CFG->dirroot . '/local/subscriptions/order_invoice.php'),
            'Printable Order Details and shared invoice download are available.');

        $compatibility = new CommerceLegacyUrlCompatibilityService($GLOBALS['DB']);
        $policy = $compatibility::route_policy();
        $compatibilityok = count($policy) === 8;
        foreach (array_keys($policy) as $route) {
            $compatibilityok = $compatibilityok
                && is_file($CFG->dirroot . '/local/subscriptions/' . $route);
        }
        $checks[] = $this->finding('Legacy URL compatibility', $compatibilityok,
            count($policy) . ' historical routes classified and retained or redirected.');

        $errors = count(array_filter($checks, static fn(array $check): bool => !$check['ok']));
        return [
            'checks' => $checks,
            'errors' => $errors,
            'status' => $errors === 0 ? 'CERTIFIED' : 'NOT_CERTIFIED',
        ];
    }

    private function finding(string $label, bool $ok, string $detail): array {
        return ['label' => $label, 'ok' => $ok, 'detail' => $detail];
    }
}
