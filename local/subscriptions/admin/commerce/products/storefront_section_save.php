<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontSectionSaveService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontSectionStatusService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

header('Content-Type: application/json; charset=utf-8');

try {
    $sku = required_param('sku', PARAM_RAW_TRIMMED);
    $language = required_param('editlang', PARAM_ALPHANUMEXT);
    $factory = new CommerceCatalogFactory($DB);
    $manager = $factory->product_manager();
    $product = $manager->get_editor_data($sku)->get_product();

    $service = new CommerceStorefrontSectionSaveService(
        new CommerceStorefrontPageEditor(),
        CommerceStorefrontContentFileService::create()
    );
    $result = $service->save($product, $language, $_POST);
    $saved = $manager->save_metadata($product->get_sku(), $result['metadata']);
    $persistedproduct = $saved->get_product();
    $persisteddefinition = (new CommerceStorefrontPageEditor())
        ->definition_from_product($persistedproduct, $language);
    $persistedsection = [];
    $sectionid = (string)($result['section']['id'] ?? '');
    foreach ((array)($persisteddefinition['sections'] ?? []) as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }
        if ($sectionid !== '' && (string)($candidate['id'] ?? '') === $sectionid) {
            $persistedsection = $candidate;
            break;
        }
    }
    if ($persistedsection === []) {
        throw new coding_exception('Storefront section could not be reloaded after save.');
    }
    $persisteditemid = (int)($persistedsection['mediaitemid'] ?? 0);
    if ($persisteditemid <= 0 || $persisteditemid !== (int)($result['section']['mediaitemid'] ?? 0)) {
        throw new coding_exception('Storefront media item ID changed after persistence.');
    }

    $statusservice = new CommerceStorefrontSectionStatusService(
        CommerceStorefrontContentFileService::create()
    );
    $editorialstatus = $statusservice->status($persistedsection);
    echo json_encode([
        'success' => true,
        'message' => get_string('commerce_storefront_section_saved', 'local_subscriptions'),
        'section' => $persistedsection,
        'ready' => $editorialstatus === CommerceStorefrontSectionStatusService::READY,
        'editorialstatus' => $editorialstatus,
        'readylabel' => get_string('commerce_storefront_builder_ready', 'local_subscriptions'),
        'attentionlabel' => get_string('commerce_storefront_builder_attention', 'local_subscriptions'),
        'emptylabel' => get_string('commerce_storefront_builder_empty_status', 'local_subscriptions'),
        'diagnostics' => $result['diagnostics'],
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof moodle_exception
            ? $exception->getMessage()
            : get_string('error'),
    ], JSON_THROW_ON_ERROR);
}
exit;
