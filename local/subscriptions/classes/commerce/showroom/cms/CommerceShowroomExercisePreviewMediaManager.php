<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * File API storage/resolution for Exercise Explorer screenshots.
 *
 * Media are stored in the existing public showroom_block_media filearea so block
 * duplication, pluginfile delivery and cache policy remain shared with other
 * Showroom assets. Each slot uses a flat field path to remain compatible with the
 * historical CommerceShowroomBlockMediaManager duplication logic.
 */
final class CommerceShowroomExercisePreviewMediaManager {
    public const DEFAULT_LANGUAGE = 'default';
    public const LANGUAGES = ['default', 'fr', 'en', 'ru'];

    public function __construct(
        private readonly \context_system $context,
        private readonly ?CommerceShowroomBlockMediaManager $blockmediamanager = null
    ) {
    }

    public function store_uploaded_media(
        int $blockid,
        string $exercisekey,
        string $language,
        string $uploadfield = 'media'
    ): \stored_file {
        $this->validate_slot($blockid, $exercisekey, $language);

        if (
            !isset($_FILES[$uploadfield])
            || (int)($_FILES[$uploadfield]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ) {
            throw new \invalid_parameter_exception('No exercise preview image was uploaded.');
        }

        $upload = $_FILES[$uploadfield];
        if ((int)($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }

        $tmpname = (string)($upload['tmp_name'] ?? '');
        if ($tmpname === '' || !is_uploaded_file($tmpname)) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }

        return $this->store_file(
            $blockid,
            $exercisekey,
            $language,
            $tmpname,
            (string)($upload['name'] ?? '')
        );
    }

    public function store_file(
        int $blockid,
        string $exercisekey,
        string $language,
        string $pathname,
        ?string $originalfilename = null
    ): \stored_file {
        $this->validate_slot($blockid, $exercisekey, $language);
        if (!is_readable($pathname) || !is_file($pathname)) {
            throw new \invalid_parameter_exception('Exercise preview image is not readable.');
        }

        $size = (int)filesize($pathname);
        if ($size <= 0 || $size > CommerceShowroomBlockMediaManager::MAX_IMAGE_BYTES) {
            throw new \moodle_exception('maxbytes', 'error');
        }

        $originalfilename = clean_param(
            $originalfilename ?? basename($pathname),
            PARAM_FILE
        );
        $extension = strtolower(pathinfo($originalfilename, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            throw new \invalid_parameter_exception('Unsupported exercise preview image format.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimetype = (string)$finfo->file($pathname);
        if (!in_array($mimetype, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            throw new \invalid_parameter_exception('Exercise preview file is not a supported image.');
        }

        $field = self::field_name($exercisekey, $language);
        $manager = $this->block_media_manager();
        $manager->delete_field($blockid, $field);

        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        return get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id,
            'component' => CommerceShowroomBlockMediaManager::COMPONENT,
            'filearea' => CommerceShowroomBlockMediaManager::FILEAREA,
            'itemid' => $blockid,
            'filepath' => '/' . $field . '/',
            'filename' => $field . '.' . $extension,
        ], $pathname);
    }

    public function get_file(int $blockid, string $exercisekey, string $language): ?\stored_file {
        $this->validate_slot($blockid, $exercisekey, $language);
        return $this->block_media_manager()->get_file(
            $blockid,
            self::field_name($exercisekey, $language)
        );
    }

    public function get_url(int $blockid, string $exercisekey, string $language): ?\moodle_url {
        $this->validate_slot($blockid, $exercisekey, $language);
        return $this->block_media_manager()->get_url(
            $blockid,
            self::field_name($exercisekey, $language)
        );
    }

    /**
     * Resolves locale override first, then the canonical default image.
     *
     * @return array{url:moodle_url,language:string,file:stored_file}|null
     */
    public function resolve(int $blockid, string $exercisekey, string $language): ?array {
        $this->validate_slot($blockid, $exercisekey, self::DEFAULT_LANGUAGE);
        $language = CommerceShowroomExerciseCatalog::normalise_language($language);

        foreach ([$language, self::DEFAULT_LANGUAGE] as $candidate) {
            $file = $this->get_file($blockid, $exercisekey, $candidate);
            if ($file !== null) {
                return [
                    'url' => $this->get_url($blockid, $exercisekey, $candidate),
                    'language' => $candidate,
                    'file' => $file,
                ];
            }
        }

        return null;
    }

    public function delete(int $blockid, string $exercisekey, string $language): void {
        $this->validate_slot($blockid, $exercisekey, $language);
        $this->block_media_manager()->delete_field(
            $blockid,
            self::field_name($exercisekey, $language)
        );
    }

    public static function field_name(string $exercisekey, string $language): string {
        if (!CommerceShowroomExerciseCatalog::exists($exercisekey)) {
            throw new \invalid_parameter_exception('Unknown Showroom exercise key.');
        }
        $language = strtolower(trim($language));
        if (!in_array($language, self::LANGUAGES, true)) {
            throw new \invalid_parameter_exception('Unsupported Showroom exercise preview language.');
        }
        return 'exercisepreview_' . $exercisekey . '_' . $language;
    }

    private function block_media_manager(): CommerceShowroomBlockMediaManager {
        return $this->blockmediamanager
            ?? new CommerceShowroomBlockMediaManager($this->context);
    }

    private function validate_slot(int $blockid, string $exercisekey, string $language): void {
        if ($blockid <= 0) {
            throw new \invalid_parameter_exception('Invalid Showroom block id.');
        }
        self::field_name($exercisekey, $language);
    }
}
