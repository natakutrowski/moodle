<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$checks = [];
$check = static function(string $name, bool $ok) use (&$checks): void {
    $checks[$name] = $ok;
};
$read = static fn(string $relative): string => (string)file_get_contents($root . '/' . $relative);

$media = $read('classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');
$assets = $read('admin/commerce/products/assets.php');
$status = $read('admin/commerce/products/status.php');
$index = $read('admin/commerce/products/index.php');
$pluginfile = $read('lib.php');

$check('moodle_file_api_media', str_contains($media, 'create_file_from_pathname') && !str_contains($media, 'mkdir('));
$check('catalog_media_filearea', str_contains($media, "FILEAREA = 'catalog_media'") && str_contains($pluginfile, "'catalog_media'"));
$check('cover_for_all_products', str_contains($assets, "ROLE_COVER") && !str_contains($assets, "get_type() === 'bundle'"));
$check('digital_files_separated', str_contains($assets, 'commerce_edit_digital_source') && str_contains($assets, 'mobile_filename'));
$check('activation_validation', str_contains($status, 'CommerceCatalogActivationValidator') && str_contains($status, 'is_valid()'));
$check('active_inactive_actions', str_contains($index, "action' => 'activate'") && str_contains($index, "action' => 'deactivate'"));
$check('simple_status_display', str_contains($index, "badge('lifecycle'") && !str_contains($index, "select('visibility'"));
$check('safe_cli_config_path', str_contains($read('cli/commerce/audit_795e16_e17_media_workflow.php'), "__DIR__ . '/../../../../config.php'"));

echo "== 7.95E16-E17 Media and workflow ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-44s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
