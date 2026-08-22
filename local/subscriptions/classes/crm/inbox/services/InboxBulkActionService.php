<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxPriority;
use local_subscriptions\crm\inbox\domain\InboxThreadStatus;

final class InboxBulkActionService {

    public function __construct(
        private readonly InboxThreadActionService $actions
    ) {
    }

    /**
     * @param int[] $threadids
     * @return array{
     *     requested:int,
     *     succeeded:int,
     *     failed:int,
     *     errors:array<int,string>
     * }
     */
    public function execute(
        array $threadids,
        string $action
    ): array {
        $threadids = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $threadids),
                    static fn(int $id): bool => $id > 0
                )
            )
        );

        if ($threadids === []) {
            return [
                'requested' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }

        $operation = $this->resolve_operation(
            $action
        );

        $succeeded = 0;
        $errors = [];

        foreach ($threadids as $threadid) {
            try {
                $operation($threadid);
                $succeeded++;
            } catch (\Throwable $exception) {
                $errors[$threadid] =
                    $exception->getMessage();
            }
        }

        return [
            'requested' => count($threadids),
            'succeeded' => $succeeded,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    private function resolve_operation(
        string $action
    ): \Closure {
        return match ($action) {
            'read' =>
                fn(int $threadid) =>
                    $this->actions->mark_read(
                        $threadid,
                        true
                    ),

            'unread' =>
                fn(int $threadid) =>
                    $this->actions->mark_read(
                        $threadid,
                        false
                    ),

            'archive' =>
                fn(int $threadid) =>
                    $this->actions->archive(
                        $threadid
                    ),

            'trash' =>
                fn(int $threadid) =>
                    $this->actions->trash(
                        $threadid
                    ),

            'restore' =>
                fn(int $threadid) =>
                    $this->actions->restore_to_inbox(
                        $threadid
                    ),

            'status_open' =>
                fn(int $threadid) =>
                    $this->actions->set_status(
                        $threadid,
                        InboxThreadStatus::OPEN
                    ),

            'status_pending' =>
                fn(int $threadid) =>
                    $this->actions->set_status(
                        $threadid,
                        InboxThreadStatus::PENDING
                    ),

            'status_resolved' =>
                fn(int $threadid) =>
                    $this->actions->set_status(
                        $threadid,
                        InboxThreadStatus::RESOLVED
                    ),

            'status_closed' =>
                fn(int $threadid) =>
                    $this->actions->set_status(
                        $threadid,
                        InboxThreadStatus::CLOSED
                    ),

            'status_spam' =>
                fn(int $threadid) =>
                    $this->actions->set_status(
                        $threadid,
                        InboxThreadStatus::SPAM
                    ),

            'priority_low' =>
                fn(int $threadid) =>
                    $this->actions->set_priority(
                        $threadid,
                        InboxPriority::LOW
                    ),

            'priority_normal' =>
                fn(int $threadid) =>
                    $this->actions->set_priority(
                        $threadid,
                        InboxPriority::NORMAL
                    ),

            'priority_high' =>
                fn(int $threadid) =>
                    $this->actions->set_priority(
                        $threadid,
                        InboxPriority::HIGH
                    ),

            'priority_urgent' =>
                fn(int $threadid) =>
                    $this->actions->set_priority(
                        $threadid,
                        InboxPriority::URGENT
                    ),

            default =>
                throw new \invalid_parameter_exception(
                    'Unknown Inbox bulk action.'
                ),
        };
    }
}
