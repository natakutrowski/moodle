<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomAliasConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSlugService;

/** 7.98E.7.3 — short Showroom aliases redirect to canonical language routes. */
final class commerce_showroom_short_alias_798e73_test extends \advanced_testcase {
    public function test_a1_seed_adds_short_alias_and_keeps_language_specific_canonical_slugs(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $showroomid = $service->ensure(2);
        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get($showroomid);
        self::assertNotNull($showroom);

        // Simulate the DEV idea of temporarily using /a1 in every locale.
        $repo->save([
            'id' => $showroomid,
            'showroomkey' => (string)$showroom->showroomkey,
            'status' => (string)$showroom->status,
            'name' => (string)$showroom->name,
            'template' => (string)$showroom->template,
            'slugfr' => 'a1',
            'slugen' => 'a1',
            'slugru' => 'a1',
            'titlekey' => (string)$showroom->titlekey,
            'descriptionkey' => (string)$showroom->descriptionkey,
            'productsjson' => (string)$showroom->productsjson,
            'settingsjson' => (string)$showroom->settingsjson,
        ], 2);

        $result = $service->apply_short_alias_v1(2);
        self::assertSame(1, $result['aliasset']);
        self::assertSame(3, $result['canonicalslugsnormalised']);

        $showroom = $repo->get($showroomid);
        self::assertNotNull($showroom);
        self::assertSame('draft', (string)$showroom->status);
        self::assertSame('francais-a1', (string)$showroom->slugfr);
        self::assertSame('french-a1', (string)$showroom->slugen);
        self::assertSame('francuzskij-a1', (string)$showroom->slugru);
        self::assertSame(
            'a1',
            CommerceShowroomAliasConfig::from_settings_json((string)$showroom->settingsjson)
        );

        $again = $service->apply_short_alias_v1(2);
        self::assertSame(0, $again['aliasset']);
        self::assertSame(0, $again['canonicalslugsnormalised']);
    }

    public function test_alias_resolves_to_requested_language_and_falls_back_to_french(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $settings = CommerceShowroomAliasConfig::merge_into_settings_json('{}', 'a1');
        $repo->save([
            'showroomkey' => 'a1-alias-test',
            'status' => 'published',
            'name' => 'A1',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'francais-a1',
            'slugen' => 'french-a1',
            'slugru' => 'francuzskij-a1',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => $settings,
        ], 2);

        $service = new CommerceShowroomSlugService($DB);
        self::assertSame([
            'showroomkey' => 'a1-alias-test',
            'language' => 'fr',
            'slug' => 'francais-a1',
        ], $service->resolve_published_short_alias('a1', 'fr'));
        self::assertSame('french-a1', $service->resolve_published_short_alias('a1', 'en_US')['slug']);
        self::assertSame('francuzskij-a1', $service->resolve_published_short_alias('/A1/', 'ru')['slug']);
        self::assertSame('francais-a1', $service->resolve_published_short_alias('a1', 'de')['slug']);
        self::assertNull($service->resolve_published_short_alias('unknown', 'fr'));
    }

    public function test_alias_only_resolves_for_published_showrooms(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $repo->save([
            'showroomkey' => 'draft-a1-alias',
            'status' => 'draft',
            'name' => 'Draft A1',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'draft-francais-a1',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => CommerceShowroomAliasConfig::merge_into_settings_json('{}', 'a1'),
        ], 2);

        self::assertNull((new CommerceShowroomSlugService($DB))
            ->resolve_published_short_alias('a1', 'fr'));
    }

    public function test_publication_validation_rejects_alias_collisions_and_redirect_loops(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $repo->save([
            'showroomkey' => 'published-alias-owner',
            'status' => 'published',
            'name' => 'Published',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'published-canonical',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => CommerceShowroomAliasConfig::merge_into_settings_json('{}', 'a1'),
        ], 2);

        $secondid = $repo->save([
            'showroomkey' => 'second-showroom',
            'status' => 'draft',
            'name' => 'Second',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'a1',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $slugservice = new CommerceShowroomSlugService($DB);
        try {
            $slugservice->assert_publishable_slugs($repo->get($secondid));
            self::fail('Canonical slug must collide with another published short alias.');
        } catch (\moodle_exception $exception) {
            self::assertSame('commerce_showroom_publish_slug_conflict', $exception->errorcode);
        }

        $loopid = $repo->save([
            'showroomkey' => 'loop-showroom',
            'status' => 'draft',
            'name' => 'Loop',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'same-route',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => CommerceShowroomAliasConfig::merge_into_settings_json('{}', 'same-route'),
        ], 2);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(
            get_string('commerce_showroom_publish_slug_conflict', 'local_subscriptions', 'same-route')
        );
        $slugservice->assert_publishable_slugs($repo->get($loopid));
    }

    public function test_imported_showroom_clears_short_alias_together_with_canonical_slugs(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $sourceid = $repo->save([
            'showroomkey' => 'export-source',
            'status' => 'draft',
            'name' => 'Export source',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'source-fr',
            'slugen' => 'source-en',
            'slugru' => 'source-ru',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => CommerceShowroomAliasConfig::merge_into_settings_json('{}', 'short-source'),
        ], 2);
        $repo->save_block($sourceid, [
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $packages = new CommerceShowroomPackageService($repo);
        $export = $packages->export($sourceid);
        $importid = $packages->import(json_encode($export, JSON_THROW_ON_ERROR), 2);
        $imported = $repo->get($importid);

        self::assertNotNull($imported);
        self::assertSame('', (string)$imported->slugfr);
        self::assertSame('', (string)$imported->slugen);
        self::assertSame('', (string)$imported->slugru);
        self::assertSame('', CommerceShowroomAliasConfig::from_settings_json((string)$imported->settingsjson));
    }

    public function test_router_checks_short_alias_before_canonical_showroom_and_uses_temporary_redirect(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/public_router.php');

        $aliaspos = strpos($source, 'resolve_published_short_alias(');
        $canonicalpos = strpos($source, 'find_published_showroom_key($slug)');
        self::assertNotFalse($aliaspos);
        self::assertNotFalse($canonicalpos);
        self::assertLessThan($canonicalpos, $aliaspos);
        self::assertStringContainsString("header('Location: ' . \$target->out(false), true, 302);", $source);
        self::assertStringContainsString("unset(\n                \$queryparams['route']", $source);
        self::assertStringContainsString("optional_param('lang', '', PARAM_LANG)", $source);
    }

    public function test_seo_admin_exposes_generic_short_alias_field(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/showrooms/edit.php'
        );
        self::assertStringContainsString("name' => 'shortalias'", $source);
        self::assertStringContainsString('CommerceShowroomAliasConfig::merge_into_settings_json', $source);
        self::assertStringContainsString('commerce_showroom_short_alias_title', $source);
    }
}
