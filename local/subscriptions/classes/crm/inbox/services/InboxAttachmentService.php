<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxAttachmentFetcherInterface;
use local_subscriptions\crm\inbox\contracts\InboxAttachmentStorageInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\repositories\InboxAttachmentRepository;

final class InboxAttachmentService {

    public function __construct(
        private readonly InboxAttachmentRepository $repository,
        private readonly InboxAttachmentStorageInterface $storage,
        private readonly InboxAttachmentFetcherInterface $fetcher
    ) {
    }

    public function download(
        InboxAccount $account,
        int $attachmentid,
        string $folder,
        string $provideruid
    ): void {
        $attachment = $this->repository->find(
            $attachmentid
        );

        if (!$attachment) {
            throw new \invalid_parameter_exception(
                'Inbox attachment not found.'
            );
        }

        $providerattachmentid = trim(
            (string)(
                $attachment->providerattachmentid
                ?? ''
            )
        );

        if ($providerattachmentid === '') {
            throw new \RuntimeException(
                'Inbox attachment has no provider identifier.'
            );
        }

        $this->repository->mark_downloading(
            $attachmentid
        );

        try {
            $content = $this->fetcher
                ->fetch_attachment(
                    $account,
                    $folder,
                    $provideruid,
                    $providerattachmentid
                );

            if (is_resource($content)) {
                $rawcontent = stream_get_contents(
                    $content
                );

                if ($rawcontent === false) {
                    throw new \RuntimeException(
                        'Unable to read Inbox attachment.'
                    );
                }
            } else {
                $rawcontent = (string)$content;
            }

            $fileitemid = $this->storage->store(
                $attachmentid,
                (string)$attachment->filename,
                (string)(
                    $attachment->mimetype
                    ?? 'application/octet-stream'
                ),
                $rawcontent
            );

            $this->repository->mark_stored(
                $attachmentid,
                $fileitemid,
                hash('sha256', $rawcontent),
                strlen($rawcontent)
            );
        } catch (\Throwable $exception) {
            $this->repository->mark_failed(
                $attachmentid,
                $exception->getMessage()
            );

            throw $exception;
        }
    }
}