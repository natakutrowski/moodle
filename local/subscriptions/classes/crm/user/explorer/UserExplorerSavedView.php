<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerSavedView {

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $criteria,
        public readonly int $timecreated
    ) {
    }

    public static function from_array(array $data): ?self {
        $id = clean_param(
            (string)($data['id'] ?? ''),
            PARAM_ALPHANUMEXT
        );

        $name = trim(
            clean_param(
                (string)($data['name'] ?? ''),
                PARAM_TEXT
            )
        );

        $criteria = $data['criteria'] ?? [];

        if (
            $id === '' ||
            $name === '' ||
            !is_array($criteria)
        ) {
            return null;
        }

        return new self(
            $id,
            $name,
            $criteria,
            (int)($data['timecreated'] ?? 0)
        );
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'criteria' => $this->criteria,
            'timecreated' => $this->timecreated,
        ];
    }
}