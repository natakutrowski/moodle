<?php

namespace local_subscriptions\crm\inbox\contracts;

defined('MOODLE_INTERNAL') || die();

interface InboxAttachmentStorageInterface {

    /**
     * @param resource|string $content
     */
    public function store(
        int $fileitemid,
        string $filename,
        string $mimetype,
        mixed $content
    ): int;

    public function delete(
        int $fileitemid
    ): void;

    public function exists(
        int $fileitemid
    ): bool;
}