<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB;

$now = time();

$slug = 'verbes-3e-groupe';

$data = [
    'slug' => $slug,
    'name' => 'Les verbes du 3e groupe — PDF pratique',
    'description' => 'Un PDF pratique pour comprendre, apprendre et réviser les verbes du 3e groupe en français.',
    'descriptionformat' => FORMAT_HTML,
    'filename' => 'campusfr-verbes-3e-groupe.pdf',
    'mobile_filename' => 'campusfr-verbes-3e-groupe-mobile.pdf',
    'coverimage' => 'pdf_3e_groupe_cover.png',
    'price_eur' => 4.90,
    'price_rub' => 470.00,
    'enabled' => 1,
    'sortorder' => 10,
];

$existing = $DB->get_record('subscription_digital_product', ['slug' => $slug]);

if ($existing) {
    $product = (object)$data;
    $product->id = $existing->id;
    $product->creation_date = $existing->creation_date ?? $now;
    $product->last_update = $now;

    $DB->update_record('subscription_digital_product', $product);

    mtrace("Digital product updated. ID: {$existing->id}, slug: {$slug}");
    exit(0);
}

$product = (object)$data;
$product->creation_date = $now;
$product->last_update = $now;

$id = $DB->insert_record('subscription_digital_product', $product);

mtrace("Digital product created. ID: {$id}, slug: {$slug}");
