<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Draft, review, publication and immutable revision workflow for showrooms. */
final class CommerceShowroomPublicationService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceShowroomCmsRepository $repository
    ) {
    }

    /** @return array<int,\stdClass> */
    public function revisions(int $showroomid): array {
        return array_values($this->db->get_records(
            'local_subs_showroom_rev',
            ['showroomid' => $showroomid],
            'revisionno DESC'
        ));
    }

    public function submit_for_review(
        int $showroomid,
        int $userid,
        string $note = ''
    ): int {
        return $this->transition(
            $showroomid,
            CommerceShowroomStatus::REVIEW,
            'submit_review',
            $userid,
            $note,
            function () use ($showroomid): void {
                $this->require_status(
                    $showroomid,
                    CommerceShowroomStatus::DRAFT
                );
            }
        );
    }

    public function publish(
        int $showroomid,
        int $userid,
        string $note = ''
    ): int {
        return $this->transition(
            $showroomid,
            CommerceShowroomStatus::PUBLISHED,
            'publish',
            $userid,
            $note,
            function () use ($showroomid): void {
                $this->require_status(
                    $showroomid,
                    CommerceShowroomStatus::REVIEW
                );
                $this->require_publishable($showroomid);

                (new CommerceShowroomPublicationIntegrityValidator(
                    $this->db,
                    $this->repository,
                    \context_system::instance()
                ))->validate($showroomid);
            }
        );
    }

    public function return_to_draft(
        int $showroomid,
        int $userid,
        string $note = ''
    ): int {
        return $this->transition(
            $showroomid,
            CommerceShowroomStatus::DRAFT,
            'return_draft',
            $userid,
            $note,
            function () use ($showroomid): void {
                $showroom = $this->repository->get($showroomid);
                if (
                    $showroom === null
                    || !in_array(
                        (string)$showroom->status,
                        [
                            CommerceShowroomStatus::REVIEW,
                            CommerceShowroomStatus::PUBLISHED,
                        ],
                        true
                    )
                ) {
                    throw new \moodle_exception(
                        'commerce_showroom_invalid_transition',
                        'local_subscriptions'
                    );
                }
            }
        );
    }

    public function restore(
        int $showroomid,
        int $revisionid,
        int $userid
    ): void {
        $revision = $this->db->get_record(
            'local_subs_showroom_rev',
            ['id' => $revisionid, 'showroomid' => $showroomid],
            '*',
            MUST_EXIST
        );

        $snapshot = json_decode(
            (string)$revision->snapshotjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        if (
            !is_array($snapshot)
            || !isset($snapshot['showroom'], $snapshot['blocks'])
        ) {
            throw new \coding_exception(
                'Invalid showroom revision snapshot.'
            );
        }

        $transaction = $this->db->start_delegated_transaction();
        $obsoleteblockids = [];

        try {
            $showroom = (array)$snapshot['showroom'];
            $showroom['id'] = $showroomid;
            $showroom['status'] = CommerceShowroomStatus::DRAFT;
            $this->repository->save($showroom, $userid);

            $currentbykey = [];
            foreach ($this->repository->blocks($showroomid) as $current) {
                $currentbykey[(string)$current->blockkey] = $current;
            }

            $keptids = [];
            foreach ((array)$snapshot['blocks'] as $snapshotblock) {
                $data = (array)$snapshotblock;
                $key = (string)($data['blockkey'] ?? '');

                unset(
                    $data['showroomid'],
                    $data['timecreated'],
                    $data['timemodified'],
                    $data['usermodified']
                );

                if ($key !== '' && isset($currentbykey[$key])) {
                    // Keep the current ID so File API itemids and media URLs
                    // remain valid when the same logical block is restored.
                    $data['id'] = (int)$currentbykey[$key]->id;
                } else {
                    unset($data['id']);
                }

                $targetid = $this->repository->save_block(
                    $showroomid,
                    $data,
                    $userid
                );
                $keptids[$targetid] = true;
            }

            foreach ($currentbykey as $current) {
                if (isset($keptids[(int)$current->id])) {
                    continue;
                }

                // DB deletion is transactional. Media cleanup is deliberately
                // postponed until after commit, so rollback cannot destroy the
                // currently-live state.
                $this->db->delete_records(
                    'local_subs_showroom_block',
                    [
                        'id' => (int)$current->id,
                        'showroomid' => $showroomid,
                    ]
                );
                $obsoleteblockids[] = (int)$current->id;
            }

            $this->create_revision(
                $showroomid,
                'restore',
                $userid,
                'Restored from revision ' . (int)$revision->revisionno
            );

            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }

        // Best-effort post-commit cleanup. A cleanup failure can leave orphaned
        // files, but cannot corrupt the restored DB state.
        $media = new CommerceShowroomBlockMediaManager(
            \context_system::instance()
        );
        foreach ($obsoleteblockids as $blockid) {
            $media->delete_block($blockid);
        }
    }

    private function require_status(
        int $showroomid,
        string $expected
    ): void {
        $showroom = $this->repository->get($showroomid);
        if (
            $showroom === null
            || (string)$showroom->status !== $expected
        ) {
            throw new \moodle_exception(
                'commerce_showroom_invalid_transition',
                'local_subscriptions'
            );
        }
    }

    private function require_publishable(int $showroomid): void {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        $hasenabledblock = false;
        foreach ($this->repository->blocks($showroomid) as $block) {
            if ((int)$block->enabled === 1) {
                $hasenabledblock = true;
                break;
            }
        }

        if (!$hasenabledblock) {
            throw new \moodle_exception(
                'commerce_showroom_publish_requires_block',
                'local_subscriptions'
            );
        }

        $hasslug = false;
        foreach (['slugfr', 'slugen', 'slugru'] as $field) {
            if (trim((string)($showroom->{$field} ?? '')) !== '') {
                $hasslug = true;
                break;
            }
        }

        if (!$hasslug) {
            throw new \moodle_exception(
                'commerce_showroom_publish_requires_slug',
                'local_subscriptions'
            );
        }

        (new CommerceShowroomSlugService($this->db))
            ->assert_publishable_slugs($showroom);
    }

    private function transition(
        int $showroomid,
        string $status,
        string $action,
        int $userid,
        string $note,
        ?\Closure $precondition = null
    ): int {
        $transaction = $this->db->start_delegated_transaction();

        if ($precondition !== null) {
            $precondition();
        }

        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        $revisionid = $this->create_revision(
            $showroomid,
            $action,
            $userid,
            $note
        );

        $this->db->update_record('local_subs_showroom', (object)[
            'id' => $showroomid,
            'status' => $status,
            'timemodified' => time(),
            'usermodified' => $userid,
        ]);

        $transaction->allow_commit();

        return $revisionid;
    }

    private function create_revision(
        int $showroomid,
        string $action,
        int $userid,
        string $note
    ): int {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }

        $max = $this->db->get_field_sql(
            'SELECT MAX(revisionno) FROM {local_subs_showroom_rev} '
                . 'WHERE showroomid = :showroomid',
            ['showroomid' => $showroomid]
        );

        $snapshot = [
            'showroom' => (array)$showroom,
            'blocks' => array_map(
                static fn(\stdClass $block): array => (array)$block,
                $this->repository->blocks($showroomid)
            ),
        ];

        return (int)$this->db->insert_record(
            'local_subs_showroom_rev',
            (object)[
                'showroomid' => $showroomid,
                'revisionno' => ((int)$max) + 1,
                'action' => $action,
                'note' => trim($note),
                'snapshotjson' => json_encode(
                    $snapshot,
                    JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                        | JSON_THROW_ON_ERROR
                ),
                'timecreated' => time(),
                'usercreated' => $userid,
            ]
        );
    }
}
