<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\logging\InboxAdminEventLogger;
use local_subscriptions\crm\inbox\contracts\InboxOutboundConnectorInterface;
use local_subscriptions\crm\inbox\dto\InboxReplyRequest;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;

final class InboxReplyService {

    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxReadRepository $read,
        private readonly InboxDraftRepository $drafts,
        private readonly InboxThreadRepository $threads,
        private readonly InboxOutboundConnectorInterface $connector,
        private readonly ?InboxAdminEventLogger $events = null
    ) {
    }

    public function save_draft(
        int $threadid,
        string $subject,
        string $body,
        int $actorid
    ): object {
        $thread = $this->read->get_thread(
            $threadid
        );

        if (!$thread) {
            throw new \moodle_exception(
                'crm_inbox_thread_not_found',
                'local_subscriptions'
            );
        }

        return $this->drafts->save(
            (int)$thread->accountid,
            $threadid,
            clean_param($subject, PARAM_TEXT),
            trim($body),
            null,
            $actorid
        );
    }

    public function send(
        int $threadid,
        string $subject,
        string $body,
        int $actorid
    ): void {
        $thread = $this->read->get_thread(
            $threadid
        );

        if (!$thread) {
            throw new \moodle_exception(
                'crm_inbox_thread_not_found',
                'local_subscriptions'
            );
        }

        $recipient = trim(
            (string)$thread->contactemail
        );

        if (
            $recipient === '' ||
            !validate_email($recipient)
        ) {
            throw new \moodle_exception(
                'crm_inbox_invalid_recipient',
                'local_subscriptions'
            );
        }

        $account = $this->accounts->find(
            (int)$thread->accountid
        );

        if (!$account) {
            throw new \moodle_exception(
                'crm_inbox_account_not_found',
                'local_subscriptions'
            );
        }

        $draft = $this->drafts->save(
            $account->id,
            $threadid,
            clean_param($subject, PARAM_TEXT),
            trim($body),
            null,
            $actorid
        );

        $this->drafts->mark_sending(
            (int)$draft->id
        );

        $messages = $this->read->get_messages(
            $threadid
        );

        $lastremote = null;

        foreach (array_reverse($messages) as $message) {
            if (
                !empty($message->providermessageid)
            ) {
                $lastremote = $message;
                break;
            }
        }

        $references = [];

        if (
            $lastremote &&
            !empty($lastremote->referencesjson)
        ) {
            $references = json_decode(
                $lastremote->referencesjson,
                true
            ) ?: [];
        }

        if (
            $lastremote &&
            !empty($lastremote->providermessageid)
        ) {
            $references[] =
                $lastremote->providermessageid;
        }

        $request = new InboxReplyRequest(
            $account->id,
            $threadid,
            [$recipient],
            [],
            [],
            clean_param($subject, PARAM_TEXT),
            trim($body),
            null,
            $lastremote
                ? $lastremote->providermessageid
                : null,
            array_values(array_unique($references)),
            $actorid
        );

        $result = $this->connector->send(
            $account,
            $request
        );

        if (!$result->success) {
            $this->drafts->mark_failed(
                (int)$draft->id,
                $result->error
                    ?? 'Unknown SMTP error.'
            );

            throw new \moodle_exception(
                'crm_inbox_send_failed',
                'local_subscriptions',
                '',
                $result->error
                    ?? 'Unknown SMTP error.'
            );
        }

        $this->drafts->mark_sent(
            (int)$draft->id,
            $result->providermessageid,
            $result->sentat ?? time()
        );

        $this->threads->update_after_message(
            $threadid,
            !empty($thread->contactid)
                ? (int)$thread->contactid
                : null,
            $subject,
            (string)$thread->folder,
            $result->sentat ?? time(),
            false,
            true,
            (int)$draft->id
        );

        $this->event_logger()->reply_sent(
            $threadid,
            (int)$draft->id
        );        
    }

    private function event_logger():
        InboxAdminEventLogger {
        return $this->events
            ?? new InboxAdminEventLogger(
                $this->read
            );
    }

}