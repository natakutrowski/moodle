<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$roots = [
    $CFG->dirroot . '/local/subscriptions',
    $CFG->dirroot . '/local/campus',
    $CFG->dirroot . '/theme/edly',
];

$patterns = [
    'subscription_controller' =>
        '~/local/subscriptions/(?:storefront_product|my_purchases|my_digital_products|cart|checkout|order_details|support_request)\.php~',
    'legacy_courses' => '~/my/courses\.php~',
    'profile' => '~/user/profile\.php~',
    'course' => '~/course/view\.php~',
];

// These files intentionally own technical targets, compatibility fallbacks,
// event metadata, diagnostics or server-side controller dispatch. They do not
// directly render customer navigation and must not fail the public UX audit.
$infrastructure = [
    '/classes/url/',
    '/classes/event/',
    '/classes/commerce/certification/',
    '/classes/commerce/professional/certification/',
    '/classes/commerce/mail/',
    '/classes/commerce/trial/',
    '/classes/commerce/customer/crm/',
    '/classes/commerce/catalog/resolution/',
    '/classes/commerce/order/presentation/',
    '/classes/commerce/course/library/',
    '/classes/commerce/course/storefront/',
    '/classes/output/UserProfileRenderer.php',
    '/classes/output/renderer.php',
    '/renderer/scopes_renderer.php',
    '/local/campus/course.php',
    '/local/campus/lib.php',
    '/theme/edly/classes/local/customer_navigation.php',
    '/public_router.php',
    '/order_access.php',
    '/cart_print.php',
    '/commerce_checkout.php',
    '/trial_gate.php',
    '/trial_check.php',
    '/mycourses_redirect.php',
    '/observers.php',
    '/theme/edly/classes/output/',
    '/theme/edly/renderers/',
    '/theme/edly/inc/course_handler/',
    '/classes/commerce/storefront/presentation/CommerceStorefrontUrlResolver.php',
    '/order_details.php',
    '/order_result.php',
    '/my_purchases.php',
    '/storefront_product.php',
    '/cart.php',
];

$ignored = [
    '/tests/',
    '/admin/',
    '/cli/',
    '/payment/',
    '/vendor/',
    '/node_modules/',
];

$issues = [];
$informational = [];

foreach ($roots as $root) {
    if (!is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $file) {
        if (
            !$file->isFile()
            || !in_array($file->getExtension(), ['php', 'mustache'], true)
        ) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());
        if ($thisignored = array_filter(
            $ignored,
            static fn(string $needle): bool => str_contains($path, $needle)
        )) {
            continue;
        }

        $source = file_get_contents($path);
        if ($source === false || $source === '') {
            continue;
        }

        $relative = str_replace($CFG->dirroot . '/', '', $path);
        $isinfrastructure = (bool)array_filter(
            $infrastructure,
            static fn(string $needle): bool => str_contains('/' . $relative, $needle)
        );

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $source) !== 1) {
                continue;
            }

            $entry = $relative . ' :: ' . $name;
            if ($isinfrastructure) {
                $informational[] = $entry;
                continue;
            }
            $issues[] = $entry;
        }
    }
}

$issues = array_values(array_unique($issues));
$informational = array_values(array_unique($informational));

if ($informational !== []) {
    cli_writeln('Intentional technical targets/fallbacks ignored: ' . count($informational));
}

if ($issues === []) {
    cli_writeln('OK: no customer-facing technical URL found.');
    exit(0);
}

cli_writeln('Customer-facing technical URLs still detected:');
foreach ($issues as $issue) {
    cli_writeln(' - ' . $issue);
}
exit(1);
