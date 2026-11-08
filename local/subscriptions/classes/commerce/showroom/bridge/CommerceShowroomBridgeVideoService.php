<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Public presentation videos for Lovable Bridge, stored in Moodle's File API. */
final class CommerceShowroomBridgeVideoService {
    public const COMPONENT = 'local_subscriptions';
    public const FILEAREA = 'showroom_bridge_video';
    public const MAX_VIDEO_BYTES = 500 * 1024 * 1024;
    private const ALLOWED_MIMES = ['mp4' => 'video/mp4', 'webm' => 'video/webm'];

    public function __construct(private readonly \context_system $context) {
    }

    /** @return bool True when a new file was submitted. */
    public static function has_upload(string $field = 'bridgevideo_upload'): bool {
        return isset($_FILES[$field])
            && (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** Validates an upload before the showroom record is saved. Does not mutate storage. */
    public static function validate_upload(string $field = 'bridgevideo_upload'): string {
        if (!self::has_upload($field)) {
            throw new \invalid_parameter_exception('A video file is required.');
        }
        $upload = $_FILES[$field];
        if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \invalid_parameter_exception('Video upload failed (check PHP upload and POST limits).');
        }
        $tmp = (string)($upload['tmp_name'] ?? '');
        $size = (int)($upload['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \invalid_parameter_exception('Invalid uploaded file.');
        }
        if ($size <= 0 || $size > self::MAX_VIDEO_BYTES) {
            throw new \invalid_parameter_exception('Presentation video exceeds the 500 MB limit.');
        }
        $name = clean_param((string)($upload['name'] ?? ''), PARAM_FILE);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_MIMES[$ext])) {
            throw new \invalid_parameter_exception('Only MP4 and WebM are accepted.');
        }
        $mime = (string)(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if ($mime !== self::ALLOWED_MIMES[$ext]) {
            throw new \invalid_parameter_exception('Presentation video MIME type does not match the extension.');
        }
        return $ext;
    }

    public function store_uploaded_video(int $showroomid, string $field = 'bridgevideo_upload'): \stored_file {
        if ($showroomid <= 0) {
            throw new \invalid_parameter_exception('Invalid showroom ID.');
        }
        $ext = self::validate_upload($field);
        $previous = $this->get_file($showroomid);
        $filename = 'presentation-' . bin2hex(random_bytes(8)) . '.' . $ext;
        // Write the replacement first: never erase an existing video on a failed upload.
        $file = get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $showroomid,
            'filepath' => '/',
            'filename' => $filename,
        ], (string)$_FILES[$field]['tmp_name']);
        if ($previous !== null) {
            $previous->delete();
        }
        return $file;
    }

    public function get_file(int $showroomid): ?\stored_file {
        if ($showroomid <= 0) {
            return null;
        }
        $files = get_file_storage()->get_area_files(
            $this->context->id, self::COMPONENT, self::FILEAREA, $showroomid, 'id DESC', false
        );
        foreach ($files as $file) {
            if ($file->get_filepath() === '/') {
                return $file;
            }
        }
        return null;
    }

    public function get_url(int $showroomid): ?\moodle_url {
        $file = $this->get_file($showroomid);
        return $file === null ? null : \moodle_url::make_pluginfile_url(
            $this->context->id, self::COMPONENT, self::FILEAREA,
            $showroomid, $file->get_filepath(), $file->get_filename(), false
        );
    }

    public function delete(int $showroomid): void {
        if ($showroomid > 0) {
            get_file_storage()->delete_area_files(
                $this->context->id, self::COMPONENT, self::FILEAREA, $showroomid
            );
        }
    }

    public function duplicate(int $sourceid, int $targetid): void {
        $source = $this->get_file($sourceid);
        if ($source === null || $targetid <= 0) {
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
