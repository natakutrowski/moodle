<?php

namespace local_subscriptions;

use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferBeneficiaryCorrectionService;

final class commerce_personal_offer_beneficiary_correction_m43b_test extends \advanced_testcase {
    public function test_corrects_unused_offer_member_and_queued_mail_without_changing_memberkey(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $memberid, $offerid, $mailid] = $this->fixture();
        $user = $this->getDataGenerator()->create_user(['email' => 'correct@example.test', 'firstname' => 'Correct', 'lastname' => 'User']);

        $result = CommercePersonalOfferBeneficiaryCorrectionService::create($DB)->correct($campaignid, $memberid, (int)$user->id);
        $member = $DB->get_record('local_subs_commerce_offer_campaign_member', ['id' => $memberid], '*', MUST_EXIST);
        $offer = $DB->get_record('local_subs_commerce_offer', ['id' => $offerid], '*', MUST_EXIST);
        $mail = $DB->get_record('local_subs_commerce_mail', ['id' => $mailid], '*', MUST_EXIST);

        $this->assertSame('email:original-snapshot-key', $member->memberkey);
        $this->assertSame((int)$user->id, (int)$member->userid);
        $this->assertSame('correct@example.test', $member->email);
        $this->assertSame((int)$user->id, (int)$offer->beneficiaryuserid);
        $this->assertSame('correct@example.test', $offer->beneficiaryemail);
        $this->assertSame('queued', $mail->status);
        $this->assertSame(0, (int)$mail->attemptcount);
        $this->assertSame('correct@example.test', $mail->recipientemail);
        $this->assertSame($offerid, $result['offerid']);
    }

    public function test_refuses_sent_mail(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $memberid, , $mailid] = $this->fixture();
        $DB->set_field('local_subs_commerce_mail', 'status', 'sent', ['id' => $mailid]);
        $DB->set_field('local_subs_commerce_mail', 'timesent', time(), ['id' => $mailid]);
        $user = $this->getDataGenerator()->create_user(['email' => 'sent-target@example.test']);
        $this->expectException(\coding_exception::class);
        CommercePersonalOfferBeneficiaryCorrectionService::create($DB)->correct($campaignid, $memberid, (int)$user->id);
    }

    public function test_refuses_redeemed_offer(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $memberid, $offerid] = $this->fixture();
        $DB->set_field('local_subs_commerce_offer', 'status', 'redeemed', ['id' => $offerid]);
        $DB->set_field('local_subs_commerce_offer', 'redeemedat', time(), ['id' => $offerid]);
        $user = $this->getDataGenerator()->create_user(['email' => 'redeemed-target@example.test']);
        $this->expectException(\coding_exception::class);
        CommercePersonalOfferBeneficiaryCorrectionService::create($DB)->correct($campaignid, $memberid, (int)$user->id);
    }

    public function test_refuses_competing_campaign_member(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$campaignid, $memberid] = $this->fixture();
        $user = $this->getDataGenerator()->create_user(['email' => 'duplicate@example.test']);
        $now = time();
        $DB->insert_record('local_subs_commerce_offer_campaign_member', (object)[
            'campaignid' => $campaignid, 'memberkey' => 'user:' . $user->id, 'purchaseid' => null, 'userid' => $user->id,
            'firstname' => $user->firstname, 'lastname' => $user->lastname, 'email' => $user->email, 'evidencejson' => '[]',
            'eligibilitystatus' => 'excluded', 'reason' => null, 'existingofferid' => null, 'snapshotselected' => 0,
            'offerid' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $this->expectException(\coding_exception::class);
        CommercePersonalOfferBeneficiaryCorrectionService::create($DB)->correct($campaignid, $memberid, (int)$user->id);
    }

    private function fixture(): array {
        global $DB;
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'M43B.TEST', 'type' => 'digital', 'status' => 'active', 'name' => 'M43B', 'description' => null,
            'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $campaignid = (int)$DB->insert_record('local_subs_commerce_offer_campaign', (object)[
            'campaignkey' => 'm43b-' . bin2hex(random_bytes(4)), 'name' => 'M43B', 'audiencetype' => 'criteria',
            'sourceproductsku' => null, 'targetproductid' => $productid, 'termsversion' => 1,
            'termsjson' => '{"pricing":{"strategy":"fixed_price","amounts":{"EUR":2900}}}', 'criteriajson' => '{}',
            'validfrom' => null, 'expiresat' => $now + DAYSECS, 'validitymode' => 'fixed_datetime', 'validityduration' => null,
            'validitytimezone' => 'Europe/Paris', 'snapshotat' => $now, 'snapshothash' => hash('sha256', 'm43b'),
            'selectedcount' => 1, 'certifiedat' => null, 'certifiedby' => null, 'status' => 'issued',
            'timecreated' => $now, 'timemodified' => $now, 'usercreated' => null, 'usermodified' => null,
        ]);
        $offerid = (int)$DB->insert_record('local_subs_commerce_offer', (object)[
            'offeruuid' => bin2hex(random_bytes(16)), 'campaignkey' => 'm43b', 'sourcepurchaseid' => null,
            'targetproductid' => $productid, 'beneficiaryuserid' => null, 'beneficiaryemail' => 'wrong@example.test',
            'status' => 'issued', 'validfrom' => null, 'expiresat' => $now + DAYSECS, 'validitymode' => 'fixed_datetime',
            'validityduration' => null, 'validitytimezone' => 'Europe/Paris', 'termsversion' => 1,
            'termsjson' => '{"pricing":{"strategy":"fixed_price","amounts":{"EUR":2900}}}', 'metadatajson' => '{}',
            'redeemedat' => null, 'redeemedpurchaseid' => null, 'revokedat' => null, 'revokedbyuserid' => null,
            'revokereason' => null, 'issuedbyuserid' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $memberid = (int)$DB->insert_record('local_subs_commerce_offer_campaign_member', (object)[
            'campaignid' => $campaignid, 'memberkey' => 'email:original-snapshot-key', 'purchaseid' => null, 'userid' => null,
            'firstname' => 'Wrong', 'lastname' => 'Email', 'email' => 'wrong@example.test', 'evidencejson' => '[]',
            'eligibilitystatus' => 'issued', 'reason' => null, 'existingofferid' => null, 'snapshotselected' => 1,
            'offerid' => $offerid, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $mailid = (int)$DB->insert_record('local_subs_commerce_mail', (object)[
            'mailtype' => 'personal_offer', 'status' => 'queued',
            'idempotencykey' => 'personal-offer:campaign:' . $campaignid . ':member:' . $memberid,
            'purchaseid' => null, 'userid' => null, 'recipientemail' => 'wrong@example.test', 'recipientname' => null,
            'language' => 'fr', 'subject' => null, 'contextjson' => '{}', 'attemptcount' => 3, 'maxattempts' => 5,
            'nextruntime' => $now, 'lasterror' => 'bounce', 'timeprocessing' => null, 'timesent' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return [$campaignid, $memberid, $offerid, $mailid];
    }
}
