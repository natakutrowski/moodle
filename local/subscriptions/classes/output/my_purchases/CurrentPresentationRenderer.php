<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../lib/plans_lib.php');
require_once(__DIR__ . '/../../../lib/user_subs_lib.php');

use html_writer;
use local_subscriptions\url\CommerceCustomerPublicUrlResolver;
use local_subscriptions\commerce\student\StudentCommercePurchaseFactory;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;
use local_subscriptions\commerce\order\presentation\CommerceBundleComponentResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderExperienceResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\constants\Status;
use local_subscriptions\payment\Provider;
use local_subscriptions\output\my_purchases\components\PurchaseActions;
use local_subscriptions\output\my_purchases\components\PurchaseCard;
use local_subscriptions\output\my_purchases\components\PurchasesList;
use local_subscriptions\output\my_purchases\components\PurchaseDetailModal;
use local_subscriptions\support\SubsPresenter;
use local_subscriptions\url\UrlFactory;
use moodle_url;
use renderer_base;

/**
 * Preserved legacy presentation renderer used during the I4.1 transition.
 *
 * This class isolates the current HTML presentation from the page output model.
 * Later I4 deliveries can replace each section with dedicated output models and
 * Mustache templates without reintroducing rendering logic in the controller.
 */
final class CurrentPresentationRenderer {
    public function __construct(
        private readonly \stdClass $targetuser,
        private readonly bool $isadminview,
        private readonly MyPurchasesFilter $filter
    ) {
    }

    public function render(renderer_base $output): string {
        global $DB;

        $OUTPUT = $output;
        $targetuser = $this->targetuser;
        $isadminview = $this->isadminview;

        ob_start();

        $fmtmoney = static function($amt, $cur): string {
            return format_float((float)$amt, 2) . ' ' . strtoupper((string)$cur);
        };

        $isunlimited = static function($ts): bool {
            return empty($ts) || (int)$ts >= 4102444800; // 0/null OR >= 2100-01-01.
        };

        $trialids = array_filter(array_map('intval',
            explode(',', (string)get_config('local_subscriptions', 'trial_planids'))
        ));

        $detecttrial = static function($plan, $sub) use ($trialids): bool {
            if (!empty($trialids) && in_array((int)$plan->id, $trialids, true)) {
                return true;
            }

            $name = mb_strtolower((string)format_string($plan->name), 'UTF-8');

            if (preg_match('/\b(essai|trial|проб)/u', $name)) {
                return true;
            }

            $durationsec = (int)$sub->end_date - (int)$sub->start_date;
            $days = (int)round($durationsec / DAYSECS);
            $paid0 = (float)($sub->pricepaid ?? 0) == 0.0;

            return $paid0 && $days >= 6 && $days <= 8;
        };

        $addsection = static function(array &$rows, string $title, string $icon = 'fa-solid fa-circle-info'): void {
            $rows[] = [
                'section' => $title,
                'icon' => $icon,
            ];
        };


        $studentcommerce = StudentCommercePurchaseFactory::create()->get_for_customer(
            (int)$targetuser->id,
            (string)$targetuser->email
        );
        $subs = $studentcommerce->get_subscriptions();
        $digitalpurchases = $studentcommerce->get_digital_purchases();
        $purchaseslist = new PurchasesList($OUTPUT);
        $nativepurchases = new CommercePurchaseReadRepository($DB);
        $nativeorders = $nativepurchases->find_details_for_customer(
            (int)$targetuser->id,
            (string)$targetuser->email
        );
        $nativeordersbyreference = [];
        $nativeordersbylegacy = [];
        $bundleorders = [];
        $usednativeorders = [];
        $experienceresolver = new CommerceOrderExperienceResolver();
        $bundlecomponentresolver = new CommerceBundleComponentResolver($DB);
        $legacyproductresolver = new CommerceLegacyStorefrontProductResolver($DB);

        $resolveNativeProductSku = static function(?object $nativeorder, array $granttypes = []): string {
            if ($nativeorder === null) {
                return '';
            }

            foreach ($nativeorder->grants ?? [] as $grant) {
                $granttype = strtolower(trim((string)($grant->type ?? '')));
                if ($granttypes !== [] && !in_array($granttype, $granttypes, true)) {
                    continue;
                }
                $sku = trim((string)($grant->productsku ?? ''));
                if ($sku !== '') {
                    return $sku;
                }
            }

            foreach ($nativeorder->items ?? [] as $item) {
                $metadata = is_array($item->metadata ?? null) ? $item->metadata : [];
                $sku = trim((string)($metadata['productsku'] ?? $metadata['sku'] ?? ''));
                if ($sku !== '') {
                    return $sku;
                }
            }

            return '';
        };
        foreach ($nativeorders as $nativeorderdetails) {
            $reference = $nativeorderdetails->summary->reference;
            $nativeordersbyreference[$reference] = $nativeorderdetails;
            if ($nativeorderdetails->legacyfamily !== null && $nativeorderdetails->legacyid !== null) {
                $nativeordersbylegacy[$nativeorderdetails->legacyfamily . ':' . $nativeorderdetails->legacyid] = $nativeorderdetails;
            }

            $presentation = CommerceOrderPresentationService::create()->present($nativeorderdetails);
            if ($experienceresolver->resolve($presentation)['isbundle']) {
                $bundleorders[] = $presentation;
            }
        }

        $matchnativeorder = static function(
            string $family,
            int $legacyid,
            string $reference,
            int $timestamp,
            int $amountminor
        ) use ($nativeordersbyreference, $nativeordersbylegacy, $nativeorders, &$usednativeorders): ?object {
            if ($reference !== '' && isset($nativeordersbyreference[$reference])) {
                $usednativeorders[$reference] = true;
                return $nativeordersbyreference[$reference];
            }

            $legacykey = $family . ':' . $legacyid;
            if ($legacyid > 0 && isset($nativeordersbylegacy[$legacykey])) {
                $details = $nativeordersbylegacy[$legacykey];
                $usednativeorders[$details->summary->reference] = true;
                return $details;
            }

            $allowedtypes = $family === 'subscription'
                ? ['subscription', 'course_access', 'course']
                : ['digital', 'digital_download'];
            $candidates = [];
            foreach ($nativeorders as $details) {
                $summary = $details->summary;
                if (isset($usednativeorders[$summary->reference])) {
                    continue;
                }
                if (!in_array(strtolower($summary->type), $allowedtypes, true)) {
                    continue;
                }
                if ($amountminor > 0 && abs($summary->totalminor - $amountminor) > 1) {
                    continue;
                }
                if ($timestamp > 0 && abs($summary->timecreated - $timestamp) > (2 * DAYSECS)) {
                    continue;
                }
                $candidates[] = $details;
            }

            if (count($candidates) !== 1) {
                return null;
            }

            $details = $candidates[0];
            $usednativeorders[$details->summary->reference] = true;
            return $details;
        };
        $bundlereferences = array_fill_keys(
            array_map(static fn($order): string => $order->reference, $bundleorders),
            true
        );
        $subs = array_values(array_filter($subs, static function($purchase) use ($bundlereferences): bool {
            $reference = trim((string)($purchase->commerce_reference ?? ''));
            return $reference === '' || !isset($bundlereferences[$reference]);
        }));
        $digitalpurchases = array_values(array_filter($digitalpurchases, static function($purchase) use ($bundlereferences): bool {
            $reference = trim((string)($purchase->commerce_reference ?? ''));
            return $reference === '' || !isset($bundlereferences[$reference]);
        }));
        usort($subs, static function($a, $b): int {
            $aactive = strtolower((string)($a->status ?? '')) === 'active' ? 1 : 0;
            $bactive = strtolower((string)($b->status ?? '')) === 'active' ? 1 : 0;
            return ($bactive <=> $aactive) ?: ((int)($b->startdate ?? $b->timecreated ?? 0) <=> (int)($a->startdate ?? $a->timecreated ?? 0));
        });
        usort($digitalpurchases, static fn($a, $b): int => (int)($b->payment_date ?? $b->creation_date ?? 0) <=> (int)($a->payment_date ?? $a->creation_date ?? 0));
        usort($bundleorders, static fn($a, $b): int => $b->timecreated <=> $a->timecreated);
        $totalpurchases = count($subs) + count($digitalpurchases) + count($bundleorders);

        $pagetitle = $isadminview
            ? get_string('user_purchases_title', 'local_subscriptions', fullname($targetuser))
            : get_string('mysubs_title', 'local_subscriptions');

        echo html_writer::start_div('my-purchases-hero');
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa-solid fa-bag-shopping', 'aria-hidden' => 'true']),
            'my-purchases-hero__icon'
        );
        echo html_writer::start_div('my-purchases-hero__content');
        echo html_writer::tag('h2', $pagetitle, ['class' => 'my-purchases-hero__title']);
        echo html_writer::div(
            html_writer::tag('span', (string)$totalpurchases, ['class' => 'my-purchases-hero__count']) .
            html_writer::span($pagetitle, 'my-purchases-hero__label'),
            'my-purchases-hero__summary'
        );
        echo html_writer::end_div();
        echo html_writer::end_div();

        if (!$isadminview) {
            echo html_writer::div(
                html_writer::link(
                    UrlFactory::storefront(),
                    html_writer::tag('i', '', ['class' => 'fa-solid fa-arrow-up-right-from-square', 'aria-hidden' => 'true'])
                        . ' ' . get_string('commerce_mypurchases_store_link', 'local_subscriptions'),
                    ['class' => 'my-purchases-store-link']
                ),
                'my-purchases-store-link-wrap'
            );
        }

        /**
         * Achats de cours.
         */
        echo html_writer::start_div('my-purchases-section my-purchases-section--courses');
        echo html_writer::start_div('my-purchases-section__heading');
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa-solid fa-graduation-cap', 'aria-hidden' => 'true']),
            'my-purchases-section__icon'
        );
        echo html_writer::tag('h3', get_string('course_purchases_profile_title', 'local_subscriptions'), [
            'class' => 'my-purchases-section__title',
        ]);
        echo html_writer::tag('span', (string)count($subs), ['class' => 'my-purchases-section__count']);
        echo html_writer::end_div();

        if (!$subs) {
            echo $OUTPUT->notification(get_string('mysubs_empty', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
        } else {
            $subscriptionmodals = [];
            echo html_writer::start_div('my-purchases-grid');

            $planids = array_unique(array_map(static fn($s) => (int)$s->planid, $subs));
            $plans = $planids ? $DB->get_records_list('subscription_plan', 'id', $planids, '', 'id,name,is_recurring,is_trial') : [];

            foreach ($subs as $sub) {
                $nativeorder = $matchnativeorder(
                    'subscription',
                    (int)($sub->id ?? 0),
                    trim((string)($sub->commerce_reference ?? '')),
                    (int)($sub->start_date ?? $sub->creation_date ?? 0),
                    (int)round(((float)($sub->pricepaid ?? 0)) * 100)
                );
                $nativeitemlabel = $nativeorder?->items[0]->label ?? '';
                $plan = $plans[$sub->planid] ?? (object)[
                    'id' => 0,
                    'name' => $nativeitemlabel !== '' ? $nativeitemlabel : get_string('commerce_i49_course_purchase', 'local_subscriptions'),
                    'is_recurring' => 0,
                    'is_trial' => 0,
                ];

                $istrial = $detecttrial($plan, $sub);
                // Native purchases keep their immutable commercial label. This avoids an
                // upgraded Legacy chain making the original purchase look like the upgrade.
                $planname = trim((string)$nativeitemlabel) !== ''
                    ? trim((string)$nativeitemlabel)
                    : local_subscriptions_plan_display_name($plan);
                $isactive = ($sub->status === Status::ACTIVE);
                $unlimited = $isunlimited($sub->end_date ?? null);

                $cardclasses = 'card shadow-sm mb-3' . ($isactive ? '' : ' my-purchase-card--inactive');

                $head = html_writer::start_div('d-flex align-items-center justify-content-between');
                $head .= html_writer::tag('span', format_string($planname), ['class' => 'h5 m-0']);
                $head .= SubsPresenter::render_status_badge($sub->status);
                $head .= html_writer::end_div();

                $list = html_writer::start_tag('ul', ['class' => 'list-unstyled mb-2 small']);

                $datelabel = (!$istrial && $unlimited)
                    ? get_string('purchase_date', 'local_subscriptions')
                    : get_string('start_date', 'local_subscriptions');

                $list .= html_writer::tag('li',
                    html_writer::tag('span', $datelabel . ': ', ['class' => 'text-muted']) .
                    userdate((int)$sub->start_date)
                );

                if (!$unlimited) {
                    $list .= html_writer::tag('li',
                        html_writer::tag('span', get_string('end_date', 'local_subscriptions') . ': ', ['class' => 'text-muted']) .
                        userdate((int)$sub->end_date)
                    );
                }

                if (!$istrial) {
                    $list .= html_writer::tag('li',
                        html_writer::tag('span', get_string('pricepaid', 'local_subscriptions') . ': ', ['class' => 'text-muted']) .
                        $fmtmoney($sub->pricepaid ?? 0, $sub->currency ?? '')
                    );
                }

                $courses = local_subscriptions_get_courses_by_plan((int)$sub->planid);
                if ($courses) {
                    $links = [];
                    foreach ($courses as $course) {
                        $links[] = html_writer::link(
                            CommerceCustomerPublicUrlResolver::course((int)$course->id),
                            format_string($course->fullname)
                        );
                    }

                    $list .= html_writer::tag('li',
                        html_writer::tag('span', get_string('available_courses', 'local_subscriptions') . ': ', ['class' => 'text-muted']) .
                        implode(', ', $links)
                    );
                }

                if (!empty($sub->payment_failed)) {
                    $list .= html_writer::tag('li',
                        html_writer::span(get_string('payment_failed', 'local_subscriptions'), 'badge bg-warning text-dark') .
                        (!empty($sub->last_payment_failed_at) ? html_writer::span(' — ' . userdate($sub->last_payment_failed_at), 'text-muted ms-1') : ''),
                        ['class' => 'mt-1']
                    );
                }

                $list .= html_writer::end_tag('ul');

                $btns = [];

                $nativeproductsku = $resolveNativeProductSku(
                    $nativeorder,
                    ['course_access', 'course', 'subscription']
                );
                $producturl = $nativeproductsku !== ''
                    ? CommerceCustomerPublicUrlResolver::product($nativeproductsku)
                    : $legacyproductresolver->storefront_url('subscription_plan', (int)$sub->planid);
                if ($producturl !== null) {
                    $btns[] = html_writer::link(
                        $producturl,
                        html_writer::tag('i', '', [
                            'class' => 'fa-solid fa-arrow-up-right-from-square',
                            'aria-hidden' => 'true',
                        ]) . ' ' . get_string('digital_product_view_page', 'local_subscriptions'),
                        [
                            'class' => 'btn btn-outline-primary btn-sm product-page-action',
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]
                    );
                }

                if (!empty($plan->is_recurring) && $sub->payment_provider === Provider::STRIPE && !empty($sub->provider_customer_id)) {
                    $btns[] = html_writer::link(
                        UrlFactory::portal(['subid' => $sub->id]),
                        get_string('manage_billing', 'local_subscriptions'),
                        ['class' => 'btn btn-outline-primary btn-sm']
                    );

                    $list .= html_writer::div(
                        html_writer::span(get_string('badge_recurring', 'local_subscriptions'), 'badge bg-info'),
                        'mb-2'
                    );
                }

                if ($nativeorder !== null) {
                    $presentation = CommerceOrderPresentationService::create()->present($nativeorder);
                    foreach ($presentation->items as $nativeitem) {
                        foreach ($nativeitem->accesses as $access) {
                            if ($access->type === 'course_access' && $access->available && $access->url !== null) {
                                $btns[] = html_writer::link(
                                    $access->url,
                                    html_writer::tag('i', '', ['class' => 'fa-solid fa-graduation-cap', 'aria-hidden' => 'true'])
                                        . ' ' . get_string('commerce_i49_open_course', 'local_subscriptions'),
                                    ['class' => 'btn btn-primary btn-sm']
                                );
                            }
                        }
                    }
                }

                $modalid = 'subModal' . $sub->id;
                if ($nativeorder !== null) {
                    $btns[] = html_writer::link(
                        \local_subscriptions\url\UrlFactory::order_details([
                            'reference' => $nativeorder->summary->reference,
                        ]),
                        get_string('details', 'local_subscriptions'),
                        ['class' => 'btn btn-outline-secondary btn-sm']
                    );
                } else {
                    $btns[] = html_writer::tag('button', get_string('details', 'local_subscriptions'), [
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#' . $modalid,
                    ]);
                }

                $actions = new PurchaseActions($btns, 'my-purchase-card__actions');
                $card = new PurchaseCard(
                    $head,
                    $list,
                    $actions,
                    $cardclasses . ' my-purchase-card',
                    'my-purchase-card__header',
                    'course',
                    'fa-solid fa-graduation-cap'
                );
                echo $purchaseslist->render_item($card);

                $rows = SubsPresenter::rows(
                    $sub,
                    $plan,
                    function(float $amount, string $cur) use ($fmtmoney): string {
                        return $fmtmoney($amount, $cur);
                    },
                    'user'
                );

                if ($isadminview) {
                    $addsection(
                        $rows,
                        get_string('admin_subscription_details', 'local_subscriptions'),
                        'fa-solid fa-user-shield'
                    );

                    $rows[] = [get_string('subfield_id', 'local_subscriptions'), s((string)$sub->id)];
                    $rows[] = [get_string('subfield_userid', 'local_subscriptions'), s((string)$sub->userid)];
                    $rows[] = [get_string('subfield_planid', 'local_subscriptions'), s((string)$sub->planid)];

                    if (!empty($sub->payment_provider)) {
                        $rows[] = [
                            get_string('subfield_provider', 'local_subscriptions'),
                            Provider::label_with_icon_env($sub->payment_provider),
                        ];
                    }

                    if (!empty($sub->payment_request_id)) {
                        $rows[] = [get_string('subfield_payment_request_id', 'local_subscriptions'), s((string)$sub->payment_request_id)];
                    }

                    if (!empty($sub->provider_subscription_id)) {
                        $rows[] = [get_string('subfield_provider_subscription_id', 'local_subscriptions'), s($sub->provider_subscription_id)];
                    }

                    if (!empty($sub->provider_customer_id)) {
                        $rows[] = [get_string('subfield_provider_customer_id', 'local_subscriptions'), s($sub->provider_customer_id)];
                    }

                    if (!empty($sub->transactionid)) {
                        $rows[] = [get_string('subfield_txn', 'local_subscriptions'), s($sub->transactionid)];
                    }

                    if (!empty($sub->renewal_date)) {
                        $rows[] = [get_string('subfield_renewal_date', 'local_subscriptions'), userdate((int)$sub->renewal_date)];
                    }

                    $paymentrequest = null;

                    if (!empty($sub->payment_request_id)) {
                        $paymentrequest = $DB->get_record(
                            'subscription_payment_request',
                            ['id' => (int)$sub->payment_request_id],
                            '*',
                            IGNORE_MISSING
                        );
                    }

                    if (!$paymentrequest) {
                        $paymentrequest = $DB->get_record_sql("
                            SELECT *
                            FROM {subscription_payment_request}
                            WHERE subscriptionid = :subscriptionid
                        ORDER BY id DESC
                        ", [
                            'subscriptionid' => (int)$sub->id,
                        ], IGNORE_MULTIPLE);
                    }

                    if ($paymentrequest) {
                        $addsection(
                            $rows,
                            get_string('admin_payment_request_details', 'local_subscriptions'),
                            'fa-solid fa-credit-card'
                        );

                        $rows[] = [get_string('subfield_payment_request_id', 'local_subscriptions'), s((string)$paymentrequest->id)];

                        if (!empty($paymentrequest->operation)) {
                            $rows[] = [get_string('subfield_operation', 'local_subscriptions'), s($paymentrequest->operation)];
                        }

                        if (!empty($paymentrequest->status)) {
                            $rows[] = [
                                get_string('subfield_status', 'local_subscriptions'),
                                SubsPresenter::render_status_badge($paymentrequest->status),
                            ];
                        }

                        if (!empty($paymentrequest->payment_provider)) {
                            $rows[] = [
                                get_string('subfield_provider', 'local_subscriptions'),
                                Provider::label_with_icon_env($paymentrequest->payment_provider),
                            ];
                        }

                        if (!empty($paymentrequest->sessionid)) {
                            $rows[] = [get_string('subfield_sessionid', 'local_subscriptions'), s($paymentrequest->sessionid)];
                        }

                        if (!empty($paymentrequest->transactionid)) {
                            $rows[] = [get_string('subfield_txn', 'local_subscriptions'), s($paymentrequest->transactionid)];
                        }

                        if (!empty($paymentrequest->amount_minor)) {
                            $rows[] = [get_string('subfield_amount_minor', 'local_subscriptions'), s((string)$paymentrequest->amount_minor)];
                        }

                        if (isset($paymentrequest->locked_list_price)) {
                            $rows[] = [
                                get_string('subfield_locked_list_price', 'local_subscriptions'),
                                $fmtmoney($paymentrequest->locked_list_price, $paymentrequest->currency ?? ''),
                            ];
                        }

                        if (!empty($paymentrequest->locked_discount_percent)) {
                            $rows[] = [
                                get_string('subfield_locked_discount_percent', 'local_subscriptions'),
                                s((string)$paymentrequest->locked_discount_percent) . ' %',
                            ];
                        }

                        if (!empty($paymentrequest->locked_discount_amount)) {
                            $rows[] = [
                                get_string('subfield_locked_discount_amount', 'local_subscriptions'),
                                $fmtmoney($paymentrequest->locked_discount_amount, $paymentrequest->currency ?? ''),
                            ];
                        }

                        if (!empty($paymentrequest->locked_discount_reason)) {
                            $rows[] = [get_string('subfield_locked_discount_reason', 'local_subscriptions'), s($paymentrequest->locked_discount_reason)];
                        }

                        if (!empty($paymentrequest->locked_at)) {
                            $rows[] = [get_string('subfield_locked_at', 'local_subscriptions'), userdate((int)$paymentrequest->locked_at)];
                        }

                        if (!empty($paymentrequest->creation_date)) {
                            $rows[] = [get_string('subfield_created_at', 'local_subscriptions'), userdate((int)$paymentrequest->creation_date)];
                        }

                        if (!empty($paymentrequest->last_update)) {
                            $rows[] = [get_string('subfield_updated_at', 'local_subscriptions'), userdate((int)$paymentrequest->last_update)];
                        }

                        if (!empty($paymentrequest->payment_date)) {
                            $rows[] = [get_string('subfield_paid_at', 'local_subscriptions'), userdate((int)$paymentrequest->payment_date)];
                        }

                        if (!empty($paymentrequest->expiration_date)) {
                            $rows[] = [get_string('subfield_expires_at', 'local_subscriptions'), userdate((int)$paymentrequest->expiration_date)];
                        }

                        if (!empty($paymentrequest->attempts)) {
                            $rows[] = [get_string('subfield_attempts', 'local_subscriptions'), s((string)$paymentrequest->attempts)];
                        }

                        if (!empty($paymentrequest->last_attempt)) {
                            $rows[] = [get_string('subfield_last_attempt', 'local_subscriptions'), userdate((int)$paymentrequest->last_attempt)];
                        }

                        if (!empty($paymentrequest->last_error)) {
                            $rows[] = [
                                get_string('subfield_last_error', 'local_subscriptions'),
                                html_writer::tag('pre', s($paymentrequest->last_error), [
                                    'class' => 'small mb-0 text-danger',
                                    'style' => 'white-space:pre-wrap;max-height:180px;overflow:auto;',
                                ]),
                            ];
                        }

                        if (!empty($paymentrequest->created_ip)) {
                            $rows[] = [get_string('subfield_created_ip', 'local_subscriptions'), s($paymentrequest->created_ip)];
                        }

                        if (!empty($paymentrequest->accept_language)) {
                            $rows[] = [get_string('subfield_accept_language', 'local_subscriptions'), s($paymentrequest->accept_language)];
                        }

                        if (!empty($paymentrequest->http_referer)) {
                            $rows[] = [
                                get_string('subfield_http_referer', 'local_subscriptions'),
                                html_writer::link($paymentrequest->http_referer, s($paymentrequest->http_referer), [
                                    'target' => '_blank',
                                    'rel' => 'noopener',
                                ]),
                            ];
                        }

                        if (!empty($paymentrequest->payment_link)) {
                            $rows[] = [
                                get_string('subfield_payment_link', 'local_subscriptions'),
                                html_writer::link($paymentrequest->payment_link, s($paymentrequest->payment_link), [
                                    'target' => '_blank',
                                    'rel' => 'noopener',
                                ]),
                            ];
                        }

                        if (!empty($paymentrequest->response_json)) {
                            $rows[] = [
                                get_string('subfield_response_json', 'local_subscriptions'),
                                html_writer::tag('pre', s($paymentrequest->response_json), [
                                    'class' => 'small mb-0',
                                    'style' => 'white-space:pre-wrap;max-height:260px;overflow:auto;',
                                ]),
                            ];
                        }

                        if (!empty($paymentrequest->created_useragent)) {
                            $rows[] = [
                                get_string('subfield_created_useragent', 'local_subscriptions'),
                                html_writer::tag('pre', s($paymentrequest->created_useragent), [
                                    'class' => 'small mb-0',
                                    'style' => 'white-space:pre-wrap;max-height:120px;overflow:auto;',
                                ]),
                            ];
                        }
                    }
                }

                $subscriptionmodals[] = (new PurchaseDetailModal(
                    $modalid,
                    get_string('subscription_details', 'local_subscriptions'),
                    $rows
                ))->render($OUTPUT);
            }

            echo html_writer::end_div();
            echo implode('', $subscriptionmodals);
        }
        echo html_writer::end_div();

        /**
         * Achats digitaux.
         */
        echo html_writer::start_div('my-purchases-section my-purchases-section--digital');
        echo html_writer::start_div('my-purchases-section__heading');
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa-solid fa-file-arrow-down', 'aria-hidden' => 'true']),
            'my-purchases-section__icon'
        );
        echo html_writer::tag('h3', get_string('digital_purchases_profile_title', 'local_subscriptions'), [
            'class' => 'my-purchases-section__title',
        ]);
        echo html_writer::tag('span', (string)count($digitalpurchases), ['class' => 'my-purchases-section__count']);
        echo html_writer::end_div();

        $lang = strtolower(substr(current_language(), 0, 2));

        if ($digitalpurchases) {
            $productids = array_values(array_unique(array_filter(array_map(
                static fn($purchase): int => (int)($purchase->productid ?? 0),
                $digitalpurchases
            ))));
            $products = $productids
                ? $DB->get_records_list('subscription_digital_product', 'id', $productids)
                : [];
            $translations = [];
            if ($productids) {
                $translationrecords = $DB->get_records_list(
                    'subscription_digital_product_lang',
                    'productid',
                    $productids
                );
                foreach ($translationrecords as $translation) {
                    $translations[(int)$translation->productid][(string)$translation->lang] = $translation;
                }
            }
            foreach ($digitalpurchases as $purchase) {
                $product = $products[(int)$purchase->productid] ?? null;
                $currenttranslation = $translations[(int)$purchase->productid][$lang] ?? null;
                $frenchtranslation = $translations[(int)$purchase->productid]['fr'] ?? null;
                $purchase->slug = $product->slug
                    ?? ($purchase->productslug ?? null);
                $purchase->mobile_filename = $product->mobile_filename ?? null;
                $purchase->productname = $currenttranslation->title
                    ?? $frenchtranslation->title
                    ?? $product->name
                    ?? ($purchase->productname ?? null)
                    ?? ('Digital product #' . (int)$purchase->productid);
            }
        }

        if (!$digitalpurchases) {
            echo $OUTPUT->notification(get_string('digital_purchases_empty', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
        } else {
            $digitalmodals = [];
            echo html_writer::start_div('my-purchases-grid');

            foreach ($digitalpurchases as $purchase) {
                $productname = format_string($purchase->productname ?? '');
                $purchasedate = !empty($purchase->payment_date)
                    ? userdate((int)$purchase->payment_date)
                    : userdate((int)$purchase->creation_date);

                $downloadlinks = [];

                if (!empty($purchase->download_token)) {
                    $downloadlinks[] = html_writer::link(
                        UrlFactory::digital_download(['token' => $purchase->download_token]),
                        html_writer::tag('i', '', ['class' => 'fa-solid fa-download', 'aria-hidden' => 'true']) . ' ' . get_string('digital_download_classic', 'local_subscriptions'),
                        ['class' => 'btn btn-outline-primary btn-sm']
                    );

                    if (!empty($purchase->mobile_filename)) {
                        $downloadlinks[] = html_writer::link(
                            UrlFactory::digital_download([
                                'token' => $purchase->download_token,
                                'version' => 'mobile',
                            ]),
                            html_writer::tag('i', '', ['class' => 'fa-solid fa-mobile-screen-button', 'aria-hidden' => 'true']) . ' ' . get_string('digital_download_mobile', 'local_subscriptions'),
                            ['class' => 'btn btn-outline-primary btn-sm']
                        );
                    }
                }

                $modalid = 'digitalPurchaseModal' . $purchase->id;

                $head = html_writer::start_div('d-flex align-items-center justify-content-between');
                $head .= html_writer::tag('span', $productname, ['class' => 'h5 m-0']);
                $head .= SubsPresenter::render_status_badge($purchase->status);
                $head .= html_writer::end_div();

                $list = html_writer::start_tag('ul', ['class' => 'list-unstyled mb-2 small']);

                $list .= html_writer::tag('li',
                    html_writer::tag('span', get_string('digital_purchase_date', 'local_subscriptions') . ': ', ['class' => 'text-muted']) .
                    $purchasedate
                );

                $list .= html_writer::tag('li',
                    html_writer::tag('span', get_string('pricepaid', 'local_subscriptions') . ': ', ['class' => 'text-muted']) .
                    $fmtmoney($purchase->price ?? 0, $purchase->currency ?? '')
                );

                $list .= html_writer::end_tag('ul');

                $btns = [];

                $nativeorder = $matchnativeorder(
                    'digital',
                    (int)($purchase->id ?? 0),
                    trim((string)($purchase->commerce_reference ?? '')),
                    (int)($purchase->payment_date ?? $purchase->creation_date ?? 0),
                    (int)round(((float)($purchase->price ?? 0)) * 100)
                );

                $nativeproductsku = $resolveNativeProductSku(
                    $nativeorder,
                    ['digital_download', 'digital']
                );
                $producturl = $nativeproductsku !== ''
                    ? CommerceCustomerPublicUrlResolver::product($nativeproductsku)
                    : $legacyproductresolver->storefront_url(
                        'subscription_digital_product',
                        (int)($purchase->productid ?? 0)
                    );
                if ($producturl !== null) {
                    $btns[] = html_writer::link(
                        $producturl,
                        html_writer::tag('i', '', [
                            'class' => 'fa-solid fa-arrow-up-right-from-square',
                            'aria-hidden' => 'true',
                        ]) . ' ' . get_string('digital_product_view_page', 'local_subscriptions'),
                        [
                            'class' => 'btn btn-outline-primary btn-sm product-page-action',
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]
                    );
                }

                $btns = array_merge($btns, $downloadlinks);

                if ($nativeorder !== null) {
                    $btns[] = html_writer::link(
                        \local_subscriptions\url\UrlFactory::order_details([
                            'reference' => $nativeorder->summary->reference,
                        ]),
                        get_string('details', 'local_subscriptions'),
                        ['class' => 'btn btn-outline-secondary btn-sm']
                    );
                } else {
                    $btns[] = html_writer::tag('button', get_string('details', 'local_subscriptions'), [
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#' . $modalid,
                    ]);
                }

                $actions = new PurchaseActions($btns, 'my-purchase-card__actions');
                $card = new PurchaseCard(
                    $head,
                    $list,
                    $actions,
                    'card shadow-sm mb-3 my-purchase-card',
                    'my-purchase-card__header',
                    'digital',
                    'fa-solid fa-file-arrow-down'
                );
                echo $purchaseslist->render_item($card);

                $rows = [
                    [get_string('digital_product', 'local_subscriptions'), $productname],
                    [get_string('status', 'local_subscriptions'), SubsPresenter::render_status_badge($purchase->status)],
                ];

                if (!empty($downloadlinks)) {
                    $rows[] = [get_string('digital_purchase_downloads', 'local_subscriptions'), implode(' ', $downloadlinks)];
                }

                if (!empty($purchase->payment_provider)) {
                    $rows[] = [
                        get_string('subfield_provider', 'local_subscriptions'),
                        Provider::label_with_icon_env($purchase->payment_provider),
                    ];
                }

                if ($isadminview) {
                    $addsection(
                        $rows,
                        get_string('admin_details', 'local_subscriptions'),
                        'fa-solid fa-user-shield'
                    );
                    $rows[] = [get_string('subfield_id', 'local_subscriptions'), s((string)$purchase->id)];
                    $rows[] = [get_string('subfield_userid', 'local_subscriptions'), s((string)($purchase->userid ?? ''))];
                    $rows[] = [get_string('subfield_productid', 'local_subscriptions'), s((string)($purchase->productid ?? ''))];
                    $rows[] = [get_string('subfield_slug', 'local_subscriptions'), s($purchase->slug ?? '')];

                    if (!empty($purchase->paymentid)) {
                        $rows[] = [get_string('subfield_paymentid', 'local_subscriptions'), s($purchase->paymentid)];
                    }

                    if (!empty($purchase->provider_paymentid)) {
                        $rows[] = [get_string('subfield_provider_paymentid', 'local_subscriptions'), s($purchase->provider_paymentid)];
                    }

                    if (!empty($purchase->transactionid)) {
                        $rows[] = [get_string('subfield_txn', 'local_subscriptions'), s($purchase->transactionid)];
                    }

                    if (!empty($purchase->creation_date)) {
                        $rows[] = [get_string('subfield_created_at', 'local_subscriptions'), userdate((int)$purchase->creation_date)];
                    }

                    if (!empty($purchase->expiration_date)) {
                        $rows[] = [get_string('subfield_expires_at', 'local_subscriptions'), userdate((int)$purchase->expiration_date)];
                    }

                    if (!empty($purchase->checkout_url)) {
                        $rows[] = [
                            get_string('subfield_checkout_url', 'local_subscriptions'),
                            html_writer::link($purchase->checkout_url, s($purchase->checkout_url), ['target' => '_blank', 'rel' => 'noopener']),
                        ];
                    }

                    if (!empty($purchase->success_url)) {
                        $rows[] = [
                            get_string('subfield_success_url', 'local_subscriptions'),
                            html_writer::link($purchase->success_url, s($purchase->success_url), ['target' => '_blank', 'rel' => 'noopener']),
                        ];
                    }

                    if (!empty($purchase->cancel_url)) {
                        $rows[] = [
                            get_string('subfield_cancel_url', 'local_subscriptions'),
                            html_writer::link($purchase->cancel_url, s($purchase->cancel_url), ['target' => '_blank', 'rel' => 'noopener']),
                        ];
                    }

                    if (!empty($purchase->download_token)) {
                        $rows[] = [get_string('subfield_download_token', 'local_subscriptions'), s($purchase->download_token)];
                    }

                    if (!empty($purchase->buyer_lang)) {
                        $rows[] = [get_string('language'), s($purchase->buyer_lang)];
                    }

                    if (!empty($purchase->raw_response)) {
                        $rows[] = [
                            get_string('subfield_raw_response', 'local_subscriptions'),
                            html_writer::tag('pre', s($purchase->raw_response), [
                                'class' => 'small mb-0',
                                'style' => 'white-space:pre-wrap;max-height:260px;overflow:auto;',
                            ]),
                        ];
                    }
                }

                $digitalmodals[] = (new PurchaseDetailModal(
                    $modalid,
                    get_string('digital_purchase_details', 'local_subscriptions'),
                    $rows
                ))->render($OUTPUT);
            }

            echo html_writer::end_div();
            echo implode('', $digitalmodals);
        }
        echo html_writer::end_div();

        if ($bundleorders !== []) {
            echo html_writer::start_div('my-purchases-section my-purchases-section--bundles');
            echo html_writer::start_div('my-purchases-section__heading');
            echo html_writer::div(
                html_writer::tag('i', '', ['class' => 'fa-solid fa-box-open', 'aria-hidden' => 'true']),
                'my-purchases-section__icon'
            );
            echo html_writer::tag('h3', get_string('commerce_i49_bundle_purchases', 'local_subscriptions'), [
                'class' => 'my-purchases-section__title',
            ]);
            echo html_writer::tag('span', (string)count($bundleorders), ['class' => 'my-purchases-section__count']);
            echo html_writer::end_div();
            echo html_writer::start_div('my-purchases-grid');

            foreach ($bundleorders as $bundleorder) {
                $bundleitem = null;
                foreach ($bundleorder->items as $candidateitem) { if (strtolower($candidateitem->type) === 'bundle') { $bundleitem = $candidateitem; break; } }
                $components = $bundleitem === null ? [] : $bundlecomponentresolver->resolve($bundleitem);
                $ismultiitemorder = $bundleitem === null && count($bundleorder->items) > 1;
                $title = trim((string)($bundleorder->metadata['label'] ?? $bundleorder->metadata['product_name'] ?? ''));
                if ($ismultiitemorder) {
                    $publicreference = (new CommercePublicOrderReference())->from_internal(
                        $bundleorder->reference,
                        $bundleorder->timecreated
                    );
                    $title = get_string('commerce_multi_item_order_title', 'local_subscriptions', $publicreference);
                } elseif ($title === '' && $bundleitem !== null) {
                    $title = trim((string)$bundleitem->label);
                }
                if ($title === '') { $title = get_string('commerce_i49_bundle_default_name', 'local_subscriptions'); }
                $head = html_writer::start_div('my-purchase-card__title-row');
                $head .= html_writer::tag('span', format_string($title), ['class' => 'h5 m-0 my-purchase-card__title']);
                $head .= html_writer::span(get_string('commerce_i411_paid_badge', 'local_subscriptions'), 'crm-commerce-status-badge crm-commerce-status-active my-purchase-card__status');
                $head .= html_writer::end_div();

                $list = html_writer::start_tag('ul', ['class' => 'list-unstyled mb-2 small my-purchase-bundle-items']);
                $list .= html_writer::tag('li', html_writer::tag('span', get_string('purchase_date', 'local_subscriptions') . ': ', ['class' => 'text-muted']) . userdate($bundleorder->timecreated));
                $list .= html_writer::tag('li', html_writer::tag('span', get_string('pricepaid', 'local_subscriptions') . ': ', ['class' => 'text-muted']) . $fmtmoney($bundleorder->totalminor / 100, $bundleorder->currency));
                $listeditems = $components;
                if ($ismultiitemorder) {
                    $listeditems = array_map(static fn($item): array => [
                        'quantity' => max(1, (int)$item->quantity),
                        'name' => format_string((string)$item->label),
                    ], $bundleorder->items);
                }
                foreach ($listeditems as $component) {
                    $list .= html_writer::tag(
                        'li',
                        '<strong>' . (int)$component['quantity'] . ' ×</strong> ' . s((string)$component['name']),
                        ['class' => 'my-purchase-bundle-items__item']
                    );
                }
                $list .= html_writer::end_tag('ul');

                $btns = [];
                $sku = $bundleitem === null ? '' : trim((string)($bundleitem->metadata['productsku'] ?? $bundleitem->metadata['sku'] ?? $bundleitem->reference));
                if ($sku !== '') {
                    $btns[] = html_writer::link(
                        CommerceCustomerPublicUrlResolver::product($sku),
                        html_writer::tag('i', '', [
                            'class' => 'fa-solid fa-arrow-up-right-from-square',
                            'aria-hidden' => 'true',
                        ]) . ' ' . get_string('commerce_i411_product_page', 'local_subscriptions'),
                        [
                            'class' => 'btn btn-outline-primary btn-sm product-page-action',
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]
                    );
                }
                $btns[] = html_writer::link(
                    \local_subscriptions\url\UrlFactory::order_details(['reference' => $bundleorder->reference]),
                    get_string('details', 'local_subscriptions'),
                    ['class' => 'btn btn-outline-secondary btn-sm']
                );
                $card = new PurchaseCard(
                    $head,
                    $list,
                    new PurchaseActions($btns, 'my-purchase-card__actions'),
                    'card shadow-sm mb-3 my-purchase-card',
                    'my-purchase-card__header',
                    'bundle',
                    'fa-solid fa-box-open'
                );
                echo $purchaseslist->render_item($card);
            }
            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        return (string)ob_get_clean();
        }
}