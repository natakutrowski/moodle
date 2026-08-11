<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\compatibility;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;
use local_subscriptions\url\UrlFactory;

/** Centralises compatibility redirects from historical Commerce URLs. */
final class CommerceLegacyUrlCompatibilityService {
    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * Redirects an historical payment result route when a Native purchase reference is supplied.
     * Legacy pid-only requests deliberately continue through their historical adapter.
     */
    public function redirect_native_result_if_present(string $result): void {
        $reference = $this->request_purchase_reference();
        if ($reference === '' || !$this->native_purchase_exists($reference)) {
            return;
        }

        $params = [
            'reference' => $reference,
            'result' => $this->normalise_result($result),
        ];
        $lang = strtolower(substr(optional_param('lang', '', PARAM_ALPHANUMEXT), 0, 2));
        if (in_array($lang, ['fr', 'en', 'ru'], true)) {
            $params['lang'] = $lang;
        }

        redirect(UrlFactory::order_result($params));
    }

    /** Returns the canonical Native storefront URL for an historical digital product. */
    public function digital_product_url(int $legacyproductid): ?\moodle_url {
        return (new CommerceLegacyStorefrontProductResolver($this->db))
            ->storefront_url('subscription_digital_product', $legacyproductid, false);
    }

    /** Durable compatibility policy used by certification and documentation. */
    public static function route_policy(): array {
        return [
            'payment_success.php' => 'legacy_adapter_with_native_result_redirect',
            'payment_cancel.php' => 'legacy_adapter_with_native_result_redirect',
            'payment_error.php' => 'legacy_adapter_with_native_result_redirect',
            'digital_success.php' => 'legacy_adapter_with_native_result_redirect',
            'digital_cancel.php' => 'legacy_adapter_with_native_result_redirect',
            'digital_product.php' => 'redirect_mapped_product_to_native_storefront',
            'subscribe.php' => 'storefront_rollout_redirect_with_legacy_fallback',
            'checkout.php' => 'legacy_adapter_retained_for_historical_operations',
        ];
    }

    private function request_purchase_reference(): string {
        foreach (['reference', 'purchase_reference', 'purchasereference'] as $name) {
            $value = trim((string)optional_param($name, '', PARAM_ALPHANUMEXT));
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private function native_purchase_exists(string $reference): bool {
        return $this->db->record_exists('local_subscriptions_commerce_purchase', [
            'reference' => $reference,
        ]);
    }

    private function normalise_result(string $result): string {
        $result = strtolower(trim($result));
        return match ($result) {
            'success', 'paid', 'completed' => 'success',
            'cancel', 'cancelled', 'canceled' => 'cancelled',
            'failure', 'failed', 'error' => 'failed',
            'pending', 'processing' => $result,
            default => 'unknown',
        };
    }
}
