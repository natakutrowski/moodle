<?php

namespace local_subscriptions\crm\inbox\ai\contracts;

defined('MOODLE_INTERNAL') || die();

interface InboxAiContextProviderInterface {

    public function key(): string;

    public function priority(): int;

    public function supports(
        int $threadid
    ): bool;

    public function provide(
        int $threadid
    ): array;
}