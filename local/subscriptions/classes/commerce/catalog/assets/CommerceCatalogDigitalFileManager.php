<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\assets;

defined('MOODLE_INTERNAL') || die();

/** Manages private deliverable files owned directly by Native digital products. */
final class CommerceCatalogDigitalFileManager {
    public const COMPONENT = 'local_subscriptions';
    public const FILEAREA = 'catalog_digital_file';
    public const ROLE_DESKTOP = 'desktop';
    public const ROLE_MOBILE = 'mobile';
    public const MAX_BYTES = 100 * 1024 * 1024;

    public function __construct(private readonly \context_system $context) {
    }

    public function store_uploaded_file(int $productid, string $role, string $field): ?\stored_file {
        if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int)($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || empty($_FILES[$field]['tmp_name'])
            || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }

        $role = $this->require_role($role);
        $filename = clean_param((string)($_FILES[$field]['name'] ?? ''), PARAM_FILE);
        if ($filename === '' || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new \moodle_exception('commerce_invalid_digital_file_type', 'local_subscriptions');
        }
        if ((int)($_FILES[$field]['size'] ?? 0) > self::MAX_BYTES) {
            throw new \moodle_exception('maxbytes', 'error');
        }

        $this->delete_file($productid, $role);
        return get_file_storage()->create_file_from_pathname([
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/' . $role . '/',
            'filename' => $filename,
        ], (string)$_FILES[$field]['tmp_name']);
    }

    public function delete_file(int $productid, string $role): void {
        $role = $this->require_role($role);
        $filepath = '/' . $role . '/';
        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            'id',
            false
        );

        foreach ($files as $file) {
            if ($file->get_filepath() === $filepath) {
                $file->delete();
            }
        }
    }

    public function get_file(int $productid, string $role): ?\stored_file {
        $role = $this->require_role($role);
        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            'filename',
            false
        );
        foreach ($files as $file) {
            if ($file->get_filepath() === '/' . $role . '/') {
                return $file;
            }
        }
        return null;
    }

    public function has_any_file(int $productid): bool {
        return $this->get_file($productid, self::ROLE_DESKTOP) !== null
            || $this->get_file($productid, self::ROLE_MOBILE) !== null;
    }

    private function require_role(string $role): string {
        $role = trim($role, '/');
        if (!in_array($role, [self::ROLE_DESKTOP, self::ROLE_MOBILE], true)) {
            throw new \coding_exception('Unsupported Native digital file role: ' . $role);
        }
        return $role;
    }
}
