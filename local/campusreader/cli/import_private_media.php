<?php
// CampusFR C2.2b.1 — DEV-only single-file media import. GPL-3.0-or-later.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('CLI_SCRIPT', true);
$root = realpath(dirname(__DIR__, 3));
if ($root !== '/var/www/moodle-dev') {
    fwrite(STDERR, "STOP : uniquement Moodle DEV.\n");
    exit(3);
}
require_once($root . '/config.php');

global $DB;
$usage = "Usage: php cli/import_private_media.php --check|--apply --bookid=ID --area=cover|pageimage|narration|video|pdf --file=/absolute/chemin/fichier [--page=N]\n";
$input = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--check' || $arg === '--apply') {
        $name = 'mode'; $value = substr($arg, 2);
    } else if (preg_match('/\A--(bookid|area|file|page)=(.+)\z/D', $arg, $matches)) {
        $name = $matches[1]; $value = $matches[2];
    } else {
        fwrite(STDERR, $usage); exit(2);
    }
    if (isset($input[$name])) {
        fwrite(STDERR, "STOP : argument répété : $name\n"); exit(2);
    }
    $input[$name] = $value;
}
if (!in_array($input['mode'] ?? '', ['check', 'apply'], true)
    || !preg_match('/\A[1-9][0-9]{0,9}\z/D', (string)($input['bookid'] ?? ''))
    || !in_array($input['area'] ?? '', ['cover', 'pageimage', 'narration', 'video', 'pdf'], true)
    || empty($input['file']) || !str_starts_with($input['file'], '/')) {
    fwrite(STDERR, $usage); exit(2);
}
$area = $input['area'];
$bookid = (int)$input['bookid'];
$pageindex = null;
if ($area === 'pageimage') {
    if (!preg_match('/\A(0|[1-9][0-9]{0,2})\z/D', (string)($input['page'] ?? ''), $match)) {
        fwrite(STDERR, "STOP : page index requis (zéro-based).\n"); exit(2);
    }
    $pageindex = (int)$match[1];
} else if (isset($input['page'])) {
    fwrite(STDERR, "STOP : --page réservé à pageimage.\n"); exit(2);
}
$source = realpath($input['file']);
if (!$source || !is_file($source) || is_link($input['file']) || !is_readable($source)) {
    throw new RuntimeException('Source absente, lien symbolique ou illisible sous www-data.');
}
$name = basename($source);
if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,119}\z/D', $name) || str_contains($name, '..')) {
    throw new RuntimeException('Nom du fichier non conforme. Renommer sans espaces ni caractères spéciaux.');
}
$limits = ['cover' => 8 * 1024 * 1024, 'pageimage' => 8 * 1024 * 1024,
    'narration' => 65 * 1024 * 1024, 'video' => 200 * 1024 * 1024, 'pdf' => 35 * 1024 * 1024];
$size = filesize($source);
if ($size === false || $size < 1 || $size > $limits[$area]) {
    throw new RuntimeException('Taille incompatible avec la zone autorisée.');
}
$types = [
    'cover' => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'],
    'pageimage' => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'],
    'narration' => ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'ogg' => 'audio/ogg'],
    'video' => ['mp4' => 'video/mp4', 'webm' => 'video/webm'],
    'pdf' => ['pdf' => 'application/pdf'],
];
$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (!isset($types[$area][$extension])) {
    throw new RuntimeException('Extension non autorisée pour ' . $area);
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detected = $finfo->file($source);
$aliases = ['audio/ogg' => ['audio/ogg', 'application/ogg'],
    'audio/mp4' => ['audio/mp4', 'audio/x-m4a'],
    'audio/mpeg' => ['audio/mpeg', 'audio/mp3']];
$accepted = $aliases[$types[$area][$extension]] ?? [$types[$area][$extension]];
if (!in_array($detected, $accepted, true)) {
    throw new RuntimeException('MIME détecté inattendu : ' . $detected . ' / extension ' . $extension);
}
if ($area === 'cover' || $area === 'pageimage') {
    $imageinfo = @getimagesize($source);
    if (!$imageinfo || $imageinfo[0] < 1 || $imageinfo[1] < 1 ||
        $imageinfo[0] * $imageinfo[1] > 35000000) {
        throw new RuntimeException('Image invalide ou dimensions excessives.');
    }
}
[$book, $collection, $slot] = \local_campusreader\local\collection_repository::book_bundle($bookid);
if ((int)$collection->courseid !== 19 || $collection->slug !== 'avent-2026'
    || (int)$collection->published !== 0 || (int)$book->published !== 0) {
    throw new RuntimeException('Import limité aux livres brouillons de la collection DEV #19.');
}
$filepath = '/';
$page = null;
if ($pageindex !== null) {
    $page = $DB->get_record('local_cr_page', ['bookid' => $bookid, 'pageindex' => $pageindex], '*', MUST_EXIST);
    if (!in_array($page->kind, ['cover', 'back', 'image', 'mixed'], true)) {
        throw new RuntimeException('Ce type de page ne peut pas contenir une image.');
    }
    if (!empty($page->imagefile) && $page->imagefile !== $name) {
        throw new RuntimeException('Une autre image est déjà référencée par cette page. Import refusé.');
    }
    $filepath = '/p' . $pageindex . '/';
}
$fs = get_file_storage();
$context = context_system::instance();
$files = $fs->get_area_files($context->id, 'local_campusreader', $area, $bookid, 'id ASC', false);
$existing = [];
foreach ($files as $entry) {
    if (!$entry->is_directory() && $entry->get_filesize() > 0) {
        if ($area !== 'pageimage' || $entry->get_filepath() === $filepath) {
            $existing[] = $entry;
        }
    }
}
if (count($existing) > 1) {
    throw new RuntimeException('Plusieurs médias présents : vérifier manuellement avant toute opération.');
}
$already = false;
if ($existing) {
    $other = reset($existing);
    if ($other->get_filename() !== $name || $other->get_contenthash() !== sha1_file($source)) {
        throw new RuntimeException('Un média différent existe déjà : remplacement volontairement interdit en C2.2b.1.');
    }
    $already = true;
}
if ($area === 'pageimage' && $already && empty($page->imagefile)) {
    throw new RuntimeException('Métadonnée image manquante, importer arrêté pour éviter un état ambigu.');
}
$state = $already ? 'DEJA PRESENT' : 'NOUVEAU';
echo 'MEDIA ' . $state . ': book=' . $bookid . ' area=' . $area .
    ' filepath=' . $filepath . ' filename=' . $name . ' MIME=' . $detected . ' octets=' . $size . "\n";
if ($input['mode'] === 'check' || $already) {
    echo 'OK : ' . ($already ? 'idempotent.' : 'vérification sans écriture.') . "\n";
    exit(0);
}
// No overwrite: keep CLI imports intentionally additive until the full admin interface is reviewed.
$tx = $DB->start_delegated_transaction();
$fs->create_file_from_pathname([
    'contextid' => $context->id,
    'component' => 'local_campusreader',
    'filearea' => $area,
    'itemid' => $bookid,
    'filepath' => $filepath,
    'filename' => $name,
    'mimetype' => $types[$area][$extension],
    'userid' => 0,
    'license' => 'allrightsreserved',
    'author' => 'CampusFR',
], $source);
if ($page !== null && empty($page->imagefile)) {
    $DB->set_field('local_cr_page', 'imagefile', $name, ['id' => $page->id]);
}
$tx->allow_commit();
echo "OK : média créé dans la File API Moodle privée. Aucun fichier web/public créé.\n";
