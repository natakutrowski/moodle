<?php

// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewZipImporter;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'showroom' => 'third-group-verbs',
    'blockid' => 0,
    'file' => '',
    'language' => 'default',
    'dry-run' => false,
], [
    'h' => 'help',
    's' => 'showroom',
    'b' => 'blockid',
    'f' => 'file',
    'l' => 'language',
    'd' => 'dry-run',
]);

if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}

if ($options['help']) {
    echo <<<HELP
Import Exercise Explorer screenshots into a Commerce Showroom block.

Options:
  --showroom=KEY       Showroom key (default: third-group-verbs)
  --blockid=ID         Explicit exercise_explorer block id (optional)
  --file=/path/file.zip
  --language=LANG      default, fr, en or ru (default: default)
  --dry-run            Analyse the ZIP without writing Moodle files
  -h, --help           Show this help

Examples:
  php local/subscriptions/cli/commerce/import_showroom_exercise_previews.php \\
    --showroom=third-group-verbs --file=/tmp/blocExercices.zip --language=default --dry-run

  php local/subscriptions/cli/commerce/import_showroom_exercise_previews.php \\
    --showroom=third-group-verbs --file=/tmp/blocExercices.zip --language=default

HELP;
    exit(0);
}

$zippath = trim((string)$options['file']);
if ($zippath === '') {
    cli_error('--file is required.');
}
$language = strtolower(trim((string)$options['language']));
if (!in_array($language, CommerceShowroomExercisePreviewMediaManager::LANGUAGES, true)) {
    cli_error('--language must be default, fr, en or ru.');
}

$repository = new CommerceShowroomCmsRepository($DB);
$blockid = (int)$options['blockid'];

if ($blockid <= 0) {
    $showroom = $repository->get_by_key((string)$options['showroom']);
    if ($showroom === null) {
        cli_error('Unknown Showroom: ' . $options['showroom']);
    }
    $matches = array_values(array_filter(
        $repository->blocks((int)$showroom->id),
        static fn(stdClass $block): bool => (string)$block->blocktype === 'exercise_explorer'
    ));
    if (count($matches) !== 1) {
        cli_error('Expected exactly one exercise_explorer block; use --blockid to choose explicitly.');
    }
    $blockid = (int)$matches[0]->id;
} else {
    $block = $repository->get_block($blockid);
    if ($block === null || (string)$block->blocktype !== 'exercise_explorer') {
        cli_error('--blockid does not reference an exercise_explorer block.');
    }
}

$context = context_system::instance();
$media = new CommerceShowroomExercisePreviewMediaManager(
    $context,
    new CommerceShowroomBlockMediaManager($context)
);
$importer = new CommerceShowroomExercisePreviewZipImporter($media);

$result = $importer->import(
    $blockid,
    $zippath,
    $language,
    !empty($options['dry-run'])
);

echo 'Exercise Explorer media import' . PHP_EOL;
echo 'Block: ' . $blockid . PHP_EOL;
echo 'Language slot: ' . $language . PHP_EOL;
echo 'Mode: ' . (!empty($options['dry-run']) ? 'DRY RUN' : 'WRITE') . PHP_EOL;
echo PHP_EOL;

foreach ($result['matched'] as $key => $filename) {
    echo '[MATCH] ' . $key . ' <- ' . $filename . PHP_EOL;
}
foreach ($result['unmatched'] as $filename) {
    echo '[SKIP]  ' . $filename . PHP_EOL;
}
foreach ($result['missing'] as $key) {
    echo '[MISS]  ' . $key . PHP_EOL;
}

echo PHP_EOL;
echo 'Matched: ' . count($result['matched']) . '/12' . PHP_EOL;
echo 'Stored: ' . $result['stored'] . PHP_EOL;
echo 'Unmatched: ' . count($result['unmatched']) . PHP_EOL;
echo 'Missing: ' . count($result['missing']) . PHP_EOL;

if ($result['missing'] !== []) {
    cli_error('Import incomplete: one or more canonical exercise previews are missing.');
}

echo '[OK]' . PHP_EOL;
