<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

$root = $CFG->dirroot . '/theme/edly';
$errors = [];

$storefront = file_get_contents($root . '/templates/storefront_shell.mustache');
$footer = file_get_contents($root . '/templates/theme_boost/footer.mustache');
$navbar = file_get_contents($root . '/templates/theme_boost/navbar.mustache');
$context = file_get_contents($root . '/inc/edly_themehandler_context.php');

if (substr_count($storefront, 'output.standard_end_of_body_html') !== 1) {
    $errors[] = 'storefront_shell.mustache must contain one conditional standard_end_of_body_html fallback.';
}
if (strpos($storefront, '{{#storefrontminimal}}') === false) {
    $errors[] = 'The Storefront fallback must be restricted to minimal layouts.';
}
if (substr_count($footer, 'output.standard_end_of_body_html') !== 1) {
    $errors[] = 'The standard Edly footer must emit standard_end_of_body_html exactly once.';
}
if (preg_match("~require\\s*\\(\\s*\\['theme_edly/mobile_menu'\\]~", $navbar)) {
    $errors[] = 'navbar.mustache still contains an inline mobile_menu require call.';
}
if (strpos($context, "js_call_amd('theme_edly/mobile_menu', 'init')") === false) {
    $errors[] = 'mobile_menu is not registered through js_call_amd().';
}

if ($errors !== []) {
    fwrite(STDERR, "Storefront JavaScript audit failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

echo "OK: Storefront emits one Moodle end-of-body block per layout path.\n";
echo "OK: Mobile navigation is registered through js_call_amd().\n";
exit(0);
