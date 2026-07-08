<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationRepository {

    private const TABLE = 'local_subscriptions_automation_rule';

    public function get_enabled_rules_for_trigger(string $triggerkey): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            [
                'triggerkey' => $triggerkey,
                'enabled' => 1,
            ],
            'priority ASC, id ASC'
        );

        return array_values(array_map([$this, 'hydrate_rule'], $records));
    }

    public function get_all_rules(): array {
        global $DB;

        $records = $DB->get_records(self::TABLE, null, 'enabled DESC, priority ASC, id ASC');

        return array_values(array_map([$this, 'hydrate_rule'], $records));
    }

    public function get_rule(int $id): AutomationRule {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);

        return $this->hydrate_rule($record);
    }

    public function save_rule(AutomationRule $rule): int {
        global $DB;

        $now = time();

        $record = (object)[
            'rulekey' => $rule->key,
            'name' => $rule->name,
            'triggerkey' => $rule->trigger->key,
            'triggerpayload' => $this->encode_json($rule->trigger->payload),
            'conditionsjson' => $this->encode_json(array_map(
                static fn(AutomationCondition $condition): array => $condition->to_array(),
                $rule->conditions
            )),
            'actionsjson' => $this->encode_json(array_map(
                static fn(AutomationAction $action): array => $action->to_array(),
                $rule->actions
            )),
            'enabled' => $rule->enabled ? 1 : 0,
            'priority' => $rule->priority,
            'metadatajson' => $this->encode_json($rule->metadata),
            'timemodified' => $now,
        ];

        if ($rule->id > 0) {
            $record->id = $rule->id;
            $DB->update_record(self::TABLE, $record);

            return $rule->id;
        }

        $record->timecreated = $now;

        return (int)$DB->insert_record(self::TABLE, $record);
    }

    public function enable_rule(int $id): void {
        global $DB;

        $DB->set_field(self::TABLE, 'enabled', 1, ['id' => $id]);
        $DB->set_field(self::TABLE, 'timemodified', time(), ['id' => $id]);
    }

    public function disable_rule(int $id): void {
        global $DB;

        $DB->set_field(self::TABLE, 'enabled', 0, ['id' => $id]);
        $DB->set_field(self::TABLE, 'timemodified', time(), ['id' => $id]);
    }

    private function hydrate_rule(\stdClass $record): AutomationRule {
        $triggerpayload = $this->decode_json((string)($record->triggerpayload ?? ''));

        $conditions = array_map(
            static fn(array $condition): AutomationCondition => AutomationCondition::make(
                (string)($condition['key'] ?? ''),
                (array)($condition['payload'] ?? []),
                (bool)($condition['negated'] ?? false)
            ),
            $this->decode_json((string)($record->conditionsjson ?? '[]'))
        );

        $actions = array_map(
            static fn(array $action): AutomationAction => AutomationAction::make(
                (string)($action['key'] ?? ''),
                (array)($action['payload'] ?? []),
                (bool)($action['stoponfailure'] ?? true)
            ),
            $this->decode_json((string)($record->actionsjson ?? '[]'))
        );

        return new AutomationRule(
            (int)$record->id,
            (string)$record->rulekey,
            (string)$record->name,
            AutomationTrigger::make((string)$record->triggerkey, $triggerpayload),
            $conditions,
            $actions,
            (bool)$record->enabled,
            (int)$record->priority,
            $this->decode_json((string)($record->metadatajson ?? '[]'))
        );
    }

    private function encode_json(array $data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    private function decode_json(string $json): array {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}