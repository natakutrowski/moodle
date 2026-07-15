<?php

namespace local_subscriptions\crm\inbox\ai\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\domain\InboxAiCategory;
use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;
use local_subscriptions\crm\inbox\ai\domain\InboxAiUrgency;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxAiResult;

final class InboxAiResultValidator {

    private const MAX_SUMMARY_LENGTH = 4000;
    private const MAX_REPLY_SUBJECT_LENGTH = 500;
    private const MAX_REPLY_BODY_LENGTH = 12000;
    private const MAX_TRANSLATION_LENGTH = 20000;
    private const MAX_ARRAY_ITEMS = 20;
    private const MAX_ARRAY_ITEM_LENGTH = 1000;

    /**
     * @return string[]
     */
    public function validate(
        InboxAiRequest $request,
        InboxAiResult $result
    ): array {
        $errors = [];

        if (
            !InboxAiCapability::is_valid(
                $result->capability
            )
        ) {
            $errors[] = 'Invalid AI capability.';
        }

        if (
            $result->capability !==
            $request->capability
        ) {
            $errors[] =
                'The result capability does not match the request.';
        }

        if (
            !InboxAiStatus::is_valid(
                $result->status
            )
        ) {
            $errors[] = 'Invalid AI result status.';
        }

        if (
            $result->confidence < 0.0 ||
            $result->confidence > 1.0
        ) {
            $errors[] =
                'Confidence must be between 0 and 1.';
        }

        /*
         * Les résultats indisponibles, bloqués ou en erreur
         * ne contiennent pas nécessairement de payload métier.
         */
        if (!$result->succeeded()) {
            return array_values(
                array_unique($errors)
            );
        }

        $errors = array_merge(
            $errors,
            match ($request->capability) {
                InboxAiCapability::LANGUAGE_DETECTION =>
                    $this->validate_language($result),

                InboxAiCapability::URGENCY_CLASSIFICATION =>
                    $this->validate_urgency($result),

                InboxAiCapability::CATEGORIZATION =>
                    $this->validate_category($result),

                InboxAiCapability::SUMMARY =>
                    $this->validate_summary($result),

                InboxAiCapability::TRANSLATION =>
                    $this->validate_translation($result),

                InboxAiCapability::REPLY_SUGGESTION =>
                    $this->validate_reply($result),

                InboxAiCapability::REQUEST_EXTRACTION =>
                    $this->validate_requests($result),

                InboxAiCapability::CRM_RELEVANCE =>
                    $this->validate_relevance($result),

                default => [
                    'No validator exists for this capability.',
                ],
            }
        );

        return array_values(
            array_unique($errors)
        );
    }

    /**
     * @return string[]
     */
    private function validate_language(
        InboxAiResult $result
    ): array {
        $language = trim(
            (string)($result->data['language'] ?? '')
        );

        if ($language === '') {
            return ['Detected language is missing.'];
        }

        if (
            \core_text::strlen($language) > 16
        ) {
            return ['Detected language is too long.'];
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function validate_urgency(
        InboxAiResult $result
    ): array {
        $errors = [];

        $urgency = trim(
            (string)($result->data['urgency'] ?? '')
        );

        if (!InboxAiUrgency::is_valid($urgency)) {
            $errors[] = 'Invalid urgency value.';
        }

        $errors = array_merge(
            $errors,
            $this->validate_string_array(
                $result->data['signals'] ?? [],
                'Urgency signals'
            )
        );

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validate_category(
        InboxAiResult $result
    ): array {
        $errors = [];

        $category = trim(
            (string)($result->data['category'] ?? '')
        );

        if (!InboxAiCategory::is_valid($category)) {
            $errors[] = 'Invalid category value.';
        }

        $secondary =
            $result->data['secondarycategories']
            ?? [];

        if (!is_array($secondary)) {
            $errors[] =
                'Secondary categories must be an array.';
        } else {
            if (count($secondary) > 2) {
                $errors[] =
                    'Too many secondary categories.';
            }

            foreach ($secondary as $value) {
                if (
                    !InboxAiCategory::is_valid(
                        (string)$value
                    )
                ) {
                    $errors[] =
                        'Invalid secondary category.';
                }
            }
        }

        $errors = array_merge(
            $errors,
            $this->validate_string_array(
                $result->data['signals'] ?? [],
                'Category signals'
            )
        );

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validate_summary(
        InboxAiResult $result
    ): array {
        $errors = [];

        $summary = trim(
            (string)($result->data['summary'] ?? '')
        );

        if ($summary === '') {
            $errors[] = 'Summary is empty.';
        } else if (
            \core_text::strlen($summary) >
            self::MAX_SUMMARY_LENGTH
        ) {
            $errors[] = 'Summary is too long.';
        }

        foreach (
            [
                'keypoints' => 'Key points',
                'pendingquestions' =>
                    'Pending questions',
                'customerrequests' =>
                    'Customer requests',
            ]
            as $key => $label
        ) {
            $errors = array_merge(
                $errors,
                $this->validate_string_array(
                    $result->data[$key] ?? [],
                    $label
                )
            );
        }

        $language = trim(
            (string)($result->data['language'] ?? '')
        );

        if ($language === '') {
            $errors[] =
                'Summary language is missing.';
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validate_translation(
        InboxAiResult $result
    ): array {
        $errors = [];

        $translatedtext = trim(
            (string)(
                $result->data['translatedtext']
                ?? ''
            )
        );

        if ($translatedtext === '') {
            $errors[] = 'Translated text is empty.';
        } else if (
            \core_text::strlen($translatedtext) >
            self::MAX_TRANSLATION_LENGTH
        ) {
            $errors[] =
                'Translated text is too long.';
        }

        foreach (
            ['sourcelanguage', 'targetlanguage']
            as $key
        ) {
            $language = trim(
                (string)($result->data[$key] ?? '')
            );

            if ($language === '') {
                $errors[] =
                    $key . ' is missing.';
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validate_reply(
        InboxAiResult $result
    ): array {
        $errors = [];

        $subject = trim(
            (string)($result->data['subject'] ?? '')
        );

        $body = trim(
            (string)($result->data['body'] ?? '')
        );

        if (
            $subject === '' &&
            $body === ''
        ) {
            $errors[] =
                'The suggested reply is empty.';
        }

        if (
            \core_text::strlen($subject) >
            self::MAX_REPLY_SUBJECT_LENGTH
        ) {
            $errors[] =
                'The suggested subject is too long.';
        }

        if (
            \core_text::strlen($body) >
            self::MAX_REPLY_BODY_LENGTH
        ) {
            $errors[] =
                'The suggested reply body is too long.';
        }

        if (
            ($result->data['requiresreview'] ?? null)
            !== true
        ) {
            $errors[] =
                'AI replies must require human review.';
        }

        $errors = array_merge(
            $errors,
            $this->validate_string_array(
                $result->data['warnings'] ?? [],
                'Reply warnings'
            )
        );

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validate_requests(
        InboxAiResult $result
    ): array {
        $requests =
            $result->data['requests'] ?? null;

        if (!is_array($requests)) {
            return [
                'Extracted requests must be an array.',
            ];
        }

        if (
            count($requests) >
            self::MAX_ARRAY_ITEMS
        ) {
            return [
                'Too many extracted requests.',
            ];
        }

        $errors = [];

        foreach ($requests as $request) {
            if (!is_array($request)) {
                $errors[] =
                    'An extracted request is invalid.';

                continue;
            }

            if (
                trim(
                    (string)(
                        $request['description']
                        ?? ''
                    )
                ) === ''
            ) {
                $errors[] =
                    'An extracted request has no description.';
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validate_relevance(
        InboxAiResult $result
    ): array {
        $relevance =
            $result->data['relevance'] ?? null;

        if (!is_numeric($relevance)) {
            return ['CRM relevance is missing.'];
        }

        $relevance = (float)$relevance;

        if (
            $relevance < 0.0 ||
            $relevance > 1.0
        ) {
            return [
                'CRM relevance must be between 0 and 1.',
            ];
        }

        return $this->validate_string_array(
            $result->data['reasons'] ?? [],
            'CRM relevance reasons'
        );
    }

    /**
     * @return string[]
     */
    private function validate_string_array(
        mixed $value,
        string $label
    ): array {
        if (!is_array($value)) {
            return [
                $label . ' must be an array.',
            ];
        }

        $errors = [];

        if (
            count($value) >
            self::MAX_ARRAY_ITEMS
        ) {
            $errors[] =
                $label . ' contains too many items.';
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                $errors[] =
                    $label .
                    ' must contain strings only.';

                continue;
            }

            if (
                \core_text::strlen($item) >
                self::MAX_ARRAY_ITEM_LENGTH
            ) {
                $errors[] =
                    $label .
                    ' contains an item that is too long.';
            }
        }

        return $errors;
    }
}