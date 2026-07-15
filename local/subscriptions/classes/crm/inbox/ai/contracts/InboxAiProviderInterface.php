<?php

namespace local_subscriptions\crm\inbox\ai\contracts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxAiResult;

interface InboxAiProviderInterface {

    public function key(): string;

    public function is_available(): bool;

    public function supports(
        string $capability
    ): bool;

    public function analyse(
        InboxAiRequest $request
    ): InboxAiResult;
}