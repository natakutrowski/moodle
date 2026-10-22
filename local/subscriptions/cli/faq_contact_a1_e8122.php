<?php
// Moodle CLI tool — read-only diagnosis by default. Use --apply explicitly to repair the FAQ contact block.
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

[$options, $unrecognized] = cli_get_params([
    'apply' => false,
    'help' => false,
], [
    'a' => 'apply',
    'h' => 'help',
]);
if ($unrecognized !== []) {
    cli_error('Unknown arguments: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    echo "Diagnose the CampusFR A1 FAQ contact block (read-only by default).\n";
    echo "php local/subscriptions/cli/faq_contact_a1_e8122.php [--apply]\n";
    echo "--apply: add or enable faq_contact, then place it directly after FAQ.\n";
    exit(0);
}

$showroomkey = 'a1-course';
$repository = new CommerceShowroomCmsRepository($DB);
$showroom = $repository->get_by_key($showroomkey);
if ($showroom === null) {
    cli_error('The Showroom a1-course does not exist. No changes made.');
}
if (!CommerceShowroomBlockTypeRegistry::exists('faq_contact')) {
    cli_error('Block type faq_contact is not registered. Apply E8.12.1/E8.14.1 DELTA first.');
}

$blocks = $repository->blocks((int)$showroom->id);
$bytype = [];
foreach ($blocks as $block) {
    $bytype[(string)$block->blocktype][] = $block;
}

echo "Showroom: {$showroomkey} (id={$showroom->id}, status={$showroom->status})\n";
foreach (['faq', 'support', 'faq_contact', 'final_cta'] as $type) {
    $matches = $bytype[$type] ?? [];
    if ($matches === []) {
        echo "  {$type}: MISSING\n";
        continue;
    }
    foreach ($matches as $block) {
        $config = json_decode((string)$block->configjson, true);
        $fields = is_array($config) ? count($config) : 0;
        echo "  {$type}: id={$block->id}, enabled={$block->enabled}, sortorder={$block->sortorder}, configfields={$fields}\n";
    }
}

$runtime = CommerceShowroomRuntimeBlockSet::load_preview($DB, (int)$showroom->id);
$ordered = array_map(static fn(array $item): string => (string)$item['type'], $runtime->to_template_data()['showroomorderedblocks']);
echo 'Rendered block order in Preview: ' . implode(' > ', $ordered) . "\n";
$faqpos = array_search('faq', $ordered, true);
$contactpos = array_search('faq_contact', $ordered, true);
$ready = $faqpos !== false && $contactpos === $faqpos + 1;
echo 'FAQ contact enabled and immediately after FAQ: ' . ($ready ? 'YES' : 'NO') . "\n";
if (!$options['apply']) {
    echo "Read-only mode: no changes made. Use --apply to repair the block.\n";
    exit(0);
}

if (count($bytype['faq_contact'] ?? []) > 1) {
    cli_error('Multiple faq_contact blocks exist. Resolve duplicates in the Builder; no changes made.');
}
if (count($bytype['faq'] ?? []) !== 1) {
    cli_error('Expected exactly one FAQ block. Resolve this in the Builder; no changes made.');
}

$configpath = __DIR__ . '/faq_contact_config_e8122.json';
$configjson = file_get_contents($configpath);
if ($configjson === false || !is_array(json_decode($configjson, true))) {
    cli_error('The FAQ contact JSON is missing or invalid; no changes made.');
}

$userid = (int)($USER->id ?? 0);
$transaction = $DB->start_delegated_transaction();
$existing = ($bytype['faq_contact'] ?? [])[0] ?? null;
if ($existing === null) {
    $newid = $repository->save_block((int)$showroom->id, [
        'blocktype' => 'faq_contact',
        'sortorder' => 9999,
        'enabled' => true,
        'configjson' => $configjson,
    ], $userid);
    echo "Created FAQ contact block id={$newid}.\n";
} else {
    $existingjson = json_decode((string)$existing->configjson, true);
    $useconfig = is_array($existingjson) && $existingjson !== []
        ? (string)$existing->configjson : $configjson;
    $repository->save_block((int)$showroom->id, [
        'id' => (int)$existing->id,
        'blocktype' => 'faq_contact',
        'blockkey' => (string)$existing->blockkey,
        'sortorder' => (int)$existing->sortorder,
        'enabled' => true,
        'configjson' => $useconfig,
    ], $userid);
    echo "Enabled FAQ contact block id={$existing->id}; existing nonempty JSON preserved.\n";
}

$blocks = $repository->blocks((int)$showroom->id);
$contactid = null;
$orderedids = [];
foreach ($blocks as $block) {
    if ((string)$block->blocktype === 'faq_contact') {
        $contactid = (int)$block->id;
    } else {
        $orderedids[] = (int)$block->id;
    }
}
$faqid = (int)$bytype['faq'][0]->id;
$faqindex = array_search($faqid, $orderedids, true);
if ($faqindex === false || $contactid === null) {
    cli_error('FAQ or contact block could not be located. Transaction not committed.');
}
array_splice($orderedids, $faqindex + 1, 0, [$contactid]);
$repository->reorder_blocks((int)$showroom->id, $orderedids, $userid);
$transaction->allow_commit();

$runtime = CommerceShowroomRuntimeBlockSet::load_preview($DB, (int)$showroom->id);
$ordered = array_map(static fn(array $item): string => (string)$item['type'], $runtime->to_template_data()['showroomorderedblocks']);
echo 'Updated block order: ' . implode(' > ', $ordered) . "\n";
echo "Success. Review the Showroom preview; no publication/status was changed.\n";
