<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;

final class InboxDraftAutosaveService {

    public function __construct(
        private readonly InboxAccountRepository $accounts =
            new InboxAccountRepository(),
        private readonly InboxContactRepository $contacts =
            new InboxContactRepository(),
        private readonly InboxDraftRepository $drafts =
            new InboxDraftRepository(),
        private readonly InboxReadRepository $read =
            new InboxReadRepository(),
        private readonly InboxThreadRepository $threads =
            new InboxThreadRepository()
    ) {
    }

    public function save(
        string $mode,
        int $accountid,
        int $threadid,
        string $subject,
        string $bodytext,
        string $bodyhtml,
        array $to,
        array $cc,
        array $bcc,
        int $actorid
    ): array {
        /*
         * A reply belongs to an existing Inbox thread. Trust the persisted
         * thread account rather than a browser-posted account identifier.
         * This also keeps draft autosave working for historical conversations
         * if an Inbox account is later disabled for new outbound mail.
         */
        if ($mode === 'reply') {
            $replythread = $this->read->get_thread(
                $threadid
            );

            if (!$replythread) {
                throw new \moodle_exception(
                    'crm_inbox_thread_not_found',
                    'local_subscriptions'
                );
            }

            $accountid =
                (int)$replythread->accountid;

            $account = $this->accounts->find(
                $accountid
            );

            if (!$account) {
                throw new \moodle_exception(
                    'crm_inbox_account_not_found',
                    'local_subscriptions'
                );
            }
        } else {
            $account = $this->accounts->find(
                $accountid
            );

            if (!$account || !$account->enabled) {
                throw new \moodle_exception(
                    'crm_inbox_account_not_found',
                    'local_subscriptions'
                );
            }
        }

        $recipientservice =
            new InboxRecipientService();

        $envelope = $recipientservice
            ->normalize(
                $to,
                $cc,
                $bcc
            );

        $htmlservice =
            new InboxReplyHtmlService();

        $safehtml = $htmlservice->sanitize(
            $bodyhtml
        );

        $text = $safehtml !== ''
            ? $htmlservice->text_version(
                $safehtml
            )
            : trim($bodytext);

        if ($mode === 'compose') {
            if ($threadid <= 0) {
                $contactid = null;

                if ($envelope['to'] !== []) {
                    $contact = $this->contacts
                        ->get_or_create(
                            $envelope['to'][0]
                        );

                    $contactid = (int)$contact->id;
                }

                $thread = $this->threads
                    ->create_outbound(
                        $accountid,
                        $contactid,
                        clean_param(
                            $subject,
                            PARAM_TEXT
                        ),
                        'DRAFTS'
                    );

                $threadid = (int)$thread->id;
            } else {
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
            }
        } else {
            $thread = $replythread;
        }

        $draft = $this->drafts->save(
            $accountid,
            $threadid,
            clean_param(
                $subject,
                PARAM_TEXT
            ),
            $text,
            $safehtml !== ''
                ? $safehtml
                : null,
            $actorid
        );

        $this->drafts->save_envelope(
            (int)$draft->id,
            $envelope
        );

        if ($mode === 'compose') {
            $this->threads->update_draft_metadata(
                $threadid,
                clean_param(
                    $subject,
                    PARAM_TEXT
                ),
                (int)$draft->id,
                (int)$draft->timemodified
            );
        }

        return [
            'threadid' => $threadid,
            'draftid' => (int)$draft->id,
            'savedat' => (int)$draft->timemodified,
        ];
    }
}
