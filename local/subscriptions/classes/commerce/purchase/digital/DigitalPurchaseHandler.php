<?php

namespace local_subscriptions\commerce\purchase\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandler;
use local_subscriptions\commerce\purchase\handler\CommercePurchasePreparationException;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Business handler for digital product purchases.
 *
 * No payment request or download token is created here.
 */
final class DigitalPurchaseHandler
    implements CommercePurchaseHandler {

    public const KEY = 'digital';

    public const FULFILLMENT_KEY =
        'digital_download';

    public function __construct(
        private readonly ?DigitalProductRepository $productrepository = null
    ) {
    }

    public function get_key(): string {
        return self::KEY;
    }

    public function supports(
        CommercePurchaseRequestItem $item
    ): bool {
        return $item->get_item()->get_type()
            === CommerceItem::TYPE_DIGITAL;
    }

    public function validate(
        CommercePurchaseRequestItem $item,
        CommerceCustomer $customer
    ): CommercePurchaseValidationResult {
        $result =
            CommercePurchaseValidationResult::valid();

        if (!$this->supports($item)) {
            return $result->add_error(
                'unsupported_digital_item',
                'The DigitalPurchaseHandler does not support this Commerce item.',
                [
                    'itemreference' =>
                        $item->get_item()->get_reference(),

                    'itemtype' =>
                        $item->get_item()->get_type(),
                ]
            );
        }

        $productid = $this->resolve_product_id(
            $item
        );

        if ($productid === null) {
            return $result->add_error(
                'digital_product_missing',
                'The Commerce digital item has no valid legacy product identifier.',
                [
                    'itemreference' =>
                        $item->get_item()->get_reference(),
                ]
            );
        }

        $product = $this->get_product_repository()
            ->find($productid);

        if ($product === null) {
            return $result->add_error(
                'digital_product_not_found',
                'The requested digital product does not exist.',
                [
                    'productid' => $productid,
                ]
            );
        }

        if (!$product->is_active()) {
            $result->add_error(
                'digital_product_inactive',
                'The requested digital product is inactive.',
                [
                    'productid' => $productid,
                ]
            );
        }

        if ($product->get_filename() === null) {
            $result->add_warning(
                'digital_product_file_missing',
                'The digital product has no configured filename.',
                [
                    'productid' => $productid,
                ]
            );
        }

        if ($customer->is_guest()) {
            $result->add_warning(
                'digital_purchase_guest_customer',
                'A digital purchase is being prepared for a guest customer.',
                [
                    'productid' => $productid,
                    'email' => $customer->get_email(),
                ]
            );
        }

        return $result;
    }

    public function prepare(
        CommercePurchaseRequestItem $item,
        CommerceCustomer $customer
    ): CommercePreparedPurchaseItem {
        $validation = $this->validate(
            $item,
            $customer
        );

        if (!$validation->is_valid()) {
            throw new CommercePurchasePreparationException(
                'The digital purchase item is invalid.',
                $validation
            );
        }

        $productid = $this->resolve_product_id(
            $item
        );

        if ($productid === null) {
            throw new CommercePurchasePreparationException(
                'The digital product identifier could not be resolved.',
                $validation
            );
        }

        $product = $this->get_product_repository()
            ->find($productid);

        if ($product === null) {
            throw new CommercePurchasePreparationException(
                'The digital product could not be loaded.',
                $validation
            );
        }

        return new CommercePreparedPurchaseItem(
            $item,
            self::KEY,
            self::FULFILLMENT_KEY,
            [
                'item_reference' =>
                    $item->get_item()->get_reference(),

                'item_type' =>
                    CommerceItem::TYPE_DIGITAL,

                'description' =>
                    $product->get_name(),

                'customer_email' =>
                    $customer->get_email(),

                'quantity' =>
                    $item->get_quantity(),
            ],
            [
                'productid' =>
                    $product->get_id(),

                'slug' =>
                    $product->get_slug(),

                'filename' =>
                    $product->get_filename(),

                'userid' =>
                    $customer->get_user_id(),

                'customer_email' =>
                    $customer->get_email(),

                'quantity' =>
                    $item->get_quantity(),
            ],
            [
                'handler' => self::KEY,

                'product_name' =>
                    $product->get_name(),

                'validation_warnings' =>
                    array_map(
                        static fn($issue): array =>
                            $issue->to_array(),
                        $validation->get_warnings()
                    ),
            ]
        );
    }

    private function resolve_product_id(
        CommercePurchaseRequestItem $item
    ): ?int {
        $legacyid = $item->get_item()
            ->get_legacy_id();

        if (
            $legacyid !== null
            && $legacyid > 0
        ) {
            return $legacyid;
        }

        $metadataid = (int)$item
            ->get_metadata_value(
                'productid',
                0
            );

        return $metadataid > 0
            ? $metadataid
            : null;
    }

    private function get_product_repository():
        DigitalProductRepository {
        return $this->productrepository
            ?? new LegacyDigitalProductRepository();
    }
}