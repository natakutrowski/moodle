<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$query = optional_param('q', '', PARAM_RAW_TRIMMED);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$commercialstatus = optional_param('commercialstatus', '', PARAM_ALPHANUMEXT);
$paymentstatus = optional_param('paymentstatus', '', PARAM_ALPHANUMEXT);
$fulfillmentstatus = optional_param('fulfillmentstatus', '', PARAM_ALPHANUMEXT);
$provider = optional_param('provider', '', PARAM_ALPHANUMEXT);
$currency = optional_param('currency', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$filter = new CommercePurchaseListFilter($query, $type, $commercialstatus, $paymentstatus, $fulfillmentstatus, $provider, $currency);
$repository = new CommercePurchaseReadRepository($DB);
$result = $repository->search($filter, $page, $perpage);
$actionservice = CommercePurchaseActionServiceFactory::create();
$closedwithoutfulfillment = array_fill_keys(
    $actionservice->closed_without_fulfillment_ids(
        array_map(static fn($purchase): int => (int)$purchase->id, $result->purchases)
    ),
    true
);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php');
$pagetitle = get_string('commerce_purchases_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-purchases-page');

$options = static fn(array $values, callable $label): array => ['' => get_string('all')] + array_combine($values, array_map($label, $values));
$typeoptions = $options(['subscription', 'digital', 'bundle'], static fn(string $value): string => CommercePurchasePresentation::type_label($value));
$commercialoptions = $options(CommerceCommercialStatus::all(), static fn(string $value): string => CommercePurchasePresentation::commercial_status_label($value));
$paymentoptions = $options(['none', 'pending', 'paid', 'failed', 'refunded', 'cancelled'], static fn(string $value): string => CommercePurchasePresentation::technical_status_label('payment', $value));
$fulfillmentoptions = $options(['none', 'pending', 'processing', 'fulfilled', 'failed'], static fn(string $value): string => CommercePurchasePresentation::technical_status_label('fulfillment', $value));

$params = array_filter([
    'q' => $query, 'type' => $type, 'commercialstatus' => $commercialstatus,
    'paymentstatus' => $paymentstatus, 'fulfillmentstatus' => $fulfillmentstatus,
    'provider' => $provider, 'currency' => $currency, 'perpage' => $perpage,
], static fn($value): bool => $value !== '');

$filterhtml = html_writer::start_tag('form', ['method' => 'get', 'class' => 'row g-3 align-items-end']);
$fields = [
    ['q', get_string('commerce_purchases_search', 'local_subscriptions'), html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q', 'id' => 'q', 'value' => $query, 'class' => 'form-control'])],
    ['type', get_string('commerce_purchase_type', 'local_subscriptions'), html_writer::select($typeoptions, 'type', $type, false, ['id' => 'type', 'class' => 'form-select'])],
    ['commercialstatus', get_string('commerce_purchase_commercial_status', 'local_subscriptions'), html_writer::select($commercialoptions, 'commercialstatus', $commercialstatus, false, ['id' => 'commercialstatus', 'class' => 'form-select'])],
    ['paymentstatus', get_string('commerce_purchase_payment_status', 'local_subscriptions'), html_writer::select($paymentoptions, 'paymentstatus', $paymentstatus, false, ['id' => 'paymentstatus', 'class' => 'form-select'])],
    ['fulfillmentstatus', get_string('commerce_purchase_fulfillment_status', 'local_subscriptions'), html_writer::select($fulfillmentoptions, 'fulfillmentstatus', $fulfillmentstatus, false, ['id' => 'fulfillmentstatus', 'class' => 'form-select'])],
];
foreach ($fields as [$id, $label, $control]) {
    $filterhtml .= html_writer::div(html_writer::tag('label', $label, ['for' => $id, 'class' => 'form-label']) . $control, $id === 'q' ? 'col-lg-4' : 'col-md-6 col-lg-2');
}
$filterhtml .= html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('filter')]) .
    html_writer::link($pageurl, get_string('reset'), ['class' => 'btn btn-outline-secondary ms-2']),
    'col-12'
);
$filterhtml .= html_writer::end_tag('form');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render($pagetitle, get_string('commerce_purchases_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PURCHASES);
echo CommerceDesignSystemRenderer::filter_panel($filterhtml);
echo CommerceDesignSystemRenderer::metrics([['label' => get_string('commerce_purchases_results', 'local_subscriptions'), 'value' => $result->total]]);

if ($result->purchases === []) {
    echo CommerceDesignSystemRenderer::empty_state(get_string('commerce_purchases_empty_title', 'local_subscriptions'), get_string('commerce_purchases_empty', 'local_subscriptions'));
} else {
    $catalogrepository = new CommerceCatalogReadRepository($DB);

$table = new html_table();
    $table->head = [
        get_string('date'),
        get_string('commerce_purchase_reference', 'local_subscriptions'),
        get_string('commerce_purchase_customer', 'local_subscriptions'),
        get_string('commerce_purchase_type', 'local_subscriptions'),
        get_string('commerce_purchase_products', 'local_subscriptions'),
        get_string('commerce_purchase_amount', 'local_subscriptions'),
        get_string('commerce_purchase_status', 'local_subscriptions'),
        get_string('actions'),
    ];
    $table->attributes = ['class' => 'generaltable table table-hover align-middle', 'aria-label' => get_string('commerce_purchases_table_label', 'local_subscriptions')];
    foreach ($result->purchases as $purchase) {
        $viewurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $purchase->id]);
        $customername = $purchase->customer->display_name();
        $customerlabel = $customername !== ''
            ? $customername
            : ($purchase->customer->email !== '' ? $purchase->customer->email : get_string('unknownuser'));
        $customerhtml = '';

        if ($purchase->customer->userid !== null || $purchase->customer->email !== '') {
            $user360params = $purchase->customer->userid !== null
                ? ['id' => $purchase->customer->userid]
                : ['email' => $purchase->customer->email];
            $user360url = new moodle_url('/local/subscriptions/admin/users/view.php', $user360params);
            $customerhtml .= html_writer::div(
                html_writer::link($user360url, s($customerlabel), ['class' => 'fw-semibold']),
                'commerce-purchase-customer-name'
            );
        } else {
            $customerhtml .= html_writer::div(s($customerlabel), 'fw-semibold');
        }

        if ($purchase->customer->email !== '') {
            $customerhtml .= html_writer::div(s($purchase->customer->email), 'small text-muted');
        }
        $productlinks = [];
        foreach (array_slice($purchase->productitems, 0, 3) as $productitem) {
            $catalogdetails = $catalogrepository->find_by_purchase_reference((string)$productitem['sku']);
            if ($catalogdetails === null) {
                $productlinks[] = s($productitem['label']);
                continue;
            }
            $producturl = CommerceCatalogLinkGenerator::view_url($catalogdetails->get_summary());
            $productlinks[] = html_writer::link($producturl, s($productitem['label']));
        }
        $products = implode(', ', $productlinks);
        if (count($purchase->productitems) > 3) {
            $products .= ' +' . (count($purchase->productitems) - 3);
        }
        $statushtml = CommercePurchasePresentation::commercial_status_badge($purchase->commercialstatus);
        $table->data[] = [
            userdate($purchase->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            html_writer::div(
                html_writer::link(
                    $viewurl,
                    s($purchase->publicreference),
                    ['class' => 'font-monospace fw-semibold']
                )
                . html_writer::div(
                    get_string('commerce_purchase_internal_reference_short', 'local_subscriptions')
                        . ': ' . html_writer::tag('code', s($purchase->reference)),
                    'small text-muted mt-1'
                ),
                'commerce-purchase-reference'
            ),
            $customerhtml,
            CommercePurchasePresentation::type_badge($purchase->type),
            $products,
            CommercePurchasePresentation::money($purchase->totalminor, $purchase->currency),
            $statushtml,
            (static function() use ($purchase, $viewurl, $context, $closedwithoutfulfillment): string {
                $actions = html_writer::link(
                    $viewurl,
                    get_string('view'),
                    ['class' => 'btn btn-sm btn-outline-primary me-1']
                );
                $orderdetailsurl = new moodle_url(
                    '/local/subscriptions/order_details.php',
                    ['reference' => $purchase->reference]
                );
                $actions .= html_writer::link(
                    $orderdetailsurl,
                    get_string('commerce_purchase_open_order_details', 'local_subscriptions'),
                    [
                        'class' => 'btn btn-sm btn-outline-secondary me-1',
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                    ]
                );

                $policy = new CommercePurchaseActionPolicy();
                if (!isset($closedwithoutfulfillment[$purchase->id])
                        && $policy->can_retry_summary($purchase)
                        && has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
                    $retryurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/retry_fulfillment.php', [
                        'id' => $purchase->id,
                        'confirm' => 1,
                        'sesskey' => sesskey(),
                    ]);
                    $actions .= html_writer::link(
                        $retryurl,
                        get_string('commerce_purchase_retry_short', 'local_subscriptions'),
                        [
                            'class' => 'btn btn-sm btn-warning',
                            'data-confirmation' => 'modal',
                            'data-confirmation-title-str' => json_encode([
                                'commerce_purchase_retry_fulfillment',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-content-str' => json_encode([
                                'commerce_purchase_retry_confirm',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-yes-button-str' => json_encode(['yes']),
                            'data-confirmation-destination' => $retryurl->out(false),
                        ]
                    );
                }

                return $actions;
            })(),
        ];
    }
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($result->total, $result->page, $result->perpage, new moodle_url($pageurl, $params));
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
