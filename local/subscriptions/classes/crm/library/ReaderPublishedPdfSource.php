<?php
// CampusFR D2.12.1 — private source adapter for immutable published stories.
// NOT an HTTP endpoint; the learner route MUST check its existing Commerce grants
// and publication/slot authorization BEFORE invoking prepare() or PDF rendering.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderPublishedPdfSource {
    /**
     * Resolve only immutable approved publication data. Never touch editable
     * local_cr_book/local_cr_page records or public pluginfile URLs.
     *
     * Caller MUST FIRST enforce the existing learner access policy for the
     * publication, user, book/chapter and frozen calendar slot.
     *
     * @return array{book:object,pages:array,imageprovider:callable}
     */
    public static function prepare(int $publicationid, int $chapterposition): array {
        if ($publicationid < 1 || $chapterposition < 1 || $chapterposition > 100) {
            throw new \moodle_exception('invalidparameter');
        }
        $pub = ReaderCommercePublicationService::get($publicationid);
        if ((string)$pub->status !== 'published' || (int)$pub->publishedat < 1 ||
                trim((string)($pub->productsku ?? '')) === '' ||
                !in_array((string)$pub->kind, ['calendar', 'anthology'], true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $anthologyid = (int)$pub->anthologyid;
        $revisionid = (int)$pub->revisionid;
        $revision = ReaderAnthologyRevisionService::get($anthologyid, $revisionid);
        if ((string)$revision->state !== 'approved') {
            throw new \moodle_exception('invalidparameter');
        }
        // Digest-validated immutable data; never live book/page tables.
        $snapshot = ReaderAnthologyRevisionService::snapshot($anthologyid, $revisionid);
        if (!isset($snapshot['chapters'][$chapterposition - 1])) {
            throw new \moodle_exception('invalidparameter');
        }
        $chapter = $snapshot['chapters'][$chapterposition - 1];
        if (($chapter['position'] ?? null) !== $chapterposition) {
            throw new \moodle_exception('invalidparameter');
        }
        if ((string)$pub->kind === 'calendar') {
            self::assert_frozen_unlocked((string)$pub->calendarjson, $chapterposition);
        }
        $bookid = (int)$chapter['bookid'];
        if ($bookid < 1 || !isset($chapter['slug'], $chapter['title'], $chapter['pages']) ||
                !is_string($chapter['slug']) ||
                !preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $chapter['slug']) ||
                !is_string($chapter['title']) ||
                !is_array($chapter['pages']) || !array_is_list($chapter['pages']) ||
                !count($chapter['pages']) || count($chapter['pages']) > 60) {
            throw new \moodle_exception('invalidparameter');
        }
        $pages = [];
        foreach ($chapter['pages'] as $idx => $page) {
            if (!is_array($page) || ($page['index'] ?? null) !== $idx ||
                    !isset($page['kind'], $page['title'], $page['paragraphs']) ||
                    !is_array($page['paragraphs']) || !array_is_list($page['paragraphs'])) {
                throw new \moodle_exception('invalidparameter');
            }
            $pages[] = (object)[
                'pageindex' => $idx,
                'kind' => (string)$page['kind'],
                'title' => (string)$page['title'],
                'paragraphsjson' => json_encode($page['paragraphs'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'imagefile' => (string)($page['imagefile'] ?? ''),
            ];
        }
        ReaderStoryLayoutPdf::validate_pages($pages);
        $media = self::index_images($snapshot['media'], $chapterposition);
        $byindex = self::bound_image_map($chapterposition, $pages, $media);
        $provider = static function(int $requestedbookid, object $page) use ($bookid, $revisionid, $byindex): ?\stored_file {
            $index = (int)$page->pageindex;
            if ($requestedbookid !== $bookid || !array_key_exists($index, $byindex)) {
                throw new \moodle_exception('invalidparameter');
            }
            $item = $byindex[$index];
            if ($item === null) { return null; }
            $file = get_file_storage()->get_file(
                (int)\context_system::instance()->id, 'local_campusreader', 'anthrev',
                $revisionid, $item['filepath'], $item['filename']);
            if (!($file instanceof \stored_file) || $file->is_directory() ||
                    !hash_equals($item['sha1'], (string)$file->get_contenthash()) ||
                    (string)$item['size'] !== (string)$file->get_filesize() ||
                    $item['mimetype'] !== $file->get_mimetype()) {
                throw new \moodle_exception('invalidparameter');
            }
            return $file;
        };
        return [
            'book' => (object)['slug' => (string)$chapter['slug'], 'title' => (string)$chapter['title']],
            'pages' => $pages,
            'imageprovider' => $provider,
        ];
    }

    private static function assert_frozen_unlocked(string $raw, int $chapterposition): void {
        if ($raw === '' || strlen($raw) > 65536) {
            throw new \moodle_exception('invalidparameter');
        }
        $cal = json_decode($raw, true);
        if (!is_array($cal) || ($cal['schema'] ?? null) !== 1 ||
                ($cal['timezone'] ?? '') !== 'Europe/Paris' ||
                !isset($cal['slots']) || !is_array($cal['slots']) || !array_is_list($cal['slots'])) {
            throw new \moodle_exception('invalidparameter');
        }
        $found = 0;
        foreach ($cal['slots'] as $slot) {
            if (!is_array($slot) || !isset($slot['chapter'], $slot['unlockat']) ||
                    !is_int($slot['chapter']) || !is_int($slot['unlockat'])) {
                throw new \moodle_exception('invalidparameter');
            }
            if ($slot['chapter'] === $chapterposition) {
                $found++;
                if ($slot['unlockat'] > time() || $slot['unlockat'] < 1) {
                    throw new \moodle_exception('invalidparameter');
                }
            }
        }
        if ($found !== 1) { throw new \moodle_exception('invalidparameter'); }
    }

    /** Index only snapshot-bound illustrations; never accept user input as filepath. */
    private static function index_images(array $source, int $chapter): array {
        $indexed = [];
        foreach ($source as $meta) {
            if (($meta['chapter'] ?? null) !== $chapter ||
                    !in_array(($meta['area'] ?? ''), ['cover', 'pageimage'], true)) {
                continue;
            }
            if (!in_array(($meta['mimetype'] ?? ''), ['image/jpeg', 'image/png', 'image/webp'], true) ||
                    !is_int($meta['size'] ?? null) || $meta['size'] < 1 || $meta['size'] > 8388608 ||
                    !is_string($meta['sha1'] ?? null) ||
                    !preg_match('/\A[a-f0-9]{40}\z/D', $meta['sha1'])) {
                throw new \moodle_exception('invalidparameter');
            }
            $key = (string)$meta['filepath'] . "\0" . (string)$meta['filename'];
            if (isset($indexed[$key])) { throw new \moodle_exception('invalidparameter'); }
            $indexed[$key] = $meta;
        }
        return $indexed;
    }

    /** Detect missing or ambiguous media before starting a PDF response. */
    private static function bound_image_map(int $chapter, array $pages, array $indexed): array {
        $out = [];
        foreach ($pages as $page) {
            $idx = (int)$page->pageindex;
            $filename = (string)$page->imagefile;
            if ($filename !== '') {
                $key = '/story' . $chapter . '/pageimage/p' . $idx . "/\0" . $filename;
                if (!isset($indexed[$key])) { throw new \moodle_exception('invalidparameter'); }
                $out[$idx] = $indexed[$key];
            } else if ($idx === 0) {
                $covers = array_values(array_filter($indexed, static fn(array $meta): bool =>
                    $meta['area'] === 'cover' && $meta['filepath'] === '/story' . $chapter . '/cover/'));
                if (count($covers) > 1) { throw new \moodle_exception('invalidparameter'); }
                $out[$idx] = $covers[0] ?? null;
            } else {
                $out[$idx] = null;
            }
        }
        return $out;
    }
}
