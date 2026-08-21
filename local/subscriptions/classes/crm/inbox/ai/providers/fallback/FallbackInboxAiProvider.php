<?php

namespace local_subscriptions\crm\inbox\ai\providers\fallback;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\contracts\InboxAiProviderInterface;
use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxAiResult;

final class FallbackInboxAiProvider implements
    InboxAiProviderInterface {

    public function key(): string {
        return 'fallback';
    }

    public function is_available(): bool {
        return true;
    }

    public function supports(
        string $capability
    ): bool {
        return in_array(
            $capability,
            [
                InboxAiCapability::LANGUAGE_DETECTION,
                InboxAiCapability::URGENCY_CLASSIFICATION,
                InboxAiCapability::CATEGORIZATION,
                InboxAiCapability::REQUEST_EXTRACTION,
                InboxAiCapability::SUMMARY,
            ],
            true
        );
    }

    public function analyse(
        InboxAiRequest $request
    ): InboxAiResult {
        if (
            !$this->supports(
                $request->capability
            )
        ) {
            return InboxAiResult::unavailable(
                $request->capability,
                $this->key(),
                'The fallback provider does not support this capability.'
            );
        }

        return match ($request->capability) {
            InboxAiCapability::LANGUAGE_DETECTION =>
                $this->detect_language($request),

            InboxAiCapability::URGENCY_CLASSIFICATION =>
                $this->detect_urgency($request),

            InboxAiCapability::CATEGORIZATION =>
                $this->categorize($request),

            InboxAiCapability::REQUEST_EXTRACTION =>
                $this->extract_request($request),

            InboxAiCapability::SUMMARY =>
                $this->summarize($request),

            default =>
                InboxAiResult::unavailable(
                    $request->capability,
                    $this->key(),
                    'Unsupported capability.'
                ),
        };
    }

    private function detect_language(
        InboxAiRequest $request
    ): InboxAiResult {
        $content = \core_text::strtolower(
            trim($request->content)
        );

        if ($content === '') {
            return new InboxAiResult(
                InboxAiStatus::SUCCESS,
                $request->capability,
                $this->key(),
                null,
                ['language' => 'unknown'],
                0.0,
                [
                    'No content was available for local language detection.',
                ],
                null,
                time()
            );
        }

        /*
         * Script detection is much more reliable than keywords for Russian
         * support mail. A normal Cyrillic sentence should never fall back to
         * UNKNOWN simply because it does not contain a short CRM vocabulary.
         */
        $cyrillic = preg_match_all(
            '/\p{Cyrillic}/u',
            $content
        ) ?: 0;

        $letters = preg_match_all(
            '/\p{L}/u',
            $content
        ) ?: 0;

        if (
            $cyrillic >= 3
            && (
                $letters === 0
                || ($cyrillic / $letters) >= 0.25
            )
        ) {
            return new InboxAiResult(
                InboxAiStatus::SUCCESS,
                $request->capability,
                $this->key(),
                null,
                ['language' => 'ru'],
                0.96,
                [
                    'Russian was detected locally from Cyrillic script.',
                ],
                null,
                time()
            );
        }

        $scores = [
            'fr' => $this->score_terms(
                $content,
                [
                    'bonjour',
                    'bonsoir',
                    'merci',
                    'je ',
                    'vous ',
                    'votre ',
                    'mon ',
                    'ma ',
                    'mes ',
                    'cours',
                    'accès',
                    'paiement',
                    'problème',
                    'commande',
                    'abonnement',
                    'pouvez-vous',
                    'comment',
                ]
            ),
            'en' => $this->score_terms(
                $content,
                [
                    'hello',
                    'hi ',
                    'thank',
                    'please',
                    'you ',
                    'your ',
                    'my ',
                    'course',
                    'access',
                    'payment',
                    'problem',
                    'order',
                    'subscription',
                    'can you',
                    'how ',
                ]
            ),
            'ru' => $this->score_terms(
                $content,
                [
                    'здравствуйте',
                    'добрый',
                    'спасибо',
                    'пожалуйста',
                    'курс',
                    'доступ',
                    'оплата',
                    'проблем',
                    'заказ',
                    'подписк',
                    'помог',
                ]
            ),
        ];

        if (
            preg_match(
                '/[àâçéèêëîïôùûüÿœæ]/u',
                $content
            )
        ) {
            $scores['fr'] += 2;
        }

        arsort($scores);

        $language = (string)array_key_first(
            $scores
        );
        $bestscore = (int)reset($scores);
        $seconds = array_values($scores);
        $secondscore = (int)($seconds[1] ?? 0);

        if ($bestscore === 0) {
            $language = 'unknown';
        }

        $confidence = $bestscore === 0
            ? 0.15
            : min(
                0.92,
                0.58
                + ($bestscore * 0.06)
                + max(
                    0,
                    $bestscore - $secondscore
                ) * 0.04
            );

        return new InboxAiResult(
            InboxAiStatus::SUCCESS,
            $request->capability,
            $this->key(),
            null,
            ['language' => $language],
            $confidence,
            [
                'Language was detected using local script and vocabulary heuristics.',
            ],
            null,
            time()
        );
    }

    private function detect_urgency(
        InboxAiRequest $request
    ): InboxAiResult {
        $content = \core_text::strtolower(
            $request->content
        );

        $critical = [
            'fraude',
            'fraud',
            'мошен',
            'compte piraté',
            'account hacked',
            'взлом',
            'double débit',
            'charged twice',
            'списали дважды',
        ];

        $high = [
            'urgent',
            'urgence',
            'срочно',
            'je ne peux pas accéder',
            'cannot access',
            'не могу войти',
            'paiement effectué',
            'payment completed',
            'оплата прошла',
        ];

        $level = 'normal';
        $confidence = 0.55;
        $signals = [];

        foreach ($critical as $term) {
            if (str_contains($content, $term)) {
                $level = 'critical';
                $confidence = 0.9;
                $signals[] = $term;
            }
        }

        if ($level !== 'critical') {
            foreach ($high as $term) {
                if (str_contains($content, $term)) {
                    $level = 'high';
                    $confidence = 0.8;
                    $signals[] = $term;
                }
            }
        }

        return new InboxAiResult(
            InboxAiStatus::SUCCESS,
            $request->capability,
            $this->key(),
            null,
            [
                'urgency' => $level,
                'signals' => array_values(
                    array_unique($signals)
                ),
            ],
            $confidence,
            [
                'Urgency was estimated using local keyword heuristics.',
            ],
            null,
            time()
        );
    }

    private function categorize(
        InboxAiRequest $request
    ): InboxAiResult {
        $content = \core_text::strtolower(
            $request->content
        );

        $categories = [
            'payment' => [
                'paiement',
                'payment',
                'оплата',
                'carte bancaire',
                'bank card',
            ],
            'access' => [
                'accès',
                'access',
                'доступ',
                'connexion',
                'login',
            ],
            'subscription' => [
                'abonnement',
                'subscription',
                'подписка',
                'upgrade',
            ],
            'technical' => [
                'erreur',
                'error',
                'ошибка',
                'bug',
                'page blanche',
            ],
            'content' => [
                'cours',
                'lesson',
                'урок',
                'exercice',
                'exercise',
            ],
        ];

        $scores = [];

        foreach (
            $categories
            as $category => $terms
        ) {
            $scores[$category] =
                $this->score_terms(
                    $content,
                    $terms
                );
        }

        arsort($scores);

        $category = (string)array_key_first(
            $scores
        );

        $score = (int)reset($scores);

        if ($score === 0) {
            $category = 'other';
        }

        return new InboxAiResult(
            InboxAiStatus::SUCCESS,
            $request->capability,
            $this->key(),
            null,
            ['category' => $category],
            $score > 0 ? 0.7 : 0.3,
            [
                'Category was selected using local keyword heuristics.',
            ],
            null,
            time()
        );
    }

    private function extract_request(
        InboxAiRequest $request
    ): InboxAiResult {
        $content = trim($request->content);

        $sentences = preg_split(
            '/(?<=[.!?])\s+/u',
            $content,
            6,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $description = $sentences[0]
            ?? $content;

        $description = shorten_text(
            $description,
            300
        );

        return new InboxAiResult(
            InboxAiStatus::PARTIAL,
            $request->capability,
            $this->key(),
            null,
            [
                'requests' => [
                    [
                        'type' => 'general',
                        'description' => $description,
                        'entities' => [],
                        'confidence' => 0.35,
                    ],
                ],
            ],
            0.35,
            [
                'The fallback provider only extracts the first relevant sentence.',
            ],
            null,
            time()
        );
    }

    private function score_terms(
        string $content,
        array $terms
    ): int {
        $score = 0;

        foreach ($terms as $term) {
            if (
                str_contains(
                    $content,
                    \core_text::strtolower($term)
                )
            ) {
                $score++;
            }
        }

        return $score;
    }

    private function summarize(
        InboxAiRequest $request
    ): InboxAiResult {
        $content = trim($request->content);

        if ($content === '') {
            return InboxAiResult::failed(
                $request->capability,
                $this->key(),
                'Conversation content is empty.'
            );
        }

        $messages = preg_split(
            '/\R---\R/u',
            $content,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [$content];

        $latest = trim(
            (string)end($messages)
        );

        $sentences = preg_split(
            '/(?<=[\.\!\?。！？])\s+/u',
            preg_replace(
                '/\s+/u',
                ' ',
                $latest
            ) ?? $latest,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $sentences = array_values(
            array_filter(
                array_map(
                    static fn(string $sentence): string =>
                        trim($sentence),
                    $sentences
                ),
                static fn(string $sentence): bool =>
                    $sentence !== ''
            )
        );

        $keypoints = array_slice(
            $sentences,
            0,
            4
        );

        $requestterms = [
            '?',
            'помог',
            'подскаж',
            'можно',
            'как ',
            'почему',
            'не могу',
            'please',
            'can you',
            'could you',
            'how ',
            'why ',
            'help',
            'pouvez-vous',
            'comment',
            'pourquoi',
            'aidez',
            'besoin',
        ];

        $customerrequests = [];
        $pendingquestions = [];

        foreach ($sentences as $sentence) {
            $normalized =
                \core_text::strtolower($sentence);

            if (
                str_contains($sentence, '?')
                || str_contains($sentence, '？')
            ) {
                $pendingquestions[] = $sentence;
            }

            foreach ($requestterms as $term) {
                if (
                    str_contains(
                        $normalized,
                        $term
                    )
                ) {
                    $customerrequests[] =
                        $sentence;
                    break;
                }
            }
        }

        $summaryparts = array_slice(
            $sentences,
            0,
            2
        );

        $summary = $summaryparts
            ? implode(' ', $summaryparts)
            : shorten_text(
                $latest,
                700
            );

        return new InboxAiResult(
            InboxAiStatus::PARTIAL,
            $request->capability,
            $this->key(),
            null,
            [
                'summary' =>
                    shorten_text(
                        $summary,
                        900
                    ),
                'keypoints' =>
                    array_values(
                        array_unique($keypoints)
                    ),
                'pendingquestions' =>
                    array_values(
                        array_unique(
                            $pendingquestions
                        )
                    ),
                'customerrequests' =>
                    array_values(
                        array_unique(
                            $customerrequests
                        )
                    ),
                'language' =>
                    $request->requestedlanguage,
            ],
            0.45,
            [
                'The local fallback provides a limited request-oriented analysis.',
            ],
            null,
            time()
        );
    }

}