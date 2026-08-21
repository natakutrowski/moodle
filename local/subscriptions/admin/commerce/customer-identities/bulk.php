<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityImpactRenderer;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerBulkReconciliationService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
require_sesskey();
$action = required_param('action', PARAM_ALPHA);
$purchaseids = optional_param_array('purchaseids', [], PARAM_INT);
$returnraw = optional_param('returnurl', '', PARAM_LOCALURL);
$returnurl = $returnraw !== '' ? new moodle_url($returnraw) : new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php');
if ($purchaseids === []) {
    redirect($returnurl, get_string('commerce_identity_bulk_none_selected', 'local_subscriptions'), null, \core\output\notification::NOTIFY_WARNING);
}
$service = new CommerceCustomerBulkReconciliationService(new CommerceCustomerIdentityReconciliationService($DB));
if ($action === 'execute') {
    $results = $service->execute($purchaseids);
    $reconciled = count(array_filter($results, static fn($r) => $r->status === CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED));
    redirect($returnurl, get_string('commerce_identity_bulk_execute_result', 'local_subscriptions', (object)['done'=>$reconciled,'total'=>count($results)]), null, \core\output\notification::NOTIFY_SUCCESS);
}
if ($action !== 'preview') {
    throw new moodle_exception('invalidparameter');
}
$previews = $service->preview($purchaseids);
$title = get_string('commerce_identity_bulk_preview_title', 'local_subscriptions');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/bulk.php');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-customer-identities-bulk-page');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
 ['label'=>get_string('crm_commerce_title','local_subscriptions'),'url'=>new moodle_url('/local/subscriptions/admin/commerce/index.php')],
 ['label'=>get_string('commerce_identity_reconciliation_title','local_subscriptions'),'url'=>$returnurl],
 ['label'=>$title,'url'=>null],
]);
echo CrmPageHeader::render($title, get_string('commerce_identity_bulk_preview_description','local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::IDENTITIES, $context);
echo CommerceCustomerIdentityNavigationRenderer::render(
    CommerceCustomerIdentityNavigationRenderer::RECONCILIATION
);

echo html_writer::div(get_string('commerce_identity_bulk_preview_warning','local_subscriptions'),'alert alert-warning');
$table = new html_table();
$table->attributes['class'] =
    'generaltable table table-hover align-middle crm-identity-table';
$table->head = [
    get_string(
        'crm_identity_reconciliation_sale',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_diagnostic',
        'local_subscriptions'
    ),
    get_string(
        'crm_identity_reconciliation_proposed_account',
        'local_subscriptions'
    ),
    get_string(
        'crm_identity_reconciliation_expected_effect',
        'local_subscriptions'
    ),
];

$matched = [];
$publicreferencebuilder = new CommercePublicOrderReference();

foreach ($previews as $preview) {
    $r = $preview->result;

    if (
        $r->status
            === CommerceCustomerIdentityReconciliationResult::
                STATUS_MATCHED
        && $r->purchaseid !== null
    ) {
        $matched[] = $r->purchaseid;
    }

    $purchase = $r->purchaseid !== null
        ? $DB->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $r->purchaseid],
            '*',
            IGNORE_MISSING
        )
        : false;

    $sale = '—';
    if ($purchase !== false) {
        $publicreference = $publicreferencebuilder->from_internal(
            (string)$purchase->reference,
            (int)$purchase->timecreated
        );

        $sale = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/view.php',
                ['id' => (int)$purchase->id]
            ),
            s($publicreference),
            [
                'class' =>
                    'crm-identity-reconciliation-sale-reference',
            ]
        );
    }

    $candidate = '—';
    if ($r->userid !== null) {
        $candidateuser = $DB->get_record(
            'user',
            ['id' => $r->userid],
            implode(
                ',',
                array_merge(
                    ['id', 'email'],
                    \core_user\fields::get_name_fields()
                )
            ),
            IGNORE_MISSING
        );

        $candidate = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/users/view.php',
                ['id' => $r->userid]
            ),
            html_writer::span(
                $candidateuser !== false
                    ? fullname($candidateuser)
                    : get_string(
                        'crm_identity_reconciliation_moodle_account',
                        'local_subscriptions'
                    ),
                'crm-identity-reconciliation-candidate-name'
            )
            . html_writer::span(
                '#' . $r->userid,
                'crm-identity-reconciliation-candidate-id'
            ),
            [
                'class' =>
                    'crm-identity-reconciliation-candidate-link',
            ]
        );
    } else if ($r->candidateuserids !== []) {
        $candidate = implode(
            ', ',
            array_map(
                static function(int $candidateuserid): string {
                    return '#' . $candidateuserid;
                },
                $r->candidateuserids
            )
        );
    }

    $impact = (
        $r->status
            === CommerceCustomerIdentityReconciliationResult::
                STATUS_MATCHED
        && $purchase !== false
    )
        ? CommerceCustomerIdentityImpactRenderer::render(
            $preview,
            $purchase,
            $r->userid
        )
        : '—';

    $table->data[] = [
        $sale,
        $statuslabel = get_string(
            'commerce_identity_status_' . $r->status,
            'local_subscriptions'
        ),
        $candidate,
        $impact,
    ];
}

echo html_writer::table($table);
echo html_writer::start_div(
    'd-flex gap-2 crm-identity-reconciliation-bulk-actions'
);
echo html_writer::link($returnurl,get_string('cancel'),['class'=>'btn btn-outline-secondary']);
if($matched!==[]){
 echo html_writer::start_tag('form',['method'=>'post','action'=>$pageurl->out(false)]);
 echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
 echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'action','value'=>'execute']);
 echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'returnurl','value'=>$returnurl->out(false)]);
 foreach($matched as $id){echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'purchaseids[]','value'=>$id]);}
 echo html_writer::tag('button',get_string('commerce_identity_bulk_execute','local_subscriptions'),['type'=>'submit','class'=>'btn btn-danger','data-confirmation'=>'modal','data-confirmation-title-str'=>json_encode(['commerce_identity_bulk_execute','local_subscriptions']),'data-confirmation-content-str'=>json_encode(['commerce_identity_bulk_execute_confirm','local_subscriptions']),'data-confirmation-yes-button-str'=>json_encode(['yes'])]);
 echo html_writer::end_tag('form');
}
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
