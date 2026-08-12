<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;

/** Resolves the trusted destination of a validated Personal Offer entry. */
final class CommercePersonalOfferDestinationResolver {
    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * @return array{destination:string,campaignid:?int,showroomid:?int,showroomkey:?string,definition:?CommerceShowroomDefinition}
     */
    public function resolve(CommercePersonalOffer $offer): array {
        $checkout = [
            'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
            'campaignid' => null,
            'showroomid' => null,
            'showroomkey' => null,
            'definition' => null,
        ];

        $campaignkey = trim((string)$offer->get_campaign_key());
        if ($campaignkey === '') {
            return $checkout;
        }

        $campaign = $this->db->get_record(
            'local_subs_commerce_offer_campaign',
            ['campaignkey' => $campaignkey],
            'id,campaignkey,targetproductid,status',
            IGNORE_MISSING
        );
        if ($campaign === false) {
            // Legacy/non-CRM Personal Offers keep their historical direct-checkout behaviour.
            return $checkout;
        }

        $metadata = $offer->get_metadata();
        $iscampaignemailtest = !empty($metadata['campaignemailtest'])
            && (int)($metadata['campaignemailtestcampaignid'] ?? 0) === (int)$campaign->id;

        if (
            (int)$campaign->targetproductid !== $offer->get_target_product_id()
            || (
                !in_array((string)$campaign->status, ['issued', 'closed'], true)
                && !$iscampaignemailtest
            )
        ) {
            throw new \moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }

        $checkout['campaignid'] = (int)$campaign->id;
        $config = CommercePersonalOfferCampaignEmailService::create($this->db)
            ->resolve_destination((int)$campaign->id);
        if ($config['destination'] !== CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
            return $checkout;
        }

        $showroomid = (int)($config['showroomid'] ?? 0);
        if ($showroomid <= 0) {
            throw new \moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }

        $repository = new CommerceShowroomCmsRepository($this->db);
        $showroom = $repository->get($showroomid);
        if ($showroom === null) {
            throw new \moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }

        $sku = strtoupper(trim((string)$this->db->get_field(
            'local_subs_commerce_product',
            'sku',
            ['id' => $offer->get_target_product_id()],
            MUST_EXIST
        )));
        $products = json_decode((string)$showroom->productsjson, true);
        $showroomskus = is_array($products)
            ? array_map(static fn($value): string => strtoupper(trim((string)$value)), array_values($products))
            : [];
        if (!in_array($sku, $showroomskus, true)) {
            throw new \moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }

        // J16S public-state enforcement: only a currently Published, renderable Showroom is accepted.
        $definition = (new CommerceShowroomPublishedDefinitionResolver($this->db))
            ->require((string)$showroom->showroomkey);

        return [
            'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM,
            'campaignid' => (int)$campaign->id,
            'showroomid' => $showroomid,
            'showroomkey' => (string)$showroom->showroomkey,
            'definition' => $definition,
        ];
    }
}
