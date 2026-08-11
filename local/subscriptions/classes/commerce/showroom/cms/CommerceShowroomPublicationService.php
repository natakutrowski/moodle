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

    public function submit_for_review(int $showroomid, int $userid, string $note = ''): int {
        return $this->transition($showroomid, 'review', 'submit_review', $userid, $note);
    }

    public function publish(int $showroomid, int $userid, string $note = ''): int {
        return $this->transition($showroomid, 'published', 'publish', $userid, $note);
    }

    public function return_to_draft(int $showroomid, int $userid, string $note = ''): int {
        return $this->transition($showroomid, 'draft', 'return_draft', $userid, $note);
    }

    public function restore(int $showroomid, int $revisionid, int $userid): void {
        $revision = $this->db->get_record('local_subs_showroom_rev', [
            'id' => $revisionid,
            'showroomid' => $showroomid,
        ], '*', MUST_EXIST);
        $snapshot = json_decode((string)$revision->snapshotjson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($snapshot) || !isset($snapshot['showroom'], $snapshot['blocks'])) {
            throw new \coding_exception('Invalid showroom revision snapshot.');
        }

        $transaction = $this->db->start_delegated_transaction();
        $showroom = (array)$snapshot['showroom'];
        $showroom['id'] = $showroomid;
        $showroom['status'] = 'draft';
        $this->repository->save($showroom, $userid);
        $this->db->delete_records('local_subs_showroom_block', ['showroomid' => $showroomid]);
        foreach ((array)$snapshot['blocks'] as $block) {
            $data = (array)$block;
            unset($data['id'], $data['showroomid'], $data['timecreated'], $data['timemodified'], $data['usermodified']);
            $this->repository->save_block($showroomid, $data, $userid);
        }
        $this->create_revision($showroomid, 'restore', $userid, 'Restored from revision ' . (int)$revision->revisionno);
        $transaction->allow_commit();
    }

    private function transition(int $showroomid, string $status, string $action, int $userid, string $note): int {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }
        $transaction = $this->db->start_delegated_transaction();
        $revisionid = $this->create_revision($showroomid, $action, $userid, $note);
        $this->db->update_record('local_subs_showroom', (object)[
            'id' => $showroomid,
            'status' => $status,
            'timemodified' => time(),
            'usermodified' => $userid,
        ]);
        $transaction->allow_commit();
        return $revisionid;
    }

    private function create_revision(int $showroomid, string $action, int $userid, string $note): int {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }
        $max = $this->db->get_field_sql(
            'SELECT MAX(revisionno) FROM {local_subs_showroom_rev} WHERE showroomid = :showroomid',
            ['showroomid' => $showroomid]
        );
        $snapshot = [
            'showroom' => (array)$showroom,
            'blocks' => array_map(static fn(\stdClass $block): array => (array)$block, $this->repository->blocks($showroomid)),
        ];
        return (int)$this->db->insert_record('local_subs_showroom_rev', (object)[
            'showroomid' => $showroomid,
            'revisionno' => ((int)$max) + 1,
            'action' => $action,
            'note' => trim($note),
            'snapshotjson' => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'timecreated' => time(),
            'usercreated' => $userid,
        ]);
    }
}
