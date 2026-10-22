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
     *   directpurchaseresumelabel:string
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
        ];
    }
}
