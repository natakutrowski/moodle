<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\currency\CommerceCurrencyLabelFormatter;

/** Entry/identity boundary between a signed Personal Offer URL and Native checkout. */
final class CommercePersonalOfferCheckoutService {
    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /** @return array{offer:CommercePersonalOffer,sku:string,priceid:int,currency:string} */
    public function validate_entry(string $token, string $currency, ?int $userid, ?string $email): array {
        $validation = CommercePersonalOfferFactory::create($this->db)->validate_token($token);
        if (!$validation->is_valid() || $validation->get_offer() === null) {
            throw new \moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }
        $offer = $validation->get_offer();

        // M3H.7: a short-lived campaign test email is an admin certification aid.
        // If the tester opens it while authenticated as another Moodle account,
        // treat the signed test link exactly like an anonymous bearer link instead
        // of rejecting it for the tester's unrelated session identity.
        $metadata = $offer->get_metadata();
        if (!empty($metadata['campaignemailtest'])) {
            $userid = null;
            $email = null;
        }
        $this->assert_identity($offer, $userid, $email, true);

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $prices = new CommerceProductPriceRepository($this->db, $hydrator, $products);
        $product = $products->find_by_id($offer->get_target_product_id());
        if ($product === null || !$product->is_available_at(time())) {
            throw new \moodle_exception('commerce_personal_offer_target_unavailable', 'local_subscriptions');
        }
        $price = null;
        foreach ($prices->find_by_product_sku($product->get_sku(), true) as $candidate) {
            if ($candidate->get_currency() === strtoupper($currency)) {
                $price = $candidate;
                if ($candidate->get_provider() === null) {
                    break;
                }
            }
        }
        if ($price === null || $price->get_id() === null) {
            throw new \moodle_exception('commerce_personal_offer_currency_unavailable', 'local_subscriptions');
        }

        // The real Personal Offer terms are always resolved server-side for the requested currency.
        CommercePersonalOfferCheckoutPricingService::create($this->db)->resolve_unit_minor(
            $offer->get_offer_uuid(),
            $product->get_sku(),
            $currency,
            $price->get_amount_minor()
        );

        return [
            'offer' => $offer,
            'sku' => $product->get_sku(),
            'priceid' => $price->get_id(),
            'currency' => strtoupper($currency),
        ];
    }

    /** @return array{offer:CommercePersonalOffer,sku:string,priceid:int,currency:string} */
    public function prepare(string $token, string $currency, ?int $userid, ?string $email, string $language): array {
        $prepared = $this->validate_entry($token, $currency, $userid, $email);
        $offer = $prepared['offer'];

        $customerid = $userid ?? 0;
        $cart = CommerceCartRuntimeFactory::create();
        $cart->clear_cart($customerid, $prepared['currency']);
        $result = $cart->add_product(
            $customerid,
            $prepared['currency'],
            $language,
            $prepared['sku'],
            $prepared['priceid'],
            1,
            [
                'operation' => 'personaloffer',
                'personal_offer_uuid' => $offer->get_offer_uuid(),
                'personal_offer_campaign' => (string)($offer->get_campaign_key() ?? ''),
            ]
        );
        if (!$result->has_changed()) {
            // A Personal Offer entry must be replay-safe. A previous request may have
            // prepared the cart successfully and then failed later while rendering the
            // checkout (theme/cache/navigation, browser retry, etc.). In that case,
            // accept the existing identical Personal Offer line instead of presenting
            // the signed link as invalid.
            $existing = CommerceCartRuntimeFactory::create()->open($customerid, $prepared['currency']);
            $matched = false;
            foreach ($existing->get_items() as $item) {
                $metadata = $item->get_metadata();
                if (strtolower((string)($metadata['operation'] ?? '')) !== 'personaloffer') {
                    continue;
                }
                if (strtolower((string)($metadata['personal_offer_uuid'] ?? '')) !== $offer->get_offer_uuid()) {
                    continue;
                }
                $matched = true;
                break;
            }
            if (!$matched) {
                throw new \moodle_exception('commerce_personal_offer_cart_failed', 'local_subscriptions');
            }
        }
        return $prepared;
    }


    public function choose_currency(string $token, string $fallback): string {
        $validation = CommercePersonalOfferFactory::create($this->db)->validate_token($token);
        if (!$validation->is_valid() || $validation->get_offer() === null) {
            throw new \moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
        }
        $offer = $validation->get_offer();
        $available = array_column($this->get_available_currencies($offer), 'currency');
        if ($available === []) {
            throw new \moodle_exception('commerce_personal_offer_currency_unavailable', 'local_subscriptions');
        }

        if ($offer->get_source_purchase_id()) {
            $purchasecurrency = strtoupper((string)$this->db->get_field(
                'local_subscriptions_commerce_purchase',
                'currency',
                ['id' => $offer->get_source_purchase_id()],
                IGNORE_MISSING
            ));
            if ($purchasecurrency !== '' && in_array($purchasecurrency, $available, true)) {
                return $purchasecurrency;
            }
        }

        if (count($available) === 1) {
            return reset($available);
        }

        $fallback = strtoupper(trim($fallback));
        if (in_array($fallback, $available, true)) {
            return $fallback;
        }
        return reset($available);
    }

    public function get_cart_offer(int $customerid, string $currency): ?CommercePersonalOffer {
        $cart = CommerceCartRuntimeFactory::create()->open($customerid, strtoupper($currency));
        foreach ($cart->get_items() as $item) {
            $metadata = $item->get_metadata();
            if (strtolower((string)($metadata['operation'] ?? '')) !== 'personaloffer') {
                continue;
            }
            $uuid = strtolower(trim((string)($metadata['personal_offer_uuid'] ?? '')));
            if ($uuid === '') {
                continue;
            }
            return (new \local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository($this->db))
                ->get_by_uuid($uuid);
        }
        return null;
    }

    /** @return array{email:string,firstname:string,lastname:string,userid:?int} */
    public function get_beneficiary_identity(CommercePersonalOffer $offer): array {
        $email = $offer->get_beneficiary_email();
        $firstname = '';
        $lastname = '';
        $userid = $offer->get_beneficiary_user_id();

        if ($userid !== null && $userid > 0) {
            $user = $this->db->get_record('user', ['id' => $userid, 'deleted' => 0], 'id,firstname,lastname,email', IGNORE_MISSING);
            if ($user) {
                $firstname = trim((string)$user->firstname);
                $lastname = trim((string)$user->lastname);
            }
        }

        if (($firstname === '' || $lastname === '') && $offer->get_source_purchase_id()) {
            $purchase = $this->db->get_record(
                'local_subscriptions_commerce_purchase',
                ['id' => $offer->get_source_purchase_id()],
                'id,customerjson',
                IGNORE_MISSING
            );
            if ($purchase) {
                $customer = json_decode((string)$purchase->customerjson, true);
                if (is_array($customer)) {
                    $firstname = $firstname !== '' ? $firstname : trim((string)($customer['firstname'] ?? $customer['first_name'] ?? ''));
                    $lastname = $lastname !== '' ? $lastname : trim((string)($customer['lastname'] ?? $customer['last_name'] ?? ''));
                }
            }
        }

        if ($firstname === '' || $lastname === '') {
            $legacy = $this->db->get_record_sql(
                "SELECT firstname, lastname
                   FROM {subscription_digital_payment_request}
                  WHERE " . $this->db->sql_equal('email', ':email', false, false) . "
                    AND status IN (:paid,:completed,:succeeded)
               ORDER BY COALESCE(payment_date,creation_date) DESC, id DESC",
                [
                    'email' => $email,
                    'paid' => 'paid',
                    'completed' => 'completed',
                    'succeeded' => 'succeeded',
                ],
                IGNORE_MULTIPLE
            );
            if ($legacy) {
                $firstname = $firstname !== '' ? $firstname : trim((string)$legacy->firstname);
                $lastname = $lastname !== '' ? $lastname : trim((string)$legacy->lastname);
            }
        }

        return [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'userid' => $userid,
        ];
    }

    /** @return array<int,array{currency:string,label:string}> */
    public function get_available_currencies(CommercePersonalOffer $offer): array {
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $prices = new CommerceProductPriceRepository($this->db, $hydrator, $products);
        $product = $products->find_by_id($offer->get_target_product_id());
        if ($product === null) {
            return [];
        }

        $available = [];
        foreach ($prices->find_by_product_sku($product->get_sku(), true) as $price) {
            $currency = strtoupper($price->get_currency());
            if (!in_array($currency, ['EUR', 'RUB'], true) || isset($available[$currency])) {
                continue;
            }
            try {
                CommercePersonalOfferCheckoutPricingService::create($this->db)->resolve_unit_minor(
                    $offer->get_offer_uuid(),
                    $product->get_sku(),
                    $currency,
                    $price->get_amount_minor()
                );
            } catch (\Throwable) {
                continue;
            }
            $available[$currency] = [
                'currency' => $currency,
                'label' => CommerceCurrencyLabelFormatter::format($currency),
            ];
        }
        return array_values($available);
    }

    public function assert_checkout_identity(int $customerid, string $currency, ?int $userid, string $email): void {
        $cart = CommerceCartRuntimeFactory::create()->open($customerid, $currency);
        foreach ($cart->get_items() as $item) {
            $metadata = $item->get_metadata();
            if (strtolower((string)($metadata['operation'] ?? '')) !== 'personaloffer') { continue; }
            $uuid = strtolower(trim((string)($metadata['personal_offer_uuid'] ?? '')));
            if ($uuid === '') { throw new \moodle_exception('commerce_personal_offer_identity_mismatch', 'local_subscriptions'); }
            $offer = (new \local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository($this->db))->get_by_uuid($uuid);
            if ($offer === null || !$offer->is_available_at(time())) {
                throw new \moodle_exception('commerce_personal_offer_not_redeemable', 'local_subscriptions');
            }
            $this->assert_identity($offer, $userid, $email, false);
        }
    }

    private function assert_identity(CommercePersonalOffer $offer, ?int $userid, ?string $email, bool $allowanonymous): void {
        $email = strtolower(trim((string)$email));
        if (($userid === null || $userid <= 0) && $email === '') {
            if ($allowanonymous) { return; }
            throw new \moodle_exception('commerce_personal_offer_identity_mismatch', 'local_subscriptions');
        }
        $emailmatch = $email !== '' && hash_equals($offer->get_beneficiary_email(), $email);
        $usermatch = $userid !== null && $userid > 0 && $offer->get_beneficiary_user_id() !== null
            && $userid === $offer->get_beneficiary_user_id();
        if (!$emailmatch && !$usermatch) {
            if ($allowanonymous && $email !== '') {
                throw new CommercePersonalOfferIdentityConflictException(
                    $offer->get_beneficiary_email(),
                    $email
                );
            }
            throw new \moodle_exception('commerce_personal_offer_identity_mismatch', 'local_subscriptions');
        }
    }
}
