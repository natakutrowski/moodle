<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayText;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;

/** Queues Personal Offer emails into the existing Commerce outbox. */
final class CommercePersonalOfferMailService {
    private const CAMPAIGN = 'local_subs_commerce_offer_campaign';
    private const MEMBER = 'local_subs_commerce_offer_campaign_member';
    private const PRODUCT = 'local_subs_commerce_product';

    public function __construct(private readonly \moodle_database $db) {}
    public static function create(?\moodle_database $db = null): self { global $DB; return new self($db ?? $DB); }

    public function queue_campaign(int $campaignid): array {
        $campaign = $this->db->get_record(self::CAMPAIGN, ['id' => $campaignid], '*', MUST_EXIST);
        if (!in_array((string)$campaign->status, ['issued', 'closed'], true)) {
            throw new \coding_exception('Personal Offer emails require an issued campaign.');
        }
        $result = ['eligible'=>0,'queued'=>0,'existing'=>0,'skipped'=>0,'errors'=>0];
        foreach ($this->db->get_records(self::MEMBER, ['campaignid'=>$campaignid], 'id ASC') as $member) {
            if (empty($member->offerid) || !in_array((string)$member->eligibilitystatus, ['issued','replayed'], true)) { $result['skipped']++; continue; }
            $result['eligible']++;
            try {
                $before = $this->mail_record_for_campaign_member($campaignid, (int)$member->id);
                $this->queue_offer((int)$member->offerid, $campaignid, (int)$member->id);
                if ($before === null) { $result['queued']++; } else { $result['existing']++; }
            } catch (\Throwable $e) { $result['errors']++; }
        }
        return $result;
    }

    public function queue_offer(int $offerid, ?int $campaignid = null, ?int $memberid = null): \stdClass {
        $offer = (new MoodleCommercePersonalOfferRepository($this->db))->get_by_id($offerid)
            ?? throw new \moodle_exception('commerce_personal_offer_not_found', 'local_subscriptions');
        if ($offer->get_effective_status(time()) !== CommercePersonalOffer::STATUS_ISSUED) {
            throw new \coding_exception('Only an active Personal Offer can be emailed.');
        }
        $url = (new CommercePersonalOfferAdminService($this->db))->secure_url($offer);
        if ($url === null) { throw new \coding_exception('Personal Offer has no valid secure token.'); }
        $product = $this->db->get_record(self::PRODUCT, ['id'=>$offer->get_target_product_id()], 'id,name,sku', MUST_EXIST);
        $user = $offer->get_beneficiary_user_id() ? $this->db->get_record('user', ['id'=>$offer->get_beneficiary_user_id(),'deleted'=>0], 'id,firstname,lastname,email,lang', IGNORE_MISSING) : null;
        $campaign = $campaignid ? $this->db->get_record(self::CAMPAIGN, ['id'=>$campaignid], 'id,name,campaignkey', IGNORE_MISSING) : null;
        $name = $user ? trim((string)$user->firstname . ' ' . (string)$user->lastname) : '';
        if ($name === '' && $offer->get_source_purchase_id()) {
            $sourcepurchase = $this->db->get_record('local_subscriptions_commerce_purchase', ['id' => $offer->get_source_purchase_id()], 'customerjson', IGNORE_MISSING);
            if ($sourcepurchase) {
                $customer = json_decode((string)$sourcepurchase->customerjson, true);
                if (is_array($customer)) {
                    $name = trim((string)($customer['firstname'] ?? $customer['first_name'] ?? '') . ' ' . (string)($customer['lastname'] ?? $customer['last_name'] ?? ''));
                }
            }
        }
        $language = $this->resolve_language($offer, $user);
        $productname = $this->resolve_product_name((int)$product->id, (string)$product->name, $language);
        $coverurl = (string)(CommerceProductCoverService::create()
            ->resolve((int)$product->id, CommerceProductCoverContext::RESOURCES)
            ->get_url() ?? '');
        $mailimageurl = (new CommercePersonalOfferMailImageService())->url((int)$offer->get_id());

        $context = new CommerceMailContext([
            'customer' => ['firstname'=>$user ? (string)$user->firstname : '', 'fullname'=>$name],
            'purchase' => ['reference'=>'', 'totalformatted'=>''],
            'items' => [], 'payment' => [], 'links' => [],
            'personaloffer' => [
                'offeruuid'=>$offer->get_offer_uuid(), 'url'=>$url->out(false),
                'productname'=>$productname, 'productsku'=>(string)$product->sku,
                'coverurl'=>$coverurl, 'hascover'=>$coverurl !== '',
                'campaignname'=>$campaign ? (string)$campaign->name : (string)($offer->get_campaign_key() ?? ''),
                'pricing'=>$offer->get_terms()->get_data()['pricing'] ?? [],
                'validfrom'=>$offer->get_valid_from(),
                'expiresat'=>$offer->get_expires_at(),
                'campaignid'=>$campaignid, 'campaignmemberid'=>$memberid,
                'mailimageurl'=>$mailimageurl?->out(false) ?? '',
            ],
        ]);
        $key = $campaignid && $memberid
            ? 'personal-offer:campaign:' . $campaignid . ':member:' . $memberid
            : 'personal-offer:offer:' . $offerid;
        return CommerceMailRuntime::queue_service()->queue(new CommerceMailRequest(
            CommerceMailType::PERSONAL_OFFER,
            new CommerceMailRecipient($offer->get_beneficiary_email(), $name, $offer->get_beneficiary_user_id()),
            $context, $language, CommerceMailIdempotencyKey::normalise($key), null
        ));
    }

    public function mail_record_for_campaign_member(int $campaignid, int $memberid): ?\stdClass {
        return (new CommerceMailQueueRepository())->find_by_idempotency_key(
            CommerceMailIdempotencyKey::normalise('personal-offer:campaign:' . $campaignid . ':member:' . $memberid)
        );
    }

    public function retry_failed_campaign(int $campaignid): array {
        $campaign = $this->db->get_record(
            self::CAMPAIGN,
            ['id' => $campaignid],
            'id,status',
            MUST_EXIST
        );
        if (!in_array((string)$campaign->status, ['issued', 'closed'], true)) {
            throw new \coding_exception(
                'Personal Offer mail retries require an issued campaign.'
            );
        }

        $repository = new CommerceMailQueueRepository();
        $result = ['failed' => 0, 'requeued' => 0, 'skipped' => 0];

        foreach ($this->db->get_records(
            self::MEMBER,
            ['campaignid' => $campaignid],
            'id ASC'
        ) as $member) {
            if (
                empty($member->offerid)
                || !in_array(
                    (string)$member->eligibilitystatus,
                    ['issued', 'replayed'],
                    true
                )
            ) {
                continue;
            }

            $mail = $this->mail_record_for_campaign_member(
                $campaignid,
                (int)$member->id
            );
            if ($mail === null || (string)$mail->status !== 'failed') {
                $result['skipped']++;
                continue;
            }

            $result['failed']++;
            if ($repository->reset_failed((int)$mail->id)) {
                $result['requeued']++;
            }
        }

        return $result;
    }

    public function queue_missing_campaign(int $campaignid): array {
        return $this->queue_campaign($campaignid);
    }

    public function campaign_mail_summary(int $campaignid): array {
        $out = [
            'eligible' => 0,
            'notqueued' => 0,
            'queued' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'cancelled' => 0,
        ];

        foreach ($this->db->get_records(
            self::MEMBER,
            ['campaignid' => $campaignid],
            'id ASC'
        ) as $member) {
            if (
                empty($member->offerid)
                || !in_array(
                    (string)$member->eligibilitystatus,
                    ['issued', 'replayed'],
                    true
                )
            ) {
                continue;
            }

            $out['eligible']++;
            $mail = $this->mail_record_for_campaign_member(
                $campaignid,
                (int)$member->id
            );
            if ($mail === null) {
                $out['notqueued']++;
                continue;
            }

            $status = (string)$mail->status;
            if (isset($out[$status])) {
                $out[$status]++;
            } else {
                $out['queued']++;
            }
        }

        return $out;
    }

    private function resolve_product_name(int $productid, string $technicalname, string $language): string {
        $requested = strtolower(trim($language));
        $base = explode('_', str_replace('-', '_', $requested))[0];

        foreach (array_values(array_unique(array_filter([$requested, $base, 'fr', 'en', 'ru']))) as $candidate) {
            $name = trim((string)$this->db->get_field(
                'local_subs_commerce_prod_tr',
                'name',
                ['productid' => $productid, 'language' => $candidate],
                IGNORE_MISSING
            ));
            if ($name !== '') {
                return CommerceProductDisplayText::title($name);
            }
        }

        $record = $this->db->get_record_sql(
            "SELECT name FROM {local_subs_commerce_prod_tr}
              WHERE productid = :productid
           ORDER BY CASE language WHEN 'fr' THEN 0 WHEN 'en' THEN 1 WHEN 'ru' THEN 2 ELSE 3 END,
                    language ASC, id ASC",
            ['productid' => $productid],
            IGNORE_MISSING
        );
        $fallback = trim((string)($record->name ?? ''));
        return CommerceProductDisplayText::title($fallback !== '' ? $fallback : $technicalname);
    }

    private function resolve_language(CommercePersonalOffer $offer, ?\stdClass $user): string {
        $lang = $user ? strtolower(trim((string)$user->lang)) : '';
        if (in_array(substr($lang,0,2), ['fr','en','ru'], true)) return substr($lang,0,2);
        if ($offer->get_source_purchase_id()) {
            $purchase=$this->db->get_record('local_subscriptions_commerce_purchase',['id'=>$offer->get_source_purchase_id()],'customerjson,metadatajson,legacyfamily,legacyid',IGNORE_MISSING);
            if ($purchase) {
                foreach (['customerjson','metadatajson'] as $jsonfield) {
                    $data=json_decode((string)$purchase->{$jsonfield},true);
                    if (!is_array($data)) { continue; }
                    foreach (['lang','language','buyer_lang','buyer_language'] as $key) {
                        $value=strtolower(trim((string)($data[$key]??'')));
                        if(in_array(substr($value,0,2),['fr','en','ru'],true)) return substr($value,0,2);
                    }
                }
                if ((string)$purchase->legacyfamily === 'digital' && !empty($purchase->legacyid)) {
                    $legacy=$this->db->get_record('subscription_digital_payment_request',['id'=>(int)$purchase->legacyid],'buyer_lang',IGNORE_MISSING);
                    $value=$legacy ? strtolower(trim((string)$legacy->buyer_lang)) : '';
                    if(in_array(substr($value,0,2),['fr','en','ru'],true)) return substr($value,0,2);
                }
            }
        }
        return 'fr';
    }

}
