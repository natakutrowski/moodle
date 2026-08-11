<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Fail-closed integrity checks executed before public publication.
 */
final class CommerceShowroomPublicationIntegrityValidator {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceShowroomCmsRepository $repository,
        private readonly \context_system $context
    ) {
    }

    public function validate(int $showroomid): void {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        CommerceShowroomRenderTemplateRegistry::normalise(
            (string)$showroom->template
        );

        foreach (['productsjson', 'settingsjson'] as $field) {
            $decoded = json_decode(
                (string)$showroom->{$field},
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($decoded)) {
                $this->fail('invalid ' . $field);
            }
        }

        $blocks = $this->repository->blocks($showroomid);
        $hasenabled = false;
        $keys = [];

        foreach ($blocks as $block) {
            $blockid = (int)$block->id;
            $blockkey = (string)$block->blockkey;
            $blocktype = (string)$block->blocktype;

            if (
                $blockkey === ''
                || isset($keys[$blockkey])
                || !CommerceShowroomBlockTypeRegistry::exists($blocktype)
            ) {
                $this->fail('invalid or duplicate block key/type');
            }
            $keys[$blockkey] = true;

            $config = json_decode(
                (string)$block->configjson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($config)) {
                $this->fail('invalid config for block ' . $blockkey);
            }

            if ((int)$block->enabled !== 1) {
                continue;
            }

            $hasenabled = true;
            $this->validate_required_fields(
                $blocktype,
                $blockkey,
                $config
            );
            $this->validate_internal_media_urls(
                $blockid,
                $blockkey,
                $config
            );
        }

        if (!$hasenabled) {
            $this->fail('no enabled block');
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    private function validate_required_fields(
        string $blocktype,
        string $blockkey,
        array $config
    ): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema($blocktype);

        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (empty($field['required'])) {
                continue;
            }

            $name = (string)($field['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $value = $config[$name] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '' || $value === []) {
                $this->fail(
                    'required field "' . $name
                    . '" is empty in block "' . $blockkey . '"'
                );
            }
        }
    }

    /**
     * Validate only internal Moodle File API references.
     *
     * @param array<string,mixed> $config
     */
    private function validate_internal_media_urls(
        int $blockid,
        string $blockkey,
        array $config
    ): void {
        $urls = [];
        array_walk_recursive(
            $config,
            static function (mixed $value) use (&$urls): void {
                if (
                    is_string($value)
                    && str_contains(
                        $value,
                        '/local_subscriptions/showroom_block_media/'
                    )
                ) {
                    $urls[] = $value;
                }
            }
        );

        foreach (array_unique($urls) as $url) {
            if (
                !preg_match(
                    '#/local_subscriptions/showroom_block_media/'
                    . '(\d+)/(.+)$#',
                    $url,
                    $matches
                )
            ) {
                $this->fail(
                    'malformed internal media URL in block "' . $blockkey . '"'
                );
            }

            $itemid = (int)$matches[1];
            if ($itemid !== $blockid) {
                $this->fail(
                    'stale media itemid in block "' . $blockkey . '"'
                );
            }

            $relative = rawurldecode((string)$matches[2]);
            $lastslash = strrpos($relative, '/');
            if ($lastslash === false) {
                $this->fail(
                    'malformed media path in block "' . $blockkey . '"'
                );
            }

            $filepath = '/' . substr($relative, 0, $lastslash + 1);
            $filename = substr($relative, $lastslash + 1);

            $file = get_file_storage()->get_file(
                $this->context->id,
                CommerceShowroomBlockMediaManager::COMPONENT,
                CommerceShowroomBlockMediaManager::FILEAREA,
                $blockid,
                $filepath,
                $filename
            );

            if ($file === false || $file === null || $file->is_directory()) {
                $this->fail(
                    'missing media file in block "' . $blockkey . '"'
                );
            }
        }
    }

    private function fail(string $detail): never {
        throw new \moodle_exception(
            'commerce_showroom_publish_integrity_failed',
            'local_subscriptions',
            '',
            $detail
        );
    }
}
