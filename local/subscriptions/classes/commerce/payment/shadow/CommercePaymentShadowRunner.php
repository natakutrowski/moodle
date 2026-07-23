<?php

namespace local_subscriptions\commerce\payment\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Safely executes payment shadow comparison.
 */
final class CommercePaymentShadowRunner {

    public static function run(
        \stdClass $paymentrequest,
        string $paymentrequesttable,
        array $legacyoptions,
        string $source,
        bool $live
    ): void {
        if (
            !get_config(
                'local_subscriptions',
                'commerce_payment_shadow_enabled'
            )
        ) {
            return;
        }

        try {
            $report =
                CommerceRuntimeFactory::create()
                    ->payment_shadow()
                    ->evaluate(
                        $paymentrequest,
                        $paymentrequesttable,
                        $legacyoptions,
                        $live
                    );

            (new CommercePaymentShadowLogger())
                ->log(
                    $report,
                    $source
                );
        } catch (\Throwable $exception) {
            error_log(
                sprintf(
                    '[Commerce payment shadow][%s] %s: %s',
                    $source,
                    get_class($exception),
                    $exception->getMessage()
                )
            );
        }
    }
}