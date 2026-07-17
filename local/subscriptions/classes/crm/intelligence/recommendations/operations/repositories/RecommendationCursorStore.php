<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\repositories;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores the lightweight Recommendation Engine batch cursor.
 */
final class RecommendationCursorStore {

    private const CONFIG_KEY =
        'recommendation_batch_cursor';

    public function get(): int {
        return max(
            0,
            (int)get_config(
                'local_subscriptions',
                self::CONFIG_KEY
            )
        );
    }

    public function save(int $userid): void {
        if ($userid < 0) {
            throw new \InvalidArgumentException(
                'Recommendation batch cursor cannot be negative.'
            );
        }

        set_config(
            self::CONFIG_KEY,
            $userid,
            'local_subscriptions'
        );
    }

    public function reset(): void {
        $this->save(0);
    }
}