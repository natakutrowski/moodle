<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\reservation;

defined('MOODLE_INTERNAL') || die();

final class CommercePedagogicalSeatReservationRepository {
    private const TABLE = 'local_subs_commerce_ped_resv';

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;

        return new self($db ?? $DB);
    }

    public function find(
        int $promotionid,
        int $productid,
        string $cartuuid
    ): ?CommercePedagogicalSeatReservation {
        $record = $this->db->get_record(
            self::TABLE,
            [
                'promotionid' => $promotionid,
                'productid' => $productid,
                'cartuuid' => strtolower(trim($cartuuid)),
            ],
            '*',
            IGNORE_MISSING
        );

        return $record
            ? $this->hydrate($record)
            : null;
    }


    public function find_for_cart_product(
        string $cartuuid,
        int $productid
    ): ?CommercePedagogicalSeatReservation {
        $records = $this->db->get_records(
            self::TABLE,
            [
                'cartuuid' => strtolower(trim($cartuuid)),
                'productid' => $productid,
            ],
            'id DESC'
        );

        if ($records === []) {
            return null;
        }

        // Prefer the live/terminal row that owns the current cart journey.
        // Historical released/expired rows may coexist when the same stable
        // Commerce product is reused by successive pedagogical promotions.
        foreach ([
            CommercePedagogicalSeatReservation::ACTIVE,
            CommercePedagogicalSeatReservation::CONSUMED,
        ] as $preferredstate) {
            foreach ($records as $record) {
                if ((string)$record->state === $preferredstate) {
                    return $this->hydrate($record);
                }
            }
        }

        return $this->hydrate(reset($records));
    }

    public function save(
        CommercePedagogicalSeatReservation $reservation
    ): CommercePedagogicalSeatReservation {
        $record = (object)[
            'promotionid' => $reservation->get_promotion_id(),
            'productid' => $reservation->get_product_id(),
            'cartuuid' => $reservation->get_cart_uuid(),
            'customerid' => $reservation->get_customer_id(),
            'quantity' => $reservation->get_quantity(),
            'state' => $reservation->get_state(),
            'expiresat' => $reservation->get_expires_at(),
            'checkoutstartedat' => $reservation->get_checkout_started_at(),
            'paymentstartedat' => $reservation->get_payment_started_at(),
            'purchasereference' =>
                $reservation->get_purchase_reference(),
            'timecreated' => $reservation->get_time_created(),
            'timemodified' => $reservation->get_time_modified(),
        ];

        if ($reservation->get_id() !== null) {
            $record->id = $reservation->get_id();
            $this->db->update_record(self::TABLE, $record);

            return $reservation;
        }

        $record->id = $this->db->insert_record(
            self::TABLE,
            $record
        );

        return $this->hydrate($record);
    }

    public function has_active_for_customer_offer(
        int $promotionid,
        int $productid,
        int $customerid,
        int $now,
        ?string $excludedcartuuid = null
    ): bool {
        if ($customerid <= 0) {
            return false;
        }

        $params = [
            'promotionid' => $promotionid,
            'productid' => $productid,
            'customerid' => $customerid,
            'state' => CommercePedagogicalSeatReservation::ACTIVE,
            'now' => $now,
        ];
        $select = 'promotionid = :promotionid'
            . ' AND productid = :productid'
            . ' AND customerid = :customerid'
            . ' AND state = :state'
            . ' AND expiresat > :now';

        if ($excludedcartuuid !== null) {
            $excludedcartuuid = strtolower(trim($excludedcartuuid));
            if ($excludedcartuuid !== '') {
                $select .= ' AND cartuuid <> :excludedcartuuid';
                $params['excludedcartuuid'] = $excludedcartuuid;
            }
        }

        return $this->db->record_exists_select(
            self::TABLE,
            $select,
            $params
        );
    }

    public function active_quantity_for_promotion(
        int $promotionid,
        int $now,
        ?string $excludedcartuuid = null,
        ?int $excludedproductid = null
    ): int {
        $params = [
            'promotionid' => $promotionid,
            'state' => CommercePedagogicalSeatReservation::ACTIVE,
            'now' => $now,
        ];

        $sql =
            "SELECT COALESCE(SUM(quantity), 0)
               FROM {" . self::TABLE . "}
              WHERE promotionid = :promotionid
                AND state = :state
                AND expiresat > :now";

        if (
            $excludedcartuuid !== null
            && $excludedproductid !== null
        ) {
            $sql .=
                " AND NOT (
                    cartuuid = :excludedcartuuid
                    AND productid = :excludedproductid
                )";
            $params['excludedcartuuid'] =
                strtolower(trim($excludedcartuuid));
            $params['excludedproductid'] =
                $excludedproductid;
        }

        return (int)$this->db->get_field_sql(
            $sql,
            $params
        );
    }

    public function active_quantity_for_offer(
        int $promotionid,
        int $productid,
        int $now,
        ?string $excludedcartuuid = null
    ): int {
        $params = [
            'promotionid' => $promotionid,
            'productid' => $productid,
            'state' => CommercePedagogicalSeatReservation::ACTIVE,
            'now' => $now,
        ];

        $sql =
            "SELECT COALESCE(SUM(quantity), 0)
               FROM {" . self::TABLE . "}
              WHERE promotionid = :promotionid
                AND productid = :productid
                AND state = :state
                AND expiresat > :now";

        if ($excludedcartuuid !== null) {
            $sql .= " AND cartuuid <> :excludedcartuuid";
            $params['excludedcartuuid'] =
                strtolower(trim($excludedcartuuid));
        }

        return (int)$this->db->get_field_sql(
            $sql,
            $params
        );
    }

    /**
     * @return CommercePedagogicalSeatReservation[]
     */
    public function active_for_cart(
        string $cartuuid,
        int $now
    ): array {
        $records = $this->db->get_records_select(
            self::TABLE,
            'cartuuid = :cartuuid AND state = :state AND expiresat > :now',
            [
                'cartuuid' => strtolower(trim($cartuuid)),
                'state' => CommercePedagogicalSeatReservation::ACTIVE,
                'now' => $now,
            ],
            'promotionid ASC, productid ASC, id ASC'
        );

        return array_values(array_map(
            fn(\stdClass $record): CommercePedagogicalSeatReservation =>
                $this->hydrate($record),
            $records
        ));
    }

    public function expire_due(int $now): int {
        $records = $this->db->get_records_select(
            self::TABLE,
            'state = :state AND expiresat <= :now',
            [
                'state' => CommercePedagogicalSeatReservation::ACTIVE,
                'now' => $now,
            ],
            '',
            'id'
        );

        foreach ($records as $record) {
            $this->db->update_record(
                self::TABLE,
                (object)[
                    'id' => (int)$record->id,
                    'state' =>
                        CommercePedagogicalSeatReservation::EXPIRED,
                    'timemodified' => $now,
                ]
            );
        }

        return count($records);
    }

    public function release_cart(
        string $cartuuid,
        int $now
    ): int {
        $cartuuid = strtolower(trim($cartuuid));

        $records = $this->db->get_records(
            self::TABLE,
            [
                'cartuuid' => $cartuuid,
                'state' =>
                    CommercePedagogicalSeatReservation::ACTIVE,
            ],
            '',
            'id'
        );

        foreach ($records as $record) {
            $this->db->update_record(
                self::TABLE,
                (object)[
                    'id' => (int)$record->id,
                    'state' =>
                        CommercePedagogicalSeatReservation::RELEASED,
                    'timemodified' => $now,
                ]
            );
        }

        return count($records);
    }

    private function hydrate(
        \stdClass $record
    ): CommercePedagogicalSeatReservation {
        return new CommercePedagogicalSeatReservation(
            (int)$record->id,
            (int)$record->promotionid,
            (int)$record->productid,
            (string)$record->cartuuid,
            (int)$record->customerid,
            (int)$record->quantity,
            (string)$record->state,
            (int)$record->expiresat,
            $record->checkoutstartedat !== null
                ? (int)$record->checkoutstartedat
                : null,
            $record->paymentstartedat !== null
                ? (int)$record->paymentstartedat
                : null,
            $record->purchasereference !== null
                ? (string)$record->purchasereference
                : null,
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }
}
