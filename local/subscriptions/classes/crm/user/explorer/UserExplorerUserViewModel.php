<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\UserExplorerFilter;
use local_subscriptions\crm\user\UserProfileTag;

final class UserExplorerUserViewModel {

    public function __construct(
        public readonly \stdClass $user,
        public readonly array $tags,
        public readonly array $segments,
        public readonly array $opportunities,
        public readonly array $recommendations
    ) {
    }

    public static function from_record(
        \stdClass $record,
        array $tags
    ): self {
        return new self(
            $record,
            array_map(
                static fn(string $tag): \stdClass =>
                    (object)[
                        'key' => $tag,
                        'label' => UserProfileTag::label($tag),
                    ],
                $tags
            ),
            self::decode_labels(
                (string)($record->segmentsjson ?? ''),
                true
            ),
            self::decode_labels(
                (string)($record->opportunitiesjson ?? ''),
                true
            ),
            self::decode_labels(
                (string)($record->recommendationsjson ?? ''),
                false
            )
        );
    }

    public function score_level_label(): string {
        $level = trim(
            (string)($this->user->scorelevel ?? '')
        );

        if ($level === '') {
            return get_string(
                'crm_user_score_level_unknown',
                'local_subscriptions'
            );
        }

        $key = 'crm_user_score_level_' . $level;

        if (
            get_string_manager()->string_exists(
                $key,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $key,
                'local_subscriptions'
            );
        }

        return ucfirst(str_replace('_', ' ', $level));
    }

    public function account_status_label(): string {
        if (!empty($this->user->iscommerceguest)) {
            return get_string(
                'crm_user_account_commerce_only',
                'local_subscriptions'
            );
        }

        return !empty($this->user->suspended)
            ? get_string(
                'crm_user_account_suspended',
                'local_subscriptions'
            )
            : get_string(
                'crm_user_account_active',
                'local_subscriptions'
            );
    }

    private static function decode_labels(
        string $json,
        bool $useexplorerfilter
    ): array {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];

        foreach ($decoded as $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }

            $label = $value;

            if (
                $useexplorerfilter &&
                in_array(
                    $value,
                    UserExplorerFilter::allowed(),
                    true
                )
            ) {
                $label = UserExplorerFilter::label($value);
            } else {
                $stringkey = 'crm_intelligence_key_' . $value;

                if (
                    get_string_manager()->string_exists(
                        $stringkey,
                        'local_subscriptions'
                    )
                ) {
                    $label = get_string(
                        $stringkey,
                        'local_subscriptions'
                    );
                } else {
                    $label = ucfirst(
                        str_replace('_', ' ', $value)
                    );
                }
            }

            $items[] = (object)[
                'key' => $value,
                'label' => $label,
            ];
        }

        return $items;
    }
}