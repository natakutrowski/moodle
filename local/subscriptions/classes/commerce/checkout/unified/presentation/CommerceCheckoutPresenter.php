<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSnapshot;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;

/** Maps a frozen unified checkout snapshot to public-safe Mustache data. */
final class CommerceCheckoutPresenter {
    /**
     * @param CommercePaymentProvider[] $providers
     */
    public static function present(
        CommerceCheckoutSnapshot $snapshot,
        array $providers,
        string $selectedprovider,
        string $language
    ): array {
        $summary = $snapshot->get_summary();
        $cartdata = CommerceCartPresenter::present($summary->get_cart_snapshot(), $language);
        $currency = $summary->get_currency();
        $paymentrequest = $snapshot->get_payment_request();
        $providerdata = [];

        foreach ($providers as $provider) {
            if (!$provider instanceof CommercePaymentProvider) {
                continue;
            }

            $key = $provider->get_key();
            $available = $provider->is_available() && $provider->supports($paymentrequest);
            $providerdata[] = [
                'key' => $key,
                'label' => get_string('commerce_checkout_provider_' . $key, 'local_subscriptions'),
                'description' => get_string('commerce_checkout_provider_' . $key . '_desc', 'local_subscriptions'),
                'available' => $available,
                'selected' => $available && $key === $selectedprovider,
                'iconurl' => (new \moodle_url('/local/subscriptions/pix/email/' . $key . '.png'))->out(false),
            ];
        }

        return [
            'items' => $cartdata['items'],
            'hasitems' => $cartdata['hasitems'],
            'promotionadjustments' => $cartdata['promotionadjustments'],
            'haspromotionadjustments' => $cartdata['haspromotionadjustments'],
            'subtotalformatted' => $cartdata['subtotalformatted'],
            'listtotalformatted' => $cartdata['listtotalformatted'],
            'hasproductpromotiontotal' =>
                $cartdata['hasproductpromotiontotal'],
            'productpromotiontotalformatted' =>
                $cartdata['productpromotiontotalformatted'],
            'hastrialdiscounttotal' =>
                $cartdata['hastrialdiscounttotal'] ?? false,
            'trialdiscounttotalformatted' =>
                $cartdata['trialdiscounttotalformatted'] ?? '',
            'hasupgradecredittotal' =>
                $cartdata['hasupgradecredittotal'] ?? false,
            'upgradecredittotalformatted' =>
                $cartdata['upgradecredittotalformatted'] ?? '',
            'hastotalreductions' => $cartdata['hastotalreductions'],
            'totalreductionsformatted' =>
                $cartdata['totalreductionsformatted'],
            'totalformatted' => CommercePurchasePresentation::money(
                $summary->get_total_minor(),
                $currency
            ),
            'currency' => $currency,
            'providers' => $providerdata,
            'hasproviders' => count(array_filter($providerdata, static fn(array $provider): bool => $provider['available'])) > 0,
            'selectedprovider' => $selectedprovider,
            'valid' => $summary->is_valid(),
            'issues' => array_map(static fn($issue): array => [
                'code' => $issue->get_code(),
                'message' => get_string_manager()->string_exists(
                    'commerce_checkout_issue_' . $issue->get_code(),
                    'local_subscriptions'
                ) ? get_string('commerce_checkout_issue_' . $issue->get_code(), 'local_subscriptions')
                    : get_string('commerce_checkout_issue_generic', 'local_subscriptions'),
            ], $summary->get_validation()->get_issues()),
        ];
    }
}
