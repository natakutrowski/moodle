<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\logging\InboxAdminEventLogger;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxAssignmentRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;

final class InboxAssignmentService {

    public function __construct(
        private readonly InboxAssignmentRepository $assignments,
        private readonly InboxTeamRepository $teams,
        private readonly InboxThreadRepository $threads,
        private readonly ?InboxAdminEventLogger $events = null
    ) {
    }

    public function assign_to_user(
        int $threadid,
        int $userid
    ): void {
        $this->assert_thread_exists(
            $threadid
        );

        $this->assignments->assign(
            $threadid,
            $userid,
            null
        );

        $this->event_logger()->assigned(
            $threadid,
            $userid,
            null
        );
    }

    public function assign_to_team(
        int $threadid,
        int $teamid
    ): void {
        $this->assert_thread_exists(
            $threadid
        );

        if (
            !$this->teams->find(
                $teamid
            )
        ) {
            throw new \invalid_parameter_exception(
                'Inbox team not found.'
            );
        }

        $this->assignments->assign(
            $threadid,
            null,
            $teamid
        );

        $this->event_logger()->assigned(
            $threadid,
            null,
            $teamid
        );
    }

    public function assign_to_user_and_team(
        int $threadid,
        int $userid,
        int $teamid
    ): void {
        $this->assert_thread_exists(
            $threadid
        );

        if (
            !$this->teams->find(
                $teamid
            )
        ) {
            throw new \invalid_parameter_exception(
                'Inbox team not found.'
            );
        }

        if (
            !$this->teams->is_member(
                $teamid,
                $userid
            )
        ) {
            throw new \invalid_parameter_exception(
                'The selected administrator is not a member of this Inbox team.'
            );
        }

        $this->assignments->assign(
            $threadid,
            $userid,
            $teamid
        );

        $this->event_logger()->assigned(
            $threadid,
            $userid,
            $teamid
        );
    }

    public function unassign(
        int $threadid
    ): void {
        $this->assert_thread_exists(
            $threadid
        );

        $this->assignments->unassign(
            $threadid
        );

        $this->event_logger()->unassigned(
            $threadid
        );
    }

    private function event_logger():
        InboxAdminEventLogger {
        return $this->events
            ?? new InboxAdminEventLogger(
                new InboxReadRepository()
            );
    }

    private function assert_thread_exists(
        int $threadid
    ): void {
        if (!$this->threads->find($threadid)) {
            throw new \invalid_parameter_exception(
                'Inbox thread not found.'
            );
        }
    }
}