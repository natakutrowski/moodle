<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Read-only projection of account merge audit records for CRM User360.
 */
final class CommerceCustomerMergeHistoryService {
    public function __construct(private readonly moodle_database $database) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function for_user(int $userid): array {
        $sql = "SELECT DISTINCT m.*
                  FROM {local_subs_identity_merge} m
             LEFT JOIN {local_subs_identity_merge_source} s ON s.mergeid = m.id
                 WHERE m.targetuserid = :targetuserid
                    OR s.sourceuserid = :sourceuserid
              ORDER BY m.timecreated DESC, m.id DESC";

        $rows = $this->database->get_records_sql($sql, [
            'targetuserid' => $userid,
            'sourceuserid' => $userid,
        ]);

        $history = [];
        foreach ($rows as $row) {
            $sources = $this->database->get_records(
                'local_subs_identity_merge_source',
                ['mergeid' => (int)$row->id],
                'id ASC'
            );
            $sourceitems = [];
            foreach ($sources as $source) {
                $user = $this->database->get_record(
                    'user',
                    ['id' => (int)$source->sourceuserid],
                    'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email,suspended,deleted',
                    IGNORE_MISSING
                );
                $sourceitems[] = [
                    'userid' => (int)$source->sourceuserid,
                    'email' => (string)($source->sourceemail ?? ($user->email ?? '')),
                    'name' => $user ? fullname($user) : '',
                    'wassuspended' => (bool)$source->wassuspended,
                    'iscurrent' => (int)$source->sourceuserid === $userid,
                ];
            }

            $target = $this->database->get_record(
                'user',
                ['id' => (int)$row->targetuserid],
                'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email,suspended,deleted',
                IGNORE_MISSING
            );
            $actor = $this->database->get_record(
                'user',
                ['id' => (int)$row->performedby],
                'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email',
                IGNORE_MISSING
            );

            $result = json_decode((string)$row->resultjson, true);
            if (!is_array($result)) {
                $result = [];
            }
            $transfers = is_array($result['transfers'] ?? null) ? $result['transfers'] : [];
            $certification = is_array($result['certification'] ?? null) ? $result['certification'] : [];
            $summary = is_array($certification['summary'] ?? null) ? $certification['summary'] : [];

            $history[] = [
                'id' => (int)$row->id,
                'mergeuuid' => (string)$row->mergeuuid,
                'status' => (string)$row->status,
                'timecreated' => (int)$row->timecreated,
                'performedby' => (int)$row->performedby,
                'actorname' => $actor ? fullname($actor) : '',
                'targetuserid' => (int)$row->targetuserid,
                'targetname' => $target ? fullname($target) : '',
                'targetemail' => $target ? (string)$target->email : '',
                'isretained' => (int)$row->targetuserid === $userid,
                'sources' => $sourceitems,
                'transfers' => $transfers,
                'learningdecisions' => is_array($result['learningresolutions'] ?? null)
                    ? $result['learningresolutions']
                    : [],
                'certification' => $certification,
                'certified' => !empty($certification['passed']),
                'certificationchecks' => (int)($summary['passed'] ?? 0),
                'manualdecisions' => (int)($summary['manualdecisions'] ?? 0),
                'preferredidentityuserid' => isset($result['preferredidentityuserid']) ? (int)$result['preferredidentityuserid'] : null,
                'preferredpassworduserid' => isset($result['preferredpassworduserid']) ? (int)$result['preferredpassworduserid'] : null,
                'identitytransfer' => is_array($result['identitytransfer'] ?? null) ? $result['identitytransfer'] : null,
            ];
        }

        return $history;
    }
}
