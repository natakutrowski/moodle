<?php
/** D2.17.3.0 — Read-only readiness of an already imported QA calendar + anthology. */
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderUnifiedQAReadiness {
    /** Independent of chapter order. No book may appear twice in either side. */
    public static function identical_book_sets(array $calendar, array $anthology, int $expected = 24): bool {
        if ($expected < 1 || $expected > 31 || count($calendar) !== $expected ||
            count($anthology) !== $expected) { return false; }
        foreach ([$calendar, $anthology] as $ids) {
            foreach ($ids as $id) { if (!is_int($id) || $id <= 0) { return false; } }
            if (count(array_unique($ids, SORT_NUMERIC)) !== $expected) { return false; }
        }
        sort($calendar, SORT_NUMERIC);
        sort($anthology, SORT_NUMERIC);
        return $calendar === $anthology;
    }

    /** No writes, imports or publication are performed. */
    public static function inspect(int $collectionid, int $anthologyid): array {
        global $DB;
        if ($collectionid <= 0 || $anthologyid <= 0) {
            throw new \InvalidArgumentException('Sélectionne une collection et un recueil QA');
        }
        $coll = $DB->get_record('local_cr_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $anth = $DB->get_record('local_cr_anthology', ['id' => $anthologyid], '*', MUST_EXIST);
        if (!preg_match('/(?:^|-)(?:qa|test)(?:-|$)/i', (string)$coll->slug) ||
            !preg_match('/(?:^|-)(?:qa|test)(?:-|$)/i', (string)$anth->slug) ||
            (int)$coll->published !== 0 || (string)$anth->status !== 'draft') {
            throw new \DomainException('Destinations non QA, publiées ou recueil non brouillon : STOP');
        }
        $course = $DB->get_record('course', ['id' => (int)$coll->courseid],
            'id,shortname,fullname,visible', MUST_EXIST);
        if ((int)$course->visible !== 0 || !preg_match('/qa|test/i',
            (string)$course->shortname . ' ' . (string)$course->fullname)) {
            throw new \DomainException('Cours de destination non masqué ou non QA : STOP');
        }
        if ($DB->count_records('local_cr_collection', ['courseid' => (int)$course->id]) !== 1) {
            throw new \DomainException('Le cours QA doit héberger une seule collection');
        }
        if ($DB->record_exists_sql('SELECT 1 FROM {user_enrolments} ue '
            . 'JOIN {enrol} e ON e.id=ue.enrolid WHERE e.courseid=:course',
            ['course' => (int)$course->id])) {
            throw new \DomainException('Cours QA avec inscriptions : STOP');
        }
        foreach (['collectionid' => $collectionid, 'anthologyid' => $anthologyid] as $field => $id) {
            if ($DB->record_exists('local_cr_publication', [$field => $id])) {
                throw new \DomainException('Une publication existe sur la destination QA : STOP');
            }
        }
        if ((string)$coll->timezone !== 'Europe/Paris') {
            throw new \DomainException('Fuseau du calendrier différent de Europe/Paris : STOP');
        }
        $slots = ReaderEditorialCalendarService::slots($collectionid);
        $count = ReaderEditorialCalendarService::count($coll);
        $chapters = ReaderEditorialAnthologyService::chapters($anthologyid);
        if ($count !== 24 || count($slots) !== 24 || count($chapters) !== 24) {
            throw new \DomainException('Ce lot QA exige 24 cases remplies et 24 chapitres');
        }
        $calendarids = []; $prev = 0;
        for ($i = 1; $i <= 24; $i++) {
            $slot = $slots[$i] ?? null;
            if (!$slot || (int)$slot->position !== $i || (int)$slot->bookid < 1 ||
                (int)$slot->unlockat <= $prev ||
                (int)$slot->unlockat >= (int)$coll->libraryafter) {
                throw new \DomainException('Case ou date incohérente : position ' . $i);
            }
            $calendarids[] = (int)$slot->bookid;
            $prev = (int)$slot->unlockat;
        }
        $chapterids = []; $index = 0;
        foreach ($chapters as $chapter) {
            $index++;
            if ((int)$chapter->position !== $index ||
                (int)$chapter->collectionid !== $collectionid ||
                (int)$chapter->published !== 0) {
                throw new \DomainException('Chapitre divergent : position ' . $index);
            }
            $chapterids[] = (int)$chapter->bookid;
        }
        if (!self::identical_book_sets($calendarids, $chapterids)) {
            throw new \DomainException('Les cases et chapitres ne référencent pas les mêmes 24 livres');
        }
        $books = $DB->get_records('local_cr_book', ['collectionid' => $collectionid], 'id ASC', 'id,published');
        if (count($books) !== 24 || array_diff(array_keys($books), $calendarids)) {
            throw new \DomainException('Livres supplémentaires, absents ou dupliqués');
        }
        $pagecount = 0; $filecount = 0;
        $fs = get_file_storage();
        $ctx = (int)\context_system::instance()->id;
        foreach ($books as $book) {
            if ((int)$book->published !== 0) {
                throw new \DomainException('Livre déjà publié dans cette copie QA');
            }
            $pages = $DB->count_records('local_cr_page', ['bookid' => (int)$book->id]);
            if ($pages < 1 || $pages > 200) {
                throw new \DomainException('Pages manquantes ou trop nombreuses : livre #' . $book->id);
            }
            $pagecount += $pages;
            foreach (['cover','pageimage','narration','video','pdf'] as $area) {
                foreach ($fs->get_area_files($ctx, 'local_campusreader', $area,
                    (int)$book->id, 'id ASC', false) as $file) {
                    if ($file->is_directory() || $file->is_external_file() ||
                        (int)$file->get_status() !== 0 ||
                        (int)$file->get_filesize() < 1 ||
                        !preg_match('/\A[a-f0-9]{40}\z/D', (string)$file->get_contenthash())) {
                        throw new \DomainException('Fichier média local invalide : livre #' . $book->id);
                    }
                    $filecount++;
                }
            }
        }
        // Strict fixture contract: 24 stories x 6 pages and x 4 story media.
        if ($pagecount !== 144 || $filecount !== 96) {
            throw new \DomainException('Pages ou médias des 24 histoires QA incomplets');
        }
        $revs = $DB->get_records('local_cr_anthrev', ['anthologyid' => $anthologyid], 'id ASC');
        if (count($revs) !== 1) {
            throw new \DomainException('Une unique révision QA en revue est attendue');
        }
        $rev = reset($revs);
        if ((string)$rev->state !== 'review' || (int)$rev->approvedby !== 0 ||
            (int)$rev->approvedat !== 0) {
            throw new \DomainException('Révision non en revue ou déjà approuvée');
        }
        $snapshot = ReaderAnthologyRevisionService::snapshot($anthologyid, (int)$rev->id);
        if (count($snapshot['chapters'] ?? []) !== 24 || count($snapshot['media'] ?? []) !== 96) {
            throw new \DomainException('Snapshot figé incomplet');
        }
        foreach ($snapshot['chapters'] as $i => $c) {
            if (!isset($chapters[$i]) || (int)$c['bookid'] !== (int)$chapters[$i]->bookid) {
                throw new \DomainException('Révision figée rattachée à un mauvais chapitre');
            }
        }
        ReaderAnthologyRevisionService::assert_media((int)$rev->id, $snapshot);
        return [
            'collectionid' => $collectionid, 'anthologyid' => $anthologyid,
            'courseid' => (int)$course->id, 'revisionid' => (int)$rev->id,
            'books' => count($books), 'slots' => count($slots),
            'chapters' => count($chapters), 'pages' => $pagecount,
            'storymedia' => $filecount, 'frozenmedia' => count($snapshot['media']),
            'revisionstate' => (string)$rev->state,
        ];
    }
}
