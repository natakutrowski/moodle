<?php

namespace local_subscriptions\crm\assistant\ai\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\dto\CrmAssistantContext;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantResult;

interface CrmAssistantProviderInterface {

    public function available(): bool;

    public function answer(
        CrmAssistantQuestion $question,
        CrmAssistantContext $context
    ): CrmAssistantResult;
}