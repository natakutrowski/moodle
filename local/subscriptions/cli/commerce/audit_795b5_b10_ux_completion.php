<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$checks = [
    'shared_page_headers' => [
        'admin/commerce/products/edit.php',
        'admin/commerce/products/components.php',
        'admin/commerce/products/pricing.php',
        'admin/commerce/products/preview.php',
    ],
    'shared_form_actions' => [
        'admin/commerce/products/edit.php',
        'admin/commerce/products/components.php',
        'admin/commerce/products/pricing.php',
    ],
    'responsive_polish' => ['styles.css'],
];

$errors = [];
foreach ($checks['shared_page_headers'] as $relative) {
    $content = file_get_contents($root . '/' . $relative);
    if ($content === false || !str_contains($content, 'CommerceProductPageHeaderRenderer::render')) {
        $errors[] = $relative . ': shared page header missing';
    }
}
foreach ($checks['shared_form_actions'] as $relative) {
    $content = file_get_contents($root . '/' . $relative);
    if ($content === false || !str_contains($content, 'CommerceDesignSystemRenderer::form_actions')) {
        $errors[] = $relative . ': shared form actions missing';
    }
}
$styles = file_get_contents($root . '/styles.css');
if ($styles === false || !str_contains($styles, 'Commerce 7.95B5-B10') || !str_contains($styles, '@media (max-width: 575.98px)')) {
    $errors[] = 'styles.css: responsive completion missing';
}

$passed = $errors === [];
echo "== 7.95B5-B10 Commerce UX completion ==\n\n";
echo 'shared_page_headers       ' . ($passed ? 'OK' : 'FAIL') . "\n";
echo 'shared_form_actions       ' . ($passed ? 'OK' : 'FAIL') . "\n";
echo 'responsive_polish        ' . ($passed ? 'OK' : 'FAIL') . "\n\n";
if ($passed) {
    echo "[CERTIFIED]\n";
    exit(0);
}
foreach ($errors as $error) {
    echo '[ERROR] ' . $error . "\n";
}
exit(1);
