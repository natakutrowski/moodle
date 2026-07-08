<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use core_text;

final class UserProfileNoteService {

    public function __construct(
        private readonly UserProfileRepository $repository
    ) {
    }

    public function get_for_profile(int $userid, int $limit = 20): array {
        return array_map(
            static fn(\stdClass $record): \stdClass => UserProfileNote::from_record($record)->to_object(),
            $this->repository->get_notes_for_profile($userid, $limit)
        );
    }

    public function add(int $userid, int $authorid, string $note, string $type = 'general'): int {
        $note = trim($note);

        if ($note === '') {
            throw new \moodle_exception('crm_note_empty', 'local_subscriptions');
        }

        if (core_text::strlen($note) > 5000) {
            throw new \moodle_exception('crm_note_too_long', 'local_subscriptions');
        }

        return $this->repository->add_note($userid, $authorid, $note, $type);
    }
}