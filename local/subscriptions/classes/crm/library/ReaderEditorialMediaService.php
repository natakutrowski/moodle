<?php
// CampusFR D2.2 — Media imported into the EXISTING private local_campusreader File API areas.
declare(strict_types=1);
namespace local_subscriptions\crm\library;

defined('MOODLE_INTERNAL') || die();

final class ReaderEditorialMediaService {
    private const RULES = [
        'cover' => ['limit' => 8388608, 'types' => ['png' => 'image/png', 'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 'webp' => 'image/webp']],
        'pageimage' => ['limit' => 8388608, 'types' => ['png' => 'image/png', 'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 'webp' => 'image/webp']],
        'narration' => ['limit' => 68157440, 'types' => ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg']],
        'video' => ['limit' => 209715200, 'types' => ['mp4' => 'video/mp4', 'webm' => 'video/webm']],
        'pdf' => ['limit' => 36700160, 'types' => ['pdf' => 'application/pdf']],
    ];

    public static function inventory(int $bookid): array {
        $fs = get_file_storage();
        $context = \context_system::instance();
        $result = [];
        foreach (array_keys(self::RULES) as $area) {
            $result[$area] = [];
            foreach ($fs->get_area_files((int)$context->id, 'local_campusreader', $area, $bookid, 'id ASC', false) as $file) {
                if (!$file->is_directory()) {
                    $result[$area][] = ['filepath' => $file->get_filepath(),
                        'filename' => $file->get_filename(), 'size' => $file->get_filesize()];
                }
            }
        }
        return $result;
    }

    /** Never overwrites: replacement + version history are intentionally out of scope. */
    public static function import(int $bookid, string $area, int $pageindex, array $upload): string {
        global $DB, $USER;
        ReaderEditorialPageService::assert_draft($bookid);
        if (!isset(self::RULES[$area]) || ($area !== 'pageimage' && $pageindex !== 0) ||
                ($area === 'pageimage' && ($pageindex < 0 || $pageindex > 99))) {
            throw new \moodle_exception('invalidparameter');
        }
        $rule = self::RULES[$area];
        if (!isset($upload['error'], $upload['tmp_name'], $upload['name'], $upload['size']) ||
                (int)$upload['error'] !== UPLOAD_ERR_OK || !is_string($upload['tmp_name']) ||
                !is_uploaded_file($upload['tmp_name']) || !is_string($upload['name']) ||
                (int)$upload['size'] < 1 || (int)$upload['size'] > $rule['limit'] ||
                filesize($upload['tmp_name']) !== (int)$upload['size']) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Transfert incomplet ou taille non autorisée');
        }
        $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $rule['types'])) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Extension de fichier non autorisée');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $expected = $rule['types'][$ext];
        $aliases = ['audio/mpeg' => ['audio/mpeg', 'audio/mp3'],
            'audio/mp4' => ['audio/mp4', 'audio/x-m4a'],
            'audio/ogg' => ['audio/ogg', 'application/ogg']];
        if (!in_array($mime, $aliases[$expected] ?? [$expected], true)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Type réel du média incompatible avec son extension');
        }
        if ($area === 'cover' || $area === 'pageimage') {
            $size = @getimagesize($upload['tmp_name']);
            if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 35000000) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Illustration invalide ou trop grande');
            }
        }
        $page = null;
        $filepath = '/';
        if ($area === 'pageimage') {
            $page = $DB->get_record('local_cr_page', ['bookid' => $bookid, 'pageindex' => $pageindex], '*', MUST_EXIST);
            if (!in_array($page->kind, ['cover', 'back', 'image', 'mixed'], true)) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Page incompatible avec une illustration');
            }
            $filepath = '/p' . $pageindex . '/';
        }
        $sha = sha1_file($upload['tmp_name']);
        if (!$sha) { throw new \moodle_exception('invalidparameter'); }
        $filename = ($area === 'pageimage' ? 'p' . $pageindex : $area) . '-' . substr($sha, 0, 20) . '.' . $ext;
        $fs = get_file_storage();
        $context = \context_system::instance();
        $old = $fs->get_area_files((int)$context->id, 'local_campusreader', $area, $bookid, 'id ASC', false);
        $existing = [];
        foreach ($old as $file) {
            if (!$file->is_directory() && ($area !== 'pageimage' || $file->get_filepath() === $filepath)) {
                $existing[] = $file;
            }
        }
        if ($existing) {
            if (count($existing) === 1 && $existing[0]->get_filename() === $filename &&
                    $existing[0]->get_contenthash() === $sha &&
                    ($page === null || (string)$page->imagefile === $filename)) {
                return 'already';
            }
            throw new \moodle_exception('invalidparameter', 'error', '', 'Média déjà présent : remplacement interdit sans révision');
        }
        if ($page !== null && !empty($page->imagefile)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Page faisant déjà référence à une image');
        }
        $tx = $DB->start_delegated_transaction();
        $fs->create_file_from_pathname([
            'contextid' => (int)$context->id, 'component' => 'local_campusreader',
            'filearea' => $area, 'itemid' => $bookid, 'filepath' => $filepath,
            'filename' => $filename, 'mimetype' => $expected,
            'userid' => (int)$USER->id, 'license' => 'allrightsreserved', 'author' => 'CampusFR',
        ], $upload['tmp_name']);
        if ($page !== null) {
            $DB->set_field('local_cr_page', 'imagefile', $filename, ['id' => $page->id]);
        }
        $DB->set_field('local_cr_book', 'timemodified', time(), ['id' => $bookid]);
        $tx->allow_commit();
        error_log('[CampusFR D2.2 media] actor=' . (int)$USER->id . ' book=' . $bookid .
            ' area=' . $area . ' page=' . $pageindex . ' sha1=' . $sha . ' event=import');
        return 'created';
    }
}
