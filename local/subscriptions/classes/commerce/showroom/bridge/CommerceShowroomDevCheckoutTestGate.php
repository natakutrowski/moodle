<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\Provider;
use local_subscriptions\payment\stripe\StripeConfiguration;

/** DEV-only, administrator-only, opt-in checkout test for draft Articles/Alf #8. */
final class CommerceShowroomDevCheckoutTestGate {
    public const SHOWROOM_ID = 8;
    public const KEY = 'articlesalfbridgepilot';
    public const TEMPLATE = 'local_subscriptions/showroom/bridge';
    public const DESIGN = 'articles_alf_reimport';
    public const DESIGN_VERSION = '0.4.11';
    public const DEV_ROOT = 'https://moodle-v2.campusfr.fr';

    public static function eligible(
        int $id,
        string $status,
        string $key,
        string $template,
        string $wwwroot,
        string $design,
        string $version
    ): bool {
        return $id === self::SHOWROOM_ID
            && $status === 'draft'
            && $key === self::KEY
            && $template === self::TEMPLATE
            && rtrim($wwwroot, '/') === self::DEV_ROOT
            && $design === self::DESIGN
            && $version === self::DESIGN_VERSION;
    }

    /** DEV-only customer-account pilot: never valid on PROD or another showroom. */
    public const PUBLIC_PILOT_SLUGS = [
        'slugfr' => 'articles-francais',
        'slugen' => 'french-articles',
        'slugru' => 'francuzskie-artikli',
    ];

    public static function eligible_published_pilot(
        int $id, string $status, string $key, string $template,
        string $wwwroot, string $design, string $version, array $slugs
    ): bool {
        return $id === self::SHOWROOM_ID
            && $status === 'published'
            && $key === self::KEY
            && $template === self::TEMPLATE
            && rtrim($wwwroot, '/') === self::DEV_ROOT
            && $design === self::DESIGN
            && $version === self::DESIGN_VERSION
            && $slugs === self::PUBLIC_PILOT_SLUGS;
    }

    /** Never enable a checkout test when any active gateway uses a LIVE profile. */
    public static function require_sandbox(): void {
        if (StripeConfiguration::active_profile() !== StripeConfiguration::TEST
            || strtolower((string)Provider::env(Provider::ALFA)) !== 'test'
            || !in_array(strtolower((string)Provider::env(Provider::PAYPAL)), ['test', 'sandbox'], true)) {
            throw new \coding_exception(
                'DEV Commerce test disabled: configure Stripe TEST, Alfa TEST and PayPal SANDBOX first.'
            );
        }
        $keys = StripeConfiguration::get(StripeConfiguration::TEST);
        if (trim((string)$keys['secret_key']) !== ''
            && !str_starts_with(trim((string)$keys['secret_key']), 'sk_test_')) {
            throw new \coding_exception('DEV Commerce test disabled: Stripe TEST secret key format invalid.');
        }
        if (trim((string)$keys['publishable_key']) !== ''
            && !str_starts_with(trim((string)$keys['publishable_key']), 'pk_test_')) {
            throw new \coding_exception('DEV Commerce test disabled: Stripe TEST publishable key format invalid.');
        }
        // No API credential or payment gateway configuration is modified here.
    }
}
