<?php
/**
 * D2.16.3: atomic published Reader revision pointer - test in Moodle PHPUnit only.
 * @coversNothing
 */
declare(strict_types=1);
namespace local_campusreader;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\library\ReaderCommercePublicationService as Editions;
use local_subscriptions\crm\library\ReaderAnthologySnapshotContract as Snapshot;
use local_campusreader\local\publication_access as Access;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

final class publication_revision_swap_test extends \advanced_testcase {
    public function test_calendar_swaps_with_no_downtime_and_can_roll_back_to_old_revision(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$user, $id, $old, $new] = $this->fixture('calendar', 24);
        $this->setUser($user);
        $before = Editions::get($id);
        self::assertTrue(Access::can_view($before));
        self::assertTrue(Access::chapter_allowed($before, 1));
        self::assertFalse(Access::chapter_allowed($before, 24));
        $oldtitle = Access::frozen($before)['chapters'][0]['title'];
        Editions::swap_published_revision($id, $old, $new);
        $after = Editions::get($id);
        self::assertSame($new, (int)$after->revisionid);
        foreach (['slug', 'kind', 'anthologyid', 'collectionid', 'calendarjson',
            'productsku', 'status', 'timecreated', 'publishedat'] as $field) {
            self::assertSame((string)$before->{$field}, (string)$after->{$field}, $field);
        }
        self::assertTrue(Access::can_view($after));
        self::assertTrue(Access::chapter_allowed($after, 1));
        self::assertFalse(Access::chapter_allowed($after, 24));
        self::assertNotSame($oldtitle, Access::frozen($after)['chapters'][0]['title']);
        self::assertSame(1, $DB->count_records('local_subs_commerce_grant', [
            'productsku' => $after->productsku, 'beneficiaryuserid' => $user->id,
        ]));
        self::assertSame('approved', $DB->get_field('local_cr_anthrev', 'state', ['id' => $old]));
        Editions::swap_published_revision($id, $new, $old);
        self::assertSame($old, (int)Editions::get($id)->revisionid);
        self::assertTrue(Access::can_view(Editions::get($id)));
    }

    public function test_anthology_swap_keeps_same_publication_and_product(): void {
        $this->resetAfterTest(true);
        [$user, $id, $old, $new] = $this->fixture('anthology', 2);
        $this->setUser($user);
        $before = Editions::get($id);
        Editions::swap_published_revision($id, $old, $new);
        $after = Editions::get($id);
        self::assertSame('published', $after->status);
        self::assertSame($before->slug, $after->slug);
        self::assertSame($before->productsku, $after->productsku);
        self::assertSame($before->publishedat, $after->publishedat);
        self::assertTrue(Access::can_view($after));
        self::assertSame($new, (int)$after->revisionid);
    }

    public function test_calendar_mapping_rejects_changed_count_duplicates_and_dates(): void {
        $this->resetAfterTest(true);
        $valid = $this->snapshot(2, 'Valid');
        $calendar = $this->calendar(2);
        Editions::assert_swap_calendar_compatible($calendar, $valid);
        $cases = [];
        $cases[] = [$calendar, $this->snapshot(3, 'Wrong count')];
        $copy = json_decode($calendar, true, 512, JSON_THROW_ON_ERROR);
        $copy['slots'][1]['chapter'] = 1;
        $cases[] = [json_encode($copy, JSON_THROW_ON_ERROR), $valid];
        $copy['slots'][1]['chapter'] = 2;
        $copy['slots'][1]['unlockat'] = $copy['slots'][0]['unlockat'];
        $cases[] = [json_encode($copy, JSON_THROW_ON_ERROR), $valid];
        $copy['slots'][1]['unlockat'] += 86400;
        $copy['libraryafter'] = $copy['slots'][1]['unlockat'];
        $cases[] = [json_encode($copy, JSON_THROW_ON_ERROR), $valid];
        foreach ($cases as [$badcalendar, $snapshot]) {
            try {
                Editions::assert_swap_calendar_compatible($badcalendar, $snapshot);
                self::fail('Unsafe calendar swap accepted');
            } catch (\invalid_parameter_exception $e) {
                self::assertNotEmpty($e->getMessage());
            }
        }
    }

    public function test_stale_revision_form_fails_before_any_write(): void {
        $this->resetAfterTest(true);
        [$user, $id, $old, $new] = $this->fixture('calendar', 2);
        $this->setUser($user);
        try {
            Editions::swap_published_revision($id, $old + 1000, $new);
            self::fail('Stale form accepted');
        } catch (\invalid_parameter_exception $e) {
            self::assertNotEmpty($e->getMessage());
        }
        self::assertSame($old, (int)Editions::get($id)->revisionid);
    }

    private function fixture(string $kind, int $count): array {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $idpart = bin2hex(random_bytes(6));
        $anthologyid = (int)$DB->insert_record('local_cr_anthology', (object)[
            'slug' => 'qa-anth-d2163-' . $idpart, 'title' => 'QA Test',
            'subtitle' => '', 'status' => 'draft', 'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $old = $this->revision($anthologyid, 1, $this->snapshot($count, 'Fictif'));
        $new = $this->revision($anthologyid, 2, $this->snapshot($count, 'Réel'));
        $slug = 'qa-swap-d2163-' . $idpart;
        $sku = 'QA.READER.D2163.' . strtoupper($idpart);
        $id = (int)$DB->insert_record(Editions::TABLE, (object)[
            'slug' => $slug, 'kind' => $kind, 'anthologyid' => $anthologyid,
            'revisionid' => $old, 'collectionid' => $kind === 'calendar' ? 1 : 0,
            'calendarjson' => $kind === 'calendar' ? $this->calendar($count) : '',
            'productsku' => $sku, 'status' => 'published',
            'timecreated' => $now - 100, 'timemodified' => $now - 90,
            'publishedat' => $now - 80,
        ]);
        $catalogue = (new CommerceCatalogFactory($DB))->admin();
        $catalogue->save_product(new CommerceProduct($sku, CommerceProductType::SERVICE,
            CommerceProductStatus::ACTIVE, 'QA D2.16.3'));
        $catalogue->replace_definition($sku, [], [new CommerceProductEntitlementDefinition(
            $sku, 'reader_access', Access::key($slug), null, 1, []
        )]);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'gr-d2163-' . $idpart,
            'idempotencykey' => 'd2163-' . $idpart,
            'purchasereference' => 'qa-d2163-' . $idpart,
            'itemreference' => $sku, 'productsku' => $sku,
            'type' => 'reader_access', 'resourcekey' => Access::key($slug),
            'quantity' => 1, 'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => (string)$user->email,
            'validfrom' => $now - 100, 'validuntil' => null,
            'status' => 'active', 'configurationjson' => '{}', 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return [$user, $id, $old, $new];
    }

    private function revision(int $anthologyid, int $revno, array $payload): int {
        global $DB;
        $now = time();
        $json = Snapshot::encode($payload);
        return (int)$DB->insert_record('local_cr_anthrev', (object)[
            'anthologyid' => $anthologyid, 'revno' => $revno,
            'digest' => hash('sha256', $json), 'snapshotjson' => $json,
            'state' => 'approved', 'createdby' => (int)get_admin()->id,
            'timecreated' => $now, 'approvedby' => (int)get_admin()->id,
            'approvedat' => $now,
        ]);
    }

    private function snapshot(int $count, string $label): array {
        $chapters = [];
        for ($i = 1; $i <= $count; $i++) {
            $chapters[] = ['position' => $i, 'bookid' => ($label === 'Fictif' ? 100 : 200) + $i,
                'slug' => 'story-' . $i, 'title' => $label . ' · Histoire ' . $i,
                'subtitle' => '', 'pages' => [[
                    'index' => 0, 'kind' => 'text', 'title' => 'Page',
                    'paragraphs' => ['Un récit de vérification.'],
                ]]];
        }
        return ['schema' => 1, 'anthology' => [
            'slug' => 'qa-edition-d2163', 'title' => $label, 'subtitle' => '',
        ], 'chapters' => $chapters, 'media' => []];
    }

    private function calendar(int $count): string {
        $now = time();
        $slots = [];
        for ($i = 1; $i <= $count; $i++) {
            $slots[] = ['position' => $i, 'chapter' => $i,
                'unlockat' => $now + ($i - 2) * 86400,
                'teaser' => 'À venir', 'layoutsize' => 'normal'];
        }
        return json_encode(['schema' => 1, 'timezone' => 'Europe/Paris',
            'libraryafter' => $now + ($count + 1) * 86400,
            'slots' => $slots], JSON_THROW_ON_ERROR);
    }
}
