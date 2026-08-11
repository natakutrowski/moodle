<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\cart\currency\CommerceCartCurrencySwitchService;
use local_subscriptions\commerce\checkout\express\CommerceCheckoutExpressService;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomTrackingContext;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$currency = CommerceShowroomCurrencyResolver::resolve(
    CommerceShowroomCurrencyResolver::active_currencies($DB),
    strtoupper(optional_param('currency', '', PARAM_ALPHA)),
    strtoupper((string)($SESSION->local_subscriptions_showroom_currency ?? ''))
);
$sku = optional_param('sku', '', PARAM_RAW_TRIMMED);
$priceid = optional_param('priceid', 0, PARAM_INT);
$quantity = optional_param('quantity', 1, PARAM_INT);
$promotioncode = optional_param('promotioncode', '', PARAM_RAW_TRIMMED);
$targetcurrency = strtoupper(optional_param('targetcurrency', '', PARAM_ALPHA));
$operation = strtolower(optional_param('operation', '', PARAM_ALPHA));
$targetplanid = optional_param('targetplanid', 0, PARAM_INT);
$source = strtolower(optional_param('source', '', PARAM_ALPHANUMEXT));
$showroom = strtolower(optional_param('showroom', '', PARAM_ALPHANUMEXT));
$showroomoffer = strtolower(optional_param('showroomoffer', '', PARAM_ALPHANUMEXT));
$express = optional_param('express', 0, PARAM_BOOL);
$providerconfirmed = optional_param('providerconfirmed', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', UrlFactory::digital_catalog(['currency' => $currency])->out(false), PARAM_LOCALURL);
$customerid = isloggedin() && !isguestuser() ? (int)$USER->id : 0;
$service = CommerceCartRuntimeFactory::create();
$notice = 'unchanged';
$redirecttocheckout = false;

try {
    if ($action === 'add' || $action === 'buynow') {
        $metadata = [];
        if ($operation === 'upgrade') {
            $metadata = ['operation' => 'upgrade', 'targetplanid' => $targetplanid];
        }
        if ($source === 'showroom') {
            $metadata = array_replace(
                $metadata,
                CommerceShowroomTrackingContext::metadata($showroom, $showroomoffer)
            );
            $SESSION->local_subscriptions_showroom_currency = $currency;
        }
        if ($action === 'buynow') {
            $service->clear_cart($customerid, $currency);
        }
        $result = $service->add_product($customerid, $currency, current_language(), $sku, $priceid, $quantity, $metadata);
        $redirecttocheckout = $action === 'buynow' && $result->has_changed();
    } else if ($action === 'remove') {
        $result = $service->remove_product($customerid, $currency, $sku, $priceid);
    } else if ($action === 'update') {
        $result = $service->update_quantity($customerid, $currency, current_language(), $sku, $priceid, $quantity);
    } else if ($action === 'clear') {
        $result = $service->clear_cart($customerid, $currency);
    } else if ($action === 'applypromo') {
        $result = $service->apply_promotion_code($customerid, $currency, $promotioncode);
    } else if ($action === 'removepromo') {
        $result = $service->remove_promotion_code($customerid, $currency);
    } else if ($action === 'switchcurrency' || $action === 'switchpurchasecurrency') {
        $available = CommerceShowroomCurrencyResolver::active_currencies($DB);
        if (!in_array($targetcurrency, $available, true)) {
            throw new moodle_exception('invalidparameter');
        }
        // An Express purchase has not necessarily materialised a cart yet. Build the
        // source cart first, then reuse the same authoritative currency switch service.
        if ($action === 'switchpurchasecurrency' && $sku !== '') {
            $metadata = [];
            if ($source === 'showroom') {
                $metadata = CommerceShowroomTrackingContext::metadata($showroom, $showroomoffer);
                $SESSION->local_subscriptions_showroom_currency = $currency;
            }
            $service->clear_cart($customerid, $currency);
            $prepared = $service->add_product(
                $customerid,
                $currency,
                current_language(),
                $sku,
                $priceid,
                max(1, $quantity),
                $metadata
            );
            if (!$prepared->has_changed()) {
                throw new moodle_exception('invalidparameter');
            }
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
        if ($customerid > 0) {
            set_user_preference('local_subscriptions_storefront_currency', $targetcurrency, $customerid);
        }
        redirect(new moodle_url('/local/subscriptions/cart.php', ['currency' => $targetcurrency]));
    } else {
        throw new moodle_exception('invalidparameter');
    }

    $messages = $result->get_messages();
    if ($action === 'applypromo') {
        // The operation result is authoritative: rejected codes never enter the cart state.
        $notice = $messages !== []
            ? $messages[0]->get_code()
            : ($result->has_changed() ? 'promotion_code_saved' : 'unchanged');
    } else {
        $notice = $messages !== [] ? $messages[0]->get_code() : ($result->has_changed() ? $action . '_success' : 'unchanged');
    }
} catch (Throwable $exception) {
    $notice = 'error';
}

if ($redirecttocheckout) {
    if ($express && $providerconfirmed && $customerid > 0) {
        try {
            $expressservice = new CommerceCheckoutExpressService();
            $reason = $expressservice->ineligibility_reason($customerid, $currency);
            if ($reason === '') {
                $launch = $expressservice->launch($customerid, $currency, [
                    'checkout_source' => $source,
                    'showroom' => $showroom,
                    'showroom_offer' => $showroomoffer,
                    'origin_return' => $returnurl,
                ]);
                $action = $launch->get_initialization()->get_payment_result()?->get_action();

                if ($action?->is_redirect() && $action->get_url() !== null) {
                    redirect($action->get_url());
                }

                if ($action?->is_form_post() && $action->get_url() !== null) {
                    $PAGE->set_context(context_system::instance());
                    $PAGE->set_url(new moodle_url('/local/subscriptions/cart_action.php'));
                    $PAGE->set_pagelayout('embedded');
                    echo $OUTPUT->header();
                    echo html_writer::start_tag('form', [
                        'id' => 'commerce-express-provider-post',
                        'method' => 'post',
                        'action' => $action->get_url(),
                    ]);
                    foreach ($action->get_parameters() as $name => $value) {
                        echo html_writer::empty_tag('input', [
                            'type' => 'hidden',
                            'name' => (string)$name,
                            'value' => (string)$value,
                        ]);
                    }
                    echo html_writer::tag(
                        'button',
                        get_string('commerce_checkout_continue_payment', 'local_subscriptions'),
                        ['type' => 'submit', 'class' => 'btn btn-primary']
                    );
                    echo html_writer::end_tag('form');
                    echo html_writer::script(
                        "document.getElementById('commerce-express-provider-post').submit();"
                    );
                    echo $OUTPUT->footer();
                    exit;
                }
            }
        } catch (Throwable $exception) {
            error_log('[local_subscriptions][express_checkout_fallback] ' . $exception);
        }
    }

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

if ($action === 'clear' && $result->has_changed()) {
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
$redirecturl->param('cartchanged', $result->has_changed() ? 1 : 0);
redirect($redirecturl);
