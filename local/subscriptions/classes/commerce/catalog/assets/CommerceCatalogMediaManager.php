<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\assets;

defined('MOODLE_INTERNAL') || die();

/** Stores catalogue presentation media through Moodle's File API. */
final class CommerceCatalogMediaManager {
    public const COMPONENT = 'local_subscriptions';
    public const FILEAREA = 'catalog_media';
    public const ROLE_COVER = 'cover';
    public const ROLE_STOREFRONT = 'storefront';
    public const ROLE_PRODUCT = 'product';
    public const ROLE_RECOMMENDATION = 'recommendation';
    public const ROLE_RESOURCES = 'resources';
    public const ROLE_CHECKOUT = 'checkout';
    public const ROLE_EMAIL = 'email';
    public const ROLE_SOCIAL = 'social';
    public const ROLE_SHOWROOM = 'showroom';
    public const MAX_BYTES = 10 * 1024 * 1024;
    private const SHOWROOM_DERIVATIVE_PATH = '/showroom_derivatives/';
    private const SHOWROOM_DERIVATIVE_WIDTHS = [640, 960];

    public function __construct(private readonly \context_system $context) {
    }

    public function prepare_draft(int $productid, string $role, int $draftitemid): void {
        file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            $this->file_options($role)
        );
    }

    public function save_draft(int $productid, string $role, int $draftitemid): void {
        file_save_draft_area_files(
            $draftitemid,
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            $this->file_options($role)
        );
        $this->delete_derivatives($productid, $role);
    }


    public function store_uploaded_file(int $productid, string $role, string $field): ?\stored_file {
        if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int)($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            throw new \moodle_exception('error_uploading_file', 'moodle');
        }
        $role = trim($role, '/');
        $options = $this->file_options($role);
        $original = clean_param((string)($_FILES[$field]['name'] ?? ''), PARAM_FILE);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = array_map(static fn(string $type): string => ltrim($type, '.'), $options['accepted_types']);
        if ((int)($_FILES[$field]['size'] ?? 0) > (int)$options['maxbytes']) {
            throw new \moodle_exception('maxbytes', 'error');
        }
        if ($original === '' || !in_array($extension, $allowed, true)) {
            throw new \moodle_exception('commerce_invalid_asset_type', 'local_subscriptions');
        }
        $storage = get_file_storage();
        $this->delete_role_files($productid, $role);

        $record = [
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/' . $role . '/',
            'filename' => $original,
        ];
        $stored = $storage->create_file_from_pathname($record, (string)$_FILES[$field]['tmp_name']);
        $this->delete_derivatives($productid, $role);
        if ($role === self::ROLE_SHOWROOM) {
            $this->ensure_showroom_derivatives($productid, $stored);
        }
        return $stored;
    }

    public function delete_file(
        int $productid,
        string $role
    ): void {
        $this->file_options($role);
        $this->delete_role_files($productid, $role);
        $this->delete_derivatives($productid, $role);
    }

    public function get_file(int $productid, string $role): ?\stored_file {
        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            'filename',
            false
        );
        foreach ($files as $file) {
            if ($file->get_filepath() === '/' . trim($role, '/') . '/') {
                return $file;
            }
        }
        return null;
    }

    public function get_url(int $productid, string $role): ?\moodle_url {
        $file = $this->get_file($productid, $role);
        if ($file === null) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            $file->get_filepath(),
            $file->get_filename(),
            false
        );
    }


    /**
     * Returns responsive derivatives for a catalogue media role.
     *
     * @param int[] $widths
     * @return array{src:string,srcset:string,width:int,height:int,role:string}|null
     */
    public function get_responsive_urls(
        int $productid,
        string $role,
        array $widths,
        int $ratiox,
        int $ratioy
    ): ?array {
        $role = trim(strtolower($role), '/');
        $this->file_options($role);
        $source = $this->get_file($productid, $role);
        if ($source === null || $widths === [] || $ratiox <= 0 || $ratioy <= 0) {
            return null;
        }

        $files = $this->ensure_derivatives($productid, $role, $source, $widths, $ratiox, $ratioy);
        if ($files === []) {
            return null;
        }

        ksort($files);
        $parts = [];
        foreach ($files as $width => $file) {
            $parts[] = $this->stored_file_url($file) . ' ' . $width . 'w';
        }
        $largestwidth = (int)array_key_last($files);
        $largest = $files[$largestwidth];
        return [
            'src' => $this->stored_file_url($largest),
            'srcset' => implode(', ', $parts),
            'width' => $largestwidth,
            'height' => (int)round($largestwidth * $ratioy / $ratiox),
            'role' => $role,
        ];
    }

    /**
     * Returns responsive Showroom derivatives, generating them lazily for
     * existing products uploaded before J15H.1E.
     *
     * @return array{src:string,srcset:string}|null
     */
    public function get_showroom_responsive_urls(int $productid): ?array {
        $resolved = $this->get_responsive_urls(
            $productid,
            self::ROLE_SHOWROOM,
            self::SHOWROOM_DERIVATIVE_WIDTHS,
            16,
            9
        );
        if ($resolved === null) {
            return null;
        }
        return ['src' => $resolved['src'], 'srcset' => $resolved['srcset']];
    }

    /** @return array<int,\stored_file> */
    private function ensure_showroom_derivatives(int $productid, \stored_file $source): array {
        return $this->ensure_derivatives(
            $productid,
            self::ROLE_SHOWROOM,
            $source,
            self::SHOWROOM_DERIVATIVE_WIDTHS,
            16,
            9
        );
    }

    /** @param int[] $widths @return array<int,\stored_file> */
    private function ensure_derivatives(
        int $productid,
        string $role,
        \stored_file $source,
        array $widths,
        int $ratiox,
        int $ratioy
    ): array {
        if (!function_exists('imagecreatefromstring')) {
            return [];
        }
        $storage = get_file_storage();
        $hash = substr($source->get_contenthash(), 0, 12);
        $path = '/' . $role . '_derivatives/';
        $result = [];
        $expected = [];
        foreach ($widths as $width) {
            $width = (int)$width;
            if ($width <= 0) {
                continue;
            }
            $filename = $role . '-' . $width . '-' . $hash
                . (function_exists('imagewebp') ? '.webp' : '.jpg');
            $expected[] = $filename;
            $existing = $storage->get_file(
                $this->context->id,
                self::COMPONENT,
                self::FILEAREA,
                $productid,
                $path,
                $filename
            );
            if ($existing !== false) {
                $result[$width] = $existing;
                continue;
            }
            $created = $this->create_derivative(
                $source, $productid, $role, $width, $ratiox, $ratioy, $filename
            );
            if ($created !== null) {
                $result[$width] = $created;
            }
        }
        foreach ($storage->get_area_files(
            $this->context->id, self::COMPONENT, self::FILEAREA, $productid, 'id ASC', false
        ) as $file) {
            if ($file->get_filepath() === $path && !in_array($file->get_filename(), $expected, true)) {
                $file->delete();
            }
        }
        return $result;
    }

    private function create_derivative(
        \stored_file $source,
        int $productid,
        string $role,
        int $targetwidth,
        int $ratiox,
        int $ratioy,
        string $filename
    ): ?\stored_file {
        $image = @imagecreatefromstring($source->get_content());
        if ($image === false) {
            return null;
        }

        $sourcewidth = imagesx($image);
        $sourceheight = imagesy($image);
        if ($sourcewidth <= 0 || $sourceheight <= 0) {
            imagedestroy($image);
            return null;
        }

        $targetheight = (int)round($targetwidth * $ratioy / $ratiox);
        $sourceratio = $sourcewidth / $sourceheight;
        $targetratio = $ratiox / $ratioy;
        if ($sourceratio > $targetratio) {
            $cropheight = $sourceheight;
            $cropwidth = (int)round($sourceheight * $targetratio);
            $sourcex = (int)floor(($sourcewidth - $cropwidth) / 2);
            $sourcey = 0;
        } else {
            $cropwidth = $sourcewidth;
            $cropheight = (int)round($sourcewidth / $targetratio);
            $sourcex = 0;
            $sourcey = (int)floor(($sourceheight - $cropheight) / 2);
        }

        $target = imagecreatetruecolor($targetwidth, $targetheight);
        imagecopyresampled(
            $target, $image,
            0, 0, $sourcex, $sourcey,
            $targetwidth, $targetheight, $cropwidth, $cropheight
        );

        $temp = make_request_directory() . '/' . $filename;
        $written = function_exists('imagewebp')
            ? imagewebp($target, $temp, 86)
            : imagejpeg($target, $temp, 88);
        imagedestroy($target);
        imagedestroy($image);
        if (!$written) {
            return null;
        }

        $record = [
            'contextid' => $this->context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/' . $role . '_derivatives/',
            'filename' => $filename,
        ];
        return get_file_storage()->create_file_from_pathname($record, $temp);
    }

    private function delete_showroom_derivatives(int $productid): void {
        $this->delete_derivatives($productid, self::ROLE_SHOWROOM);
    }

    private function delete_derivatives(int $productid, string $role): void {
        $path = '/' . trim($role, '/') . '_derivatives/';
        $files = get_file_storage()->get_area_files(
            $this->context->id, self::COMPONENT, self::FILEAREA, $productid, 'id ASC', false
        );
        foreach ($files as $file) {
            if ($file->get_filepath() === $path) {
                $file->delete();
            }
        }
    }

    private function stored_file_url(\stored_file $file): string {
        return \moodle_url::make_pluginfile_url(
            $this->context->id, self::COMPONENT, self::FILEAREA,
            $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false
        )->out(false);
    }

    /**
     * Deletes only files belonging to one visual role.
     *
     * file_storage::delete_area_files() cannot filter by filepath. Passing a
     * fifth filepath argument silently deletes the whole product file area,
     * including every other visual format.
     */
    private function delete_role_files(
        int $productid,
        string $role
    ): void {
        $rolepath = '/' . trim($role, '/') . '/';
        $files = get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT,
            self::FILEAREA,
            $productid,
            'id ASC',
            false
        );

        foreach ($files as $file) {
            if ($file->get_filepath() !== $rolepath) {
                continue;
            }
            $file->delete();
        }
    }

    /** @return array<string, mixed> */
    private function file_options(string $role): array {
        $role = trim($role, '/');
        $allowedroles = [
            self::ROLE_COVER, self::ROLE_STOREFRONT, self::ROLE_PRODUCT,
            self::ROLE_RECOMMENDATION, self::ROLE_RESOURCES, self::ROLE_CHECKOUT,
            self::ROLE_EMAIL, self::ROLE_SOCIAL, self::ROLE_SHOWROOM,
        ];
        if (!in_array($role, $allowedroles, true)) {
            throw new \coding_exception('Unsupported Commerce catalogue media role: ' . $role);
        }
        return [
            'subdirs' => true,
            'maxfiles' => 1,
            'maxbytes' => self::MAX_BYTES,
            'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp'],
            'context' => $this->context,
            'areamaxbytes' => self::MAX_BYTES,
        ];
    }
}
