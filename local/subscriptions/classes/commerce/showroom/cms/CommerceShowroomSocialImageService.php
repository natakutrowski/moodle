<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Stores the social-sharing image attached to a Showroom. */
final class CommerceShowroomSocialImageService {
    public const COMPONENT = 'local_subscriptions';
    public const FILEAREA = 'showroom_social_image';
    public const MAX_IMAGE_BYTES = 20 * 1024 * 1024;

    /** @var string[] */
    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
    /** @var string[] */
    private const MIMETYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public function __construct(private readonly \context_system $context) {
    }

    public function store_uploaded_image(int $showroomid, string $uploadfield = 'socialimage'): \stored_file {
        if ($showroomid <= 0) {
            throw new \invalid_parameter_exception('Invalid showroom id.');
        }
        if (!isset($_FILES[$uploadfield]) || (int)($_FILES[$uploadfield]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new \invalid_parameter_exception('No social image was uploaded.');
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
        if ($size <= 0 || $size > self::MAX_IMAGE_BYTES) {
            throw new \moodle_exception('maxbytes', 'error');
        }

        $original = clean_param((string)($upload['name'] ?? ''), PARAM_FILE);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS, true)) {
            throw new \invalid_parameter_exception('Unsupported Showroom social image format.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimetype = (string)$finfo->file($tmpname);
        if (!in_array($mimetype, self::MIMETYPES, true)) {
            throw new \invalid_parameter_exception('The uploaded file is not a supported image.');
        }

        $this->delete($showroomid);
        $filename = 'social.' . ($extension === 'jpeg' ? 'jpg' : $extension);

        return get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $showroomid,
            'filepath' => '/',
            'filename' => $filename,
        ], $tmpname);
    }

    public function get_file(int $showroomid): ?\stored_file {
        foreach (get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $showroomid,
            'id DESC',
            false
        ) as $file) {
            return $file;
        }
        return null;
    }

    public function get_url(int $showroomid): ?\moodle_url {
        $file = $this->get_file($showroomid);
        if ($file === null) {
            return null;
        }

        return \moodle_url::make_pluginfile_url(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $showroomid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        );
    }

    public function delete(int $showroomid): void {
        get_file_storage()->delete_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $showroomid
        );
    }

    public function duplicate(int $sourceid, int $targetid): void {
        $source = $this->get_file($sourceid);
        if ($source === null) {
            return;
        }
        $this->delete($targetid);
        get_file_storage()->create_file_from_storedfile([
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $targetid,
            'filepath' => '/',
            'filename' => $source->get_filename(),
        ], $source);
    }
}
