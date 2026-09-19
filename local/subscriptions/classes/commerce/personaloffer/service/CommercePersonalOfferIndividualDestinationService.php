<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;

/**
 * H13.1.6.2 — destination support for one-off Personal Offers.
 *
 * Individual offers do not need a synthetic Campaign merely to target a Showroom.
 */
final class CommercePersonalOfferIndividualDestinationService {
    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * @return array<int,array{name:string,showroomkey:string,productids:int[]}>
     */
    public function published_showrooms(): array {
        $productsbyid = [];
        foreach ($this->db->get_records(
            'local_subs_commerce_product',
            [],
            '',
            'id,sku'
        ) as $product) {
            $productsbyid[strtoupper(trim((string)$product->sku))] =
                (int)$product->id;
        }

        $result = [];
        $showrooms = $this->db->get_records(
            'local_subs_showroom',
            ['status' => CommerceShowroomStatus::PUBLISHED],
            'name ASC'
        );

        foreach ($showrooms as $showroom) {
            if (!$this->db->record_exists(
                'local_subs_showroom_block',
                [
                    'showroomid' => (int)$showroom->id,
                    'enabled' => 1,
                ]
            )) {
                continue;
            }

            $products = json_decode((string)$showroom->productsjson, true);
            if (!is_array($products)) {
                continue;
            }

            $productids = [];
            foreach (array_values($products) as $sku) {
                $key = strtoupper(trim((string)$sku));
                if (isset($productsbyid[$key])) {
                    $productids[] = $productsbyid[$key];
                }
            }
            $productids = array_values(array_unique($productids));
            if ($productids === []) {
                continue;
            }

            $result[(int)$showroom->id] = [
                'name' => (string)$showroom->name,
                'showroomkey' => (string)$showroom->showroomkey,
                'productids' => $productids,
            ];
        }

        return $result;
    }

    /**
     * @return array{destination:string,showroomid:?int,showroomkey:?string}
     */
    public function validate(
        string $destination,
        ?int $showroomid,
        int $targetproductid
    ): array {
        $destination = strtolower(trim($destination));

        if ($destination === '' || $destination === CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT) {
            return [
                'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
                'showroomid' => null,
                'showroomkey' => null,
            ];
        }

        if ($destination !== CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
            throw new \invalid_parameter_exception(
                'Unsupported individual Personal Offer destination.'
            );
        }

        $showrooms = $this->published_showrooms();
        $showroomid = (int)$showroomid;

        if (
            $showroomid <= 0
            || !isset($showrooms[$showroomid])
            || !in_array(
                $targetproductid,
                $showrooms[$showroomid]['productids'],
                true
            )
        ) {
            throw new \coding_exception(
                'Selected showroom is not published and compatible with the target product.'
            );
        }

        // Also require the current public/renderable definition now.
        (new CommerceShowroomPublishedDefinitionResolver($this->db))
            ->require($showrooms[$showroomid]['showroomkey']);

        return [
            'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM,
            'showroomid' => $showroomid,
            'showroomkey' => $showrooms[$showroomid]['showroomkey'],
        ];
    }

    /**
     * @return array{destination:string,campaignid:?int,showroomid:?int,showroomkey:?string,definition:mixed}
     */
    public function resolve_from_metadata(array $metadata): ?array {
        if (($metadata['campaignsource'] ?? '') !== 'crm_individual') {
            return null;
        }

        $destination = strtolower(trim((string)($metadata['individual_destination'] ?? '')));
        if ($destination === '' || $destination === CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT) {
            return [
                'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_CHECKOUT,
                'campaignid' => null,
                'showroomid' => null,
                'showroomkey' => null,
                'definition' => null,
            ];
        }

        if ($destination !== CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
            throw new \moodle_exception(
                'commerce_personal_offer_link_unavailable',
                'local_subscriptions'
            );
        }

        $showroomid = (int)($metadata['individual_showroom_id'] ?? 0);
        $showroomkey = trim((string)($metadata['individual_showroom_key'] ?? ''));
        if ($showroomid <= 0 || $showroomkey === '') {
            throw new \moodle_exception(
                'commerce_personal_offer_link_unavailable',
                'local_subscriptions'
            );
        }

        $repository = new CommerceShowroomCmsRepository($this->db);
        $showroom = $repository->get($showroomid);
        if (
            $showroom === null
            || (string)$showroom->status !== CommerceShowroomStatus::PUBLISHED
            || !hash_equals((string)$showroom->showroomkey, $showroomkey)
        ) {
            throw new \moodle_exception(
                'commerce_personal_offer_link_unavailable',
                'local_subscriptions'
            );
        }

        $definition = (new CommerceShowroomPublishedDefinitionResolver($this->db))
            ->require($showroomkey);

        return [
            'destination' => CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM,
            'campaignid' => null,
            'showroomid' => $showroomid,
            'showroomkey' => $showroomkey,
            'definition' => $definition,
        ];
    }
}
