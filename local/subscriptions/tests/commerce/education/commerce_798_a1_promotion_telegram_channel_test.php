<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseMailContext;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

/** A1 course-wide Telegram channel, separate from the tutor-group Telegram link. */
final class commerce_798_a1_promotion_telegram_channel_test extends advanced_testcase {
    private const CHANNEL = 'https://t.me/+vSK_36VTpQ82NmEy';

    public function test_a1_course_is_detected_and_a2_is_excluded(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $a1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Курс А1', // Cyrillic А.
            'shortname' => 'LEVEL-BEGINNER-ONE',
        ]);
        $a2 = $this->getDataGenerator()->create_course([
            'fullname' => 'Cours A2',
            'shortname' => 'LEVEL-SECOND-TWO',
        ]);
        $user = $this->getDataGenerator()->create_user();
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'TEST.A1.CHANNEL',
            'type' => 'course_access',
            'status' => 'inactive',
            'name' => 'Test access',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        foreach ([$a1, $a2] as $index => $course) {
            $promotionid = $DB->insert_record('local_subs_commerce_ped_promo', (object)[
                'promotionkey' => 'mail-telegram-' . $index,
                'name' => 'Promotion de rentrée', // Do not infer A1 from cohort label.
                'courseid' => (int)$course->id,
                'status' => 'draft',
                'published' => 0,
                'salesopensat' => null,
                'salesclosesat' => null,
                'startsat' => null,
                'endsat' => null,
                'capacitytotal' => null,
                'createdby' => (int)$user->id,
                'modifiedby' => (int)$user->id,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $DB->insert_record('local_subs_commerce_ped_join', (object)[
                'promotionid' => $promotionid,
                'courseid' => (int)$course->id,
                'userid' => (int)$user->id,
                'productid' => $productid,
                'purchasereference' => 'TEST-A' . ($index + 1) . '-PURCHASE',
                'state' => 'active',
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        $service = CommercePedagogicalPurchaseMailContext::create($DB);
        $a1context = $service->for_purchase('TEST-A1-PURCHASE', (int)$user->id, []);
        $a2context = $service->for_purchase('TEST-A2-PURCHASE', (int)$user->id, []);
        self::assertTrue($a1context['hasentries']);
        self::assertTrue($a1context['hasa1course']);
        self::assertTrue($a2context['hasentries']);
        self::assertFalse($a2context['hasa1course']);
    }

    public function test_course_channel_is_present_in_a1_purchase_mail_only(): void {
        $this->resetAfterTest(true);
        $pedagogy = [
            'hasentries' => true,
            'alreadyowned' => true,
            'hasa1course' => true,
            'entries' => [[
                'promotionname' => 'A1 — Novembre',
                'groupname' => 'Groupe lavande',
                'hasgroup' => true,
                'tutorname' => 'Claire',
                'hastutor' => true,
                'supportlang' => 'fr',
                'hassupportlang' => true,
                'telegramurl' => 'https://t.me/campusfr_lavande',
            ]],
        ];
        $render = static function(array $pedagogy, string $language): string {
            $context = new CommerceMailContext([
                'customer' => ['fullname' => 'Élève'],
                'purchase' => ['reference' => 'TEST-A1-CHANNEL', 'totalformatted' => '100,00 €'],
                'items' => [],
                'links' => ['campus' => 'https://example.test/mon-campus'],
                'pedagogical' => $pedagogy,
            ]);
            $request = new CommerceMailRequest(
                CommerceMailType::PURCHASE_ACCESS,
                new CommerceMailRecipient('test@example.test', 'Élève'),
                $context,
                $language,
                'a1-channel-test',
                1
            );
            return CommerceMailRuntime::template_registry()
                ->get(CommerceMailType::PURCHASE_ACCESS)->render($request)->get_html();
        };
        $html = $render($pedagogy, 'ru');
        self::assertStringContainsString(self::CHANNEL, $html);
        self::assertStringContainsString('https://t.me/campusfr_lavande', $html);

        // Moodle may fall back to English if the RU pack is not installed in PHPUnit.
        $previouslanguage = force_current_language('ru');
        try {
            $expected = get_string('commerce_mail_ped_a1_channel_invite', 'local_subscriptions');
        } finally {
            force_current_language($previouslanguage);
        }
        $htmltext = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        self::assertStringContainsString($expected, $htmltext);

        $pedagogy['hasa1course'] = false;
        $a2html = $render($pedagogy, 'fr');
        self::assertStringNotContainsString(self::CHANNEL, $a2html);
        self::assertStringContainsString('https://t.me/campusfr_lavande', $a2html);
    }
}
