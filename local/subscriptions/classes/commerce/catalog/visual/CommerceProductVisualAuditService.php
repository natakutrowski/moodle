<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\visual;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;

/** Read-only audit of existing product artwork and its J7 target format. */
final class CommerceProductVisualAuditService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly \context_system $context
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function audit(
        ?string $sku = null,
        ?int $productid = null,
        bool $includeinactive = false
    ): array {
        $products = $this->products(
            $sku,
            $productid,
            $includeinactive
        );
        $rows = [];

        foreach ($products as $product) {
            $rows[] = $this->audit_product($product);
        }

        $missing = 0;
        $fallbackonly = 0;
        $mismatched = 0;
        foreach ($rows as $row) {
            foreach ($row['formats'] as $format) {
                if (
                    !$format['available']
                    && !$format['fallback_available']
                ) {
                    $missing++;
                }
                if (
                    !$format['available']
                    && $format['fallback_available']
                ) {
                    $fallbackonly++;
                }
                if (
                    $format['available']
                    && !$format['ratio_ok']
                ) {
                    $mismatched++;
                }
            }
        }

        return [
            'generatedat' => time(),
            'readonly' => true,
            'products' => $rows,
            'summary' => [
                'products' => count($rows),
                'missing_master_formats' => $missing,
                'fallback_only_formats' => $fallbackonly,
                'ratio_mismatches' => $mismatched,
                'includes_inactive' => $includeinactive,
            ],
            'contract' => CommerceProductVisualFormat::definitions(),
        ];
    }

    /**
     * @return \stdClass[]
     */
    private function products(
        ?string $sku,
        ?int $productid,
        bool $includeinactive
    ): array {
        $conditions = [];
        $params = [];

        if ($sku !== null && trim($sku) !== '') {
            $conditions[] = 'sku = :sku';
            $params['sku'] = trim($sku);
        }
        if ($productid !== null && $productid > 0) {
            $conditions[] = 'id = :productid';
            $params['productid'] = $productid;
        }

        $hasexplicitfilter = (
            $sku !== null
            && trim($sku) !== ''
        ) || (
            $productid !== null
            && $productid > 0
        );

        if (!$includeinactive && !$hasexplicitfilter) {
            $conditions[] = 'status = :activestatus';
            $params['activestatus'] = 'active';
        }

        $where = $conditions === []
            ? ''
            : ' WHERE ' . implode(' AND ', $conditions);

        return $this->db->get_records_sql(
            'SELECT id, sku, name, type, status
               FROM {local_subs_commerce_product}'
            . $where
            . ' ORDER BY id ASC',
            $params
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function audit_product(\stdClass $product): array {
        $files = $this->files_by_role((int)$product->id);
        $formats = [];

        foreach (
            CommerceProductVisualFormat::definitions()
            as $format => $definition
        ) {
            $candidates = [];
            foreach ($definition['roles'] as $role) {
                if (isset($files[$role])) {
                    $candidates[] = $files[$role];
                }
            }

            // Legacy cover remains a final migration fallback.
            if ($candidates === [] && isset($files['cover'])) {
                $candidates[] = $files['cover'];
            }

            $selected = $candidates[0] ?? null;
            $formats[$format] = $this->format_result(
                $format,
                $definition,
                $selected,
                $files
            );
        }

        return [
            'id' => (int)$product->id,
            'sku' => (string)$product->sku,
            'name' => (string)$product->name,
            'type' => (string)$product->type,
            'status' => (string)$product->status,
            'placeholder_icon' => self::placeholder_icon(
                (string)$product->type
            ),
            'formats' => $formats,
            'legacy_roles' => array_values(array_keys($files)),
        ];
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed>|null $selected
     * @param array<string,array<string,mixed>> $files
     * @return array<string,mixed>
     */
    private function format_result(
        string $format,
        array $definition,
        ?array $selected,
        array $files
    ): array {
        $fallbackavailable = $selected !== null;
        $sourceRole = (string)($selected['role'] ?? '');
        $available = $fallbackavailable
            && in_array($sourceRole, $definition['roles'], true);
        $width = (int)($selected['width'] ?? 0);
        $height = (int)($selected['height'] ?? 0);

        return [
            'format' => $format,
            'ratio' => (string)$definition['ratio'],
            'recommended' => (int)$definition['width']
                . 'x'
                . (int)$definition['height'],
            'surfaces' => $definition['surfaces'],
            'source_role' => $selected['role'] ?? null,
            'filename' => $selected['filename'] ?? null,
            'filesize' => $selected['filesize'] ?? 0,
            'width' => $width,
            'height' => $height,
            'available' => $available,
            'fallback_available' => $fallbackavailable && !$available,
            'ratio_ok' => $available
                && CommerceProductVisualFormat::ratio_matches(
                    $format,
                    $width,
                    $height
                ),
            'fallback_ratio_ok' => $fallbackavailable
                && CommerceProductVisualFormat::ratio_matches(
                    $format,
                    $width,
                    $height
                ),
            'fallback' => $fallbackavailable && !$available,
            'available_roles' => array_values(array_keys($files)),
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function files_by_role(int $productid): array {
        $storage = get_file_storage();
        $files = $storage->get_area_files(
            $this->context->id,
            CommerceCatalogMediaManager::COMPONENT,
            CommerceCatalogMediaManager::FILEAREA,
            $productid,
            'filename',
            false
        );
        $result = [];

        foreach ($files as $file) {
            $role = trim($file->get_filepath(), '/');
            if ($role === '') {
                continue;
            }

            [$width, $height] = $this->dimensions($file);
            $result[$role] = [
                'role' => $role,
                'filename' => $file->get_filename(),
                'filesize' => $file->get_filesize(),
                'width' => $width,
                'height' => $height,
            ];
        }

        return $result;
    }

    /** @return array{0:int,1:int} */
    private function dimensions(\stored_file $file): array {
        $info = @getimagesizefromstring($file->get_content());
        if (!is_array($info)) {
            return [0, 0];
        }

        return [(int)($info[0] ?? 0), (int)($info[1] ?? 0)];
    }

    public static function placeholder_icon(string $producttype): string {
        return match (strtolower(trim($producttype))) {
            'course_access' => 'fa-solid fa-graduation-cap',
            'digital_download' => 'fa-solid fa-file-arrow-down',
            'bundle' => 'fa-solid fa-boxes-stacked',
            'service' => 'fa-solid fa-handshake',
            default => 'fa-solid fa-box',
        };
    }
}
