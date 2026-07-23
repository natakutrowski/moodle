<?php

namespace local_subscriptions\commerce\legacy\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\legacy\DigitalPurchaseFactory;

/**
 * Read-only repository exposing historical digital payments as purchases.
 */
class DigitalPurchaseRepository {

    /**
     * Returns one historical digital purchase.
     */
    public function get_by_purchase_id(
        int $purchaseid
    ): ?DigitalPurchase {
        global $DB;

        if ($purchaseid <= 0) {
            throw new \InvalidArgumentException(
                'Digital purchase identifier must be greater than zero.'
            );
        }

        $paymentrequest = $DB->get_record(
            'subscription_digital_payment_request',
            [
                'id' => $purchaseid,
            ]
        );

        if (!$paymentrequest) {
            return null;
        }

        $product = $DB->get_record(
            'subscription_digital_product',
            [
                'id' => (int)$paymentrequest->productid,
            ]
        ) ?: null;

        return DigitalPurchaseFactory::from_legacy_records(
            $paymentrequest,
            $product
        );
    }

    /**
     * Returns digital purchases linked to one Moodle user.
     *
     * @return DigitalPurchase[]
     */
    public function get_by_user_id(
        int $userid
    ): array {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'User identifier must be greater than zero.'
            );
        }

        $records = $DB->get_records(
            'subscription_digital_payment_request',
            [
                'userid' => $userid,
            ],
            'creation_date DESC, id DESC'
        );

        return $this->hydrate_records(
            $records
        );
    }

    /**
     * Returns digital purchases linked to an email.
     *
     * Useful for historical guest purchases that predate userid backfills.
     *
     * @return DigitalPurchase[]
     */
    public function get_by_email(
        string $email
    ): array {
        global $DB;

        $email = trim(
            \core_text::strtolower($email)
        );

        if ($email === '') {
            throw new \InvalidArgumentException(
                'Digital purchase email cannot be empty.'
            );
        }

        $records = $DB->get_records_select(
            'subscription_digital_payment_request',
            'LOWER(email) = :email',
            [
                'email' => $email,
            ],
            'creation_date DESC, id DESC'
        );

        return $this->hydrate_records(
            $records
        );
    }

    /**
     * Returns the latest digital purchases.
     *
     * @return DigitalPurchase[]
     */
    public function get_recent(
        int $limit = 50,
        int $offset = 0
    ): array {
        global $DB;

        $limit = max(
            1,
            min(500, $limit)
        );

        $offset = max(
            0,
            $offset
        );

        $records = $DB->get_records(
            'subscription_digital_payment_request',
            [],
            'creation_date DESC, id DESC',
            '*',
            $offset,
            $limit
        );

        return $this->hydrate_records(
            $records
        );
    }

    /**
     * @param \stdClass[] $records
     * @return DigitalPurchase[]
     */
    private function hydrate_records(
        array $records
    ): array {
        if ($records === []) {
            return [];
        }

        $productids = [];

        foreach ($records as $record) {
            $productids[] =
                (int)$record->productid;
        }

        $products = $this->get_products_by_ids(
            $productids
        );

        $result = [];

        foreach ($records as $record) {
            $productid =
                (int)$record->productid;

            $result[] =
                DigitalPurchaseFactory::from_legacy_records(
                    $record,
                    $products[$productid] ?? null
                );
        }

        return $result;
    }

    /**
     * @param int[] $productids
     * @return array<int,\stdClass>
     */
    private function get_products_by_ids(
        array $productids
    ): array {
        global $DB;

        $productids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $productids
                    ),
                    static fn(int $id): bool =>
                        $id > 0
                )
            )
        );

        if ($productids === []) {
            return [];
        }

        return $DB->get_records_list(
            'subscription_digital_product',
            'id',
            $productids
        );
    }
}