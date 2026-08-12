<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignEmailService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;

/** Revalidates a Personal Offer session proof for Showroom/Storefront/cart use. */
final class CommercePersonalOfferShoppingContextService {
    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * Returns currencies supported by an active Personal Offer for this SKU.
     *
     * Null means no Personal Offer context applies to this SKU. An empty array means
     * a context did apply but became invalid during this request; the stale session is
     * cleared so callers such as cart_action.php can fail closed instead of purchasing
     * at the public price from a stale Personal Offer page.
     *
     * @return array<int,string>|null
     */
    public function available_currencies(string $sku): ?array {
        global $USER, $SESSION;

        $context = $this->raw_context();
        $sku = strtoupper(trim($sku));
        if ($context === null || strtoupper((string)($context['sku'] ?? '')) !== $sku) {
            return null;
        }

        $token = trim((string)($SESSION->local_subscriptions_personal_offer_token ?? ''));
        if ($token === '') {
            return $this->invalidate();
        }

        try {
            $validation = CommercePersonalOfferFactory::create($this->db)->validate_token($token);
            if (!$validation->is_valid() || $validation->get_offer() === null) {
                return $this->invalidate();
            }
            $offer = $validation->get_offer();
            if (!$this->context_matches_offer($context, $offer)) {
                return $this->invalidate();
            }

            $checkout = CommercePersonalOfferCheckoutService::create($this->db);
            $available = array_values(array_unique(array_map(
                'strtoupper',
                array_column($checkout->get_available_currencies($offer), 'currency')
            )));
            if ($available === []) {
                return $this->invalidate();
            }

            // Revalidate identity, product availability and pricing using a real supported currency.
            $userid = isloggedin() && !isguestuser() ? (int)$USER->id : null;
            $email = $userid !== null ? (string)$USER->email : null;
            $validated = $checkout->validate_entry($token, $available[0], $userid, $email);
            if (strtoupper((string)$validated['sku']) !== $sku) {
                return $this->invalidate();
            }

            // Revalidate Campaign -> current Published Showroom lifecycle as well.
            $destination = CommercePersonalOfferDestinationResolver::create($this->db)->resolve($offer);
            if (!$this->destination_matches_context($context, $destination)) {
                return $this->invalidate();
            }

            return $available;
        } catch (\Throwable) {
            return $this->invalidate();
        }
    }

    /**
     * @return array{
     * offeruuid:string,campaignkey:string,sku:string,currency:string,priceid:int,
     * offeramountminor:int,regularamountminor:int,offerformatted:string,regularformatted:string,
     * hasdiscount:bool,discountpercent:int,discountlabel:string,metadata:array<string,mixed>
     * }|null
     */
    public function resolve(string $sku, string $currency, ?string $requiredshowroomkey = null): ?array {
        global $USER, $SESSION;

        $context = $this->raw_context();
        if ($context === null) {
            return null;
        }

        $sku = strtoupper(trim($sku));
        $currency = strtoupper(trim($currency));
        if ($sku === '' || $currency === '' || strtoupper((string)($context['sku'] ?? '')) !== $sku) {
            return null;
        }
        if ((string)($context['destination'] ?? '') !== CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM) {
            return null;
        }
        if ($requiredshowroomkey !== null
            && strtolower(trim((string)($context['showroomkey'] ?? ''))) !== strtolower(trim($requiredshowroomkey))) {
            return null;
        }

        $token = trim((string)($SESSION->local_subscriptions_personal_offer_token ?? ''));
        if ($token === '') {
            $this->invalidate();
            return null;
        }
        $userid = isloggedin() && !isguestuser() ? (int)$USER->id : null;
        $email = $userid !== null ? (string)$USER->email : null;

        try {
            $validated = CommercePersonalOfferCheckoutService::create($this->db)
                ->validate_entry($token, $currency, $userid, $email);
            $offer = $validated['offer'];
            if (!$this->context_matches_offer($context, $offer)
                || strtoupper((string)$validated['sku']) !== $sku) {
                $this->invalidate();
                return null;
            }

            $destination = CommercePersonalOfferDestinationResolver::create($this->db)->resolve($offer);
            if (!$this->destination_matches_context($context, $destination)) {
                $this->invalidate();
                return null;
            }
            if ($requiredshowroomkey !== null
                && strtolower((string)$destination['showroomkey']) !== strtolower(trim($requiredshowroomkey))) {
                return null;
            }

            $regularminor = (int)$this->db->get_field(
                'local_subs_commerce_prod_price',
                'amountminor',
                ['id' => (int)$validated['priceid'], 'active' => 1],
                MUST_EXIST
            );
            $offerminor = CommercePersonalOfferCheckoutPricingService::create($this->db)->resolve_unit_minor(
                $offer->get_offer_uuid(), $sku, $currency, $regularminor
            );
            $discountminor = max(0, $regularminor - $offerminor);
            $discountpercent = $regularminor > 0 ? (int)round(($discountminor * 100) / $regularminor) : 0;

            return [
                'offeruuid' => $offer->get_offer_uuid(),
                'campaignkey' => (string)($offer->get_campaign_key() ?? ''),
                'sku' => $sku,
                'currency' => $currency,
                'priceid' => (int)$validated['priceid'],
                'offeramountminor' => $offerminor,
                'regularamountminor' => $regularminor,
                'offerformatted' => CommercePurchasePresentation::money($offerminor, $currency),
                'regularformatted' => CommercePurchasePresentation::money($regularminor, $currency),
                'hasdiscount' => $offerminor < $regularminor,
                'discountpercent' => $discountpercent,
                'discountlabel' => $discountpercent > 0 ? '−' . $discountpercent . '%' : '',
                'metadata' => [
                    'operation' => 'personaloffer',
                    'personal_offer_uuid' => $offer->get_offer_uuid(),
                    'personal_offer_campaign' => (string)($offer->get_campaign_key() ?? ''),
                ],
            ];
        } catch (\Throwable) {
            $this->invalidate();
            return null;
        }
    }

    /** @param array<string,mixed> $context */
    private function context_matches_offer(array $context, \local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer $offer): bool {
        return hash_equals((string)($context['offeruuid'] ?? ''), $offer->get_offer_uuid())
            && (int)($context['targetproductid'] ?? 0) === $offer->get_target_product_id()
            && hash_equals(
                strtolower((string)($context['campaignkey'] ?? '')),
                strtolower((string)($offer->get_campaign_key() ?? ''))
            );
    }

    /**
     * @param array<string,mixed> $context
     * @param array{destination:string,campaignid:?int,showroomid:?int,showroomkey:?string} $destination
     */
    private function destination_matches_context(array $context, array $destination): bool {
        return ($destination['destination'] ?? '') === CommercePersonalOfferCampaignEmailService::DESTINATION_SHOWROOM
            && (int)($destination['campaignid'] ?? 0) === (int)($context['campaignid'] ?? 0)
            && (int)($destination['showroomid'] ?? 0) === (int)($context['showroomid'] ?? 0)
            && strtolower((string)($destination['showroomkey'] ?? '')) === strtolower((string)($context['showroomkey'] ?? ''));
    }

    /** @return array<int,string> */
    private function invalidate(): array {
        (new CommercePersonalOfferSessionService())->clear();
        return [];
    }

    /** @return array<string,mixed>|null */
    private function raw_context(): ?array {
        global $SESSION;
        $context = $SESSION->local_subscriptions_personal_offer_context ?? null;
        if (!is_array($context)) {
            return null;
        }
        if ((int)($context['version'] ?? 0) !== 1) {
            (new CommercePersonalOfferSessionService())->clear();
            return null;
        }
        return $context;
    }
}
