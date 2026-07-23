<?php

namespace local_subscriptions\commerce\payment\orchestration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;

/**
 * Creates deterministic provider contexts for Commerce payments.
 */
final class CommercePaymentProviderContextFactory {

    public function create(
        CommercePaymentRequest $request,
        bool $live,
        array $metadata = []
    ): CommercePaymentProviderContext {
        return new CommercePaymentProviderContext(
            $this->build_idempotency_key(
                $request
            ),
            $live,
            array_merge(
                [
                    'requestreference' =>
                        $request->get_reference(),

                    'currency' =>
                        $request->get_currency(),

                    'amountminor' =>
                        $request->get_amount_minor(),

                    'preferredprovider' =>
                        $request
                            ->get_preferred_provider(),
                ],
                $metadata
            ),
            time()
        );
    }

    public function build_idempotency_key(
        CommercePaymentRequest $request
    ): string {
        $legacytable =
            trim(
                (string)$request->get_metadata_value(
                    'legacy_payment_request_table',
                    ''
                )
            );

        $legacyid =
            (int)$request->get_metadata_value(
                'legacy_payment_request_id',
                0
            );

        if (
            $legacytable !== ''
            && $legacyid > 0
        ) {
            return substr(
                sprintf(
                    'commerce:%s:%d:%s',
                    preg_replace(
                        '/[^a-z0-9_]+/',
                        '_',
                        strtolower($legacytable)
                    ),
                    $legacyid,
                    hash(
                        'sha256',
                        implode(
                            '|',
                            [
                                $request
                                    ->get_reference(),

                                $request
                                    ->get_currency(),

                                $request
                                    ->get_amount_minor(),

                                $request
                                    ->get_preferred_provider()
                                    ?? 'automatic',
                            ]
                        )
                    )
                ),
                0,
                255
            );
        }

        return substr(
            'commerce:'
            . hash(
                'sha256',
                implode(
                    '|',
                    [
                        $request
                            ->get_reference(),

                        $request
                            ->get_currency(),

                        $request
                            ->get_amount_minor(),

                        $request
                            ->get_preferred_provider()
                            ?? 'automatic',
                    ]
                )
            ),
            0,
            255
        );
    }
}