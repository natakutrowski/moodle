<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxParticipantData;

final class InboxParticipantRepository {

    private const TABLE =
        'local_subscriptions_inbox_participant';

    public function create(
        int $messageid,
        ?int $contactid,
        InboxParticipantData $participant
    ): int {
        global $DB;

        $existing = $DB->get_record(
            self::TABLE,
            [
                'messageid' => $messageid,
                'participanttype' => $participant->type,
                'normalizedemail' =>
                    $participant->normalizedemail,
            ]
        );

        if ($existing) {
            return (int)$existing->id;
        }

        return (int)$DB->insert_record(
            self::TABLE,
            (object)[
                'messageid' => $messageid,
                'contactid' => $contactid,
                'participanttype' => $participant->type,
                'email' => $participant->email,
                'normalizedemail' =>
                    $participant->normalizedemail,
                'displayname' => $participant->displayname,
            ]
        );
    }
}