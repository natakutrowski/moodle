<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/config.php';

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutCrmService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$useridfilter = optional_param('userid', 0, PARAM_INT);
$classfilter = optional_param('class', '', PARAM_ALPHANUMEXT);

$url = new moodle_url('/local/subscriptions/admin/commerce/unfinished-checkouts/index.php');
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    get_string('commerce_guest_crm_title', 'local_subscriptions'),
    'local-subscriptions-commerce-unfinished-checkouts-page'
);

$service = CommerceUnfinishedGuestCheckoutCrmService::create();
$rows = $service->queue();

if ($useridfilter > 0) {
    $rows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => (int)$row['userid'] === $useridfilter
    ));
}
if ($classfilter !== '') {
    $rows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => $row['classification'] === $classfilter
    ));
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_guest_crm_title', 'local_subscriptions'),
        'url' => null,
    ],
]);
echo CrmPageHeader::render(
    get_string('commerce_guest_crm_title', 'local_subscriptions'),
    get_string('commerce_guest_crm_help', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::UNFINISHED_CHECKOUTS,
    $context
);

echo html_writer::start_div('d-flex flex-wrap gap-2 mb-4');
foreach ([
    '' => 'commerce_guest_crm_filter_all',
    'pending_purchase' => 'commerce_guest_crm_class_pending_purchase',
    'multiple_pending' => 'commerce_guest_crm_class_multiple_pending',
    'provisional_no_purchase' => 'commerce_guest_crm_class_provisional_no_purchase',
] as $class => $label) {
    echo html_writer::link(
        new moodle_url($url, $class !== '' ? ['class' => $class] : []),
        get_string($label, 'local_subscriptions'),
        ['class' => 'btn btn-sm ' . ($classfilter === $class ? 'btn-primary' : 'btn-outline-secondary')]
    );
}
echo html_writer::end_div();

if ($rows === []) {
    echo html_writer::div(
        get_string('commerce_guest_crm_empty', 'local_subscriptions'),
        'alert alert-success'
    );
} else {
    foreach ($rows as $row) {
        $pending = array_values(array_filter(
            $row['purchases'],
            static fn($purchase): bool => (string)$purchase->status === 'payment_pending'
        ));

        echo html_writer::start_div('card mb-3 shadow-sm');
        echo html_writer::start_div('card-body');

        echo html_writer::div(
            html_writer::tag('h3', s($row['email']), ['class' => 'h5 mb-1']) .
            html_writer::div(
                '#' . (int)$row['userid'] . ' · ' . s($row['username']),
                'text-muted small'
            ),
            'mb-3'
        );

        echo html_writer::span(
            get_string('commerce_guest_crm_class_' . $row['classification'], 'local_subscriptions'),
            'badge text-bg-warning mb-3'
        );

        echo html_writer::tag(
            'p',
            get_string(
                'commerce_guest_crm_source_summary',
                'local_subscriptions',
                (object)[
                    'session' => (int)$row['source_session_id'],
                    'status' => $row['source_status'],
                    'purchase' => $row['purchase_reference'] ?: '—',
                    'stuck' => (int)$row['stuck_sessions'],
                ]
            ),
            ['class' => 'small text-muted']
        );

        echo html_writer::start_div('d-flex flex-wrap gap-2 mb-3');
        echo html_writer::link(
            new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => (int)$row['userid']]),
            get_string('commerce_guest_crm_user360', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );

        if ((int)$row['stuck_sessions'] > 0) {
            echo html_writer::start_tag('form', [
                'method' => 'post',
                'action' => (new moodle_url('/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'))->out(false),
                'class' => 'd-inline',
            ]);
            foreach ([
                'sesskey' => sesskey(),
                'action' => 'repair',
                'userid' => (int)$row['userid'],
            ] as $name => $value) {
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
            }
            echo html_writer::tag(
                'button',
                get_string('commerce_guest_crm_repair', 'local_subscriptions'),
                ['type' => 'submit', 'class' => 'btn btn-sm btn-primary']
            );
            echo html_writer::end_tag('form');
        }
        echo html_writer::end_div();

        if ($pending !== []) {
            echo html_writer::tag(
                'h4',
                get_string('commerce_guest_crm_pending_purchases', 'local_subscriptions'),
                ['class' => 'h6']
            );

            foreach ($pending as $purchase) {
                $isresume = (string)$row['purchase_reference'] === (string)$purchase->reference;
                echo html_writer::start_div('border rounded p-3 mb-2');
                echo html_writer::div(
                    html_writer::tag('code', s($purchase->reference)) .
                    ' · ' . s($purchase->currency) .
                    ' · ' . (int)$purchase->totalminor,
                    'mb-2'
                );

                echo html_writer::start_div('d-flex flex-wrap gap-2');
                echo html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['reference' => $purchase->reference]),
                    get_string('commerce_guest_crm_open_purchase', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );

                if (!$isresume) {
                    echo html_writer::start_tag('form', [
                        'method' => 'post',
                        'action' => (new moodle_url('/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'))->out(false),
                    ]);
                    foreach ([
                        'sesskey' => sesskey(),
                        'action' => 'selectpurchase',
                        'userid' => (int)$row['userid'],
                        'reference' => $purchase->reference,
                    ] as $name => $value) {
                        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
                    }
                    echo html_writer::tag(
                        'button',
                        get_string('commerce_guest_crm_use_for_resume', 'local_subscriptions'),
                        ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']
                    );
                    echo html_writer::end_tag('form');
                } else {
                    echo html_writer::span(
                        get_string('commerce_guest_crm_current_resume', 'local_subscriptions'),
                        'badge text-bg-success align-self-center'
                    );
                }
                echo html_writer::end_div();

                $purchasepayments = array_values(array_filter(
                    $row['payments'],
                    static fn($payment): bool => (string)$payment->purchasereference === (string)$purchase->reference
                ));
                foreach ($purchasepayments as $payment) {
                    echo html_writer::start_div('small mt-2');
                    echo s((string)$payment->provider) . ' · #' . (int)$payment->id . ' · ' . s((string)$payment->status);
                    if (in_array(strtolower((string)$payment->provider), ['alfa', 'stripe'], true)
                            && in_array((string)$payment->status, ['created', 'redirected', 'pending'], true)) {
                        echo ' ';
                        echo html_writer::start_tag('form', [
                            'method' => 'post',
                            'action' => (new moodle_url('/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'))->out(false),
                            'class' => 'd-inline',
                        ]);
                        foreach ([
                            'sesskey' => sesskey(),
                            'action' => 'reconcile',
                            'userid' => (int)$row['userid'],
                            'paymentid' => (int)$payment->id,
                        ] as $name => $value) {
                            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
                        }
                        echo html_writer::tag(
                            'button',
                            get_string('commerce_guest_crm_check_provider', 'local_subscriptions'),
                            ['type' => 'submit', 'class' => 'btn btn-link btn-sm p-0 align-baseline']
                        );
                        echo html_writer::end_tag('form');
                    }
                    echo html_writer::end_div();
                }

                echo html_writer::end_div();
            }
        }

        if ($row['classification'] === 'provisional_no_purchase') {
            echo html_writer::div(
                get_string('commerce_guest_crm_no_purchase_help', 'local_subscriptions'),
                'alert alert-light border mb-0'
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
