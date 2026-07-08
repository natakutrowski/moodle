<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationDispatcher {

    public function dispatch(AutomationContext $context): array {
        if ($context->get('source') === 'automation') {
            return [];
        }

        return AutomationFactory::runner()->run_for_context($context);
    }

    public function dispatch_user(string $triggerkey, int $userid, array $data = []): array {
        return $this->dispatch(
            AutomationContext::for_user($triggerkey, $userid, $data)
        );
    }

    public function dispatch_entity(
        string $triggerkey,
        string $entitytype,
        int $entityid,
        int $userid = 0,
        array $data = []
    ): array {
        return $this->dispatch(
            AutomationContext::for_entity($triggerkey, $entitytype, $entityid, $userid, $data)
        );
    }
}