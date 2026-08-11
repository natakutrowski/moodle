<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\assets;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogAssetManager {
    public function __construct(private readonly string $root) {}

    public function store_upload(string $field, string $subdir, string $basename, array $extensions): ?string {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return null;
        }
        $original = (string)($_FILES[$field]['name'] ?? '');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, $extensions, true)) {
            throw new \moodle_exception('commerce_invalid_asset_type', 'local_subscriptions');
        }
        $directory = rtrim($this->root, '/') . '/' . trim($subdir, '/');
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create Commerce asset directory.');
        }
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename) . '.' . $extension;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $directory . '/' . $filename)) {
            throw new \RuntimeException('Unable to move uploaded Commerce asset.');
        }
        return trim($subdir, '/') . '/' . $filename;
    }

    public function absolute_path(string $relative): string {
        return rtrim($this->root, '/') . '/' . ltrim($relative, '/');
    }
}
