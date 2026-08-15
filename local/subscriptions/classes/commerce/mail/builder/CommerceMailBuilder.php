<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared CampusFR Mail Builder vocabulary.
 *
 * N5.2 deliberately keeps the builder document HTML-first so existing campaign
 * copy can migrate without destructive conversion. Structural tags are a stable
 * contract consumed by specialised contexts (Personal Offer today; generic
 * marketing/transactional contexts in later N5 phases).
 */
final class CommerceMailBuilder {
    public const VERSION = 2;

    public const CTA_GOLD = 'gold';
    public const CTA_CAMPUS_PINK = 'campus_pink';
    public const CTA_LEGACY_BLUE = 'legacy_blue';

    /** @return string[] */
    public static function cta_variants(): array {
        return [
            self::CTA_GOLD,
            self::CTA_CAMPUS_PINK,
            self::CTA_LEGACY_BLUE,
        ];
    }

    /** @return array<int,array{tag:string,label:string,scope:string}> */
    public static function common_structural_tags(): array {
        return [
            [
                'tag' => '{{cta|gold}}Texte du bouton{{/cta}}',
                'label' => 'CTA Gold',
                'scope' => 'common',
            ],
            [
                'tag' => '{{cta|campus_pink}}Texte du bouton{{/cta}}',
                'label' => 'CTA Campus',
                'scope' => 'common',
            ],
            [
                'tag' => '{{cta|legacy_blue}}Texte du bouton{{/cta}}',
                'label' => 'CTA Legacy',
                'scope' => 'common',
            ],
        ];
    }

    /** @return array<int,array{tag:string,label:string,scope:string}> */
    public static function personal_offer_structural_tags(): array {
        return array_merge(self::common_structural_tags(), [
            ['tag' => '{{offer}}', 'label' => 'Offre', 'scope' => 'personal_offer'],
            ['tag' => '{{secondary_cta}}', 'label' => 'CTA secondaire', 'scope' => 'personal_offer'],
            ['tag' => '{{direct_pay}}', 'label' => 'Paiement direct', 'scope' => 'personal_offer'],
            ['tag' => '{{image}}', 'label' => 'Image', 'scope' => 'personal_offer'],
        ]);
    }

    /** @return string[] */
    public static function common_variables(): array {
        return ['firstname', 'fullname', 'email'];
    }

    /** @return string[] */
    /** @return string[] */
    public static function sales_followup_variables(): array {
        return [
            'firstname',
            'fullname',
            'email',
            'order_reference',
            'product_name',
            'order_total',
            'currency',
            'payment_provider',
            'payment_status',
            'checkout_url',
            'my_purchases_url',
            'support_email',
        ];
    }

    /** @return array<int,array{tag:string,label:string,scope:string}> */
    public static function sales_followup_structural_tags(): array {
        return [[
            'tag' => '{{resume_payment}}',
            'label' => 'Payment resume button when a safe provider link is available',
            'scope' => 'sales_followup',
        ]];
    }

    public static function transactional_variables(): array {
        return [
            'firstname',
            'fullname',
            'order_reference',
            'order_total',
            'order_url',
            'my_purchases_url',
            'my_courses_url',
            'digital_library_url',
            'support_email',
            'offer_url',
            'offer_product',
            'offer_expiry',
            'offer_price',
            'campaign_name',
        ];
    }

    public static function editorial_empty(string $value): bool {
        if (trim($value) === '') {
            return true;
        }
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        return trim($text) === '';
    }

    private function __construct() {}
}
