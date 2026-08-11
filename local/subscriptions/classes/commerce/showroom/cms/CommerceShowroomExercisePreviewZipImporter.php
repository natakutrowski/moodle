<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Imports a batch of Exercise Explorer screenshots from a ZIP archive. */
final class CommerceShowroomExercisePreviewZipImporter {
    public function __construct(
        private readonly CommerceShowroomExercisePreviewMediaManager $media
    ) {
    }

    /**
     * @return array{matched:array<string,string>,unmatched:string[],missing:string[],stored:int}
     */
    public function import(
        int $blockid,
        string $zippath,
        string $language = CommerceShowroomExercisePreviewMediaManager::DEFAULT_LANGUAGE,
        bool $dryrun = false
    ): array {
        if (!class_exists('ZipArchive')) {
            throw new \moodle_exception('ZipArchive PHP extension is required.');
        }
        if (!is_readable($zippath) || !is_file($zippath)) {
            throw new \invalid_parameter_exception('Exercise preview ZIP is not readable.');
        }
        if (!in_array($language, CommerceShowroomExercisePreviewMediaManager::LANGUAGES, true)) {
            throw new \invalid_parameter_exception('Unsupported preview language.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zippath) !== true) {
            throw new \invalid_parameter_exception('Unable to open exercise preview ZIP.');
        }

        $matched = [];
        $unmatched = [];
        $stored = 0;
        $tempfiles = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = is_array($stat) ? (string)($stat['name'] ?? '') : '';
                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                $basename = basename($name);
                $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    $unmatched[] = $basename;
                    continue;
                }

                $key = $this->match_filename($basename);
                if ($key === null || isset($matched[$key])) {
                    $unmatched[] = $basename;
                    continue;
                }
                $matched[$key] = $basename;

                if ($dryrun) {
                    continue;
                }

                $contents = $zip->getFromIndex($index);
                if (!is_string($contents) || $contents === '') {
                    throw new \moodle_exception('Unable to read an exercise preview from ZIP.');
                }
                if (strlen($contents) > CommerceShowroomBlockMediaManager::MAX_IMAGE_BYTES) {
                    throw new \moodle_exception('maxbytes', 'error');
                }

                $temp = make_request_directory() . '/' . uniqid('showroom-exercise-', true) . '.' . $extension;
                if (file_put_contents($temp, $contents) === false) {
                    throw new \moodle_exception('Unable to create a temporary exercise preview file.');
                }
                $tempfiles[] = $temp;
                $this->media->store_file($blockid, $key, $language, $temp, $basename);
                $stored++;
            }
        } finally {
            $zip->close();
            foreach ($tempfiles as $temp) {
                if (is_file($temp)) {
                    @unlink($temp);
                }
            }
        }

        $missing = array_values(array_diff(CommerceShowroomExerciseCatalog::keys(), array_keys($matched)));
        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'missing' => $missing,
            'stored' => $stored,
        ];
    }

    public function match_filename(string $filename): ?string {
        $decoded = self::decode_unicode_filename($filename);
        $stem = pathinfo($decoded, PATHINFO_FILENAME);

        // Preferred future naming: stable technical key, optionally suffixed by locale.
        foreach (CommerceShowroomExerciseCatalog::keys() as $key) {
            if (preg_match('/(^|[^a-z0-9])' . preg_quote($key, '/') . '([^a-z0-9]|$)/i', $stem)) {
                return $key;
            }
        }

        // Human-friendly names are accepted too. This deliberately tolerates:
        // - the Windows-style #U0418 encoding used by Nata's ZIP archives;
        // - locale suffixes;
        // - optional numeric prefixes ("01 - ...");
        // - dashes/underscores/non-breaking spaces and duplicate whitespace.
        $clean = self::normalise_source_stem($stem);
        return CommerceShowroomExerciseCatalog::key_from_source_title($clean);
    }

    private static function normalise_source_stem(string $stem): string {
        $stem = preg_replace('/[-_](default|fr|en|ru)$/iu', '', trim($stem)) ?? trim($stem);
        $stem = preg_replace('/^\s*\d{1,2}\s*[-_.:)]+\s*/u', '', $stem) ?? $stem;
        $stem = str_replace(["\u{00A0}", '_'], [' ', ' '], $stem);
        $stem = preg_replace('/\s*[–—-]\s*/u', ' ', $stem) ?? $stem;
        $stem = preg_replace('/\s+/u', ' ', $stem) ?? $stem;

        return trim($stem, " \t\n\r\0\x0B.-_");
    }

    /** Decodes Windows-style #U0418 sequences found in the supplied ZIP names. */
    public static function decode_unicode_filename(string $filename): string {
        return preg_replace_callback('/#U([0-9A-Fa-f]{4,6})/', static function(array $matches): string {
            return mb_chr(hexdec($matches[1]), 'UTF-8');
        }, $filename) ?? $filename;
    }
}
