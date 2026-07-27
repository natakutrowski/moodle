<?php
// local/subscriptions/cli/development/test_username.php
declare(strict_types=1);

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Charge la fonction depuis ton plugin (ajuste les chemins si besoin).
$tries = [
    $CFG->dirroot . '/local/subscriptions/lib/user_subs_lib.php',
    $CFG->dirroot . '/local/subscriptions/lib.php',
    $CFG->dirroot . '/local/subscriptions/locallib.php',
];
foreach ($tries as $path) {
    if (file_exists($path)) {
        require_once($path);
    }
}
if (!function_exists('local_subscriptions_generate_unique_username')) {
    cli_error('Fonction local_subscriptions_generate_unique_username introuvable. Ajuste les require_once() ci-dessus.');
}

// Params CLI.
list($options, $unrecognized) = cli_get_params(
    [
        'firstname' => '',
        'lastname'  => '',
        'email'     => '',
        'demo'      => false,
        'help'      => false,
    ],
    [
        'f' => 'firstname',
        'l' => 'lastname',
        'e' => 'email',
        'd' => 'demo',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrec = implode(', ', $unrecognized);
    cli_error("Options non reconnues: {$unrec}");
}

$help = <<<EOF
Test de génération de username (Moodle CLI)

Usage :
  php local/subscriptions/cli/development/test_username.php -f "Иван" -l "Петров" -e "ivan@example.com"
  php local/subscriptions/cli/development/test_username.php --demo

Options :
  -f, --firstname   Prénom
  -l, --lastname    Nom
  -e, --email       Email (utilisé si prénom+nom sont vides)
  -d, --demo        Lance une petite batterie de tests
  -h, --help        Affiche cette aide

EOF;

if (!empty($options['help'])) {
    echo $help;
    exit(0);
}

// Affiche l'état du réglage "extendedusernamechars".
$extended = (int)get_config('core', 'extendedusernamechars');
cli_heading('Génération de usernames');
mtrace('Extended username chars : ' . ($extended ? 'ON' : 'OFF'));
mtrace('');

$tests = [];

if (!empty($options['demo']) || ($options['firstname'] === '' && $options['lastname'] === '' && $options['email'] === '')) {
    // Démo : quelques cas multi-alphabets.
    $tests = [
        ['Иван',   'Петров',      'ivan.petrov@example.com'],
        ['张',      '三',            'zhang.san@example.com'],
        ['محمد',   'الزهراني',     'mohammad@example.com'],
        ['Jean',   "D'Été",        'jean.dete@example.com'],
        ['',       '',             'empty@example.com'],
    ];
} else {
    $tests[] = [(string)$options['firstname'], (string)$options['lastname'], (string)$options['email']];
}

foreach ($tests as [$f, $l, $e]) {
    $label = trim("$f $l");
    if ($label === '') { $label = "(∅) via email: $e"; }

    $username = local_subscriptions_generate_unique_username($f, $l, $e);

    mtrace(str_pad($label, 35) . " -> " . $username);
}

mtrace('');
mtrace('Terminé.');
