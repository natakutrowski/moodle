<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

final class MoodleCommercePersonalOfferRepository implements CommercePersonalOfferRepository {
    private const TABLE = 'local_subs_commerce_offer';

    public function __construct(private readonly \moodle_database $db) {}

    public function get_by_id(int $id): ?CommercePersonalOffer {
        $record = $this->db->get_record(self::TABLE, ['id' => $id]);
        return $record ? $this->hydrate($record) : null;
    }

    public function get_by_uuid(string $offeruuid): ?CommercePersonalOffer {
        $record = $this->db->get_record(self::TABLE, ['offeruuid' => strtolower(trim($offeruuid))]);
        return $record ? $this->hydrate($record) : null;
    }

    public function save(CommercePersonalOffer $offer): CommercePersonalOffer {
        $now = time();
        $record = $this->to_record($offer);
        $record->timemodified = $now;
        if ($offer->get_id() === null) {
            $record->timecreated = $now;
            $id = (int)$this->db->insert_record(self::TABLE, $record);
        } else {
            $existing = $this->db->get_record(self::TABLE, ['id' => $offer->get_id()], '*', MUST_EXIST);
            $this->assert_assignment_unchanged($existing, $record);
            $record->id = $offer->get_id();
            $this->db->update_record(self::TABLE, $record);
            $id = $offer->get_id();
        }
        return $this->get_by_id($id) ?? throw new \RuntimeException('Unable to reload persisted Personal Offer.');
    }

    public function count(array $filters = []): int {
        [$where, $params] = $this->build_where($filters);
        return (int)$this->db->count_records_sql('SELECT COUNT(1) FROM {' . self::TABLE . '} o ' . $where, $params);
    }

    public function find(array $filters = [], int $limit = 100, int $offset = 0): array {
        [$where, $params] = $this->build_where($filters);
        $records = $this->db->get_records_sql(
            'SELECT o.* FROM {' . self::TABLE . '} o ' . $where . ' ORDER BY o.timecreated DESC, o.id DESC',
            $params,
            max(0, $offset),
            max(1, $limit)
        );
        return array_values(array_map(fn(\stdClass $record): CommercePersonalOffer => $this->hydrate($record), $records));
    }

    private function build_where(array $filters): array {
        $clauses = [];
        $params = [];
        if (!empty($filters['status'])) {
            $clauses[] = 'o.status = :status';
            $params['status'] = (string)$filters['status'];
        }
        if (!empty($filters['email'])) {
            $clauses[] = $this->db->sql_equal('o.beneficiaryemail', ':email', false, false);
            $params['email'] = strtolower(trim((string)$filters['email']));
        }
        if (!empty($filters['beneficiaryquery'])) {
            $query = '%' . $this->db->sql_like_escape(trim((string)$filters['beneficiaryquery'])) . '%';
            $params['beneficiaryqueryemail'] = $query;
            $params['beneficiaryqueryfirstname'] = $query;
            $params['beneficiaryquerylastname'] = $query;
            $params['beneficiaryqueryfullname'] = $query;
            $fullname = $this->db->sql_concat('u.firstname', "' '", 'u.lastname');
            $clauses[] = '(' . $this->db->sql_like('o.beneficiaryemail', ':beneficiaryqueryemail', false)
                . ' OR EXISTS (SELECT 1 FROM {user} u WHERE u.id = o.beneficiaryuserid AND u.deleted = 0 AND ('
                . $this->db->sql_like('u.firstname', ':beneficiaryqueryfirstname', false) . ' OR '
                . $this->db->sql_like('u.lastname', ':beneficiaryquerylastname', false) . ' OR '
                . $this->db->sql_like($fullname, ':beneficiaryqueryfullname', false) . ')))';
        }
        if (!empty($filters['campaignkey'])) {
            $clauses[] = 'o.campaignkey = :campaignkey';
            $params['campaignkey'] = trim((string)$filters['campaignkey']);
        }
        if (!empty($filters['targetproductid'])) {
            $clauses[] = 'o.targetproductid = :targetproductid';
            $params['targetproductid'] = (int)$filters['targetproductid'];
        }
        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }


    private function assert_assignment_unchanged(\stdClass $existing, \stdClass $candidate): void {
        foreach ([
            'offeruuid', 'campaignkey', 'sourcepurchaseid', 'targetproductid', 'beneficiaryemail',
            'validfrom', 'expiresat', 'termsversion', 'termsjson',
        ] as $field) {
            if ((string)($existing->{$field} ?? '') !== (string)($candidate->{$field} ?? '')) {
                throw new \coding_exception('A persisted Personal Offer assignment and its commercial terms are immutable.');
            }
        }
    }

    private function to_record(CommercePersonalOffer $offer): \stdClass {
        return (object)[
            'offeruuid' => $offer->get_offer_uuid(),
            'campaignkey' => $offer->get_campaign_key(),
            'sourcepurchaseid' => $offer->get_source_purchase_id(),
            'targetproductid' => $offer->get_target_product_id(),
            'beneficiaryuserid' => $offer->get_beneficiary_user_id(),
            'beneficiaryemail' => $offer->get_beneficiary_email(),
            'status' => $offer->get_status(),
            'validfrom' => $offer->get_valid_from(),
            'expiresat' => $offer->get_expires_at(),
            'termsversion' => $offer->get_terms()->get_version(),
            'termsjson' => json_encode($offer->get_terms()->get_data(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'metadatajson' => json_encode($offer->get_metadata(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'redeemedat' => $offer->get_redeemed_at(),
            'redeemedpurchaseid' => $offer->get_redeemed_purchase_id(),
            'revokedat' => $offer->get_revoked_at(),
            'revokedbyuserid' => $offer->get_revoked_by_user_id(),
            'revokereason' => $offer->get_revoke_reason(),
            'issuedbyuserid' => $offer->get_issued_by_user_id(),
        ];
    }

    private function hydrate(\stdClass $record): CommercePersonalOffer {
        $termsdata = json_decode((string)$record->termsjson, true, 512, JSON_THROW_ON_ERROR);
        $metadata = json_decode((string)$record->metadatajson, true, 512, JSON_THROW_ON_ERROR);
        if ((int)$record->termsversion !== CommercePersonalOfferTerms::VERSION) {
            throw new \coding_exception('Unsupported persisted Personal Offer terms version.');
        }
        return new CommercePersonalOffer(
            (int)$record->id,
            (string)$record->offeruuid,
            $record->campaignkey === null ? null : (string)$record->campaignkey,
            $record->sourcepurchaseid === null ? null : (int)$record->sourcepurchaseid,
            (int)$record->targetproductid,
            $record->beneficiaryuserid === null ? null : (int)$record->beneficiaryuserid,
            (string)$record->beneficiaryemail,
            (string)$record->status,
            new CommercePersonalOfferTerms(is_array($termsdata) ? $termsdata : []),
            $record->validfrom === null ? null : (int)$record->validfrom,
            $record->expiresat === null ? null : (int)$record->expiresat,
            is_array($metadata) ? $metadata : [],
            $record->redeemedat === null ? null : (int)$record->redeemedat,
            $record->redeemedpurchaseid === null ? null : (int)$record->redeemedpurchaseid,
            $record->revokedat === null ? null : (int)$record->revokedat,
            $record->revokedbyuserid === null ? null : (int)$record->revokedbyuserid,
            $record->revokereason === null ? null : (string)$record->revokereason,
            $record->issuedbyuserid === null ? null : (int)$record->issuedbyuserid,
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }
}
