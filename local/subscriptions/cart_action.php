<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartCustomerResolver;
use local_subscriptions\commerce\cart\currency\CommerceCartCurrencySwitchService;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\currency\selection\CommerceCurrencyAvailabilityService;
use local_subscriptions\currency\Currency;
use local_subscriptions\commerce\showroom\CommerceShowroomTrackingContext;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferShoppingContextService;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPromotionJoinCartTransferService;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseCurrencySwitchService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;

\local_subscriptions\subscription_config::guard_public_access();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$currencyregistry = new CommerceCurrencyRegistry();
$currency = $currencyregistry->require_enabled(
    Currency::sanitize(
        required_param(
            'currency',
            PARAM_ALPHA
        )
    )
);
$sku = optional_param('sku', '', PARAM_RAW_TRIMMED);
$priceid = optional_param('priceid', 0, PARAM_INT);
$quantity = optional_param('quantity', 1, PARAM_INT);
$promotioncode = optional_param('promotioncode', '', PARAM_RAW_TRIMMED);
$targetcurrency = Currency::sanitize(optional_param('targetcurrency', '', PARAM_ALPHA));
$operation = strtolower(optional_param('operation', '', PARAM_ALPHANUMEXT));
$targetplanid = optional_param('targetplanid', 0, PARAM_INT);
$source = strtolower(optional_param('source', '', PARAM_ALPHANUMEXT));
$showroom = strtolower(optional_param('showroom', '', PARAM_ALPHANUMEXT));
$showroomoffer = strtolower(optional_param('showroomoffer', '', PARAM_ALPHANUMEXT));
$returnurl = optional_param('returnurl', UrlFactory::digital_catalog(['currency' => $currency])->out(false), PARAM_LOCALURL);
$customerid = CommerceGuestCartCustomerResolver::create()->resolve($currency);
$service = CommerceCartRuntimeFactory::create();
$cartcustomerresolver = CommerceGuestCartCustomerResolver::create();
$notice = 'unchanged';
$redirecttocheckout = false;
$result = null;

$syncguestdirectcurrency = static function(
    string $sourcecurrency,
    string $targetcurrency,
    array $targetdirectpurchase
) use ($DB): void {
    global $SESSION;

    if (isloggedin() && !isguestuser()) {
        return;
    }

    $guesttoken = trim((string)(
        $SESSION->local_subscriptions_guest_checkout_token
        ?? ''
    ));
    if ($guesttoken === '') {
        return;
    }

    $guestrepository = new CommerceGuestCheckoutSessionRepository($DB);
    $guestsession = $guestrepository->find_by_token($guesttoken);
    if (
        $guestsession === null
        || $guestsession->is_expired()
    ) {
        return;
    }

    if (
        $guestsession->get_currency() !== $sourcecurrency
        && $guestsession->get_currency() !== $targetcurrency
    ) {
        throw new coding_exception(
            'Direct Purchase Guest Checkout currency is out of sync.'
        );
    }

    CommerceGuestCheckoutService::create()
        ->switch_direct_purchase_currency(
            $guestsession,
            $targetcurrency,
            $targetdirectpurchase
        );
};

try {
    if ($action === 'add' || $action === 'buynow') {
        $metadata = [];
        if ($operation === 'upgrade') {
            $metadata = ['operation' => 'upgrade', 'targetplanid' => $targetplanid];
        } else if ($operation === CommercePedagogicalPromotionJoinOperation::OPERATION) {
            // Only the intent is accepted from the browser. CartService rebuilds
            // the complete owner/promotion/price context server-side.
            $metadata = ['operation' => CommercePedagogicalPromotionJoinOperation::OPERATION];
        }
        if ($source === 'showroom') {
            $metadata = array_replace(
                $metadata,
                CommerceShowroomTrackingContext::metadata($showroom, $showroomoffer)
            );
            $SESSION->local_subscriptions_showroom_currency = $currency;
        }
        if (!in_array($operation, ['upgrade', CommercePedagogicalPromotionJoinOperation::OPERATION], true)) {
            $personalservice = CommercePersonalOfferShoppingContextService::create($DB);
            $personalcurrencies = $personalservice->available_currencies($sku);
            if ($personalcurrencies !== null) {
                if ($personalcurrencies === []) {
                    throw new moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
                }
                if (!in_array($currency, $personalcurrencies, true)) {
                    throw new moodle_exception('commerce_personal_offer_currency_unavailable', 'local_subscriptions');
                }
                $personal = $personalservice->resolve(
                    $sku,
                    $currency,
                    $source === 'showroom' && $showroom !== '' ? $showroom : null
                );
                if ($personal === null) {
                    throw new moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
                }
                $metadata = array_replace($metadata, $personal['metadata']);

                // The visual origin remains the Showroom (showroom/showroomoffer/returnurl),
                // but the checkout itself must know that this cart line is a Personal Offer.
                // This unlocks authoritative beneficiary display and EUR/RUB switching.
                $source = 'personaloffer';
            }
        }
        if ($action === 'buynow') {
            $existingdirect = $operation === 'upgrade'
                ? null
                : CommerceDirectPurchaseSession::current_any_currency();
            $normalizedsku = strtoupper(trim($sku));
            $existingoperation = $existingdirect === null
                ? ''
                : strtolower(trim((string)(
                    $existingdirect['metadata']['operation'] ?? ''
                )));
            $sameproduct =
                $existingdirect !== null
                && $existingdirect['sku'] === $normalizedsku
                && $existingdirect['quantity'] === $quantity
                && $existingdirect['cartuuid'] !== null
                && $existingoperation === $operation;

            $sourcecustomerid = $customerid;
            $sourceholdusable = false;
            if ($sameproduct) {
                $sourcecustomerid = $cartcustomerresolver->resolve(
                    $existingdirect['currency']
                );
                $reservationservice =
                    CommercePedagogicalSeatReservationService::create($DB);
                $pedagogicallinked =
                    $reservationservice->is_pedagogically_linked(
                        $normalizedsku
                    );
                $sourceholdusable = !$pedagogicallinked
                    || $reservationservice->has_active_hold(
                        $normalizedsku,
                        (string)$existingdirect['cartuuid'],
                        time()
                    );
            }

            if (
                $sameproduct
                && $sourceholdusable
                && $existingdirect['currency'] !== $currency
            ) {
                $sourcecurrency = $existingdirect['currency'];
                $result = CommerceDirectPurchaseCurrencySwitchService::create()
                    ->switch(
                        $sourcecustomerid,
                        $existingdirect,
                        $currency,
                        current_language()
                    );

                if ($result->has_changed()) {
                    $targetcart = $result->get_cart();
                    $targetitems = $targetcart->get_items();
                    if (count($targetitems) !== 1) {
                        throw new coding_exception(
                            'Direct Purchase surface resume must keep exactly one line.'
                        );
                    }
                    $targetitem = reset($targetitems);
                    if ($targetitem === false) {
                        throw new coding_exception(
                            'Direct Purchase surface resume target line is missing.'
                        );
                    }

                    CommerceDirectPurchaseSession::store(
                        $currency,
                        $targetitem->get_product_sku(),
                        $targetitem->get_price_id(),
                        $targetitem->get_quantity(),
                        $targetitem->get_metadata(),
                        $targetcart->get_uuid()
                    );
                    $targetdirectpurchase =
                        CommerceDirectPurchaseSession::current($currency);
                    if ($targetdirectpurchase === null) {
                        throw new coding_exception(
                            'Direct Purchase surface resume session could not be persisted.'
                        );
                    }

                    $syncguestdirectcurrency(
                        $sourcecurrency,
                        $currency,
                        $targetdirectpurchase
                    );
                }
            } else if (
                $sameproduct
                && $sourceholdusable
                && $existingdirect['currency'] === $currency
                && $existingdirect['priceid'] === $priceid
            ) {
                // Returning from checkout to the same authorised surface must
                // resume the existing Direct Purchase instead of trying to
                // reserve the customer's own seat a second time.
                $redirecttocheckout = true;
            } else {
                // If the previous pedagogical hold expired, create a fresh
                // Direct Purchase. When a provisional Guest identity already
                // exists in the source currency, keep that userid as the
                // reservation owner while moving the durable Guest session.
                $directcustomerid = $sameproduct
                    ? $sourcecustomerid
                    : $customerid;
                $sourcecurrency = $sameproduct
                    ? $existingdirect['currency']
                    : $currency;

                $result = $service->prepare_direct_product(
                    $directcustomerid,
                    $currency,
                    current_language(),
                    $sku,
                    $priceid,
                    $quantity,
                    $metadata
                );

                if ($result->has_changed()) {
                    CommerceDirectPurchaseSession::store(
                        $currency,
                        $sku,
                        $priceid,
                        $quantity,
                        $result->get_cart()->get_items()[0]->get_metadata(),
                        $result->get_cart()->get_uuid()
                    );

                    if ($sameproduct && $sourcecurrency !== $currency) {
                        $targetdirectpurchase =
                            CommerceDirectPurchaseSession::current($currency);
                        if ($targetdirectpurchase === null) {
                            throw new coding_exception(
                                'Fresh Direct Purchase currency resume could not be persisted.'
                            );
                        }
                        $syncguestdirectcurrency(
                            $sourcecurrency,
                            $currency,
                            $targetdirectpurchase
                        );
                    }
                }
            }
        } else {
            if ($operation === CommercePedagogicalPromotionJoinOperation::OPERATION) {
                $result = (new CommerceDirectPromotionJoinCartTransferService(
                    $service,
                    CommercePedagogicalSeatReservationService::create($DB)
                ))->add_current_to_cart(
                    $customerid,
                    $currency,
                    current_language(),
                    $sku,
                    $priceid,
                    $quantity,
                    $metadata
                );
            } else {
                $result = $service->add_product(
                    $customerid,
                    $currency,
                    current_language(),
                    $sku,
                    $priceid,
                    $quantity,
                    $metadata
                );
            }
        }

        $redirecttocheckout = $redirecttocheckout || (
            $action === 'buynow'
            && $result !== null
            && $result->has_changed()
        );
    } else if ($action === 'remove') {
        $result = $service->remove_product($customerid, $currency, $sku, $priceid);
    } else if ($action === 'update') {
        $result = $service->update_quantity($customerid, $currency, current_language(), $sku, $priceid, $quantity);
    } else if ($action === 'clear') {
        $result = $service->clear_cart($customerid, $currency);
    } else if ($action === 'renewseats') {
        $result = $service->renew_seat_reservations(
            $customerid,
            $currency
        );
    } else if ($action === 'applypromo') {
        $result = $service->apply_promotion_code($customerid, $currency, $promotioncode);
    } else if ($action === 'removepromo') {
        $result = $service->remove_promotion_code($customerid, $currency);
    } else if ($action === 'switchcurrency') {
        $available = (new CommerceCurrencyAvailabilityService())->enabled_from(
            CommerceShowroomCurrencyResolver::active_currencies($DB)
        );
        if (!in_array($targetcurrency, $available, true)) {
            throw new moodle_exception('invalidparameter');
        }

        $switch = CommerceCartCurrencySwitchService::create()->switch(
            $customerid,
            $currency,
            $targetcurrency,
            current_language()
        );
        $SESSION->local_subscriptions_cart_currency_switch_report = [
            'currency' => $targetcurrency,
            'removedlabels' => $switch->get_removed_labels(),
            'promotionremoved' => $switch->was_promotion_removed(),
        ];
        $SESSION->local_subscriptions_storefront_currency = $targetcurrency;

        $cartcustomerresolver->synchronize(
            $customerid,
            $targetcurrency,
            $service->open(
                $customerid,
                $targetcurrency
            ),
            true
        );

        if (isloggedin() && !isguestuser()) {
            set_user_preference(
                'local_subscriptions_storefront_currency',
                $targetcurrency,
                (int)$USER->id
            );
        }
        redirect(new moodle_url('/local/subscriptions/cart.php', ['currency' => $targetcurrency]));
    } else if ($action === 'switchpurchasecurrency') {
        $available = (new CommerceCurrencyAvailabilityService())->enabled_from(
            CommerceShowroomCurrencyResolver::active_currencies($DB)
        );
        if (!in_array($targetcurrency, $available, true)) {
            throw new moodle_exception('invalidparameter');
        }

        $directpurchase = CommerceDirectPurchaseSession::current($currency);
        if ($directpurchase === null) {
            throw new moodle_exception('invalidparameter');
        }

        // Browser fields are only hints for the form that initiated the switch.
        // The durable Direct Purchase session is authoritative.
        if (
            ($sku !== '' && strtoupper(trim($sku)) !== $directpurchase['sku'])
            || ($priceid > 0 && $priceid !== $directpurchase['priceid'])
        ) {
            throw new moodle_exception('invalidparameter');
        }

        $switched = CommerceDirectPurchaseCurrencySwitchService::create()->switch(
            $customerid,
            $directpurchase,
            $targetcurrency,
            current_language()
        );
        if (!$switched->has_changed()) {
            $messages = $switched->get_messages();
            throw new moodle_exception(
                $messages !== []
                    ? $messages[0]->get_code()
                    : 'invalidparameter',
                'local_subscriptions'
            );
        }

        $targetcart = $switched->get_cart();
        $targetitems = $targetcart->get_items();
        if (count($targetitems) !== 1) {
            throw new coding_exception(
                'Direct Purchase currency switch must keep exactly one line.'
            );
        }
        $targetitem = reset($targetitems);
        if ($targetitem === false) {
            throw new coding_exception(
                'Direct Purchase currency switch target line is missing.'
            );
        }

        CommerceDirectPurchaseSession::store(
            $targetcurrency,
            $targetitem->get_product_sku(),
            $targetitem->get_price_id(),
            $targetitem->get_quantity(),
            $targetitem->get_metadata(),
            $targetcart->get_uuid()
        );
        $targetdirectpurchase =
            CommerceDirectPurchaseSession::current($targetcurrency);
        if ($targetdirectpurchase === null) {
            throw new coding_exception(
                'Direct Purchase currency switch session could not be persisted.'
            );
        }

        // Keep an in-progress Guest Checkout on the same identity/session. A
        // Direct Purchase is transient, so never route this through the normal
        // anonymous/provisional cart transfer path.
        $syncguestdirectcurrency(
            $currency,
            $targetcurrency,
            $targetdirectpurchase
        );

        $SESSION->local_subscriptions_storefront_currency =
            $targetcurrency;
        if (isloggedin() && !isguestuser()) {
            set_user_preference(
                'local_subscriptions_storefront_currency',
                $targetcurrency,
                (int)$USER->id
            );
        }

        $checkoutparams = [
            'currency' => $targetcurrency,
            'flow' => \local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow::DIRECT,
        ];
        if ($source !== '') {
            $checkoutparams['source'] = $source;
        }
        if ($showroom !== '') {
            $checkoutparams['showroom'] = $showroom;
        }
        if ($showroomoffer !== '') {
            $checkoutparams['showroomoffer'] = $showroomoffer;
        }
        if ($returnurl !== '') {
            $checkoutparams['originreturn'] = $returnurl;
        }

        redirect(
            new moodle_url(
                '/local/subscriptions/commerce_checkout.php',
                $checkoutparams
            )
        );
    } else {
        throw new moodle_exception('invalidparameter');
    }

    $SESSION->local_subscriptions_storefront_currency = $currency;
    if (isloggedin() && !isguestuser()) {
        set_user_preference(
            'local_subscriptions_storefront_currency',
            $currency,
            (int)$USER->id
        );
    }

    if ($action !== 'buynow' && $result !== null) {
        $cartcustomerresolver->synchronize(
            $customerid,
            $currency,
            $result->get_cart()
        );
    }

    $messages = $result !== null
        ? $result->get_messages()
        : [];
    if ($action === 'applypromo') {
        // The operation result is authoritative: rejected codes never enter the cart state.
        $notice = $messages !== []
            ? $messages[0]->get_code()
            : ($result !== null && $result->has_changed()
                ? 'promotion_code_saved'
                : 'unchanged');
    } else {
        $notice = $messages !== []
            ? $messages[0]->get_code()
            : ($result !== null && $result->has_changed()
                ? $action . '_success'
                : 'unchanged');
    }
} catch (Throwable $exception) {
    $notice = 'error';
}

if ($redirecttocheckout) {
    $checkoutparams = [
        'currency' => $currency,
        'flow' => \local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow::DIRECT,
    ];
    if ($source !== '') {
        $checkoutparams['source'] = $source;
    }
    if ($showroom !== '') {
        $checkoutparams['showroom'] = $showroom;
    }
    if ($showroomoffer !== '') {
        $checkoutparams['showroomoffer'] = $showroomoffer;
    }
    if ($returnurl !== '') {
        $checkoutparams['originreturn'] = $returnurl;
    }
    redirect(new moodle_url('/local/subscriptions/commerce_checkout.php', $checkoutparams));
}
if ($action === 'clear' && $result !== null && $result->has_changed()) {
    $shopurl = UrlFactory::digital_catalog([
        'currency' => $currency,
        'cartnotice' => $notice,
        'cartactionresult' => 'clear',
        'cartchanged' => 1,
    ]);
    redirect($shopurl);
}

$redirecturl = new moodle_url($returnurl);
$redirecturl->param('cartnotice', $notice);
if ($sku !== '') {
    $redirecturl->param('cartsku', $sku);
}
if ($priceid > 0) {
    $redirecturl->param('cartpriceid', $priceid);
}
$redirecturl->param('cartactionresult', $action);
$redirecturl->param(
    'cartchanged',
    $result !== null && $result->has_changed() ? 1 : 0
);
redirect($redirecturl);
