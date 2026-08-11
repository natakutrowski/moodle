<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$repository = new MoodleCommercePromotionRepository();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php');
$title = get_string('commerce_promotions_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-promotions-page');

$formatvalue = static function(CommercePromotion $promotion): string {
    if ($promotion->get_discount_type() === CommercePromotion::TYPE_PERCENTAGE) {
        return format_float($promotion->get_discount_value() / 100, 2) . ' %';
    }
    return format_float($promotion->get_discount_value() / 100, 2) . ' ' . ($promotion->get_currency() ?? '');
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_configuration_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_promotions_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php'), get_string('commerce_promotion_add', 'local_subscriptions'), ['class' => 'btn btn-primary']), 'mb-3');

$promotions = $repository->find_all();
if ($promotions === []) {
    echo html_writer::div(get_string('commerce_promotions_empty', 'local_subscriptions'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [get_string('commerce_promotion_code', 'local_subscriptions'), get_string('commerce_promotion_type', 'local_subscriptions'), get_string('commerce_promotion_value', 'local_subscriptions'), get_string('status'), get_string('commerce_promotion_priority', 'local_subscriptions'), get_string('commerce_promotion_uses', 'local_subscriptions'), get_string('commerce_promotion_customer_eligibility', 'local_subscriptions'), get_string('actions')];
    foreach ($promotions as $promotion) {
        $id = (int)$promotion->get_id();
        $toggle = new moodle_url('/local/subscriptions/admin/commerce/promotions/action.php', ['id' => $id, 'action' => $promotion->is_active() ? 'disable' : 'enable', 'sesskey' => sesskey()]);
        $delete = new moodle_url('/local/subscriptions/admin/commerce/promotions/action.php', ['id' => $id, 'action' => 'delete', 'sesskey' => sesskey()]);
        $actions = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php', ['id' => $id]), get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary me-1']) .
            html_writer::link($toggle, get_string($promotion->is_active() ? 'disable' : 'enable'), ['class' => 'btn btn-sm btn-outline-secondary me-1']) .
            html_writer::link($delete, get_string('delete'), ['class' => 'btn btn-sm btn-outline-danger']);
        $rules = CommercePromotionEligibilityRuleSet::from_metadata($promotion->get_metadata());
        $eligibilitylabel = $rules->is_empty()
            ? get_string('commerce_promotion_eligibility_everyone', 'local_subscriptions')
            : get_string('commerce_promotion_eligibility_conditional', 'local_subscriptions');
        $table->data[] = [
            s($promotion->get_code() ?? get_string('commerce_promotion_automatic', 'local_subscriptions')) . html_writer::div(s($promotion->get_name()), 'small text-muted'),
            s($promotion->get_discount_type()),
            s($formatvalue($promotion)),
            $promotion->is_active() ? get_string('active') : get_string('inactive'),
            $promotion->get_priority(),
            $repository->count_redemptions($id),
            s($eligibilitylabel),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
