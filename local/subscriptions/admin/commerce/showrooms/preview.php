<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPreviewDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);

$id = required_param('id', PARAM_INT);
$repository = new CommerceShowroomCmsRepository($DB);
$record = $repository->get($id);
if ($record === null) {
    throw new moodle_exception('invalidrecord');
}

$definition = (new CommerceShowroomPreviewDefinitionResolver($DB))
    ->require($id);

$GLOBALS['local_subscriptions_showroom_admin_preview'] = [
    'id' => $id,
    'definition' => $definition,
    'runtimeblocks' => CommerceShowroomRuntimeBlockSet::load_preview($DB, $id),
    'pageurl' => new moodle_url(
        '/local/subscriptions/admin/commerce/showrooms/preview.php',
        ['id' => $id]
    ),
    'currencyendpoint' => new moodle_url(
        '/local/subscriptions/admin/commerce/showrooms/preview_prices.php',
        ['id' => $id]
    ),
];

require(__DIR__ . '/../../../showroom.php');
