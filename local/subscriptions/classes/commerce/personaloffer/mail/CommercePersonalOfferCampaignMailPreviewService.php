<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailDispatcher;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\MoodleCommerceMailTransport;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignValidityService;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutPricingService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

/** Builds and sends safe pre-issue previews for Campaign Personal Offer emails. */
final class CommercePersonalOfferCampaignMailPreviewService {
    public function __construct(private readonly \moodle_database $db) {}
    public static function create(?\moodle_database $db = null): self { global $DB; return new self($db ?? $DB); }

    public function preview(int $campaignid, string $language, string $firstname = 'Natalia'): CommerceMailMessage {
        $request = $this->request($campaignid, $language, 'preview@example.invalid', $firstname);
        $registry = CommerceMailRuntime::template_registry();
        return (new CommerceMailDispatcher($registry, new MoodleCommerceMailTransport()))->preview($request);
    }

    public function send_test(int $campaignid, string $language, string $email, string $firstname = 'Natalia'): void {
        global $USER;

        if (!validate_email($email)) {
            throw new \coding_exception('Campaign email test recipient must be a valid email address.');
        }

        $campaign = $this->db->get_record(
            'local_subs_commerce_offer_campaign',
            ['id' => $campaignid],
            '*',
            MUST_EXIST
        );
        $terms = new CommercePersonalOfferTerms(
            json_decode((string)$campaign->termsjson, true, 512, JSON_THROW_ON_ERROR)
        );
        $now = time();
        $expiresat = $now + (2 * HOURSECS);
        $language = $this->normalise_language($language);
        $issuancekey = 'campaign-mail-test:' . $campaignid . ':' . $language . ':'
            . hash('sha256', strtolower(trim($email))) . ':' . $now . ':' . bin2hex(random_bytes(6));

        $issued = CommercePersonalOfferFactory::create($this->db)->issue(
            new CommercePersonalOfferIssueRequest(
                $issuancekey,
                (int)$campaign->targetproductid,
                strtolower(trim($email)),
                $terms,
                (string)$campaign->campaignkey,
                null,
                null,
                $now,
                $expiresat,
                [
                    'campaignemailtest' => true,
                    'campaignemailtestlanguage' => $language,
                    'campaignemailtestcampaignid' => $campaignid,
                ],
                isloggedin() && !isguestuser() ? (int)$USER->id : null
            )
        );

        $url = (new CommercePersonalOfferAdminService($this->db))->secure_url($issued->get_offer());
        if ($url === null) {
            throw new \coding_exception('Campaign email test offer has no valid secure URL.');
        }

        $request = $this->request(
            $campaignid,
            $language,
            $email,
            $firstname,
            $issued->get_offer()->get_offer_uuid(),
            $url->out(false),
            $now,
            $expiresat
        );
        $registry = CommerceMailRuntime::template_registry();
        $message = (new CommerceMailDispatcher($registry, new MoodleCommerceMailTransport()))->preview($request);
        (new MoodleCommerceMailTransport())->send($message);
    }

    private function request(
        int $campaignid,
        string $language,
        string $email,
        string $firstname,
        string $offeruuid = '',
        string $offerurl = '',
        ?int $validfrom = null,
        ?int $expiresat = null
    ): CommerceMailRequest {
        global $CFG;
        $campaign = $this->db->get_record('local_subs_commerce_offer_campaign', ['id' => $campaignid], '*', MUST_EXIST);
        $product = $this->db->get_record('local_subs_commerce_product', ['id' => (int)$campaign->targetproductid], 'id,sku,name', MUST_EXIST);
        $language = $this->normalise_language($language);
        $pricing = $this->pricing($campaign, (int)$product->id, (string)$product->sku, $language);
        $productname = $this->product_name((int)$product->id, (string)$product->name, $language);
        $coverurl = (string)(CommerceProductCoverService::create()
            ->resolve((int)$product->id, CommerceProductCoverContext::RESOURCES)
            ->get_url() ?? '');
        $previewurl = $offerurl !== ''
            ? $offerurl
            : (new \moodle_url(
                '/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php',
                ['id' => $campaignid]
            ))->out(false);
        $fullname = trim($firstname) !== '' ? trim($firstname) . ' Preview' : 'CampusFR Preview';

        $previewvalidity = $validfrom !== null && $expiresat !== null
            ? ['validfrom' => $validfrom, 'expiresat' => $expiresat]
            : (new CommercePersonalOfferCampaignValidityService())->resolve($campaign, time());
        $context = new CommerceMailContext([
            'customer' => ['firstname' => trim($firstname), 'fullname' => $fullname],
            'purchase' => ['reference' => '', 'totalformatted' => ''],
            'items' => [], 'payment' => [], 'links' => [],
            'personaloffer' => [
                'offeruuid' => $offeruuid,
                'url' => $previewurl,
                'productname' => $productname,
                'productsku' => (string)$product->sku,
                'coverurl' => $coverurl,
                'hascover' => $coverurl !== '',
                'campaignname' => (string)$campaign->name,
                'pricing' => json_decode((string)$campaign->termsjson, true, 512, JSON_THROW_ON_ERROR)['pricing'] ?? [],
                'validfrom' => $previewvalidity['validfrom'],
                'expiresat' => $previewvalidity['expiresat'],
                'validitymode' => (string)($campaign->validitymode ?? CommercePersonalOfferCampaignValidityService::MODE_LEGACY),
                'validitytimezone' => (string)($campaign->validitytimezone ?? CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE),
                'campaignid' => $campaignid,
                'campaignmemberid' => null,
                'mailimageurl' => '',
                'campaignpreview' => $offeruuid === '',
                'previewpricing' => $pricing,
            ],
        ]);

        return new CommerceMailRequest(
            CommerceMailType::PERSONAL_OFFER,
            new CommerceMailRecipient($email, $fullname, null),
            $context,
            $language,
            CommerceMailIdempotencyKey::normalise('personal-offer:campaign-preview:' . $campaignid . ':' . $language . ':' . hash('sha256', $email)),
            null
        );
    }

    /** @return array<string,mixed> */
    private function pricing(\stdClass $campaign, int $productid, string $sku, string $language): array {
        $terms = new CommercePersonalOfferTerms(json_decode((string)$campaign->termsjson, true, 512, JSON_THROW_ON_ERROR));
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $prices = new CommerceProductPriceRepository($this->db, $hydrator, $products);
        $catalogue = [];
        foreach ($prices->find_by_product_sku($sku, true) as $price) {
            $currency = strtoupper($price->get_currency());
            if (!in_array($currency, ['EUR', 'RUB'], true)) { continue; }
            if (!isset($catalogue[$currency]) || $price->get_provider() === null) { $catalogue[$currency] = $price; }
        }
        $available = [];
        $pricingservice = CommercePersonalOfferCheckoutPricingService::create($this->db);
        foreach ($catalogue as $currency => $price) {
            try {
                $offerminor = $pricingservice->resolve_from_terms($terms, $currency, $price->get_amount_minor());
            } catch (\Throwable) { continue; }
            $available[$currency] = ['regularminor' => $price->get_amount_minor(), 'offerminor' => $offerminor];
        }
        if ($available === []) {
            throw new \coding_exception('Campaign email preview requires an authoritative compatible catalogue price.');
        }
        $preferred = $language === 'ru' ? 'RUB' : 'EUR';
        return CommercePersonalOfferPricingPresentationBuilder::build($available, $preferred);
    }

    private function product_name(int $productid, string $fallback, string $language): string {
        $row = $this->db->get_record('local_subs_commerce_prod_tr', ['productid' => $productid, 'language' => $language], 'name', IGNORE_MISSING);
        if (!$row && $language !== 'fr') {
            $row = $this->db->get_record('local_subs_commerce_prod_tr', ['productid' => $productid, 'language' => 'fr'], 'name', IGNORE_MISSING);
        }
        return trim((string)($row->name ?? $fallback));
    }

    private function normalise_language(string $language): string {
        $base = strtolower(explode('_', str_replace('-', '_', trim($language)), 2)[0]);
        return in_array($base, ['fr','en','ru'], true) ? $base : 'fr';
    }
}
