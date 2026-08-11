<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Import/export service for portable Showroom definitions.
 *
 * Package v2 stores every block configuration as a real JSON object rather
 * than a JSON-encoded string. Moodle File API media binaries are intentionally
 * not embedded in the JSON package.
 */
final class CommerceShowroomPackageService {
    public const FORMAT = 'campusfr-showroom';
    public const VERSION = 2;
    public const MAX_BLOCKS = 200;
    public const MAX_IMPORT_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private readonly CommerceShowroomCmsRepository $repository
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function export(int $showroomid): array {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        $showroomdata = (array)$showroom;
        unset(
            $showroomdata['id'],
            $showroomdata['timecreated'],
            $showroomdata['timemodified'],
            $showroomdata['usermodified']
        );

        // Never export a live publication state as an import default.
        // The imported copy is forced to draft as a second safety barrier.
        $showroomdata['status'] = CommerceShowroomStatus::DRAFT;

        $blocks = [];
        foreach ($this->repository->blocks($showroomid) as $block) {
            $config = json_decode(
                (string)$block->configjson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($config)) {
                $config = [];
            }

            $blocks[] = [
                'blockkey' => (string)$block->blockkey,
                'blocktype' => (string)$block->blocktype,
                'sortorder' => (int)$block->sortorder,
                'enabled' => (int)$block->enabled === 1,
                'config' => $config,
            ];
        }

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exportedat' => time(),
            'showroom' => $showroomdata,
            'blocks' => $blocks,
            'media' => [
                'included' => false,
                'note' => 'Moodle File API media binaries are not embedded in this JSON package.',
            ],
        ];
    }

    public function import(string $json, int $userid): int {
        if (strlen($json) > self::MAX_IMPORT_BYTES) {
            throw new \invalid_parameter_exception(
                'Showroom package exceeds the maximum JSON import size.'
            );
        }

        $package = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $result = $this->import_package($package, $userid);

        return $result['showroomid'];
    }

    /**
     * Imports an already decoded package.
     *
     * The block map is required by the portable ZIP importer because Moodle
     * File API media itemids must be rewritten to the newly-created block IDs.
     *
     * @param mixed $package
     * @return array{
     *     showroomid:int,
     *     blockmap:array<string,int>,
     *     blockcount:int
     * }
     */
    public function import_package(mixed $package, int $userid): array {
        $normalised = $this->validate_and_normalise_package($package);

        $showroom = $normalised['showroom'];
        $blocks = $normalised['blocks'];

        $basekey = clean_param(
            (string)($showroom['showroomkey'] ?? 'imported-showroom'),
            PARAM_ALPHANUMEXT
        );
        $showroom['showroomkey'] = $this->unique_showroom_key($basekey);
        $showroom['name'] = trim(
            (string)($showroom['name'] ?? 'Showroom importé')
        ) . ' (import)';

        // Security: imports are never public until explicitly reviewed/published.
        $showroom['status'] = CommerceShowroomStatus::DRAFT;

        // Imported copies cannot collide with public routes.
        foreach (['slugfr', 'slugen', 'slugru'] as $slugfield) {
            $showroom[$slugfield] = '';
        }

        $showroomid = $this->repository->save($showroom, $userid);
        $blockmap = [];

        foreach ($blocks as $index => $block) {
            $sourcekey = trim((string)($block['sourceblockkey'] ?? ''));
            if ($sourcekey === '') {
                $sourcekey = 'block-' . ($index + 1);
            }

            $blockid = $this->repository->save_block(
                $showroomid,
                [
                    'blocktype' => $block['blocktype'],
                    // Generate new block keys to guarantee local uniqueness.
                    'blockkey' => '',
                    'sortorder' => $block['sortorder'],
                    'enabled' => $block['enabled'],
                    'configjson' => json_encode(
                        $block['config'],
                        JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                            | JSON_THROW_ON_ERROR
                    ),
                ],
                $userid
            );

            $blockmap[$sourcekey] = $blockid;
        }

        return [
            'showroomid' => $showroomid,
            'blockmap' => $blockmap,
            'blockcount' => count($blocks),
        ];
    }

    /**
     * @param mixed $package
     * @return array{
     *     showroom:array<string,mixed>,
     *     blocks:array<int,array{
     *         blocktype:string,
     *         sortorder:int,
     *         enabled:bool,
     *         config:array<string,mixed>
     *     }>
     * }
     */
    private function validate_and_normalise_package(mixed $package): array {
        if (
            !is_array($package)
            || ($package['format'] ?? '') !== self::FORMAT
            || !isset($package['showroom'])
            || !is_array($package['showroom'])
        ) {
            throw new \invalid_parameter_exception(
                'Invalid showroom package.'
            );
        }

        $version = (int)($package['version'] ?? 1);
        if (!in_array($version, [1, self::VERSION], true)) {
            throw new \invalid_parameter_exception(
                'Unsupported showroom package version.'
            );
        }

        $rawblocks = $package['blocks'] ?? [];
        if (!is_array($rawblocks)) {
            throw new \invalid_parameter_exception(
                'Invalid showroom block list.'
            );
        }
        if (count($rawblocks) > self::MAX_BLOCKS) {
            throw new \invalid_parameter_exception(
                'Showroom package contains too many blocks.'
            );
        }

        $blocks = [];
        foreach ($rawblocks as $index => $block) {
            if (!is_array($block)) {
                throw new \invalid_parameter_exception(
                    'Invalid showroom block at index ' . $index . '.'
                );
            }

            $blocktype = clean_param(
                (string)($block['blocktype'] ?? ''),
                PARAM_ALPHANUMEXT
            );
            if (
                $blocktype === ''
                || !CommerceShowroomBlockTypeRegistry::exists($blocktype)
            ) {
                throw new \invalid_parameter_exception(
                    'Unsupported showroom block type at index ' . $index . '.'
                );
            }

            $config = $this->normalise_block_config($block, $version, $index);

            $blocks[] = [
                'sourceblockkey' => clean_param(
                    (string)($block['blockkey'] ?? ''),
                    PARAM_ALPHANUMEXT
                ),
                'blocktype' => $blocktype,
                'sortorder' => max(
                    0,
                    (int)($block['sortorder'] ?? (($index + 1) * 10))
                ),
                'enabled' => !empty($block['enabled']),
                'config' => $config,
            ];
        }

        return [
            'showroom' => (array)$package['showroom'],
            'blocks' => $blocks,
        ];
    }

    /**
     * @param array<string,mixed> $block
     * @return array<string,mixed>
     */
    private function normalise_block_config(
        array $block,
        int $version,
        int $index
    ): array {
        if ($version >= 2) {
            $config = $block['config'] ?? [];
            if (!is_array($config)) {
                throw new \invalid_parameter_exception(
                    'Invalid showroom block config at index ' . $index . '.'
                );
            }
            return $config;
        }

        // Backward compatibility with the original v1 package format.
        $configjson = (string)($block['configjson'] ?? '{}');
        $config = json_decode(
            $configjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        if (!is_array($config)) {
            throw new \invalid_parameter_exception(
                'Invalid showroom block config at index ' . $index . '.'
            );
        }

        return $config;
    }

    private function unique_showroom_key(string $base): string {
        $base = $base !== '' ? $base : 'imported-showroom';
        $candidate = $base;
        $counter = 2;

        while ($this->repository->get_by_key($candidate) !== null) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
