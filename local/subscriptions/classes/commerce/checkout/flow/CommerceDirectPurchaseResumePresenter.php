<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\flow;

defined('MOODLE_INTERNAL') || die();

/**
 * Small presentation adapter for an interrupted Buy Now checkout.
 *
 * Direct Purchase intentionally stays outside the normal cart. When the
 * customer leaves checkout, this adapter exposes the durable session as an
 * explicit "resume payment" action on Commerce navigation surfaces.
 */
final class CommerceDirectPurchaseResumePresenter {
    /**
     * @return array{
     *   hasdirectpurchase:bool,
     *   directpurchaseresumeurl:string,
     *   directpurchaseresumetitle:string,
     *   directpurchaseresumetext:string,
     *   directpurchaseresumelabel:string,
     *   directpurchasecancelurl:string,
     *   directpurchasecancelkey:string,
     *   directpurchasecancelcurrency:string,
     *   directpurchasecancelsku:string,
     *   directpurchasecancelpriceid:int,
     *   directpurchasecancelstoredat:int,
     *   directpurchasecancelcartuuid:string,
     *   directpurchasecancellabel:string,
     *   directpurchasecancelhelp:string
     * }
     */
    public static function present(): array {
        $directpurchase = CommerceDirectPurchaseSession::current_any_currency();
        if ($directpurchase === null) {
            return [
                'hasdirectpurchase' => false,
                'directpurchaseresumeurl' => '',
                'directpurchaseresumetitle' => '',
                'directpurchaseresumetext' => '',
                'directpurchaseresumelabel' => '',
                'directpurchasecancelurl' => '',
                'directpurchasecancelkey' => '',
                'directpurchasecancelcurrency' => '',
                'directpurchasecancelsku' => '',
                'directpurchasecancelpriceid' => 0,
                'directpurchasecancelstoredat' => 0,
                'directpurchasecancelcartuuid' => '',
                'directpurchasecancellabel' => '',
                'directpurchasecancelhelp' => '',
            ];
        }

        $url = new \moodle_url(
            '/local/subscriptions/commerce_checkout.php',
            [
                'currency' => $directpurchase['currency'],
                'flow' => CommercePurchaseFlow::DIRECT,
            ]
        );

        return [
            'hasdirectpurchase' => true,
            'directpurchaseresumeurl' => $url->out(false),
            'directpurchaseresumetitle' => get_string(
                'commerce_direct_purchase_resume_title',
                'local_subscriptions'
            ),
            'directpurchaseresumetext' => get_string(
                'commerce_direct_purchase_resume_text',
                'local_subscriptions'
            ),
            'directpurchaseresumelabel' => get_string(
                'commerce_direct_purchase_resume_action',
                'local_subscriptions'
            ),
            'directpurchasecancelurl' => (new \moodle_url(
                '/local/subscriptions/direct_purchase_cancel.php'
            ))->out(false),
            'directpurchasecancelkey' => sesskey(),
            'directpurchasecancelcurrency' => $directpurchase['currency'],
            'directpurchasecancelsku' => $directpurchase['sku'],
            'directpurchasecancelpriceid' => $directpurchase['priceid'],
            'directpurchasecancelstoredat' => $directpurchase['storedat'],
            'directpurchasecancelcartuuid' => (string)($directpurchase['cartuuid'] ?? ''),
            'directpurchasecancellabel' => get_string(
                'commerce_direct_purchase_cancel_action',
                'local_subscriptions'
            ),
            'directpurchasecancelhelp' => get_string(
                'commerce_direct_purchase_cancel_help',
                'local_subscriptions'
            ),
        ];
    }
}
