<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\transfer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

/** Portable Storefront package (.cfrproduct) with metadata and media files. */
final class CommerceStorefrontPackageService {
    public const FORMAT = 'campusfr-commerce-product';
    public const VERSION = 1;
    public const EXTENSION = 'cfrproduct';
    public const MAX_PACKAGE_BYTES = 250 * 1024 * 1024;

    public function __construct(
        private readonly \context_system $context
    ) {
    }

    public static function create(): self {
        return new self(\context_system::instance());
    }

    /** Creates a package and returns its temporary pathname. */
    public function export(CommerceProduct $product): string {
        $storefront = $this->storefront_configuration($product->get_metadata());
        $temporary = make_request_directory();
        $archivepath = $temporary . '/' . clean_filename(
            strtolower($product->get_sku()) . '-storefront.' . self::EXTENSION
        );
        $archive = new \ZipArchive();
        if ($archive->open($archivepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \moodle_exception('errorcreatingfile', 'error');
        }

        $manifest = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'scope' => 'storefront',
            'exportedat' => time(),
            'source' => [
                'sku' => $product->get_sku(),
                'type' => $product->get_type(),
                'name' => $product->get_name(),
            ],
            'storefront' => $storefront,
            'media' => [],
        ];

        $mediaitemids = $this->collect_media_item_ids($storefront);
        foreach ($mediaitemids as $itemid) {
            $files = get_file_storage()->get_area_files(
                $this->context->id,
                CommerceStorefrontContentFileService::COMPONENT,
                CommerceStorefrontContentFileService::FILEAREA,
                $itemid,
                'filepath, filename',
                false
            );
            foreach ($files as $file) {
                $entry = 'media/' . $itemid
                    . $file->get_filepath()
                    . $file->get_filename();
                $archive->addFromString($entry, $file->get_content());
                $manifest['media'][] = [
                    'itemid' => $itemid,
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                    'mimetype' => $file->get_mimetype(),
                    'entry' => $entry,
                ];
            }
        }

        // Content Bank H5P IDs are not portable. Include their package file
        // and restore it as the section-local H5P slot on import.
        foreach ($this->section_references($storefront) as $reference) {
            $section = $reference['section'];
            $contentid = (int)($section['h5pcontentid'] ?? 0);
            if ($contentid <= 0) {
                continue;
            }
            $content = $GLOBALS['DB']->get_record(
                'contentbank_content',
                ['id' => $contentid, 'contenttype' => 'contenttype_h5p'],
                'id,contextid',
                IGNORE_MISSING
            );
            if (!$content) {
                continue;
            }
            $files = get_file_storage()->get_area_files(
                (int)$content->contextid,
                'contentbank',
                'public',
                (int)$content->id,
                'id DESC',
                false
            );
            foreach ($files as $file) {
                if (strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)) !== 'h5p') {
                    continue;
                }
                $entry = 'contentbank/' . $reference['key'] . '/' . $file->get_filename();
                $archive->addFromString($entry, $file->get_content());
                $manifest['media'][] = [
                    'sectionkey' => $reference['key'],
                    'slot' => 'h5p',
                    'filepath' => '/h5p/',
                    'filename' => $file->get_filename(),
                    'mimetype' => $file->get_mimetype(),
                    'entry' => $entry,
                ];
                break;
            }
        }

        $archive->addFromString(
            'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        $archive->close();
        return $archivepath;
    }

    /** Imports one package into the target product and returns merged metadata. */
    public function import(string $pathname, CommerceProduct $target): array {
        if (!is_file($pathname) || filesize($pathname) > self::MAX_PACKAGE_BYTES) {
            throw new \moodle_exception('commerce_storefront_package_invalid', 'local_subscriptions');
        }
        $archive = new \ZipArchive();
        if ($archive->open($pathname) !== true) {
            throw new \moodle_exception('commerce_storefront_package_invalid', 'local_subscriptions');
        }
        try {
            $rawmanifest = $archive->getFromName('manifest.json');
            $manifest = is_string($rawmanifest) ? json_decode($rawmanifest, true) : null;
            if (
                !is_array($manifest)
                || ($manifest['format'] ?? '') !== self::FORMAT
                || (int)($manifest['version'] ?? 0) !== self::VERSION
                || !is_array($manifest['storefront'] ?? null)
            ) {
                throw new \moodle_exception('commerce_storefront_package_invalid', 'local_subscriptions');
            }
            $storefront = $manifest['storefront'];
            $mapping = [];
            foreach ($this->collect_media_item_ids($storefront) as $olditemid) {
                $mapping[$olditemid] = CommerceStorefrontContentFileService::create()->ensure_item_id();
            }
            $storefront = $this->remap_storefront_item_ids($storefront, $mapping);

            foreach ((array)($manifest['media'] ?? []) as $media) {
                if (!is_array($media)) {
                    continue;
                }
                $entry = (string)($media['entry'] ?? '');
                if (!$this->safe_entry($entry)) {
                    continue;
                }
                $content = $archive->getFromName($entry);
                if (!is_string($content)) {
                    continue;
                }
                $targetitemid = 0;
                if (!empty($media['sectionkey'])) {
                    $targetitemid = $this->item_id_for_section_key($storefront, (string)$media['sectionkey']);
                } else {
                    $targetitemid = (int)($mapping[(int)($media['itemid'] ?? 0)] ?? 0);
                }
                if ($targetitemid <= 0) {
                    continue;
                }
                $filepath = clean_param((string)($media['filepath'] ?? '/'), PARAM_PATH);
                $filepath = '/' . trim($filepath, '/') . '/';
                if ($filepath === '//') {
                    $filepath = '/';
                }
                $filename = clean_filename((string)($media['filename'] ?? ''));
                if ($filename === '') {
                    continue;
                }
                $storage = get_file_storage();
                $existing = $storage->get_file(
                    $this->context->id,
                    CommerceStorefrontContentFileService::COMPONENT,
                    CommerceStorefrontContentFileService::FILEAREA,
                    $targetitemid,
                    $filepath,
                    $filename
                );
                if ($existing !== false) {
                    $existing->delete();
                }
                $storage->create_file_from_string([
                    'contextid' => $this->context->id,
                    'component' => CommerceStorefrontContentFileService::COMPONENT,
                    'filearea' => CommerceStorefrontContentFileService::FILEAREA,
                    'itemid' => $targetitemid,
                    'filepath' => $filepath,
                    'filename' => $filename,
                ], $content);
            }

            $storefront = $this->make_h5p_references_portable($storefront, (array)($manifest['media'] ?? []));
            $metadata = $target->get_metadata();
            $metadata['storefront'] = $storefront;
            return $metadata;
        } finally {
            $archive->close();
        }
    }

    /** @return array<string,mixed> */
    private function storefront_configuration(array $metadata): array {
        $configuration = $metadata['storefront'] ?? [];
        if (is_string($configuration)) {
            $decoded = json_decode($configuration, true);
            $configuration = is_array($decoded) ? $decoded : [];
        }
        return is_array($configuration) ? $configuration : [];
    }

    /** @return int[] */
    private function collect_media_item_ids(array $storefront): array {
        $ids = [];
        foreach ($this->section_references($storefront) as $reference) {
            $itemid = (int)($reference['section']['mediaitemid'] ?? 0);
            if ($itemid > 0) {
                $ids[$itemid] = $itemid;
            }
        }
        return array_values($ids);
    }

    /** @return array<int,array{key:string,section:array<string,mixed>}> */
    private function section_references(array $storefront): array {
        $references = [];
        foreach ((array)($storefront['sections'] ?? []) as $index => $section) {
            if (is_array($section)) {
                $references[] = ['key' => 'default:' . $index, 'section' => $section];
            }
        }
        foreach ((array)($storefront['locales'] ?? []) as $language => $locale) {
            foreach ((array)($locale['sections'] ?? []) as $index => $section) {
                if (is_array($section)) {
                    $references[] = ['key' => 'locale:' . $language . ':' . $index, 'section' => $section];
                }
            }
        }
        return $references;
    }

    private function remap_storefront_item_ids(array $storefront, array $mapping): array {
        $remap = static function(array $sections) use ($mapping): array {
            foreach ($sections as &$section) {
                if (!is_array($section)) {
                    continue;
                }
                $old = (int)($section['mediaitemid'] ?? 0);
                if ($old > 0 && isset($mapping[$old])) {
                    $section['mediaitemid'] = $mapping[$old];
                }
            }
            unset($section);
            return $sections;
        };
        $storefront['sections'] = $remap((array)($storefront['sections'] ?? []));
        foreach ((array)($storefront['locales'] ?? []) as $language => $locale) {
            if (!is_array($locale)) {
                continue;
            }
            $locale['sections'] = $remap((array)($locale['sections'] ?? []));
            $storefront['locales'][$language] = $locale;
        }
        return $storefront;
    }

    private function item_id_for_section_key(array $storefront, string $key): int {
        $parts = explode(':', $key);
        if (($parts[0] ?? '') === 'default') {
            return (int)($storefront['sections'][(int)($parts[1] ?? -1)]['mediaitemid'] ?? 0);
        }
        if (($parts[0] ?? '') === 'locale') {
            return (int)($storefront['locales'][$parts[1] ?? '']['sections'][(int)($parts[2] ?? -1)]['mediaitemid'] ?? 0);
        }
        return 0;
    }

    private function make_h5p_references_portable(array $storefront, array $media): array {
        $keys = [];
        foreach ($media as $entry) {
            if (is_array($entry) && ($entry['slot'] ?? '') === 'h5p' && !empty($entry['sectionkey'])) {
                $keys[(string)$entry['sectionkey']] = true;
            }
        }
        foreach (array_keys($keys) as $key) {
            $parts = explode(':', $key);
            if (($parts[0] ?? '') === 'default' && isset($storefront['sections'][(int)$parts[1]])) {
                $storefront['sections'][(int)$parts[1]]['h5pcontentid'] = 0;
            }
            if (($parts[0] ?? '') === 'locale' && isset($storefront['locales'][$parts[1]]['sections'][(int)$parts[2]])) {
                $storefront['locales'][$parts[1]]['sections'][(int)$parts[2]]['h5pcontentid'] = 0;
            }
        }
        return $storefront;
    }

    private function safe_entry(string $entry): bool {
        return $entry !== ''
            && !str_contains($entry, '..')
            && !str_starts_with($entry, '/')
            && !str_contains($entry, "\\");
    }
}
