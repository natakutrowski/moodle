<?php
// CampusFR Reader C1 — DEV-only post-upgrade audit, read-only.
define('CLI_SCRIPT', true);
$root = realpath(dirname(__DIR__, 3));
if ($root !== '/var/www/moodle-dev') {
    fwrite(STDERR, "STOP : contrôles réservés à DEV.\n");
    exit(3);
}
require_once($root . '/config.php');

global $DB;
$tables = ['local_cr_collection', 'local_cr_book', 'local_cr_slot', 'local_cr_page', 'local_cr_progress'];
foreach ($tables as $table) {
    if (!$DB->get_manager()->table_exists(new xmldb_table($table))) {
        throw new RuntimeException('Table absente : ' . $table);
    }
    echo "OK TABLE $table\n";
}
$pluginversion = (int) get_config('local_campusreader', 'version');
if ($pluginversion < 2026111301) {
    throw new RuntimeException('Version DB minimum 2026111301, obtenue ' . $pluginversion);
}
$collection = $DB->get_record('local_cr_collection', ['slug' => 'avent-2026']);
if (!$collection) {
    echo "DB OK, collection pas encore initialisée.\n";
    exit(0);
}
if ((int)$collection->courseid !== 19 || (int)$collection->published !== 0) {
    throw new RuntimeException('Collection initiale doit être brouillon, liée au cours #19.');
}
$slots = $DB->get_records('local_cr_slot', ['collectionid' => $collection->id], 'position ASC');
if (count($slots) !== 31) {
    throw new RuntimeException('31 cases attendues, reçues ' . count($slots));
}
$tz = new DateTimeZone('Europe/Paris');
foreach (array_values($slots) as $idx => $slot) {
    $d = (new DateTimeImmutable('@' . $slot->unlockat))->setTimezone($tz)->format('Y-m-d H:i');
    $expected = '2026-12-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) . ' 00:00';
    if ((int)$slot->position !== $idx + 1 || $d !== $expected) {
        throw new RuntimeException('Case #' . ($idx + 1) . ' incohérente: ' . $d);
    }
}
foreach (['noel-des-etoiles' => 10, 'le-renard-de-minuit' => 6] as $slug => $total) {
    $book = $DB->get_record('local_cr_book', ['collectionid' => $collection->id, 'slug' => $slug], '*', MUST_EXIST);
    if ((int)$book->published !== 0 || $DB->count_records('local_cr_page', ['bookid' => $book->id]) !== $total) {
        throw new RuntimeException("Livre brouillon inattendu $slug");
    }
    $bookslots = $DB->count_records('local_cr_slot', ['collectionid' => $collection->id, 'bookid' => $book->id]);
    if ($bookslots !== 1) {
        throw new RuntimeException('Book slot mapping invalide : ' . $slug);
    }
    echo "OK BOOK $slug ($total pages, draft)\n";
}
if ($DB->count_records('local_cr_progress') !== 0) {
    echo "NOTE : la table progression contient déjà des lignes.\n";
}
echo 'OK C1 : 31 cases Paris, livres fictifs non publiés, cours support #19. Aucun accès public ouvert.' . "\n";
