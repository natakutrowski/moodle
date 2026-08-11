<?php
namespace local_subscriptions\commerce\personaloffer\certification;

defined('MOODLE_INTERNAL') || die();

final class CommercePersonalOfferCertificationService {
    private const OFFER = 'local_subs_commerce_offer';
    private const TOKEN = 'local_subs_commerce_offer_token';
    private const PURCHASE = 'local_subscriptions_commerce_purchase';
    private const PRODUCT = 'local_subs_commerce_product';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function certify(?string $campaign = null, bool $strict = false, int $samplelimit = 10): array {
        $campaign = $campaign !== null ? trim($campaign) : null;
        $samplelimit = max(0, min(100, $samplelimit));
        [$where, $params] = $this->scope($campaign);

        $metrics = [
            'offers_scanned' => (int)$this->db->count_records_select(self::OFFER, $where, $params),
            'issued' => 0, 'expired' => 0, 'redeemed' => 0, 'revoked' => 0,
        ];
        $now = time();
        $offers = $this->db->get_records_select(self::OFFER, $where, $params, 'id ASC');
        foreach ($offers as $offer) {
            if ($offer->status === 'issued' && !empty($offer->expiresat) && (int)$offer->expiresat < $now) {
                $metrics['expired']++;
            } elseif (array_key_exists($offer->status, $metrics)) {
                $metrics[$offer->status]++;
            }
        }

        $checks = [];
        $checks[] = $this->check('missing_token', 'error',
            "SELECT o.id, o.offeruuid, o.beneficiaryemail
               FROM {" . self::OFFER . "} o
          LEFT JOIN {" . self::TOKEN . "} t ON t.offerid = o.id
              WHERE t.id IS NULL" . $this->campaign_sql('o', $campaign),
            $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('orphan_token', 'error',
            "SELECT t.id, t.offerid, t.issuancekey
               FROM {" . self::TOKEN . "} t
          LEFT JOIN {" . self::OFFER . "} o ON o.id = t.offerid
              WHERE o.id IS NULL",
            [], $samplelimit);

        $checks[] = $this->check('missing_target_product', 'error',
            "SELECT o.id, o.offeruuid, o.targetproductid
               FROM {" . self::OFFER . "} o
          LEFT JOIN {" . self::PRODUCT . "} p ON p.id = o.targetproductid
              WHERE p.id IS NULL" . $this->campaign_sql('o', $campaign),
            $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('missing_source_purchase', 'error',
            "SELECT o.id, o.offeruuid, o.sourcepurchaseid
               FROM {" . self::OFFER . "} o
          LEFT JOIN {" . self::PURCHASE . "} p ON p.id = o.sourcepurchaseid
              WHERE o.sourcepurchaseid IS NOT NULL AND p.id IS NULL" . $this->campaign_sql('o', $campaign),
            $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('missing_redeemed_purchase', 'error',
            "SELECT o.id, o.offeruuid, o.redeemedpurchaseid
               FROM {" . self::OFFER . "} o
          LEFT JOIN {" . self::PURCHASE . "} p ON p.id = o.redeemedpurchaseid
              WHERE o.redeemedpurchaseid IS NOT NULL AND p.id IS NULL" . $this->campaign_sql('o', $campaign),
            $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('invalid_redeemed_state', 'error',
            "SELECT o.id, o.offeruuid, o.status, o.redeemedat, o.redeemedpurchaseid
               FROM {" . self::OFFER . "} o
              WHERE ((o.status = :redeemed AND (o.redeemedat IS NULL OR o.redeemedpurchaseid IS NULL))
                 OR (o.status <> :redeemed2 AND (o.redeemedat IS NOT NULL OR o.redeemedpurchaseid IS NOT NULL)))"
                . $this->campaign_sql('o', $campaign),
            ['redeemed' => 'redeemed', 'redeemed2' => 'redeemed'] + $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('invalid_revoked_state', 'error',
            "SELECT o.id, o.offeruuid, o.status, o.revokedat
               FROM {" . self::OFFER . "} o
              WHERE ((o.status = :revoked AND o.revokedat IS NULL)
                 OR (o.status <> :revoked2 AND o.revokedat IS NOT NULL))"
                . $this->campaign_sql('o', $campaign),
            ['revoked' => 'revoked', 'revoked2' => 'revoked'] + $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('unknown_status', 'error',
            "SELECT o.id, o.offeruuid, o.status
               FROM {" . self::OFFER . "} o
              WHERE o.status NOT IN (:issued, :redeemed, :revoked)" . $this->campaign_sql('o', $campaign),
            ['issued' => 'issued', 'redeemed' => 'redeemed', 'revoked' => 'revoked'] + $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('invalid_terms_json', 'error', null, [], $samplelimit,
            function() use ($offers): array {
                $bad = [];
                foreach ($offers as $offer) {
                    $decoded = json_decode((string)$offer->termsjson, true);
                    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                        $bad[] = ['id' => (int)$offer->id, 'offeruuid' => $offer->offeruuid];
                    }
                }
                return $bad;
            });

        $checks[] = $this->check('beneficiary_user_missing', 'error',
            "SELECT o.id, o.offeruuid, o.beneficiaryuserid
               FROM {" . self::OFFER . "} o
          LEFT JOIN {user} u ON u.id = o.beneficiaryuserid
              WHERE o.beneficiaryuserid IS NOT NULL AND u.id IS NULL" . $this->campaign_sql('o', $campaign),
            $this->campaign_params($campaign), $samplelimit);

        $checks[] = $this->check('beneficiary_email_changed', 'warning',
            "SELECT o.id, o.offeruuid, o.beneficiaryemail, u.email AS currentemail
               FROM {" . self::OFFER . "} o
               JOIN {user} u ON u.id = o.beneficiaryuserid
              WHERE o.beneficiaryuserid IS NOT NULL
                AND " . $this->db->sql_compare_text('LOWER(o.beneficiaryemail)') . " <> " . $this->db->sql_compare_text('LOWER(u.email)')
                . $this->campaign_sql('o', $campaign),
            $this->campaign_params($campaign), $samplelimit);

        $errors = 0; $warnings = 0;
        foreach ($checks as $check) {
            if ($check['count'] <= 0) { continue; }
            if ($check['severity'] === 'error') { $errors += $check['count']; }
            else { $warnings += $check['count']; }
        }
        $passed = $errors === 0 && (!$strict || $warnings === 0);
        return [
            'certified' => $passed,
            'strict' => $strict,
            'campaign' => $campaign,
            'generatedat' => time(),
            'metrics' => $metrics,
            'errors' => $errors,
            'warnings' => $warnings,
            'checks' => $checks,
        ];
    }

    private function scope(?string $campaign): array {
        if ($campaign === null || $campaign === '') { return ['1=1', []]; }
        return ['campaignkey = :campaignscope', ['campaignscope' => $campaign]];
    }

    private function campaign_sql(string $alias, ?string $campaign): string {
        return ($campaign === null || $campaign === '') ? '' : " AND {$alias}.campaignkey = :campaignfilter";
    }

    private function campaign_params(?string $campaign): array {
        return ($campaign === null || $campaign === '') ? [] : ['campaignfilter' => $campaign];
    }

    private function check(string $key, string $severity, ?string $sql, array $params, int $samplelimit, ?callable $provider = null): array {
        if ($provider !== null) {
            $rows = $provider();
            $count = count($rows);
            $samples = array_slice($rows, 0, $samplelimit);
        } else {
            $all = $this->db->get_records_sql($sql, $params);
            $count = count($all);
            $samples = array_slice(array_map(static fn($r) => (array)$r, array_values($all)), 0, $samplelimit);
        }
        return ['key' => $key, 'severity' => $severity, 'count' => $count, 'samples' => $samples];
    }
}
