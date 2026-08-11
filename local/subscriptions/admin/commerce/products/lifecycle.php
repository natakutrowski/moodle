<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\admin\CommerceProductLifecycleService;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$product = $manager->get_editor_data($sku)->get_product();
$service = new CommerceProductLifecycleService($DB);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/lifecycle.php', ['sku' => $sku]);
$editurl = new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $sku]);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    get_string('commerce_product_lifecycle_title', 'local_subscriptions'),
    'local-subscriptions-commerce-product-lifecycle-page'
);

if (data_submitted() && confirm_sesskey()) {
    $action = required_param('action', PARAM_ALPHA);

    if ($action === 'archive') {
        $manager->archive_product($sku);
        redirect($pageurl, get_string('commerce_product_archived', 'local_subscriptions'));
    }

    if ($action === 'restore') {
        // Restoring as draft is safer than silently republishing an archived offer.
        $manager->set_status($sku, 'draft');
        redirect($pageurl, get_string('commerce_product_restored', 'local_subscriptions'));
    }

    if ($action === 'delete') {
        global $USER;

        $force = optional_param('force', 0, PARAM_BOOL) === 1;
        if ($force && !get_config('local_subscriptions', 'commerce_allow_destructive_product_delete')) {
            throw new moodle_exception('commerce_product_force_delete_disabled', 'local_subscriptions');
        }

        $confirmed = optional_param('confirmdelete', 0, PARAM_BOOL) === 1;
        $password = (string)optional_param('adminpassword', '', PARAM_RAW);
        $passworduser = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);

        if (!$confirmed || $password === '' || !validate_internal_user_password($passworduser, $password)) {
            throw new moodle_exception('commerce_product_delete_confirmation_failed', 'local_subscriptions');
        }

        if ($force) {
            $confirmation = (string)optional_param('confirmation', '', PARAM_RAW_TRIMMED);
            if ($confirmation !== 'SUPPRIMER') {
                throw new moodle_exception('commerce_product_force_delete_confirmation_failed', 'local_subscriptions');
            }
        }

        $service->delete((int)$product->get_id(), $product->get_sku(), $force);
        redirect(
            new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
            get_string('commerce_product_deleted', 'local_subscriptions')
        );
    }
}

$product = $manager->get_editor_data($sku)->get_product();
$counts = $service->dependency_counts((int)$product->get_id(), $product->get_sku());
$candelete = $service->can_delete_without_sales((int)$product->get_id(), $product->get_sku());
$isarchived = $product->get_status() === 'archived';
$destructiveenabled = (bool)get_config('local_subscriptions', 'commerce_allow_destructive_product_delete');

$countlabels = [
    'prices' => 'commerce_product_dependency_prices',
    'translations' => 'commerce_product_dependency_translations',
    'components' => 'commerce_product_dependency_components',
    'entitlements' => 'commerce_product_dependency_entitlements',
    'mappings' => 'commerce_product_dependency_mappings',
    'nativepurchaseitems' => 'commerce_product_dependency_native_purchase_items',
    'nativepurchases' => 'commerce_product_dependency_native_purchases',
    'legacyplansales' => 'commerce_product_dependency_legacy_plan_sales',
    'legacydigitalsales' => 'commerce_product_dependency_legacy_digital_sales',
    'grants' => 'commerce_product_dependency_grants',
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $product->get_name(),
    get_string('commerce_product_lifecycle_title', 'local_subscriptions')
);
echo $OUTPUT->heading(get_string('commerce_product_lifecycle_title', 'local_subscriptions'));
echo html_writer::div(get_string('commerce_product_lifecycle_intro', 'local_subscriptions'), 'alert alert-info');

echo html_writer::start_div('d-flex gap-2 flex-wrap mb-4');
echo html_writer::link($editurl, '← ' . get_string('commerce_product_back_to_editor', 'local_subscriptions'), [
    'class' => 'btn btn-outline-secondary',
]);
echo html_writer::end_div();

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('commerce_product_dependencies_title', 'local_subscriptions'), ['class' => 'h5 mb-3']);
echo html_writer::start_div('row g-3');
foreach ($countlabels as $key => $stringkey) {
    $value = (int)($counts[$key] ?? 0);
    $tone = $value > 0 ? 'border-primary-subtle bg-light' : 'border-light';
    echo html_writer::start_div('col-sm-6 col-xl-4');
    echo html_writer::start_div('border rounded-3 p-3 h-100 ' . $tone);
    echo html_writer::div(get_string($stringkey, 'local_subscriptions'), 'text-muted small mb-1');
    echo html_writer::div((string)$value, 'fs-4 fw-semibold');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Archive / restore.
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => $isarchived ? 'restore' : 'archive',
]);
echo html_writer::tag(
    'h3',
    get_string($isarchived ? 'commerce_product_restore_title' : 'commerce_product_archive_title', 'local_subscriptions'),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string($isarchived ? 'commerce_product_restore_help' : 'commerce_product_archive_help', 'local_subscriptions'),
    ['class' => 'text-muted']
);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => $isarchived ? 'btn btn-outline-success align-self-start' : 'btn btn-outline-warning align-self-start',
    'value' => get_string(
        $isarchived ? 'commerce_product_restore_action' : 'commerce_product_archive_action',
        'local_subscriptions'
    ),
]);
echo html_writer::end_tag('form');

// Delete / destructive delete.
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body mb-4 border-danger']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'delete']);
echo html_writer::tag('h3', get_string('commerce_product_delete_title', 'local_subscriptions'), ['class' => 'h5 text-danger']);

if ($candelete) {
    echo html_writer::div(get_string('commerce_product_delete_safe_help', 'local_subscriptions'), 'alert alert-success');
} else {
    echo html_writer::div(get_string('commerce_product_delete_blocked_help', 'local_subscriptions'), 'alert alert-warning');
    if ($destructiveenabled) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'force', 'value' => 1]);
        echo html_writer::tag('label', get_string('commerce_product_delete_confirmation', 'local_subscriptions'), [
            'for' => 'confirmation',
            'class' => 'form-label',
        ]);
        echo html_writer::empty_tag('input', [
            'id' => 'confirmation',
            'name' => 'confirmation',
            'class' => 'form-control mb-3',
            'placeholder' => 'SUPPRIMER',
            'required' => 'required',
        ]);
    } else {
        echo html_writer::div(get_string('commerce_product_force_delete_disabled_help', 'local_subscriptions'), 'alert alert-secondary');
    }
}

if ($candelete || $destructiveenabled) {
    echo html_writer::tag('label', get_string('commerce_product_admin_password', 'local_subscriptions'), [
        'for' => 'adminpassword',
        'class' => 'form-label',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'password',
        'id' => 'adminpassword',
        'name' => 'adminpassword',
        'class' => 'form-control mb-3',
        'required' => 'required',
        'autocomplete' => 'current-password',
    ]);

    echo html_writer::start_div('form-check mb-3');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => 'confirmdelete',
        'name' => 'confirmdelete',
        'value' => 1,
        'class' => 'form-check-input',
        'required' => 'required',
    ]);
    echo html_writer::tag('label', get_string('commerce_product_delete_checkbox', 'local_subscriptions'), [
        'for' => 'confirmdelete',
        'class' => 'form-check-label',
    ]);
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-danger align-self-start',
        'value' => get_string(
            $candelete ? 'commerce_product_delete_action' : 'commerce_product_force_delete_action',
            'local_subscriptions'
        ),
    ]);
}

echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
