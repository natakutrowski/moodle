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
use local_subscriptions\crm\inbox\repositories\InboxParticipantRepository;
use local_subscriptions\crm\inbox\dto\InboxParticipantData;

final class InboxReplyService {

    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxReadRepository $read,
        private readonly InboxDraftRepository $drafts,
        private readonly InboxThreadRepository $threads,
        private readonly InboxOutboundConnectorInterface $connector,
        private readonly ?InboxAdminEventLogger $events = null,
        private readonly ?InboxReplyAttachmentService $replyattachments = null,
        private readonly ?InboxParticipantRepository $participants = null,
        private readonly ?InboxRecipientService $recipients = null
    ) {
    }

    public function save_draft(
        int $threadid,
        string $subject,
        string $body,
        int $actorid,
        array $uploads = [],
        array $removeattachmentids = [],
        string $bodyhtml = '',
        array $inlineuploads = [],
        array $inlinecids = [],
        array $to = [],
        array $cc = [],
        array $bcc = []
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

        $htmlservice =
            new InboxReplyHtmlService();

        $safehtml = $htmlservice->sanitize(
            $bodyhtml
        );

        $bodytext = $safehtml !== ''
            ? $htmlservice->text_version(
                $safehtml
            )
            : trim($body);

        $draft = $this->drafts->save(
            (int)$thread->accountid,
            $threadid,
            clean_param($subject, PARAM_TEXT),
            $bodytext,
            $safehtml !== ''
                ? $safehtml
                : null,
            $actorid
        );

        $this->reply_attachment_service()
            ->apply(
                (int)$draft->id,
                $uploads,
                $removeattachmentids
            );

        $this->reply_attachment_service()
            ->apply_inline_images(
                (int)$draft->id,
                $safehtml,
                $inlineuploads,
                $inlinecids
            );

        $envelope = $this->recipient_service()
            ->normalize(
                $to,
                $cc,
                $bcc
            );

        $this->drafts->save_envelope(
            (int)$draft->id,
            $envelope
        );

        return $draft;
    }

    public function send(
        int $threadid,
        string $subject,
        string $body,
        int $actorid,
        array $uploads = [],
        array $removeattachmentids = [],
        string $bodyhtml = '',
        array $inlineuploads = [],
        array $inlinecids = [],
        array $to = [],
        array $cc = [],
        array $bcc = []
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

        $defaultrecipient = trim(
            (string)$thread->contactemail
        );

        if ($to === [] && $defaultrecipient !== '') {
            $to = [$defaultrecipient];
        }

        $recipientset =
            $this->recipient_service()
                ->normalize(
                    $to,
                    $cc,
                    $bcc
                );

        if ($recipientset['to'] === []) {
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

        $htmlservice =
            new InboxReplyHtmlService();

        $safehtml = $htmlservice->sanitize(
            $bodyhtml
        );

        $bodytext = $safehtml !== ''
            ? $htmlservice->text_version(
                $safehtml
            )
            : trim($body);

        $draft = $this->drafts->save(
            $account->id,
            $threadid,
            clean_param($subject, PARAM_TEXT),
            $bodytext,
            $safehtml !== ''
                ? $safehtml
                : null,
            $actorid
        );

        $this->reply_attachment_service()
            ->apply(
                (int)$draft->id,
                $uploads,
                $removeattachmentids
            );

        $this->reply_attachment_service()
            ->apply_inline_images(
                (int)$draft->id,
                $safehtml,
                $inlineuploads,
                $inlinecids
            );

        $this->reply_attachment_service()
            ->assert_ready_for_send(
                (int)$draft->id
            );

        $attachments =
            $this->reply_attachment_service()
                ->outbound_attachments(
                    (int)$draft->id
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
            $recipientset['to'],
            $recipientset['cc'],
            $recipientset['bcc'],
            clean_param($subject, PARAM_TEXT),
            $bodytext,
            $safehtml !== ''
                ? $safehtml
                : null,
            $lastremote
                ? $lastremote->providermessageid
                : null,
            array_values(array_unique($references)),
            $actorid,
            $attachments
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

        $this->drafts->record_sent_copy_result(
            (int)$draft->id,
            $result->sentfolder,
            $result->sentcopyerror
        );

        $this->persist_outbound_participants(
            (int)$draft->id,
            $account->email,
            $recipientset
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

    private function persist_outbound_participants(
        int $messageid,
        string $from,
        array $recipients
    ): void {
        $repository = $this->participants
            ?? new InboxParticipantRepository();

        $values = [
            'from' => [$from],
            'to' => $recipients['to'] ?? [],
            'cc' => $recipients['cc'] ?? [],
            'bcc' => $recipients['bcc'] ?? [],
        ];

        foreach ($values as $type => $emails) {
            foreach ($emails as $email) {
                $normalized =
                    \core_text::strtolower(
                        trim((string)$email)
                    );

                $repository->create(
                    $messageid,
                    null,
                    new InboxParticipantData(
                        $type,
                        (string)$email,
                        $normalized
                    )
                );
            }
        }
    }

    private function recipient_service():
        InboxRecipientService {
        return $this->recipients
            ?? new InboxRecipientService();
    }

    private function reply_attachment_service():
        InboxReplyAttachmentService {
        return $this->replyattachments
            ?? new InboxReplyAttachmentService();
    }

    private function event_logger():
        InboxAdminEventLogger {
        return $this->events
            ?? new InboxAdminEventLogger(
                $this->read
            );
    }

}