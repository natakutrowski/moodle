<?php

namespace local_subscriptions\crm\inbox\ai\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\contracts\InboxAiContextProviderInterface;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;

final class InboxContactContextProvider implements
    InboxAiContextProviderInterface {

    public function __construct(
        private readonly InboxReadRepository $repository
    ) {
    }

    public function key(): string {
        return 'contact';
    }

    public function priority(): int {
        return 20;
    }

    public function supports(
        int $threadid
    ): bool {
        $thread = $this->repository->get_thread(
            $threadid
        );

        return $thread !== null &&
            !empty($thread->contactid);
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
            'displayname' =>
                trim(
                    (string)(
                        $thread->contactname
                        ?? ''
                    )
                ),
            'email' =>
                trim(
                    (string)(
                        $thread->contactemail
                        ?? ''
                    )
                ),
            'matchstatus' =>
                (string)(
                    $thread->matchstatus
                    ?? 'unmatched'
                ),
            'registereduser' =>
                !empty($thread->matcheduserid),
            'matcheduserid' =>
                !empty($thread->matcheduserid)
                    ? (int)$thread->matcheduserid
                    : null,
        ];
    }
}