<?php

namespace local_subscriptions\crm\user\inbox;

defined('MOODLE_INTERNAL') || die();

final class UserInboxService {

    public function __construct(
        private readonly UserInboxRepository $repository
    ) {
    }

    public function get_for_user(
        int $userid,
        int $recentlimit = 5
    ): UserInboxSummary {
        if (
            !$this->repository->is_available()
        ) {
            return UserInboxSummary::unavailable();
        }

        $summary =
            $this->repository->get_summary(
                $userid
            );

        $recentthreads =
            $this->repository->get_recent_threads(
                $userid,
                $recentlimit
            );

        $aisuggestioncount =
            $this->repository
                ->count_ai_reply_suggestions(
                    $userid
                );

        $lastthread =
            $recentthreads[0] ?? null;

        $lastsubject = '';

        if ($lastthread !== null) {
            $lastsubject = trim(
                (string)(
                    $lastthread->lastmessagesubject
                    ?: $lastthread->subject
                    ?: ''
                )
            );
        }

        return new UserInboxSummary(
            true,

            (int)(
                $summary->conversationcount
                ?? 0
            ),

            (int)(
                $summary->openconversationcount
                ?? 0
            ),

            (int)(
                $summary->unreadcount
                ?? 0
            ),

            $aisuggestioncount,

            !empty(
                $summary->lastmessageat
            )
                ? (int)$summary->lastmessageat
                : null,

            $lastthread !== null
                ? (int)$lastthread->id
                : null,

            $lastsubject,

            $lastthread !== null
                ? (string)(
                    $lastthread->lastdirection
                    ?? ''
                )
                : '',

            $recentthreads
        );
    }
}