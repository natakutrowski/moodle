<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\ai\context\InboxAiContextRegistry;
use local_subscriptions\crm\inbox\ai\context\InboxContactContextProvider;
use local_subscriptions\crm\inbox\ai\context\InboxThreadContextProvider;
use local_subscriptions\crm\inbox\ai\prompts\InboxReplyPromptBuilder;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;
use local_subscriptions\crm\inbox\ai\services\InboxAiCrmContextBuilder;
use local_subscriptions\crm\inbox\ai\services\InboxAiOrchestrator;
use local_subscriptions\crm\inbox\ai\services\InboxAiThreadContentBuilder;
use local_subscriptions\crm\inbox\ai\services\InboxReplySuggestionService;
use local_subscriptions\crm\inbox\ai\services\InboxAiServiceFactory;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'threadid' => 0,
        'language' => 'fr',
        'tone' => 'professional',
        'force' => false,
    ],
    [
        'h' => 'help',
        't' => 'threadid',
        'l' => 'language',
        'f' => 'force',
    ]
);

if ($unrecognized) {
    cli_error(
        'Options non reconnues : ' .
        implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Teste la génération d'une suggestion de réponse pour une conversation CRM Inbox.

Options:
--threadid=ID          Identifiant de la conversation Inbox
--language=fr          Langue de la réponse : fr, en ou ru
--tone=professional    Ton : professional, friendly, empathetic ou concise
--force                Ignore le résultat IA en cache
-h, --help             Affiche cette aide

Exemples:

php local/subscriptions/cli/test_crm_inbox_ai_reply.php \
    --threadid=12

php local/subscriptions/cli/test_crm_inbox_ai_reply.php \
    --threadid=12 \
    --language=ru \
    --tone=empathetic

php local/subscriptions/cli/test_crm_inbox_ai_reply.php \
    --threadid=12 \
    --language=fr \
    --tone=concise \
    --force

HELP;

    exit(0);
}

$threadid = max(
    0,
    (int)$options['threadid']
);

if ($threadid <= 0) {
    cli_error(
        'L’option --threadid est obligatoire et doit contenir un identifiant valide.'
    );
}

$language = \core_text::strtolower(
    trim((string)$options['language'])
);

$allowedlanguages = [
    'fr',
    'en',
    'ru',
];

if (
    !in_array(
        $language,
        $allowedlanguages,
        true
    )
) {
    cli_error(
        'Langue invalide. Valeurs autorisées : ' .
        implode(', ', $allowedlanguages)
    );
}

$tone = \core_text::strtolower(
    trim((string)$options['tone'])
);

$allowedtones = [
    'professional',
    'friendly',
    'empathetic',
    'concise',
];

if (
    !in_array(
        $tone,
        $allowedtones,
        true
    )
) {
    cli_error(
        'Ton invalide. Valeurs autorisées : ' .
        implode(', ', $allowedtones)
    );
}

$forcerefresh = !empty(
    $options['force']
);

$readrepository =
    new InboxReadRepository();

$thread = $readrepository->get_thread(
    $threadid
);

if (!$thread) {
    cli_error(
        'Conversation Inbox introuvable : ' .
        $threadid
    );
}

/*
 * Le registry construit uniquement le contexte CRM explicitement
 * autorisé pour la suggestion de réponse.
 */
$contextregistry =
    new InboxAiContextRegistry();

$contextregistry->register(
    new InboxThreadContextProvider(
        $readrepository
    )
);

$contextregistry->register(
    new InboxContactContextProvider(
        $readrepository
    )
);

$sanitizer =
    new InboxAiContentSanitizer();

$contextbuilder =
    new InboxAiCrmContextBuilder(
        $contextregistry
    );

$contentbuilder =
    new InboxAiThreadContentBuilder(
        $readrepository,
        $sanitizer
    );

/*
 * L’orchestrateur est récupéré depuis le conteneur Moodle.
 * Cela évite de dupliquer ici sa construction, son provider registry,
 * son repository de résultats et sa politique de cache.
 */
try {
    /** @var InboxAiOrchestrator $orchestrator */
    $orchestrator = InboxAiServiceFactory::orchestrator();
} catch (\Throwable $exception) {
    cli_error(
        implode(PHP_EOL, [
            'Impossible de construire InboxAiOrchestrator depuis le conteneur Moodle.',
            '',
            'Erreur : ' . $exception->getMessage(),
            '',
            'Vérifie que toutes les dépendances du constructeur de',
            'InboxAiOrchestrator peuvent être injectées automatiquement.',
        ])
    );
}

$service =
    new InboxReplySuggestionService(
        $orchestrator,
        new InboxAiSafetyPolicy(),
        $contentbuilder,
        $contextbuilder,
        new InboxReplyPromptBuilder()
    );

mtrace('CRM Inbox AI reply suggestion');
mtrace('=============================');
mtrace('Thread ID: ' . $threadid);
mtrace(
    'Subject: ' .
    (
        trim((string)$thread->subject) !== ''
            ? (string)$thread->subject
            : '(sans objet)'
    )
);
mtrace(
    'Contact: ' .
    (
        trim(
            (string)(
                $thread->contactemail
                ?? ''
            )
        ) !== ''
            ? (string)$thread->contactemail
            : '(contact inconnu)'
    )
);
mtrace('Language: ' . $language);
mtrace('Tone: ' . $tone);
mtrace(
    'Force refresh: ' .
    ($forcerefresh ? 'yes' : 'no')
);
mtrace('');

try {
    $result = $service->suggest(
        $threadid,
        $language,
        $tone,
        null,
        $forcerefresh
    );
} catch (\Throwable $exception) {
    cli_error(
        implode(PHP_EOL, [
            'La suggestion de réponse a échoué.',
            '',
            'Exception : ' .
                get_class($exception),
            'Message : ' .
                $exception->getMessage(),
            'Fichier : ' .
                $exception->getFile() .
                ':' .
                $exception->getLine(),
        ])
    );
}

$hasreply =
    trim($result->subject) !== '' ||
    trim($result->body) !== '';

mtrace(
    'Generated: ' .
    ($hasreply ? 'yes' : 'no')
);

mtrace(
    'Confidence: ' .
    number_format(
        $result->confidence,
        2,
        '.',
        ''
    )
);

mtrace(
    'Requires review: ' .
    ($result->requiresreview ? 'yes' : 'no')
);

mtrace('');

if (trim($result->subject) !== '') {
    mtrace('Suggested subject');
    mtrace('-----------------');
    mtrace($result->subject);
    mtrace('');
}

if (trim($result->body) !== '') {
    mtrace('Suggested reply');
    mtrace('---------------');
    mtrace($result->body);
    mtrace('');
} else {
    mtrace('No reply body was generated.');
    mtrace('');
}

if ($result->warnings) {
    mtrace('Warnings');
    mtrace('--------');

    foreach ($result->warnings as $warning) {
        mtrace(
            '- ' . trim((string)$warning)
        );
    }

    mtrace('');
}

mtrace('Raw result');
mtrace('----------');

$json = json_encode(
    $result,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

mtrace(
    $json !== false
        ? $json
        : 'Unable to encode the result as JSON.'
);

/*
 * Un provider fallback peut légitimement retourner un résultat
 * indisponible. Le CLI utilise alors un code différent de zéro
 * afin de rendre ce cas visible dans les scripts de test.
 */
exit($hasreply ? 0 : 2);
