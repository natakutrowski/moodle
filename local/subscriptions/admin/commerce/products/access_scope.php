<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\editing\CommerceProductEditorCapabilities;
use local_subscriptions\commerce\catalog\navigation\CommerceLegacyCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$product = $manager->get_editor_data($sku)->get_product();

if (!CommerceProductEditorCapabilities::for_product($product)->can_manage_access_scope()) {
    throw new coding_exception('This product type does not use an Access Scope relation.');
}

$mappingrepository = new CommerceLegacyProductMapRepository($DB);
$metadata = $product->get_metadata();
$accessmetadata = is_array($metadata['access'] ?? null) ? $metadata['access'] : [];

if (data_submitted() && confirm_sesskey()) {
    $action = required_param('action', PARAM_ALPHA);

    if ($action === 'scope') {
        $sourceplanid = optional_param('scopeplanid', 0, PARAM_INT);
        if ($sourceplanid > 0) {
            $plan = $DB->get_record('subscription_plan', ['id' => $sourceplanid], 'id,accessscopeid', MUST_EXIST);
            if (empty($plan->accessscopeid)) {
                throw new moodle_exception('commerce_access_scope_plan_without_scope', 'local_subscriptions');
            }
            $accessmetadata['sourceplanid'] = (int)$plan->id;
            $accessmetadata['scopeid'] = (int)$plan->accessscopeid;
        } else {
            unset($accessmetadata['sourceplanid'], $accessmetadata['scopeid']);
        }
        $metadata['access'] = $accessmetadata;
        $manager->save_metadata($product->get_sku(), $metadata);
    }

    if ($action === 'canonical') {
        $planid = optional_param('legacyplanid', 0, PARAM_INT);
        if ($planid <= 0) {
            $mappingrepository->unlink_product((int)$product->get_id(), 'subscription_plan');
        } else {
            $DB->get_record('subscription_plan', ['id' => $planid], 'id', MUST_EXIST);
            $conflict = $mappingrepository->find_legacy_link('subscription_plan', $planid);
            if ($conflict && (int)$conflict->productid !== (int)$product->get_id()) {
                throw new moodle_exception('commerce_access_scope_canonical_conflict', 'local_subscriptions');
            }
            $mappingrepository->link_product(
                (int)$product->get_id(),
                'subscription',
                'subscription_plan',
                $planid
            );
        }
    }

    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/products/access_scope.php', ['sku' => $sku]),
        get_string('changessaved')
    );
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/access_scope.php', ['sku' => $sku]);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    get_string('commerce_access_scope_relation_title', 'local_subscriptions'),
    'local-subscriptions-commerce-access-scope-page'
);

$plans = $DB->get_records('subscription_plan', [], 'name ASC', 'id,name,is_active,accessscopeid');
$nativeproducts = $DB->get_records('local_subs_commerce_product', [], '', 'id,sku,name');
$currentmapping = $mappingrepository->find_by_product((int)$product->get_id(), 'subscription_plan');
$currentcanonicalplanid = $currentmapping ? (int)$currentmapping->legacyid : 0;
$currentsourceplanid = (int)($accessmetadata['sourceplanid'] ?? 0);
$currentscopeid = (int)($accessmetadata['scopeid'] ?? 0);

$scopeoptions = [0 => get_string('commerce_access_scope_no_scope', 'local_subscriptions')];
$canonicaloptions = [0 => get_string('commerce_access_scope_no_canonical_plan', 'local_subscriptions')];

foreach ($plans as $plan) {
    $label = format_string($plan->name) . ' (#' . (int)$plan->id . ')';
    if (empty($plan->accessscopeid)) {
        $label .= ' — ' . get_string('commerce_access_scope_plan_without_scope', 'local_subscriptions');
    } else {
        $scopeoptions[(int)$plan->id] = $label;
    }

    $link = $mappingrepository->find_legacy_link('subscription_plan', (int)$plan->id);
    if ($link && (int)$link->productid !== (int)$product->get_id()) {
        $linkedproduct = $nativeproducts[(int)$link->productid] ?? null;
        $label .= ' — ' . get_string(
            'commerce_access_scope_already_linked_to',
            'local_subscriptions',
            $linkedproduct ? $linkedproduct->sku : ('#' . (int)$link->productid)
        );
    } else {
        $canonicaloptions[(int)$plan->id] = $label;
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $product->get_name(),
    get_string('commerce_product_step_access_scope', 'local_subscriptions')
);
echo CommerceProductEditorNavigationRenderer::render($product, CommerceProductEditorNavigationRenderer::ACCESS_SCOPE);
echo $OUTPUT->heading(get_string('commerce_access_scope_relation_title', 'local_subscriptions'));
echo html_writer::div(get_string('commerce_access_scope_f6e_help', 'local_subscriptions'), 'alert alert-info');

// Shared scope: several Native products may reuse the same effective scope.
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'scope']);
echo html_writer::tag('h3', get_string('commerce_access_scope_shared_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_access_scope_shared_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::tag('label', get_string('commerce_access_scope_source_plan', 'local_subscriptions'), [
    'for' => 'scopeplanid',
    'class' => 'form-label',
]);
echo html_writer::select($scopeoptions, 'scopeplanid', $currentsourceplanid, false, [
    'id' => 'scopeplanid',
    'class' => 'form-select mb-3',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('savechanges'),
    'class' => 'btn btn-primary align-self-start',
]);
echo html_writer::end_tag('form');

// Canonical migration mapping: one Legacy plan can map to one Native product only.
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body mb-4 border-secondary']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'canonical']);
echo html_writer::tag('h3', get_string('commerce_access_scope_canonical_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::div(get_string('commerce_access_scope_canonical_help', 'local_subscriptions'), 'alert alert-secondary');
echo html_writer::tag('label', get_string('commerce_access_scope_canonical_plan', 'local_subscriptions'), [
    'for' => 'legacyplanid',
    'class' => 'form-label',
]);
echo html_writer::select($canonicaloptions, 'legacyplanid', $currentcanonicalplanid, false, [
    'id' => 'legacyplanid',
    'class' => 'form-select mb-3',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('savechanges'),
    'class' => 'btn btn-outline-secondary align-self-start',
]);
echo html_writer::end_tag('form');

if ($currentscopeid <= 0 || $currentsourceplanid <= 0) {
    echo html_writer::div(get_string('commerce_access_scope_unmapped', 'local_subscriptions'), 'alert alert-warning');
} else {
    $plan = $plans[$currentsourceplanid] ?? null;
    $scope = $DB->get_record('subscription_access_scope', ['id' => $currentscopeid], 'id,name');
    $table = new html_table();
    $table->data = [
        [get_string('commerce_access_scope_plan', 'local_subscriptions'), $plan ? format_string($plan->name) . ' (#' . $plan->id . ')' : '#' . $currentsourceplanid],
        [get_string('commerce_access_scope_scope', 'local_subscriptions'), $scope ? format_string($scope->name) . ' (#' . $scope->id . ')' : '#' . $currentscopeid],
    ];
    echo html_writer::table($table);

    echo html_writer::start_div('d-flex gap-2 mt-3 mb-4 flex-wrap');
    echo html_writer::link(
        CommerceLegacyCatalogLinkGenerator::plan_edit_url($currentsourceplanid),
        get_string('commerce_access_scope_edit_plan', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    );
    echo html_writer::link(
        CommerceLegacyCatalogLinkGenerator::scope_edit_url($currentscopeid),
        get_string('commerce_access_scope_edit_scope', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo html_writer::end_div();

    $sql = 'SELECT c.id, c.fullname
              FROM {subscription_plan_entitlement} e
              JOIN {course} c ON c.id = e.courseid
             WHERE e.planid = :planid
          ORDER BY c.fullname ASC';
    $courses = $DB->get_records_sql($sql, ['planid' => $currentsourceplanid]);
    echo $OUTPUT->heading(get_string('commerce_access_scope_courses', 'local_subscriptions'), 3);
    $coursetable = new html_table();
    $coursetable->head = [get_string('idnumber'), get_string('fullnamecourse')];
    foreach ($courses as $course) {
        $coursetable->data[] = [(int)$course->id, format_string($course->fullname)];
    }
    echo html_writer::table($coursetable);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
