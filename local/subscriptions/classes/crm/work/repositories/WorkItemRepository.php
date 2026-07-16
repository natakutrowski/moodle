<?php

namespace local_subscriptions\crm\work\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\domain\WorkItemStatus;

final class WorkItemRepository {

    private const ITEM_TABLE =
        'local_subscriptions_work_item';

    private const COMMENT_TABLE =
        'local_subscriptions_work_comment';

    private const LINK_TABLE =
        'local_subscriptions_work_link';

    private const HISTORY_TABLE =
        'local_subscriptions_work_history';

    public function create(
        CreateWorkItemRequest $request
    ): \stdClass {
        global $DB;

        $now = time();

        $record = (object)[
            'reference' => 'PENDING-' . bin2hex(random_bytes(8)),
            'type' => $request->type,
            'title' => trim($request->title),
            'description' => trim($request->description),
            'status' => WorkItemStatus::OPEN,
            'priority' => $request->priority,
            'source' => $request->source,
            'targetuserid' => $request->targetuserid,
            'assigneduserid' => $request->assigneduserid,
            'assignedteamid' => $request->assignedteamid,
            'parentid' => $request->parentid,
            'createdby' => $request->createdby,
            'dueat' => $request->dueat,
            'resolvedat' => null,
            'closedat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = $DB->insert_record(
            self::ITEM_TABLE,
            $record
        );

        $record->reference = sprintf(
            'WORK-%06d',
            (int)$record->id
        );

        $DB->set_field(
            self::ITEM_TABLE,
            'reference',
            $record->reference,
            ['id' => $record->id]
        );

        return $record;
    }

    public function get(int $itemid): \stdClass {
        global $DB;

        return $DB->get_record(
            self::ITEM_TABLE,
            ['id' => $itemid],
            '*',
            MUST_EXIST
        );
    }

    public function update_fields(
        int $itemid,
        array $fields
    ): \stdClass {
        global $DB;

        $allowed = [
            'title',
            'description',
            'status',
            'priority',
            'targetuserid',
            'assigneduserid',
            'assignedteamid',
            'parentid',
            'dueat',
            'resolvedat',
            'closedat',
        ];

        $record = (object)[
            'id' => $itemid,
            'timemodified' => time(),
        ];

        foreach ($fields as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                throw new \InvalidArgumentException(
                    'Unsupported work item field: ' . $field
                );
            }

            $record->{$field} = $value;
        }

        $DB->update_record(
            self::ITEM_TABLE,
            $record
        );

        return $this->get($itemid);
    }

    public function add_comment(
        int $itemid,
        int $authorid,
        string $body
    ): int {
        global $DB;

        $body = trim($body);

        if ($body === '') {
            throw new \InvalidArgumentException(
                'Work item comment cannot be empty.'
            );
        }

        if (\core_text::strlen($body) > 10000) {
            throw new \InvalidArgumentException(
                'Work item comment is too long.'
            );
        }

        $now = time();

        return (int)$DB->insert_record(
            self::COMMENT_TABLE,
            (object)[
                'itemid' => $itemid,
                'authorid' => $authorid,
                'body' => $body,
                'visibility' => 'internal',
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function add_link(
        int $itemid,
        string $objecttype,
        int $objectid,
        string $relation
    ): int {
        global $DB;

        $existing = $DB->get_record(
            self::LINK_TABLE,
            [
                'itemid' => $itemid,
                'objecttype' => $objecttype,
                'objectid' => $objectid,
                'relation' => $relation,
            ],
            'id',
            IGNORE_MISSING
        );

        if ($existing) {
            return (int)$existing->id;
        }

        return (int)$DB->insert_record(
            self::LINK_TABLE,
            (object)[
                'itemid' => $itemid,
                'objecttype' => $objecttype,
                'objectid' => $objectid,
                'relation' => $relation,
                'timecreated' => time(),
            ]
        );
    }

    public function add_history(
        int $itemid,
        ?int $actorid,
        string $action,
        mixed $oldvalue = null,
        mixed $newvalue = null,
        array $metadata = []
    ): void {
        global $DB;

        $DB->insert_record(
            self::HISTORY_TABLE,
            (object)[
                'itemid' => $itemid,
                'actorid' => $actorid,
                'action' => $action,
                'oldvalue' => $this->encode_value($oldvalue),
                'newvalue' => $this->encode_value($newvalue),
                'metadatajson' => $metadata !== []
                    ? json_encode(
                        $metadata,
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    )
                    : null,
                'timecreated' => time(),
            ]
        );
    }

    public function get_comments(int $itemid): array {
        global $DB;

        return array_values($DB->get_records_sql(
            "SELECT c.*, u.firstname, u.lastname,
                    u.firstnamephonetic, u.lastnamephonetic,
                    u.middlename, u.alternatename
            FROM {local_subscriptions_work_comment} c
            JOIN {user} u ON u.id = c.authorid
            WHERE c.itemid = :itemid
        ORDER BY c.timecreated ASC, c.id ASC",
            ['itemid' => $itemid]
        ));
    }

    public function get_links(int $itemid): array {
        global $DB;

        return array_values($DB->get_records(
            self::LINK_TABLE,
            ['itemid' => $itemid],
            'timecreated ASC, id ASC'
        ));
    }

    public function get_history(int $itemid): array {
        global $DB;

        return array_values($DB->get_records_sql(
            "SELECT h.*, u.firstname, u.lastname,
                    u.firstnamephonetic, u.lastnamephonetic,
                    u.middlename, u.alternatename
            FROM {local_subscriptions_work_history} h
        LEFT JOIN {user} u ON u.id = h.actorid
            WHERE h.itemid = :itemid
        ORDER BY h.timecreated DESC, h.id DESC",
            ['itemid' => $itemid]
        ));
    }

    public function get_children(int $itemid): array {
        global $DB;

        return array_values($DB->get_records(
            self::ITEM_TABLE,
            ['parentid' => $itemid],
            'timecreated ASC, id ASC'
        ));
    }

    private function encode_value(mixed $value): ?string {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }

    public function user_exists(
        int $userid
    ): bool {
        global $DB;

        return $userid > 0 &&
            $DB->record_exists(
                'user',
                [
                    'id' => $userid,
                    'deleted' => 0,
                    'suspended' => 0,
                ]
            );
    }

    public function enabled_team_exists(
        int $teamid
    ): bool {
        global $DB;

        return $teamid > 0 &&
            $DB->record_exists(
                'local_subscriptions_work_team',
                [
                    'id' => $teamid,
                    'enabled' => 1,
                ]
            );
    }

}