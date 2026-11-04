<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\group\CommerceStudentClanService;
use local_subscriptions\commerce\education\group\CommerceClanPhotoService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;

/** Security, multi-promotion isolation, and hidden scoreboard behaviour. */
final class commerce_798_my_clan_test extends advanced_testcase {
    private function fixture(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'CLAN-' . strtoupper(random_string(10)),
            'type' => 'course_access', 'status' => 'active',
            'name' => 'Clan test product', 'description' => null, 'metadatajson' => null,
            'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'clan-' . random_string(10),
            'name' => 'Clan test promotion', 'courseid' => (int)$course->id,
            'status' => 'started', 'published' => 1,
            'salesopensat' => null, 'salesclosesat' => null, 'startsat' => null, 'endsat' => null,
            'xpstartsat' => null, 'leaderboardhidehours' => 0,
            'capacitytotal' => null, 'createdby' => null, 'modifiedby' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $g1 = $this->create_group($promotionid, (int)$course->id, 'Les Alpes', 0);
        $g2 = $this->create_group($promotionid, (int)$course->id, 'Les Ecrins', 1);
        $mine = $this->getDataGenerator()->create_user(['firstname' => 'Olga', 'country' => 'FR']);
        $teammate = $this->getDataGenerator()->create_user(['firstname' => 'Liza', 'country' => 'RU']);
        $stranger = $this->getDataGenerator()->create_user(['firstname' => 'Stranger']);
        $this->attach($promotionid, (int)$course->id, $g1, $productid, (int)$mine->id);
        $this->attach($promotionid, (int)$course->id, $g1, $productid, (int)$teammate->id);
        $this->attach($promotionid, (int)$course->id, $g2, $productid, (int)$stranger->id);
        return [$promotionid, (int)$mine->id, (int)$teammate->id, (int)$stranger->id, $g1, $g2];
    }

    private function create_group(int $promotionid, int $courseid, string $name, int $position): int {
        global $DB;
        $g = $this->getDataGenerator()->create_group(['courseid' => $courseid, 'name' => $name]);
        return (int)$DB->insert_record('local_subs_commerce_ped_group', (object)[
            'promotionid' => $promotionid, 'moodlegroupid' => (int)$g->id,
            'productid' => null, 'displayname' => $name, 'position' => $position,
            'tutorid' => null, 'tutorname' => null, 'supportlang' => 'fr',
            'telegramref' => 'https://t.me/test_group', 'levelupxp' => null, 'active' => 1,
            'createdby' => null, 'modifiedby' => null,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
    }

    private function attach(int $promotionid, int $courseid, int $groupid, int $productid, int $userid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid, 'courseid' => $courseid, 'userid' => $userid,
            'productid' => $productid, 'purchasereference' => 'clan-' . random_string(12),
            'state' => 'active', 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => $courseid, 'userid' => $userid,
            'promotionid' => $promotionid, 'profile' => 'promotion_progressive',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_gmem', (object)[
            'groupid' => $groupid, 'userid' => $userid, 'active' => 1,
            'createdby' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
    }

    public function test_only_current_clan_members_are_visible_to_each_other(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        [$promo, $mine, $teammate, $stranger] = $this->fixture();
        $PAGE->set_context(\context_system::instance());
        $service = CommerceStudentClanService::create($DB);
        $data = $service->get_for_user($mine, $promo, $PAGE);
        self::assertNotNull($data);
        self::assertSame('Les Alpes', $data['groupname']);
        self::assertSame(2, $data['membercount']);
        self::assertSame(3, $data['participantcount']);
        self::assertSame(2, $data['groupcount']);
        self::assertCount(2, $data['members']);
        self::assertSame('https://t.me/test_group', $data['telegramurl']);
        self::assertNotEmpty($data['supportlanguage']);
        $names = array_column($data['members'], 'firstname');
        self::assertContains('Olga', $names);
        self::assertContains('Liza', $names);
        self::assertNotContains('Stranger', $names);
        self::assertNull($service->get_for_user($mine, 99999999, $PAGE));
        $this->setUser($this->getDataGenerator()->create_user());
        self::assertNull($service->get_for_user((int)$this->getDataGenerator()->create_user()->id, $promo, $PAGE));
    }

    public function test_revoking_membership_or_promotion_access_fails_closed(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        [$promo, $mine, $teammate] = $this->fixture();
        $PAGE->set_context(\context_system::instance());
        $service = CommerceStudentClanService::create($DB);
        $DB->set_field('local_subs_commerce_ped_gmem', 'active', 0,
            ['userid' => $mine]);
        self::assertNull($service->get_for_user($mine, $promo, $PAGE));
        $DB->set_field('local_subs_commerce_ped_gmem', 'active', 1,
            ['userid' => $mine]);
        $DB->set_field('local_subs_commerce_ped_join', 'state', 'revoked',
            ['userid' => $mine, 'promotionid' => $promo]);
        self::assertNull($service->get_for_user($mine, $promo, $PAGE));
        // Former members are absent from cards even while another member remains active.
        $DB->set_field('local_subs_commerce_ped_join', 'state', 'revoked',
            ['userid' => $teammate, 'promotionid' => $promo]);
    }

    public function test_singular_plural_and_russian_count_forms(): void {
        self::assertSame('one', CommerceStudentClanService::count_form(1, 'fr'));
        self::assertSame('many', CommerceStudentClanService::count_form(2, 'fr'));
        self::assertSame('one', CommerceStudentClanService::count_form(1, 'ru'));
        self::assertSame('few', CommerceStudentClanService::count_form(2, 'ru'));
        self::assertSame('few', CommerceStudentClanService::count_form(24, 'ru'));
        self::assertSame('many', CommerceStudentClanService::count_form(5, 'ru'));
        self::assertSame('many', CommerceStudentClanService::count_form(11, 'ru'));
        self::assertSame('many', CommerceStudentClanService::count_form(112, 'ru'));
        self::assertSame('one', CommerceStudentClanService::count_form(121, 'ru'));
    }

    public function test_explicit_dates_and_last_next_scheduled_sections(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        [$promo, $mine] = $this->fixture();
        $PAGE->set_context(\context_system::instance());
        $p = $DB->get_record('local_subs_commerce_ped_promo', ['id' => $promo], 'id,courseid', MUST_EXIST);
        $now = time();
        $DB->set_field('local_subs_commerce_ped_promo', 'startsat', $now - 7 * DAYSECS, ['id' => $promo]);
        $DB->set_field('local_subs_commerce_ped_promo', 'endsat', $now + 7 * DAYSECS, ['id' => $promo]);
        $sections = [];
        foreach ([71 => 'Bienvenue', 72 => 'À venir'] as $number => $name) {
            $sections[] = (int)$DB->insert_record('course_sections', (object)[
                'course' => (int)$p->courseid, 'section' => $number, 'name' => $name,
                'summary' => '', 'summaryformat' => 1, 'sequence' => '', 'visible' => 1,
            ]);
        }
        foreach ($sections as $i => $sectionid) {
            $DB->insert_record('local_subs_commerce_ped_cal', (object)[
                'promotionid' => $promo, 'itemtype' => 'course_section',
                'itemid' => $sectionid, 'position' => $i,
                'unlocksat' => $now + ($i === 0 ? -1 : 1) * DAYSECS,
                'createdby' => null, 'modifiedby' => null,
                'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        $data = CommerceStudentClanService::create($DB)->get_for_user($mine, $promo, $PAGE, $now);
        self::assertNotNull($data);
        self::assertTrue($data['haslastlesson']);
        self::assertTrue($data['hasnextlesson']);
        self::assertSame('Bienvenue', $data['lastlesson']);
        self::assertSame('À venir', $data['nextlesson']);
        self::assertStringContainsString('section=71', $data['lastlessonurl']);
        self::assertNotSame(get_string('commerce_my_clan_not_set', 'local_subscriptions'), $data['startdate']);
        self::assertNotSame(get_string('commerce_my_clan_not_set', 'local_subscriptions'), $data['enddate']);
    }

    public function test_clan_photo_is_private_and_membership_driven(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        [$promo, $mine, $mate, $stranger, $minegroup, $othergroup] = $this->fixture();
        $PAGE->set_context(\context_system::instance());
        $courseid = (int)$DB->get_field('local_subs_commerce_ped_promo', 'courseid', ['id' => $promo]);
        $photo = CommerceClanPhotoService::create($DB);
        self::assertTrue($photo->may_view($minegroup, $mine, false));
        self::assertFalse($photo->may_view($minegroup, $stranger, false));
        self::assertFalse($photo->may_view($othergroup, $mine, false));
        self::assertTrue($photo->may_view($othergroup, $mine, true)); // CRM only.
        self::assertSame('', $photo->url($minegroup, $courseid));

        // Simulate a CRM-uploaded file without using an HTTP request in PHPUnit.
        $contextid = \context_course::instance($courseid)->id;
        get_file_storage()->create_file_from_string((object)[
            'contextid' => $contextid, 'component' => 'local_subscriptions',
            'filearea' => 'clanphoto', 'itemid' => $minegroup,
            'filepath' => '/', 'filename' => 'cover.png',
            'mimetype' => 'image/png', 'userid' => $mine,
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l0kAAAAASUVORK5CYII='));
        self::assertStringContainsString('clan_photo.php', $photo->url($minegroup, $courseid));
        $data = CommerceStudentClanService::create($DB)->get_for_user($mine, $promo, $PAGE);
        self::assertTrue($data['hasclanphoto']);
    }

    public function test_hidden_promotion_never_reveals_other_scores_or_ranking(): void {
        global $DB, $PAGE;
        $this->resetAfterTest(true);
        [$promo, $mine, $teammate] = $this->fixture();
        $PAGE->set_context(\context_system::instance());
        $now = time();
        CommercePedagogicalXpService::create($DB)->record_once($promo, $teammate,
            'block_xp', 'points_increased', 'clan-gain', 42, $now, $now);
        $DB->set_field('local_subs_commerce_ped_promo', 'xpstartsat', $now - HOURSECS,
            ['id' => $promo]);
        $DB->set_field('local_subs_commerce_ped_promo', 'leaderboardhidehours', 48,
            ['id' => $promo]);
        $data = CommerceStudentClanService::create($DB)->get_for_user($mine, $promo, $PAGE, $now);
        self::assertNotNull($data);
        self::assertTrue($data['ishidden']);
        self::assertArrayNotHasKey('grouppoints', $data);
        self::assertArrayNotHasKey('grouprank', $data);
        self::assertArrayNotHasKey('points', $data['members'][0]);
        self::assertArrayNotHasKey('points', $data['members'][1]);
    }
}
