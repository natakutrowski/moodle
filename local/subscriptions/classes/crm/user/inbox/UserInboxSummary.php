<?php

namespace local_subscriptions\crm\user\inbox;

defined('MOODLE_INTERNAL') || die();

final class UserInboxSummary {

    /**
     * @param object[] $recentthreads
     */
    public function __construct(
        public readonly bool $available,
        public readonly int $conversationcount,
        public readonly int $openconversationcount,
        public readonly int $unreadcount,
        public readonly int $aisuggestioncount,
        public readonly ?int $lastmessageat,
        public readonly ?int $lastthreadid,
        public readonly string $lastsubject,
        public readonly string $lastdirection,
        public readonly array $recentthreads
    ) {
    }

    public static function unavailable(): self {
        return new self(
            false,
            0,
            0,
            0,
            0,
            null,
            null,
            '',
            '',
            []
        );
    }

    public function has_conversations(): bool {
        return $this->conversationcount > 0;
    }

    public function has_unread(): bool {
        return $this->unreadcount > 0;
    }

    public function has_ai_suggestions(): bool {
        return $this->aisuggestioncount > 0;
    }

    public function to_object(): \stdClass {
        return (object)[
            'available' =>
                $this->available,

            'conversationcount' =>
                $this->conversationcount,

            'openconversationcount' =>
                $this->openconversationcount,

            'unreadcount' =>
                $this->unreadcount,

            'aisuggestioncount' =>
                $this->aisuggestioncount,

            'lastmessageat' =>
                $this->lastmessageat,

            'lastthreadid' =>
                $this->lastthreadid,

            'lastsubject' =>
                $this->lastsubject,

            'lastdirection' =>
                $this->lastdirection,

            'recentthreads' =>
                $this->recentthreads,

            'hasconversations' =>
                $this->has_conversations(),

            'hasunread' =>
                $this->has_unread(),

            'hasaisuggestions' =>
                $this->has_ai_suggestions(),
        ];
    }
}