<?php

namespace local_subscriptions\crm\inbox\connectors\imap;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxAttachmentFetcherInterface;
use local_subscriptions\crm\inbox\contracts\InboxConnectorInterface;
use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\domain\InboxMessageDirection;
use local_subscriptions\crm\inbox\domain\InboxMessageStatus;
use local_subscriptions\crm\inbox\domain\InboxParticipantType;
use local_subscriptions\crm\inbox\dto\InboxFolder;
use local_subscriptions\crm\inbox\dto\InboxMessageData;
use local_subscriptions\crm\inbox\dto\InboxParticipantData;
use local_subscriptions\crm\inbox\dto\InboxRemoteMessageState;
use local_subscriptions\crm\inbox\dto\InboxRemoteStateSnapshot;
use local_subscriptions\crm\inbox\dto\InboxSyncPage;
use local_subscriptions\crm\inbox\exception\InboxConnectorException;
use local_subscriptions\crm\inbox\sync\InboxSyncCursor;

final class OvhImapConnector implements
    InboxConnectorInterface,
    InboxAttachmentFetcherInterface {

    private readonly ImapErrorCollector $errors;

    public function __construct(
        private readonly InboxCredentialStoreInterface $credentials,
        private readonly ImapMimeParser $mimeparser,
        ?ImapErrorCollector $errors = null
    ) {
        $this->errors =
            $errors ?? new ImapErrorCollector();
    }

    public function test_connection(
        InboxAccount $account
    ): void {
        $stream = null;

        try {
            $stream = $this->open(
                $account,
                $this->default_folder($account)
            );

            $check = imap_check($stream);

            if ($check === false) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to verify the IMAP mailbox.',
                        [
                            'account' => $account->email,
                            'folder' =>
                                $this->default_folder($account),
                        ]
                    )
                );
            }
        } finally {
            $this->close($stream);
        }
    }

    public function list_folders(
        InboxAccount $account
    ): array {
        $stream = $this->open(
            $account,
            $this->default_folder($account)
        );

        try {
            $mailbox = $this->mailbox($account);

            $items = imap_getmailboxes(
                $stream,
                $mailbox->server(),
                '*'
            );

            if ($items === false) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to list IMAP folders.',
                        [
                            'account' => $account->email,
                            'host' => $mailbox->server(),
                        ]
                    )
                );
            }

            $folders = [];

            foreach ($items as $item) {
                $fullname = (string)$item->name;
                $server = $mailbox->server();

                $name = str_starts_with(
                    $fullname,
                    $server
                )
                    ? substr(
                        $fullname,
                        strlen($server)
                    )
                    : $fullname;

                $name = imap_utf7_decode($name);

                $attributes =
                    $this->folder_attributes(
                        (int)$item->attributes
                    );

                $folders[] = new InboxFolder(
                    $name,
                    (string)($item->delimiter ?? '/'),
                    $attributes,
                    $this->special_use_from_name($name)
                );
            }

            return $folders;
        } finally {
            $this->close($stream);
        }
    }

    public function fetch_page(
        InboxAccount $account,
        string $folder,
        ?string $cursor,
        int $limit
    ): InboxSyncPage {
        $stream = $this->open(
            $account,
            $folder
        );

        try {
            $status = imap_status(
                $stream,
                $this->mailbox($account)->folder($folder),
                SA_UIDVALIDITY
            );

            if ($status === false) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to read IMAP UIDVALIDITY.',
                        [
                            'account' => $account->email,
                            'folder' => $folder,
                        ]
                    )
                );
            }

            $uidvalidity = (string)$status->uidvalidity;
            $synccursor = InboxSyncCursor::decode($cursor);

            $lastuid = (
                $synccursor->uidvalidity === null ||
                $synccursor->uidvalidity === $uidvalidity
            )
                ? $synccursor->lastuid
                : 0;

            $uids = imap_search(
                $stream,
                'ALL',
                SE_UID
            );

            if ($uids === false) {
                $errors = $this->errors->collect();

                /*
                * imap_search() renvoie également false lorsqu’aucun
                * message ne correspond. Une absence d’erreur signifie
                * donc simplement que le dossier est vide.
                */
                if ($errors) {
                    throw new InboxConnectorException(
                        implode(' ', [
                            'Unable to search IMAP messages.',
                            '[account=' . $account->email . ',',
                            'folder=' . $folder . ']',
                            implode(' | ', $errors),
                        ])
                    );
                }

                return new InboxSyncPage(
                    [],
                    $cursor,
                    false
                );
            }

            $uids = array_values(
                array_unique(
                    array_map('intval', $uids)
                )
            );

            if ($lastuid > 0) {
                $uids = array_values(
                    array_filter(
                        $uids,
                        static fn(int $uid): bool =>
                            $uid > $lastuid
                    )
                );
            }

            sort($uids, SORT_NUMERIC);

            $limit = max(1, min(200, $limit));

            /*
             * Commerce 7.95O1.4 — live sync is recent-first.
             *
             * A newly connected mailbox must become useful immediately:
             * do not force the CRM to walk years of history before it can
             * see mail received today.
             *
             * - no cursor yet: bootstrap from the newest available UIDs;
             * - cursor present: keep the normal forward-only incremental
             *   behaviour (UID > last imported UID).
             *
             * Older messages deliberately remain outside the live cursor.
             * Historical backfill, when needed, must be an explicit workflow
             * rather than something the normal cron keeps consuming.
             */
            $initialsync = $cursor === null
                || trim($cursor) === '';

            $selected = $initialsync
                ? array_slice($uids, -$limit)
                : array_slice($uids, 0, $limit);

            $messages = [];

            foreach ($selected as $uid) {
                $messages[] = $this->message(
                    $account,
                    $stream,
                    $folder,
                    $uidvalidity,
                    $uid
                );
            }

            $nextcursor = (
                new InboxSyncCursor(
                    $uidvalidity,
                    $selected
                        ? (int)max($selected)
                        : $lastuid
                )
            )->encode();

            return new InboxSyncPage(
                $messages,
                $nextcursor,
                /*
                 * On bootstrap, older UIDs are intentionally ignored by the
                 * live synchronisation. Reporting them as "more" would make
                 * manual sync / cron continue backfilling history forever.
                 */
                !$initialsync
                    && count($uids) > count($selected)
            );
        } finally {
            $this->close($stream);
        }
    }

    public function inspect_messages(
        InboxAccount $account,
        string $folder,
        array $provideruids
    ): InboxRemoteStateSnapshot {
        $stream = $this->open(
            $account,
            $folder
        );

        try {
            $status = imap_status(
                $stream,
                $this->mailbox($account)->folder($folder),
                SA_UIDVALIDITY
            );

            if ($status === false) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to read IMAP UIDVALIDITY.',
                        [
                            'account' => $account->email,
                            'folder' => $folder,
                        ]
                    )
                );
            }

            $uidvalidity = (string)$status->uidvalidity;
            $uids = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(mixed $uid): int =>
                                max(0, (int)$uid),
                            $provideruids
                        ),
                        static fn(int $uid): bool =>
                            $uid > 0
                    )
                )
            );

            if ($uids === []) {
                return new InboxRemoteStateSnapshot(
                    $folder,
                    $uidvalidity,
                    []
                );
            }

            sort($uids, SORT_NUMERIC);
            $states = [];

            /*
             * PHP's imap_search() criteria parser does not support an IMAP
             * "UID ..." criterion even though the underlying protocol has a
             * UID SEARCH command. Ask for the lightweight UID inventory once,
             * then intersect it locally with the CRM-known UIDs.
             *
             * This also handles messages that disappeared from the folder
             * without turning a missing UID into a connector error.
             */
            $this->errors->clear();

            $remoteuids = imap_search(
                $stream,
                'ALL',
                SE_UID
            );

            if ($remoteuids === false) {
                $errors = $this->errors->collect();

                if ($errors) {
                    throw new InboxConnectorException(
                        implode(' ', [
                            'Unable to inspect IMAP message state.',
                            '[account=' . $account->email . ',',
                            'folder=' . $folder . ']',
                            implode(' | ', $errors),
                        ])
                    );
                }

                $remoteuids = [];
            }

            $remoteuidset = array_fill_keys(
                array_map(
                    'intval',
                    $remoteuids
                ),
                true
            );

            foreach (array_chunk($uids, 100) as $chunk) {
                $existinguids = array_values(
                    array_filter(
                        $chunk,
                        static fn(int $uid): bool =>
                            isset($remoteuidset[$uid])
                    )
                );

                if ($existinguids === []) {
                    continue;
                }

                $overview = imap_fetch_overview(
                    $stream,
                    implode(',', $existinguids),
                    FT_UID
                );

                if ($overview === false) {
                    throw new InboxConnectorException(
                        $this->errors->message(
                            'Unable to inspect IMAP message state.',
                            [
                                'account' => $account->email,
                                'folder' => $folder,
                            ]
                        )
                    );
                }

                foreach ($overview as $item) {
                    $uid = (int)($item->uid ?? 0);

                    if ($uid <= 0) {
                        continue;
                    }

                    $states[] = new InboxRemoteMessageState(
                        $folder,
                        $uidvalidity,
                        (string)$uid,
                        $this->clean_message_id(
                            $item->message_id ?? null
                        ),
                        self::overview_is_read($item),
                        (bool)($item->answered ?? false),
                        (bool)($item->flagged ?? false),
                        (bool)($item->deleted ?? false),
                        (bool)($item->draft ?? false)
                    );
                }
            }

            return new InboxRemoteStateSnapshot(
                $folder,
                $uidvalidity,
                $states
            );
        } finally {
            $this->close($stream);
        }
    }

    public function locate_message(
        InboxAccount $account,
        array $folders,
        string $providermessageid
    ): ?InboxRemoteMessageState {
        $providermessageid = $this->clean_message_id(
            $providermessageid
        );

        if ($providermessageid === null) {
            return null;
        }

        $searchvalues = [
            '<' . $providermessageid . '>',
            $providermessageid,
        ];

        foreach (
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(mixed $folder): string =>
                                trim((string)$folder),
                            $folders
                        )
                    )
                )
            ) as $folder
        ) {
            $stream = null;

            try {
                $stream = $this->open(
                    $account,
                    $folder
                );

                $status = imap_status(
                    $stream,
                    $this->mailbox($account)->folder($folder),
                    SA_UIDVALIDITY
                );

                if ($status === false) {
                    continue;
                }

                $uids = false;

                foreach ($searchvalues as $searchvalue) {
                    $escaped = str_replace(
                        ['\\', '"'],
                        ['\\\\', '\"'],
                        $searchvalue
                    );

                    $this->errors->clear();
                    $uids = imap_search(
                        $stream,
                        'HEADER Message-ID "' . $escaped . '"',
                        SE_UID
                    );

                    if ($uids !== false) {
                        break;
                    }

                    $this->errors->clear();
                }

                if ($uids === false) {
                    continue;
                }

                $uids = array_values(
                    array_unique(
                        array_map('intval', $uids)
                    )
                );

                rsort($uids, SORT_NUMERIC);

                foreach ($uids as $uid) {
                    if ($uid <= 0) {
                        continue;
                    }

                    $overview = imap_fetch_overview(
                        $stream,
                        (string)$uid,
                        FT_UID
                    );

                    if (!$overview || !isset($overview[0])) {
                        continue;
                    }

                    $item = $overview[0];

                    return new InboxRemoteMessageState(
                        $folder,
                        (string)$status->uidvalidity,
                        (string)$uid,
                        $this->clean_message_id(
                            $item->message_id ?? null
                        ),
                        self::overview_is_read($item),
                        (bool)($item->answered ?? false),
                        (bool)($item->flagged ?? false),
                        (bool)($item->deleted ?? false),
                        (bool)($item->draft ?? false)
                    );
                }
            } catch (\Throwable $exception) {
                debugging(
                    'CRM Inbox Message-ID location probe failed: ' .
                    $exception->getMessage(),
                    DEBUG_DEVELOPER
                );
            } finally {
                $this->close($stream);
            }
        }

        return null;
    }

    public function move_message(
        InboxAccount $account,
        string $sourcefolder,
        string $provideruid,
        string $targetfolder
    ): void {
        $stream = $this->open(
            $account,
            $sourcefolder,
            false
        );

        try {
            $success = imap_mail_move(
                $stream,
                $provideruid,
                $targetfolder,
                CP_UID
            );

            if (!$success) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to move IMAP message.',
                        [
                            'account' => $account->email,
                            'source' => $sourcefolder,
                            'target' => $targetfolder,
                            'uid' => $provideruid,
                        ]
                    )
                );
            }

            if (!imap_expunge($stream)) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to expunge the IMAP mailbox.',
                        [
                            'account' => $account->email,
                            'folder' => $sourcefolder,
                        ]
                    )
                );
            }
        } finally {
            $this->close($stream);
        }
    }

    public function mark_as_read(
        InboxAccount $account,
        string $folder,
        string $provideruid,
        bool $read
    ): void {
        $stream = $this->open(
            $account,
            $folder,
            false
        );

        try {
            $success = $read
                ? imap_setflag_full(
                    $stream,
                    $provideruid,
                    '\\Seen',
                    ST_UID
                )
                : imap_clearflag_full(
                    $stream,
                    $provideruid,
                    '\\Seen',
                    ST_UID
                );

            if (!$success) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to update the IMAP read state.',
                        [
                            'account' => $account->email,
                            'folder' => $folder,
                            'uid' => $provideruid,
                            'read' => $read ? 'yes' : 'no',
                        ]
                    )
                );
            }
        } finally {
            $this->close($stream);
        }
    }

    public function fetch_attachment(
        InboxAccount $account,
        string $folder,
        string $provideruid,
        string $providerattachmentid
    ): mixed {
        $stream = $this->open(
            $account,
            $folder
        );

        try {
            $structure = imap_fetchstructure(
                $stream,
                (int)$provideruid,
                FT_UID
            );

            if ($structure === false) {
                throw new InboxConnectorException(
                    $this->errors->message(
                        'Unable to read the attachment structure.',
                        [
                            'account' => $account->email,
                            'folder' => $folder,
                            'uid' => $provideruid,
                        ]
                    )
                );
            }

            $part = $this->find_part(
                $structure,
                $providerattachmentid
            );

            if ($part === null) {
                throw new InboxConnectorException(
                    implode(' ', [
                        'IMAP attachment part not found.',
                        '[account=' . $account->email . ',',
                        'folder=' . $folder . ',',
                        'uid=' . $provideruid . ',',
                        'part=' . $providerattachmentid . ']',
                    ])
                );
            }

            return $this->mimeparser
                ->fetch_part_content(
                    $stream,
                    (int)$provideruid,
                    $providerattachmentid,
                    $part
                );
        } finally {
            $this->close($stream);
        }
    }

    private function message(
        InboxAccount $account,
        mixed $stream,
        string $folder,
        string $uidvalidity,
        int $uid
    ): InboxMessageData {
        $overview = imap_fetch_overview(
            $stream,
            (string)$uid,
            FT_UID
        );

        if (!$overview || !isset($overview[0])) {
            throw new InboxConnectorException(
                $this->errors->message(
                    'Unable to fetch the IMAP message overview.',
                    [
                        'folder' => $folder,
                        'uid' => $uid,
                    ]
                )
            );
        }

        $overview = $overview[0];

        $structure = imap_fetchstructure(
            $stream,
            $uid,
            FT_UID
        );

        if ($structure === false) {
            throw new InboxConnectorException(
                $this->errors->message(
                    'Unable to fetch the IMAP message structure.',
                    [
                        'folder' => $folder,
                        'uid' => $uid,
                    ]
                )
            );
        }

        $parsed = $this->mimeparser->parse(
            $stream,
            $uid,
            $structure
        );

        $headersource = imap_fetchheader(
            $stream,
            $uid,
            FT_UID | FT_PREFETCHTEXT
        );

        if ($headersource === false) {
            throw new InboxConnectorException(
                $this->errors->message(
                    'Unable to fetch the IMAP message headers.',
                    [
                        'folder' => $folder,
                        'uid' => $uid,
                    ]
                )
            );
        }        

        $header = imap_rfc822_parse_headers(
            $headersource
        );

        if (!is_object($header)) {
            throw new InboxConnectorException(
                implode(' ', [
                    'Unable to parse the IMAP message headers.',
                    '[folder=' . $folder . ',',
                    'uid=' . $uid . ']',
                ])
            );
        }

        $participants = [];

        $participants = array_merge(
            $participants,
            $this->participants(
                $header->from ?? [],
                InboxParticipantType::FROM
            ),
            $this->participants(
                $header->to ?? [],
                InboxParticipantType::TO
            ),
            $this->participants(
                $header->cc ?? [],
                InboxParticipantType::CC
            ),
            $this->participants(
                $header->bcc ?? [],
                InboxParticipantType::BCC
            ),
            $this->participants(
                $header->reply_to ?? [],
                InboxParticipantType::REPLY_TO
            )
        );

        $fromaccount = false;

        foreach ($participants as $participant) {
            if (
                $participant->type ===
                    InboxParticipantType::FROM &&
                $participant->normalizedemail ===
                    \core_text::strtolower(
                        trim($account->email)
                    )
            ) {
                $fromaccount = true;
                break;
            }
        }

        $messageid = $this->clean_message_id(
            $overview->message_id
                ?? $header->message_id
                ?? null
        );

        $inreplyto = $this->clean_message_id(
            $header->in_reply_to ?? null
        );

        $references = $this->references(
            $headersource
        );

        $subject = isset($overview->subject)
            ? $this->decode_header(
                (string)$overview->subject
            )
            : null;

        $date = $overview->udate
            ?? strtotime(
                (string)($overview->date ?? '')
            )
            ?: time();

        $headers = [
            'raw' => (string)$headersource,
            'size' => (int)($overview->size ?? 0),
            'recent' => (bool)($overview->recent ?? false),
            'flagged' => (bool)($overview->flagged ?? false),
            'answered' => (bool)($overview->answered ?? false),
            'deleted' => (bool)($overview->deleted ?? false),
            'draft' => (bool)($overview->draft ?? false),
        ];

        $providerthreadid =
            $references[0]
            ?? $inreplyto
            ?? $messageid;

        $participantemails = array_map(
            static fn(
                InboxParticipantData $participant
            ): string => $participant->normalizedemail,
            $participants
        );

        sort($participantemails, SORT_STRING);

        $checksum = hash(
            'sha256',
            implode('|', [
                $messageid ?? '',
                \core_text::strtolower(
                    trim($subject ?? '')
                ),
                (string)$date,
                (string)($overview->size ?? 0),
                implode(',', $participantemails),
            ])
        ); 
        
        $direction = $fromaccount
            ? InboxMessageDirection::OUTBOUND
            : InboxMessageDirection::INBOUND;

        $status = $fromaccount
            ? InboxMessageStatus::SENT
            : InboxMessageStatus::RECEIVED;

        $isread =
            $direction === InboxMessageDirection::OUTBOUND
                ? true
                : self::overview_is_read($overview);

        return new InboxMessageData(
            $folder,
            $uidvalidity,
            (string)$uid,
            $messageid,
            $inreplyto,
            $providerthreadid,
            $direction,
            $status,
            $subject,
            $parsed['bodytext'],
            $parsed['bodyhtml'],
            $headers,
            $inreplyto,
            $references,
            (int)$date,
            null,
            $isread,
            $participants,
            $parsed['attachments'],
            $checksum
        );
    }

    /**
     * Returns the IMAP \Seen state exposed by imap_fetch_overview().
     *
     * PHP's IMAP overview object exposes `seen`, not `unseen`. The previous
     * O1 implementation read a non-existent `unseen` property and therefore
     * defaulted every imported message to "read".
     */
    private static function overview_is_read(
        object $overview
    ): bool {
        return (bool)($overview->seen ?? false);
    }

    private function participants(
        mixed $addresses,
        string $type
    ): array {
        if (!is_array($addresses)) {
            return [];
        }

        $participants = [];

        foreach ($addresses as $address) {
            $mailbox = (string)(
                $address->mailbox ?? ''
            );
            $host = (string)(
                $address->host ?? ''
            );

            if (
                $mailbox === '' ||
                $host === '' ||
                $host === '.SYNTAX-ERROR.'
            ) {
                continue;
            }

            $email = \core_text::strtolower(
                trim($mailbox . '@' . $host)
            );

            if (!validate_email($email)) {
                continue;
            }

            $name = isset($address->personal)
                ? $this->decode_header(
                    (string)$address->personal
                )
                : null;

            $participants[] =
                new InboxParticipantData(
                    $type,
                    $email,
                    $email,
                    $name
                );
        }

        return $participants;
    }

    private function open(
        InboxAccount $account,
        string $folder,
        bool $readonly = true
    ): mixed {
        $this->assert_extension();

        if ($account->credentialkey === null) {
            throw new InboxConnectorException(
                implode(' ', [
                    'Inbox account has no credential key.',
                    '[account=' . $account->email . ']',
                ])
            );
        }

        $folder = trim($folder);

        if ($folder === '') {
            throw new InboxConnectorException(
                implode(' ', [
                    'The IMAP folder is empty.',
                    '[account=' . $account->email . ']',
                ])
            );
        }

        $mailbox = $this->mailbox($account);
        $mailboxpath = $mailbox->folder($folder);

        $this->errors->clear();

        $stream = @imap_open(
            $mailboxpath,
            $this->credentials->get_username(
                $account->credentialkey
            ),
            $this->credentials->get_password(
                $account->credentialkey
            ),
            $readonly ? OP_READONLY : 0,
            1
        );

        if ($stream === false) {
            throw new InboxConnectorException(
                $this->errors->message(
                    'Unable to open the IMAP connection.',
                    [
                        'account' => $account->email,
                        'mailbox' => $mailboxpath,
                        'readonly' =>
                            $readonly ? 'yes' : 'no',
                    ]
                )
            );
        }

        return $stream;
    }

    private function close(mixed &$stream): void {
        if ($stream === null || $stream === false) {
            return;
        }

        try {
            @imap_close($stream);
        } finally {
            $stream = null;

            /*
            * Évite que les alertes de fermeture polluent
            * l’opération IMAP suivante.
            */
            $this->errors->clear();
        }
    }

    private function mailbox(
        InboxAccount $account
    ): ImapMailbox {
        $configuration =
            $account->configuration['imap']
                ?? [];

        return new ImapMailbox(
            (string)($configuration['host'] ?? ''),
            (int)($configuration['port'] ?? 993),
            (string)(
                $configuration['encryption'] ?? 'ssl'
            ),
            (bool)(
                $configuration[
                    'validatecertificate'
                ] ?? true
            )
        );
    }

    private function default_folder(
        InboxAccount $account
    ): string {
        return (string)(
            $account->configuration['imap']['folder']
                ?? 'INBOX'
        );
    }

    private function assert_extension(): void {
        if (!extension_loaded('imap')) {
            throw new InboxConnectorException(
                get_string(
                    'crm_inbox_imap_extension_missing',
                    'local_subscriptions'
                )
            );
        }
    }


    private function folder_attributes(
        int $attributes
    ): array {
        $map = [
            LATT_NOINFERIORS => 'noinferiors',
            LATT_NOSELECT => 'noselect',
            LATT_MARKED => 'marked',
            LATT_UNMARKED => 'unmarked',
        ];

        $result = [];

        foreach ($map as $flag => $name) {
            if (($attributes & $flag) === $flag) {
                $result[] = $name;
            }
        }

        return $result;
    }

    private function special_use_from_name(
        string $name
    ): ?string {
        $normalized = \core_text::strtolower(
            trim($name)
        );

        $basename = preg_replace(
            '#^.*[/.]#u',
            '',
            $normalized
        ) ?? $normalized;

        $aliases = [
            'inbox' => [
                'inbox',
                'boîte de réception',
                'boite de reception',
                'входящие',
            ],
            'sent' => [
                'sent',
                'sent items',
                'sent messages',
                'messages envoyés',
                'messages envoyes',
                'envoyés',
                'envoyes',
                'отправленные',
            ],
            'drafts' => [
                'drafts',
                'brouillons',
                'черновики',
            ],
            'archive' => [
                'archive',
                'archives',
                'архив',
            ],
            'trash' => [
                'trash',
                'deleted',
                'deleted items',
                'corbeille',
                'корзина',
                'удалённые',
                'удаленные',
            ],
        ];

        foreach ($aliases as $type => $names) {
            foreach ($names as $alias) {
                if (
                    $normalized === $alias ||
                    $basename === $alias
                ) {
                    return $type;
                }
            }
        }

        return null;
    }

    private function references(string $header): array {
        if (
            !preg_match(
                '/^References:\s*(.+(?:\r?\n[ \t].+)*)/mi',
                $header,
                $matches
            )
        ) {
            return [];
        }

        preg_match_all(
            '/<[^>]+>/',
            $matches[1],
            $ids
        );

        return array_values(
            array_unique(
                array_map(
                    fn(string $id): string =>
                        trim($id, '<> '),
                    $ids[0] ?? []
                )
            )
        );
    }

    private function clean_message_id(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string)$value,
            " \t\n\r\0\x0B<>"
        );

        return $value !== ''
            ? $value
            : null;
    }

    private function decode_header(
        string $value
    ): string {
        $parts = imap_mime_header_decode($value);
        $decoded = '';

        foreach ($parts as $part) {
            $text = (string)($part->text ?? '');
            $charset = (string)(
                $part->charset ?? 'default'
            );

            if (
                $charset !== 'default' &&
                strcasecmp($charset, 'UTF-8') !== 0 &&
                function_exists('mb_convert_encoding')
            ) {
                try {
                    $text = mb_convert_encoding(
                        $text,
                        'UTF-8',
                        $charset
                    );
                } catch (\Throwable $exception) {
                    // Keep original text.
                }
            }

            $decoded .= $text;
        }

        return trim($decoded);
    }

    private function find_part(
        object $structure,
        string $partnumber
    ): ?object {
        $indexes = array_map(
            static fn(string $value): int =>
                max(0, ((int)$value) - 1),
            explode('.', $partnumber)
        );

        $current = $structure;

        foreach ($indexes as $index) {
            if (
                empty($current->parts) ||
                !isset($current->parts[$index])
            ) {
                return null;
            }

            $current = $current->parts[$index];
        }

        return $current;
    }
}