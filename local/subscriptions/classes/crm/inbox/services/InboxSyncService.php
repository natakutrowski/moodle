<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxConnectorInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\domain\InboxParticipantType;
use local_subscriptions\crm\inbox\dto\InboxMessageData;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxAttachmentRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxParticipantRepository;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxSyncLogRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;
use local_subscriptions\crm\inbox\sync\InboxSyncCursor;
use local_subscriptions\crm\inbox\sync\InboxSyncLock;
use local_subscriptions\crm\inbox\sync\InboxSyncResult;

final class InboxSyncService {

    public function __construct(
        private readonly InboxConnectorInterface $connector,
        private readonly InboxAccountRepository $accounts,
        private readonly InboxContactRepository $contacts,
        private readonly InboxThreadRepository $threads,
        private readonly InboxMessageRepository $messages,
        private readonly InboxParticipantRepository $participants,
        private readonly InboxAttachmentRepository $attachments,
        private readonly InboxSyncLogRepository $logs,
        private readonly InboxSyncLock $locks,
        private readonly InboxRemoteMessageRepository $remote,
        private readonly InboxUserMatcher $matcher
    ) {
    }

    public function sync_folder(
        InboxAccount $account,
        string $folder,
        int $batchsize = 50
    ): InboxSyncResult {
        $lock = $this->locks->acquire(
            $account->id
        );

        try {
            return $this->run_sync(
                $account,
                $folder,
                $batchsize
            );
        } finally {
            $lock->release();
        }
    }

    private function run_sync(
        InboxAccount $account,
        string $folder,
        int $batchsize
    ): InboxSyncResult {
        $state = $account->syncstate;

        $folderstate = $state['folders'][$folder]
            ?? [];

        $cursor = isset($folderstate['cursor'])
            ? (string)$folderstate['cursor']
            : null;

        $logid = $this->logs->start(
            $account->id,
            $cursor === null
                ? 'initial'
                : 'incremental',
            $folder,
            $cursor
        );

        $fetched = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        /*
        * Le curseur sûr représente le dernier message
        * effectivement importé ou reconnu comme existant.
        *
        * Il ne doit jamais dépasser un message en erreur.
        */
        $safecursor = $cursor;
        $stoppedonerror = false;

        try {
            $page = $this->connector->fetch_page(
                $account,
                $folder,
                $cursor,
                max(1, min(200, $batchsize))
            );

            foreach ($page->messages as $message) {
                $fetched++;

                try {
                    $wascreated =
                        $this->import_message(
                            $account,
                            $message
                        );

                    if ($wascreated) {
                        $created++;
                    } else {
                        $skipped++;
                    }

                    /*
                    * Ce message a été traité avec succès.
                    * Le prochain passage pourra reprendre après lui.
                    */
                    $safecursor = (
                        new InboxSyncCursor(
                            $message->uidvalidity,
                            max(
                                0,
                                (int)$message->provideruid
                            )
                        )
                    )->encode();
                } catch (\Throwable $exception) {
                    $errors++;
                    $stoppedonerror = true;

                    mtrace(
                        '[CRM Inbox] Message import failed ' .
                        sprintf(
                            '[account=%s, folder=%s, uid=%s]: ',
                            $account->email,
                            $folder,
                            (string)$message->provideruid
                        ) .
                        $exception->getMessage()
                    );

                    /*
                    * Ne surtout pas traiter les messages suivants :
                    * cela ferait avancer le curseur au-delà
                    * du message actuellement en erreur.
                    */
                    break;
                }
            }

            $state['folders'][$folder] = [
                'cursor' => $safecursor,
                'updatedat' => time(),
            ];

            $this->accounts->update_sync_state(
                $account->id,
                $state
            );

            $status = $errors > 0
                ? 'partial'
                : 'success';

            $this->logs->complete(
                $logid,
                $status,
                $safecursor,
                $fetched,
                $created,
                $updated,
                $skipped,
                $errors
            );

            return new InboxSyncResult(
                $fetched,
                $created,
                $updated,
                $skipped,
                $errors,
                $safecursor,
                $stoppedonerror || $page->hasmore
            );
        } catch (\Throwable $exception) {
            $this->accounts->record_error(
                $account->id,
                $exception->getMessage()
            );

            $this->logs->complete(
                $logid,
                'failed',
                $safecursor,
                $fetched,
                $created,
                $updated,
                $skipped,
                $errors + 1,
                $exception->getMessage()
            );

            throw $exception;
        }
    }

    private function import_message(
        InboxAccount $account,
        InboxMessageData $message
    ): bool {
        global $DB;

        $providerkey =
            InboxMessageRepository::provider_key(
                $message
            );

        $existing = $this->messages->find_existing(
            $account->id,
            $message
        );

        if ($existing) {
            $this->remote->upsert(
                (int)$existing->id,
                $account->id,
                $message
            );

            $this->remote->deactivate_other_locations(
                (int)$existing->id,
                $providerkey
            );

            return false;
        }

        $transaction =
            $DB->start_delegated_transaction();

        try {
            $primarycontact =
                $this->resolve_primary_contact(
                    $account,
                    $message
                );

            $providerthreadid =
                $this->provider_thread_id(
                    $message,
                    $providerkey
                );

            $thread =
                $this->threads
                    ->find_by_provider_thread(
                        $account->id,
                        $providerthreadid
                    );

            $messageat =
                $message->receivedat
                ?? $message->sentat
                ?? time();

            $unread = !$message->isread;

            if (!$thread) {
                $thread = $this->threads->create(
                    $account->id,
                    $primarycontact?->id,
                    $providerthreadid,
                    $message->subject,
                    $message->folder,
                    $messageat,
                    false
                );
            }

            $storedmessage =
                $this->messages->create(
                    $account->id,
                    (int)$thread->id,
                    $providerkey,
                    $message
                );

            $this->remote->upsert(
                (int)$storedmessage->id,
                $account->id,
                $message
            );

            foreach ($message->participants as $participant) {
                $contact = null;

                if (
                    !$this->is_account_address(
                        $account,
                        $participant->normalizedemail
                    )
                ) {
                    $contact =
                        $this->contacts->get_or_create(
                            $participant->email,
                            $participant->displayname
                        );

                    if ($contact->can_be_reconciled()) {
                        $this->matcher->reconcile($contact);

                        $contact = $this->contacts->find(
                            $contact->id
                        ) ?? $contact;
                    }
                }

                $this->participants->create(
                    (int)$storedmessage->id,
                    $contact?->id,
                    $participant
                );
            }

            foreach (
                $message->attachments
                as $position => $attachment
            ) {
                $stableproviderid =
                    $attachment->providerattachmentid;

                if (
                    $stableproviderid === null ||
                    trim($stableproviderid) === ''
                ) {
                    $stableproviderid = hash(
                        'sha256',
                        implode('|', [
                            $attachment->filename,
                            (string)$attachment->filesize,
                            $attachment->contentid ?? '',
                            (string)$position,
                        ])
                    );
                }

                $this->attachments->get_or_create(
                    (int)$storedmessage->id,
                    $attachment,
                    $stableproviderid
                );
            }

            $this->threads->update_after_message(
                (int)$thread->id,
                $primarycontact?->id,
                $message->subject,
                $message->folder,
                $messageat,
                $unread,
                true,
                (int)$storedmessage->id
            );

            $transaction->allow_commit();

            return true;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);

            throw $exception;
        }
    }

    private function resolve_primary_contact(
        InboxAccount $account,
        InboxMessageData $message
    ): ?object {
        $wantedtype =
            $message->direction === 'outbound'
                ? InboxParticipantType::TO
                : InboxParticipantType::FROM;

        foreach ($message->participants as $participant) {
            if ($participant->type !== $wantedtype) {
                continue;
            }

            if (
                $this->is_account_address(
                    $account,
                    $participant->normalizedemail
                )
            ) {
                continue;
            }

            $contact = $this->contacts->get_or_create(
                $participant->email,
                $participant->displayname
            );

            if ($contact->can_be_reconciled()) {
                $this->matcher->reconcile($contact);

                $contact = $this->contacts->find(
                    $contact->id
                ) ?? $contact;
            }

            return $contact;
        }

        return null;
    }

    private function is_account_address(
        InboxAccount $account,
        string $email
    ): bool {
        return \core_text::strtolower(
            trim($email)
        ) === \core_text::strtolower(
            trim($account->email)
        );
    }

    private function provider_thread_id(
        InboxMessageData $message,
        string $providerkey
    ): string {
        $providerthreadid = trim(
            (string)(
                $message->providerthreadid ?? ''
            )
        );

        if ($providerthreadid !== '') {
            return substr(
                hash('sha256', $providerthreadid),
                0,
                64
            );
        }

        return $providerkey;
    }
}