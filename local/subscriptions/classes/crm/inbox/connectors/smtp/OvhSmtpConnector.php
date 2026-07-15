<?php

namespace local_subscriptions\crm\inbox\connectors\smtp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\contracts\InboxOutboundConnectorInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\dto\InboxReplyRequest;
use local_subscriptions\crm\inbox\dto\InboxSendResult;
use local_subscriptions\crm\inbox\exception\InboxConnectorException;

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

            $success = $mailer->send();

            if (!$success) {
                return new InboxSendResult(
                    false,
                    null,
                    null,
                    $mailer->ErrorInfo
                );
            }

            return new InboxSendResult(
                true,
                trim(
                    (string)$mailer->getLastMessageID(),
                    '<> '
                ) ?: null,
                time()
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