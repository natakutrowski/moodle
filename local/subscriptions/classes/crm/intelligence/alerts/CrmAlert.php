<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable CRM alert DTO.
 *
 * Alert detection data and lightweight user presentation data are carried
 * together so read-side consumers never need to reload the Moodle user.
 */
final class CrmAlert {

    public function __construct(
        public readonly string $key,
        public readonly string $severity = 'info',
        public readonly int $priority = 50,
        public readonly ?int $userid = null,
        public readonly ?string $displayname = null,
        public readonly ?string $email = null,
        public readonly ?int $snapshottime = null,
        public readonly ?int $commercialscore = null,
        public readonly ?int $engagementscore = null,
        public readonly ?int $riskscore = null,
        public readonly ?int $globalscore = null
    ) {
    }

    /**
     * Converts the alert to a generic object.
     */
    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'severity' => $this->severity,
            'priority' => $this->priority,
            'userid' => $this->userid,
            'displayname' => $this->displayname,
            'email' => $this->email,
            'snapshottime' => $this->snapshottime,
            'commercialscore' => $this->commercialscore,
            'engagementscore' => $this->engagementscore,
            'riskscore' => $this->riskscore,
            'globalscore' => $this->globalscore,
        ];
    }

    /**
     * Returns whether the alert contains usable user identity information.
     */
    public function has_user_identity(): bool {
        return
            $this->userid !== null &&
            $this->userid > 0 &&
            $this->displayname !== null &&
            trim($this->displayname) !== '';
    }
}