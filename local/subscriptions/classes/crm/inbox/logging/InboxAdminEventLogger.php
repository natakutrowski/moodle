<?php

namespace local_subscriptions\crm\inbox\logging;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;

final class InboxAdminEventLogger {

    public function __construct(
        private readonly InboxReadRepository $read
    ) {
    }

    public function reply_sent(
        int $threadid,
        ?int $messageid = null
    ): void {
        $this->log(
            AdminEvents::INBOX_REPLY_SENT,
            $threadid,
            [
                'messageid' => $messageid,
            ]
        );
    }

    public function assigned(
        int $threadid,
        ?int $userid,
        ?int $teamid
    ): void {
        $this->log(
            AdminEvents::INBOX_THREAD_ASSIGNED,
            $threadid,
            [
                'assigneduserid' => $userid,
                'assignedteamid' => $teamid,
            ]
        );
    }

    public function unassigned(
        int $threadid
    ): void {
        $this->log(
            AdminEvents::INBOX_THREAD_UNASSIGNED,
            $threadid
        );
    }

    public function status_changed(
        int $threadid,
        string $status
    ): void {
        $this->log(
            AdminEvents::INBOX_THREAD_STATUS_CHANGED,
            $threadid,
            [
                'status' => $status,
            ]
        );
    }

    public function priority_changed(
        int $threadid,
        string $priority
    ): void {
        $this->log(
            AdminEvents::INBOX_THREAD_PRIORITY_CHANGED,
            $threadid,
            [
                'priority' => $priority,
            ]
        );
    }

    public function ai_analysis_executed(
        int $threadid,
        array $result = []
    ): void {
        $this->log(
            AdminEvents::INBOX_AI_ANALYSIS_EXECUTED,
            $threadid,
            [
                'resulttype' =>
                    (string)($result['type'] ?? 'analysis'),

                'generatedat' =>
                    (int)($result['generatedat'] ?? time()),
            ]
        );
    }

    public function ai_reply_suggested(
        int $threadid,
        array $result = []
    ): void {
        $this->log(
            AdminEvents::INBOX_AI_REPLY_SUGGESTED,
            $threadid,
            [
                'resulttype' =>
                    (string)($result['type'] ?? 'reply'),

                'generatedat' =>
                    (int)($result['generatedat'] ?? time()),
            ]
        );
    }

    private function log(
        string $event,
        int $threadid,
        array $details = []
    ): void {
        $thread = $this->read->get_thread(
            $threadid
        );

        if (!$thread) {
            debugging(
                'Unable to log CRM Inbox event: thread #' .
                $threadid .
                ' was not found.',
                DEBUG_DEVELOPER
            );

            return;
        }

        $details = array_merge(
            [
                'threadid' => $threadid,

                'subject' =>
                    (string)($thread->subject ?? ''),

                'contactemail' =>
                    (string)($thread->contactemail ?? ''),
            ],
            $details
        );

        AdminLog::log(
            $event,
            !empty($thread->matcheduserid)
                ? (int)$thread->matcheduserid
                : null,
            'inbox_thread',
            $threadid,
            $details
        );
    }
}