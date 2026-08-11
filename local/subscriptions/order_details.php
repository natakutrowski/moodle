<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/commerce/tracking/CommerceTrackedActionUrl.php');

use local_subscriptions\commerce\checkout\guest\CommerceProvisionalGuestAccountContext;
use local_subscriptions\commerce\order\presentation\CommerceBundleComponentResolver;
use local_subscriptions\commerce\order\presentation\CommerceCustomerStatusResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderExperienceResolver;
use local_subscriptions\commerce\order\presentation\CommerceLegacyOrderAccessResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\tracking\CommerceTrackedActionUrl;
use local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $OUTPUT, $PAGE, $SITE, $USER, $SESSION;

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$autoprint = optional_param('print', 0, PARAM_BOOL);
$context = context_system::instance();
$isfullyauthenticated = isloggedin() && !isguestuser();
$isadmin = $isfullyauthenticated && has_capability('moodle/site:config', $context);
$provisionalcontext = $isfullyauthenticated ? null : CommerceProvisionalGuestAccountContext::resolve($reference);
$requiresaccountfinalisation = $provisionalcontext !== null;
$accountactivationurl = $requiresaccountfinalisation
    ? $provisionalcontext['activationurl']
    : null;

try {
    if ($isfullyauthenticated) {
        $order = CommerceOrderPresentationService::create()->find_for_user(
            $reference,
            (int)$USER->id,
            $isadmin,
            (string)$USER->email
        );
    } else if ($provisionalcontext !== null) {
        $order = CommerceOrderPresentationService::create()->find_for_user(
            $reference,
            (int)$provisionalcontext['session']->get_user_id()
        );
    } else {
        throw new CommerceOrderPresentationAccessDeniedException('No authenticated or Guest Checkout ownership context.');
    }
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    throw new moodle_exception('nopermissions', 'error');
}
if ($order === null) {
    throw new moodle_exception('commerce_i2_order_not_found', 'local_subscriptions');
}

$publicreference = (new CommercePublicOrderReference())->from_internal($order->reference, $order->timecreated);
$pageurl = new moodle_url('/local/subscriptions/order_details.php', ['reference' => $reference]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('commerce_i43_page_title', 'local_subscriptions', $publicreference));
$PAGE->set_heading(format_string($SITE->fullname));
if (!$requiresaccountfinalisation) {
    $PAGE->navbar->add(
        get_string('commerce_customer_hub_title', 'local_subscriptions'),
        UrlFactory::my_campus()
    );
    $PAGE->navbar->add(
        get_string('mysubs_title', 'local_subscriptions'),
        UrlFactory::my_purchases()
    );
}
$PAGE->navbar->add(
    get_string('commerce_i43_page_title', 'local_subscriptions', $publicreference)
);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/order_details.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/provisional_account.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/order_print.css'));
$PAGE->requires->js_call_amd('local_subscriptions/guest_checkout_security', 'init');

$formatmoney = static fn(int $minor, string $currency): string =>
    format_float($minor / 100, 2) . ' ' . strtoupper($currency);
$statusresolver = new CommerceCustomerStatusResolver();
$bundleresolver = new CommerceBundleComponentResolver($DB);
$detailsrecord = (new \local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository($DB))->find_by_reference($reference);
$legacyactions = $detailsrecord === null ? [] : (new CommerceLegacyOrderAccessResolver($DB))->resolve(
    $detailsrecord->legacyfamily,
    $detailsrecord->legacyid
);
$purchaseorigin = $detailsrecord !== null
    && trim((string)$detailsrecord->legacyfamily) !== ''
    && (int)($detailsrecord->legacyid ?? 0) > 0
        ? get_string('commerce_purchase_origin_legacy', 'local_subscriptions')
        : get_string('commerce_purchase_origin_native', 'local_subscriptions');
$experience = (new CommerceOrderExperienceResolver())->resolve($order);
$orderstatus = $statusresolver->resolve_order($order->status);
$paymentstatus = $statusresolver->resolve_payment($order->paymentstatus);
$accessstatus = $statusresolver->resolve_access($order->fulfillmentstatus);

$supportemail = (string)(get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr');
$supportdestination = UrlFactory::support_for_order($reference);
$invoicedestination = new moodle_url('/local/subscriptions/order_invoice.php', ['reference' => $reference]);
$printdestination = new moodle_url('/local/subscriptions/order_details.php', ['reference' => $reference, 'print' => 1]);
$supporturl = CommerceTrackedActionUrl::build($reference, 'order_contact_support', 'order_details', $supportdestination)->out(false);
$invoiceurl = CommerceTrackedActionUrl::build($reference, 'order_download_invoice', 'order_details', $invoicedestination)->out(false);
$printurl = CommerceTrackedActionUrl::build($reference, 'order_print', 'order_details', $printdestination)->out(false);

$cataloghydrator = new CommerceCatalogHydrator();
$catalogproducts = new CommerceProductRepository($DB, $cataloghydrator);
$producttranslations = new CommerceProductTranslationRepository(
    $DB,
    $cataloghydrator,
    $catalogproducts
);

$resolveaccesslabel = static function($access) use ($DB, $catalogproducts, $producttranslations): string {
    $sku = strtoupper(trim((string)($access->metadata['productsku'] ?? '')));

    if ($sku !== '') {
        $product = $catalogproducts->find_by_sku($sku);
        if ($product !== null) {
            // Same translation repository/fallback path as Cart and Checkout.
            $translation = $producttranslations->find(
                $product->get_sku(),
                current_language()
            );
            if ($translation !== null && trim($translation->get_name()) !== '') {
                return format_string(strip_tags(
                    preg_replace('/<\s*br\s*\/?\s*>/i', ' ', $translation->get_name()) ?? $translation->get_name()
                ));
            }

            if (trim($product->get_name()) !== '') {
                return format_string(strip_tags(
                    preg_replace('/<\s*br\s*\/?\s*>/i', ' ', $product->get_name()) ?? $product->get_name()
                ));
            }
        }
    }

    if ($access->type === 'course_access') {
        $courseid = (int)($access->metadata['courseid'] ?? 0);
        if ($courseid > 0) {
            $course = $DB->get_record(
                'course',
                ['id' => $courseid],
                'fullname',
                IGNORE_MISSING
            );
            if ($course !== false) {
                return format_string((string)$course->fullname);
            }
        }
    }

    return '';
};
$accesslabelsbygrant = [];
foreach ($order->items as $item) {
    foreach ($item->accesses as $access) {
        $label = $resolveaccesslabel($access);
        if ($label === '') {
            $label = format_string($item->label);
        }
        if ($access->grantreference !== '') {
            $accesslabelsbygrant[$access->grantreference] = $label;
        }
    }
}

$timelinelabels = [
    'order_created' => 'commerce_i44_event_order_created',
    'payment_confirmed' => 'commerce_i44_event_payment_confirmed',
    'payment_pending' => 'commerce_i44_event_payment_pending',
    'payment_processing' => 'commerce_i44_event_payment_processing',
    'payment_failed' => 'commerce_i44_event_payment_failed',
    'payment_cancelled' => 'commerce_i44_event_payment_cancelled',
    'payment_canceled' => 'commerce_i44_event_payment_cancelled',
    'access_available' => 'commerce_i44_event_access_available',
    'access_planned' => 'commerce_i44_event_access_planned',
    'access_processing' => 'commerce_i44_event_access_processing',
    'access_failed' => 'commerce_i44_event_access_failed',
];
$timeline = [];
foreach ($order->timeline as $event) {
    $state = $statusresolver->resolve_timeline($event->status);
    $labelkey = $timelinelabels[$event->label] ?? null;
    $grantreference = trim((string)($event->metadata['grantreference'] ?? ''));
    $detail = $grantreference === '' ? '' : ($accesslabelsbygrant[$grantreference] ?? '');
    $timeline[] = [
        'label' => $labelkey === null ? get_string('commerce_i410_order_update', 'local_subscriptions')
            : get_string($labelkey, 'local_subscriptions'),
        'hasdetail' => $detail !== '',
        'detail' => $detail,
        'status' => $state['label'],
        'statusclass' => $state['class'],
        'date' => userdate($event->timestamp),
    ];
}

$providerkey = strtolower(trim((string)($order->payment?->provider ?? $order->provider ?? '')));
$providerkey = match ($providerkey) {
    'alfabank', 'alfa-bank' => Provider::ALFA,
    default => $providerkey,
};
$provider = $providerkey === ''
    ? get_string('commerce_i410_payment_method_unknown', 'local_subscriptions')
    : Provider::get($providerkey);
$providerhtml = $isadmin
    ? Provider::label_with_icon_env($providerkey)
    : Provider::label_with_icon($providerkey);
$payment = $order->payment;
$pricingpresenter =
    new CommercePersistedCommercialPricingPresenter();
$itempricingmodels = [];
foreach ($order->items as $item) {
    $itempricingmodels[] = $pricingpresenter->item(
        $item->metadata,
        $item->grossminor,
        $item->discountminor,
        $item->netminor,
        $item->quantity
    );
}
$orderpricing = $pricingpresenter->order(
    $order->metadata,
    $itempricingmodels,
    $order->totalminor
);
$promotioncodes = array_values(array_filter(
    (array)($order->metadata['promotion_codes'] ?? []),
    'is_string'
));

// Detect a Personal Offer from the immutable purchase item metadata.
// The customer sees the commercial origin of the reduction; admins also get
// the internal offer id and a direct CRM link.
$personalofferrecord = null;
$personalofferuuid = '';
foreach ($order->items as $orderitem) {
    $metadata = is_array($orderitem->metadata ?? null) ? $orderitem->metadata : [];
    if (strtolower(trim((string)($metadata['operation'] ?? ''))) !== 'personaloffer') {
        continue;
    }
    $personalofferuuid = strtolower(trim((string)($metadata['personal_offer_uuid'] ?? '')));
    if ($personalofferuuid !== '') {
        break;
    }
}
if ($personalofferuuid !== '') {
    $personalofferrecord = (new MoodleCommercePersonalOfferRepository($DB))->get_by_uuid($personalofferuuid);
}
$promotioncontext = [
    'haspromotion' =>
        $orderpricing['haspricing']
        || $promotioncodes !== [],
    'subtotal' => $formatmoney(
        (int)$orderpricing['initialminor'],
        $order->currency
    ),
    'discount' => $formatmoney(
        (int)$orderpricing['totalreductionminor'],
        $order->currency
    ),
    'hasproductpromotion' => $orderpricing['haspromotion'],
    'productpromotion' => $formatmoney(
        (int)$orderpricing['promotionminor'],
        $order->currency
    ),
    'hastrialdiscount' => $orderpricing['hastrial'],
    'trialdiscount' => $formatmoney(
        (int)$orderpricing['trialminor'],
        $order->currency
    ),
    'hasownedcredit' => $orderpricing['hascredit'],
    'ownedcredit' => $formatmoney(
        (int)$orderpricing['creditminor'],
        $order->currency
    ),
    'hasadjustmentdiscount' => $orderpricing['hasadjustment'],
    'adjustmentdiscount' => $formatmoney(
        (int)$orderpricing['adjustmentminor'],
        $order->currency
    ),
    'ispersonaloffer' => $personalofferrecord !== null,
    'personalofferlabel' => get_string('commerce_personal_offer_order_discount_label', 'local_subscriptions'),
    'personalofferid' => $personalofferrecord?->get_id() ?? 0,
    'personalofferurl' => $personalofferrecord !== null
        ? (new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', [
            'id' => $personalofferrecord->get_id(),
        ]))->out(false)
        : '',
    'codes' => implode(', ', array_map('s', $promotioncodes)),
    'hascodes' => $promotioncodes !== [],
    'paid' => $formatmoney(
        (int)$orderpricing['paidminor'],
        $order->currency
    ),
];

$paymentcontext = [
    'status' => $paymentstatus['label'],
    'statusclass' => $paymentstatus['class'],
    'provider' => $provider,
    'providerhtml' => $providerhtml,
    'amount' => $formatmoney($payment?->amountminor ?? $order->totalminor, $payment?->currency ?? $order->currency),
    'paidat' => ($payment?->paidat ?? $order->paidat) === null
        ? get_string('commerce_i410_not_available', 'local_subscriptions')
        : userdate((int)($payment?->paidat ?? $order->paidat)),
    'providerreference' => $payment?->providerreference === null ? '—' : s($payment->providerreference),
    'transactionid' => $payment?->transactionid === null ? '—' : s($payment->transactionid),
];

$templatecontext = [
    'reference' => s($publicreference),
    'createdat' => userdate($order->timecreated),
    'total' => $formatmoney($order->totalminor, $order->currency),
    'status' => $orderstatus['label'],
    'statusclass' => $orderstatus['class'],
    'paymentstatus' => $paymentstatus['label'],
    'paymentstatusclass' => $paymentstatus['class'],
    'hasaccessstatus' => $accessstatus !== null,
    'accessstatus' => $accessstatus['label'] ?? '',
    'accessstatusclass' => $accessstatus['class'] ?? 'neutral',
    'backurl' => $requiresaccountfinalisation
        ? (new moodle_url('/local/subscriptions/order_result.php', ['reference' => $reference]))->out(false)
        : UrlFactory::my_purchases()->out(false),
    'requiresaccountfinalisation' => $requiresaccountfinalisation,
    'accountactivationurl' => $accountactivationurl?->out(false) ?? '',
    'autoopen' => '0',
    'supporturl' => $supporturl,
    'invoiceurl' => $invoiceurl,
    'printurl' => $printurl,
    'supportemail' => s($supportemail),
    'isadmin' => $isadmin,
    'purchaseid' => $order->purchaseid,
    'internalreference' => s($order->reference),
    'uuid' => s($order->uuid),
    'purchaseorigin' => $purchaseorigin,
    'payment' => $paymentcontext,
    'promotion' => $promotioncontext,
    'timeline' => $timeline,
    'hastimeline' => $timeline !== [],
    'items' => [],
    'itemcount' => $experience['itemcount'],
    'isbundle' => $experience['isbundle'],
    'ismultiproduct' => $experience['ismultiproduct'] ?? false,
    'coursecount' => $experience['coursecount'],
    'digitalcount' => $experience['digitalcount'],
    'accesscount' => $experience['accesscount'],
    'hascourses' => $experience['hascourses'],
    'hasdigitals' => $experience['hasdigitals'],
    'hasavailableaccesses' => $experience['hasaccesses'],
];

$legacyactionsattached = false;

foreach ($order->items as $index => $item) {
    $type = strtolower($item->type);
    $isbundleitem = $type === 'bundle';
    $itemmetadata = is_array($item->metadata ?? null) ? $item->metadata : [];
    $itemispersonaloffer = strtolower(trim((string)($itemmetadata['operation'] ?? ''))) === 'personaloffer';

    $itemcontext = [
        'label' => format_string($item->label),
        'ispersonaloffer' => $itemispersonaloffer,
        'personalofferlabel' => get_string('commerce_personal_offer_order_discount_label', 'local_subscriptions'),
        'position' => $index + 1,
        'iscourse' => in_array($type, ['course_access', 'subscription', 'course'], true),
        'isdigital' => in_array($type, ['digital_download', 'digital'], true),
        'isbundleitem' => $isbundleitem,
        'typelabel' => get_string(match (true) {
            $isbundleitem => 'commerce_i410_type_bundle',
            in_array($type, ['course_access', 'subscription', 'course'], true) => 'commerce_i410_type_course',
            in_array($type, ['digital_download', 'digital'], true) => 'commerce_i410_type_digital',
            default => 'commerce_i410_type_product',
        }, 'local_subscriptions'),
        'quantity' => $item->quantity,
        'showunitprice' => $item->quantity > 1,
        'unitprice' => $formatmoney($item->unitminor, $item->currency),
        'linetotal' => $formatmoney($item->netminor, $item->currency),
        'finalprice' => $formatmoney(
            (int)$itempricingmodels[$index]['finalminor'],
            $item->currency
        ),
        'compareprice' => $formatmoney(
            (int)$itempricingmodels[$index]['initialminor'],
            $item->currency
        ),
        'hascompareprice' =>
            (int)$itempricingmodels[$index]['initialminor']
                > (int)$itempricingmodels[$index]['finalminor'],
        'isupgradepricing' =>
            $itempricingmodels[$index]['isupgrade'],
        'hasupgradepath' =>
            $itempricingmodels[$index]['hasupgradepath'],
        'upgradepath' =>
            $itempricingmodels[$index]['hasupgradepath']
                ? (string)$itempricingmodels[$index]['fromlabel']
                    . ' → '
                    . (string)$itempricingmodels[$index]['tolabel']
                : '',
        'promotionpercent' =>
            (int)$itempricingmodels[$index]['promotionpercent'],
        'haspromotionpercent' =>
            $itempricingmodels[$index]['haspromotionpercent'],
        'trialpercent' =>
            (int)$itempricingmodels[$index]['trialpercent'],
        'hastrialpercent' =>
            $itempricingmodels[$index]['hastrialpercent'],
        'hascommercialpricing' =>
            $itempricingmodels[$index]['haspricing'],
        'initialprice' => $formatmoney(
            (int)$itempricingmodels[$index]['initialminor'],
            $item->currency
        ),
        'hasproductpromotion' =>
            $itempricingmodels[$index]['haspromotion'],
        'productpromotion' => $formatmoney(
            (int)$itempricingmodels[$index]['promotionminor'],
            $item->currency
        ),
        'productpromotionlabel' =>
            $itempricingmodels[$index]['haspromotionpercent']
                ? get_string(
                    'commerce_pricing_initial_promotion_percent',
                    'local_subscriptions',
                    (int)$itempricingmodels[$index]['promotionpercent']
                )
                : get_string(
                    'commerce_pricing_initial_promotion',
                    'local_subscriptions'
                ),
        'hastrialdiscount' =>
            $itempricingmodels[$index]['hastrial'],
        'trialdiscount' => $formatmoney(
            (int)$itempricingmodels[$index]['trialminor'],
            $item->currency
        ),
        'hasownedcredit' =>
            $itempricingmodels[$index]['hascredit'],
        'ownedcreditlabel' =>
            (string)$itempricingmodels[$index]['fromlabel'] !== ''
                ? get_string(
                    'commerce_pricing_owned_credit',
                    'local_subscriptions',
                    (string)$itempricingmodels[$index]['fromlabel']
                )
                : get_string(
                    'commerce_invoice_owned_credit',
                    'local_subscriptions'
                ),
        'ownedcredit' => $formatmoney(
            (int)$itempricingmodels[$index]['creditminor'],
            $item->currency
        ),
        'hasotherdiscount' =>
            $itempricingmodels[$index]['hasotherdiscount'],
        'otherdiscount' => $formatmoney(
            (int)$itempricingmodels[$index]['otherdiscountminor'],
            $item->currency
        ),
        'hasdiscount' => false,
        'discount' => '',
        'accesses' => [],
        'components' => $isbundleitem ? $bundleresolver->resolve($item) : [],
    ];
    foreach ($item->accesses as $access) {
        $hasdesktop = $access->type === 'digital_download' && !empty($access->metadata['hasdesktop']);
        $hasmobile = $access->type === 'digital_download' && !empty($access->metadata['hasmobile']);
        $baseurl = $access->url;
        $action = $access->type === 'course_access' ? 'order_open_course' : 'order_download_file';
        $trackedbaseurl = $baseurl === null ? null : CommerceTrackedActionUrl::build(
            $reference,
            $action,
            'order_details',
            $baseurl
        )->out(false);
        $failedaccess = in_array(strtolower((string)$access->status), ['failed', 'error'], true)
            || in_array(strtolower((string)$order->fulfillmentstatus), ['failed', 'error'], true);
        $itemcontext['accesses'][] = [
            'iscourse' => $access->type === 'course_access',
            'requiresaccountfinalisation' => $requiresaccountfinalisation && $access->type === 'course_access',
            'isdigital' => $access->type === 'digital_download',
            'available' => $access->available,
            'hasurl' => $access->available && $baseurl !== null,
            'url' => $trackedbaseurl,
            'desktopurl' => $baseurl === null ? null : CommerceTrackedActionUrl::build($reference, 'order_download_file', 'order_details', new moodle_url($baseurl, ['version' => 'desktop']))->out(false),
            'mobileurl' => $baseurl === null ? null : CommerceTrackedActionUrl::build($reference, 'order_download_file', 'order_details', new moodle_url($baseurl, ['version' => 'mobile']))->out(false),
            'hasdesktop' => $hasdesktop || ($access->type === 'digital_download' && !$hasmobile),
            'hasmobile' => $hasmobile,
            'pendinglabel' => get_string($failedaccess ? 'commerce_access_temporarily_unavailable' : 'commerce_access_preparing', 'local_subscriptions'),
            'accessstateclass' => $failedaccess ? 'failed' : 'preparing',
            'resourcelabel' => $resolveaccesslabel($access),
            'hasresourcelabel' => $resolveaccesslabel($access) !== '',
        ];
    }
    if (!$legacyactionsattached && $legacyactions !== [] && ($itemcontext['iscourse'] || $itemcontext['isdigital'])) {
        foreach ($legacyactions as $legacyaction) {
            $itemcontext['accesses'][] = $legacyaction + [
                'iscourse' => $legacyaction['type'] === 'course_access',
                'requiresaccountfinalisation' => $requiresaccountfinalisation && $legacyaction['type'] === 'course_access',
                'isdigital' => $legacyaction['type'] === 'digital_download',
                'hasurl' => !empty($legacyaction['url']),
                'pendinglabel' => get_string('commerce_order_access_preparing', 'local_subscriptions'),
                'hasresourcelabel' => trim((string)($legacyaction['resourcelabel'] ?? '')) !== '',
            ];
        }
        $legacyactionsattached = true;
    }
    $itemcontext['hasaccesses'] = $itemcontext['accesses'] !== [];
    $itemcontext['hascomponents'] = $itemcontext['components'] !== [];
    $templatecontext['items'][] = $itemcontext;
}
$templatecontext['hasitems'] = $templatecontext['items'] !== [];
$includedcount = 0;
foreach ($templatecontext['items'] as $presenteditem) {
    if (!empty($presenteditem['components'])) {
        foreach ($presenteditem['components'] as $component) {
            $includedcount += max(1, (int)($component['quantity'] ?? 1));
        }
    }
}
if ($includedcount > 0) {
    $templatecontext['itemcount'] = $includedcount;
    $templatecontext['includedcount'] = $includedcount;
    $templatecontext['hasincludedcount'] = true;
}

if ($autoprint) {
    $PAGE->requires->js_init_code('window.addEventListener("load", function() { window.print(); });');
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/order_details/page', $templatecontext);
echo $OUTPUT->footer();
