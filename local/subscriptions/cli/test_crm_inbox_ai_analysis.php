<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\ai\prompts\InboxCategoryPromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxLanguagePromptBuilder;
use local_subscriptions\crm\inbox\ai\prompts\InboxUrgencyPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;
use local_subscriptions\crm\inbox\ai\services\InboxAiServiceFactory;
use local_subscriptions\crm\inbox\ai\services\InboxCategorizationService;
use local_subscriptions\crm\inbox\ai\services\InboxLanguageDetectionService;
use local_subscriptions\crm\inbox\ai\services\InboxUrgencyService;

/*
 * Réutilise ici les dépendances exactes de ton orchestrateur E6.
 * Ne crée pas un deuxième système de cache ou de persistance.
 */
$orchestrator = InboxAiServiceFactory::orchestrator();

$safety = new InboxAiSafetyPolicy();
$sanitizer = new InboxAiContentSanitizer();

$content =
    'Bonjour, mon paiement a été effectué hier, mais je ne peux toujours pas accéder au cours. C’est urgent car mon examen est demain.';

$language = (
    new InboxLanguageDetectionService(
        $orchestrator,
        $safety,
        $sanitizer,
        new InboxLanguagePromptBuilder()
    )
)->detect(
    1,
    null,
    $content
);

mtrace(
    'Language: ' .
    json_encode(
        $language,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);

$urgency = (
    new InboxUrgencyService(
        $orchestrator,
        $safety,
        $sanitizer,
        new InboxUrgencyPromptBuilder()
    )
)->classify(
    1,
    null,
    $content
);

mtrace(
    'Urgency: ' .
    json_encode(
        $urgency,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);

$category = (
    new InboxCategorizationService(
        $orchestrator,
        $safety,
        $sanitizer,
        new InboxCategoryPromptBuilder()
    )
)->categorize(
    1,
    null,
    $content
);

mtrace(
    'Category: ' .
    json_encode(
        $category,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);