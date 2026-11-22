<?php
// CampusFR reader C1 — DEV ONLY, explicit controlled demonstration seed.
define('CLI_SCRIPT', true);
$root = realpath(dirname(__DIR__, 3));
if ($root !== '/var/www/moodle-dev') {
    fwrite(STDERR, "STOP: seed réservé à /var/www/moodle-dev\n");
    exit(3);
}
require_once($root . '/config.php');

use local_campusreader\local\demo_book_repository;

$options = $argv[1] ?? '';
if (!in_array($options, ['--check', '--apply'], true) || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php seed_demo.php --check|--apply\n");
    exit(2);
}
$apply = $options === '--apply';
global $DB;
$courseid = 19;
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
if ($course->shortname !== 'AVENT2026-DEV' || (int) $course->visible !== 0) {
    throw new RuntimeException('Le cours support DEV #19 ne correspond pas aux garde-fous attendus.');
}
$tables = ['local_cr_collection', 'local_cr_book', 'local_cr_slot', 'local_cr_page', 'local_cr_progress'];
foreach ($tables as $table) {
    if (!$DB->get_manager()->table_exists(new xmldb_table($table))) {
        throw new RuntimeException('Table manquante : ' . $table . '. Lancer l’upgrade Moodle avant seed.');
    }
}
$slug = 'avent-2026';
$collection = $DB->get_record('local_cr_collection', ['slug' => $slug]);
if ($collection && ((int) $collection->courseid !== $courseid || $collection->timezone !== 'Europe/Paris'
    || (int) $collection->published !== 0)) {
    throw new RuntimeException('Collection existante incompatible ; aucune modification.');
}
$bookmap = [1 => 'noel-des-etoiles', 2 => 'le-renard-de-minuit'];
$demo = demo_book_repository::all();
foreach ($bookmap as $name) {
    if (!isset($demo[$name])) {
        throw new RuntimeException('Livre JSON introuvable : ' . $name);
    }
}
$tz = new DateTimeZone('Europe/Paris');
$start = new DateTimeImmutable('2026-12-01 00:00:00', $tz);
for ($day = 1; $day <= 31; $day++) {
    $dt = $start->modify('+' . ($day - 1) . ' days');
    if ($dt->format('Y-m-d H:i:s') !== '2026-12-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT) . ' 00:00:00') {
        throw new RuntimeException('Erreur de calcul des jours.');
    }
    if ($collection) {
        $slot = $DB->get_record('local_cr_slot', ['collectionid' => $collection->id, 'position' => $day]);
        if ($slot && (int)$slot->unlockat !== $dt->getTimestamp()) {
            throw new RuntimeException('Case ' . $day . ' existe avec une autre date ; refus.');
        }
    }
}
if ($collection) {
    foreach ($bookmap as $day => $id) {
        $book = $DB->get_record('local_cr_book', ['collectionid' => $collection->id, 'slug' => $id]);
        if ($book && (int) $book->published !== 0) {
            throw new RuntimeException('Livre déjà publié ; seed ne doit pas le modifier.');
        }
        $slot = $DB->get_record('local_cr_slot', ['collectionid' => $collection->id, 'position' => $day]);
        if ($slot && $book && !empty($slot->bookid) && (int)$slot->bookid !== (int)$book->id) {
            throw new RuntimeException('Conflit sur la case ' . $day);
        }
    }
}
foreach ($demo as $id => $book) {
    foreach ($book['pages'] as $page) {
        if (!empty($page['image'])) {
            $asset = __DIR__ . '/../seed/assets/' . pathinfo($page['image'], PATHINFO_FILENAME) . '.png';
            if (!is_readable($asset) || mime_content_type($asset) !== 'image/png') {
                throw new RuntimeException('Image PNG de seed absente : ' . $asset);
            }
        }
    }
}
if (!$apply) {
    echo "C1 SEED CHECK OK : cours #19, collection $slug, 31 cases / 2 livres fictifs (brouillons).\n";
    echo "Exemples : 01/12/2026 00:00 Europe/Paris => " . $start->getTimestamp() . " UTC unix\n";
    echo "          31/12/2026 00:00 Europe/Paris => " . $start->modify('+30 days')->getTimestamp() . " UTC unix\n";
    echo "Aucune écriture.\n";
    exit(0);
}
$now = time();
$transaction = $DB->start_delegated_transaction();
if (!$collection) {
    $cid = $DB->insert_record('local_cr_collection', (object)[
        'slug' => $slug, 'title' => 'Calendrier de l’Avent & du Nouvel An 2026',
        'courseid' => $courseid, 'timezone' => 'Europe/Paris', 'published' => 0,
        'timecreated' => $now, 'timemodified' => $now,
    ]);
    $collection = $DB->get_record('local_cr_collection', ['id' => $cid], '*', MUST_EXIST);
    echo "CREATE COLLECTION id=$cid (draft)\n";
}
$storedbooks = [];
foreach ($bookmap as $day => $slugbook) {
    $data = $demo[$slugbook];
    $book = $DB->get_record('local_cr_book', ['collectionid' => $collection->id, 'slug' => $slugbook]);
    if (!$book) {
        $bid = $DB->insert_record('local_cr_book', (object)[
            'collectionid' => $collection->id, 'slug' => $slugbook, 'title' => $data['title'],
            'subtitle' => $data['subtitle'], 'published' => 0,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $book = $DB->get_record('local_cr_book', ['id' => $bid], '*', MUST_EXIST);
        echo "CREATE BOOK id=$bid slug=$slugbook (draft)\n";
    }
    $storedbooks[$day] = $book;
    foreach ($data['pages'] as $idx => $page) {
        if ($DB->record_exists('local_cr_page', ['bookid' => $book->id, 'pageindex' => $idx])) {
            continue;
        }
        $image = isset($page['image']) ? pathinfo($page['image'], PATHINFO_FILENAME) . '.png' : null;
        $DB->insert_record('local_cr_page', (object)[
            'bookid' => $book->id, 'pageindex' => $idx, 'kind' => $page['kind'], 'title' => $page['title'],
            'paragraphsjson' => json_encode($page['paragraphs'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'imagefile' => $image, 'imagealt' => $page['image_alt'] ?? null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }
}
for ($day = 1; $day <= 31; $day++) {
    $unlock = $start->modify('+' . ($day - 1) . ' days')->getTimestamp();
    $slot = $DB->get_record('local_cr_slot', ['collectionid' => $collection->id, 'position' => $day]);
    if (!$slot) {
        $DB->insert_record('local_cr_slot', (object)[
            'collectionid' => $collection->id, 'position' => $day, 'unlockat' => $unlock,
            'bookid' => $storedbooks[$day]->id ?? null,
            'teaser' => 'Une surprise t’attend…', 'timecreated' => $now, 'timemodified' => $now,
        ]);
    } else if (!$slot->bookid && isset($storedbooks[$day])) {
        $DB->set_field('local_cr_slot', 'bookid', $storedbooks[$day]->id, ['id' => $slot->id]);
    }
}
$fs = get_file_storage();
$context = context_system::instance();
foreach ($storedbooks as $day => $book) {
    $data = $demo[$book->slug];
    foreach ($data['pages'] as $idx => $page) {
        if (empty($page['image'])) {
            continue;
        }
        $name = pathinfo($page['image'], PATHINFO_FILENAME) . '.png';
        $filepath = '/p' . $idx . '/';
        if ($fs->file_exists($context->id, 'local_campusreader', 'pageimage', $book->id, $filepath, $name)) {
            continue;
        }
        $fs->create_file_from_pathname([
            'contextid' => $context->id, 'component' => 'local_campusreader',
            'filearea' => 'pageimage', 'itemid' => $book->id,
            'filepath' => $filepath, 'filename' => $name,
            'mimetype' => 'image/png', 'userid' => 0,
            'license' => 'allrightsreserved', 'author' => 'CampusFR',
        ], __DIR__ . '/../seed/assets/' . $name);
    }
}
$transaction->allow_commit();
echo "C1 SEED OK : collection {$collection->id}, 31 cases du 1 au 31 décembre, 2 livres fictifs en brouillon.\n";
echo "Rien n’a été publié, aucune inscription ou droit Commerce créé.\n";
