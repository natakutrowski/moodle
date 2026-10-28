<?php

/**
 * Server-side promo application on Commerce Checkout (Cart and isolated Buy Now).
 * POST/Redirect/GET: no price or discount is accepted from the browser.
 */
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartCustomerResolver;

\local_subscriptions\subscription_config::guard_public_access();
require_sesskey();

$currency = (new CommerceCurrencyRegistry())->require_enabled(
    strtoupper(required_param('currency', PARAM_ALPHA))
);
$flow = CommercePurchaseFlow::normalise(optional_param('flow', CommercePurchaseFlow::CART, PARAM_ALPHA));
$action = required_param('action', PARAM_ALPHA);
$code = strtoupper(trim(optional_param('promotioncode', '', PARAM_RAW_TRIMMED)));

// Rebuild a local checkout URL, preserving showroom attribution and the payment
// choice but refusing an arbitrary return URL supplied by the request.
$parameters = ['currency' => $currency, 'flow' => $flow];
$parameters['paymentmethod'] = strtolower(optional_param('paymentmethod', '', PARAM_ALPHANUMEXT));
$parameters['source'] = strtolower(optional_param('source', '', PARAM_ALPHANUMEXT));
$parameters['showroom'] = strtolower(optional_param('showroom', '', PARAM_ALPHANUMEXT));
$parameters['showroomoffer'] = strtolower(optional_param('showroomoffer', '', PARAM_ALPHANUMEXT));
$parameters['originreturn'] = optional_param('originreturn', '', PARAM_LOCALURL);
$parameters = array_filter($parameters, static fn($value) => $value !== '');

if ($action === 'applypromo' && \core_text::strlen($code) > 64) {
    $parameters['promonotice'] = 'promotion_code_too_long';
    redirect(new \moodle_url('/local/subscriptions/commerce_checkout.php', $parameters));
}

$notice = '';
try {
    if (!in_array($action, ['applypromo', 'removepromo'], true)) {
        throw new \moodle_exception('invalidparameter');
    }

    $customerid = CommerceGuestCartCustomerResolver::create()->resolve($currency);
    $carts = CommerceCartRuntimeFactory::create();

    if (CommercePurchaseFlow::is_direct($flow)) {
        $purchase = CommerceDirectPurchaseSession::current($currency);
        if ($purchase === null) {
            throw new \moodle_exception('commerce_cart_message_item_not_found', 'local_subscriptions');
        }
        if ($action === 'removepromo') {
            CommerceDirectPurchaseSession::set_promotion_code($currency, null);
            $notice = 'promotion_removed';
        } else if ($code === '') {
            $notice = 'promotion_code_required';
        } else if ($code === ($purchase['promotioncode'] ?? '')) {
            $notice = 'promotion_already_applied';
        } else {
            $rejection = $carts->validate_direct_promotion_code(
                $customerid,
                $purchase,
                current_language(),
                $code
            );
            if ($rejection !== null) {
                $notice = $rejection->get_code();
            } else {
                CommerceDirectPurchaseSession::set_promotion_code($currency, $code);
                $notice = 'promotion_code_saved';
            }
        }

        // Guest login may regenerate Moodle's browser session. Preserve the
        // accepted code in the existing durable Guest Checkout recovery state.
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        if ($token !== '') {
            $repository = new \local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository($DB);
            $guestsession = $repository->find_by_token($token);
            if ($guestsession !== null && !$guestsession->is_expired()
                    && $guestsession->get_currency() === $currency) {
                $metadata = $guestsession->get_metadata();
                $savedpurchase = $metadata['direct_purchase'] ?? null;
                if (is_array($savedpurchase)
                        && ($savedpurchase['cartuuid'] ?? null) === ($purchase['cartuuid'] ?? null)
                        && ($savedpurchase['sku'] ?? '') === $purchase['sku']) {
                    $metadata['direct_purchase'] = CommerceDirectPurchaseSession::current($currency);
                    $repository->transition($guestsession, $guestsession->get_status(), [
                        'metadatajson' => $metadata,
                    ]);
                }
            }
        }
    } else {
        $result = $action === 'applypromo'
            ? $carts->apply_promotion_code($customerid, $currency, $code)
            : $carts->remove_promotion_code($customerid, $currency);
        $messages = $result->get_messages();
        $notice = $messages !== []
            ? $messages[0]->get_code()
            : '';
        // Same provisional-user cart synchronization as cart_action.php.
        (new \local_subscriptions\commerce\checkout\guest\CommerceGuestCartCustomerResolver(
            new \local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository($DB)
        ))->synchronize($customerid, $currency, $result->get_cart());
    }
} catch (\Throwable $exception) {
    // Never publish internal exception details or a rejected code in the URL.
    if ($exception instanceof \moodle_exception) {
        throw $exception;
    }
    error_log('[local_subscriptions][checkout_promotion] ' . $exception);
    $notice = 'promotion_checkout_error';
}

if ($notice !== '') {
    $parameters['promonotice'] = $notice;
}
redirect(new \moodle_url('/local/subscriptions/commerce_checkout.php', $parameters));
