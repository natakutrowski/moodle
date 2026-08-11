<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

/**
 * Finalises a paid Trial-priced purchase without consuming the global Trial.
 *
 * The Native course fulfillment handler already replaces trialstudent by the
 * purchased role on the purchased course only. This completion service must
 * therefore preserve:
 * - the active Trial subscription;
 * - trialstudent on every other course in the Trial scope;
 * - eligibility for subsequent Trial-priced purchases.
 */
final class CommerceTrialConversionCompletionService {
    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @param \stdClass[] $items Persisted Native purchase items.
     */
    public function complete(
        \stdClass $purchase,
        array $items,
        ?int $completedat = null
    ): CommerceTrialConversionCompletionResult {
        if (!$this->contains_trial_conversion($items)) {
            return CommerceTrialConversionCompletionResult::not_applicable();
        }

        $userid = (int)($purchase->userid ?? 0);
        if ($userid <= 0) {
            throw new \RuntimeException(
                'A fulfilled Trial-priced purchase requires a Moodle user identifier.'
            );
        }

        // Course-specific role replacement is performed by
        // MoodleCourseRoleService during fulfillment:
        // grammarstudent removes trialstudent only from the purchased course;
        // student does the same for the purchased Full course.
        //
        // Deliberately do not replace the active Trial subscription and do not
        // remove Trial roles from the other courses in its access scope.
        return CommerceTrialConversionCompletionResult::completed(0, 0);
    }

    /**
     * @param \stdClass[] $items
     */
    private function contains_trial_conversion(array $items): bool {
        foreach ($items as $item) {
            $metadata = $this->extract_item_metadata($item);
            if (
                strtolower(trim((string)($metadata['operation'] ?? '')))
                === 'trialconversion'
            ) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function extract_item_metadata(\stdClass $item): array {
        $metadata = [];

        foreach (['metadatajson', 'fulfillmentjson'] as $field) {
            $decoded = json_decode(
                (string)($item->{$field} ?? ''),
                true
            );
            if (is_array($decoded)) {
                $metadata = array_replace($metadata, $decoded);
            }
        }

        return $metadata;
    }
}
