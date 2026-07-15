<?php

namespace local_subscriptions\crm\inbox\ai\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\contracts\InboxAiContextProviderInterface;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;

final class InboxThreadContextProvider implements
    InboxAiContextProviderInterface {

    public function __construct(
        private readonly InboxReadRepository $repository
    ) {
    }

    public function key(): string {
        return 'thread';
    }

    public function priority(): int {
        return 10;
    }

    public function supports(
        int $threadid
    ): bool {
        return $this->repository->get_thread(
            $threadid
        ) !== null;
    }

    public function provide(
        int $threadid
    ): array {
        $thread = $this->repository->get_thread(
            $threadid
        );

        if (!$thread) {
            return [];
        }

        return [
            'subject' =>
                trim((string)$thread->subject),
            'status' =>
                (string)$thread->status,
            'priority' =>
                (string)$thread->priority,
            'folder' =>
                (string)$thread->folder,
            'unreadcount' =>
                (int)$thread->unreadcount,
            'messagecount' =>
                (int)$thread->messagecount,
            'lastmessageat' =>
                !empty($thread->lastmessageat)
                    ? (int)$thread->lastmessageat
                    : null,
            'assigned' =>
                !empty($thread->assigneduserid),
            'assignedteam' =>
                trim(
                    (string)(
                        $thread->assignedteamname
                        ?? ''
                    )
                ),
        ];
    }
}