<?php
// CampusFR reader C2.1 — DEV read-only verification after migration/configuration.
define('CLI_SCRIPT', true);
$root = realpath(dirname(__DIR__, 3));
if ($root !== '/var/www/moodle-dev') {
    fwrite(STDERR, "STOP : DEV uniquement.\n"); exit(3);
}
require_once($root . '/config.php');

global $DB;
if ((int) get_config('local_campusreader', 'version') !== 2026111302) {
    throw new RuntimeException('Version DB C2.1 incorrecte.');
}
foreach (['presentationmode', 'libraryafter'] as $name) {
    $field = new xmldb_field($name);
    if (!$DB->get_manager()->field_exists(new xmldb_table('local_cr_collection'), $field)) {
        throw new RuntimeException('Champ collection manquant : ' . $name);
    }
}
if (!$DB->get_manager()->field_exists(new xmldb_table('local_cr_slot'), new xmldb_field('layoutsize'))) {
    throw new RuntimeException('Champ layoutsize manquant.');
}
$collection = $DB->get_record('local_cr_collection', ['slug' => 'avent-2026'], '*', MUST_EXIST);
$timestamp = (new DateTimeImmutable('2027-01-01 00:00:00', new DateTimeZone('Europe/Paris')))->getTimestamp();
if ($collection->presentationmode !== 'auto' || (int)$collection->libraryafter !== $timestamp
    || (int)$collection->published !== 0 || (int)$collection->courseid !== 19) {
    throw new RuntimeException('Collection de démonstration non conforme.');
}
$slots = $DB->get_records('local_cr_slot', ['collectionid' => $collection->id], 'position ASC');
if (count($slots) !== 31) throw new RuntimeException('31 cases attendues.');
$counts = [];
foreach ($slots as $slot) {
    if (!in_array($slot->layoutsize, ['normal', 'wide', 'tall', 'feature'], true)) {
        throw new RuntimeException('Taille de case invalide.');
    }
    $counts[$slot->layoutsize] = ($counts[$slot->layoutsize] ?? 0) + 1;
}
if ((int)$DB->count_records('local_cr_progress') !== 0) {
    echo "ATTENTION : la progression contient des données, pas de changement effectué.\n";
}
echo 'OK C2.1 : collection #'.$collection->id.' en brouillon ; '.count($slots).' cases ; ';
foreach ($counts as $size => $count) echo "$size=$count ";
echo "\nOK Bascule : 2027-01-01 00:00 Europe/Paris. Aucun changement Commerce.\n";
