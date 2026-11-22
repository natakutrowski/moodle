<?php
/** D2.17.3.1 — DEV-only unified QA pair preflight. Does not import or publish. */
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderUnifiedBundlePlan {
    /** Pure part of the matching contract: refs may differ, source story identity must not. */
    public static function match_stories(array $calendar, array $anthology,
        array $calhashes, array $anthhashes, array $slots, array $chapters): array {
        $n = count($calendar);
        if ($n < 1 || $n > 31 || count($anthology) !== $n ||
            count($slots) !== $n || count($chapters) !== $n) {
            throw new \DomainException('Le calendrier et le recueil doivent avoir le même nombre N (1 à 31) de livres et cases');
        }
        $byrefCal = []; $byrefAnth = []; $bySlugCal = []; $bySlugAnth = [];
        foreach ([[$calendar, &$byrefCal, &$bySlugCal], [$anthology, &$byrefAnth, &$bySlugAnth]] as $tuple) {
            [$stories, &$refs, &$slugs] = $tuple;
            foreach ($stories as $story) {
                if (!is_array($story) || !is_int($story['ref'] ?? null) ||
                    !is_string($story['slug'] ?? null) || $story['slug'] === '' ||
                    $story['ref'] < 1 || $story['ref'] > $n ||
                    isset($refs[$story['ref']]) || isset($slugs[$story['slug']])) {
                    throw new \DomainException('Références ou slugs d’histoires absents, dupliqués ou invalides');
                }
                $refs[$story['ref']] = $story['slug'];
                $slugs[$story['slug']] = true;
            }
        }
        if (count($bySlugCal) !== $n || count($bySlugAnth) !== $n ||
            array_diff_key($bySlugCal, $bySlugAnth) || array_diff_key($bySlugAnth, $bySlugCal)) {
            throw new \DomainException('Les deux archives ne contiennent pas les mêmes slugs d’histoires');
        }
        foreach (array_keys($bySlugCal) as $slug) {
            if (!isset($calhashes[$slug], $anthhashes[$slug]) ||
                !is_string($calhashes[$slug]) || !is_string($anthhashes[$slug]) ||
                preg_match('/\A[a-f0-9]{64}\z/D', $calhashes[$slug]) !== 1 ||
                !hash_equals($calhashes[$slug], $anthhashes[$slug])) {
                throw new \DomainException('Contenu éditorial ou médias différents entre les archives : ' . $slug);
            }
        }
        $seenSlots = [];
        foreach ($slots as $i => $slot) {
            $ref = $slot['story'] ?? null;
            if (!is_array($slot) || ($slot['position'] ?? null) !== $i + 1 ||
                !is_int($ref) || !isset($byrefCal[$ref]) || isset($seenSlots[$ref])) {
                throw new \DomainException('Cases non remplies, références inconnues ou histoire dupliquée');
            }
            $seenSlots[$ref] = true;
        }
        $seenChapters = []; $chapterSlugs = [];
        foreach ($chapters as $ref) {
            if (!is_int($ref) || !isset($byrefAnth[$ref]) || isset($seenChapters[$ref])) {
                throw new \DomainException('Chapitre orphelin ou dupliqué');
            }
            $seenChapters[$ref] = true;
            $chapterSlugs[] = $byrefAnth[$ref];
        }
        if (count($seenSlots) !== $n || count($seenChapters) !== $n) {
            throw new \DomainException('Certaines histoires sont absentes des cases ou chapitres');
        }
        return ['stories' => $n, 'chapter_slugs' => $chapterSlugs,
            'manifest_sha256' => $calhashes];
    }

    /** Hash a nested story manifest after the existing full ZIP and media checks. */
    private static function nested_manifest_sha(\ZipArchive $zip, array $story): string {
        $path = $story['path'];
        $size = (int)$story['bytes'];
        if ($size < 100 || $size > 268435456) {
            throw new \DomainException('Histoire ZIP hors limites');
        }
        $stream = $zip->getStream($path);
        if (!is_resource($stream)) { throw new \DomainException('Histoire ZIP inaccessible'); }
        $tmp = tempnam(make_request_directory(), 'cfr-uqa-');
        if ($tmp === false) { fclose($stream); throw new \RuntimeException('Temporaire absent'); }
        $out = fopen($tmp, 'wb');
        if (!is_resource($out)) { fclose($stream); @unlink($tmp); throw new \RuntimeException('Temporaire inaccessible'); }
        $n = 0; $hash = hash_init('sha256');
        try {
            while (!feof($stream)) {
                $block = fread($stream, 1048576);
                if ($block === false || ($block === '' && !feof($stream))) {
                    throw new \DomainException('Flux ZIP interrompu');
                }
                if ($block === '') { break; }
                $n += strlen($block);
                if ($n > $size || fwrite($out, $block) !== strlen($block)) {
                    throw new \DomainException('Taille ZIP de l’histoire divergente');
                }
                hash_update($hash, $block);
            }
        } finally { fclose($stream); fclose($out); }
        try {
            if ($n !== $size || !hash_equals((string)$story['sha256'], hash_final($hash))) {
                throw new \DomainException('Empreinte SHA256 du ZIP histoire divergente');
            }
            $nested = new \ZipArchive();
            if ($nested->open($tmp, \ZipArchive::RDONLY) !== true) {
                throw new \DomainException('Histoire imbriquée invalide');
            }
            try {
                $raw = $nested->getFromName('manifest.json');
                if (!is_string($raw) || strlen($raw) > 524288 || strlen($raw) < 10) {
                    throw new \DomainException('Manifeste histoire absent ou invalide');
                }
                return hash('sha256', $raw);
            } finally { $nested->close(); }
        } finally { @unlink($tmp); }
    }

    /** First invokes the production bundle validators, then cross-checks all stories. */
    public static function inspect_pair(string $calendarPath, string $anthologyPath): array {
        $cal = ReaderLibraryBundleService::inspect_zip($calendarPath);
        $anth = ReaderLibraryBundleService::inspect_zip($anthologyPath);
        if ($cal['kind'] !== 'calendar' || $anth['kind'] !== 'anthology') {
            throw new \DomainException('Sélectionne un ZIP calendrier puis un ZIP recueil');
        }
        if (($cal['manifest']['object']['timezone'] ?? '') !== 'Europe/Paris') {
            throw new \DomainException('Calendrier avec fuseau non conforme');
        }
        if (count($anth['manifest']['revisions']) !== 1) {
            throw new \DomainException('Ce pilote QA exige une révision figée ; les exports multi-révisions seront traités séparément');
        }
        $zcal = new \ZipArchive(); $zanth = new \ZipArchive();
        if ($zcal->open($calendarPath, \ZipArchive::RDONLY) !== true) {
            throw new \DomainException('Archive calendrier inaccessible');
        }
        if ($zanth->open($anthologyPath, \ZipArchive::RDONLY) !== true) {
            $zcal->close(); throw new \DomainException('Archive recueil inaccessible');
        }
        try {
            $hashes = [[], []];
            foreach ([[$zcal, $cal['manifest']['stories']], [$zanth, $anth['manifest']['stories']]] as $k => $pair) {
                [$zip, $stories] = $pair;
                foreach ($stories as $story) {
                    $slug = $story['slug'];
                    if (isset($hashes[$k][$slug])) { throw new \DomainException('Histoire en double'); }
                    $hashes[$k][$slug] = self::nested_manifest_sha($zip, $story);
                }
            }
            $matched = self::match_stories($cal['manifest']['stories'], $anth['manifest']['stories'],
                $hashes[0], $hashes[1], $cal['manifest']['slots'], $anth['manifest']['chapters']);
        } finally { $zcal->close(); $zanth->close(); }
        return [
            'stories' => $matched['stories'], 'slots' => count($cal['manifest']['slots']),
            'chapters' => count($anth['manifest']['chapters']),
            'media_frozen' => count($anth['manifest']['revisions'][0]['media']),
            'calendar_name' => (string)$cal['title'], 'anthology_name' => (string)$anth['title'],
            'calendar_sha256' => $cal['zipsha256'], 'anthology_sha256' => $anth['zipsha256'],
            'calendar_manifest' => $cal['manifest'],
            'anthology_manifest' => $anth['manifest'],
            'chapter_slugs' => $matched['chapter_slugs'],
        ];
    }

    /** Existing empty QA course only; no course creation or SQL writes. */
    public static function destination_plan(array $info, string $calendarSlug, string $anthologySlug,
        int $courseid): array {
        global $DB;
        $problems = [];
        foreach ([$calendarSlug, $anthologySlug] as $slug) {
            if (strlen($slug) > 64 || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug) ||
                !preg_match('/(?:^|-)(qa|test)(?:-|$)/', $slug)) {
                $problems[] = 'Les slugs doivent être distincts, valides et contenir qa ou test';
            }
        }
        if ($calendarSlug === $anthologySlug) { $problems[] = 'Les deux slugs doivent être distincts'; }
        if ($DB->record_exists('local_cr_collection', ['slug' => $calendarSlug]) ||
            $DB->record_exists('local_cr_anthology', ['slug' => $anthologySlug])) {
            $problems[] = 'Destination déjà existante : aucun écrasement permis';
        }
        if ($courseid < 2) { $problems[] = 'Choisir un cours QA vide et masqué'; }
        else {
            $course = $DB->get_record('course', ['id' => $courseid], 'id,shortname,fullname,visible');
            if (!$course || (int)$course->visible !== 0 ||
                !preg_match('/qa|test/i', (string)$course->shortname . ' ' . (string)$course->fullname) ||
                $DB->record_exists('local_cr_collection', ['courseid' => $courseid]) ||
                $DB->record_exists_sql('SELECT 1 FROM {user_enrolments} ue JOIN {enrol} e '
                    . 'ON e.id=ue.enrolid WHERE e.courseid=:cid', ['cid' => $courseid])) {
                $problems[] = 'Cours QA non masqué, déjà utilisé ou comportant des inscriptions';
            }
        }
        if (!$problems) {
            $existing = ReaderLibraryBundleService::plan_qa($info['calendar_manifest'], $calendarSlug, 0, $courseid);
            foreach ($existing['problems'] as $problem) { $problems[] = $problem; }
        }
        return ['ready' => !$problems, 'problems' => $problems,
            'courseid' => $courseid, 'calendarslug' => $calendarSlug, 'anthologyslug' => $anthologySlug,
            'stories' => $info['stories']];
    }
}
