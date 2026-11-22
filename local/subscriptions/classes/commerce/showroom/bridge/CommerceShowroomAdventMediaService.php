<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Public marketing samples for the Advent showroom, stored in Moodle File API. */
final class CommerceShowroomAdventMediaService {
    public const AUDIO_AREA = 'showroom_advent_preview_audio';
    public const COVER_AREA = 'showroom_advent_cover';
    private const COMPONENT = 'local_subscriptions';
    private const MAX_AUDIO_BYTES = 25 * 1024 * 1024;
    private const MAX_COVER_BYTES = 5 * 1024 * 1024;
    private const COVER_TYPES = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'png' => 'image/png'];

    public function __construct(private readonly \context_system $context) {
    }

    public static function has_upload(string $name): bool {
        return isset($_FILES[$name]) && is_array($_FILES[$name])
            && (int)($_FILES[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** Ensure only genuine uploaded media with an allowed MIME type can reach File API. */
    public static function validate_upload(string $name, bool $audio = false): string {
        if (!self::has_upload($name)) {
            throw new \invalid_parameter_exception('Fichier manquant : ' . $name);
        }
        $upload = $_FILES[$name];
        if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \invalid_parameter_exception('Échec du transfert : ' . $name);
        }
        $tmp = (string)($upload['tmp_name'] ?? '');
        $size = (int)($upload['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0
            || $size > ($audio ? self::MAX_AUDIO_BYTES : self::MAX_COVER_BYTES)) {
            throw new \invalid_parameter_exception('Fichier invalide ou trop volumineux : ' . $name);
        }
        $filename = clean_param((string)($upload['name'] ?? ''), PARAM_FILE);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = (string)(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if ($audio) {
            if ($extension !== 'mp3' || !in_array($mime, ['audio/mpeg', 'audio/mp3'], true)) {
                throw new \invalid_parameter_exception('Extrait audio : MP3 requis.');
            }
        } else {
            if (!isset(self::COVER_TYPES[$extension]) || self::COVER_TYPES[$extension] !== $mime) {
                throw new \invalid_parameter_exception('Couverture : WebP, JPG ou PNG requis.');
            }
            $info = @getimagesize($tmp);
            if (!$info || $info[0] < 200 || $info[1] < 200
                || $info[0] > 6000 || $info[1] > 6000
                || $info[0] * $info[1] > 25000000) {
                throw new \invalid_parameter_exception('Couverture : dimensions invalides.');
            }
        }
        return $extension;
    }

    public function get_audio(int $id): ?\stored_file {
        if ($id <= 0) {
            return null;
        }
        $files = get_file_storage()->get_area_files($this->context->id, self::COMPONENT,
            self::AUDIO_AREA, $id, 'id DESC', false);
        foreach ($files as $file) {
            if (preg_match('/\Aaudio-[a-f0-9]{16}\.mp3\z/D', $file->get_filename())
                && $file->get_filepath() === '/') {
                return $file;
            }
        }
        return null;
    }

    /** @return array<int,\stored_file> */
    public function get_covers(int $id): array {
        if ($id <= 0) {
            return [];
        }
        $files = get_file_storage()->get_area_files($this->context->id, self::COMPONENT,
            self::COVER_AREA, $id, 'id DESC', false);
        $covers = [];
        foreach ($files as $file) {
            if ($file->get_filepath() !== '/' || !preg_match(
                '/\Acover-([0-9]{2})-[a-f0-9]{16}\.(?:webp|jpg|png)\z/D',
                $file->get_filename(), $match)) {
                continue;
            }
            $day = (int)$match[1];
            if ($day >= 1 && $day <= 24 && !isset($covers[$day])) {
                $covers[$day] = $file;
            }
        }
        ksort($covers);
        return $covers;
    }

    private function url(\stored_file $file, string $area, int $id): string {
        return \moodle_url::make_pluginfile_url($this->context->id, self::COMPONENT,
            $area, $id, '/', $file->get_filename(), false)->out(false);
    }

    public function audio_url(int $id): string {
        $file = $this->get_audio($id);
        return $file ? $this->url($file, self::AUDIO_AREA, $id) : '';
    }

    /** @return array<int,string> */
    public function cover_urls(int $id): array {
        $urls = [];
        foreach ($this->get_covers($id) as $day => $file) {
            $urls[$day] = $this->url($file, self::COVER_AREA, $id);
        }
        return $urls;
    }

    public function store_audio(int $id): void {
        $ext = self::validate_upload('advent_audio', true);
        $previous = $this->get_audio($id);
        get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id, 'component' => self::COMPONENT,
            'filearea' => self::AUDIO_AREA, 'itemid' => $id, 'filepath' => '/',
            'filename' => 'audio-' . bin2hex(random_bytes(8)) . '.' . $ext,
        ], (string)$_FILES['advent_audio']['tmp_name']);
        if ($previous) {
            $previous->delete();
        }
    }

    public function store_cover(int $id, int $day): void {
        if ($day < 1 || $day > 24) {
            throw new \invalid_parameter_exception('Jour de couverture invalide.');
        }
        $name = 'advent_cover_' . $day;
        $ext = self::validate_upload($name);
        $old = $this->get_covers($id)[$day] ?? null;
        get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id, 'component' => self::COMPONENT,
            'filearea' => self::COVER_AREA, 'itemid' => $id, 'filepath' => '/',
            'filename' => 'cover-' . sprintf('%02d', $day) . '-' . bin2hex(random_bytes(8)) . '.' . $ext,
        ], (string)$_FILES[$name]['tmp_name']);
        if ($old) {
            $old->delete();
        }
    }

    public function delete_audio(int $id): void {
        if ($id > 0) {
            get_file_storage()->delete_area_files($this->context->id, self::COMPONENT,
                self::AUDIO_AREA, $id);
        }
    }

    public function delete_cover(int $id, int $day): void {
        if ($id > 0 && $day >= 1 && $day <= 24) {
            $old = $this->get_covers($id)[$day] ?? null;
            if ($old) {
                $old->delete();
            }
        }
    }
}
