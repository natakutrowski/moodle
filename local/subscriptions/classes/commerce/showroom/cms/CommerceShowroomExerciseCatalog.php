<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical catalogue for the fixed exercise explorer used by the verbs Showroom.
 *
 * Exercise keys are stable technical identifiers. Editorial text and preview media
 * can evolve independently without changing the key used by the renderer/Builder.
 */
final class CommerceShowroomExerciseCatalog {
    public const LANGUAGES = ['fr', 'en', 'ru'];

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(?string $language = null): array {
        $language = self::normalise_language($language ?? current_language());
        $result = [];

        foreach (self::definitions() as $definition) {
            $translated = $definition['translations'][$language]
                ?? $definition['translations']['fr'];
            $result[] = [
                'key' => $definition['key'],
                'position' => $definition['position'],
                'icon' => $definition['icon'],
                'title' => $translated['title'],
                'text' => $translated['text'],
                'translations' => $definition['translations'],
            ];
        }

        return $result;
    }

    /** @return array<string,mixed>|null */
    public static function get(string $key, ?string $language = null): ?array {
        foreach (self::all($language) as $exercise) {
            if ($exercise['key'] === $key) {
                return $exercise;
            }
        }
        return null;
    }

    /** @return string[] */
    public static function keys(): array {
        return array_column(self::definitions(), 'key');
    }

    public static function exists(string $key): bool {
        return in_array($key, self::keys(), true);
    }

    /**
     * Maps the Russian source title used in Nata's initial screenshot batch to a
     * stable exercise key. The lookup is Unicode/case/whitespace tolerant.
     */
    public static function key_from_source_title(string $title): ?string {
        $needle = self::normalise_title($title);
        foreach (self::definitions() as $definition) {
            $ru = (string)$definition['translations']['ru']['title'];
            if (self::normalise_title($ru) === $needle) {
                return (string)$definition['key'];
            }
        }
        return null;
    }

    public static function normalise_language(string $language): string {
        $language = strtolower(substr(trim($language), 0, 2));
        return in_array($language, self::LANGUAGES, true) ? $language : 'fr';
    }

    private static function normalise_title(string $title): string {
        $title = preg_replace('/\.[a-z0-9]{2,5}$/iu', '', trim($title)) ?? trim($title);
        $title = \core_text::strtolower($title);
        return trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    }

    /** @return array<int,array<string,mixed>> */
    private static function definitions(): array {
        return [
            [
                'position' => 1,
                'key' => '01_learn_conjugation',
                'icon' => 'fa-solid fa-headphones',
                'translations' => [
                    'ru' => ['title' => 'Изучаем спряжение', 'text' => 'Прослушать и запомнить спряжение глагола'],
                    'fr' => ['title' => 'Découvrir la conjugaison', 'text' => 'Écouter et mémoriser la conjugaison du verbe.'],
                    'en' => ['title' => 'Learn the conjugation', 'text' => 'Listen to and memorise the verb conjugation.'],
                ],
            ],
            [
                'position' => 2,
                'key' => '02_memory_pairs',
                'icon' => 'fa-solid fa-clone',
                'translations' => [
                    'ru' => ['title' => 'Найди пары по памяти', 'text' => 'Открывать карточки по памяти и находить пары'],
                    'fr' => ['title' => 'Retrouver les paires', 'text' => 'Retourner les cartes et retrouver les paires de mémoire.'],
                    'en' => ['title' => 'Match the pairs', 'text' => 'Flip the cards and find the matching pairs from memory.'],
                ],
            ],
            [
                'position' => 3,
                'key' => '03_restore_conjugation',
                'icon' => 'fa-solid fa-link',
                'translations' => [
                    'ru' => ['title' => 'Восстановить спряжение', 'text' => 'Соединить местоимения с правильными формами глагола'],
                    'fr' => ['title' => 'Reconstituer la conjugaison', 'text' => 'Associer chaque pronom à la bonne forme du verbe.'],
                    'en' => ['title' => 'Rebuild the conjugation', 'text' => 'Match each pronoun with the correct verb form.'],
                ],
            ],
            [
                'position' => 4,
                'key' => '04_multiple_choice',
                'icon' => 'fa-solid fa-list-check',
                'translations' => [
                    'ru' => ['title' => 'Выбрать правильный ответ', 'text' => 'Найти правильный ответ среди предложенных вариантов'],
                    'fr' => ['title' => 'Choisir la bonne réponse', 'text' => 'Trouver la bonne réponse parmi plusieurs propositions.'],
                    'en' => ['title' => 'Choose the correct answer', 'text' => 'Find the correct answer among several choices.'],
                ],
            ],
            [
                'position' => 5,
                'key' => '05_true_false',
                'icon' => 'fa-solid fa-circle-check',
                'translations' => [
                    'ru' => ['title' => 'Верно или неверно', 'text' => 'Определить правильность сочетания подлежащего и формы глагола'],
                    'fr' => ['title' => 'Vrai ou faux', 'text' => 'Déterminer si le sujet correspond à la forme du verbe.'],
                    'en' => ['title' => 'True or false', 'text' => 'Decide whether the subject matches the verb form.'],
                ],
            ],
            [
                'position' => 6,
                'key' => '06_find_verb_form',
                'icon' => 'fa-solid fa-magnifying-glass',
                'translations' => [
                    'ru' => ['title' => 'Найти подходящую форму глагола', 'text' => 'Подобрать форму глагола для указанного местоимения'],
                    'fr' => ['title' => 'Trouver la bonne forme', 'text' => 'Choisir la forme correcte du verbe pour le pronom indiqué.'],
                    'en' => ['title' => 'Find the correct verb form', 'text' => 'Choose the correct verb form for the given pronoun.'],
                ],
            ],
            [
                'position' => 7,
                'key' => '07_build_verb_form',
                'icon' => 'fa-solid fa-font',
                'translations' => [
                    'ru' => ['title' => 'Собрать форму глагола', 'text' => 'Собрать форму глагола из предложенных букв'],
                    'fr' => ['title' => 'Composer la forme du verbe', 'text' => 'Reconstituer la forme du verbe à partir des lettres proposées.'],
                    'en' => ['title' => 'Build the verb form', 'text' => 'Build the verb form using the given letters.'],
                ],
            ],
            [
                'position' => 8,
                'key' => '08_restore_verb_form',
                'icon' => 'fa-solid fa-pen-to-square',
                'translations' => [
                    'ru' => ['title' => 'Восстановить форму глагола', 'text' => 'Вписать недостающие буквы, чтобы получить правильную форму глагола'],
                    'fr' => ['title' => 'Compléter la forme du verbe', 'text' => 'Compléter les lettres manquantes pour obtenir la bonne forme.'],
                    'en' => ['title' => 'Complete the verb form', 'text' => 'Fill in the missing letters to complete the verb form.'],
                ],
            ],
            [
                'position' => 9,
                'key' => '09_write_verb_form',
                'icon' => 'fa-solid fa-keyboard',
                'translations' => [
                    'ru' => ['title' => 'Написать форму глагола', 'text' => 'Заполнить пропуск правильной формой глагола'],
                    'fr' => ['title' => 'Écrire la forme du verbe', 'text' => 'Compléter avec la bonne forme du verbe.'],
                    'en' => ['title' => 'Write the verb form', 'text' => 'Fill in the blank with the correct verb form.'],
                ],
            ],
            [
                'position' => 10,
                'key' => '10_complete_sentence',
                'icon' => 'fa-solid fa-align-left',
                'translations' => [
                    'ru' => ['title' => 'Дополнить предложение', 'text' => 'Вписать подходящую форму глагола, чтобы закончить предложение'],
                    'fr' => ['title' => 'Compléter la phrase', 'text' => 'Choisir la bonne forme pour terminer la phrase.'],
                    'en' => ['title' => 'Complete the sentence', 'text' => 'Complete the sentence with the correct verb form.'],
                ],
            ],
            [
                'position' => 11,
                'key' => '11_listen_write',
                'icon' => 'fa-solid fa-volume-high',
                'translations' => [
                    'ru' => ['title' => 'Прослушать и записать', 'text' => 'Прослушать запись и записать услышанное'],
                    'fr' => ['title' => 'Écouter et écrire', 'text' => 'Écouter l’enregistrement puis écrire ce que vous entendez.'],
                    'en' => ['title' => 'Listen and write', 'text' => 'Listen to the recording and write what you hear.'],
                ],
            ],
            [
                'position' => 12,
                'key' => '12_listen_memory',
                'icon' => 'fa-solid fa-brain',
                'translations' => [
                    'ru' => ['title' => 'Послушать и записать по памяти', 'text' => 'Прослушать запись и записать услышанное в том же порядке'],
                    'fr' => ['title' => 'Écouter puis écrire de mémoire', 'text' => 'Écouter puis réécrire les formes dans le même ordre.'],
                    'en' => ['title' => 'Listen and write from memory', 'text' => 'Listen and write the forms back in the same order.'],
                ],
            ],
        ];
    }
}
