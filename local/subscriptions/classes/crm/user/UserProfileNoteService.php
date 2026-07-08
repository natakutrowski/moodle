<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use core_text;
use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\automation\AutomationDispatcher;
use local_subscriptions\crm\automation\AutomationTriggerKeys;

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

    public function add(int $userid, int $authorid, string $note, string $type = 'general', bool $dispatchautomation = true): int {
        $note = trim($note);

        if ($note === '') {
            throw new \moodle_exception('crm_note_empty', 'local_subscriptions');
        }

        if (core_text::strlen($note) > 5000) {
            throw new \moodle_exception('crm_note_too_long', 'local_subscriptions');
        }

        $noteid = $this->repository->add_note($userid, $authorid, $note, $type);

        if ($dispatchautomation) {
            (new AutomationDispatcher())->dispatch(
                AutomationContext::for_user_action(
                    AutomationTriggerKeys::NOTE_ADDED,
                    $userid,
                    $authorid,
                    [
                        'noteid' => $noteid,
                        'type' => $type,
                        'source' => 'crm_note_service',
                    ]
                )
            );
        }

        return $noteid;
    }
}