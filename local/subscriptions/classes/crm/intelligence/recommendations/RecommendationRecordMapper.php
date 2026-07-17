<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps Recommendation domain objects to persistent database records.
 */
final class RecommendationRecordMapper {

    /**
     * Build fields used when creating a persistent recommendation.
     */
    public function to_create_record(
        Recommendation $recommendation,
        int $now
    ): \stdClass {
        return (object)[
            'fingerprint' => $recommendation->fingerprint(),
            'recommendationkey' => $recommendation->key,
            'recommendationtype' =>
                $recommendation->recommendationtype,
            'presentationtype' => $recommendation->type,
            'priority' => $recommendation->priority,
            'prioritylevel' =>
                $recommendation->prioritylevel,
            'status' => RecommendationStatus::PROPOSED,
            'targettype' =>
                $recommendation->target?->objecttype,
            'targetid' =>
                $recommendation->target?->objectid,
            'sourcesjson' =>
                $this->encode($recommendation->sources),
            'evidencejson' =>
                $this->encode_objects(
                    $recommendation->evidence
                ),
            'actionsjson' =>
                $this->encode_objects(
                    $recommendation->actions
                ),
            'generatedat' =>
                $recommendation->createdat ?? $now,
            'validuntil' =>
                $recommendation->validuntil,
            'firstdetectedat' => $now,
            'lastdetectedat' => $now,
            'acceptedby' => null,
            'acceptedat' => null,
            'dismissedby' => null,
            'dismissedat' => null,
            'completedby' => null,
            'completedat' => null,
            'dismissalreason' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
    }

    /**
     * Build mutable fields refreshed after a new engine run.
     */
    public function to_refresh_record(
        int $id,
        Recommendation $recommendation,
        string $status,
        int $now
    ): \stdClass {
        return (object)[
            'id' => $id,
            'recommendationkey' => $recommendation->key,
            'recommendationtype' =>
                $recommendation->recommendationtype,
            'presentationtype' => $recommendation->type,
            'priority' => $recommendation->priority,
            'prioritylevel' =>
                $recommendation->prioritylevel,
            'status' => $status,
            'targettype' =>
                $recommendation->target?->objecttype,
            'targetid' =>
                $recommendation->target?->objectid,
            'sourcesjson' =>
                $this->encode($recommendation->sources),
            'evidencejson' =>
                $this->encode_objects(
                    $recommendation->evidence
                ),
            'actionsjson' =>
                $this->encode_objects(
                    $recommendation->actions
                ),
            'generatedat' =>
                $recommendation->createdat ?? $now,
            'validuntil' =>
                $recommendation->validuntil,
            'lastdetectedat' => $now,
            'timemodified' => $now,
        ];
    }

    /**
     * Decode a JSON column safely.
     */
    public function decode(?string $json): array {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode(
            $json,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * Encode an array for database persistence.
     */
    private function encode(array $value): string {
        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_THROW_ON_ERROR
        );

        return $encoded;
    }

    /**
     * Encode domain objects exposing to_object().
     */
    private function encode_objects(array $objects): string {
        $values = [];

        foreach ($objects as $object) {
            if (
                !is_object($object) ||
                !method_exists($object, 'to_object')
            ) {
                throw new \InvalidArgumentException(
                    'Recommendation persistence requires serializable domain objects.'
                );
            }

            $values[] = (array)$object->to_object();
        }

        return $this->encode($values);
    }
}