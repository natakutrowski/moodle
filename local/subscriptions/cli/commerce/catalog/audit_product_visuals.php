<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'sku' => null,
        'productid' => 0,
        'json' => false,
        'include-inactive' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    $help = <<<TXT
CampusFR Commerce — Product visual audit (READ-ONLY)

Options:
  --sku=SKU          Audit one product by SKU.
  --productid=ID     Audit one product by Native product ID.
  --json             Return machine-readable JSON.
  --include-inactive Include inactive and archived products.
  -h, --help         Show this help.

Examples:
  php local/subscriptions/cli/commerce/catalog/audit_product_visuals.php
  php local/subscriptions/cli/commerce/catalog/audit_product_visuals.php --sku=SUB.PLAN.32
  php local/subscriptions/cli/commerce/catalog/audit_product_visuals.php --json
  php local/subscriptions/cli/commerce/catalog/audit_product_visuals.php --include-inactive

TXT;
    echo $help;
    exit(0);
}

$service = new CommerceProductVisualAuditService(
    $DB,
    context_system::instance()
);
$report = $service->audit(
    $options['sku'] !== null ? (string)$options['sku'] : null,
    (int)$options['productid'] > 0
        ? (int)$options['productid']
        : null,
    !empty($options['include-inactive'])
);

if ($options['json']) {
    echo json_encode(
        $report,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
    exit(0);
}

echo "============================================================\n";
echo "CampusFR Commerce — Product Visual Audit\n";
echo "MODE: READ-ONLY\n";
echo "============================================================\n\n";

foreach ($report['products'] as $product) {
    echo $product['sku']
        . ' (#'
        . $product['id']
        . ') — '
        . $product['name']
        . ' ['
        . $product['type']
        . "]\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($product['formats'] as $format) {
        $status = $format['available']
            ? ($format['ratio_ok'] ? 'OK' : 'RATIO MISMATCH')
            : (
                !empty($format['fallback_available'])
                    ? 'FALLBACK ONLY'
                    : 'MISSING'
            );
        $source = $format['source_role'] ?? 'NONE';
        $dimensions = (
            $format['available']
            || !empty($format['fallback_available'])
        )
            ? $format['width'] . 'x' . $format['height']
            : 'NONE';

        echo sprintf(
            "%-10s %-15s target=%-9s source=%-15s actual=%s\n",
            strtoupper($format['format']),
            $status,
            $format['ratio'],
            $source,
            $dimensions
        );
        echo '  Surfaces: '
            . implode(', ', $format['surfaces'])
            . "\n";
    }

    echo 'Placeholder: ' . $product['placeholder_icon'] . "\n\n";
}

echo "============================================================\n";
echo 'Products: ' . $report['summary']['products'] . "\n";
echo 'Missing master formats: '
    . $report['summary']['missing_master_formats']
    . "\n";
echo 'Fallback-only formats: '
    . $report['summary']['fallback_only_formats']
    . "\n";
echo 'Ratio mismatches: '
    . $report['summary']['ratio_mismatches']
    . "\n";
echo 'Includes inactive: '
    . (
        $report['summary']['includes_inactive']
            ? 'yes'
            : 'no'
    )
    . "\n";
echo "STATUS: READ-ONLY DIAGNOSTIC COMPLETE\n";
echo "============================================================\n";
