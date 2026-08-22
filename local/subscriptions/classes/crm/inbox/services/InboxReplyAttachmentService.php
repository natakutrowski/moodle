<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAttachmentStatus;
use local_subscriptions\crm\inbox\dto\InboxAttachmentData;
use local_subscriptions\crm\inbox\dto\InboxOutboundAttachmentData;
use local_subscriptions\crm\inbox\repositories\InboxAttachmentRepository;
use local_subscriptions\crm\inbox\storage\MoodleFileInboxAttachmentStorage;

/**
 * Handles files attached to an Inbox draft/reply.
 *
 * The same attachment table/file area is used for inbound and outbound mail,
 * which means a sent CRM reply keeps its files in the thread history.
 */
final class InboxReplyAttachmentService {

    public const MAX_FILES = 10;
    public const MAX_FILE_SIZE = 10485760; // 10 MiB.
    public const MAX_TOTAL_SIZE = 26214400; // 25 MiB.

    public function __construct(
        private readonly InboxAttachmentRepository $repository =
            new InboxAttachmentRepository(),
        private readonly MoodleFileInboxAttachmentStorage $storage =
            new MoodleFileInboxAttachmentStorage()
    ) {
    }

    /**
     * Normalizes PHP's multiple-upload shape.
     *
     * @return array<int,array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    public static function normalize_uploads(array $files): array {
        if (
            !isset($files['name'])
            || !is_array($files['name'])
        ) {
            return [];
        }

        $uploads = [];

        foreach ($files['name'] as $index => $name) {
            $name = trim((string)$name);

            if ($name === '') {
                continue;
            }

            $uploads[] = [
                'name' => $name,
                'type' => (string)($files['type'][$index] ?? ''),
                'tmp_name' => (string)($files['tmp_name'][$index] ?? ''),
                'error' => (int)(
                    $files['error'][$index]
                    ?? UPLOAD_ERR_NO_FILE
                ),
                'size' => (int)($files['size'][$index] ?? 0),
            ];
        }

        return $uploads;
    }

    /**
     * @param array<int,array{name:string,type:string,tmp_name:string,error:int,size:int}> $uploads
     * @param int[] $removeids
     */
    public function apply(
        int $messageid,
        array $uploads,
        array $removeids = []
    ): void {
        if ($messageid <= 0) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox reply message identifier.'
            );
        }

        /*
         * A PHP/runtime failure can leave a local attachment metadata row in
         * PENDING/FAILED state even though no Moodle stored_file exists.
         * Such a phantom must never survive into a later send where the other
         * attachments would be delivered silently without it.
         */
        $this->cleanup_unstored_local_attachments(
            $messageid
        );

        foreach (
            array_values(
                array_unique(
                    array_filter(
                        array_map('intval', $removeids),
                        static fn(int $id): bool => $id > 0
                    )
                )
            )
            as $attachmentid
        ) {
            $attachment = $this->repository->find(
                $attachmentid
            );

            if (
                !$attachment
                || (int)$attachment->messageid !== $messageid
            ) {
                continue;
            }

            if (!empty($attachment->fileitemid)) {
                $this->storage->delete(
                    (int)$attachment->fileitemid
                );
            }

            $this->repository->delete(
                $attachmentid
            );
        }

        $existing = $this->repository
            ->get_for_message($messageid);

        if (
            count($existing) + count($uploads)
            > self::MAX_FILES
        ) {
            throw new \moodle_exception(
                'crm_inbox_attachment_too_many_o4',
                'local_subscriptions',
                '',
                self::MAX_FILES
            );
        }

        $totalsize = array_sum(
            array_map(
                static fn(object $file): int =>
                    max(0, (int)$file->filesize),
                $existing
            )
        );

        foreach ($uploads as $upload) {
            $this->validate_upload(
                $upload
            );

            $totalsize += max(
                0,
                (int)$upload['size']
            );

            if ($totalsize > self::MAX_TOTAL_SIZE) {
                throw new \moodle_exception(
                    'crm_inbox_attachment_total_too_large_o4',
                    'local_subscriptions',
                    '',
                    display_size(self::MAX_TOTAL_SIZE)
                );
            }

            $raw = file_get_contents(
                (string)$upload['tmp_name']
            );

            if ($raw === false) {
                throw new \moodle_exception(
                    'crm_inbox_attachment_read_failed_o4',
                    'local_subscriptions'
                );
            }

            $filename = clean_param(
                (string)$upload['name'],
                PARAM_FILE
            );

            if ($filename === '') {
                $filename = 'attachment';
            }

            $mimetype = self::normalize_mimetype(
                (string)$upload['type']
            );

            $stableid = 'local-' . hash(
                'sha256',
                implode('|', [
                    $filename,
                    (string)strlen($raw),
                    hash('sha256', $raw),
                ])
            );

            $record = $this->repository
                ->get_or_create(
                    $messageid,
                    new InboxAttachmentData(
                        $stableid,
                        $filename,
                        $mimetype,
                        strlen($raw)
                    ),
                    $stableid
                );

            $fileitemid = $this->storage->store(
                (int)$record->id,
                $filename,
                $mimetype,
                $raw
            );

            $this->repository->mark_stored(
                (int)$record->id,
                $fileitemid,
                hash('sha256', $raw),
                strlen($raw)
            );
        }

        $this->refresh_message_flag(
            $messageid
        );
    }

    /**
     * Synchronises inline images referenced by cid: URLs in the HTML body.
     *
     * @param array<int,array{name:string,type:string,tmp_name:string,error:int,size:int}> $uploads
     * @param string[] $cids
     */
    public function apply_inline_images(
        int $messageid,
        string $bodyhtml,
        array $uploads,
        array $cids
    ): void {
        $htmlservice = new InboxReplyHtmlService();

        $referenced = array_fill_keys(
            $htmlservice->referenced_cids(
                $bodyhtml
            ),
            true
        );

        foreach (
            $this->repository->get_for_message(
                $messageid
            )
            as $attachment
        ) {
            if (
                empty($attachment->isinline)
            ) {
                continue;
            }

            $cid = trim(
                (string)($attachment->contentid ?? ''),
                "<> \t\n\r\0\x0B"
            );

            if (
                $cid !== ''
                && isset($referenced[$cid])
            ) {
                continue;
            }

            if (!empty($attachment->fileitemid)) {
                $this->storage->delete(
                    (int)$attachment->fileitemid
                );
            }

            $this->repository->delete(
                (int)$attachment->id
            );
        }

        foreach ($uploads as $index => $upload) {
            $cid = trim(
                (string)($cids[$index] ?? ''),
                "<> \t\n\r\0\x0B"
            );

            if (
                $cid === ''
                || !isset($referenced[$cid])
            ) {
                continue;
            }

            $this->validate_upload(
                $upload
            );

            $mimetype = self::normalize_mimetype(
                (string)$upload['type']
            );

            if (!str_starts_with(
                $mimetype,
                'image/'
            )) {
                throw new \moodle_exception(
                    'crm_inbox_inline_image_type_o5',
                    'local_subscriptions',
                    '',
                    (string)$upload['name']
                );
            }

            $raw = file_get_contents(
                (string)$upload['tmp_name']
            );

            if ($raw === false) {
                throw new \moodle_exception(
                    'crm_inbox_attachment_read_failed_o4',
                    'local_subscriptions'
                );
            }

            $filename = clean_param(
                (string)$upload['name'],
                PARAM_FILE
            );

            if ($filename === '') {
                $filename = 'inline-image';
            }

            $stableid = 'inline-' . hash(
                'sha256',
                $cid
            );

            $record = $this->repository
                ->get_or_create(
                    $messageid,
                    new InboxAttachmentData(
                        $stableid,
                        $filename,
                        $mimetype,
                        strlen($raw),
                        $cid,
                        true
                    ),
                    $stableid
                );

            $fileitemid = $this->storage->store(
                (int)$record->id,
                $filename,
                $mimetype,
                $raw
            );

            $this->repository->mark_stored(
                (int)$record->id,
                $fileitemid,
                hash('sha256', $raw),
                strlen($raw)
            );
        }

        $all = $this->repository
            ->get_for_message(
                $messageid
            );

        if (count($all) > self::MAX_FILES) {
            throw new \moodle_exception(
                'crm_inbox_attachment_too_many_o4',
                'local_subscriptions',
                '',
                self::MAX_FILES
            );
        }

        $totalsize = array_sum(
            array_map(
                static fn(object $attachment): int =>
                    max(
                        0,
                        (int)$attachment->filesize
                    ),
                $all
            )
        );

        if ($totalsize > self::MAX_TOTAL_SIZE) {
            throw new \moodle_exception(
                'crm_inbox_attachment_total_too_large_o4',
                'local_subscriptions',
                '',
                display_size(
                    self::MAX_TOTAL_SIZE
                )
            );
        }

        $this->refresh_message_flag(
            $messageid
        );
    }

    /**
     * @return InboxOutboundAttachmentData[]
     */
    public function outbound_attachments(
        int $messageid
    ): array {
        $result = [];

        foreach (
            $this->repository->get_for_message(
                $messageid
            )
            as $attachment
        ) {
            if (
                (string)$attachment->downloadstatus !==
                    InboxAttachmentStatus::STORED
                || empty($attachment->fileitemid)
            ) {
                continue;
            }

            $file = $this->storage->get(
                (int)$attachment->fileitemid,
                (string)$attachment->filename
            );

            if (!$file) {
                continue;
            }

            $result[] = new InboxOutboundAttachmentData(
                (string)$attachment->filename,
                (string)(
                    $attachment->mimetype
                    ?: 'application/octet-stream'
                ),
                $file->get_content(),
                !empty($attachment->contentid)
                    ? (string)$attachment->contentid
                    : null,
                !empty($attachment->isinline)
            );
        }

        return $result;
    }

    public function assert_ready_for_send(
        int $messageid
    ): void {
        foreach (
            $this->repository->get_for_message(
                $messageid
            )
            as $attachment
        ) {
            if (
                (string)$attachment->downloadstatus !==
                    InboxAttachmentStatus::STORED
                || empty($attachment->fileitemid)
                || !$this->storage->exists(
                    (int)$attachment->fileitemid
                )
            ) {
                throw new \moodle_exception(
                    'crm_inbox_attachment_not_ready_o44',
                    'local_subscriptions',
                    '',
                    (string)$attachment->filename
                );
            }
        }
    }

    private function cleanup_unstored_local_attachments(
        int $messageid
    ): void {
        foreach (
            $this->repository->get_for_message(
                $messageid
            )
            as $attachment
        ) {
            if (
                !str_starts_with(
                    (string)(
                        $attachment->providerattachmentid
                        ?? ''
                    ),
                    'local-'
                )
                || (
                    (string)$attachment->downloadstatus ===
                        InboxAttachmentStatus::STORED
                    && !empty($attachment->fileitemid)
                    && $this->storage->exists(
                        (int)$attachment->fileitemid
                    )
                )
            ) {
                continue;
            }

            if (!empty($attachment->fileitemid)) {
                $this->storage->delete(
                    (int)$attachment->fileitemid
                );
            }

            $this->repository->delete(
                (int)$attachment->id
            );
        }

        $this->refresh_message_flag(
            $messageid
        );
    }

    private static function normalize_mimetype(
        string $mimetype
    ): string {
        $mimetype = strtolower(
            trim($mimetype)
        );

        /*
         * Moodle does not expose a PARAM_MIMETYPE cleaning constant.
         * Accept a conservative RFC-style type/subtype token and fall back
         * safely when browsers provide an empty or malformed value.
         */
        if (
            $mimetype === ''
            || !preg_match(
                '~^[a-z0-9][a-z0-9!#$&^_.+\-]*/[a-z0-9][a-z0-9!#$&^_.+\-]*$~i',
                $mimetype
            )
        ) {
            return 'application/octet-stream';
        }

        return $mimetype;
    }

    private function validate_upload(array $upload): void {
        $error = (int)(
            $upload['error']
            ?? UPLOAD_ERR_NO_FILE
        );

        if ($error !== UPLOAD_ERR_OK) {
            throw new \moodle_exception(
                'crm_inbox_attachment_upload_failed_o4',
                'local_subscriptions',
                '',
                (string)($upload['name'] ?? '')
            );
        }

        $size = max(
            0,
            (int)($upload['size'] ?? 0)
        );

        if ($size > self::MAX_FILE_SIZE) {
            throw new \moodle_exception(
                'crm_inbox_attachment_too_large_o4',
                'local_subscriptions',
                '',
                (object)[
                    'filename' =>
                        (string)($upload['name'] ?? ''),
                    'limit' =>
                        display_size(self::MAX_FILE_SIZE),
                ]
            );
        }

        $tmpname = trim(
            (string)($upload['tmp_name'] ?? '')
        );

        if (
            $tmpname === ''
            || !is_readable($tmpname)
        ) {
            throw new \moodle_exception(
                'crm_inbox_attachment_read_failed_o4',
                'local_subscriptions'
            );
        }
    }

    private function refresh_message_flag(
        int $messageid
    ): void {
        global $DB;

        $hasattachments =
            $this->repository
                ->get_for_message($messageid) !== [];

        $DB->set_field(
            'local_subscriptions_inbox_message',
            'hasattachments',
            $hasattachments ? 1 : 0,
            ['id' => $messageid]
        );
    }
}
