<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferService;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferAudienceProviderRegistry;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferAudienceRuleEvaluator;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

final class CommercePersonalOfferCampaignManager {
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_SNAPSHOT = 'snapshot';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CLOSED = 'closed';
    public const AUDIENCE_CRITERIA = 'criteria';
    public const AUDIENCE_LIST = 'list';
    public const MEMBER_ELIGIBLE = 'eligible';
    public const MEMBER_EXCLUDED = 'excluded';
    public const MEMBER_COVERED = 'covered';
    public const MEMBER_IDENTITY_REVIEW = 'identity_review';
    public const MEMBER_ERROR = 'error';
    public const MEMBER_ISSUED = 'issued';
    public const MEMBER_REPLAYED = 'replayed';
    public const COLLISION_SKIP = 'skip';
    public const COLLISION_REPLACE = 'replace';
    public const COLLISION_RESEND = 'resend';

    private const CAMPAIGN = 'local_subs_commerce_offer_campaign';
    private const MEMBER = 'local_subs_commerce_offer_campaign_member';
    private const PURCHASE = 'local_subscriptions_commerce_purchase';
    private const ITEM = 'local_subscriptions_commerce_purchase_item';
    private const PAYMENT = 'local_subscriptions_commerce_payment';
    private const PRODUCT = 'local_subs_commerce_product';
    private const SUCCESS = ['paid', 'completed', 'captured', 'succeeded', 'success'];

    public function __construct(private readonly \moodle_database $db, private readonly CommercePersonalOfferService $offers) {}
    public static function create(?\moodle_database $db = null): self { global $DB; $db ??= $DB; return new self($db, CommercePersonalOfferFactory::create($db)); }

    public function create_campaign(array $data, int $userid): int {
        $key = trim((string)($data['campaignkey'] ?? ''));
        if (!preg_match('/^[a-z0-9][a-z0-9._:-]{2,99}$/i', $key)) { throw new \coding_exception('Invalid campaign key.'); }
        if ($this->db->record_exists(self::CAMPAIGN, ['campaignkey' => $key])) { throw new \coding_exception('Campaign key already exists.'); }
        $audience = (string)($data['audiencetype'] ?? self::AUDIENCE_CRITERIA);
        if (!in_array($audience, [self::AUDIENCE_CRITERIA, self::AUDIENCE_LIST], true)) { throw new \coding_exception('Invalid audience type.'); }
        $validitymode = CommercePersonalOfferCampaignValidityService::normalise_mode(
            (string)($data['validitymode'] ?? CommercePersonalOfferCampaignValidityService::MODE_LEGACY)
        );
        $validityduration = isset($data['validityduration']) ? (int)$data['validityduration'] : null;
        if ($validitymode === CommercePersonalOfferCampaignValidityService::MODE_DURATION
            && ($validityduration === null || $validityduration <= 0)) {
            throw new \coding_exception('Personal Offer campaign duration must be positive.');
        }
        $validfrom = isset($data['validfrom']) ? (int)$data['validfrom'] : null;
        $expiresat = isset($data['expiresat']) ? (int)$data['expiresat'] : null;
        if ($validitymode === CommercePersonalOfferCampaignValidityService::MODE_FIXED
            && ($expiresat === null || ($validfrom !== null && $expiresat <= $validfrom))) {
            throw new \coding_exception('A valid fixed Personal Offer campaign expiration is required.');
        }
        $now=time();
        return (int)$this->db->insert_record(self::CAMPAIGN, (object)[
            'campaignkey'=>$key, 'name'=>trim((string)$data['name']), 'audiencetype'=>$audience,
            'sourceproductsku'=>($data['sourceproductsku'] ?? '') !== '' ? strtoupper(trim((string)$data['sourceproductsku'])) : null,
            'targetproductid'=>(int)$data['targetproductid'], 'termsversion'=>(int)($data['termsversion'] ?? 1),
            'termsjson'=>json_encode($data['terms'], JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
            'criteriajson'=>json_encode($data['criteria'] ?? [], JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
            'validfrom'=>$validfrom, 'expiresat'=>$expiresat,
            'validitymode'=>$validitymode,
            'validityduration'=>$validityduration,
            'validitytimezone'=>CommercePersonalOfferCampaignValidityService::normalise_timezone((string)($data['validitytimezone'] ?? CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE)),
            'status'=>self::STATUS_DRAFT, 'timecreated'=>$now, 'timemodified'=>$now, 'usercreated'=>$userid, 'usermodified'=>$userid,
        ]);
    }

    public function get_campaign(int $id): \stdClass { return $this->db->get_record(self::CAMPAIGN, ['id'=>$id], '*', MUST_EXIST); }
    public function list_campaigns(): array { return array_values($this->db->get_records(self::CAMPAIGN, [], 'timemodified DESC')); }
    public function members(int $campaignid): array { return array_values($this->db->get_records(self::MEMBER, ['campaignid'=>$campaignid], 'id ASC')); }
    public function summary(int $campaignid): array {
        $out=['total'=>0,'eligible'=>0,'covered'=>0,'identity_review'=>0,'excluded'=>0,'error'=>0,'issued'=>0,'replayed'=>0];
        foreach ($this->members($campaignid) as $m) { $out['total']++; if (isset($out[$m->eligibilitystatus])) $out[$m->eligibilitystatus]++; }
        return $out;
    }

    public function preview(int $campaignid, int $userid): array {
        $c = $this->get_campaign($campaignid);
        if (in_array($c->status, [self::STATUS_SNAPSHOT, self::STATUS_ISSUED, self::STATUS_CLOSED], true)) {
            throw new \coding_exception('Issued or closed campaigns cannot be recalculated.');
        }

        $criteria = json_decode((string)$c->criteriajson, true, 512, JSON_THROW_ON_ERROR);
        $candidates = $c->audiencetype === self::AUDIENCE_LIST
            ? $this->list_candidates($criteria)
            : $this->criteria_candidates($c, $criteria);

        $this->db->delete_records(self::MEMBER, ['campaignid' => $campaignid]);

        $now = time();
        foreach ($candidates as $candidate) {
            $email = strtolower(trim((string)($candidate['email'] ?? '')));
            $status = self::MEMBER_ELIGIBLE;
            $reason = null;
            $existingofferid = null;

            if (!validate_email($email)) {
                $status = self::MEMBER_EXCLUDED;
                $reason = 'invalid_email';
            } else if (!empty($candidate['identityreason'])) {
                $status = self::MEMBER_IDENTITY_REVIEW;
                $reason = (string)$candidate['identityreason'];
            } else {
                $account = (string)($criteria['account'] ?? 'all');
                if ($account === 'yes' && empty($candidate['userid'])) {
                    $status = self::MEMBER_EXCLUDED;
                    $reason = 'account_required';
                } else if ($account === 'no' && !empty($candidate['userid'])) {
                    $status = self::MEMBER_EXCLUDED;
                    $reason = 'account_not_allowed';
                }
            }

            if ($status === self::MEMBER_ELIGIBLE && !empty($criteria['filtergroups'])) {
                $ruleevaluation = (new CommercePersonalOfferAudienceRuleEvaluator($this->db))->evaluate(
                    $candidate,
                    is_array($criteria['filtergroups']) ? $criteria['filtergroups'] : []
                );
                if (!empty($ruleevaluation['evidence'])) {
                    $candidate['evidence'] = array_merge(
                        is_array($candidate['evidence'] ?? null) ? $candidate['evidence'] : [],
                        $ruleevaluation['evidence']
                    );
                }
                if (!$ruleevaluation['matched']) {
                    $status = self::MEMBER_EXCLUDED;
                    $reason = 'advanced_audience_rules_not_matched';
                }
            }

            if ($status === self::MEMBER_ELIGIBLE && !empty($criteria['excludeowned'])) {
                if ($this->customer_has_target($candidate, (int)$c->targetproductid)) {
                    $status = self::MEMBER_EXCLUDED;
                    $reason = 'target_already_owned';
                }
            }

            if ($status === self::MEMBER_ELIGIBLE) {
                $existingofferid = $this->active_offer_id(
                    $candidate,
                    (int)$c->targetproductid,
                    $now
                );
                if ($existingofferid !== null) {
                    $collisionpolicy = $this->collision_policy($criteria);
                    if ($collisionpolicy === self::COLLISION_SKIP) {
                        $status = self::MEMBER_COVERED;
                        $reason = 'active_offer_exists';
                    } else if ($collisionpolicy === self::COLLISION_REPLACE
                        && $this->offer_has_payment_in_progress($existingofferid)) {
                        $status = self::MEMBER_COVERED;
                        $reason = 'active_offer_payment_in_progress';
                    } else {
                        // Keep the row selectable. Generation will re-check the active offer and either
                        // supersede it or reuse it according to the frozen campaign policy.
                        $reason = $collisionpolicy === self::COLLISION_REPLACE
                            ? 'active_offer_will_be_replaced'
                            : 'active_offer_will_be_resent';
                    }
                }
            }

            $memberkey = !empty($candidate['purchaseid'])
                ? 'purchase:' . (int)$candidate['purchaseid']
                : (!empty($candidate['userid'])
                    ? 'user:' . (int)$candidate['userid']
                    : 'email:' . hash('sha256', $email));

            $this->db->insert_record(self::MEMBER, (object)[
                'campaignid' => $campaignid,
                'memberkey' => $memberkey,
                'purchaseid' => !empty($candidate['purchaseid']) ? (int)$candidate['purchaseid'] : null,
                'userid' => !empty($candidate['userid']) ? (int)$candidate['userid'] : null,
                'firstname' => trim((string)($candidate['firstname'] ?? '')),
                'lastname' => trim((string)($candidate['lastname'] ?? '')),
                'email' => $email !== '' ? $email : '-',
                'evidencejson' => json_encode(
                    array_values(array_unique($candidate['evidence'] ?? [])),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'eligibilitystatus' => $status,
                'reason' => $reason,
                'existingofferid' => $existingofferid,
                'offerid' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }

        $c->status = self::STATUS_PREVIEWED;
        $c->timemodified = $now;
        $c->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $c);

        return $this->summary($campaignid);
    }

    /**
     * Persists the operator selection after a dry-run without touching
     * covered, identity-review or technical-error members.
     *
     * @param int[] $selectedmemberids
     */
    public function update_member_selection(
        int $campaignid,
        array $selectedmemberids,
        int $userid
    ): void {
        $campaign = $this->get_campaign($campaignid);
        if (in_array($campaign->status, [self::STATUS_SNAPSHOT, self::STATUS_ISSUED, self::STATUS_CLOSED], true)) {
            throw new \coding_exception('Issued or closed campaigns cannot be edited.');
        }

        $selected = array_fill_keys(
            array_map('intval', array_filter($selectedmemberids, 'is_numeric')),
            true
        );
        $now = time();

        foreach ($this->members($campaignid) as $member) {
            $status = (string)$member->eligibilitystatus;
            $reason = (string)($member->reason ?? '');

            if (
                $status === self::MEMBER_ELIGIBLE
                && !isset($selected[(int)$member->id])
            ) {
                $member->eligibilitystatus = self::MEMBER_EXCLUDED;
                $member->reason = 'manual_exclusion';
            } else if (
                $status === self::MEMBER_EXCLUDED
                && $reason === 'manual_exclusion'
                && isset($selected[(int)$member->id])
            ) {
                $member->eligibilitystatus = self::MEMBER_ELIGIBLE;
                $member->reason = null;
            } else {
                continue;
            }

            $member->timemodified = $now;
            $this->db->update_record(self::MEMBER, $member);
        }

        $campaign->timemodified = $now;
        $campaign->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $campaign);
    }

    /**
     * Persists selection changes for only the currently visible member page.
     *
     * @param int[] $visiblememberids
     * @param int[] $selectedmemberids
     */
    public function update_visible_member_selection(
        int $campaignid,
        array $visiblememberids,
        array $selectedmemberids,
        int $userid
    ): void {
        $campaign = $this->get_campaign($campaignid);
        if (in_array(
            $campaign->status,
            [self::STATUS_SNAPSHOT, self::STATUS_ISSUED, self::STATUS_CLOSED],
            true
        )) {
            throw new \coding_exception(
                'Issued or closed campaigns cannot be edited.'
            );
        }

        $visible = array_fill_keys(
            array_map(
                'intval',
                array_filter($visiblememberids, 'is_numeric')
            ),
            true
        );
        $selected = array_fill_keys(
            array_map(
                'intval',
                array_filter($selectedmemberids, 'is_numeric')
            ),
            true
        );

        if ($visible === []) {
            return;
        }

        $now = time();
        foreach ($this->members($campaignid) as $member) {
            $memberid = (int)$member->id;
            if (!isset($visible[$memberid])) {
                continue;
            }

            $status = (string)$member->eligibilitystatus;
            $reason = (string)($member->reason ?? '');

            if (
                $status === self::MEMBER_ELIGIBLE
                && !isset($selected[$memberid])
            ) {
                $member->eligibilitystatus = self::MEMBER_EXCLUDED;
                $member->reason = 'manual_exclusion';
            } else if (
                $status === self::MEMBER_EXCLUDED
                && $reason === 'manual_exclusion'
                && isset($selected[$memberid])
            ) {
                $member->eligibilitystatus = self::MEMBER_ELIGIBLE;
                $member->reason = null;
            } else {
                continue;
            }

            $member->timemodified = $now;
            $this->db->update_record(self::MEMBER, $member);
        }

        $campaign->timemodified = $now;
        $campaign->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $campaign);
    }

    public function create_snapshot(int $campaignid, int $userid): array {
        $campaign = $this->get_campaign($campaignid);
        if ($campaign->status !== self::STATUS_PREVIEWED) {
            throw new \coding_exception('Campaign must be previewed before snapshot.');
        }

        $members = array_values(array_filter(
            $this->members($campaignid),
            static fn(\stdClass $member): bool =>
                (string)$member->eligibilitystatus === self::MEMBER_ELIGIBLE
        ));
        if ($members === []) {
            throw new \moodle_exception(
                'commerce_personal_offer_snapshot_empty',
                'local_subscriptions'
            );
        }

        $now = time();

        foreach ($this->members($campaignid) as $member) {
            $member->snapshotselected = (string)$member->eligibilitystatus === self::MEMBER_ELIGIBLE ? 1 : 0;
            $member->timemodified = $now;
            $this->db->update_record(self::MEMBER, $member);
        }

        $members = array_values(array_filter(
            $this->members($campaignid),
            static fn(\stdClass $member): bool => !empty($member->snapshotselected)
        ));

        $campaign->snapshotat = $now;
        $campaign->selectedcount = count($members);
        $campaign->snapshothash = $this->snapshot_hash($campaign, $members);
        $campaign->status = self::STATUS_SNAPSHOT;
        $campaign->timemodified = $now;
        $campaign->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $campaign);

        return [
            'selected' => count($members),
            'hash' => (string)$campaign->snapshothash,
            'snapshotat' => $now,
        ];
    }

    private function snapshot_hash(\stdClass $campaign, array $members): string {
        $payload = [
            'campaignid' => (int)$campaign->id,
            'campaignkey' => (string)$campaign->campaignkey,
            'targetproductid' => (int)$campaign->targetproductid,
            'termsversion' => (int)$campaign->termsversion,
            'termsjson' => (string)$campaign->termsjson,
            'criteriajson' => (string)$campaign->criteriajson,
            'validfrom' => empty($campaign->validfrom) ? null : (int)$campaign->validfrom,
            'expiresat' => empty($campaign->expiresat) ? null : (int)$campaign->expiresat,
            'validitymode' => (string)($campaign->validitymode ?? CommercePersonalOfferCampaignValidityService::MODE_LEGACY),
            'validityduration' => empty($campaign->validityduration) ? null : (int)$campaign->validityduration,
            'validitytimezone' => (string)($campaign->validitytimezone ?? CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE),
            'members' => [],
        ];

        foreach ($members as $member) {
            $payload['members'][] = [
                'id' => (int)$member->id,
                'memberkey' => (string)$member->memberkey,
                'purchaseid' => empty($member->purchaseid) ? null : (int)$member->purchaseid,
                'userid' => empty($member->userid) ? null : (int)$member->userid,
                'email' => strtolower(trim((string)$member->email)),
                'firstname' => (string)($member->firstname ?? ''),
                'lastname' => (string)($member->lastname ?? ''),
                'evidencejson' => (string)($member->evidencejson ?? ''),
            ];
        }

        return hash(
            'sha256',
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    private function assert_snapshot_integrity(\stdClass $campaign): array {
        if (
            $campaign->status !== self::STATUS_SNAPSHOT
            || empty($campaign->snapshotat)
            || empty($campaign->snapshothash)
        ) {
            throw new \coding_exception('A frozen campaign snapshot is required before generation.');
        }

        $members = array_values(array_filter(
            $this->members((int)$campaign->id),
            static fn(\stdClass $member): bool => !empty($member->snapshotselected)
        ));

        if (count($members) !== (int)$campaign->selectedcount) {
            throw new \moodle_exception(
                'commerce_personal_offer_snapshot_changed',
                'local_subscriptions'
            );
        }

        if (!hash_equals(
            (string)$campaign->snapshothash,
            $this->snapshot_hash($campaign, $members)
        )) {
            throw new \moodle_exception(
                'commerce_personal_offer_snapshot_changed',
                'local_subscriptions'
            );
        }

        return $members;
    }

    public function generate(int $campaignid, int $userid): array {
        $campaign = $this->get_campaign($campaignid);
        $members = $this->assert_snapshot_integrity($campaign);

        $terms = new CommercePersonalOfferTerms(
            json_decode((string)$campaign->termsjson, true, 512, JSON_THROW_ON_ERROR)
        );
        $criteria = json_decode(
            (string)$campaign->criteriajson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $now = time();

        foreach ($members as $member) {
            if (!in_array(
                (string)$member->eligibilitystatus,
                [self::MEMBER_ELIGIBLE, self::MEMBER_ERROR],
                true
            )) {
                continue;
            }

            try {
                $candidate = [
                    'purchaseid' => empty($member->purchaseid) ? null : (int)$member->purchaseid,
                    'userid' => empty($member->userid) ? null : (int)$member->userid,
                    'email' => (string)$member->email,
                    'firstname' => (string)($member->firstname ?? ''),
                    'lastname' => (string)($member->lastname ?? ''),
                    'evidence' => json_decode((string)($member->evidencejson ?? '[]'), true) ?: [],
                ];

                // Do not recalculate source eligibility. Only protect against a
                // newly acquired target product or another active offer.
                if (
                    !empty($criteria['excludeowned'])
                    && $this->customer_has_target($candidate, (int)$campaign->targetproductid)
                ) {
                    $member->eligibilitystatus = self::MEMBER_EXCLUDED;
                    $member->reason = 'target_acquired_after_snapshot';
                    $member->timemodified = $now;
                    $this->db->update_record(self::MEMBER, $member);
                    continue;
                }

                $existingofferid = $this->active_offer_id(
                    $candidate,
                    (int)$campaign->targetproductid,
                    $now
                );
                if ($existingofferid !== null) {
                    $existing = $this->db->get_record(
                        'local_subs_commerce_offer',
                        ['id' => $existingofferid],
                        'id,offeruuid,campaignkey',
                        MUST_EXIST
                    );

                    if ((string)$existing->campaignkey === (string)$campaign->campaignkey) {
                        // Recovery path: the offer may have been issued before
                        // an interrupted request could update the member row.
                        $member->eligibilitystatus = self::MEMBER_REPLAYED;
                        $member->offerid = $existingofferid;
                        $member->reason = null;
                        $member->timemodified = $now;
                        $this->db->update_record(self::MEMBER, $member);
                        continue;
                    }

                    $collisionpolicy = $this->collision_policy($criteria);
                    if ($collisionpolicy === self::COLLISION_RESEND) {
                        $member->eligibilitystatus = self::MEMBER_REPLAYED;
                        $member->offerid = $existingofferid;
                        $member->existingofferid = $existingofferid;
                        $member->reason = 'active_offer_reused';
                        $member->timemodified = $now;
                        $this->db->update_record(self::MEMBER, $member);
                        continue;
                    }

                    if ($collisionpolicy === self::COLLISION_REPLACE) {
                        if ($this->offer_has_payment_in_progress($existingofferid)) {
                            $member->eligibilitystatus = self::MEMBER_COVERED;
                            $member->existingofferid = $existingofferid;
                            $member->reason = 'active_offer_payment_in_progress';
                            $member->timemodified = $now;
                            $this->db->update_record(self::MEMBER, $member);
                            continue;
                        }

                        $this->offers->revoke(
                            (string)$existing->offeruuid,
                            $userid,
                            'superseded_by_campaign:' . (string)$campaign->campaignkey,
                            $now
                        );
                        $member->existingofferid = $existingofferid;
                        // Continue below: a fresh offer/token is issued for this campaign.
                    } else {
                        $member->eligibilitystatus = self::MEMBER_COVERED;
                        $member->existingofferid = $existingofferid;
                        $member->reason = 'active_offer_created_after_snapshot';
                        $member->timemodified = $now;
                        $this->db->update_record(self::MEMBER, $member);
                        continue;
                    }
                }

                $validity = (new CommercePersonalOfferCampaignValidityService())->resolve($campaign, time());
                $result = $this->offers->issue(new CommercePersonalOfferIssueRequest(
                    'crm-campaign:' . $campaign->campaignkey . ':' . $member->memberkey,
                    (int)$campaign->targetproductid,
                    (string)$member->email,
                    $terms,
                    (string)$campaign->campaignkey,
                    empty($member->purchaseid) ? null : (int)$member->purchaseid,
                    empty($member->userid) ? null : (int)$member->userid,
                    $validity['validfrom'],
                    $validity['expiresat'],
                    array_filter([
                        'campaignsource' => 'crm_ui',
                        'eligibilitymode' => 'campaign_snapshot',
                        'campaignid' => (int)$campaign->id,
                        'campaignkey' => (string)$campaign->campaignkey,
                        'campaignname' => (string)$campaign->name,
                        'campaignmemberid' => (int)$member->id,
                        'validitymode' => (string)($campaign->validitymode ?? CommercePersonalOfferCampaignValidityService::MODE_LEGACY),
                        'validitytimezone' => (string)($campaign->validitytimezone ?? CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE),
                        'audiencetype' => (string)$campaign->audiencetype,
                        'sourceproductsku' => $campaign->sourceproductsku !== null
                            ? (string)$campaign->sourceproductsku
                            : null,
                        'eligibilitysourcetype' => $criteria['sourcetype'] ?? null,
                        'eligibilitysourceid' => $criteria['sourceid'] ?? null,
                        'eligibilityevidence' => $candidate['evidence'],
                        'collisionpolicy' => $this->collision_policy($criteria),
                        'supersedesofferid' => !empty($member->existingofferid) ? (int)$member->existingofferid : null,
                        'snapshotat' => (int)$campaign->snapshotat,
                        'snapshothash' => (string)$campaign->snapshothash,
                    ], static fn($value) => $value !== null && $value !== ''),
                    $userid
                ));

                $member->eligibilitystatus = $result->is_replayed()
                    ? self::MEMBER_REPLAYED
                    : self::MEMBER_ISSUED;
                $member->offerid = $result->get_offer()->get_id();
                $member->reason = null;
            } catch (\Throwable $exception) {
                $member->eligibilitystatus = self::MEMBER_ERROR;
                $member->reason = $exception->getMessage();
            }

            $member->timemodified = time();
            $this->db->update_record(self::MEMBER, $member);
        }

        $campaign->status = self::STATUS_ISSUED;
        $campaign->timemodified = time();
        $campaign->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $campaign);

        return $this->summary($campaignid);
    }

    public function retry_generation_errors(int $campaignid, int $userid): array {
        $campaign = $this->get_campaign($campaignid);
        if ((string)$campaign->status !== self::STATUS_ISSUED) {
            throw new \coding_exception(
                'Generation retries require an issued Personal Offer campaign.'
            );
        }

        $errors = 0;
        foreach ($this->members($campaignid) as $member) {
            if (
                !empty($member->snapshotselected)
                && (string)$member->eligibilitystatus === self::MEMBER_ERROR
            ) {
                $errors++;
            }
        }

        if ($errors === 0) {
            return $this->summary($campaignid);
        }

        // Re-open only the immutable snapshot execution state. generate()
        // ignores every terminal member and therefore retries failed rows only.
        $campaign->status = self::STATUS_SNAPSHOT;
        $campaign->timemodified = time();
        $campaign->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $campaign);

        return $this->generate($campaignid, $userid);
    }

    public function certification_state(
        int $campaignid,
        array $mailsummary
    ): array {
        $campaign = $this->get_campaign($campaignid);
        $members = $this->members($campaignid);

        $generationerrors = 0;
        $selectedpending = 0;
        foreach ($members as $member) {
            if ((string)$member->eligibilitystatus === self::MEMBER_ERROR) {
                $generationerrors++;
            }

            if (
                !empty($member->snapshotselected)
                && in_array(
                    (string)$member->eligibilitystatus,
                    [self::MEMBER_ELIGIBLE, self::MEMBER_IDENTITY_REVIEW],
                    true
                )
            ) {
                $selectedpending++;
            }
        }

        $mailblocking = (int)($mailsummary['notqueued'] ?? 0)
            + (int)($mailsummary['queued'] ?? 0)
            + (int)($mailsummary['processing'] ?? 0)
            + (int)($mailsummary['failed'] ?? 0)
            + (int)($mailsummary['cancelled'] ?? 0);

        $ready = (string)$campaign->status === self::STATUS_ISSUED
            && $generationerrors === 0
            && $selectedpending === 0
            && $mailblocking === 0;

        return [
            'ready' => $ready,
            'generationerrors' => $generationerrors,
            'selectedpending' => $selectedpending,
            'mailblocking' => $mailblocking,
        ];
    }

    public function certify_campaign(
        int $campaignid,
        int $userid,
        array $mailsummary
    ): array {
        $campaign = $this->get_campaign($campaignid);
        if ((string)$campaign->status === self::STATUS_CLOSED) {
            return [
                'certified' => true,
                'certifiedat' => empty($campaign->certifiedat)
                    ? null
                    : (int)$campaign->certifiedat,
            ];
        }

        $state = $this->certification_state($campaignid, $mailsummary);
        if (!$state['ready']) {
            throw new \moodle_exception(
                'commerce_personal_offer_campaign_not_certifiable',
                'local_subscriptions'
            );
        }

        $now = time();
        $campaign->status = self::STATUS_CLOSED;
        $campaign->certifiedat = $now;
        $campaign->certifiedby = $userid;
        $campaign->timemodified = $now;
        $campaign->usermodified = $userid;
        $this->db->update_record(self::CAMPAIGN, $campaign);

        return [
            'certified' => true,
            'certifiedat' => $now,
        ];
    }

    public function issue_individual(array $data, int $userid): array {
        $email=strtolower(trim((string)$data['email'])); if (!validate_email($email)) throw new \coding_exception('Valid beneficiary email required.');
        $terms=new CommercePersonalOfferTerms($data['terms']);
        $key='crm-individual:'.hash('sha256',implode('|',[$email,(int)$data['targetproductid'],(int)($data['sourcepurchaseid']??0),microtime(true),random_int(1,PHP_INT_MAX)]));
        $result=$this->offers->issue(new CommercePersonalOfferIssueRequest($key,(int)$data['targetproductid'],$email,$terms,
            ($data['campaignkey']??'')!==''?(string)$data['campaignkey']:'crm-individual',empty($data['sourcepurchaseid'])?null:(int)$data['sourcepurchaseid'],
            empty($data['beneficiaryuserid'])?null:(int)$data['beneficiaryuserid'],$data['validfrom']??null,$data['expiresat']??null,
            array_filter([
                'campaignsource' => 'crm_individual',
                'eligibilitymode' => $data['eligibilitymode'] ?? 'standalone',
                'ownershipsource' => $data['ownershipsource'] ?? null,
                'ownershipproductid' => $data['ownershipproductid'] ?? null,
                'ownershipproductsku' => $data['ownershipproductsku'] ?? null,
                'validitymode' => $data['validitymode'] ?? null,
                'validitytimezone' => $data['validitytimezone'] ?? null,
                'noexpiration' => $data['noexpiration'] ?? null,
                'mailtemplateid' => $data['mailtemplateid'] ?? null,
                'mailtemplatename' => $data['mailtemplatename'] ?? null,
                'mailtemplatesnapshot' => $data['mailtemplatesnapshot'] ?? null,
            ], static fn($value) => $value !== null && $value !== '' && $value !== []), $userid));
        return ['offer'=>$result->get_offer(),'token'=>$result->get_token()];
    }

    private function list_candidates(array $criteria): array {
        $raw = (string)($criteria['list'] ?? '');
        $parts = preg_split('/[\s,;]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        $seen = [];

        foreach ($parts as $part) {
            $userid = null;
            $email = '';
            $firstname = '';
            $lastname = '';
            $identityreason = '';

            if (ctype_digit($part)) {
                $user = $this->db->get_record(
                    'user',
                    ['id' => (int)$part, 'deleted' => 0],
                    'id,email,firstname,lastname',
                    IGNORE_MISSING
                );
                if ($user) {
                    $userid = (int)$user->id;
                    $email = (string)$user->email;
                    $firstname = (string)$user->firstname;
                    $lastname = (string)$user->lastname;
                }
            } else {
                $email = strtolower(trim($part));
                $matches = $this->db->get_records(
                    'user',
                    ['email' => $email, 'deleted' => 0],
                    'id ASC',
                    'id,email,firstname,lastname',
                    0,
                    2
                );
                if (count($matches) === 1) {
                    $user = reset($matches);
                    $userid = (int)$user->id;
                    $firstname = (string)$user->firstname;
                    $lastname = (string)$user->lastname;
                } else if (count($matches) > 1) {
                    $identityreason = 'ambiguous_email';
                }
            }

            if ($email === '' || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = 1;

            $out[] = [
                'purchaseid' => null,
                'userid' => $userid,
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'evidence' => ['explicit_list'],
                'identityreason' => $identityreason,
            ];
        }

        return $out;
    }

    private function criteria_candidates(\stdClass $campaign, array $criteria): array {
        $sourcetype = strtolower(trim((string)($criteria['sourcetype'] ?? '')));
        $sourceid = (int)($criteria['sourceid'] ?? 0);

        // Backward compatibility for K8/K9 campaigns created with sourceproductsku.
        if (($sourcetype === '' || $sourceid <= 0) && !empty($campaign->sourceproductsku)) {
            $record = $this->db->get_record(
                self::PRODUCT,
                ['sku' => strtoupper((string)$campaign->sourceproductsku)],
                'id',
                IGNORE_MISSING
            );
            if ($record) {
                $sourcetype = 'native_product';
                $sourceid = (int)$record->id;
            }
        }

        if ($sourcetype === '' || $sourceid <= 0) {
            throw new \coding_exception('Criteria campaigns require an eligibility source.');
        }

        $sources = [[
            'sourcetype' => $sourcetype,
            'sourceid' => $sourceid,
        ]];
        foreach (($criteria['additionalsources'] ?? []) as $additionalsource) {
            if (!is_array($additionalsource)) {
                continue;
            }
            $additionalsourcetype = strtolower(trim((string)($additionalsource['sourcetype'] ?? '')));
            $additionalsourceid = (int)($additionalsource['sourceid'] ?? 0);
            if ($additionalsourcetype === '' || $additionalsourceid <= 0) {
                continue;
            }
            $key = $additionalsourcetype . ':' . $additionalsourceid;
            $already = false;
            foreach ($sources as $source) {
                if ($source['sourcetype'] . ':' . $source['sourceid'] === $key) {
                    $already = true;
                    break;
                }
            }
            if (!$already) {
                $sources[] = [
                    'sourcetype' => $additionalsourcetype,
                    'sourceid' => $additionalsourceid,
                ];
            }
        }

        $registry = CommercePersonalOfferAudienceProviderRegistry::create($this->db);
        $language = clean_param(current_language(), PARAM_LANG) ?: 'fr';
        $merged = [];
        foreach ($sources as $source) {
            $provider = $registry->get((string)$source['sourcetype']);
            foreach ($provider->candidates((int)$source['sourceid'], $criteria, $language) as $candidate) {
                $key = !empty($candidate['userid'])
                    ? 'u:' . (int)$candidate['userid']
                    : 'e:' . strtolower(trim((string)($candidate['email'] ?? '')));
                if (!isset($merged[$key])) {
                    $merged[$key] = $candidate;
                    continue;
                }
                $merged[$key]['evidence'] = array_values(array_unique(array_merge(
                    is_array($merged[$key]['evidence'] ?? null) ? $merged[$key]['evidence'] : [],
                    is_array($candidate['evidence'] ?? null) ? $candidate['evidence'] : []
                )));
                if (empty($merged[$key]['purchaseid']) && !empty($candidate['purchaseid'])) {
                    $merged[$key]['purchaseid'] = (int)$candidate['purchaseid'];
                }
                if (empty($merged[$key]['identityreason']) && !empty($candidate['identityreason'])) {
                    $merged[$key]['identityreason'] = (string)$candidate['identityreason'];
                }
            }
        }

        return array_values($merged);
    }

    private function customer_has_target(array $candidate, int $targetproductid): bool {
        $target = $this->db->get_record(
            self::PRODUCT,
            ['id' => $targetproductid],
            'sku',
            MUST_EXIST
        );

        if (!empty($candidate['userid'])) {
            return (new CommerceStorefrontOwnershipResolver($this->db))->owns(
                (int)$candidate['userid'],
                (string)$target->sku
            );
        }

        if (empty($candidate['email'])) {
            return false;
        }

        $params = [
            'sku' => strtoupper((string)$target->sku),
            'email' => strtolower((string)$candidate['email']),
        ];
        $success = [];
        foreach (self::SUCCESS as $i => $status) {
            $params['o' . $i] = $status;
            $success[] = ':o' . $i;
        }

        return $this->db->record_exists_sql(
            'SELECT 1
               FROM {' . self::PURCHASE . '} p
               JOIN {' . self::ITEM . '} i ON i.purchaseid = p.id
              WHERE ' . $this->db->sql_equal('p.customeremail', ':email', false, false) . '
                AND UPPER(i.itemreference) = :sku
                AND EXISTS (
                    SELECT 1
                      FROM {' . self::PAYMENT . '} pay
                     WHERE pay.purchaseid = p.id
                       AND pay.status IN (' . implode(',', $success) . ')
                )',
            $params
        );
    }

    private function collision_policy(array $criteria): string {
        $policy = strtolower(trim((string)($criteria['collisionpolicy'] ?? self::COLLISION_SKIP)));
        return in_array($policy, [self::COLLISION_SKIP, self::COLLISION_REPLACE, self::COLLISION_RESEND], true)
            ? $policy
            : self::COLLISION_SKIP;
    }

    /**
     * Protect an offer that is already attached to a checkout/payment attempt still in flight.
     * The Personal Offer UUID is captured in immutable purchase-item metadata by checkout.
     */
    private function offer_has_payment_in_progress(int $offerid): bool {
        $offer = $this->db->get_record('local_subs_commerce_offer', ['id' => $offerid], 'id,offeruuid', IGNORE_MISSING);
        if (!$offer) {
            return false;
        }

        $needle = '%"personal_offer_uuid":"' . $this->db->sql_like_escape((string)$offer->offeruuid) . '"%';
        $items = $this->db->get_records_select(
            self::ITEM,
            $this->db->sql_like('metadatajson', ':offerneedle', false),
            ['offerneedle' => $needle],
            '',
            'id,purchaseid',
            0,
            100
        );
        if (!$items) {
            return false;
        }

        foreach ($items as $item) {
            $payments = $this->db->get_records(self::PAYMENT, ['purchaseid' => (int)$item->purchaseid], 'id DESC', 'id,status');
            foreach ($payments as $payment) {
                $status = strtolower(trim((string)$payment->status));
                if (in_array($status, ['pending', 'processing', 'created', 'initiated', 'authorized', 'requires_action'], true)) {
                    return true;
                }
                // The newest terminal payment is decisive for this purchase.
                if (in_array($status, array_merge(self::SUCCESS, ['failed', 'cancelled', 'canceled', 'refunded']), true)) {
                    break;
                }
            }
        }
        return false;
    }

    private function active_offer_id(array $candidate, int $targetproductid, int $now): ?int {
        $identity = [];
        $params = [
            'targetproductid' => $targetproductid,
            'issued' => \local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer::STATUS_ISSUED,
            'now' => $now,
        ];

        if (!empty($candidate['userid'])) {
            $identity[] = 'beneficiaryuserid = :beneficiaryuserid';
            $params['beneficiaryuserid'] = (int)$candidate['userid'];
        }
        if (!empty($candidate['email'])) {
            $identity[] = $this->db->sql_equal(
                'beneficiaryemail',
                ':beneficiaryemail',
                false,
                false
            );
            $params['beneficiaryemail'] = strtolower((string)$candidate['email']);
        }
        if ($identity === []) {
            return null;
        }

        $sql = 'SELECT id
                  FROM {local_subs_commerce_offer}
                 WHERE targetproductid = :targetproductid
                   AND status = :issued
                   AND (expiresat IS NULL OR expiresat >= :now)
                   AND (' . implode(' OR ', $identity) . ')
              ORDER BY id DESC';

        $record = $this->db->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        return $record ? (int)$record->id : null;
    }
}
