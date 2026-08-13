<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferCampaignMailPreviewService;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_campaign_email_m3d_test extends advanced_testcase {
    public function test_preissue_preview_uses_campaign_terms_without_creating_offer(): void {
        global $DB;
        $this->resetAfterTest(true);
        $productid = $this->create_product('M3D.PREVIEW', 5500, 549000);
        $campaignid = $this->create_campaign($productid, 'm3d-preview', ['EUR'=>3000,'RUB'=>299000]);
        $this->insert_content($campaignid, 'ru');

        $before = $DB->count_records('local_subs_commerce_offer');
        $message = CommercePersonalOfferCampaignMailPreviewService::create($DB)->preview($campaignid, 'ru', 'Natalia');
        $after = $DB->count_records('local_subs_commerce_offer');

        $this->assertSame($before, $after);
        $this->assertStringContainsString('Natalia', $message->get_subject());
        $this->assertStringContainsString('Здравствуйте, Natalia!', $message->get_html());
        $this->assertStringContainsString('₽', $message->get_html());
        $this->assertStringContainsString('2', $message->get_html());
        $this->assertStringContainsString('990', $message->get_html());
        $this->assertStringContainsString('currency=RUB', html_entity_decode($message->get_html()));
        $this->assertStringContainsString('campaign_email_preview.php', html_entity_decode($message->get_html()));
    }

    public function test_preissue_preview_falls_back_to_french_content_but_keeps_english_currency_preference(): void {
        global $DB;
        $this->resetAfterTest(true);
        $productid = $this->create_product('M3D.FALLBACK', 5500, 549000);
        $campaignid = $this->create_campaign($productid, 'm3d-fallback', ['EUR'=>3000,'RUB'=>299000]);
        $this->insert_content($campaignid, 'fr', 'Offre {{firstname}}', 'Bonjour {{firstname}}');

        $message = CommercePersonalOfferCampaignMailPreviewService::create($DB)->preview($campaignid, 'en', 'Alice');
        $this->assertSame('Offre Alice', $message->get_subject());
        $this->assertStringContainsString('Bonjour Alice', $message->get_html());
        $this->assertStringContainsString('currency=EUR', html_entity_decode($message->get_html()));
    }

    public function test_campaign_member_snapshot_supplies_identity_for_legacy_only_recipient(): void {
        global $DB;
        $this->resetAfterTest(true);
        $productid = $this->create_product('M12.LEGACY.IDENTITY', 3900, 390000);
        $campaignid = $this->create_campaign($productid, 'm12-legacy-identity', ['EUR'=>2900,'RUB'=>290000]);
        $this->insert_content($campaignid, 'fr', 'Offre {{firstname}}', 'Bonjour {{firstname}}');
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm12-legacy-identity', $productid, 'rgrg01099@gmail.com',
            CommercePersonalOfferTerms::fixed_price(['EUR'=>2900,'RUB'=>290000]),
            'm12-test', null, null, time()-60, time()+DAYSECS
        ));
        $offerid = (int)$issued->get_offer()->get_id();
        $now = time();
        $memberid = (int)$DB->insert_record('local_subs_commerce_offer_campaign_member', (object)[
            'campaignid'=>$campaignid, 'memberkey'=>'email:m12-kasia', 'purchaseid'=>null, 'userid'=>null,
            'firstname'=>'Кася', 'lastname'=>'Иванова', 'email'=>'rgrg01099@gmail.com', 'evidencejson'=>'[]',
            'eligibilitystatus'=>'issued', 'reason'=>null, 'existingofferid'=>null, 'snapshotselected'=>1,
            'offerid'=>$offerid, 'timecreated'=>$now, 'timemodified'=>$now,
        ]);

        $mail = CommercePersonalOfferMailService::create($DB)->queue_offer($offerid, $campaignid, $memberid);
        $context = json_decode((string)$mail->contextjson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('rgrg01099@gmail.com', (string)$mail->recipientemail);
        $this->assertSame('Кася Иванова', (string)$mail->recipientname);
        $this->assertSame('Кася', $context['customer']['firstname']);
        $this->assertSame('Кася Иванова', $context['customer']['fullname']);
        $preview = (new CommerceMailAdminService())->preview((int)$mail->id);
        $this->assertSame('Offre Кася', $preview['subject']);
        $this->assertStringContainsString('Bonjour Кася', $preview['html']);
    }

    public function test_real_queue_and_admin_resend_keep_campaign_context_for_renderer(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['firstname'=>'Nata','email'=>'m3d-resend@example.test']);
        $productid = $this->create_product('M3D.RESEND', 5500, 549000);
        $campaignid = $this->create_campaign($productid, 'm3d-resend', ['EUR'=>3000,'RUB'=>299000]);
        $this->insert_content($campaignid, 'fr', 'Campagne {{firstname}}', 'Bonjour {{firstname}}');
        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3d-resend', $productid, $user->email,
            CommercePersonalOfferTerms::fixed_price(['EUR'=>3000,'RUB'=>299000]),
            'm3d-test', null, (int)$user->id, time()-60, time()+DAYSECS
        ));
        $mail = CommercePersonalOfferMailService::create($DB)->queue_offer((int)$issued->get_offer()->get_id(), $campaignid, 123);
        $context = json_decode((string)$mail->contextjson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($campaignid, (int)$context['personaloffer']['campaignid']);
        $preview = (new CommerceMailAdminService())->preview((int)$mail->id);
        $this->assertSame('Campagne Nata', $preview['subject']);

        // Resend is only allowed after sent. Mark the fixture as sent to exercise the existing admin path.
        $DB->set_field('local_subs_commerce_mail', 'status', 'sent', ['id'=>(int)$mail->id]);
        $resent = (new CommerceMailAdminService())->resend((int)$mail->id, (int)$user->id);
        $resentcontext = json_decode((string)$resent->contextjson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($campaignid, (int)$resentcontext['personaloffer']['campaignid']);
        $resentpreview = (new CommerceMailAdminService())->preview((int)$resent->id);
        $this->assertSame('Campagne Nata', $resentpreview['subject']);
    }

    private function create_product(string $sku, int $eurminor, int $rubminor): int {
        global $DB; $now=time();
        $id=(int)$DB->insert_record('local_subs_commerce_product',(object)[
            'sku'=>$sku,'type'=>'digital','status'=>'active','name'=>$sku.' product','description'=>'','metadatajson'=>'{}',
            'availablefrom'=>null,'availableuntil'=>null,'timecreated'=>$now,'timemodified'=>$now,
        ]);
        foreach(['EUR'=>$eurminor,'RUB'=>$rubminor] as $currency=>$minor){
            $DB->insert_record('local_subs_commerce_prod_price',(object)[
                'productid'=>$id,'currency'=>$currency,'amountminor'=>$minor,'provider'=>null,'providerpriceid'=>null,
                'active'=>1,'metadatajson'=>'{}','timecreated'=>$now,'timemodified'=>$now,
            ]);
        }
        return $id;
    }

    private function create_campaign(int $productid, string $key, array $amounts): int {
        global $DB; $now=time();
        return (int)$DB->insert_record('local_subs_commerce_offer_campaign',(object)[
            'campaignkey'=>$key,'name'=>$key,'audiencetype'=>'list','sourceproductsku'=>null,'targetproductid'=>$productid,
            'termsversion'=>1,'termsjson'=>json_encode(CommercePersonalOfferTerms::fixed_price($amounts)->get_data(),JSON_THROW_ON_ERROR),
            'criteriajson'=>'{}','validfrom'=>$now,'expiresat'=>$now+DAYSECS,'status'=>'draft','timecreated'=>$now,'timemodified'=>$now,
            'usercreated'=>null,'usermodified'=>null,
        ]);
    }

    private function insert_content(int $campaignid, string $language, string $subject='Вершина для {{firstname}}', string $body='Здравствуйте, {{firstname}}!'): void {
        global $DB; $now=time();
        $DB->insert_record('local_subs_commerce_offer_campaign_email_content',(object)[
            'campaignid'=>$campaignid,'language'=>$language,'subject'=>$subject,'body'=>$body,'bodyformat'=>(int)FORMAT_PLAIN,
            'ctalabel'=>'Начать','closing'=>'До встречи','closingformat'=>(int)FORMAT_PLAIN,
            'timecreated'=>$now,'timemodified'=>$now,'usercreated'=>0,'usermodified'=>0,
        ]);
    }
}
