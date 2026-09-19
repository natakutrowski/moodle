<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\express;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\guest\CommerceCheckoutIdentityResolver;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutContext;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutLaunchResult;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutRuntimeFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\commerce\legal\document\CommerceLegalDocumentResolver;
use local_subscriptions\url\UrlFactory;

/**
 * Eligibility and provider launch service for authenticated one-click checkout.
 *
 * Express checkout is intentionally conservative. A direct purchase only skips
 * the review page when the customer is authenticated, the cart contains one
 * quantity-one product and the current legal documents were already accepted.
 */
final class CommerceCheckoutExpressService {
    private const PREF_LEGAL_FINGERPRINT = 'local_subscriptions_checkout_legal_fingerprint';
    private const PREF_LEGAL_ACCEPTED_AT = 'local_subscriptions_checkout_legal_accepted_at';

    /** Records acceptance of the currently resolved legal documents. */
    public function record_legal_acceptance(int $userid, ?int $acceptedat = null): int {
        $acceptedat ??= time();
        if ($userid > 0) {
            set_user_preference(self::PREF_LEGAL_FINGERPRINT, $this->legal_fingerprint(), $userid);
            set_user_preference(self::PREF_LEGAL_ACCEPTED_AT, (string)$acceptedat, $userid);
        }
        return $acceptedat;
    }

    /** Whether the user has accepted the exact legal document configuration. */
    public function has_current_legal_acceptance(int $userid): bool {
        if ($userid <= 0) {
            return false;
        }

        return hash_equals(
            $this->legal_fingerprint(),
            (string)get_user_preferences(self::PREF_LEGAL_FINGERPRINT, '', $userid)
        );
    }

    /**
     * Returns an empty string when express checkout is eligible, otherwise a stable reason code.
     */
    public function ineligibility_reason(int $userid, string $currency): string {
        $reason = $this->account_ineligibility_reason($userid, $currency);
        if ($reason !== '') {
            return $reason;
        }

        $currency = strtoupper(trim($currency));
        $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
        $context = $this->build_context($identity->userid, $currency, $this->select_provider($currency), []);
        $customer = new CommerceCustomer(
            $identity->userid,
            $identity->email,
            $identity->firstname,
            $identity->lastname,
            ['language' => current_language(), 'checkout_mode' => 'express']
        );
        $snapshot = CommerceCheckoutRuntimeFactory::create()->prepare($context, $customer);
        $items = $snapshot->get_summary()->get_cart_snapshot()->get_items();

        if (count($items) !== 1) {
            return 'single_product_required';
        }

        $item = reset($items);
        if ($item === false || $item->get_item()->get_quantity() !== 1) {
            return 'single_quantity_required';
        }

        $operation = strtolower(trim((string)($item->get_item()->get_metadata()['operation'] ?? '')));
        if ($operation !== '') {
            return 'special_operation';
        }

        return '';
    }


    /**
     * Checks a direct purchase candidate before it has been materialised in the cart.
     *
     * This is used by the provider modal eligibility endpoint. It avoids inspecting
     * the current cart, which is still empty on the very first Buy now click.
     */
    public function direct_purchase_ineligibility_reason(
        int $userid,
        string $currency,
        string $sku,
        int $priceid,
        int $quantity,
        string $operation = ''
    ): string {
        $reason = $this->account_ineligibility_reason($userid, $currency);
        if ($reason !== '') {
            return $reason;
        }
        if (trim($sku) === '' || $priceid <= 0) {
            return 'product_required';
        }
        if ($quantity !== 1) {
            return 'single_quantity_required';
        }
        if (trim($operation) !== '') {
            return 'special_operation';
        }

        return '';
    }

    /** Checks account, legal acceptance, currency and stable identity. */
    private function account_ineligibility_reason(int $userid, string $currency): string {
        if (!isloggedin() || isguestuser() || $userid <= 0) {
            return 'authentication_required';
        }
        if (!$this->has_current_legal_acceptance($userid)) {
            return 'legal_acceptance_required';
        }

        $currency = strtoupper(trim($currency));
        if (!(new CommerceCurrencyRegistry())->is_enabled($currency)) {
            return 'unsupported_currency';
        }
        if (!$this->has_available_provider_for_currency($currency)) {
            return 'unsupported_currency';
        }

        $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
        if ($identity->userid !== $userid || $identity->is_guest_checkout()) {
            return 'stable_account_required';
        }

        return '';
    }

    /** Launches an eligible direct checkout and returns the provider action. */
    public function launch(int $userid, string $currency, array $metadata = []): CommerceCheckoutLaunchResult {
        $reason = $this->ineligibility_reason($userid, $currency);
        if ($reason !== '') {
            throw new \domain_exception('Express checkout is not eligible: ' . $reason);
        }

        $currency = strtoupper(trim($currency));
        $provider = $this->select_provider($currency);
        $identity = CommerceCheckoutIdentityResolver::create()->resolve($currency);
        $context = $this->build_context($identity->userid, $currency, $provider, $metadata);
        $customer = new CommerceCustomer(
            $identity->userid,
            $identity->email,
            $identity->firstname,
            $identity->lastname,
            ['language' => current_language(), 'checkout_mode' => 'express']
        );

        return CommerceCheckoutRuntimeFactory::create()->launch($context, $customer);
    }

    private function build_context(int $userid, string $currency, string $provider, array $metadata): CommerceCheckoutContext {
        $returnurl = (new \moodle_url('/local/subscriptions/payment/return.php'))->out(false);
        $cancelurl = UrlFactory::cart(['currency' => $currency])->out(false);

        $acceptedat = (int)get_user_preferences(self::PREF_LEGAL_ACCEPTED_AT, '0', $userid);

        return new CommerceCheckoutContext(
            $userid,
            $currency,
            current_language(),
            $provider,
            $returnurl,
            $cancelurl,
            true,
            array_replace([
                'checkout_entrypoint' => 'cart_action.php',
                'checkout_phase' => 'J14C',
                'checkout_mode' => 'express',
                'purchase_flow' => CommercePurchaseFlow::DIRECT,
                'legal_acceptance' => [
                    'accepted' => true,
                    'accepted_at' => $acceptedat > 0 ? $acceptedat : time(),
                    'source' => 'express_preaccepted',
                ],
            ], $metadata)
        );
    }

    private function select_provider(string $currency): string {
        $currency = strtoupper(trim($currency));
        $available = array_values(array_filter(
            CommerceRuntimeFactory::create()->payment_providers()->all(),
            static fn($provider): bool => $provider->is_available()
                && $provider->get_capabilities()->supports_currency($currency)
        ));

        usort(
            $available,
            static fn($left, $right): int => $right->get_priority() <=> $left->get_priority()
        );

        if ($available !== []) {
            return $available[0]->get_key();
        }

        throw new \runtime_exception('No payment provider is available for express checkout currency ' . $currency . '.');
    }

    private function has_available_provider_for_currency(string $currency): bool {
        $currency = strtoupper(trim($currency));
        foreach (CommerceRuntimeFactory::create()->payment_providers()->all() as $provider) {
            if ($provider->is_available() && $provider->get_capabilities()->supports_currency($currency)) {
                return true;
            }
        }
        return false;
    }

    private function legal_fingerprint(): string {
        return (new CommerceLegalDocumentResolver())
            ->resolve(null, current_language())
            ->get_fingerprint();
    }
}
