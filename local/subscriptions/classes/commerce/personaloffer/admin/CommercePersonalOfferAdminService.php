<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\security\CommercePersonalOfferTokenCodec;
use local_subscriptions\commerce\personaloffer\security\CommercePersonalOfferTokenRepository;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class CommercePersonalOfferAdminService {
    private const OFFER_TABLE = 'local_subs_commerce_offer';

    public function __construct(private readonly \moodle_database $db) {}

    public function secure_url(CommercePersonalOffer $offer): ?\moodle_url {
        if ($offer->get_id() === null) {
            return null;
        }
        $tokens = new CommercePersonalOfferTokenRepository($this->db);
        if ($tokens->get_by_offer_id($offer->get_id()) === null) {
            return null;
        }
        return new \moodle_url('/local/subscriptions/offer.php', ['token' => CommercePersonalOfferTokenCodec::build($offer->get_offer_uuid())]);
    }

    public function revoke(CommercePersonalOffer $offer, int $byuserid, ?string $reason): CommercePersonalOffer {
        return CommercePersonalOfferFactory::create($this->db)->revoke($offer->get_offer_uuid(), $byuserid, $reason);
    }

    public function reissue(CommercePersonalOffer $source, int $issuedbyuserid, int $validitydays): CommercePersonalOffer {
        if ($source->get_effective_status(time()) === CommercePersonalOffer::STATUS_ISSUED) {
            throw new \moodle_exception('commerce_personal_offer_reissue_active', 'local_subscriptions');
        }
        $validitydays = max(1, min(3650, $validitydays));
        $now = time();
        $metadata = $source->get_metadata();
        $metadata['reissuedfromofferuuid'] = $source->get_offer_uuid();
        $metadata['reissuedfromofferid'] = $source->get_id();
        $metadata['reissuedat'] = $now;
        $request = new CommercePersonalOfferIssueRequest(
            'admin-reissue:' . $source->get_offer_uuid() . ':' . $now . ':' . bin2hex(random_bytes(4)),
            $source->get_target_product_id(),
            $source->get_beneficiary_email(),
            $source->get_terms(),
            $source->get_campaign_key(),
            $source->get_source_purchase_id(),
            $source->get_beneficiary_user_id(),
            $now,
            $now + ($validitydays * DAYSECS),
            $metadata,
            $issuedbyuserid
        );
        return CommercePersonalOfferFactory::create($this->db)->issue($request)->get_offer();
    }

    public function can_delete(CommercePersonalOffer $offer): bool {
        if ($offer->get_id() === null || $offer->get_status() === CommercePersonalOffer::STATUS_REDEEMED || $offer->get_redeemed_purchase_id() !== null) {
            return false;
        }
        if ($this->db->record_exists('local_subs_commerce_offer_campaign_member', ['offerid' => $offer->get_id()])) {
            return false;
        }
        $needle = '%"offeruuid":"' . $this->db->sql_like_escape($offer->get_offer_uuid()) . '"%';
        if ($this->db->record_exists_select('local_subs_commerce_mail', $this->db->sql_like('contextjson', ':ctx', false), ['ctx' => $needle])) {
            return false;
        }
        return true;
    }

    public function delete(CommercePersonalOffer $offer): void {
        if (!$this->can_delete($offer)) {
            throw new \moodle_exception('commerce_personal_offer_delete_not_allowed', 'local_subscriptions');
        }
        $transaction = $this->db->start_delegated_transaction();
        $this->db->delete_records('local_subs_commerce_offer_token', ['offerid' => $offer->get_id()]);
        $this->db->delete_records(self::OFFER_TABLE, ['id' => $offer->get_id()]);
        $transaction->allow_commit();
    }

    public function replace(CommercePersonalOffer $source, int $targetproductid, \local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms $terms, ?string $campaignkey, ?int $validfrom, ?int $expiresat, int $issuedbyuserid): CommercePersonalOffer {
        if ($source->get_status() !== CommercePersonalOffer::STATUS_ISSUED || $source->get_effective_status(time()) !== CommercePersonalOffer::STATUS_ISSUED) {
            throw new \moodle_exception('commerce_personal_offer_edit_not_allowed', 'local_subscriptions');
        }
        $metadata = $source->get_metadata();
        $metadata['replacesofferuuid'] = $source->get_offer_uuid();
        $metadata['replacesofferid'] = $source->get_id();
        $metadata['replacedat'] = time();
        $request = new CommercePersonalOfferIssueRequest(
            'admin-replace:' . $source->get_offer_uuid() . ':' . time() . ':' . bin2hex(random_bytes(4)),
            $targetproductid,
            $source->get_beneficiary_email(),
            $terms,
            $campaignkey,
            $source->get_source_purchase_id(),
            $source->get_beneficiary_user_id(),
            $validfrom,
            $expiresat,
            $metadata,
            $issuedbyuserid
        );
        $new = CommercePersonalOfferFactory::create($this->db)->issue($request)->get_offer();
        CommercePersonalOfferFactory::create($this->db)->revoke($source->get_offer_uuid(), $issuedbyuserid, 'Replaced by offer #' . $new->get_id());
        return $new;
    }

    /** @return array<string,int> */
    public function global_stats(): array {
        $now = time();
        $total = (int)$this->db->count_records(self::OFFER_TABLE);
        $issued = (int)$this->db->count_records(self::OFFER_TABLE, ['status' => CommercePersonalOffer::STATUS_ISSUED]);
        $redeemed = (int)$this->db->count_records(self::OFFER_TABLE, ['status' => CommercePersonalOffer::STATUS_REDEEMED]);
        $revoked = (int)$this->db->count_records(self::OFFER_TABLE, ['status' => CommercePersonalOffer::STATUS_REVOKED]);
        $expired = (int)$this->db->count_records_select(self::OFFER_TABLE, 'status = :issued AND expiresat IS NOT NULL AND expiresat < :now', ['issued' => CommercePersonalOffer::STATUS_ISSUED, 'now' => $now]);
        return ['total' => $total, 'available' => max(0, $issued - $expired), 'expired' => $expired, 'redeemed' => $redeemed, 'revoked' => $revoked];
    }

    /** @return array<int,\stdClass> */
    public function campaign_stats(): array {
        $sql = "SELECT COALESCE(campaignkey, '') campaignkey, COUNT(1) total,
                       SUM(CASE WHEN status = :redeemed THEN 1 ELSE 0 END) redeemed,
                       SUM(CASE WHEN status = :revoked THEN 1 ELSE 0 END) revoked
                  FROM {" . self::OFFER_TABLE . "}
              GROUP BY campaignkey
              ORDER BY total DESC, campaignkey ASC";
        return array_values($this->db->get_records_sql($sql, ['redeemed' => CommercePersonalOffer::STATUS_REDEEMED, 'revoked' => CommercePersonalOffer::STATUS_REVOKED]));
    }
}
