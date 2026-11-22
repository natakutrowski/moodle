<?php
// CampusFR D2.2 — Editor of canonical CampusReader pages. Drafts only.
declare(strict_types=1);
namespace local_subscriptions\crm\library;

defined('MOODLE_INTERNAL') || die();

final class ReaderEditorialPageService {
    private const PAGE = 'local_cr_page';
    private const KINDS = ['cover', 'text', 'image', 'mixed', 'back'];

    public static function pages(int $bookid): array {
        global $DB;
        return array_values($DB->get_records(self::PAGE, ['bookid' => $bookid], 'pageindex ASC', '*', 0, 101));
    }

    public static function assert_draft(int $bookid): array {
        $book = ReaderEditorialService::book($bookid);
        $collection = ReaderEditorialService::collection((int)$book->collectionid);
        if ((int)$book->published !== 0 || (int)$collection->published !== 0) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Livre ou collection publié : modification interdite');
        }
        return [$book, $collection];
    }

    /** Split only on blank lines. Preserve sentence breaks within paragraphs as spaces. */
    public static function parse_paragraphs(string $text): array {
        if (!mb_check_encoding($text, 'UTF-8') || strlen($text) > 42000 ||
                preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $text)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Texte invalide ou trop long');
        }
        $chunks = preg_split('/\R[\t ]*\R+/u', trim($text));
        if (!is_array($chunks) || count($chunks) < 1 || count($chunks) > 8) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Entre 1 et 8 paragraphes par page');
        }
        $result = [];
        foreach ($chunks as $chunk) {
            $para = trim(preg_replace('/\s+/u', ' ', $chunk));
            if ($para === '' || strlen($para) > 5000) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Paragraphe vide ou supérieur à 5 000 octets');
            }
            $result[] = $para;
        }
        return $result;
    }

    public static function save(int $bookid, int $index, string $kind, string $title,
        string $text, string $imagealt): int {
        global $DB, $USER;
        self::assert_draft($bookid);
        $pages = self::pages($bookid);
        $count = count($pages);
        // Never edit or append to an already inconsistent legacy page sequence.
        foreach ($pages as $position => $page) {
            if ((int)$page->pageindex !== $position) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Séquence des pages incohérente : inspection requise');
            }
        }
        if ($index < 0 || $index > $count || $index > 99 || $count > 100 ||
                !in_array($kind, self::KINDS, true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $title = trim(strip_tags($title));
        $imagealt = trim(strip_tags($imagealt));
        if ($title === '' || \core_text::strlen($title) > 191 ||
                \core_text::strlen($imagealt) > 255) {
            throw new \moodle_exception('invalidparameter');
        }
        $paragraphs = self::parse_paragraphs($text);
        if ($index === 0 ? $kind !== 'cover' : $kind === 'cover') {
            throw new \moodle_exception('invalidparameter', 'error', '', 'La première page doit être la couverture');
        }
        if ($kind === 'back' && $index !== $count && $index !== $count - 1) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'La fin doit rester en dernière page');
        }
        if ($index === $count && $count && $pages[$count - 1]->kind === 'back') {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Transformer la page de fin en page intérieure avant d’en ajouter une');
        }
        if ($index < $count && !empty($pages[$index]->imagefile) && $kind === 'text') {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Retirer une image nécessite une procédure de révision des médias');
        }
        if ($index < $count && !empty($pages[$index]->imagefile) &&
                !in_array($kind, ['cover', 'back', 'image', 'mixed'], true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $now = time();
        $tx = $DB->start_delegated_transaction();
        $data = (object)[
            'bookid' => $bookid, 'pageindex' => $index, 'kind' => $kind,
            'title' => $title,
            'paragraphsjson' => json_encode($paragraphs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'imagealt' => $imagealt, 'timemodified' => $now,
        ];
        if ($index < $count) {
            $data->id = (int)$pages[$index]->id;
            $DB->update_record(self::PAGE, $data);
        } else {
            $data->imagefile = null;
            $data->timecreated = $now;
            $DB->insert_record(self::PAGE, $data);
        }
        $DB->set_field('local_cr_book', 'timemodified', $now, ['id' => $bookid]);
        $tx->allow_commit();
        error_log('[CampusFR D2.2 page] actor=' . (int)$USER->id . ' book=' . $bookid . ' index=' . $index . ' event=save');
        return $index;
    }
}
