<?php
// Phase 7.92C static UX audit.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$errors = 0;

$checks = [
    'admin/subscriptions/index.php' => ['CommerceSectionNavigationRenderer::SUBSCRIPTIONS', 'crm-commerce-list'],
    'admin/subscriptions/view.php' => ['CommerceSectionNavigationRenderer::SUBSCRIPTIONS', 'crm-commerce-actionbar', 'crm-commerce-detail-grid'],
    'admin/subscriptions/add.php' => ['CommerceSectionNavigationRenderer::SUBSCRIPTIONS'],
    'admin/subscriptions/edit.php' => ['CommerceSectionNavigationRenderer::SUBSCRIPTIONS'],
    'admin/digital/purchases/index.php' => ['CommerceSectionNavigationRenderer::DIGITAL_PURCHASES', 'crm-commerce-filter-card'],
    'admin/digital/purchases/view.php' => ['CommerceSectionNavigationRenderer::DIGITAL_PURCHASES', 'crm-commerce-actionbar', 'crm-commerce-detail-grid'],
    'admin/digital/products/index.php' => ['CommerceSectionNavigationRenderer::DIGITAL_PRODUCTS', 'crm-commerce-actionbar'],
    'admin/digital/products/edit.php' => ['CommerceSectionNavigationRenderer::DIGITAL_PRODUCTS'],
];

foreach ($checks as $relative => $needles) {
    $content = file_get_contents($root . '/' . $relative);
    foreach ($needles as $needle) {
        if (strpos($content, $needle) === false) {
            echo "[ERROR] {$relative}: missing {$needle}\n";
            $errors++;
        }
    }
    if (!$errors) {
        echo "[OK] {$relative}\n";
    }
}

$css = file_get_contents($root . '/styles.css');
foreach (['.crm-commerce-actionbar', '.crm-commerce-filter-card', '.crm-commerce-table-wrap', '.crm-commerce-detail-grid', '.crm-commerce-status'] as $selector) {
    if (strpos($css, $selector) === false) {
        echo "[ERROR] styles.css: missing {$selector}\n";
        $errors++;
    }
}

exit($errors ? 1 : 0);
