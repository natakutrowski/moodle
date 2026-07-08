<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationHistoryRepository {

    private const TABLE = 'local_subscriptions_automation_history';

    public function record(
        AutomationRule $rule,
        AutomationContext $context,
        string $status,
        string $message = '',
        array $result = []
    ): int {
        global $DB;

        return (int)$DB->insert_record(self::TABLE, (object)[
            'ruleid' => $rule->id,
            'rulekey' => $rule->key,
            'triggerkey' => $context->triggerkey,
            'userid' => $context->userid ?: null,
            'entitytype' => $context->entitytype ?: null,
            'entityid' => $context->entityid ?: null,
            'status' => $status,
            'message' => $message,
            'contextjson' => $this->encode_json($context->data()),
            'resultjson' => $this->encode_json($result),
            'timecreated' => time(),
        ]);
    }

    public function get_recent_for_user(int $userid, int $limit = 20): array {
        global $DB;

        return array_values($DB->get_records(
            self::TABLE,
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    public function get_recent(int $limit = 50): array {
        global $DB;

        return array_values($DB->get_records(
            self::TABLE,
            null,
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    private function encode_json(array $data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }
}