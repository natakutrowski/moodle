<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores public Showroom block images with Moodle's File API.
 */
final class CommerceShowroomBlockMediaManager {
    public const COMPONENT = 'local_subscriptions';
    public const FILEAREA = 'showroom_block_media';
    public const MAX_IMAGE_BYTES = 20 * 1024 * 1024;
    public const MAX_VIDEO_BYTES = 500 * 1024 * 1024;

    /** @var string[] */
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    private const VIDEO_EXTENSIONS = ['mp4', 'webm'];

    /** @var string[] */
    private const IMAGE_MIMETYPES = ['image/png', 'image/jpeg', 'image/webp'];
    private const VIDEO_MIMETYPES = ['video/mp4', 'video/webm'];

    public function __construct(
        private readonly \context_system $context
    ) {
    }

    public function store_uploaded_media(
        int $blockid,
        string $field,
        string $uploadfield = 'media'
    ): \stored_file {
        $this->validate_field($field);
        $kind = $field === 'videourl' ? 'video' : 'image';

        if (
            !isset($_FILES[$uploadfield])
            || (int)($_FILES[$uploadfield]['error'] ?? UPLOAD_ERR_NO_FILE)
                === UPLOAD_ERR_NO_FILE
        ) {
            throw new \invalid_parameter_exception('No media file was uploaded.');
        }

        $upload = $_FILES[$uploadfield];
        if ((int)($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }

        $tmpname = (string)($upload['tmp_name'] ?? '');
        if ($tmpname === '' || !is_uploaded_file($tmpname)) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }

        $size = (int)($upload['size'] ?? 0);
        $maxbytes = $kind === 'video' ? self::MAX_VIDEO_BYTES : self::MAX_IMAGE_BYTES;
        if ($size <= 0 || $size > $maxbytes) {
            throw new \moodle_exception('maxbytes', 'error');
        }

        $original = clean_param(
            (string)($upload['name'] ?? ''),
            PARAM_FILE
        );
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $extensions = $kind === 'video' ? self::VIDEO_EXTENSIONS : self::IMAGE_EXTENSIONS;
        if (!in_array($extension, $extensions, true)) {
            throw new \invalid_parameter_exception('Unsupported Showroom ' . $kind . ' format.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimetype = (string)$finfo->file($tmpname);
        $mimetypes = $kind === 'video' ? self::VIDEO_MIMETYPES : self::IMAGE_MIMETYPES;
        if (!in_array($mimetype, $mimetypes, true)) {
            throw new \invalid_parameter_exception('The uploaded file is not a supported ' . $kind . '.');
        }

        $this->delete_field($blockid, $field);

        $filename = $field . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
        return get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $blockid,
            'filepath' => '/' . $field . '/',
            'filename' => $filename,
        ], $tmpname);
    }

    public function get_file(int $blockid, string $field): ?\stored_file {
        $this->validate_field($field);

        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $blockid,
            'id DESC',
            false
        );

        foreach ($files as $file) {
            if ($file->get_filepath() === '/' . $field . '/') {
                return $file;
            }
        }

        return null;
    }

    public function get_url(int $blockid, string $field): ?\moodle_url {
        $file = $this->get_file($blockid, $field);
        if ($file === null) {
            return null;
        }

        return \moodle_url::make_pluginfile_url(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $blockid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        );
    }

    public function delete_field(int $blockid, string $field): void {
        $this->validate_field($field);
        $filepath = '/' . $field . '/';

        foreach (
            get_file_storage()->get_area_files(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $blockid,
                'id ASC',
                false
            ) as $file
        ) {
            if ($file->get_filepath() === $filepath) {
                $file->delete();
            }
        }
    }

    public function delete_block(int $blockid): void {
        get_file_storage()->delete_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $blockid
        );
    }

    /**
     * Copies all media belonging to a duplicated block.
     *
     * @return array<string,string> Old URL => new URL.
     */
    public function duplicate_block(int $sourceblockid, int $targetblockid): array {
        $mapping = [];
        $storage = get_file_storage();

        foreach (
            $storage->get_area_files(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $sourceblockid,
                'id ASC',
                false
            ) as $source
        ) {
            $field = trim($source->get_filepath(), '/');
            if ($field === '') {
                continue;
            }

            $this->validate_field($field);
            $target = $storage->create_file_from_storedfile([
                'contextid' => $this->context->id,
                'component' => self::COMPONENT,
                'filearea' => self::FILEAREA,
                'itemid' => $targetblockid,
                'filepath' => $source->get_filepath(),
                'filename' => $source->get_filename(),
            ], $source);

            $oldurl = \moodle_url::make_pluginfile_url(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $sourceblockid,
                $source->get_filepath(),
                $source->get_filename(),
                false
            )->out(false);
            $newurl = \moodle_url::make_pluginfile_url(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $targetblockid,
                $target->get_filepath(),
                $target->get_filename(),
                false
            )->out(false);
            $mapping[$oldurl] = $newurl;
        }

        return $mapping;
    }

    /** @param array<string,mixed> $config @param array<string,string> $mapping */
    public function remap_config_urls(array $config, array $mapping): array {
        array_walk_recursive($config, static function(mixed &$value) use ($mapping): void {
            if (is_string($value) && isset($mapping[$value])) {
                $value = $mapping[$value];
            }
        });
        return $config;
    }

    private function validate_field(string $field): void {
        if (
            $field === ''
            || clean_param($field, PARAM_ALPHANUMEXT) !== $field
        ) {
            throw new \invalid_parameter_exception(
                'Invalid Showroom media field.'
            );
        }
    }
}
