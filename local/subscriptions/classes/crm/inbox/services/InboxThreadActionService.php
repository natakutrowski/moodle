<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxConnectorInterface;
use local_subscriptions\crm\inbox\domain\InboxPriority;
use local_subscriptions\crm\inbox\domain\InboxThreadStatus;
use local_subscriptions\crm\inbox\logging\InboxAdminEventLogger;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadActionRepository;
use local_subscriptions\crm\inbox\repositories\InboxRemoteMessageRepository;

final class InboxThreadActionService {

    public function __construct(
        private readonly InboxReadRepository $read,
        private readonly InboxThreadActionRepository $actions,
        private readonly InboxAccountRepository $accounts,
        private readonly InboxConnectorInterface $connector,
        private readonly InboxRemoteMessageRepository $remotes,
        private readonly ?InboxAdminEventLogger $events = null
    ) {
    }

    public function set_status(
        int $threadid,
        string $status
    ): void {
        $this->assert_thread(
            $threadid
        );

        if (
            !InboxThreadStatus::is_valid(
                $status
            )
        ) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox thread status.'
            );
        }

        $this->actions->set_status(
            $threadid,
            $status
        );

        $this->event_logger()->status_changed(
            $threadid,
            $status
        );
    }

    public function set_priority(
        int $threadid,
        string $priority
    ): void {
        $this->assert_thread(
            $threadid
        );

        if (
            !InboxPriority::is_valid(
                $priority
            )
        ) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox priority.'
            );
        }

        $this->actions->set_priority(
            $threadid,
            $priority
        );

        $this->event_logger()->priority_changed(
            $threadid,
            $priority
        );
    }

    public function mark_read(
        int $threadid,
        bool $read
    ): void {
        $thread = $this->assert_thread(
            $threadid
        );

        $account = $this->accounts->find(
            (int)$thread->accountid
        );

        if (!$account) {
            throw new \moodle_exception(
                'crm_inbox_account_not_found',
                'local_subscriptions'
            );
        }

        foreach (
            $this->read
                ->get_remote_messages_for_thread(
                    $threadid
                ) as $message
        ) {
            try {
                $this->connector->mark_as_read(
                    $account,
                    (string)$message->folder,
                    (string)$message->provideruid,
                    $read
                );
            } catch (\Throwable $exception) {
                debugging(
                    'CRM Inbox IMAP read state failed: ' .
                    $exception->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        $this->actions->mark_read(
            $threadid,
            $read
        );
    }

    public function archive(
        int $threadid
    ): void {
        $this->move(
            $threadid,
            'archive'
        );

        $this->actions->set_status(
            $threadid,
            'closed'
        );

        $this->event_logger()->status_changed(
            $threadid,
            'closed'
        );
    }

    public function trash(
        int $threadid
    ): void {
        $this->move(
            $threadid,
            'trash'
        );

        $this->actions->mark_locally_deleted(
            $threadid
        );
    }

    public function delete_locally(
        int $threadid
    ): void {
        $this->assert_thread($threadid);

        $this->actions->mark_locally_deleted(
            $threadid
        );
    }

    private function move(
        int $threadid,
        string $foldertype
    ): void {
        $thread = $this->assert_thread(
            $threadid
        );

        $account = $this->accounts->find(
            (int)$thread->accountid
        );

        if (!$account) {
            throw new \moodle_exception(
                'crm_inbox_account_not_found',
                'local_subscriptions'
            );
        }

        $targetfolder = trim(
            (string)(
                $account->configuration['folders'][
                    $foldertype
                ] ?? ''
            )
        );

        if ($targetfolder === '') {
            throw new \moodle_exception(
                'crm_inbox_folder_not_configured',
                'local_subscriptions',
                '',
                $foldertype
            );
        }

        foreach (
            $this->read
                ->get_remote_messages_for_thread(
                    $threadid
                ) as $message
        ) {
            if (
                (string)$message->folder ===
                $targetfolder
            ) {
                continue;
            }

            $this->connector->move_message(
                $account,
                (string)$message->folder,
                (string)$message->provideruid,
                $targetfolder
            );

            /*
             * Une opération MOVE IMAP peut attribuer un nouvel UID
             * dans le dossier cible.
             *
             * L’ancien emplacement ne doit donc plus être utilisé
             * pour une action ou un téléchargement ultérieur.
             */
            $this->remotes->deactivate(
                (int)$message->remoteid
            );
        }

        $this->actions->set_thread_folder(
            $threadid,
            $targetfolder
        );
    }

    private function event_logger():
        InboxAdminEventLogger {
        return $this->events
            ?? new InboxAdminEventLogger(
                $this->read
            );
    }

    private function assert_thread(
        int $threadid
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

        return $thread;
    }
}