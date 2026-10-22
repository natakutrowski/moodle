<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalSectionHierarchy;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseMailContext;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

/** DEV regression checks for major-section calendars and cohort welcome context. */
final class commerce_798_a1_promotion_major_sections_mail_test extends advanced_testcase {
    public function test_moodle_subsection_inherits_parent_unlock(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $parent = $DB->get_record('course_sections',
            ['course' => (int)$course->id, 'section' => 1], '*', MUST_EXIST);
        $second = $DB->get_record('course_sections',
            ['course' => (int)$course->id, 'section' => 2], '*', MUST_EXIST);

        $this->getDataGenerator()->create_module('subsection', [
            'course' => (int)$course->id,
            'section' => 1,
            'name' => 'Lesson 1 — grammar',
        ]);
        $delegated = $DB->get_records('course_sections', [
            'course' => (int)$course->id,
            'component' => 'mod_subsection',
        ]);
        self::assertCount(1, $delegated);
        $child = reset($delegated);

        $hierarchy = CommercePedagogicalSectionHierarchy::create($DB);
        self::assertSame(
            (int)$parent->id,
            $hierarchy->subsection_parents((int)$course->id)[(int)$child->id]
        );
        $unlocked = $hierarchy->with_unlocked_subsections(
            (int)$course->id,
            [(int)$parent->id]
        );
        self::assertContains((int)$parent->id, $unlocked);
        self::assertContains((int)$child->id, $unlocked);
        self::assertNotContains((int)$second->id, $unlocked);
    }

    public function test_mail_context_only_mentions_purchased_active_promotion_and_owner_join(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $promotionid = $DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'a1-mail-context',
            'name' => 'A1 — Novembre',
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
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'TEST.PROMO.MAIL',
            'type' => 'course_access',
            'status' => 'inactive',
            'name' => 'Test A1',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid,
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'productid' => $productid,
            'purchasereference' => 'TEST-PURCHASE-MAIL',
            'state' => 'active',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $moodlegroup = $this->getDataGenerator()->create_group([
            'courseid' => (int)$course->id,
            'name' => 'A1 Lavande',
        ]);
        $groupid = $DB->insert_record('local_subs_commerce_ped_group', (object)[
            'promotionid' => $promotionid,
            'moodlegroupid' => (int)$moodlegroup->id,
            'productid' => $productid,
            'displayname' => 'Groupe lavande',
            'position' => 0,
            'tutorid' => null,
            'tutorname' => 'Claire',
            'supportlang' => 'fr',
            'telegramref' => 'https://t.me/campusfr_lavande',
            'levelupxp' => null,
            'active' => 1,
            'createdby' => (int)$user->id,
            'modifiedby' => (int)$user->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_gmem', (object)[
            'groupid' => $groupid,
            'userid' => (int)$user->id,
            'active' => 1,
            'createdby' => (int)$user->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $context = CommercePedagogicalPurchaseMailContext::create($DB)->for_purchase(
            'TEST-PURCHASE-MAIL',
            (int)$user->id,
            [(object)['metadata' => ['operation' => 'promotion_join']]]
        );
        self::assertTrue($context['hasentries']);
        self::assertTrue($context['alreadyowned']);
        self::assertSame('A1 — Novembre', $context['entries'][0]['promotionname']);
        self::assertTrue($context['entries'][0]['hasgroup']);
        self::assertSame('Groupe lavande', $context['entries'][0]['groupname']);
        self::assertSame('Claire', $context['entries'][0]['tutorname']);
        self::assertSame('fr', $context['entries'][0]['supportlang']);
        self::assertSame('https://t.me/campusfr_lavande', $context['entries'][0]['telegramurl']);

        $othercontext = CommercePedagogicalPurchaseMailContext::create($DB)->for_purchase(
            'TEST-PURCHASE-MAIL', (int)$other->id,
            [(object)['metadata' => ['operation' => 'promotion_join']]]
        );
        self::assertFalse($othercontext['hasentries']);
        self::assertFalse($othercontext['alreadyowned']);
    }
    public function test_promotion_access_mail_shows_group_telegram_tutor_and_owner_message(): void {
        $this->resetAfterTest(true);
        $context = new CommerceMailContext([
            'customer' => ['fullname' => 'Élève A1'],
            'purchase' => ['reference' => 'CFR-A1-TEST', 'totalformatted' => '100,00 €'],
            'items' => [],
            'links' => ['campus' => 'https://example.test/mon-campus'],
            'pedagogical' => [
                'hasentries' => true,
                'alreadyowned' => true,
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
            ],
        ]);
        $request = new CommerceMailRequest(
            CommerceMailType::PURCHASE_ACCESS,
            new CommerceMailRecipient('test@example.test', 'Élève A1'),
            $context,
            'fr',
            'a1-mail-context-test',
            1
        );
        $message = CommerceMailRuntime::template_registry()
            ->get(CommerceMailType::PURCHASE_ACCESS)
            ->render($request);

        // In PHPUnit, French is not necessarily installed in moodledata/lang.
        // Moodle can legitimately fall back to en even for a 'fr' mail request.
        // Compare with Moodle's actual string resolution for that language.
        $previouslanguage = force_current_language($request->get_language());
        try {
            $expectedsubject = get_string('commerce_mail_ped_subject', 'local_subscriptions');
            $expectedowner = get_string('commerce_mail_ped_existing_owner', 'local_subscriptions');
        } finally {
            force_current_language($previouslanguage);
        }

        self::assertSame($expectedsubject, $message->get_subject());
        self::assertStringContainsString('Groupe lavande', $message->get_html());
        self::assertStringContainsString('Claire', $message->get_html());
        self::assertStringContainsString('https://t.me/campusfr_lavande', $message->get_html());
        $htmltext = html_entity_decode(strip_tags($message->get_html()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        self::assertStringContainsString($expectedowner, $htmltext);
    }

}
