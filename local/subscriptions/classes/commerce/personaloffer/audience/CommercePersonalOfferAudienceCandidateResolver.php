<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

final class CommercePersonalOfferAudienceCandidateResolver {
    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @param array<string,array<string,mixed>> $candidates
     * @param array{firstname?:string,lastname?:string} $name
     */
    public function add(
        array &$candidates,
        ?int $userid,
        string $email,
        array $name,
        string $evidence,
        ?int $purchaseid = null
    ): void {
        $email = trim(\core_text::strtolower($email));
        $resolved = null;
        $identityreason = '';

        if ($userid !== null && $userid > 0) {
            $resolved = $this->db->get_record(
                'user',
                ['id' => $userid, 'deleted' => 0],
                'id,firstname,lastname,email',
                IGNORE_MISSING
            );
        }

        if (!$resolved && $email !== '') {
            $matches = $this->db->get_records(
                'user',
                ['email' => $email, 'deleted' => 0],
                'id ASC',
                'id,firstname,lastname,email',
                0,
                2
            );
            if (count($matches) === 1) {
                $resolved = reset($matches);
                $userid = (int)$resolved->id;
            } else if (count($matches) > 1) {
                $identityreason = 'ambiguous_email';
            }
        }

        if ($resolved) {
            $email = (string)$resolved->email;
            $name = [
                'firstname' => (string)$resolved->firstname,
                'lastname' => (string)$resolved->lastname,
            ];
        }

        $key = $userid !== null && $userid > 0
            ? 'u:' . $userid
            : 'e:' . ($email !== '' ? $email : hash('sha256', $evidence));

        if (!isset($candidates[$key])) {
            $candidates[$key] = [
                'userid' => $userid,
                'firstname' => (string)($name['firstname'] ?? ''),
                'lastname' => (string)($name['lastname'] ?? ''),
                'email' => $email,
                'purchaseid' => $purchaseid,
                'evidence' => [],
                'identityreason' => $identityreason,
            ];
        }

        if ($candidates[$key]['purchaseid'] === null && $purchaseid !== null) {
            $candidates[$key]['purchaseid'] = $purchaseid;
        }
        if ($identityreason !== '') {
            $candidates[$key]['identityreason'] = $identityreason;
        }
        $candidates[$key]['evidence'][] = $evidence;
    }
}
