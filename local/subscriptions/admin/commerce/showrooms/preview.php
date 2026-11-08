<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPreviewDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

// DEV R1: explicitly load this new class before Moodle's cached class map is rebuilt.
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);

// Gustave Builder: same CampusFR showroom preview endpoint, but no CMS record is
// created until the design has passed the Commerce integration review.
$lovablejob = optional_param('lovablejob', '', PARAM_ALPHANUM);
if ($lovablejob !== '') {
    require(__DIR__ . '/lovable_campus_preview.php');
    return;
}

$id = required_param('id', PARAM_INT);
$repository = new CommerceShowroomCmsRepository($DB);
$record = $repository->get($id);
if ($record === null) {
    throw new moodle_exception('invalidrecord');
}

// P2I: opt-in, administrative DEV checkout test for draft #8 only.
// Normal previews and all public showrooms are unchanged.
$commercetest = optional_param('commerce_test', 0, PARAM_INT);
if ($commercetest !== 0 && $commercetest !== 1) {
    throw new invalid_parameter_exception('Invalid DEV Commerce test flag.');
}
if ($commercetest === 1) {
    require_capability('moodle/site:config', $context);
    $settings = json_decode((string)$record->settingsjson, true, 512, JSON_THROW_ON_ERROR);
    $designid = (string)($settings['designbridge']['id'] ?? '');
    $manifest = \local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePackageRegistry
        ::require_manifest('articles_alf_reimport');
    if (!\local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate::eligible(
        $id,
        (string)$record->status,
        (string)$record->showroomkey,
        (string)$record->template,
        (string)$CFG->wwwroot,
        $designid,
        (string)($manifest['version'] ?? '')
    )) {
        throw new moodle_exception('invalidparameter');
    }
    // An administrator can test the real cart, but NEVER against live payment gateways.
    \local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate::require_sandbox();
}

// Permanent but strictly nonpublishable Lovable CMS drafts have their own
// sandboxed viewport. All legacy CMS showroom preview routes remain unchanged.
if (\local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService::metadata($record) !== null) {
    require(__DIR__ . '/lovable_cms_preview.php');
    return;
}

$definition = (new CommerceShowroomPreviewDefinitionResolver($DB))
    ->require($id);

$pageparams = ['id' => $id];
if ($commercetest === 1) {
    $pageparams['commerce_test'] = 1;
}
$GLOBALS['local_subscriptions_showroom_admin_preview'] = [
    'checkouttest' => $commercetest === 1,
    'id' => $id,
    'definition' => $definition,
    'runtimeblocks' => CommerceShowroomRuntimeBlockSet::load_preview($DB, $id),
    'pageurl' => new moodle_url(
        '/local/subscriptions/admin/commerce/showrooms/preview.php',
        $pageparams
    ),
    'currencyendpoint' => new moodle_url(
        '/local/subscriptions/admin/commerce/showrooms/preview_prices.php',
        ['id' => $id]
    ),
];

require(__DIR__ . '/../../../showroom.php');
