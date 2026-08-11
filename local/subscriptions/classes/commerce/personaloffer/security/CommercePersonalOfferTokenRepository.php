<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\security;

defined('MOODLE_INTERNAL') || die();

final class CommercePersonalOfferTokenRepository {
    private const TABLE = 'local_subs_commerce_offer_token';

    public function __construct(private readonly \moodle_database $db) {}

    public function create(int $offerid, int $version, string $tokenhash, string $issuancekey, string $requesthash): CommercePersonalOfferTokenRecord {
        $now = time();
        $id = (int)$this->db->insert_record(self::TABLE, (object)[
            'offerid' => $offerid,
            'tokenversion' => $version,
            'tokenhash' => $tokenhash,
            'issuancekey' => $issuancekey,
            'requesthash' => $requesthash,
            'timecreated' => $now,
        ]);
        return $this->get_by_id($id) ?? throw new \RuntimeException('Unable to reload Personal Offer token record.');
    }

    public function get_by_id(int $id): ?CommercePersonalOfferTokenRecord {
        $record = $this->db->get_record(self::TABLE, ['id' => $id]);
        return $record ? $this->map($record) : null;
    }

    public function get_by_offer_id(int $offerid): ?CommercePersonalOfferTokenRecord {
        $record = $this->db->get_record(self::TABLE, ['offerid' => $offerid]);
        return $record ? $this->map($record) : null;
    }

    public function get_by_hash(string $tokenhash): ?CommercePersonalOfferTokenRecord {
        $record = $this->db->get_record(self::TABLE, ['tokenhash' => $tokenhash]);
        return $record ? $this->map($record) : null;
    }

    public function get_by_issuance_key(string $issuancekey): ?CommercePersonalOfferTokenRecord {
        $record = $this->db->get_record(self::TABLE, ['issuancekey' => trim($issuancekey)]);
        return $record ? $this->map($record) : null;
    }

    private function map(\stdClass $record): CommercePersonalOfferTokenRecord {
        return new CommercePersonalOfferTokenRecord(
            (int)$record->id,
            (int)$record->offerid,
            (int)$record->tokenversion,
            (string)$record->tokenhash,
            (string)$record->issuancekey,
            (string)$record->requesthash
        );
    }
}
