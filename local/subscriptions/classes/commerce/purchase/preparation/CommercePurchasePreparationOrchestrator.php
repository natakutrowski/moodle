<?php

namespace local_subscriptions\commerce\purchase\preparation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestStatus;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Validates and prepares complete Commerce purchase requests.
 *
 * This orchestrator is strictly provider-independent and read-only.
 */
final class CommercePurchasePreparationOrchestrator {

    public function __construct(
        private readonly CommercePurchaseHandlerRegistry $handlerregistry
    ) {
    }

    public function prepare(
        CommercePurchaseRequest $request
    ): CommercePurchasePreparation {
        $validation =
            CommercePurchaseValidationResult::valid();

        $this->validate_request_status(
            $request,
            $validation
        );

        $prepareditems = [];

        foreach ($request->get_items() as $item) {
            try {
                $handler =
                    $this->handlerregistry
                        ->resolve($item);
            } catch (\Throwable $exception) {
                $validation->add_error(
                    'purchase_handler_resolution_failed',
                    $exception->getMessage(),
                    [
                        'itemreference' =>
                            $item->get_item()
                                ->get_reference(),
                    ]
                );

                continue;
            }

            $itemvalidation = $handler->validate(
                $item,
                $request->get_customer()
            );

            $validation->merge(
                $itemvalidation
            );

            if (!$itemvalidation->is_valid()) {
                continue;
            }

            try {
                $prepareditems[] =
                    $handler->prepare(
                        $item,
                        $request->get_customer()
                    );
            } catch (\Throwable $exception) {
                $validation->add_error(
                    'purchase_item_preparation_failed',
                    $exception->getMessage(),
                    [
                        'itemreference' =>
                            $item->get_item()
                                ->get_reference(),

                        'handler' =>
                            $handler->get_key(),
                    ]
                );
            }
        }

        $this->validate_prepared_items(
            $request,
            $prepareditems,
            $validation
        );

        if (!$validation->is_valid()) {
            throw new CommercePurchaseRequestValidationException(
                'The Commerce purchase request could not be prepared.',
                $validation
            );
        }

        return new CommercePurchasePreparation(
            $request->with_status(
                CommercePurchaseRequestStatus::VALIDATED
            ),
            $prepareditems,
            $validation,
            time()
        );
    }

    private function validate_request_status(
        CommercePurchaseRequest $request,
        CommercePurchaseValidationResult $validation
    ): void {
        if (
            $request->get_status()
            !== CommercePurchaseRequestStatus::DRAFT
        ) {
            $validation->add_error(
                'purchase_request_not_draft',
                'Only a draft Commerce purchase request can be prepared.',
                [
                    'status' =>
                        $request->get_status(),

                    'reference' =>
                        $request->get_reference(),
                ]
            );
        }

        if ($request->is_terminal()) {
            $validation->add_error(
                'purchase_request_terminal',
                'A terminal Commerce purchase request cannot be prepared.',
                [
                    'status' =>
                        $request->get_status(),

                    'reference' =>
                        $request->get_reference(),
                ]
            );
        }
    }

    /**
     * @param CommercePreparedPurchaseItem[] $prepareditems
     */
    private function validate_prepared_items(
        CommercePurchaseRequest $request,
        array $prepareditems,
        CommercePurchaseValidationResult $validation
    ): void {
        if (
            count($prepareditems)
            !== count($request->get_items())
        ) {
            $validation->add_error(
                'purchase_prepared_item_count_mismatch',
                'Not all Commerce purchase request items were prepared.',
                [
                    'requested' =>
                        count($request->get_items()),

                    'prepared' =>
                        count($prepareditems),
                ]
            );

            return;
        }

        $preparedtotal = array_sum(
            array_map(
                static fn(
                    CommercePreparedPurchaseItem $item
                ): int => $item->get_total_amount_minor(),
                $prepareditems
            )
        );

        if (
            $preparedtotal
            !== $request->get_total_amount_minor()
        ) {
            $validation->add_error(
                'purchase_total_mismatch',
                'The prepared Commerce amount differs from the request amount.',
                [
                    'requestamountminor' =>
                        $request->get_total_amount_minor(),

                    'preparedamountminor' =>
                        $preparedtotal,
                ]
            );
        }

        foreach ($prepareditems as $prepareditem) {
            if (
                $prepareditem->get_currency()
                !== $request->get_currency()
            ) {
                $validation->add_error(
                    'purchase_currency_mismatch',
                    'A prepared Commerce item has an unexpected currency.',
                    [
                        'requestcurrency' =>
                            $request->get_currency(),

                        'itemcurrency' =>
                            $prepareditem->get_currency(),
                    ]
                );
            }
        }
    }
}