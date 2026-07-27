<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB;

$slug = 'verbes-3e-groupe';

$product = $DB->get_record('subscription_digital_product', ['slug' => $slug], '*', MUST_EXIST);

$now = time();

$translations = [
    'fr' => [
        'title' => 'Les verbes du 3e groupe',
        'sales_intro' => 'Un guide pratique et clair pour comprendre les verbes du 3e groupe, repérer les régularités et arrêter de les apprendre au hasard.',
        'content_items' => implode("\n", [
            'Les principales familles de verbes du 3e groupe.',
            'Les modèles de conjugaison les plus utiles.',
            'Des explications simples et visuelles.',
            'Des tableaux faciles à relire rapidement.',
            'Des regroupements logiques pour mieux mémoriser.',
            'Un support pratique à garder pendant vos révisions.',
        ]),
        'forwho_items' => implode("\n", [
            'Vous mélangez souvent les verbes du 3e groupe.',
            'Vous voulez enfin voir des régularités.',
            'Vous apprenez le français de manière autonome.',
            'Vous préparez un examen ou une certification.',
            'Vous voulez un support pratique à garder sous la main.',
        ]),
    ],
    'en' => [
        'title' => 'French third-group verbs',
        'sales_intro' => 'A practical and easy-to-follow guide to understand French third-group verbs, recognize patterns and stop learning them randomly.',
        'content_items' => implode("\n", [
            'The main families of third-group verbs.',
            'The most useful conjugation patterns.',
            'Simple and visual explanations.',
            'Tables designed for quick review.',
            'Logical groupings to help memorization.',
            'A practical support to keep during your revision sessions.',
        ]),
        'forwho_items' => implode("\n", [
            'You often confuse third-group verbs.',
            'You want to finally recognize patterns.',
            'You are learning French independently.',
            'You are preparing for an exam or certification.',
            'You want a practical support to keep close at hand.',
        ]),
    ],
    'ru' => [
        'title' => 'Карточки по глаголам <br>3-й группы',
        'sales_intro' => 'Я собрала все французские глаголы 3 группы в удобные карточки (178 глаголов), чтобы вы наконец выучили их без хаоса и потери времени.',
        'content_items' => implode("\n", [
            'Спряжение каждого глагола озвучено французом 🇫🇷.',
            'Карточки можно распечатать или использовать электронно.',
            'Все глаголы распределены по уровням (А1, А2, В1+).',
            'Карточки подойдут как начинающим, так и тем, кто уже на более высоких уровнях.',
        ]),
        'forwho_items' => implode("\n", [
            'вы часто путаете глаголы 3-й группы',
            'вы хотите наконец увидеть закономерности',
            'вы изучаете французский самостоятельно',
            'вы готовитесь к экзамену',
            'вы хотите иметь практичный материал под рукой',
        ]),
    ],
];

foreach ($translations as $lang => $data) {
    $existing = $DB->get_record('subscription_digital_product_lang', [
        'productid' => $product->id,
        'lang' => $lang,
    ]);

    $record = (object)[
        'productid' => $product->id,
        'lang' => $lang,
        'title' => $data['title'],
        'sales_intro' => $data['sales_intro'],
        'content_items' => $data['content_items'],
        'forwho_items' => $data['forwho_items'],
        'last_update' => $now,
    ];

    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('subscription_digital_product_lang', $record);
        mtrace("Updated translation: {$slug} / {$lang}");
    } else {
        $record->creation_date = $now;
        $DB->insert_record('subscription_digital_product_lang', $record);
        mtrace("Created translation: {$slug} / {$lang}");
    }
}

mtrace('Done.');
