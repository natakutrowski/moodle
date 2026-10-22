<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\currency\Currency;

/** Adds reusable Donation amount controls to Boutique and Storefront data. */
final class CommerceDonationStorefrontPresenter {
    public function __construct(
        private readonly CommerceDonationAmountPolicy $policy = new CommerceDonationAmountPolicy(),
        private readonly CommerceDonationIntentSession $intent = new CommerceDonationIntentSession()
    ) {
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function decorate(array $data, string $currency, ?CommerceCartSnapshot $snapshot = null): array {
        if (strtolower(trim((string)($data['producttype'] ?? ''))) !== 'donation') {
            return $data;
        }

        $sku = strtoupper(trim((string)($data['sku'] ?? '')));
        $currency = Currency::sanitize($currency);
        if ($sku === '' || $currency === '') {
            return $data;
        }

        $selectedpriceindex = null;
        $unitminor = 0;
        foreach (($data['prices'] ?? []) as $index => $price) {
            if (
                strtoupper(trim((string)($price['currency'] ?? ''))) !== $currency
                || (int)($price['id'] ?? 0) <= 0
            ) {
                continue;
            }
            $candidate = (int)($price['amountminor'] ?? 0);
            if ($candidate <= 0) {
                continue;
            }
            $selectedpriceindex = $index;
            $unitminor = $candidate;
            break;
        }
        if ($selectedpriceindex === null || $unitminor <= 0) {
            return $data;
        }

        $cartunits = null;
        if ($snapshot !== null) {
            foreach ($snapshot->get_items() as $calculateditem) {
                $item = $calculateditem->get_item();
                if ($item->get_product_sku() !== $sku) {
                    continue;
                }
                $metadata = $item->get_metadata();
                if (!array_key_exists(CommerceDonationAmountPolicy::METADATA_UNITS, $metadata)) {
                    continue;
                }
                try {
                    $cartunits = $this->policy->require_units(
                        $metadata[CommerceDonationAmountPolicy::METADATA_UNITS]
                    );
                } catch (\coding_exception) {
                    $cartunits = null;
                }
                break;
            }
        }

        $units = $this->intent->resolve($sku, $snapshot);
        $money = $this->policy->amount(
            \local_subscriptions\commerce\domain\value\CommerceMoney::from_minor($unitminor, $currency),
            $units
        );
        $amountminor = $money->get_amount_minor();
        $formatted = CommercePurchasePresentation::money($amountminor, $currency);

        $data['isdonation'] = true;
        $data['donationunitminor'] = $unitminor;
        $data['donationdefaultunits'] = $units;
        $data['donationmaxunits'] = $this->policy->max_units();
        $data['donationcurrency'] = $currency;
        $data['donationcurrencydigits'] = Currency::minor_unit_exponent($currency);
        $data['donationamountformatted'] = $formatted;
        $data['donationamountinputvalue'] = $money->get_amount_major_for_currency();
        $data['donationincart'] = $cartunits !== null;
        $data['donationintentendpoint'] = (new \moodle_url('/local/subscriptions/ajax/donation_intent.php'))->out(false);
        $data['donationpresets'] = array_map(
            fn(int $preset): array => [
                'units' => $preset,
                'formatted' => CommercePurchasePresentation::money($unitminor * $preset, $currency),
            ],
            $this->policy->preset_units()
        );
        $data['donationcontributelabel'] = get_string('commerce_donation_contribute', 'local_subscriptions');
        $data['donationupdatecartlabel'] = get_string('commerce_donation_update_cart', 'local_subscriptions');

        // On Donation surfaces the customer-facing amount is the selected
        // contribution, not the catalogue unit price used internally by B.2.
        if (isset($data['prices'][$selectedpriceindex]) && is_array($data['prices'][$selectedpriceindex])) {
            $data['prices'][$selectedpriceindex]['formatted'] = $formatted;
            $data['prices'][$selectedpriceindex]['donationunits'] = $units;
        }

        return $data;
    }
}
