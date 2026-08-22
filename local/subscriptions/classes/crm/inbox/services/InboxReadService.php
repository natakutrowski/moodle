<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxThreadCriteria;
use local_subscriptions\crm\inbox\dto\InboxThreadListResult;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;

final class InboxReadService {

    public function __construct(
        private readonly InboxReadRepository $repository,
        private readonly InboxTeamRepository $teams,
        private readonly InboxAccountRepository $accounts
    ) {
    }

    public function search(
        InboxThreadCriteria $criteria,
        int $actorid
    ): InboxThreadListResult {
        $total = $this->repository->count_threads(
            $criteria,
            $actorid
        );

        $lastpage = $total > 0
            ? (int)floor(
                ($total - 1) / $criteria->perpage
            )
            : 0;

        if ($criteria->page > $lastpage) {
            $criteria = $criteria->with_page(
                $lastpage
            );
        }

        $foldercounts = [];

        foreach (
            [
                'inbox',
                'sent',
                'drafts',
                'archive',
                'trash',
                'all',
            ]
            as $folder
        ) {
            $foldercounts[$folder] =
                $this->repository->count_threads(
                    $criteria->with_folder(
                        $folder
                    ),
                    $actorid
                );
        }

        return new InboxThreadListResult(
            $criteria,
            $this->repository->get_threads(
                $criteria,
                $actorid
            ),
            $total,
            $this->teams->get_enabled(),
            $this->accounts->get_enabled(),
            $foldercounts
        );
    }

    public function thread(int $threadid): object {
        $thread = $this->repository->get_thread(
            $threadid
        );

        if (!$thread) {
            throw new \moodle_exception(
                'crm_inbox_thread_not_found',
                'local_subscriptions'
            );
        }

        $messages = $this->repository
            ->get_messages($threadid);

        $messageids = array_map(
            static fn(object $message): int =>
                (int)$message->id,
            $messages
        );

        $participants =
            $this->repository
                ->get_participants_by_message(
                    $messageids
                );

        $attachments =
            $this->repository
                ->get_attachments_by_message(
                    $messageids
                );

        $participantsbymessage = [];
        $attachmentsbymessage = [];

        foreach ($participants as $participant) {
            $participantsbymessage[
                (int)$participant->messageid
            ][] = $participant;
        }

        foreach ($attachments as $attachment) {
            $attachmentsbymessage[
                (int)$attachment->messageid
            ][] = $attachment;
        }

        $thread->messages = [];

        foreach ($messages as $message) {
            $message->participants =
                $participantsbymessage[
                    (int)$message->id
                ] ?? [];

            $message->attachments =
                $attachmentsbymessage[
                    (int)$message->id
                ] ?? [];

            $thread->messages[] = $message;
        }

        return $thread;
    }
}