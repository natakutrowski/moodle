<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

[$options, $unrecognised] = cli_get_params([
    'sku' => 'SUB.PLAN.30',
    'lang' => 'fr',
    'json' => false,
    'help' => false,
], [
    's' => 'sku',
    'l' => 'lang',
    'j' => 'json',
    'h' => 'help',
]);

if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if (!empty($options['help'])) {
    echo <<<HELP
CampusFR Storefront media audit (read-only).

Options:
--sku=SUB.PLAN.30  Product SKU to inspect.
--lang=fr          Storefront locale to inspect.
--json             Emit JSON.
-h, --help         Show this help.

The command does not modify metadata or files.
HELP;
    exit(0);
}

$sku = strtoupper(trim((string)$options['sku']));
$language = strtolower(trim((string)$options['lang']));
$factory = new CommerceCatalogFactory($DB);
$product = $factory->product_manager()->get_editor_data($sku)->get_product();
$editor = new CommerceStorefrontPageEditor();
$files = CommerceStorefrontContentFileService::create();
$definition = $editor->definition_from_product($product, $language);
$rows = $editor->form_rows($product, $language);

$report = [
    'sku' => $product->get_sku(),
    'language' => $language,
    'shell_mode' => $definition['shell_mode'],
    'commerce_position' => $definition['commerce_position'],
    'global_zones' => $definition['global_zones'] ?? [],
    'sections' => [],
];

foreach ($rows as $index => $row) {
    $type = trim((string)($row['type'] ?? ''));
    if ($type === '') {
        continue;
    }
    $itemid = (int)($row['mediaitemid'] ?? 0);
    $section = [
        'index' => $index,
        'id' => (string)($row['id'] ?? ''),
        'type' => $type,
        'visible' => !empty($row['visible']),
        'mediaitemid' => $itemid,
        'content_has_draftfile' => str_contains((string)($row['content'] ?? ''), 'draftfile.php'),
        'content_has_pluginfile_token' => str_contains((string)($row['content'] ?? ''), '@@PLUGINFILE@@'),
        'slots' => [],
    ];
    foreach (['image', 'video', 'poster', 'h5p'] as $slot) {
        $diagnostic = $files->slot_diagnostic($itemid, $slot);
        $section['slots'][$slot] = $diagnostic;
        if ($diagnostic !== null) {
            $url = $files->get_slot_url($itemid, $slot);
            $section['slots'][$slot]['url'] = $url?->out(false);
        }
    }
    $report['sections'][] = $section;
}

if (!empty($options['json'])) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

echo "============================================================\n";
echo "CampusFR Storefront media audit\n";
echo "============================================================\n";
echo 'SKU: ' . $report['sku'] . PHP_EOL;
echo 'Language: ' . $report['language'] . PHP_EOL;
echo 'Shell: ' . $report['shell_mode'] . PHP_EOL;
echo 'Commerce position: ' . $report['commerce_position'] . PHP_EOL;
echo 'Global zones: ' . implode(' > ', $report['global_zones']) . PHP_EOL . PHP_EOL;

foreach ($report['sections'] as $section) {
    echo sprintf(
        "[%02d] id=%s type=%s visible=%s mediaitemid=%d\n",
        $section['index'],
        $section['id'] !== '' ? $section['id'] : '<empty>',
        $section['type'],
        $section['visible'] ? 'yes' : 'no',
        $section['mediaitemid']
    );
    echo '     content: draftfile=' . ($section['content_has_draftfile'] ? 'YES' : 'no')
        . ' pluginfile-token=' . ($section['content_has_pluginfile_token'] ? 'yes' : 'no') . PHP_EOL;
    foreach ($section['slots'] as $slot => $diagnostic) {
        if ($diagnostic === null) {
            echo sprintf("     %-6s <missing>\n", $slot);
            continue;
        }
        echo sprintf(
            "     %-6s %s (%s) %d bytes\n",
            $slot,
            $diagnostic['filename'],
            $diagnostic['mimetype'],
            $diagnostic['filesize']
        );
        echo '            ' . $diagnostic['url'] . PHP_EOL;
    }
    echo PHP_EOL;
}

exit(0);
