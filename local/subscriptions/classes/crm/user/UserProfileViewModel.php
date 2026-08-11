<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\UserIntelligence;
use local_subscriptions\crm\user\inbox\UserInboxSummary;

final class UserProfileViewModel {

    public function __construct(
        public readonly \stdClass $user,
        public readonly array $subscriptions,
        public readonly array $digitalpayments,
        public readonly UserProfileStats $stats,
        public readonly array $notes,
        public readonly array $timeline,
        public readonly array $courses,
        public readonly bool $timelinehasmore = false,
        public readonly int $timelinenextoffset = 0,
        public readonly array $tags = [],
        public readonly array $actions = [],
        public readonly ?UserIntelligence $intelligence = null,
        public readonly ?UserInboxSummary $inbox = null,
        public readonly array $commercepurchases = [],
        public readonly ?array $commercesnapshot = null,
        public readonly bool $iscommerceguest = false
    ) {
    }

    public function to_legacy_object(): \stdClass {
        return (object)[
            'user' => $this->user,
            'subscriptions' => $this->subscriptions,
            'digitalpayments' => $this->digitalpayments,
            'stats' => $this->stats->to_object(),
            'notes' => $this->notes,
            'timeline' => $this->timeline,
            'timelinehasmore' =>
                $this->timelinehasmore,
            'timelinenextoffset' =>
                $this->timelinenextoffset,
            'courses' => $this->courses,
            'actions' => $this->actions,
            'tags' => $this->tags,
            'intelligence' =>
                $this->intelligence?->to_object(),

            'inbox' =>
                $this->inbox?->to_object(),
            'commercepurchases' => $this->commercepurchases,
            'commercesnapshot' => $this->commercesnapshot,
            'iscommerceguest' => $this->iscommerceguest,
        ];
    }
}