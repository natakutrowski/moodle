<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\editing\CommerceProductEditorCapabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogFulfillmentPresentation;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$rows = max(2, min(30, optional_param('rows', 2, PARAM_INT)));
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
if (!CommerceProductEditorCapabilities::for_product($product)->can_edit_fulfillments()) {
    throw new coding_exception('This product type does not have directly editable fulfillments.');
}
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/fulfillments.php', ['sku' => $sku, 'rows' => $rows]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, get_string('commerce_product_fulfillments_title', 'local_subscriptions'), 'local-subscriptions-commerce-product-fulfillments-page');

if (optional_param('addrow', 0, PARAM_BOOL) && confirm_sesskey()) {
    redirect(new moodle_url($pageurl, ['rows' => $rows + 1]));
}
if (optional_param('savefulfillments', 0, PARAM_BOOL) && confirm_sesskey()) {
    $types = optional_param_array('entitlementtype', [], PARAM_ALPHANUMEXT);
    $resources = optional_param_array('resourcekey', [], PARAM_RAW_TRIMMED);
    $durations = optional_param_array('durationseconds', [], PARAM_INT);
    $quantities = optional_param_array('quantity', [], PARAM_INT);
    $definitions = [];
    foreach ($types as $index => $type) {
        $type = trim((string)$type);
        $resource = trim((string)($resources[$index] ?? ''));
        if ($type === '' && $resource === '') { continue; }
        if ($type === '' || $resource === '') {
            throw new moodle_exception('commerce_incomplete_fulfillment_row', 'local_subscriptions', '', $index + 1);
        }
        if (!array_key_exists($type, CommerceCatalogFulfillmentPresentation::type_options())) {
            throw new moodle_exception('commerce_unknown_fulfillment_type', 'local_subscriptions');
        }
        if (str_starts_with($resource, 'course:') && !$DB->record_exists('course', ['id' => (int)substr($resource, 7)])) {
            throw new moodle_exception('commerce_invalid_fulfillment_resource', 'local_subscriptions');
        }
        if (str_starts_with($resource, 'digital-product:') && !$DB->record_exists('subscription_digital_product', ['id' => (int)substr($resource, 16)])) {
            throw new moodle_exception('commerce_invalid_fulfillment_resource', 'local_subscriptions');
        }
        $definitions[] = new CommerceProductEntitlementDefinition($sku, $type, $resource, ((int)($durations[$index] ?? 0)) ?: null, max(1, (int)($quantities[$index] ?? 1)), [], $index);
    }
    $manager->save_entitlements($sku, $definitions);
    redirect(new moodle_url($pageurl, ['rows' => max(2, count($definitions) + 1)]), get_string('changessaved'));
}

$current = $editor->get_entitlements();
$rowcount = max($rows, count($current) + 2);
$resourceoptions = ['' => get_string('choose')];
foreach ($DB->get_records('course', null, 'fullname ASC', 'id,fullname') as $course) {
    if ((int)$course->id === SITEID) { continue; }
    $resourceoptions['course:' . $course->id] = get_string('commerce_resource_course', 'local_subscriptions', format_string($course->fullname));
}
foreach ($DB->get_records('subscription_digital_product', null, 'name ASC', 'id,name') as $digital) {
    $resourceoptions['digital-product:' . $digital->id] = get_string('commerce_resource_digital', 'local_subscriptions', format_string($digital->name));
}
$durationoptions = [0 => get_string('commerce_duration_lifetime', 'local_subscriptions'), 2592000 => get_string('commerce_duration_30_days', 'local_subscriptions'), 7776000 => get_string('commerce_duration_90_days', 'local_subscriptions'), 31536000 => get_string('commerce_duration_365_days', 'local_subscriptions')];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_fulfillments', 'local_subscriptions'));
echo CommerceProductEditorNavigationRenderer::render($product, CommerceProductEditorNavigationRenderer::FULFILLMENTS);
echo $OUTPUT->heading(get_string('commerce_product_fulfillments_title', 'local_subscriptions'));
echo html_writer::tag('p', get_string('commerce_product_fulfillments_guided_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'rows', 'value' => $rowcount]);
$table = new html_table();
$table->head = [get_string('commerce_fulfillment_type', 'local_subscriptions'), get_string('commerce_fulfillment_resource', 'local_subscriptions'), get_string('commerce_fulfillment_duration', 'local_subscriptions'), get_string('commerce_fulfillment_quantity', 'local_subscriptions')];
for ($i = 0; $i < $rowcount; $i++) {
    $definition = $current[$i] ?? null;
    $table->data[] = [
        html_writer::select(CommerceCatalogFulfillmentPresentation::type_options(), 'entitlementtype[]', $definition?->get_type() ?? '', false, ['class' => 'form-select']),
        html_writer::select($resourceoptions, 'resourcekey[]', $definition?->get_resource_key() ?? '', false, ['class' => 'form-select']),
        html_writer::select($durationoptions, 'durationseconds[]', $definition?->get_duration_seconds() ?? 0, false, ['class' => 'form-select']),
        html_writer::empty_tag('input', ['type' => 'number', 'min' => 1, 'name' => 'quantity[]', 'value' => $definition?->get_quantity() ?? 1, 'class' => 'form-control']),
    ];
}
echo html_writer::table($table);
echo html_writer::div(
    html_writer::tag('button', get_string('commerce_add_fulfillment_row', 'local_subscriptions'), ['type' => 'submit', 'name' => 'addrow', 'value' => 1, 'class' => 'btn btn-outline-secondary me-2']) .
    html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'name' => 'savefulfillments', 'value' => 1, 'class' => 'btn btn-primary']),
    'd-flex flex-wrap gap-2'
);
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
