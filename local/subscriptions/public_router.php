<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\admin\Capabilities;
use local_subscriptions\url\CommerceCustomerProfileRouteResolver;
use local_subscriptions\url\CommerceProductSlugService;
use local_subscriptions\url\CommerceRouteRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSlugService;

$route = optional_param('route', '', PARAM_ALPHANUMEXT);
$slug = optional_param('slug', '', PARAM_PATH);
$category = optional_param('category', '', PARAM_PATH);

$renderShowroom = static function (): never {
    global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION;

    try {
        require(__DIR__ . '/showroom.php');
    } catch (\moodle_exception $exception) {
        if ($exception->errorcode !== 'commerce_showroom_not_found') {
            throw $exception;
        }

        // Fail closed without exposing exception details publicly.
        http_response_code(404);
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url(new moodle_url(
            '/local/subscriptions/public_router.php',
            ['route' => CommerceRouteRegistry::SHOWROOM]
        ));
        $PAGE->set_pagelayout('standard');
        $PAGE->set_title(get_string(
            'commerce_showroom_not_found',
            'local_subscriptions'
        ));
        $PAGE->set_heading(get_string(
            'commerce_showroom_not_found',
            'local_subscriptions'
        ));

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string(
            'commerce_showroom_not_found',
            'local_subscriptions'
        ));
        echo $OUTPUT->footer();
    }

    exit;
};


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

        $requesteduserid = optional_param('id', 0, PARAM_INT);
        $systemcontext = context_system::instance();
        $canviewotherusers = is_siteadmin()
            || Capabilities::can_view_users($systemcontext);

        $profileuserid = CommerceCustomerProfileRouteResolver::resolve(
            (int)$USER->id,
            $requesteduserid,
            $canviewotherusers
        );

        redirect(
            new moodle_url(
                '/user/profile.php',
                ['id' => $profileuserid]
            )
        );
    }

    $target = CommerceRouteRegistry::target($route);

    if ($route === CommerceRouteRegistry::SHOWROOM) {
        $renderShowroom();
    }

    require(__DIR__ . '/' . $target);
    exit;
}

if ($slug !== '') {
    // J16S12 — dynamic top-level Showroom routing keeps the historical routing
    // precedence of the old explicit .htaccess Showroom rules.
    //
    // Category product URLs (/digital/..., etc.) are never intercepted.
    if ($category === '') {
        $showroomkey = (
            new CommerceShowroomSlugService($DB)
        )->find_published_showroom_key($slug);

        if ($showroomkey !== null) {
            $_GET['showroomkey'] = $showroomkey;
            $_REQUEST['showroomkey'] = $showroomkey;
            $renderShowroom();
        }
    }

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

    if ($sku !== null) {
        $_GET['sku'] = $sku;
        $_REQUEST['sku'] = $sku;
        require(__DIR__ . '/storefront_product.php');
        exit;
    }

    throw new moodle_exception(
        'commerce_route_not_found',
        'local_subscriptions'
    );
}

throw new moodle_exception('commerce_route_not_found', 'local_subscriptions');
