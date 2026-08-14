<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Persistence gateway for editable showroom definitions and blocks. */
final class CommerceShowroomCmsRepository {
    public function __construct(private readonly \moodle_database $db) {
    }

    /** @return array<int,\stdClass> */
    public function all(): array {
        return array_values($this->db->get_records('local_subs_showroom', null, 'name ASC'));
    }

    public function get(int $id): ?\stdClass {
        $record = $this->db->get_record('local_subs_showroom', ['id' => $id]);
        return $record ?: null;
    }

    public function get_by_key(string $key): ?\stdClass {
        $record = $this->db->get_record('local_subs_showroom', ['showroomkey' => $key]);
        return $record ?: null;
    }

    /** @return array<int,\stdClass> */
    public function blocks(int $showroomid): array {
        return array_values($this->db->get_records(
            'local_subs_showroom_block',
            ['showroomid' => $showroomid],
            'sortorder ASC, id ASC'
        ));
    }

    public function get_block(int $id): ?\stdClass {
        $record = $this->db->get_record('local_subs_showroom_block', ['id' => $id]);
        return $record ?: null;
    }

    /** @param array<string,mixed> $data */
    public function save(array $data, int $userid): int {
        $now = time();
        $record = (object)[
            'showroomkey' => clean_param((string)$data['showroomkey'], PARAM_ALPHANUMEXT),
            'status' => in_array(($data['status'] ?? 'draft'), ['draft', 'review', 'published', 'archived'], true)
                ? (string)$data['status'] : 'draft',
            'name' => trim((string)$data['name']),
            'template' => CommerceShowroomRenderTemplateRegistry::normalise(
                (string)($data['template'] ?? 'local_subscriptions/showroom/third_group_verbs')
            ),
            'slugfr' => trim((string)($data['slugfr'] ?? '')),
            'slugen' => trim((string)($data['slugen'] ?? '')),
            'slugru' => trim((string)($data['slugru'] ?? '')),
            'titlekey' => trim((string)($data['titlekey'] ?? '')),
            'descriptionkey' => trim((string)($data['descriptionkey'] ?? '')),
            'productsjson' => self::normalise_json((string)($data['productsjson'] ?? '{}')),
            'settingsjson' => self::normalise_json((string)($data['settingsjson'] ?? '{}')),
            'timemodified' => $now,
            'usermodified' => $userid,
        ];

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            $record->id = $id;
            $this->db->update_record('local_subs_showroom', $record);
            return $id;
        }

        $record->timecreated = $now;
        return (int)$this->db->insert_record('local_subs_showroom', $record);
    }

    /** @param array<string,mixed> $data */
    public function save_block(int $showroomid, array $data, int $userid): int {
        $now = time();
        $blocktype = clean_param((string)$data['blocktype'], PARAM_ALPHANUMEXT);
        if (!CommerceShowroomBlockTypeRegistry::exists($blocktype)) {
            throw new \invalid_parameter_exception('Unsupported showroom block type.');
        }

        $blockkey = clean_param((string)($data['blockkey'] ?? ''), PARAM_ALPHANUMEXT);
        if ($blockkey === '') {
            $blockkey = $this->unique_block_key($showroomid, $blocktype);
        }

        $record = (object)[
            'showroomid' => $showroomid,
            'blockkey' => $blockkey,
            'blocktype' => $blocktype,
            'sortorder' => max(0, (int)($data['sortorder'] ?? $this->next_sortorder($showroomid))),
            'enabled' => empty($data['enabled']) ? 0 : 1,
            'configjson' => self::normalise_json((string)($data['configjson'] ?? '{}')),
            'timemodified' => $now,
            'usermodified' => $userid,
        ];
        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            $existing = $this->get_block($id);
            if ($existing === null || (int)$existing->showroomid !== $showroomid) {
                throw new \invalid_parameter_exception('Invalid showroom block.');
            }
            $record->id = $id;
            $this->db->update_record('local_subs_showroom_block', $record);
            return $id;
        }
        $record->timecreated = $now;
        return (int)$this->db->insert_record('local_subs_showroom_block', $record);
    }

    /** @param int[] $blockids */
    public function reorder_blocks(int $showroomid, array $blockids, int $userid): void {
        $existing = $this->blocks($showroomid);
        $existingids = array_map(static fn(\stdClass $block): int => (int)$block->id, $existing);
        $normalised = array_values(array_unique(array_map('intval', $blockids)));
        sort($existingids);
        $comparison = $normalised;
        sort($comparison);
        if ($comparison !== $existingids) {
            throw new \invalid_parameter_exception('The block order is incomplete or contains foreign blocks.');
        }
        $now = time();
        foreach ($normalised as $index => $blockid) {
            $this->db->set_field('local_subs_showroom_block', 'sortorder', ($index + 1) * 10, [
                'id' => $blockid,
                'showroomid' => $showroomid,
            ]);
            $this->db->set_field('local_subs_showroom_block', 'timemodified', $now, ['id' => $blockid]);
            $this->db->set_field('local_subs_showroom_block', 'usermodified', $userid, ['id' => $blockid]);
        }
    }

    public function set_block_enabled(int $showroomid, int $blockid, bool $enabled, int $userid): void {
        $block = $this->get_block($blockid);
        if ($block === null || (int)$block->showroomid !== $showroomid) {
            throw new \invalid_parameter_exception('Invalid showroom block.');
        }
        $this->db->update_record('local_subs_showroom_block', (object)[
            'id' => $blockid,
            'enabled' => $enabled ? 1 : 0,
            'timemodified' => time(),
            'usermodified' => $userid,
        ]);
    }

    public function duplicate_block(int $showroomid, int $blockid, int $userid): int {
        $block = $this->get_block($blockid);
        if ($block === null || (int)$block->showroomid !== $showroomid) {
            throw new \invalid_parameter_exception('Invalid showroom block.');
        }
        return $this->save_block($showroomid, [
            'blocktype' => $block->blocktype,
            'blockkey' => '',
            'sortorder' => $this->next_sortorder($showroomid),
            'enabled' => (int)$block->enabled === 1,
            'configjson' => $block->configjson,
        ], $userid);
    }

    public function delete_block(int $showroomid, int $blockid): void {
        $block = $this->get_block($blockid);
        if ($block === null || (int)$block->showroomid !== $showroomid) {
            throw new \invalid_parameter_exception('Invalid showroom block.');
        }

        $this->delete_block_media($blockid);
        $this->db->delete_records(
            'local_subs_showroom_block',
            ['id' => $blockid]
        );
    }

    public function delete(int $id): void {
        if ($this->get($id) === null) {
            return;
        }

        $transaction = $this->db->start_delegated_transaction();

        (new CommerceShowroomSocialImageService(
            \context_system::instance()
        ))->delete($id);

        foreach ($this->blocks($id) as $block) {
            $this->delete_block_media((int)$block->id);
        }

        // Explicitly remove revisions before the parent row. This does not
        // depend on database-specific FK cascade behaviour.
        $this->db->delete_records(
            'local_subs_showroom_rev',
            ['showroomid' => $id]
        );
        $this->db->delete_records(
            'local_subs_showroom_block',
            ['showroomid' => $id]
        );
        $this->db->delete_records(
            'local_subs_showroom',
            ['id' => $id]
        );

        $transaction->allow_commit();
    }

    /**
     * Removes every block and its Moodle File API media.
     *
     * Used when a template/restore replaces the complete block set so old
     * block itemids cannot leave orphaned files in moodledata.
     */
    public function delete_all_blocks(int $showroomid): void {
        foreach ($this->blocks($showroomid) as $block) {
            $this->delete_block_media((int)$block->id);
        }

        $this->db->delete_records(
            'local_subs_showroom_block',
            ['showroomid' => $showroomid]
        );
    }

    public function apply_template(int $showroomid, string $templatekey, int $userid): void {
        $template = CommerceShowroomPageTemplateRegistry::get($templatekey);
        $this->delete_all_blocks($showroomid);
        foreach ($template['blocks'] as $index => $type) {
            $this->save_block($showroomid, [
                'blocktype' => $type,
                'sortorder' => ($index + 1) * 10,
                'enabled' => true,
                'configjson' => '{}',
            ], $userid);
        }
    }

    /**
     * Initialises empty block configurations from the canonical defaults.
     *
     * Existing customised blocks are never overwritten unless explicitly
     * requested by a future migration tool.
     *
     * @return int Number of blocks initialised.
     */
    public function initialise_block_defaults(int $showroomid, int $userid): int {
        $showroom = $this->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        $updated = 0;
        foreach ($this->blocks($showroomid) as $block) {
            $current = json_decode((string)$block->configjson, true);
            if (is_array($current) && $current !== []) {
                continue;
            }

            $defaults = CommerceShowroomBlockDefaultsCatalog::for_block(
                (string)$showroom->showroomkey,
                (string)$block->blocktype
            );
            if ($defaults === []) {
                continue;
            }

            $this->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => (int)$block->enabled === 1,
                'configjson' => json_encode(
                    $defaults,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
            $updated++;
        }

        return $updated;
    }

    public function duplicate_showroom(int $showroomid, int $userid): int {
        $showroom = $this->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }
        $data = (array)$showroom;
        unset($data['id'], $data['timecreated'], $data['timemodified'], $data['usermodified']);
        $base = clean_param((string)$showroom->showroomkey, PARAM_ALPHANUMEXT) . '-copy';
        $candidate = $base;
        $counter = 2;
        while ($this->get_by_key($candidate) !== null) {
            $candidate = $base . '-' . $counter++;
        }
        $data['showroomkey'] = $candidate;
        $data['name'] = (string)$showroom->name . ' (copie)';
        $data['status'] = 'draft';
        $data['slugfr'] = $data['slugen'] = $data['slugru'] = '';
        $newid = $this->save($data, $userid);
        foreach ($this->blocks($showroomid) as $block) {
            $this->save_block($newid, [
                'blocktype' => $block->blocktype,
                'sortorder' => $block->sortorder,
                'enabled' => $block->enabled,
                'configjson' => $block->configjson,
            ], $userid);
        }
        (new CommerceShowroomSocialImageService(
            \context_system::instance()
        ))->duplicate($showroomid, $newid);
        return $newid;
    }

    private function next_sortorder(int $showroomid): int {
        $max = $this->db->get_field_sql(
            'SELECT MAX(sortorder) FROM {local_subs_showroom_block} WHERE showroomid = :showroomid',
            ['showroomid' => $showroomid]
        );
        return ((int)$max) + 10;
    }

    private function delete_block_media(int $blockid): void {
        (new CommerceShowroomBlockMediaManager(
            \context_system::instance()
        ))->delete_block($blockid);
    }

    private function unique_block_key(int $showroomid, string $type): string {
        $base = clean_param($type, PARAM_ALPHANUMEXT) ?: 'block';
        $candidate = $base;
        $counter = 2;
        while ($this->db->record_exists('local_subs_showroom_block', [
            'showroomid' => $showroomid,
            'blockkey' => $candidate,
        ])) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }
        return $candidate;
    }

    private static function normalise_json(string $json): string {
        $json = trim($json);
        if ($json === '') {
            return '{}';
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('Invalid JSON configuration.');
        }
        return (string)json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
