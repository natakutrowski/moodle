<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\admin;

defined('MOODLE_INTERNAL') || die();

/**
 * Safely corrects the beneficiary of an issued campaign Personal Offer that has never been delivered/redeemed.
 *
 * This is an exceptional administrative repair path. Normal Personal Offer assignments remain immutable.
 */
final class CommercePersonalOfferBeneficiaryCorrectionService {
    private const CAMPAIGN = 'local_subs_commerce_offer_campaign';
    private const MEMBER = 'local_subs_commerce_offer_campaign_member';
    private const OFFER = 'local_subs_commerce_offer';
    private const MAIL = 'local_subs_commerce_mail';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function can_correct(int $campaignid, int $memberid): bool {
        try {
            $this->load_context($campaignid, $memberid);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{campaign:\stdClass,member:\stdClass,offer:\stdClass,mail:?\stdClass,user:\stdClass} */
    public function preview(int $campaignid, int $memberid, int $targetuserid): array {
        $context = $this->load_context($campaignid, $memberid);
        $user = $this->target_user($targetuserid);
        $this->assert_no_competing_member($campaignid, $memberid, $user);
        return $context + ['user' => $user];
    }

    /** @return array{campaignid:int,memberid:int,offerid:int,mailid:?int,userid:int,oldemail:string,newemail:string} */
    public function correct(int $campaignid, int $memberid, int $targetuserid): array {
        $tx = $this->db->start_delegated_transaction();
        $context = $this->load_context($campaignid, $memberid);
        $user = $this->target_user($targetuserid);
        $this->assert_no_competing_member($campaignid, $memberid, $user);

        $member = $context['member'];
        $offer = $context['offer'];
        $mail = $context['mail'];
        $oldemail = strtolower(trim((string)$offer->beneficiaryemail));
        $newemail = strtolower(trim((string)$user->email));
        $now = time();

        $this->db->update_record(self::MEMBER, (object)[
            'id' => (int)$member->id,
            'userid' => (int)$user->id,
            'email' => $newemail,
            'firstname' => (string)$user->firstname,
            'lastname' => (string)$user->lastname,
            'timemodified' => $now,
        ]);

        // Deliberately bypass the standard immutable repository for this guarded repair operation only.
        $this->db->update_record(self::OFFER, (object)[
            'id' => (int)$offer->id,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => $newemail,
            'timemodified' => $now,
        ]);

        if ($mail !== null) {
            $this->db->update_record(self::MAIL, (object)[
                'id' => (int)$mail->id,
                'userid' => (int)$user->id,
                'recipientemail' => $newemail,
                'recipientname' => fullname($user),
                'status' => 'queued',
                'attemptcount' => 0,
                'nextruntime' => $now,
                'lasterror' => null,
                'timeprocessing' => null,
                'timemodified' => $now,
            ]);
        }

        $tx->allow_commit();
        return [
            'campaignid' => $campaignid,
            'memberid' => $memberid,
            'offerid' => (int)$offer->id,
            'mailid' => $mail === null ? null : (int)$mail->id,
            'userid' => (int)$user->id,
            'oldemail' => $oldemail,
            'newemail' => $newemail,
        ];
    }

    /** @return array{campaign:\stdClass,member:\stdClass,offer:\stdClass,mail:?\stdClass} */
    private function load_context(int $campaignid, int $memberid): array {
        $campaign = $this->db->get_record(self::CAMPAIGN, ['id' => $campaignid], '*', MUST_EXIST);
        if (!in_array((string)$campaign->status, ['issued', 'closed'], true)) {
            throw new \coding_exception('Beneficiary correction requires an issued Personal Offer campaign.');
        }
        $member = $this->db->get_record(self::MEMBER, ['id' => $memberid, 'campaignid' => $campaignid], '*', MUST_EXIST);
        if (empty($member->offerid) || !in_array((string)$member->eligibilitystatus, ['issued', 'replayed'], true)) {
            throw new \coding_exception('Campaign member has no issued Personal Offer to correct.');
        }
        $offer = $this->db->get_record(self::OFFER, ['id' => (int)$member->offerid], '*', MUST_EXIST);
        if ((string)$offer->status !== 'issued' || !empty($offer->redeemedat) || !empty($offer->redeemedpurchaseid)
                || !empty($offer->revokedat) || !empty($offer->revokedbyuserid)) {
            throw new \coding_exception('Only an unused, non-revoked issued Personal Offer can be corrected.');
        }
        $key = 'personal-offer:campaign:' . $campaignid . ':member:' . $memberid;
        $mail = $this->db->get_record(self::MAIL, ['idempotencykey' => $key], '*', IGNORE_MISSING) ?: null;
        if ($mail !== null) {
            if (!empty($mail->timesent) || !empty($mail->timeprocessing) || !in_array((string)$mail->status, ['queued', 'failed'], true)) {
                throw new \coding_exception('Personal Offer beneficiary cannot be corrected after email delivery has started.');
            }
        }
        return ['campaign' => $campaign, 'member' => $member, 'offer' => $offer, 'mail' => $mail];
    }

    private function target_user(int $userid): \stdClass {
        $user = $this->db->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!empty($user->suspended) || !validate_email((string)$user->email)) {
            throw new \coding_exception('The target Moodle account must be active and have a valid email address.');
        }
        return $user;
    }

    private function assert_no_competing_member(int $campaignid, int $memberid, \stdClass $user): void {
        $email = strtolower(trim((string)$user->email));
        $duplicate = $this->db->record_exists_sql(
            'SELECT 1 FROM {' . self::MEMBER . '} WHERE campaignid = :campaignid AND id <> :memberid'
                . ' AND (userid = :userid OR ' . $this->db->sql_equal('email', ':email', false, false) . ')',
            ['campaignid' => $campaignid, 'memberid' => $memberid, 'userid' => (int)$user->id, 'email' => $email]
        );
        if ($duplicate) {
            throw new \coding_exception('Another member of this campaign already targets the selected Moodle account or email.');
        }
    }
}
