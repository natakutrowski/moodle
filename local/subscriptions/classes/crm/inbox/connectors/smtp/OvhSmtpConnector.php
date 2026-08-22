<?php

namespace local_subscriptions\crm\inbox\connectors\smtp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\contracts\InboxOutboundConnectorInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\dto\InboxReplyRequest;
use local_subscriptions\crm\inbox\dto\InboxSendResult;
use local_subscriptions\crm\inbox\exception\InboxConnectorException;
use local_subscriptions\crm\inbox\connectors\imap\ImapMailbox;
use local_subscriptions\crm\inbox\dto\InboxFolder;
use local_subscriptions\crm\inbox\services\InboxFolderResolver;

final class OvhSmtpConnector implements
    InboxOutboundConnectorInterface {

    public function __construct(
        private readonly InboxCredentialStoreInterface $credentials
    ) {
    }

    public function test_connection(
        InboxAccount $account
    ): void {
        $mailer = $this->mailer($account);

        try {
            if (!$mailer->smtpConnect()) {
                throw new InboxConnectorException(
                    'Unable to connect to the OVH SMTP server.'
                );
            }
        } finally {
            $mailer->smtpClose();
        }
    }

    public function send(
        InboxAccount $account,
        InboxReplyRequest $request
    ): InboxSendResult {
        $mailer = $this->mailer($account);

        try {
            $mailer->setFrom(
                $account->email,
                $account->name,
                false
            );

            foreach ($request->to as $email) {
                $mailer->addAddress($email);
            }

            foreach ($request->cc as $email) {
                $mailer->addCC($email);
            }

            foreach ($request->bcc as $email) {
                $mailer->addBCC($email);
            }

            $mailer->Subject = $request->subject;
            $mailer->AltBody = $request->bodytext;

            if (
                $request->bodyhtml !== null &&
                trim($request->bodyhtml) !== ''
            ) {
                $mailer->isHTML(true);
                $mailer->Body = $request->bodyhtml;
            } else {
                $mailer->isHTML(false);
                $mailer->Body = $request->bodytext;
            }

            foreach ($request->attachments as $attachment) {
                if (
                    $attachment->inline
                    && $attachment->contentid !== null
                ) {
                    $mailer->addStringEmbeddedImage(
                        $attachment->content,
                        trim(
                            $attachment->contentid,
                            '<> '
                        ),
                        $attachment->filename,
                        \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                        $attachment->mimetype
                    );

                    continue;
                }

                $mailer->addStringAttachment(
                    $attachment->content,
                    $attachment->filename,
                    \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                    $attachment->mimetype
                );
            }

            if ($request->inreplyto !== null) {
                $mailer->addCustomHeader(
                    'In-Reply-To',
                    '<' . trim(
                        $request->inreplyto,
                        '<> '
                    ) . '>'
                );
            }

            if ($request->references) {
                $references = array_map(
                    static fn(string $reference): string =>
                        '<' . trim(
                            $reference,
                            '<> '
                        ) . '>',
                    $request->references
                );

                $mailer->addCustomHeader(
                    'References',
                    implode(' ', $references)
                );
            }

            /*
             * Build the MIME once, send that exact representation through
             * SMTP, then append the same bytes to IMAP Sent. This mirrors
             * Thunderbird/Roundcube behaviour and guarantees that Message-ID,
             * CID images and attachments are identical in both places.
             */
            if (!$mailer->preSend()) {
                return new InboxSendResult(
                    false,
                    null,
                    null,
                    $mailer->ErrorInfo
                );
            }

            $rawmime = $mailer->getSentMIMEMessage();

            $success = $mailer->postSend();

            if (!$success) {
                return new InboxSendResult(
                    false,
                    null,
                    null,
                    $mailer->ErrorInfo
                );
            }

            $sentfolder = null;
            $sentcopyerror = null;

            try {
                $sentfolder =
                    $this->append_sent_copy(
                        $account,
                        $rawmime
                    );
            } catch (\Throwable $exception) {
                /*
                 * The SMTP delivery has already succeeded. Never report the
                 * whole send as failed here or an operator may resend the
                 * message and create a duplicate at the recipient.
                 */
                $sentcopyerror =
                    $exception->getMessage();

                debugging(
                    'CRM Inbox sent-copy append failed: ' .
                    $sentcopyerror,
                    DEBUG_DEVELOPER
                );
            }

            return new InboxSendResult(
                true,
                trim(
                    (string)$mailer->getLastMessageID(),
                    '<> '
                ) ?: null,
                time(),
                null,
                $sentfolder,
                $sentcopyerror
            );
        } catch (\Throwable $exception) {
            return new InboxSendResult(
                false,
                null,
                null,
                $exception->getMessage()
            );
        }
    }

    private function append_sent_copy(
        InboxAccount $account,
        string $rawmime
    ): string {
        if (!extension_loaded('imap')) {
            throw new InboxConnectorException(
                'PHP IMAP extension is unavailable for Sent copy.'
            );
        }

        if ($account->credentialkey === null) {
            throw new InboxConnectorException(
                'Inbox account has no credential key.'
            );
        }

        $configuration =
            $account->configuration['imap']
                ?? [];

        $host = trim(
            (string)($configuration['host'] ?? '')
        );

        $port = (int)(
            $configuration['port'] ?? 993
        );

        $encryption = trim(
            (string)(
                $configuration['encryption']
                    ?? 'ssl'
            )
        );

        $validatecertificate = (bool)(
            $configuration['validatecertificate']
                ?? true
        );

        if ($host === '') {
            throw new InboxConnectorException(
                'IMAP host is missing for Sent copy.'
            );
        }

        $mailbox = new ImapMailbox(
            $host,
            $port,
            $encryption,
            $validatecertificate
        );

        $defaultfolder = trim(
            (string)(
                $configuration['folder']
                    ?? 'INBOX'
            )
        );

        if ($defaultfolder === '') {
            $defaultfolder = 'INBOX';
        }

        $stream = @imap_open(
            $mailbox->folder(
                $defaultfolder
            ),
            $this->credentials->get_username(
                $account->credentialkey
            ),
            $this->credentials->get_password(
                $account->credentialkey
            )
        );

        if ($stream === false) {
            throw new InboxConnectorException(
                'Unable to open IMAP mailbox for Sent copy: ' .
                implode(
                    ' | ',
                    imap_errors() ?: []
                )
            );
        }

        try {
            $remote = imap_getmailboxes(
                $stream,
                $mailbox->server(),
                '*'
            );

            if ($remote === false) {
                throw new InboxConnectorException(
                    'Unable to discover IMAP folders for Sent copy.'
                );
            }

            $folders = [];

            foreach ($remote as $item) {
                $fullname =
                    (string)$item->name;

                $server =
                    $mailbox->server();

                $name = str_starts_with(
                    $fullname,
                    $server
                )
                    ? substr(
                        $fullname,
                        strlen($server)
                    )
                    : $fullname;

                $folders[] = new InboxFolder(
                    imap_utf7_decode($name),
                    (string)(
                        $item->delimiter
                            ?? '/'
                    ),
                    []
                );
            }

            $resolver =
                new InboxFolderResolver();

            $resolved = $resolver->resolve(
                $folders,
                is_array(
                    $account->configuration['folders']
                        ?? null
                )
                    ? $account->configuration['folders']
                    : []
            );

            $sentfolder = trim(
                (string)(
                    $resolved['sent']
                        ?? ''
                )
            );

            if ($sentfolder === '') {
                throw new InboxConnectorException(
                    'Unable to resolve the IMAP Sent folder.'
                );
            }

            $appended = imap_append(
                $stream,
                $mailbox->folder(
                    $sentfolder
                ),
                $rawmime,
                '\\Seen'
            );

            if (!$appended) {
                throw new InboxConnectorException(
                    'Unable to append message to IMAP Sent: ' .
                    implode(
                        ' | ',
                        imap_errors() ?: []
                    )
                );
            }

            return $sentfolder;
        } finally {
            @imap_close($stream);
        }
    }

    private function mailer(
        InboxAccount $account
    ): \PHPMailer\PHPMailer\PHPMailer {
        if ($account->credentialkey === null) {
            throw new InboxConnectorException(
                'Inbox account has no credential key.'
            );
        }

        $configuration =
            $account->configuration['smtp']
                ?? [];

        $host = trim(
            (string)($configuration['host'] ?? '')
        );

        $port = (int)(
            $configuration['port'] ?? 465
        );

        if ($host === '') {
            throw new InboxConnectorException(
                'SMTP host is missing.'
            );
        }

        $mailer = get_mailer();

        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = true;
        $mailer->Username =
            $this->credentials->get_username(
                $account->credentialkey
            );
        $mailer->Password =
            $this->credentials->get_password(
                $account->credentialkey
            );
        $mailer->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::
                ENCRYPTION_SMTPS;
        $mailer->SMTPAutoTLS = false;
        $mailer->CharSet = 'UTF-8';
        $mailer->Timeout = 30;

        return $mailer;
    }
}