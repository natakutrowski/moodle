<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\Recommendation;
use local_subscriptions\crm\intelligence\recommendations\RecommendationGenerationContext;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationContextBuilder;
use local_subscriptions\crm\success\dto\CustomerSuccessResult;
use local_subscriptions\crm\success\signals\SuccessSignal;

/**
 * Immutable context consumed by correlation rules.
 */
final class CorrelationContext {

    /**
     * @var Recommendation[]
     */
    public readonly array $recommendations;

    /**
     * @var array<string, Recommendation>
     */
    private readonly array $recommendationsbykey;

    /**
     * @var array<string, SuccessSignal>
     */
    private readonly array $signalsbykey;

    /**
     * @param Recommendation[] $recommendations
     */
    public function __construct(
        public readonly RecommendationGenerationContext $generationcontext,
        array $recommendations
    ) {
        $this->recommendations =
            $this->normalize_recommendations(
                $recommendations
            );

        $this->recommendationsbykey =
            $this->index_recommendations(
                $this->recommendations
            );

        $this->signalsbykey =
            $this->index_signals(
                $this->customer_success()
            );
    }

    public function userid(): ?int {
        return $this->generationcontext
            ->resolved_userid();
    }

    public function generatedat(): int {
        return $this->generationcontext
            ->timestamp();
    }

    public function customer_success(): ?CustomerSuccessResult {
        return RecommendationContextBuilder::customer_success(
            $this->generationcontext
        );
    }

    public function has_customer_success(): bool {
        return $this->customer_success() !== null;
    }

    public function has_signal(string $key): bool {
        return isset($this->signalsbykey[$key]);
    }

    public function signal(string $key): ?SuccessSignal {
        return $this->signalsbykey[$key] ?? null;
    }

    /**
     * @param string[] $keys
     * @return SuccessSignal[]
     */
    public function signals(array $keys): array {
        $signals = [];

        foreach ($keys as $key) {
            if (isset($this->signalsbykey[$key])) {
                $signals[] = $this->signalsbykey[$key];
            }
        }

        return $signals;
    }

    /**
     * Return the first present signal among several alternatives.
     *
     * @param string[] $keys
     */
    public function first_signal(
        array $keys
    ): ?SuccessSignal {
        foreach ($keys as $key) {
            if (isset($this->signalsbykey[$key])) {
                return $this->signalsbykey[$key];
            }
        }

        return null;
    }

    /**
     * Count present signals among stable keys.
     *
     * @param string[] $keys
     */
    public function count_signals(array $keys): int {
        $count = 0;

        foreach ($keys as $key) {
            if ($this->has_signal($key)) {
                $count++;
            }
        }

        return $count;
    }

    public function has_recommendation(
        string $key
    ): bool {
        return isset(
            $this->recommendationsbykey[$key]
        );
    }

    public function recommendation(
        string $key
    ): ?Recommendation {
        return $this->recommendationsbykey[$key]
            ?? null;
    }

    /**
     * @param string[] $keys
     * @return Recommendation[]
     */
    public function recommendations_by_keys(
        array $keys
    ): array {
        $recommendations = [];

        foreach ($keys as $key) {
            if (
                isset(
                    $this->recommendationsbykey[$key]
                )
            ) {
                $recommendations[] =
                    $this->recommendationsbykey[$key];
            }
        }

        return $recommendations;
    }

    /**
     * @param Recommendation[] $recommendations
     * @return Recommendation[]
     */
    private function normalize_recommendations(
        array $recommendations
    ): array {
        foreach ($recommendations as $recommendation) {
            if (
                !$recommendation instanceof Recommendation
            ) {
                throw new \InvalidArgumentException(
                    'Correlation context requires Recommendation objects.'
                );
            }
        }

        return array_values($recommendations);
    }

    /**
     * @param Recommendation[] $recommendations
     * @return array<string, Recommendation>
     */
    private function index_recommendations(
        array $recommendations
    ): array {
        $indexed = [];

        foreach ($recommendations as $recommendation) {
            if (!isset($indexed[$recommendation->key])) {
                $indexed[$recommendation->key] =
                    $recommendation;
                continue;
            }

            if (
                $recommendation->priority >
                $indexed[$recommendation->key]->priority
            ) {
                $indexed[$recommendation->key] =
                    $recommendation;
            }
        }

        return $indexed;
    }

    /**
     * @return array<string, SuccessSignal>
     */
    private function index_signals(
        ?CustomerSuccessResult $result
    ): array {
        if ($result === null) {
            return [];
        }

        $indexed = [];

        foreach ($result->signals->all() as $signal) {
            $indexed[$signal->key] = $signal;
        }

        return $indexed;
    }
}