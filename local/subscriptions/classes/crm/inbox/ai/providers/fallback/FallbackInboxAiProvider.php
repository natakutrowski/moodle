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
            $request->content
        );

        $scores = [
            'fr' => $this->score_terms(
                $content,
                [
                    'bonjour',
                    'merci',
                    'paiement',
                    'cours',
                    'accès',
                    'abonnement',
                    'problème',
                ]
            ),
            'en' => $this->score_terms(
                $content,
                [
                    'hello',
                    'thank',
                    'payment',
                    'course',
                    'access',
                    'subscription',
                    'problem',
                ]
            ),
            'ru' => $this->score_terms(
                $content,
                [
                    'здравствуйте',
                    'спасибо',
                    'оплата',
                    'курс',
                    'доступ',
                    'подписка',
                    'проблема',
                ]
            ),
        ];

        arsort($scores);

        $language = (string)array_key_first(
            $scores
        );

        $bestscore = (int)reset($scores);

        if ($bestscore === 0) {
            $language = 'unknown';
        }

        return new InboxAiResult(
            InboxAiStatus::SUCCESS,
            $request->capability,
            $this->key(),
            null,
            ['language' => $language],
            $bestscore > 0 ? 0.65 : 0.2,
            [
                'Language was detected using local keyword heuristics.',
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

        $latest = shorten_text(
            $latest,
            500
        );

        return new InboxAiResult(
            InboxAiStatus::PARTIAL,
            $request->capability,
            $this->key(),
            null,
            [
                'summary' => $latest,
                'keypoints' => [],
                'pendingquestions' => [],
                'customerrequests' => [],
                'language' =>
                    $request->requestedlanguage,
            ],
            0.25,
            [
                'The local fallback provides only a limited summary.',
            ],
            null,
            time()
        );
    }
}