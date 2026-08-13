<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer;

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_campaign_m11_test extends advanced_testcase {
    public function test_replace_policy_revokes_old_offer_and_issues_new_campaign_offer(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'm11@example.test']);
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'M11-TARGET', 'type' => 'digital', 'status' => 'active', 'name' => 'M11 target',
            'description' => null, 'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $old = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm11-old', $productid, 'm11@example.test', CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm11-old-campaign', null, (int)$user->id, null, $now + 3600, [], (int)$user->id
        ));

        $manager = CommercePersonalOfferCampaignManager::create($DB);
        $campaignid = $manager->create_campaign([
            'campaignkey' => 'm11-new-campaign', 'name' => 'M11 new campaign', 'audiencetype' => 'list',
            'targetproductid' => $productid, 'termsversion' => 1,
            'terms' => CommercePersonalOfferTerms::fixed_price(['EUR' => 2500])->get_data(),
            'criteria' => ['list' => 'm11@example.test', 'excludeowned' => false, 'collisionpolicy' => 'replace'],
        ], (int)$user->id);
        $preview = $manager->preview($campaignid, (int)$user->id);
        $this->assertSame(1, $preview['eligible']);
        $manager->create_snapshot($campaignid, (int)$user->id);
        $manager->generate($campaignid, (int)$user->id);

        $oldrecord = $DB->get_record('local_subs_commerce_offer', ['id' => $old->get_offer()->get_id()], '*', MUST_EXIST);
        $this->assertSame('revoked', (string)$oldrecord->status);
        $members = $manager->members($campaignid);
        $this->assertSame('issued', (string)$members[0]->eligibilitystatus);
        $this->assertNotEmpty($members[0]->offerid);
        $this->assertSame((int)$oldrecord->id, (int)$members[0]->existingofferid);
    }

    public function test_resend_policy_reuses_existing_offer_without_creating_another(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'm11-resend@example.test']);
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'M11-RESEND', 'type' => 'digital', 'status' => 'active', 'name' => 'M11 resend',
            'description' => null, 'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $old = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm11-resend-old', $productid, 'm11-resend@example.test', CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm11-resend-old-campaign', null, (int)$user->id, null, $now + 3600, [], (int)$user->id
        ));
        $manager = CommercePersonalOfferCampaignManager::create($DB);
        $campaignid = $manager->create_campaign([
            'campaignkey' => 'm11-resend-new', 'name' => 'M11 resend new', 'audiencetype' => 'list',
            'targetproductid' => $productid, 'termsversion' => 1,
            'terms' => CommercePersonalOfferTerms::fixed_price(['EUR' => 2500])->get_data(),
            'criteria' => ['list' => 'm11-resend@example.test', 'excludeowned' => false, 'collisionpolicy' => 'resend'],
        ], (int)$user->id);
        $manager->preview($campaignid, (int)$user->id);
        $manager->create_snapshot($campaignid, (int)$user->id);
        $manager->generate($campaignid, (int)$user->id);
        $this->assertSame(1, $DB->count_records('local_subs_commerce_offer'));
        $member = $manager->members($campaignid)[0];
        $this->assertSame('replayed', (string)$member->eligibilitystatus);
        $this->assertSame($old->get_offer()->get_id(), (int)$member->offerid);
    }
}
