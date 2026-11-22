<?php
// CampusFR D2.8 — private user vocabulary; no course dependency, no purchases imported.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class personal_vocabulary {
    public static function save(int $userid, string $word, string $translation, string $slug,
            int $chapter, int $page): array {
        global $DB;
        $norm = vocabulary_word::normalized($word);
        $translation = trim($translation);
        if (mb_strlen($translation, 'UTF-8') > 255 || !mb_check_encoding($translation, 'UTF-8') ||
                preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $translation)) {
            throw new \invalid_parameter_exception('Translation too long');
        }
        if ($userid <= 1) { throw new \invalid_parameter_exception('Invalid user'); }
        $hash = hash('sha256', 'fr|' . $norm);
        $transaction = $DB->start_delegated_transaction();
        try {
            $item = $DB->get_record('local_cr_vocab',
                ['userid' => $userid, 'lang' => 'fr', 'wordhash' => $hash], '*', IGNORE_MISSING);
            if (!$item) {
                if ($DB->count_records('local_cr_vocab', ['userid' => $userid]) >= 3000) {
                    throw new \moodle_exception('invalidparameter');
                }
                $item = (object)[
                    'userid' => $userid, 'lang' => 'fr', 'wordhash' => $hash,
                    'word' => $word, 'translation' => $translation,
                    'timecreated' => time(), 'timemodified' => time(),
                ];
                $item->id = $DB->insert_record('local_cr_vocab', $item);
            } else if ($translation !== '' && $translation !== (string)$item->translation) {
                $item->translation = $translation;
                $item->timemodified = time();
                $DB->update_record('local_cr_vocab', $item);
            }
            if (!$DB->record_exists('local_cr_vocab_source', [
                    'vocabid' => (int)$item->id, 'pubslug' => $slug,
                    'chapter' => $chapter, 'pageindex' => $page])) {
                $DB->insert_record('local_cr_vocab_source', (object)[
                    'vocabid' => (int)$item->id, 'pubslug' => $slug,
                    'chapter' => $chapter, 'pageindex' => $page,
                    'timecreated' => time(),
                ]);
            }
            $transaction->allow_commit();
            return ['saved' => true, 'word' => (string)$item->word, 'translation' => (string)$item->translation];
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    public static function remove(int $userid, int $id): bool {
        global $DB;
        if ($userid <= 1 || $id <= 0) { return false; }
        $item = $DB->get_record('local_cr_vocab', ['id' => $id, 'userid' => $userid], 'id', IGNORE_MISSING);
        if (!$item) { return false; }
        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('local_cr_vocab_source', ['vocabid' => $id]);
            $DB->delete_records('local_cr_vocab', ['id' => $id, 'userid' => $userid]);
            $transaction->allow_commit();
        } catch (\Throwable $e) { $transaction->rollback($e); throw $e; }
        return true;
    }

    public static function update_translation(int $userid, int $id, string $translation): bool {
        global $DB;
        if ($userid <= 1 || $id <= 0 || !mb_check_encoding($translation, 'UTF-8') ||
                mb_strlen($translation, 'UTF-8') > 255 ||
                preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $translation)) {
            throw new \invalid_parameter_exception('Invalid translation');
        }
        $item = $DB->get_record('local_cr_vocab', ['id' => $id, 'userid' => $userid], '*', IGNORE_MISSING);
        if (!$item) { return false; }
        $item->translation = trim($translation);
        $item->timemodified = time();
        $DB->update_record('local_cr_vocab', $item);
        return true;
    }


    /** Bulk-load only source references belonging to the current vocabulary owner. */
    public static function sources_for(int $userid, array $items): array {
        global $DB;
        if ($userid <= 1 || !$items) { return []; }
        $records = $DB->get_records_sql(
            'SELECT s.* FROM {local_cr_vocab_source} s JOIN {local_cr_vocab} v ON v.id = s.vocabid '
            . 'WHERE v.userid = :userid ORDER BY s.id ASC', ['userid' => $userid]);
        $cache = []; $result = []; $checked = [];
        foreach ($records as $source) {
            $id = (int)$source->vocabid;
            if (!isset($items[$id]) || (int)$items[$id]->userid !== $userid) { continue; }
            $slug = (string)$source->pubslug;
            $chapter = (int)$source->chapter;
            $page = (int)$source->pageindex;
            if (!array_key_exists($slug, $cache)) {
                $cache[$slug] = null;
                try {
                    $pub = publication_access::publication($slug);
                    if ($pub && publication_access::can_view($pub, $userid)) {
                        $cache[$slug] = [$pub, null];
                    }
                } catch (\Throwable $e) { /* Fail closed for unavailable source. */ }
            }
            if ($cache[$slug] === null) { continue; }
            [$pub, $snapshot] = $cache[$slug];
            $key = $slug . ':' . $chapter;
            if (!array_key_exists($key, $checked)) {
                $checked[$key] = publication_access::chapter_allowed($pub, $chapter);
            }
            if (!$checked[$key] || $chapter < 1 || $page < 0) { continue; }
            try {
                if ($snapshot === null) {
                    $snapshot = publication_access::frozen($pub);
                    $cache[$slug][1] = $snapshot;
                }
                $story = $snapshot['chapters'][$chapter - 1] ?? null;
                $paragraphs = $story['pages'][$page]['paragraphs'] ?? null;
                if (!is_array($paragraphs)) { continue; }
                // A replaced revision must not produce a misleading citation or IA action.
                vocabulary_word::context($paragraphs, (string)$items[$id]->word);
                $result[$id][$key] ??= [
                    'slug' => $slug, 'chapter' => $chapter, 'page' => $page,
                    'collection' => (string)$snapshot['anthology']['title'],
                    'title' => (string)$story['title'], 'key' => $key,
                ];
            } catch (\Throwable $e) { /* Keep the private word, not invalid source metadata. */ }
        }
        return $result;
    }

    /** CSV is quoted by fputcsv; neutralize spreadsheet formulas in every user field. */
    public static function csv_cell(string $value): string {
        if (preg_match('/^[\s\p{Z}\p{C}]*[=+\-@＝＋－＠]/u', $value) ||
                preg_match('/^[\t\r\n]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    public static function write_csv($stream, array $items, int $userid): void {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Français', 'Ma traduction'], ';', '"', '', "\r\n");
        foreach ($items as $item) {
            if ($userid <= 1 || (int)$item->userid !== $userid) { continue; }
            fputcsv($stream, [self::csv_cell((string)$item->word),
                self::csv_cell((string)$item->translation)], ';', '"', '', "\r\n");
        }
    }

    public static function list_for(int $userid): array {
        global $DB;
        return $DB->get_records('local_cr_vocab', ['userid' => $userid], 'timemodified DESC, id DESC', '*', 0, 3000);
    }
}
