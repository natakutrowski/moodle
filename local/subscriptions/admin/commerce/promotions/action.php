<?php
require_once(__DIR__ . '/../../../../../config.php');
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$repository = new MoodleCommercePromotionRepository();
$promotion = $repository->get_by_id($id);
if ($promotion === null) { throw new moodle_exception('invalidrecord'); }
if ($action === 'delete') {
    $repository->delete($id);
} else if (in_array($action, ['enable', 'disable'], true)) {
    $repository->save(new CommercePromotion($promotion->get_id(), $promotion->get_name(), $promotion->get_code(), $promotion->get_discount_type(), $promotion->get_discount_value(), $promotion->get_currency(), $promotion->get_minimum_cart_minor(), $promotion->get_starts_at(), $promotion->get_ends_at(), $action === 'enable', $promotion->is_automatic(), $promotion->is_stackable(), $promotion->get_priority(), $promotion->get_global_usage_limit(), $promotion->get_user_usage_limit(), $promotion->get_product_skus(), $promotion->get_product_types(), $promotion->get_metadata()));
} else { throw new moodle_exception('invalidparameter'); }
redirect(new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php'));
