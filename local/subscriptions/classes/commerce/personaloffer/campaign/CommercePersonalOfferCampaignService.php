<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferService;

final class CommercePersonalOfferCampaignService {
    private const PURCHASE = 'local_subscriptions_commerce_purchase';
    private const ITEM = 'local_subscriptions_commerce_purchase_item';
    private const PAYMENT = 'local_subscriptions_commerce_payment';
    private const PRODUCT = 'local_subs_commerce_product';
    private const SUCCESS = ['paid', 'completed', 'captured', 'succeeded', 'success'];

    public function __construct(private readonly \moodle_database $db, private readonly CommercePersonalOfferService $offers) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self($db, CommercePersonalOfferFactory::create($db));
    }

    /** @return array{summary:array,rows:array} */
    public function run(CommercePersonalOfferCampaignRequest $request, bool $execute = false, int $limit = 0): array {
        $target = $this->db->get_record(self::PRODUCT, ['id' => $request->target_product_id()], 'id,sku,name,status', MUST_EXIST);
        $source = $this->db->get_record(self::PRODUCT, ['sku' => $request->source_product_sku()], 'id,sku,name,status', IGNORE_MISSING);
        if (!$source) {
            throw new \moodle_exception('commerce_personal_offer_campaign_source_missing', 'local_subscriptions');
        }

        $params = ['sourcesku' => (string)$source->sku];
        $successparts = [];
        foreach (self::SUCCESS as $i => $status) {
            $key = 'success' . $i; $params[$key] = $status; $successparts[] = ':' . $key;
        }
        $sql = 'SELECT DISTINCT p.* FROM {' . self::PURCHASE . '} p'
            . ' JOIN {' . self::ITEM . '} i ON i.purchaseid = p.id'
            . ' WHERE UPPER(i.itemreference) = :sourcesku'
            . ' AND EXISTS (SELECT 1 FROM {' . self::PAYMENT . '} pay WHERE pay.purchaseid = p.id'
            . ' AND pay.status IN (' . implode(',', $successparts) . '))'
            . ' ORDER BY p.id ASC';
        $records = array_values($this->db->get_records_sql($sql, $params, 0, $limit > 0 ? $limit : 0));

        $summary = ['scanned' => count($records), 'eligible' => 0, 'issued' => 0, 'replayed' => 0,
            'excluded_no_email' => 0, 'excluded_target_owned' => 0, 'errors' => 0];
        $rows = [];
        foreach ($records as $purchase) {
            $email = strtolower(trim((string)($purchase->customeremail ?? '')));
            $row = ['purchaseid' => (int)$purchase->id, 'reference' => (string)$purchase->reference,
                'email' => $email, 'userid' => empty($purchase->userid) ? null : (int)$purchase->userid,
                'status' => '', 'offeruuid' => '', 'url' => '', 'message' => ''];
            if (!validate_email($email)) {
                $summary['excluded_no_email']++; $row['status'] = 'EXCLUDED_NO_EMAIL'; $rows[] = $row; continue;
            }
            if ($request->exclude_already_owned_target() && $this->customer_has_target($purchase, (string)$target->sku)) {
                $summary['excluded_target_owned']++; $row['status'] = 'EXCLUDED_TARGET_OWNED'; $rows[] = $row; continue;
            }
            $summary['eligible']++;
            if (!$execute) {
                $row['status'] = 'ELIGIBLE'; $rows[] = $row; continue;
            }
            try {
                $issuancekey = 'campaign:' . $request->campaign_key() . ':purchase:' . (int)$purchase->id;
                $result = $this->offers->issue(new CommercePersonalOfferIssueRequest(
                    $issuancekey,
                    $request->target_product_id(),
                    $email,
                    $request->terms(),
                    $request->campaign_key(),
                    (int)$purchase->id,
                    empty($purchase->userid) ? null : (int)$purchase->userid,
                    $request->valid_from(),
                    $request->expires_at(),
                    ['sourceproductsku' => (string)$source->sku, 'campaignsource' => 'crm_bulk'],
                    $request->issued_by_user_id()
                ));
                $row['status'] = $result->is_replayed() ? 'REPLAYED' : 'ISSUED';
                $row['offeruuid'] = $result->get_offer()->get_offer_uuid();
                $row['url'] = (new \moodle_url('/local/subscriptions/offer.php', ['token' => $result->get_token()]))->out(false);
                $summary[$result->is_replayed() ? 'replayed' : 'issued']++;
            } catch (\Throwable $e) {
                $summary['errors']++; $row['status'] = 'ERROR'; $row['message'] = $e->getMessage();
            }
            $rows[] = $row;
        }
        return ['summary' => $summary, 'rows' => $rows];
    }

    private function customer_has_target(\stdClass $sourcepurchase, string $targetsku): bool {
        $identity = [];
        $params = ['targetsku' => strtoupper($targetsku)];
        if (!empty($sourcepurchase->userid)) { $identity[] = 'p.userid = :userid'; $params['userid'] = (int)$sourcepurchase->userid; }
        if (!empty($sourcepurchase->customeremail)) { $identity[] = $this->db->sql_equal('p.customeremail', ':email', false, false); $params['email'] = strtolower(trim((string)$sourcepurchase->customeremail)); }
        if (!$identity) { return false; }
        $successparts = [];
        foreach (self::SUCCESS as $i => $status) { $key = 'owned' . $i; $params[$key] = $status; $successparts[] = ':' . $key; }
        $sql = 'SELECT 1 FROM {' . self::PURCHASE . '} p JOIN {' . self::ITEM . '} i ON i.purchaseid=p.id'
            . ' WHERE (' . implode(' OR ', $identity) . ') AND UPPER(i.itemreference)=:targetsku'
            . ' AND EXISTS (SELECT 1 FROM {' . self::PAYMENT . '} pay WHERE pay.purchaseid=p.id AND pay.status IN (' . implode(',', $successparts) . '))';
        return $this->db->record_exists_sql($sql, $params);
    }
}
