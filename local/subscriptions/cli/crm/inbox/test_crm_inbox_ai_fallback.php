<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\providers\fallback\FallbackInboxAiProvider;

$provider = new FallbackInboxAiProvider();

$content =
    'Bonjour, mon paiement a été effectué mais je ne peux toujours pas accéder au cours. C’est urgent.';

foreach (
    [
        InboxAiCapability::LANGUAGE_DETECTION,
        InboxAiCapability::URGENCY_CLASSIFICATION,
        InboxAiCapability::CATEGORIZATION,
        InboxAiCapability::REQUEST_EXTRACTION,
        InboxAiCapability::SUMMARY,
    ] as $capability
) {
    $result = $provider->analyse(
        new InboxAiRequest(
            $capability,
            1,
            null,
            $content,
            'fr'
        )
    );

    mtrace('');
    mtrace('Capability: ' . $capability);
    mtrace('Status: ' . $result->status);
    mtrace(
        'Data: ' .
        json_encode(
            $result->data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        )
    );
}
