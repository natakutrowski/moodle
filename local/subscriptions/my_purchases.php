<?php
// local/subscriptions/my_purchases.php.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/user_subs_lib.php');

use local_subscriptions\constants\Status;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\support\SubsPresenter;

require_login();

if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

global $DB, $OUTPUT, $USER;

$requesteduserid = optional_param('userid', 0, PARAM_INT);
$targetuserid = $USER->id;

if ($requesteduserid > 0 && $requesteduserid !== (int)$USER->id) {
    $targetcontext = context_user::instance($requesteduserid, IGNORE_MISSING);

    if ($targetcontext && has_capability('moodle/user:viewdetails', $targetcontext)) {
        $targetuserid = $requesteduserid;
    } else {
        redirect(UrlFactory::my_purchases());
    }
}

$targetuser = core_user::get_user($targetuserid, '*', MUST_EXIST);
$isadminview = ((int)$targetuser->id !== (int)$USER->id);

$PAGE->set_context(context_user::instance($targetuserid));
$PAGE->set_url(UrlFactory::my_purchases(), $targetuserid !== (int)$USER->id ? ['userid' => $targetuserid] : []);
$PAGE->set_pagelayout('standard');

$pagetitle = ($targetuserid === (int)$USER->id)
    ? get_string('mysubs_title', 'local_subscriptions')
    : get_string('user_purchases_title', 'local_subscriptions', fullname($targetuser));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

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

$renderdetailmodal = static function(string $modalid, string $title, array $rows): string {
    $table = html_writer::start_tag('table', ['class' => 'table table-sm mb-0']);

    foreach ($rows as $row) {
        if (!empty($row['section'])) {
            $table .= html_writer::tag(
                'tr',
                html_writer::tag(
                    'th',
                    html_writer::tag('i', '', [
                        'class' => ($row['icon'] ?? 'fa-solid fa-circle-info') . ' me-2',
                        'aria-hidden' => 'true',
                    ]) .
                    format_string($row['section']),
                    [
                        'colspan' => 2,
                        'class' => 'bg-light fw-bold text-dark border-top',
                    ]
                )
            );

            continue;
        }

        [$k, $v] = $row;

        $table .= '<tr>';
        $table .= '<th class="text-muted" style="width:28%;white-space:nowrap;">' . s($k) . '</th>';
        $table .= '<td class="fw-semibold">' . $v . '</td>';
        $table .= '</tr>';
    }

    $table .= html_writer::end_tag('table');

    $html = html_writer::start_div('modal fade', [
        'id' => $modalid,
        'tabindex' => '-1',
        'aria-hidden' => 'true',
    ]);

    $html .= html_writer::start_div('modal-dialog modal-lg modal-dialog-scrollable');
    $html .= html_writer::start_div('modal-content');

    $html .= html_writer::div(
        html_writer::tag('h5', $title, ['class' => 'modal-title']) .
        html_writer::tag('button', '', [
            'type' => 'button',
            'class' => 'btn-close',
            'data-bs-dismiss' => 'modal',
            'aria-label' => 'Close',
        ]),
        'modal-header d-flex align-items-center justify-content-between'
    );

    $html .= html_writer::div($table, 'modal-body bg-light');

    $html .= html_writer::div(
        html_writer::tag('button', get_string('close', 'local_subscriptions'), [
            'class' => 'btn btn-secondary',
            'data-bs-dismiss' => 'modal',
        ]),
        'modal-footer'
    );

    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    return $html;
};

$addsection = static function(array &$rows, string $title, string $icon = 'fa-solid fa-circle-info'): void {
    $rows[] = [
        'section' => $title,
        'icon' => $icon,
    ];
};


echo $OUTPUT->header();

echo html_writer::tag('h3', get_string('mysubs_title', 'local_subscriptions'), ['class' => 'mb-4']);

/**
 * Achats de cours.
 */
echo html_writer::tag('h4', get_string('course_purchases_profile_title', 'local_subscriptions'), ['class' => 'mb-3']);

$subs = $DB->get_records('user_subscription', ['userid' => $targetuser->id], 'end_date DESC');

if (!$subs) {
    echo $OUTPUT->notification(get_string('mysubs_empty', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
    echo html_writer::div(
        html_writer::link(UrlFactory::subscribe(), get_string('subscribe', 'local_subscriptions'), ['class' => 'btn btn-primary']),
        'mt-3 mb-5'
    );
} else {
    $planids = array_unique(array_map(static fn($s) => (int)$s->planid, $subs));
    $plans = $planids ? $DB->get_records_list('subscription_plan', 'id', $planids, '', 'id,name,is_recurring,is_trial') : [];

    foreach ($subs as $sub) {
        $plan = $plans[$sub->planid] ?? (object)[
            'id' => 0,
            'name' => get_string('unknown_plan', 'local_subscriptions'),
            'is_recurring' => 0,
            'is_trial' => 0,
        ];

        $istrial = $detecttrial($plan, $sub);
        $planname = local_subscriptions_plan_display_name($plan);
        $isactive = ($sub->status === Status::ACTIVE);
        $unlimited = $isunlimited($sub->end_date ?? null);

        $cardclasses = 'card shadow-sm mb-3' . ($isactive ? '' : ' border-0 bg-light');

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
                    new moodle_url('/course/view.php', ['id' => $course->id]),
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

        $modalid = 'subModal' . $sub->id;

        $btns[] = html_writer::tag('button', get_string('details', 'local_subscriptions'), [
            'class' => 'btn btn-outline-secondary btn-sm',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#' . $modalid,
        ]);

        echo html_writer::start_div($cardclasses);
        echo html_writer::div($head, 'card-header bg-white');
        echo html_writer::start_div('card-body');
        echo $list;
        echo html_writer::div(implode(' ', $btns), 'mt-2');
        echo html_writer::end_div();
        echo html_writer::end_div();

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

        echo $renderdetailmodal(
            $modalid,
            get_string('subscription_details', 'local_subscriptions'),
            $rows
        );
    }
}

/**
 * Achats digitaux.
 */
echo html_writer::tag('h4', get_string('digital_purchases_profile_title', 'local_subscriptions'), ['class' => 'mt-5 mb-3']);

$lang = strtolower(substr(current_language(), 0, 2));

$sql = "
    SELECT
        pr.*,
        p.slug,
        p.mobile_filename,
        COALESCE(NULLIF(tcur.title, ''), NULLIF(tfr.title, ''), p.name) AS productname
      FROM {subscription_digital_payment_request} pr
      JOIN {subscription_digital_product} p ON p.id = pr.productid
 LEFT JOIN {subscription_digital_product_lang} tcur
        ON tcur.productid = p.id AND tcur.lang = :lang
 LEFT JOIN {subscription_digital_product_lang} tfr
        ON tfr.productid = p.id AND tfr.lang = 'fr'
     WHERE pr.status IN ('paid', 'completed')
       AND (
            pr.userid = :userid
            OR " . $DB->sql_compare_text('pr.email') . " = " . $DB->sql_compare_text(':useremail') . "
       )
  ORDER BY COALESCE(pr.payment_date, pr.creation_date) DESC, pr.id DESC
";

$digitalpurchases = $DB->get_records_sql($sql, [
    'lang' => $lang,
    'userid' => $targetuser->id,
    'useremail' => $targetuser->email,
]);

if (!$digitalpurchases) {
    echo $OUTPUT->notification(get_string('digital_purchases_empty', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
} else {
    foreach ($digitalpurchases as $purchase) {
        $productname = format_string($purchase->productname ?? '');
        $purchasedate = !empty($purchase->payment_date)
            ? userdate((int)$purchase->payment_date)
            : userdate((int)$purchase->creation_date);

        $downloadlinks = [];

        if (!empty($purchase->download_token)) {
            $downloadlinks[] = html_writer::link(
                UrlFactory::digital_download(['token' => $purchase->download_token]),
                get_string('digital_download_classic', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary btn-sm']
            );

            if (!empty($purchase->mobile_filename)) {
                $downloadlinks[] = html_writer::link(
                    UrlFactory::digital_download([
                        'token' => $purchase->download_token,
                        'version' => 'mobile',
                    ]),
                    get_string('digital_download_mobile', 'local_subscriptions'),
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

        $list .= html_writer::tag('li',
            html_writer::tag('span', get_string('email') . ': ', ['class' => 'text-muted']) .
            s($purchase->email ?? '')
        );

        $list .= html_writer::end_tag('ul');

        $btns = [];

        if (!empty($purchase->slug)) {
            $btns[] = html_writer::link(
                UrlFactory::digital_product($purchase->slug),
                get_string('digital_product_view_page', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary btn-sm']
            );
        }

        $btns = array_merge($btns, $downloadlinks);

        $btns[] = html_writer::tag('button', get_string('details', 'local_subscriptions'), [
            'class' => 'btn btn-outline-secondary btn-sm',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#' . $modalid,
        ]);

        echo html_writer::start_div('card shadow-sm mb-3');
        echo html_writer::div($head, 'card-header bg-white');
        echo html_writer::start_div('card-body');
        echo $list;
        echo html_writer::div(implode(' ', $btns), 'mt-2 d-flex flex-wrap gap-2');
        echo html_writer::end_div();
        echo html_writer::end_div();

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

        echo $renderdetailmodal(
            $modalid,
            get_string('digital_purchase_details', 'local_subscriptions'),
            $rows
        );
    }
}

echo $OUTPUT->footer();