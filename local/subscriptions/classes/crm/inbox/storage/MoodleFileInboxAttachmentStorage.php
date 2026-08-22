<?php

namespace local_subscriptions\crm\inbox\storage;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\crm\inbox\contracts\InboxAttachmentStorageInterface;

final class MoodleFileInboxAttachmentStorage implements
    InboxAttachmentStorageInterface {

    private const COMPONENT =
        'local_subscriptions';

    private const FILEAREA =
        'inbox_attachment';

    public function store(
        int $fileitemid,
        string $filename,
        string $mimetype,
        mixed $content
    ): int {
        if ($fileitemid <= 0) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox attachment file item identifier.'
            );
        }

        $filename = clean_param(
            $filename,
            PARAM_FILE
        );

        if ($filename === '') {
            $filename = 'attachment';
        }

        $mimetype = self::normalize_mimetype(
            $mimetype
        );

        $context =
            context_system::instance();

        $filestorage =
            get_file_storage();

        /*
         * Une pièce jointe possède désormais son propre itemid.
         * La suppression ne peut donc concerner que cette pièce.
         */
        $filestorage->delete_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $fileitemid
        );

        $filerecord = [
            'contextid' => $context->id,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $fileitemid,
            'filepath' => '/',
            'filename' => $filename,
            'mimetype' => $mimetype,
        ];

        if (is_resource($content)) {
            $buffer =
                stream_get_contents($content);

            if ($buffer === false) {
                throw new \RuntimeException(
                    'Unable to read Inbox attachment stream.'
                );
            }

            $content = $buffer;
        }

        $file =
            $filestorage
                ->create_file_from_string(
                    $filerecord,
                    (string)$content
                );

        return (int)$file->get_itemid();
    }

    private static function normalize_mimetype(
        string $mimetype
    ): string {
        $mimetype = strtolower(
            trim($mimetype)
        );

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

    public function delete(
        int $fileitemid
    ): void {
        $context = context_system::instance();

        get_file_storage()->delete_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $fileitemid
        );
    }

    public function exists(
        int $fileitemid
    ): bool {
        $context = context_system::instance();

        $files = get_file_storage()->get_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $fileitemid,
            'id',
            false
        );

        return !empty($files);
    }

    public function get(
        int $fileitemid,
        string $filename
    ): ?\stored_file {
        $context = context_system::instance();

        $file = get_file_storage()->get_file(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $fileitemid,
            '/',
            clean_param(
                $filename,
                PARAM_FILE
            )
        );

        return $file ?: null;
    }
}