<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

/**
 * Saves one Storefront section without rebuilding the complete page form.
 *
 * The section is normalised through CommerceStorefrontPageEditor, then upserted
 * directly in the existing locale metadata. This deliberately avoids the old
 * full-form reconstruction which could report a successful upload while losing
 * the section/mediaitemid association.
 */
final class CommerceStorefrontSectionSaveService {
    public function __construct(
        private readonly CommerceStorefrontPageEditor $editor,
        private readonly CommerceStorefrontContentFileService $files
    ) {
    }

    /**
     * @param array<string,mixed> $post
     * @return array{metadata:array<string,mixed>,section:array<string,mixed>,diagnostics:array<string,mixed>}
     */
    public function save(CommerceProduct $product, string $language, array $post): array {
        $language = $this->normalise_language($language);
        $sourceindex = max(0, (int)($post['section_index'] ?? 0));
        $metadata = $product->get_metadata();
        $storefront = $this->storefront($metadata);
        $sections = $this->locale_sections($storefront, $language);

        $requestedid = trim((string)($post['section_id_' . $sourceindex] ?? ''));
        $targetindex = $this->find_section_index($sections, $requestedid);
        $existing = $targetindex === null ? [] : $sections[$targetindex];
        $sectionid = $requestedid !== ''
            ? $requestedid
            : $this->new_section_id($sections);

        $itemid = $this->files->ensure_item_id(
            max(
                (int)($existing['mediaitemid'] ?? 0),
                (int)($post['section_content_itemid_' . $sourceindex] ?? 0)
            )
        );

        $single = $this->single_section_submission(
            $post,
            $sourceindex,
            $sectionid,
            $itemid
        );
        $type = strtolower(trim((string)($single['section_type_0'] ?? '')));
        if (!in_array($type, CommerceStorefrontPageEditor::section_types(), true)) {
            throw new \moodle_exception('invalidparameter');
        }

        if (
            in_array(
                $type,
                ['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features'],
                true
            )
        ) {
            $single['section_content_0'] = $this->files->save_editor(
                (int)($single['section_content_draft_0'] ?? 0),
                $itemid,
                (string)($single['section_content_0'] ?? '')
            );
        }

        $this->save_media($sourceindex, $type, $itemid);

        // Use the official editor contract to normalise exactly one section.
        $normalisedmetadata = $this->editor->merge_submission([], $single, $language);
        $normalisedstorefront = $this->storefront($normalisedmetadata);
        $normalisedsections = $this->locale_sections($normalisedstorefront, $language);
        $section = $normalisedsections[0] ?? [];
        if (!is_array($section) || $section === []) {
            throw new \coding_exception('Storefront section could not be normalised.');
        }

        $section['id'] = $sectionid;
        $section['mediaitemid'] = $itemid;
        $section['order'] = (int)($existing['order'] ?? ($targetindex ?? count($sections)) * 10);

        if ($type === 'video' && $this->files->get_slot_file($itemid, 'video') !== null) {
            $section['videosource'] = 'upload';
        }
        if ($type === 'image_text' && $this->files->get_slot_file($itemid, 'image') !== null) {
            $section['imagemode'] = 'upload';
        }
        if ($type === 'h5p' && $this->files->get_slot_file($itemid, 'h5p') !== null) {
            $section['h5pcontentid'] = 0;
        }

        if ($targetindex === null) {
            if (count($sections) >= CommerceStorefrontPageEditor::MAX_SECTIONS) {
                throw new \moodle_exception('invalidparameter');
            }
            $sections[] = $section;
        } else {
            $sections[$targetindex] = $section;
        }

        $sections = array_values($sections);
        usort($sections, static function(array $left, array $right): int {
            return ((int)($left['order'] ?? 0) <=> (int)($right['order'] ?? 0))
                ?: strcmp((string)($left['id'] ?? ''), (string)($right['id'] ?? ''));
        });
        foreach ($sections as $position => &$candidate) {
            $candidate['order'] = $position * 10;
        }
        unset($candidate);

        $storefront['locales'] = is_array($storefront['locales'] ?? null)
            ? $storefront['locales']
            : [];
        $storefront['locales'][$language] = is_array($storefront['locales'][$language] ?? null)
            ? $storefront['locales'][$language]
            : [];
        $storefront['locales'][$language]['sections'] = $sections;
        if ($language === 'fr') {
            $storefront['sections'] = $sections;
        }
        $metadata['storefront'] = $storefront;

        $persistedsection = $this->find_section($sections, $sectionid);
        if ($persistedsection === null || (int)($persistedsection['mediaitemid'] ?? 0) !== $itemid) {
            throw new \coding_exception('Storefront section media reference was not persisted.');
        }

        return [
            'metadata' => $metadata,
            'section' => $persistedsection,
            'diagnostics' => [
                'sectionid' => $sectionid,
                'mediaitemid' => $itemid,
                'image' => $this->files->slot_diagnostic($itemid, 'image'),
                'video' => $this->files->slot_diagnostic($itemid, 'video'),
                'poster' => $this->files->slot_diagnostic($itemid, 'poster'),
                'h5p' => $this->files->slot_diagnostic($itemid, 'h5p'),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function single_section_submission(
        array $post,
        int $sourceindex,
        string $sectionid,
        int $itemid
    ): array {
        $submitted = [
            'template' => 'default',
            'commerce_position' => 'sidebar_sticky',
            'shell_mode' => 'standard',
            'show_header' => 1,
            'show_footer' => 1,
            'theme' => 'default',
            'section_id_0' => $sectionid,
            'section_visible_0' => array_key_exists('section_visible_' . $sourceindex, $post)
                ? !empty($post['section_visible_' . $sourceindex])
                : true,
            'section_content_itemid_0' => $itemid,
        ];

        foreach ($post as $name => $value) {
            if (!is_string($name) || !str_ends_with($name, '_' . $sourceindex)) {
                continue;
            }
            $basename = substr($name, 0, -strlen('_' . $sourceindex));
            if (!str_starts_with($basename, 'section_')) {
                continue;
            }
            $submitted[$basename . '_0'] = $value;
        }

        $submitted['section_id_0'] = $sectionid;
        $submitted['section_content_itemid_0'] = $itemid;
        return $submitted;
    }

    private function save_media(int $sourceindex, string $type, int $itemid): void {
        if ($type === 'image_text') {
            $field = $this->uploaded_field(
                'storefront_media_image',
                'section_image_file_' . $sourceindex
            );
            if ($field !== null) {
                $this->files->store_uploaded_slot(
                    $itemid,
                    'image',
                    $field,
                    ['png', 'jpg', 'jpeg', 'webp', 'gif']
                );
                $this->assert_uploaded_slot($itemid, 'image', $field);
            }
            return;
        }

        if ($type === 'video') {
            $videofield = $this->uploaded_field(
                'storefront_media_video',
                'section_video_file_' . $sourceindex
            );
            if ($videofield !== null) {
                $this->files->store_uploaded_slot(
                    $itemid,
                    'video',
                    $videofield,
                    ['mp4', 'webm', 'ogv']
                );
                $this->assert_uploaded_slot($itemid, 'video', $videofield);
            }

            $posterfield = $this->uploaded_field(
                'storefront_media_poster',
                'section_video_poster_' . $sourceindex
            );
            if ($posterfield !== null) {
                $this->files->store_uploaded_slot(
                    $itemid,
                    'poster',
                    $posterfield,
                    ['png', 'jpg', 'jpeg', 'webp']
                );
                $this->assert_uploaded_slot($itemid, 'poster', $posterfield);
            }
            return;
        }

        if ($type === 'h5p') {
            $field = $this->uploaded_field(
                'storefront_media_h5p',
                'section_h5p_file_' . $sourceindex
            );
            if ($field !== null) {
                $this->files->store_uploaded_slot(
                    $itemid,
                    'h5p',
                    $field,
                    ['h5p']
                );
                $this->assert_uploaded_slot($itemid, 'h5p', $field);
            }
        }
    }

    private function uploaded_field(string $stablefield, string $indexedfield): ?string {
        foreach ([$stablefield, $indexedfield] as $field) {
            if (!isset($_FILES[$field])) {
                continue;
            }
            $error = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_NO_FILE) {
                return $field;
            }
        }
        return null;
    }

    private function assert_uploaded_slot(int $itemid, string $slot, string $field): void {
        if ($this->files->get_slot_file($itemid, $slot) !== null) {
            return;
        }

        throw new \coding_exception(
            'Storefront upload was received but the permanent slot is empty: '
                . $field . ' -> ' . $slot
        );
    }

    /** @return array<string,mixed> */
    private function storefront(array $metadata): array {
        $storefront = $metadata['storefront'] ?? [];
        if (is_string($storefront)) {
            $decoded = json_decode($storefront, true);
            $storefront = is_array($decoded) ? $decoded : [];
        }
        return is_array($storefront) ? $storefront : [];
    }

    /** @return array<int,array<string,mixed>> */
    private function locale_sections(array $storefront, string $language): array {
        $sections = $storefront['locales'][$language]['sections']
            ?? ($language === 'fr' ? ($storefront['sections'] ?? []) : []);
        return array_values(array_filter(
            is_array($sections) ? $sections : [],
            'is_array'
        ));
    }

    /** @param array<int,array<string,mixed>> $sections */
    private function find_section_index(array $sections, string $sectionid): ?int {
        if ($sectionid === '') {
            return null;
        }
        foreach ($sections as $index => $section) {
            if ((string)($section['id'] ?? '') === $sectionid) {
                return $index;
            }
        }
        return null;
    }

    /** @param array<int,array<string,mixed>> $sections */
    private function find_section(array $sections, string $sectionid): ?array {
        $index = $this->find_section_index($sections, $sectionid);
        return $index === null ? null : $sections[$index];
    }

    /** @param array<int,array<string,mixed>> $sections */
    private function new_section_id(array $sections): string {
        $used = [];
        foreach ($sections as $section) {
            $id = (string)($section['id'] ?? '');
            if ($id !== '') {
                $used[$id] = true;
            }
        }
        do {
            $candidate = 'section-' . bin2hex(random_bytes(6));
        } while (isset($used[$candidate]));
        return $candidate;
    }

    private function normalise_language(string $language): string {
        $language = strtolower(trim(str_replace('-', '_', $language)));
        return explode('_', $language)[0] ?: 'fr';
    }
}
