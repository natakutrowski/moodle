<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\CommerceProductSlugService;
use local_subscriptions\url\CommerceRouteRegistry;

$route = optional_param('route', '', PARAM_ALPHANUMEXT);
$slug = optional_param('slug', '', PARAM_PATH);
$category = optional_param('category', '', PARAM_PATH);

if (in_array($route, ['terms', 'privacy'], true)) {
    $urls = \local_subscriptions\support\Region::policyUrls();
    $target = $route === 'privacy' ? (string)$urls['policy'] : (string)$urls['terms'];
    if ($target === '') {
        throw new moodle_exception('commerce_route_not_found', 'local_subscriptions');
    }
    redirect($target);
}

if ($route !== '') {
    // Core Moodle controllers must run from their own URL. Including them from
    // this router breaks relative require paths such as ../config.php.
    if ($route === CommerceRouteRegistry::COURSE) {
        $courseid = required_param('id', PARAM_INT);
        redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
    }

    if ($route === CommerceRouteRegistry::MY_PROFILE) {
        require_login();
        redirect(new moodle_url('/user/profile.php', ['id' => (int)$USER->id]));
    }

    $target = CommerceRouteRegistry::target($route);
    require(__DIR__ . '/' . $target);
    exit;
}

if ($slug !== '') {
    $slugservice = new CommerceProductSlugService($DB);
    $sku = $slugservice->find_sku(
        $slug,
        current_language(),
        $category !== '' ? $category : null
    );

    // Category segments are presentation concerns. Old links and products whose
    // Commerce type changed must remain resolvable by their unique public slug.
    if ($sku === null && $category !== '') {
        $sku = $slugservice->find_sku(
            $slug,
            current_language(),
            null
        );
    }

    if ($sku === null) {
        throw new moodle_exception(
            'commerce_route_not_found',
            'local_subscriptions'
        );
    }
    $_GET['sku'] = $sku;
    $_REQUEST['sku'] = $sku;
    require(__DIR__ . '/storefront_product.php');
    exit;
}

throw new moodle_exception('commerce_route_not_found', 'local_subscriptions');
