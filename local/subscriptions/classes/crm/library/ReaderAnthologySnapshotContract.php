<?php
// CampusFR D2.4.2a: pure contract, private immutable snapshots.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderAnthologySnapshotContract {
    public static function encode(array $payload): string {
        self::validate($payload);
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > 20 * 1024 * 1024) {
            throw new \InvalidArgumentException('Snapshot editorial trop volumineux');
        }
        return $json;
    }
    public static function decode(string $json, string $sha): array {
        if (strlen($json) > 20 * 1024 * 1024 ||
                !preg_match('/\A[a-f0-9]{64}\z/D', $sha) ||
                !hash_equals($sha, hash('sha256', $json))) {
            throw new \UnexpectedValueException('Empreinte du snapshot invalide');
        }
        $value = json_decode($json, true, 50, JSON_THROW_ON_ERROR);
        if (!is_array($value)) { throw new \UnexpectedValueException('Snapshot invalide'); }
        self::validate($value);
        return $value;
    }
    private static function validate(array $value): void {
        if (($value['schema'] ?? null) !== 1 ||
                !isset($value['anthology'], $value['chapters'], $value['media']) ||
                !is_array($value['anthology']) || !is_array($value['chapters']) ||
                !is_array($value['media']) || !array_is_list($value['chapters']) ||
                !array_is_list($value['media']) || count($value['chapters']) < 1 ||
                count($value['chapters']) > 100 || count($value['media']) > 12000) {
            throw new \InvalidArgumentException('Structure du snapshot invalide');
        }
        $a = $value['anthology'];
        foreach (['slug', 'title', 'subtitle'] as $field) {
            if (!isset($a[$field]) || !is_string($a[$field])) {
                throw new \InvalidArgumentException('Métadonnées absentes');
            }
        }
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $a['slug'])) {
            throw new \InvalidArgumentException('Slug invalide');
        }
        foreach ($value['chapters'] as $i => $chapter) {
            if (!is_array($chapter) || ($chapter['position'] ?? null) !== $i + 1 ||
                    !isset($chapter['bookid'], $chapter['title'], $chapter['pages']) ||
                    !is_int($chapter['bookid']) || $chapter['bookid'] < 1 ||
                    !is_string($chapter['title']) || !is_array($chapter['pages']) ||
                    !array_is_list($chapter['pages']) || count($chapter['pages']) > 100 ||
                    (array_key_exists('release_ready', $chapter) && !is_bool($chapter['release_ready']))) {
                throw new \InvalidArgumentException('Chapitre invalide');
            }
            if (($chapter['release_ready'] ?? false) === true &&
                    !\local_campusreader\local\launch_release_policy::has_content($chapter['pages'])) {
                throw new \InvalidArgumentException('Chapitre marqué prêt sans contenu suffisant');
            }
            foreach ($chapter['pages'] as $j => $page) {
                if (!is_array($page) || ($page['index'] ?? null) !== $j ||
                        !isset($page['kind'], $page['title'], $page['paragraphs']) ||
                        !is_string($page['kind']) || !is_string($page['title']) ||
                        !is_array($page['paragraphs']) || !array_is_list($page['paragraphs']) ||
                        count($page['paragraphs']) > 8) {
                    throw new \InvalidArgumentException('Page invalide');
                }
                foreach ($page['paragraphs'] as $paragraph) {
                    if (!is_string($paragraph) || strlen($paragraph) > 5000) {
                        throw new \InvalidArgumentException('Paragraphe invalide');
                    }
                }
            }
            // Optional D2.9b timing is part of the frozen, hashed story snapshot.
            // Imported archives carry it unchanged; never load mutable live book data to play it.
            if (array_key_exists('alignment', $chapter)) {
                $a = $chapter['alignment'];
                if (!is_array($a) || ($a['schema'] ?? null) !== 1 ||
                        !is_string($a['textdigest'] ?? null) ||
                        !preg_match('/\A[a-f0-9]{64}\z/D', $a['textdigest']) ||
                        !is_string($a['audiohash'] ?? null) ||
                        !preg_match('/\A[a-f0-9]{40}\z/D', $a['audiohash']) ||
                        !is_array($a['cues'] ?? null) || !array_is_list($a['cues']) ||
                        count($a['cues']) < 1 || count($a['cues']) > 1200) {
                    throw new \InvalidArgumentException('Alignement figé invalide');
                }
                $segments = [];
                foreach ($chapter['pages'] as $page) {
                    $n = 0;
                    foreach ($page['paragraphs'] as $paragraph) {
                        if (!is_string($paragraph) || trim($paragraph) === '') { continue; }
                        $parts = preg_split('/(?<=[.!?…])\s+(?=[«“"\p{Lu}\p{N}])/u', trim($paragraph));
                        if (!is_array($parts)) { throw new \InvalidArgumentException('Phrase invalide'); }
                        foreach ($parts as $part) {
                            $part = trim($part);
                            if ($part !== '') {
                                $segments[] = ['id' => 'p' . $page['index'] . 's' . $n++,
                                    'page' => $page['index'], 'text' => $part];
                            }
                        }
                    }
                }
                if (count($segments) !== count($a['cues']) ||
                    !hash_equals($a['textdigest'], hash('sha256', json_encode(array_map(
                        static fn($x) => [$x['id'], $x['text']], $segments),
                        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)))) {
                    throw new \InvalidArgumentException('Alignement désynchronisé du texte');
                }
                $prev = 0; $timed = 0;
                foreach ($a['cues'] as $ci => $cue) {
                    $seg = $segments[$ci];
                    if (!is_array($cue) || ($cue['id'] ?? null) !== $seg['id'] ||
                            ($cue['text'] ?? null) !== $seg['text'] ||
                            ($cue['page'] ?? null) !== $seg['page'] ||
                            !is_bool($cue['skip'] ?? null)) {
                        throw new \InvalidArgumentException('Repère de phrase invalide');
                    }
                    if ($cue['skip']) {
                        if (($cue['startms'] ?? null) !== null || ($cue['endms'] ?? null) !== null) {
                            throw new \InvalidArgumentException('Repère non lu incohérent');
                        }
                    } else {
                        $begin = $cue['startms'] ?? null; $end = $cue['endms'] ?? null;
                        if (!is_int($begin) || !is_int($end) || $begin < $prev || $end <= $begin ||
                                $begin < 0 || $end > 7200000) {
                            throw new \InvalidArgumentException('Repère sonore incohérent');
                        }
                        $prev = $end; $timed++;
                    }
                }
                if (!$timed) { throw new \InvalidArgumentException('Alignement sans lecture'); }
                $narrations = array_values(array_filter($value['media'], static fn($m) =>
                    is_array($m) && ($m['chapter'] ?? null) === ($i + 1) &&
                    ($m['area'] ?? null) === 'narration'));
                if (count($narrations) !== 1 ||
                        !hash_equals($a['audiohash'], (string)($narrations[0]['sha1'] ?? ''))) {
                    throw new \InvalidArgumentException('Alignement non lié au média narratif');
                }
            }
        }
        $unique = [];
        foreach ($value['media'] as $i => $f) {
            if (!is_array($f) || !isset($f['chapter'], $f['area'], $f['filename'],
                    $f['filepath'], $f['sha1'], $f['mimetype'], $f['size']) ||
                    !is_int($f['chapter']) || $f['chapter'] < 1 || $f['chapter'] > count($value['chapters']) ||
                    !in_array($f['area'], ['cover', 'pageimage', 'narration', 'video', 'pdf'], true) ||
                    !is_string($f['filename']) || !preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,119}\z/D', $f['filename']) ||
                    str_contains($f['filename'], '..') ||
                    !is_string($f['filepath']) ||
                    !preg_match('#\A/story[1-9][0-9]{0,2}/(cover|pageimage|narration|video|pdf)/(?:p[0-9]{1,2}/)?\z#', $f['filepath']) ||
                    !is_string($f['sha1']) || !preg_match('/\A[a-f0-9]{40}\z/D', $f['sha1']) ||
                    !is_string($f['mimetype']) || !is_int($f['size']) || $f['size'] < 1) {
                throw new \InvalidArgumentException('Média invalide');
            }
            if (!str_starts_with($f['filepath'], '/story' . $f['chapter'] . '/' . $f['area'] . '/')) {
                throw new \InvalidArgumentException('Chemin média non conforme');
            }
            $key = $f['filepath'] . $f['filename'];
            if (isset($unique[$key])) { throw new \InvalidArgumentException('Média en doublon'); }
            $unique[$key] = true;
        }
    }
}
