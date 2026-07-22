<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only CRM profile for a deleted Moodle user.
 */
final class HistoricalUserProfileViewModel {

    /**
     * @param int $userid Moodle user ID.
     * @param \stdClass|null $deleteduser Remaining deleted Moodle user record.
     * @param array $subscriptions Historical subscriptions.
     * @param array $digitalpayments Historical digital payments.
     * @param array $notes CRM notes.
     * @param array $tags CRM tags.
     * @param int $historicalcoursecount Number of historical course enrolments.
     * @param array<string, float> $revenuebycurrency Revenue indexed by currency.
     * @param int $lastactivity Last known CRM activity timestamp.
     */
    public function __construct(
        public readonly int $userid,
        public readonly ?\stdClass $deleteduser,
        public readonly array $subscriptions,
        public readonly array $digitalpayments,
        public readonly array $notes,
        public readonly array $tags,
        public readonly int $historicalcoursecount,
        public readonly array $revenuebycurrency,
        public readonly int $lastactivity,
        public readonly array $timeline,
        public readonly bool $timelinehasmore,
        public readonly int $timelinenextoffset,
    ) {
    }

    /**
     * Returns whether any historical CRM information remains.
     */
    public function has_history(): bool {
        return !empty($this->subscriptions)
            || !empty($this->digitalpayments)
            || !empty($this->notes)
            || !empty($this->tags)
            || !empty($this->timeline)
            || $this->historicalcoursecount > 0
            || !empty($this->revenuebycurrency)
            || $this->lastactivity > 0;
    }

    /**
     * Converts the model to a plain object for renderers.
     */
    public function to_object(): \stdClass {
        return (object)[
            'userid' => $this->userid,
            'deleteduser' => $this->deleteduser,
            'subscriptions' => $this->subscriptions,
            'digitalpayments' => $this->digitalpayments,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'historicalcoursecount' => $this->historicalcoursecount,
            'revenuebycurrency' => $this->revenuebycurrency,
            'lastactivity' => $this->lastactivity,
            'timeline' => $this->timeline,
            'timelinehasmore' => $this->timelinehasmore,
            'timelinenextoffset' => $this->timelinenextoffset,
            'hashistory' => $this->has_history(),
            'readonly' => true,
            'deleted' => true,
        ];
    }
}