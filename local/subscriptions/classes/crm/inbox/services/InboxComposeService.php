<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxOutboundConnectorInterface;
use local_subscriptions\crm\inbox\logging\InboxAdminEventLogger;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;

final class InboxComposeService {

    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxContactRepository $contacts,
        private readonly InboxReadRepository $read,
        private readonly InboxDraftRepository $drafts,
        private readonly InboxThreadRepository $threads,
        private readonly InboxOutboundConnectorInterface $connector
    ) {
    }

    public function send(
        int $accountid,
        array $to,
        array $cc,
        array $bcc,
        string $subject,
        string $body,
        int $actorid,
        array $uploads = [],
        string $bodyhtml = '',
        array $inlineuploads = [],
        array $inlinecids = [],
        int $threadid = 0
    ): int {
        $account = $this->accounts->find(
            $accountid
        );

        if (!$account || !$account->enabled) {
            throw new \moodle_exception(
                'crm_inbox_account_not_found',
                'local_subscriptions'
            );
        }

        $recipientservice =
            new InboxRecipientService();

        $recipients = $recipientservice
            ->normalize(
                $to,
                $cc,
                $bcc
            );

        if ($recipients['to'] === []) {
            throw new \moodle_exception(
                'crm_inbox_invalid_recipient',
                'local_subscriptions'
            );
        }

        $primary = $recipients['to'][0];

        $contact = $this->contacts
            ->get_or_create(
                $primary
            );

        if ($threadid > 0) {
            $thread = $this->read->get_thread(
                $threadid
            );

            if (
                !$thread
                || (int)$thread->accountid !== $accountid
                || (string)$thread->folder !== 'DRAFTS'
            ) {
                throw new \moodle_exception(
                    'crm_inbox_draft_not_found_o7',
                    'local_subscriptions'
                );
            }

            $this->threads->set_folder(
                $threadid,
                'INBOX'
            );
        } else {
            $thread = $this->threads
                ->create_outbound(
                    $accountid,
                    $contact->id,
                    clean_param(
                        $subject,
                        PARAM_TEXT
                    )
                );
        }

        $service = new InboxReplyService(
            $this->accounts,
            $this->read,
            $this->drafts,
            $this->threads,
            $this->connector,
            new InboxAdminEventLogger(
                $this->read
            )
        );

        $service->send(
            (int)$thread->id,
            $subject,
            $body,
            $actorid,
            $uploads,
            [],
            $bodyhtml,
            $inlineuploads,
            $inlinecids,
            $recipients['to'],
            $recipients['cc'],
            $recipients['bcc']
        );

        return (int)$thread->id;
    }
}
