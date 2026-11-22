<?php
// CampusFR C2.1 — ONE dedicated DEV demo collection, no Commerce changes.
define('CLI_SCRIPT', true);
$root = realpath(dirname(__DIR__, 3));
if ($root !== '/var/www/moodle-dev') {
    fwrite(STDERR, "STOP : configuration réservée à DEV.\n");
    exit(3);
}
require_once($root . '/config.php');

if (count($argv) !== 2 || !in_array($argv[1], ['--check', '--apply'], true)) {
    fwrite(STDERR, "Usage: php configure_presentation.php --check|--apply\n");
    exit(2);
}
$apply = $argv[1] === '--apply';
global $DB;
if ((int) get_config('local_campusreader', 'version') !== 2026111302) {
    throw new RuntimeException('C2.1 non migrée : version DB attendue 2026111302.');
}
$course = $DB->get_record('course', ['id' => 19], 'id,shortname,visible', MUST_EXIST);
if ($course->shortname !== 'AVENT2026-DEV' || (int) $course->visible !== 0) {
    throw new RuntimeException('Cours support #19 inattendu.');
}
$collection = $DB->get_record('local_cr_collection', ['slug' => 'avent-2026'], '*', MUST_EXIST);
if ((int) $collection->courseid !== 19 || (int) $collection->published !== 0
    || $collection->timezone !== 'Europe/Paris') {
    throw new RuntimeException('Collection différente de la démo C1.');
}
$timestamp = (new DateTimeImmutable('2027-01-01 00:00:00', new DateTimeZone('Europe/Paris')))->getTimestamp();
if ($timestamp < 1) {
    throw new RuntimeException('Date invalide.');
}
if ($collection->presentationmode !== 'auto' || !in_array((int) $collection->libraryafter, [0, $timestamp], true)) {
    throw new RuntimeException('Présentation existante personnalisée : aucun écrasement.');
}
$slots = array_values($DB->get_records('local_cr_slot', ['collectionid' => $collection->id], 'position ASC'));
if (count($slots) !== 31) {
    throw new RuntimeException('La collection n’a pas les 31 cases attendues.');
}
$decor = [1 => 'feature', 4 => 'tall', 7 => 'wide', 11 => 'wide', 15 => 'tall',
    18 => 'wide', 22 => 'feature', 25 => 'wide', 28 => 'tall', 31 => 'feature'];
$base = new DateTimeImmutable('2026-12-01 00:00:00', new DateTimeZone('Europe/Paris'));
foreach ($slots as $i => $slot) {
    $day = $i + 1;
    $expected = $base->modify('+' . $i . ' days')->getTimestamp();
    $size = $decor[$day] ?? 'normal';
    if ((int) $slot->position !== $day || (int) $slot->unlockat !== $expected
        || !in_array($slot->layoutsize, ['normal', $size], true)) {
        throw new RuntimeException('Case ou configuration inattendue #' . $day);
    }
}
if (!$apply) {
    echo "C2.1 CHECK OK : 31 cases, 10 tailles éditoriales, mode auto, cours #19 masqué.\n";
    echo "Bascule après : 2027-01-01 00:00 Europe/Paris (timestamp {$timestamp}).\n";
    echo "Aucune écriture.\n";
    exit(0);
}
$transaction = $DB->start_delegated_transaction();
if ((int) $collection->libraryafter === 0) {
    $DB->set_field('local_cr_collection', 'libraryafter', $timestamp, ['id' => $collection->id]);
}
foreach ($slots as $i => $slot) {
    $size = $decor[$i + 1] ?? 'normal';
    if ($slot->layoutsize !== $size) {
        $DB->set_field('local_cr_slot', 'layoutsize', $size, ['id' => $slot->id]);
    }
}
$transaction->allow_commit();
echo "C2.1 APPLY OK : présentation auto, 31 cases et bascule 01/01/2027 00:00 Europe/Paris.\n";
echo "Collection toujours en brouillon, aucun livre publié, aucun produit ni inscription modifié.\n";
