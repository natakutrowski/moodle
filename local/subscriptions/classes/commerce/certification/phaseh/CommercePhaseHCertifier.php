<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\phaseh;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\certification\bundle\CommerceBundlePurchaseCertifier;
use local_subscriptions\commerce\certification\course\CommerceCoursePurchaseCertifier;
use local_subscriptions\commerce\certification\digital\CommerceDigitalPurchaseCertifier;
use local_subscriptions\commerce\certification\guest\CommerceGuestCheckoutCertifier;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\recovery\CommerceCheckoutRecoveryService;

/** Aggregates the final architecture and real-transaction checks for phase H. */
final class CommercePhaseHCertifier {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly string $plugindir
    ) {
    }

    /**
     * @param array<string, string> $targets
     */
    public function certify(array $targets = []): CommercePhaseHCertificationReport {
        $sections = [
            $this->certify_native_foundation(),
            $this->certify_checkout_pipeline(),
            $this->certify_guest_checkout($targets['guest'] ?? ''),
            $this->certify_recovery($targets['transaction'] ?? '', $targets['transactionkind'] ?? 'reference'),
            $this->certify_purchase_scenario('course', $targets['course'] ?? ''),
            $this->certify_purchase_scenario('digital', $targets['digital'] ?? ''),
            $this->certify_purchase_scenario('bundle', $targets['bundle'] ?? ''),
        ];

        $certified = true;
        foreach ($sections as $section) {
            foreach ($section['checks'] as $check) {
                if ($check['status'] === 'FAIL') {
                    $certified = false;
                    break 2;
                }
            }
        }

        return new CommercePhaseHCertificationReport($certified, $sections);
    }

    /** @return array<string, mixed> */
    private function certify_native_foundation(): array {
        $checks = [];
        $manager = $this->database->get_manager();
        foreach ([
            CommercePersistenceSchema::TABLE_PURCHASE,
            CommercePersistenceSchema::TABLE_ITEM,
            CommercePersistenceSchema::TABLE_PAYMENT,
            CommercePersistenceSchema::TABLE_FULFILLMENT,
        ] as $table) {
            $this->add($checks, 'table.' . $table, $manager->table_exists(new \xmldb_table($table)),
                'Native Commerce table ' . $table . ' is available.');
        }

        foreach ([
            'classes/commerce/checkout/unified/CommerceCheckoutRuntime.php',
            'classes/commerce/payment/provider/CommercePaymentProviderRegistryFactory.php',
            'classes/commerce/payment/provider/stripe/StripeCommercePaymentProvider.php',
            'classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php',
            'classes/commerce/fulfillment/native/CommerceNativeFulfillmentExecutor.php',
            'classes/commerce/idempotency/CommerceIdempotencyService.php',
        ] as $path) {
            $this->add($checks, 'source.' . basename($path, '.php'), is_file($this->plugindir . '/' . $path),
                $path . ' is present.');
        }

        return ['key' => 'native_foundation', 'label' => 'Native foundation', 'checks' => $checks];
    }

    /** @return array<string, mixed> */
    private function certify_checkout_pipeline(): array {
        $checks = [];
        $this->source_contains($checks, 'checkout.runtime', 'commerce_checkout.php', 'CommerceCheckoutRuntimeFactory');
        $this->source_contains($checks, 'checkout.recovery', 'commerce_checkout.php', 'CommerceGuestCartRecoveryService');
        $this->source_contains($checks, 'checkout.action_recovery', 'commerce_checkout_action.php', 'CommerceGuestCartRecoveryService');
        $this->source_contains($checks, 'payment.router', 'classes/payment/EventRouter.php', 'checkout_completed');
        $this->source_contains($checks, 'recovery.service', 'classes/commerce/recovery/CommerceCheckoutRecoveryService.php', 'checkout_recovery');
        $this->source_contains($checks, 'recovery.cli', 'cli/commerce/recovery/recover_checkout.php', '--execute');

        return ['key' => 'checkout_pipeline', 'label' => 'Checkout and payment pipeline', 'checks' => $checks];
    }

    /** @return array<string, mixed> */
    private function certify_guest_checkout(string $reference): array {
        try {
            $result = (new CommerceGuestCheckoutCertifier($this->database, $this->plugindir))
                ->certify(trim($reference) !== '' ? trim($reference) : null);
            $checks = array_map(static function (array $check): array {
                return [
                    'key' => 'guest.' . $check['key'],
                    'status' => $check['status'] === 'OK' ? 'PASS' : 'FAIL',
                    'message' => $check['message'],
                    'details' => [],
                ];
            }, $result['checks']);
        } catch (\Throwable $exception) {
            $checks = [];
            $this->add($checks, 'guest.session', false, $exception->getMessage());
        }

        if (trim($reference) === '') {
            $checks[] = $this->check('guest.real_session', 'SKIP',
                'No Guest Checkout session reference was supplied; architecture checks only.');
        }

        return ['key' => 'guest_checkout', 'label' => 'Guest Checkout', 'checks' => $checks];
    }

    /** @return array<string, mixed> */
    private function certify_recovery(string $identifier, string $kind): array {
        $checks = [];
        $this->add($checks, 'recovery.class', class_exists(CommerceCheckoutRecoveryService::class),
            'Checkout Recovery service is autoloadable.');

        if (trim($identifier) === '') {
            $checks[] = $this->check('recovery.real_transaction', 'SKIP',
                'No real transaction was supplied for recovery diagnosis.');
            return ['key' => 'recovery', 'label' => 'Recovery and idempotence', 'checks' => $checks];
        }

        try {
            $diagnostic = CommerceCheckoutRecoveryService::create($this->database)
                ->diagnose(trim($identifier), $kind);
            $this->add($checks, 'recovery.transaction_found', $diagnostic->is_found(),
                $diagnostic->is_found() ? 'The Native transaction was found.' : 'The Native transaction was not found.',
                $diagnostic->to_array());
            if ($diagnostic->is_found()) {
                $status = $diagnostic->is_healthy() ? 'PASS' : ($diagnostic->is_repairable() ? 'WARN' : 'FAIL');
                $checks[] = $this->check('recovery.transaction_health', $status,
                    $diagnostic->is_healthy()
                        ? 'The transaction is healthy.'
                        : 'The transaction has recovery issues: ' . implode(', ', $diagnostic->get_issues()) . '.',
                    $diagnostic->to_array());
            }
        } catch (\Throwable $exception) {
            $this->add($checks, 'recovery.diagnostic', false, $exception->getMessage());
        }

        return ['key' => 'recovery', 'label' => 'Recovery and idempotence', 'checks' => $checks];
    }

    /** @return array<string, mixed> */
    private function certify_purchase_scenario(string $type, string $reference): array {
        if (trim($reference) === '') {
            return [
                'key' => $type . '_purchase',
                'label' => ucfirst($type) . ' purchase',
                'checks' => [$this->check($type . '.real_purchase', 'SKIP',
                    'No ' . $type . ' Purchase reference was supplied.')],
            ];
        }

        try {
            if ($type === 'course') {
                $report = (new CommerceCoursePurchaseCertifier($this->database))->certify(trim($reference))->to_array();
            } else if ($type === 'digital') {
                $report = (new CommerceDigitalPurchaseCertifier($this->database))->certify(trim($reference))->to_array();
            } else {
                $report = (new CommerceBundlePurchaseCertifier($this->database))->certify(trim($reference))->to_array();
            }

            $checks = array_map(static function (array $check) use ($type): array {
                return [
                    'key' => $type . '.' . $check['key'],
                    'status' => $check['status'] === 'PASS' ? 'PASS' : 'FAIL',
                    'message' => $check['message'],
                    'details' => $check['details'] ?? [],
                ];
            }, $report['checks']);
        } catch (\Throwable $exception) {
            $checks = [$this->check($type . '.certification', 'FAIL', $exception->getMessage())];
        }

        return ['key' => $type . '_purchase', 'label' => ucfirst($type) . ' purchase', 'checks' => $checks];
    }

    /** @param array<int, array<string, mixed>> $checks */
    private function source_contains(array &$checks, string $key, string $relativepath, string $needle): void {
        $path = $this->plugindir . '/' . $relativepath;
        $source = is_file($path) ? file_get_contents($path) : false;
        $this->add($checks, $key, is_string($source) && str_contains($source, $needle),
            $relativepath . ' contains the required integration.');
    }

    /** @param array<int, array<string, mixed>> $checks */
    private function add(array &$checks, string $key, bool $passed, string $message, array $details = []): void {
        $checks[] = $this->check($key, $passed ? 'PASS' : 'FAIL', $message, $details);
    }

    /** @return array<string, mixed> */
    private function check(string $key, string $status, string $message, array $details = []): array {
        return ['key' => $key, 'status' => $status, 'message' => $message, 'details' => $details];
    }
}
